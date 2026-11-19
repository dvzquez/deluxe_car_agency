"""Punto de entrada para ejecutar la versión Flask en desarrollo local."""

import os

from app import create_app

app = create_app()

if __name__ == "__main__":
    # El depurador solo se activa si se solicita explícitamente en .env.
    app.run(host="127.0.0.1", port=5000, debug=os.getenv("FLASK_DEBUG") == "1")
