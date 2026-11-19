"""Vistas HTML y API de catálogo de la primera fase de migración."""

from pathlib import Path

from flask import Blueprint, abort, current_app, jsonify, render_template, request, send_from_directory
from sqlalchemy import text
from sqlalchemy.exc import SQLAlchemyError

from .db import get_autos, get_categories, get_stats

main = Blueprint("main", __name__)
# Los archivos originales siguen en el directorio principal del repositorio.
CAR_IMAGES = Path(__file__).resolve().parents[2] / "imgs" / "autos"
ALLOWED_IMAGE_EXTENSIONS = {".png", ".jpg", ".jpeg", ".webp"}


def image_url(value: str | None) -> str:
    """Resuelve referencias locales del ZIP sin aceptar rutas arbitrarias."""
    if not value:
        return ""
    filename = Path(str(value).replace("\\", "/")).name
    if not filename or Path(filename).suffix.lower() not in ALLOWED_IMAGE_EXTENSIONS:
        return ""
    return f"/imgs/autos/{filename}"


@main.app_template_filter("car_image")
def car_image(value):
    return image_url(value)


@main.get("/imgs/autos/<path:filename>")
def car_file(filename: str):
    """Expone las imágenes existentes sin copiarlas al nuevo proyecto."""
    if Path(filename).name != filename or Path(filename).suffix.lower() not in ALLOWED_IMAGE_EXTENSIONS:
        abort(404)
    return send_from_directory(CAR_IMAGES, filename)


@main.get("/")
def index():
    """Página principal: misma información que index.php, ahora desde Python."""
    try:
        return render_template(
            "index.html",
            stats=get_stats(),
            categories=get_categories(),
            autos=get_autos(orden="precio_desc", limite=6),
        )
    except SQLAlchemyError:
        current_app.logger.exception("No se pudo consultar la base de datos")
        return render_template("error_db.html"), 503


@main.get("/catalogo")
def catalogo():
    """Catálogo HTML con búsqueda, filtro y ordenamiento por GET."""
    category = request.args.get("categoria", "").strip()
    search = request.args.get("q", "").strip()
    order = request.args.get("orden", "precio_asc")
    try:
        return render_template(
            "catalogo.html",
            autos=get_autos(categoria=category, q=search, orden=order),
            categories=get_categories(),
            categoria=category,
            q=search,
            orden=order,
        )
    except SQLAlchemyError:
        current_app.logger.exception("No se pudo consultar el catálogo")
        return render_template("error_db.html"), 503


@main.get("/api/autos")
def api_autos():
    """Reimplementa get_autos.php: categoria, q, orden, limite."""
    try:
        limit = int(request.args.get("limite", "50"))
    except (ValueError, TypeError):
        return jsonify({"error": "El límite debe ser un número entero."}), 400
    try:
        autos = get_autos(
            categoria=request.args.get("categoria", ""),
            q=request.args.get("q", ""),
            orden=request.args.get("orden", "precio_asc"),
            limite=limit,
        )
        return jsonify(autos)
    except SQLAlchemyError:
        current_app.logger.exception("Error en API de automóviles")
        return jsonify({"error": "No se pudo consultar el catálogo."}), 503


@main.get("/health")
def health():
    """Comprueba que Flask está respondiendo (sin conexión a la BD)."""
    return jsonify({"status": "ok", "application": "luxe-motors-python"})


@main.get("/health/db")
def health_db():
    """Comprueba la conexión a MySQL; no muestra credenciales."""
    try:
        with current_app.extensions["db_engine"].connect() as connection:
            connection.execute(text("SELECT 1"))
        return jsonify({"status": "ok", "database": "connected"})
    except SQLAlchemyError:
        current_app.logger.exception("La base de datos no responde")
        return jsonify({"status": "unavailable", "database": "disconnected"}), 503
