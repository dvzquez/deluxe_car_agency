"""Configuración central de Flask mediante una factoría de aplicación.

Una factoría permite iniciar la misma aplicación con distintas configuraciones
para desarrollo y pruebas, sin abrir conexiones en el momento de importarla.
"""

import os
from pathlib import Path

from dotenv import load_dotenv
from flask import Flask
from sqlalchemy import create_engine
from sqlalchemy.engine import URL


ENV_FILE = Path(__file__).resolve().parents[1] / ".env"


def create_app(test_config: dict | None = None) -> Flask:
    """Construye la aplicación y registra las rutas de la primera fase."""
    load_dotenv(ENV_FILE)

    app = Flask(__name__)
    app.config.from_mapping(
        DB_HOST=os.getenv("DB_HOST", "127.0.0.1"),
        DB_PORT=int(os.getenv("DB_PORT", "3306")),
        DB_NAME=os.getenv("DB_NAME", "agencia_db"),
        DB_USER=os.getenv("DB_USER", "root"),
        DB_PASSWORD=os.getenv("DB_PASSWORD", ""),
        DATABASE_URL=None,
    )
    if test_config:
        app.config.update(test_config)

    # URL.create evita tener que escapar manualmente caracteres especiales
    # dentro de las contraseñas de conexión a MySQL.
    url = app.config["DATABASE_URL"] or URL.create(
        drivername="mysql+pymysql",
        username=app.config["DB_USER"],
        password=app.config["DB_PASSWORD"],
        host=app.config["DB_HOST"],
        port=app.config["DB_PORT"],
        database=app.config["DB_NAME"],
        query={"charset": "utf8mb4"},
    )
    # Se puede inyectar un motor SQLite en las pruebas sin depender de XAMPP.
    app.extensions["db_engine"] = app.config.get("DB_ENGINE") or create_engine(
        url,
        pool_pre_ping=True,
    )

    from .routes import main

    app.register_blueprint(main)

    @app.template_filter("mxn")
    def mxn(value):
        """Presenta un importe numérico en pesos mexicanos."""
        return f"${float(value or 0):,.0f} MXN"

    return app
