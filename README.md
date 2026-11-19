# LUXE MOTORS MX · Deluxe Car Agency

Aplicación web de una agencia de vehículos de lujo. **La versión original v3 utiliza PHP + MySQL y sigue disponible**. La migración a **Python + Flask + MySQL** se realiza progresivamente, para conservar las funciones existentes mientras el backend se reconstruye por módulos.

> **Estado actual:** Fase 1 de migración preparada. Inicio, catálogo HTML y API JSON implementados en Flask. Compraventa, tasación, financiamiento, facturación y administración siguen funcionando únicamente en la versión PHP hasta que se migren. No se afirma que toda la plataforma esté en Python.

## Descripción / Description

**ES:** LUXE MOTORS MX permite explorar vehículos premium, personalizar opciones, calcular planes de pago, solicitar tasaciones y gestionar operaciones de una agencia. La versión de Python comienza migrando la consulta del inventario sin alterar los datos originales.

**EN:** LUXE MOTORS MX is a luxury vehicle dealership web application. Its original PHP implementation includes inventory browsing, vehicle configuration, financing, appraisals, invoices and administration. A phased migration to Python/Flask is underway, starting with the inventory catalog and JSON API.

## Estado de las funcionalidades / Feature status

| Módulo / Feature | PHP v3 | Python + Flask (Fase 1) |
|---|---|---|
| Página principal / Homepage | Disponible | **Migrada** |
| Catálogo y filtros / Catalog | Disponible | **Migrado** (búsqueda, categoría, orden) |
| API de automóviles / Car API | `get_autos.php` | **Migrada** (`/api/autos`) |
| Configurador / Configurator | Disponible | Pendiente |
| Tasación y compraventa / Appraisals | Disponible | Pendiente |
| Financiamiento / Financing | Disponible | Pendiente |
| Facturas / Invoices | Disponible | Pendiente |
| Panel de administración / Admin | Disponible | Pendiente |
| Autenticación / Authentication | Implementación PHP existente | Pendiente de rediseño seguro |

## Tecnologías / Tech Stack

- **Original:** PHP (PDO), MySQL, HTML5, CSS3, JavaScript, Bootstrap 5, Font Awesome.
- **Nueva etapa:** Python 3.10+, Flask 3.1+, SQLAlchemy 2.x (consultas SQL parametrizadas), PyMySQL, Jinja, MySQL, pytest.
- **Diseño:** Se conservan el tema oscuro, los acentos dorados, las imágenes del repositorio y los estilos originales de `index.php`.

## Estructura actual

```text
agencia_v3/                       # Raíz del repositorio existente
├── admin/                         # Panel original PHP (sin modificar)
├── config/db.php                  # Conexión PHP original
├── imgs/autos/                    # Imágenes originales, reutilizadas
├── *.php                          # Páginas originales (se conservan)
├── python_app/                    # Backend Python: Fase 1
│   ├── .env.example               # Plantilla de variables de entorno
│   ├── requirements.txt
│   ├── run.py                     # Ejecuta Flask en local
│   ├── app/
│   │   ├── __init__.py            # Factoría create_app y conexión
│   │   ├── db.py                  # Consultas SQL parametrizadas
│   │   ├── routes.py              # Rutas HTML, API y verificación
│   │   ├── templates/             # HTML + Jinja
│   │   └── static/css/            # Estilos originales y ajustes
│   └── tests/                     # Pruebas automatizadas
├── docs/MIGRACION_PYTHON.md       # Registro de decisiones y fases
├── .gitignore
└── README.md
```

## Instalación de la versión Python (Windows + XAMPP)

> **Requisito importante:** El ZIP de v3 **no contiene un respaldo `.sql`**. Antes de probar el catálogo, debes conservar o exportar la base `agencia_db` original que ya usa PHP. La nueva versión consulta su tabla `autos`; **no la crea automáticamente ni la modifica**. Si todavía no tienes esa tabla, importa tu respaldo original desde phpMyAdmin.

1. Descarga/clona **tu repositorio existente** y agrega el contenido de la carpeta `python_app/` y los archivos actualizados de esta entrega **en la raíz de ese mismo repositorio**. No copies un nuevo directorio `agencia_v3/` dentro del repositorio.
2. Inicia el módulo **MySQL** de XAMPP. Apache solo es necesario para seguir ejecutando las páginas PHP; Flask sirve las páginas Python de manera independiente.
3. Abre PowerShell en la raíz del repositorio y ejecuta:

   ```powershell
   cd python_app
   py -m venv .venv
   .\.venv\Scripts\python.exe -m pip install -r requirements.txt
   Copy-Item .env.example .env
   ```

4. Edita `python_app/.env` según tu MySQL local:

   ```dotenv
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=agencia_db
   DB_USER=root
   DB_PASSWORD=
   FLASK_DEBUG=0
   ```

   No subas el archivo `.env` a Git. Si tu usuario MySQL tiene contraseña, agrégala **solo a ese archivo local**.

5. Ejecuta Flask desde `python_app/`:

   ```powershell
   .\.venv\Scripts\python.exe run.py
   ```

6. Abre en tu navegador:

   - Inicio: `http://127.0.0.1:5000/`
   - Catálogo: `http://127.0.0.1:5000/catalogo`
   - API JSON: `http://127.0.0.1:5000/api/autos`
   - Aplicación activa: `http://127.0.0.1:5000/health`
   - Conexión MySQL: `http://127.0.0.1:5000/health/db`

La página muestra un mensaje de conexión si falta MySQL o la tabla `autos`. En esta fase las imágenes siguen en `imgs/autos/` y son servidas por Flask a través de `/imgs/autos/<archivo>`.

### Pruebas automatizadas / Tests

Con PowerShell dentro de `python_app/`:

```powershell
.\.venv\Scripts\python.exe -m pytest -q
```

Las pruebas usan **SQLite en memoria únicamente como base ficticia de prueba**. El proyecto real utiliza MySQL. Probar en SQLite no sustituye las verificaciones de integración con MySQL/XAMPP.

### API de ejemplo / Example API

```text
GET /api/autos
GET /api/autos?categoria=Deportivos
GET /api/autos?q=Porsche&orden=precio_desc&limite=10
```

La API conserva los campos devueltos por `get_autos.php`: `id`, `modelo`, `categoria`, `precio_base`, `ficha_tecnica`, `imagen` y las imágenes por color. Los valores permitidos para `orden` son `precio_asc`, `precio_desc` y `nombre`. `limite` se ajusta entre 1 y 100.

## Versión PHP original (v3)

Las páginas PHP se siguen ejecutando con XAMPP/Apache como antes. Verifica las credenciales en `config/db.php` y conserva las tablas de `agencia_db`. No elimines el backend anterior hasta migrar y verificar todas las funciones. El servidor PHP y Flask pueden ejecutarse a la vez en puertos diferentes.

## Publicar la primera fase en GitHub

Trabaja **dentro del clon que ya tiene configurado `origin`**. Crea primero la rama y luego descomprime esta entrega en la raíz del repositorio, combinando las carpetas existentes (no sustituyéndolas):

```powershell
# Desde la raíz del repositorio existente
# Confirmar que no hay cambios anteriores pendientes
git status

# Crear una rama nueva desde tu estado actual (sin sobrescribir main)
git switch -c feature/flask-fase1

# Ahora descomprime los archivos del ZIP de esta entrega en la raíz
# del repositorio y verifica qué cambió antes de agregarlo:
git status

# Agregar solo lo preparado para esta fase
git add README.md .gitignore docs/MIGRACION_PYTHON.md python_app
git status
git commit -m "feat: inicia migracion gradual de PHP a Flask y MySQL"
git push -u origin feature/flask-fase1
```

Después de probar con tus datos reales, podrás abrir un Pull Request para integrar la rama a `main`. Si `git status` muestra cambios previos, guárdalos/sepáralos antes de seguir. **No es necesario ejecutar `git init`, cambiar el remoto ni subir el ZIP con su `.git` interna.**

## Próximos pasos / Roadmap

1. **Fase 1 (actual):** entorno Flask, home, catálogo, API, conexión MySQL y pruebas iniciales.
2. **Fase 2:** migración de tasación y financiamiento a servicios Python con `Decimal` y pruebas unitarias.
3. **Fase 3:** configurador, cotizaciones, ventas y transacciones MySQL.
4. **Fase 4:** autenticación segura, administración, reportes y documentación de despliegue.

Consulta [el plan de migración](docs/MIGRACION_PYTHON.md) para ver las decisiones técnicas y los criterios de verificación.

## Notas de seguridad

Proyecto educativo/de portafolio. No utilizar credenciales predeterminadas ni ejecutar Flask con modo de depuración en producción. Los importes enviados desde el navegador deberán recalcularse y validarse en el backend durante la migración de ventas. La visualización de facturas de la versión PHP no equivale a emitir comprobantes CFDI fiscales válidos.
