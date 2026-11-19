"""Consultas de solo lectura a la tabla original `autos` de MySQL.

Durante la primera fase no se crean ni modifican tablas. Todas las búsquedas
utilizan parámetros SQL y los criterios de orden se eligen de una lista segura.
"""

from decimal import Decimal

from flask import current_app
from sqlalchemy import text

SORTS = {
    "precio_asc": "precio_base ASC",
    "precio_desc": "precio_base DESC",
    "nombre": "modelo ASC",
}


def query_all(sql: str, params: dict | None = None) -> list[dict]:
    """Ejecuta una consulta SELECT y devuelve diccionarios."""
    engine = current_app.extensions["db_engine"]
    with engine.connect() as connection:
        return [dict(row) for row in connection.execute(text(sql), params or {}).mappings()]


def get_categories() -> list[dict]:
    """Agrupa los vehículos por categoría y cuenta los modelos."""
    return query_all(
        "SELECT categoria, COUNT(*) AS total "
        "FROM autos GROUP BY categoria ORDER BY categoria"
    )


def get_stats() -> dict:
    """Recupera estadísticas y el vehículo más caro de la tabla original."""
    counts = query_all(
        "SELECT COUNT(*) AS total_modelos, "
        "COUNT(DISTINCT categoria) AS total_categorias FROM autos"
    )[0]
    featured = query_all(
        "SELECT modelo, precio_base, imagen "
        "FROM autos ORDER BY precio_base DESC LIMIT 1"
    )
    return {**counts, "destacado": featured[0] if featured else None}


def get_autos(*, categoria: str = "", q: str = "", orden: str = "precio_asc", limite: int = 50) -> list[dict]:
    """Filtra el catálogo manteniendo los campos del endpoint PHP original."""
    conditions = []
    params: dict = {"limite": max(1, min(100, limite))}
    if categoria.strip():
        conditions.append("categoria = :categoria")
        params["categoria"] = categoria.strip()
    if q.strip():
        conditions.append("modelo LIKE :busqueda")
        params["busqueda"] = f"%{q.strip()}%"
    where = " WHERE " + " AND ".join(conditions) if conditions else ""
    # El orden nunca proviene directamente de la entrada HTTP.
    sort = SORTS.get(orden, SORTS["precio_asc"])
    query = (
        "SELECT id, modelo, categoria, precio_base, ficha_tecnica, "
        "imagen, imagen_negro, imagen_rojo, imagen_gris, imagen_azul "
        f"FROM autos{where} ORDER BY {sort} LIMIT :limite"
    )
    autos = query_all(query, params)
    for auto in autos:
        # DECIMAL de MySQL no se puede serializar directamente como JSON.
        if isinstance(auto["precio_base"], Decimal):
            auto["precio_base"] = float(auto["precio_base"])
        for color in ("imagen_negro", "imagen_rojo", "imagen_gris", "imagen_azul"):
            if not auto[color]:
                auto[color] = auto["imagen"]
    return autos
