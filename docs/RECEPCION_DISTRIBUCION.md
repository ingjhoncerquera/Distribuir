# Módulo de recepción y distribución

## Archivos principales

- `models/MaterialesRecepcionDistribucionModel.php`
- `controllers/MaterialesRecepcionDistribucionController.php`
- `views/materiales/recepcion_distribucion/recepcion.php`
- `views/materiales/recepcion_distribucion/distribucion.php`

## Rutas

- GET `/materiales/recepcion-distribucion`: redirige a recepción.
- GET `/materiales/recepcion-distribucion/recepcion`: pantalla de recepción.
- GET `/materiales/recepcion-distribucion/distribucion`: pantalla de distribución.
- POST `/materiales/recepcion-distribucion/recibir`: registra una recepción.
- POST `/materiales/recepcion-distribucion/distribuir`: registra una distribución.

## Funcionamiento

La pantalla agrupa los materiales por producto para facilitar el control como en una hoja de cálculo. Cada producto se puede desplegar para consultar las líneas de solicitud que lo componen. Los movimientos se registran en la línea de solicitud correspondiente para conservar trazabilidad y no mezclar cantidades autorizadas entre solicitudes.

Solo se listan solicitudes con estado exacto `avalada`. El Administrador ve todas; el Coordinador solo los procesos asignados por `materiales_procesos.id_coordinador = profesionales.id`.

La recepción permite registrar entregas parciales, fecha, remisión y observaciones. No permite recibir más de la cantidad solicitada. La distribución solo permite seleccionar fichas asociadas a la línea y valida que la cantidad no supere el saldo recibido ni el máximo destinado a la ficha. Una línea sin ficha destino asociada no habilita la distribución.

Los movimientos se registran además en `auditoria`. El módulo utiliza las tablas existentes definidas en `trazabilidad_aprendices (6).sql`; no crea tablas nuevas.
