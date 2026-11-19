# Bitácora técnica de migración · PHP → Python (Flask + MySQL)

## Regla principal

No eliminar ni reescribir la versión 3 en PHP hasta tener equivalencia funcional comprobada. Los módulos nuevos viven en `python_app/` y utilizan la misma base de datos MySQL existente.

## Fase 1 · Base funcional

### Código fuente original utilizado

- `index.php`: estadísticas, categorías, vehículo destacado y seis modelos destacados.
- `get_autos.php`: filtros (`categoria`, `q`, `orden`, `limite`) y campos devueltos por JSON.
- `config/db.php`: datos de conexión local a MySQL y nombre de la base `agencia_db`.
- `imgs/autos/`: recursos visuales existentes que no deben duplicarse.

### Correspondencia PHP → Python

| Archivo original | Reemplazo previsto o implementado | Estado |
|---|---|---|
| `index.php` | `app/routes.py` + `templates/index.html` | Implementado en Fase 1 |
| `get_autos.php` | `GET /api/autos` + `app/db.py` | Implementado en Fase 1 |
| `config/db.php` | `app/__init__.py` + `.env` (solo local) | Implementado en Fase 1 |
| `config.php` | Interfaz configurador + servicio Python | Pendiente |
| `compraventa.php` | Servicio de tasación/compraventa | Pendiente |
| `plan_pagos.php` | Servicio de amortización con `Decimal` | Pendiente |
| `factura.php` | Cotizaciones e informes PDF | Pendiente |
| `admin/*.php` | Módulos administrativos, permisos y métricas | Pendiente |

### Decisiones técnicas

1. **Conservar MySQL:** permite comparar resultados sin migrar los datos a otro motor.
2. **SQLAlchemy Core + PyMySQL:** ejecutar consultas parametrizadas con el esquema existente, sin inventar modelos ORM antes de conocer la definición real de todas las tablas.
3. **Factoría `create_app`:** inicializar aplicaciones de prueba y desarrollo con configuraciones distintas.
4. **Jinja:** reutilizar el HTML/CSS original y sustituir expresiones PHP por variables escapadas automáticamente.
5. **Imágenes:** `/imgs/autos/<archivo>` utiliza los archivos del repositorio original y limita extensiones.
6. **Errores de BD:** devolver HTTP 503 sin publicar detalles de conexión en la página o la API.
7. **Pruebas unitarias/integración ligera:** usar SQLite en memoria para verificar rutas y SQL; posteriormente validar el comportamiento contra MySQL real.

### Criterios de aceptación de la Fase 1

- [ ] El servidor Flask inicia localmente sin errores.
- [ ] El endpoint `/health` devuelve HTTP 200.
- [ ] El endpoint `/health/db` devuelve HTTP 200 cuando XAMPP/MySQL está iniciado y configurado.
- [ ] La página principal muestra estadísticas reales de `autos` y un vehículo destacado.
- [ ] El catálogo filtra por categoría y nombre y ordena por precio.
- [ ] La API JSON devuelve los campos de `get_autos.php`.
- [ ] Las imágenes existentes se cargan desde `imgs/autos/`.
- [ ] `pytest -q` aprueba todas las pruebas locales.
- [ ] No se ha modificado/eliminado ningún archivo PHP original.
- [ ] La nueva rama de GitHub contiene solo los cambios de esta fase.

**Nota:** las casillas deben marcarse después de probar la entrega en el equipo real. La ausencia del respaldo SQL original impide certificar la integración MySQL con este ZIP por sí solo.

## Fase 2 · Lógica de negocio en Python

- Extraer fórmulas reales de `compraventa.php` y `plan_pagos.php`.
- Evitar tasas/base de valuación universales no fundamentadas; usar reglas configurables con advertencias.
- Usar `Decimal` para importes monetarios y probar valores límite.
- Conservar resultados reproducibles con pruebas comparativas PHP vs Python.

## Fases 3 y 4 · Integración y seguridad

- Migrar configurador y flujos de venta con validaciones del lado del servidor.
- Revisar integridad referencial, transacciones y migraciones versionadas cuando exista el esquema SQL.
- No reutilizar autenticación con credenciales en código; utilizar hashes robustos de contraseñas y controles de autorización.
- Generar cotizaciones PDF; emitir CFDI reales solo mediante una integración fiscal autorizada.
- Añadir CI y documentación de despliegue después de establecer una base verificable.
