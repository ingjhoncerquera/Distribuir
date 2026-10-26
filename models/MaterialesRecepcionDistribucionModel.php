<?php
declare(strict_types=1);

class MaterialesRecepcionDistribucionModel
{
    private PDO $db;
    public function __construct(?PDO $db = null) {
        if ($db instanceof PDO) { $this->db = $db; return; }
        $database = new Database(); $this->db = $database->conectar();
    }

    public function profesionalUsuario(int $usuario): ?int {
        $q=$this->db->prepare("SELECT pr.id FROM usuarios u JOIN personas p ON p.id=u.id_persona JOIN profesionales pr ON pr.id_persona=p.id WHERE u.id=:u AND u.estado='activo' AND p.estado='activo' AND pr.estado='activo' LIMIT 1");
        $q->execute([':u'=>$usuario]); $v=$q->fetchColumn(); return $v===false?null:(int)$v;
    }

    public function solicitudes(int $coordinador, bool $admin): array {
        $scope=$admin?'':' AND pr.id_coordinador=:coord';
        $sql="SELECT s.id id_solicitud,s.numero_solicitud,s.titulo,s.estado,pa.nombre_area,pr.anio,pr.nombre nombre_proceso,CONCAT(p.nombres,' ',p.apellidos) solicitante
        FROM materiales_solicitudes s JOIN materiales_proceso_areas pa ON pa.id=s.id_proceso_area
        JOIN materiales_procesos pr ON pr.id=pa.id_proceso JOIN profesionales pro ON pro.id=s.id_profesional_solicitante
        JOIN personas p ON p.id=pro.id_persona WHERE s.estado='avalada' $scope ORDER BY pr.anio DESC,s.id DESC";
        $q=$this->db->prepare($sql); $q->execute($admin?[]:[':coord'=>$coordinador]); $rows=$q->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as &$r) $r['detalles']=$this->detalles((int)$r['id_solicitud']); unset($r); return $rows;
    }

    

private function detalles(int $solicitud): array
{
    $sql = "SELECT
                d.id AS id_detalle,
                d.id_solicitud,
                d.id_material,
                d.id_ficha AS id_ficha_directa,
                d.cantidad_solicitada,
                d.unidad_medida,
                d.observaciones,
                m.codigo_unspsc,
                m.descripcion,

                COALESCE((
                    SELECT SUM(rd.cantidad_recibida)
                    FROM materiales_recepcion_detalle rd
                    WHERE rd.id_detalle_solicitud = d.id
                ), 0) AS recibido,

                COALESCE((
                    SELECT SUM(dd.cantidad_distribuida)
                    FROM materiales_distribucion_detalle dd
                    WHERE dd.id_detalle_solicitud = d.id
                ), 0) AS distribuido

            FROM materiales_solicitud_detalle d

            LEFT JOIN materiales_catalogo m
                ON m.id = d.id_material

            WHERE d.id_solicitud = :s

            ORDER BY d.id";

    $q = $this->db->prepare($sql);
    $q->execute([':s' => $solicitud]);

    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $cantidadSolicitada = (float) $r['cantidad_solicitada'];
        $cantidadRecibida = (float) $r['recibido'];
        $cantidadDistribuida = (float) $r['distribuido'];

        // Saldo general del detalle.
        $r['pendiente'] = max(
            0,
            $cantidadSolicitada - $cantidadRecibida
        );

        $r['disponible'] = max(
            0,
            $cantidadRecibida - $cantidadDistribuida
        );

        // Obtener las fichas autorizadas para este detalle.
        $r['fichas'] = $this->fichasDestino(
            (int) $r['id_detalle'],
            (int) ($r['id_ficha_directa'] ?? 0)
        );

        // Calcular el saldo individual de cada ficha.
        foreach ($r['fichas'] as &$ficha) {
            $idFicha = (int) ($ficha['id_ficha'] ?? 0);

            $cantidadAutorizada = $ficha['cantidad_destinada'] ?? null;

            // Compatibilidad con fichas sin cantidad destinada explícita.
            if ($cantidadAutorizada === null || $cantidadAutorizada === '') {
                $cantidadAutorizada = $cantidadSolicitada;
            } else {
                $cantidadAutorizada = (float) $cantidadAutorizada;
            }

            $qFicha = $this->db->prepare("
                SELECT COALESCE(SUM(cantidad_distribuida), 0)
                FROM materiales_distribucion_detalle
                WHERE id_detalle_solicitud = :detalle
                  AND id_ficha = :ficha
            ");

            $qFicha->execute([
                ':detalle' => (int) $r['id_detalle'],
                ':ficha' => $idFicha
            ]);

            $distribuidoFicha = (float) $qFicha->fetchColumn();

            $ficha['cantidad_autorizada'] = $cantidadAutorizada;
            $ficha['distribuido_ficha'] = $distribuidoFicha;

            // Cantidad que todavía puede recibir esta ficha.
            $ficha['pendiente_ficha'] = max(
                0,
                $cantidadAutorizada - $distribuidoFicha
            );

            // Cantidad máxima entregable ahora:
            // no puede superar ni el saldo general ni el de la ficha.
            $ficha['maximo_entregable'] = min(
                (float) $r['disponible'],
                (float) $ficha['pendiente_ficha']
            );
        }

        unset($ficha);
    }

    unset($r);

    return $rows;
}



    private function fichasDestino(int $detalle, int $directa): array
{
    $q = $this->db->prepare("
        SELECT
            f.id AS id_ficha,
            f.numero_ficha,
            sd.cantidad_destinada
        FROM materiales_solicitud_destinos sd
        INNER JOIN fichas f ON f.id = sd.id_ficha
        WHERE sd.id_detalle_solicitud = :d
          AND sd.tipo_destino = 'ficha'
          AND sd.id_ficha IS NOT NULL
        ORDER BY f.numero_ficha
    ");

    $q->execute([':d' => $detalle]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows && $directa > 0) {
        $q = $this->db->prepare("
            SELECT id AS id_ficha, numero_ficha, NULL AS cantidad_destinada
            FROM fichas
            WHERE id = :f
        ");
        $q->execute([':f' => $directa]);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
		
    }

    return $rows;
}

    private function validarDetalle(int $detalle,int $coord,bool $admin): array {
        $scope=$admin?'':' AND pr.id_coordinador=:c';
        $q=$this->db->prepare("SELECT d.id,d.id_solicitud,d.cantidad_solicitada FROM materiales_solicitud_detalle d JOIN materiales_solicitudes s ON s.id=d.id_solicitud JOIN materiales_proceso_areas pa ON pa.id=s.id_proceso_area JOIN materiales_procesos pr ON pr.id=pa.id_proceso WHERE d.id=:d AND s.estado='avalada' $scope LIMIT 1");
        $p=[':d'=>$detalle]; if(!$admin)$p[':c']=$coord; $q->execute($p); $r=$q->fetch(PDO::FETCH_ASSOC);
        if(!$r) throw new RuntimeException('La línea no existe, no está avalada o está fuera de su coordinación.'); return $r;
    }

    private function auditar(int $u,string $accion,string $tabla,int $id,string $descripcion):void {
        $q=$this->db->prepare("INSERT INTO auditoria(id_usuario,modulo,accion,tabla_afectada,registro_id,ip,descripcion) VALUES(:u,'Recepción y distribución de materiales',:a,:t,:id,:ip,:d)");
        $q->execute([':u'=>$u,':a'=>$accion,':t'=>$tabla,':id'=>$id,':ip'=>substr((string)($_SERVER['REMOTE_ADDR']??''),0,45)?:null,':d'=>$descripcion]);
    }

    public function recibir(int $detalle,float $cantidad,string $fecha,string $remision,string $obs,int $usuario,int $coord,bool $admin):void {
        if(!is_finite($cantidad)||$cantidad<=0)throw new RuntimeException('La cantidad debe ser mayor que cero.');
        $dt=DateTime::createFromFormat('!Y-m-d',$fecha);if(!$dt||$dt->format('Y-m-d')!==$fecha)throw new RuntimeException('Fecha inválida.');
        $this->db->beginTransaction();
        try {
            $ctx=$this->validarDetalle($detalle,$coord,$admin);
            $q=$this->db->prepare("SELECT COALESCE(SUM(cantidad_recibida),0) FROM materiales_recepcion_detalle WHERE id_detalle_solicitud=:d");$q->execute([':d'=>$detalle]);$total=(float)$q->fetchColumn();
            if($total+$cantidad>(float)$ctx['cantidad_solicitada']+0.00001)throw new RuntimeException('La cantidad supera lo pendiente por recibir.');
            $texto=trim($remision)!==''?'Remisión: '.trim($remision):'';if(trim($obs)!=='')$texto.=($texto?"\n":'').trim($obs);
            $q=$this->db->prepare("INSERT INTO materiales_recepciones(id_solicitud,fecha_recepcion,observaciones,id_usuario) VALUES(:s,:f,:o,:u)");
            $q->execute([':s'=>$ctx['id_solicitud'],':f'=>$fecha.' '.date('H:i:s'),':o'=>$texto?:null,':u'=>$usuario]);$id=(int)$this->db->lastInsertId();
            $q=$this->db->prepare("INSERT INTO materiales_recepcion_detalle(id_recepcion,id_detalle_solicitud,cantidad_recibida,observaciones) VALUES(:r,:d,:c,:o)");
            $q->execute([':r'=>$id,':d'=>$detalle,':c'=>$cantidad,':o'=>trim($obs)?:null]);
            $this->auditar($usuario,'INSERT','materiales_recepciones',$id,"Solicitud {$ctx['id_solicitud']}, detalle $detalle, recibido $cantidad. $texto");$this->db->commit();
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function distribuir(int $detalle,int $ficha,float $cantidad,string $obs,int $usuario,int $coord,bool $admin):void {
        if($ficha<=0||!is_finite($cantidad)||$cantidad<=0)throw new RuntimeException('Seleccione una ficha y una cantidad mayor que cero.');
        $this->db->beginTransaction();
        try{
            $ctx=$this->validarDetalle($detalle,$coord,$admin);
            $q=$this->db->prepare("SELECT cantidad_destinada FROM materiales_solicitud_destinos WHERE id_detalle_solicitud=:d AND id_ficha=:f AND tipo_destino='ficha' LIMIT 1");$q->execute([':d'=>$detalle,':f'=>$ficha]);$limite=$q->fetchColumn();
            if($limite===false){$q=$this->db->prepare("SELECT id_ficha FROM materiales_solicitud_detalle WHERE id=:d");$q->execute([':d'=>$detalle]);if((int)$q->fetchColumn()!==$ficha)throw new RuntimeException('La ficha no está asociada a la solicitud.');$limite=(float)$ctx['cantidad_solicitada'];}
            $q=$this->db->prepare("SELECT COALESCE(SUM(cantidad_recibida),0) FROM materiales_recepcion_detalle WHERE id_detalle_solicitud=:d");$q->execute([':d'=>$detalle]);$recibido=(float)$q->fetchColumn();
            $q=$this->db->prepare("SELECT COALESCE(SUM(cantidad_distribuida),0) FROM materiales_distribucion_detalle WHERE id_detalle_solicitud=:d");$q->execute([':d'=>$detalle]);$dist=(float)$q->fetchColumn();
            $q=$this->db->prepare("SELECT COALESCE(SUM(cantidad_distribuida),0) FROM materiales_distribucion_detalle WHERE id_detalle_solicitud=:d AND id_ficha=:f");$q->execute([':d'=>$detalle,':f'=>$ficha]);$distFicha=(float)$q->fetchColumn();
            if($dist+$cantidad>$recibido+0.00001)throw new RuntimeException('No puede distribuir más de lo recibido.');
            if($distFicha+$cantidad>(float)$limite+0.00001)throw new RuntimeException('La cantidad supera lo destinado a esa ficha.');
            $q=$this->db->prepare("INSERT INTO materiales_distribuciones(observaciones,id_usuario) VALUES(:o,:u)");$q->execute([':o'=>trim($obs)?:null,':u'=>$usuario]);$id=(int)$this->db->lastInsertId();
            $q=$this->db->prepare("INSERT INTO materiales_distribucion_detalle(id_distribucion,id_detalle_solicitud,id_ficha,cantidad_distribuida) VALUES(:i,:d,:f,:c)");$q->execute([':i'=>$id,':d'=>$detalle,':f'=>$ficha,':c'=>$cantidad]);
            $this->auditar($usuario,'INSERT','materiales_distribuciones',$id,"Solicitud {$ctx['id_solicitud']}, detalle $detalle, ficha $ficha, distribuido $cantidad. ".trim($obs));$this->db->commit();
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
	
	
public function distribuirMultiple(
    array $asignaciones,
    string $obs,
    int $usuario,
    int $coord,
    bool $admin
): void {
    // 1. Limpiar y agrupar las cantidades por detalle y ficha.
    $lote = [];

    foreach ($asignaciones as $item) {
        if (!is_array($item)) {
            continue;
        }

        $detalle = (int) ($item['id_detalle'] ?? 0);
        $ficha = (int) ($item['id_ficha'] ?? 0);
        $textoCantidad = trim((string) ($item['cantidad'] ?? ''));
        $textoCantidad = str_replace(',', '.', $textoCantidad);

        if ($textoCantidad === '') {
            continue;
        }

        if (!is_numeric($textoCantidad)) {
            throw new RuntimeException('Hay una cantidad que no es válida.');
        }

        $cantidad = (float) $textoCantidad;

        if ($cantidad < 0) {
            throw new RuntimeException('Las cantidades no pueden ser negativas.');
        }

        // Los campos vacíos o en cero no generan movimientos.
        if ($cantidad == 0.0) {
            continue;
        }

        if ($detalle <= 0 || $ficha <= 0 || !is_finite($cantidad)) {
            throw new RuntimeException(
                'Hay una asignación con datos incompletos o inválidos.'
            );
        }

        $clave = $detalle . ':' . $ficha;

        if (!isset($lote[$clave])) {
            $lote[$clave] = [
                'id_detalle' => $detalle,
                'id_ficha' => $ficha,
                'cantidad' => 0.0
            ];
        }

        $lote[$clave]['cantidad'] += $cantidad;
    }

    if (!$lote) {
        throw new RuntimeException(
            'Ingrese al menos una cantidad mayor que cero.'
        );
    }

    $this->db->beginTransaction();

    try {
        $porDetalle = [];
        $contextos = [];
        $limites = [];

        // 2. Validar las fichas y sus límites autorizados.
        foreach ($lote as $clave => $item) {
            $detalle = $item['id_detalle'];
            $ficha = $item['id_ficha'];

            if (!isset($contextos[$detalle])) {
                $contextos[$detalle] = $this->validarDetalle(
                    $detalle,
                    $coord,
                    $admin
                );

                $porDetalle[$detalle] = 0.0;
            }

            $porDetalle[$detalle] += $item['cantidad'];

            $q = $this->db->prepare("
                SELECT cantidad_destinada
                FROM materiales_solicitud_destinos
                WHERE id_detalle_solicitud = :d
                  AND id_ficha = :f
                  AND tipo_destino = 'ficha'
                LIMIT 1
            ");
            $q->execute([':d' => $detalle, ':f' => $ficha]);
            $limite = $q->fetchColumn();

            if ($limite === false) {
                // Compatibilidad con solicitudes que tienen ficha directa.
                $q = $this->db->prepare("
                    SELECT id_ficha
                    FROM materiales_solicitud_detalle
                    WHERE id = :d
                ");
                $q->execute([':d' => $detalle]);

                if ((int) $q->fetchColumn() !== $ficha) {
                    throw new RuntimeException(
                        "La ficha $ficha no está asociada al detalle $detalle."
                    );
                }

                $limite = (float) $contextos[$detalle]['cantidad_solicitada'];
            } elseif ($limite === null || $limite === '') {
                // Si el destino no especifica una cantidad, se usa
                // la cantidad total solicitada como límite de seguridad.
                $limite = (float) $contextos[$detalle]['cantidad_solicitada'];
            } else {
                $limite = (float) $limite;
            }

            $limites[$clave] = $limite;
        }

        // 3. Comprobar que cada detalle tenga suficiente material recibido.
        foreach ($porDetalle as $detalle => $cantidadNueva) {
            $q = $this->db->prepare("
                SELECT COALESCE(SUM(cantidad_recibida), 0)
                FROM materiales_recepcion_detalle
                WHERE id_detalle_solicitud = :d
            ");
            $q->execute([':d' => $detalle]);
            $recibido = (float) $q->fetchColumn();

            $q = $this->db->prepare("
                SELECT COALESCE(SUM(cantidad_distribuida), 0)
                FROM materiales_distribucion_detalle
                WHERE id_detalle_solicitud = :d
            ");
            $q->execute([':d' => $detalle]);
            $distribuido = (float) $q->fetchColumn();

            if ($distribuido + $cantidadNueva > $recibido + 0.00001) {
                throw new RuntimeException(
                    "El detalle $detalle no tiene saldo suficiente. " .
                    "Recibido: $recibido; distribuido: $distribuido."
                );
            }
        }

        // 4. Comprobar el saldo pendiente de cada ficha.
        foreach ($lote as $clave => $item) {
            $detalle = $item['id_detalle'];
            $ficha = $item['id_ficha'];
            $cantidad = $item['cantidad'];

            $q = $this->db->prepare("
                SELECT COALESCE(SUM(cantidad_distribuida), 0)
                FROM materiales_distribucion_detalle
                WHERE id_detalle_solicitud = :d
                  AND id_ficha = :f
            ");
            $q->execute([':d' => $detalle, ':f' => $ficha]);
            $distribuidoFicha = (float) $q->fetchColumn();

            if (
                $distribuidoFicha + $cantidad >
                $limites[$clave] + 0.00001
            ) {
                $pendiente = max(
                    0,
                    $limites[$clave] - $distribuidoFicha
                );

                throw new RuntimeException(
                    "La ficha $ficha solo tiene $pendiente unidades pendientes."
                );
            }
        }

        // 5. Crear un movimiento y registrar todas sus líneas.
        $q = $this->db->prepare("
            INSERT INTO materiales_distribuciones
                (observaciones, id_usuario)
            VALUES (:o, :u)
        ");
        $q->execute([
            ':o' => trim($obs) !== '' ? trim($obs) : null,
            ':u' => $usuario
        ]);

        $idDistribucion = (int) $this->db->lastInsertId();

        $qDetalle = $this->db->prepare("
            INSERT INTO materiales_distribucion_detalle
                (id_distribucion, id_detalle_solicitud,
                 id_ficha, cantidad_distribuida)
            VALUES (:i, :d, :f, :c)
        ");

        $resumen = [];

        foreach ($lote as $item) {
            $qDetalle->execute([
                ':i' => $idDistribucion,
                ':d' => $item['id_detalle'],
                ':f' => $item['id_ficha'],
                ':c' => $item['cantidad']
            ]);

            $resumen[] = sprintf(
                'Detalle %d, ficha %d, cantidad %s',
                $item['id_detalle'],
                $item['id_ficha'],
                (string) $item['cantidad']
            );
        }

        $this->auditar(
            $usuario,
            'INSERT',
            'materiales_distribuciones',
            $idDistribucion,
            'Distribución múltiple. ' . implode('; ', $resumen) .
            (trim($obs) !== '' ? '. Observaciones: ' . trim($obs) : '')
        );

        $this->db->commit();
    } catch (Throwable $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        throw $e;
    }
}


    public function historial(int $coord,bool $admin):array {
        $a=$admin?'':' AND pr.id_coordinador=:c1';$b=$admin?'':' AND pr.id_coordinador=:c2';
        $sql="SELECT 'Recepción' tipo,r.fecha_recepcion fecha,r.observaciones,u.username usuario,s.numero_solicitud,m.descripcion material,rd.cantidad_recibida cantidad,NULL numero_ficha
        FROM materiales_recepciones r JOIN materiales_recepcion_detalle rd ON rd.id_recepcion=r.id JOIN materiales_solicitud_detalle sd ON sd.id=rd.id_detalle_solicitud JOIN materiales_solicitudes s ON s.id=sd.id_solicitud JOIN materiales_proceso_areas pa ON pa.id=s.id_proceso_area JOIN materiales_procesos pr ON pr.id=pa.id_proceso LEFT JOIN materiales_catalogo m ON m.id=sd.id_material LEFT JOIN usuarios u ON u.id=r.id_usuario WHERE 1=1 $a
        UNION ALL SELECT 'Distribución',d.created_at,d.observaciones,u.username,s.numero_solicitud,m.descripcion,dd.cantidad_distribuida,f.numero_ficha
        FROM materiales_distribuciones d JOIN materiales_distribucion_detalle dd ON dd.id_distribucion=d.id JOIN materiales_solicitud_detalle sd ON sd.id=dd.id_detalle_solicitud JOIN materiales_solicitudes s ON s.id=sd.id_solicitud JOIN materiales_proceso_areas pa ON pa.id=s.id_proceso_area JOIN materiales_procesos pr ON pr.id=pa.id_proceso LEFT JOIN materiales_catalogo m ON m.id=sd.id_material LEFT JOIN fichas f ON f.id=dd.id_ficha LEFT JOIN usuarios u ON u.id=d.id_usuario WHERE 1=1 $b ORDER BY fecha DESC LIMIT 300";
        $q=$this->db->prepare($sql);$q->execute($admin?[]:[':c1'=>$coord,':c2'=>$coord]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}