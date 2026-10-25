# Módulo de recepción y distribución

Archivos:
- models/MaterialesRecepcionDistribucionModel.php
- controllers/MaterialesRecepcionDistribucionController.php
- views/materiales/recepcion_distribucion/index.php

Integración añadida en index.php:
- GET /materiales/recepcion-distribucion
- POST /materiales/recepcion-distribucion/recibir
- POST /materiales/recepcion-distribucion/distribuir

Usa la estructura de trazabilidad_aprendices (6).sql, no crea tablas nuevas. Solo lista solicitudes con estado exacto avalada. El Administrador ve todas; el Coordinador solo procesos asignados por materiales_procesos.id_coordinador = profesionales.id. Registra recepciones parciales, impide recibir por encima de lo solicitado y no permite distribuir más de lo recibido. Los movimientos se registran además en auditoria.
