"""Pruebas de la migración sin necesitar un servidor MySQL.

SQLite solo sirve como sustituto en pruebas: la aplicación real usa MySQL.
"""

import pytest
from sqlalchemy import create_engine, text
from sqlalchemy.pool import StaticPool

from app import create_app


@pytest.fixture()
def client():
    engine = create_engine(
        "sqlite+pysqlite:///:memory:",
        connect_args={"check_same_thread": False},
        poolclass=StaticPool,
    )
    with engine.begin() as conn:
        conn.execute(text("""
            CREATE TABLE autos (
                id INTEGER PRIMARY KEY,
                modelo TEXT NOT NULL,
                categoria TEXT NOT NULL,
                precio_base NUMERIC NOT NULL,
                ficha_tecnica TEXT,
                imagen TEXT,
                imagen_negro TEXT,
                imagen_rojo TEXT,
                imagen_gris TEXT,
                imagen_azul TEXT
            )
        """))
        conn.execute(text("""
            INSERT INTO autos (id, modelo, categoria, precio_base, ficha_tecnica, imagen)
            VALUES
              (1, 'Porsche 911 Turbo S', 'Deportivos', 3450000, '{}', 'imgs/autos/porsche-911-turbo-s.png'),
              (2, 'Tesla Model S Plaid', 'Eléctricos', 2500000, '{}', 'imgs/autos/tesla-model-s-plaid.png'),
              (3, 'Bentley Bentayga V8', 'SUVs', 4200000, '{}', 'imgs/autos/bentley-bentayga-v8.png')
        """))

    app = create_app({"TESTING": True, "DB_ENGINE": engine})
    yield app.test_client()
    engine.dispose()


def test_health(client):
    assert client.get("/health").json["status"] == "ok"
    assert client.get("/health/db").json["database"] == "connected"


def test_home(client):
    response = client.get("/")
    assert response.status_code == 200
    assert "LUXE MOTORS MX" in response.get_data(as_text=True)
    assert "Porsche 911 Turbo S" in response.get_data(as_text=True)


def test_catalog_filters(client):
    response = client.get("/catalogo?categoria=Deportivos&q=Porsche")
    html = response.get_data(as_text=True)
    assert response.status_code == 200
    assert "Porsche 911 Turbo S" in html
    assert "Tesla Model S Plaid" not in html


def test_api_compatible_with_php(client):
    result = client.get("/api/autos?categoria=Deportivos&orden=precio_desc").json
    assert len(result) == 1
    assert result[0]["modelo"] == "Porsche 911 Turbo S"
    assert result[0]["imagen_negro"] == result[0]["imagen"]


def test_api_search_and_order(client):
    result = client.get("/api/autos?orden=precio_desc&limite=2").json
    assert [car["id"] for car in result] == [3, 1]
    assert client.get("/api/autos?q=Tesla").json[0]["id"] == 2


def test_api_rejects_invalid_limit(client):
    response = client.get("/api/autos?limite=abc")
    assert response.status_code == 400


def test_api_sort_does_not_allow_sql_injection(client):
    # Un orden desconocido vuelve al criterio seguro por defecto.
    response = client.get("/api/autos?orden=precio_base%20DESC%3B%20DROP%20TABLE%20autos")
    assert response.status_code == 200
    assert len(response.json) == 3
    assert client.get("/api/autos").status_code == 200


def test_images_do_not_allow_path_traversal(client):
    assert client.get("/imgs/autos/..%5Csecret.txt").status_code == 404
    assert client.get("/imgs/autos/../../secret.txt").status_code == 404
