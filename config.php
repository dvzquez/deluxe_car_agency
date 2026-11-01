<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurador — LUXE MOTORS MX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg:       #070707;
            --surface:  #0f0f0f;
            --s2:       #141414;
            --border:   #1e1e1e;
            --gold:     #C9A96E;
            --gold-dim: #7a6540;
            --text:     #EBEBEB;
            --muted:    #555;
            --radius:   6px;
        }

        *, *::before, *::after { box-sizing: border-box; }
        body { background: var(--bg); color: var(--text); font-family: 'DM Sans', sans-serif; font-weight: 300; }

        /* ── NAVBAR ── */
        .navbar { background: rgba(7,7,7,0.96); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); padding: 16px 0; }
        .navbar-brand { font-family: 'Cormorant Garamond', serif; font-weight: 300; font-size: 1.4rem; letter-spacing: 0.05em; color: var(--text) !important; }
        .navbar-brand em { font-style: normal; color: var(--gold); }
        .nav-link { color: #666 !important; font-size: 0.85rem; font-weight: 400; letter-spacing: 0.05em; transition: color 0.25s; }
        .nav-link:hover { color: var(--text) !important; }
        .nav-link.special { color: var(--gold) !important; }

        /* ── PAGE HEADER ── */
        .page-header { text-align: center; padding: 80px 24px 56px; }
        .page-header .eyebrow { font-size: 10px; letter-spacing: 0.45em; text-transform: uppercase; color: var(--gold); margin-bottom: 16px; }
        .page-header h1 { font-family: 'Cormorant Garamond', serif; font-weight: 300; font-size: clamp(2.5rem, 5vw, 4rem); line-height: 1; margin-bottom: 16px; }
        .page-header p { color: var(--muted); max-width: 480px; margin: 0 auto; font-size: 0.9rem; }

        /* ── CATEGORY TABS ── */
        .category-tabs { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-bottom: 48px; }
        .cat-tab { padding: 9px 24px; background: transparent; border: 1px solid var(--border); border-radius: 30px; color: var(--muted); font-family: 'DM Sans', sans-serif; font-size: 0.8rem; letter-spacing: 0.15em; text-transform: uppercase; cursor: pointer; transition: all 0.3s; }
        .cat-tab:hover { border-color: #333; color: var(--text); }
        .cat-tab.active { border-color: var(--gold-dim); color: var(--gold); background: rgba(201,169,110,0.06); }

        /* ── AUTO CARDS ── */
        .autos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 64px; }

        .auto-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            cursor: pointer;
            transition: border-color 0.3s, transform 0.3s, box-shadow 0.3s;
        }
        .auto-card:hover {
            border-color: var(--gold-dim);
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.5);
        }
        .auto-card.selected {
            border-color: var(--gold);
            box-shadow: 0 0 0 1px var(--gold-dim);
        }
        .card-img-wrap { aspect-ratio: 16/9; background: #0a0a0a; overflow: hidden; }
        .card-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .auto-card:hover .card-img-wrap img { transform: scale(1.04); }
        .card-body-inner { padding: 20px; }
        .card-modelo { font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; font-weight: 300; margin-bottom: 4px; }
        .card-precio { color: var(--gold); font-size: 0.9rem; letter-spacing: 0.05em; }

        /* ── CONFIGURATOR PANEL ── */
        .configurator-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 80px;
            display: none;
            animation: panelIn 0.4s ease;
        }
        @keyframes panelIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

        .config-grid { display: grid; grid-template-columns: 1fr 480px; }

        /* ────────────────────────────────────────
           VISOR DE IMAGEN — sistema de dos capas
           imgA y imgB apiladas. Siempre una tiene
           opacity:1 y la otra opacity:0. Al cambiar
           de color se carga la nueva imagen en la
           capa inactiva y, cuando termina de cargar,
           se hace el fundido cruzado entre ambas.
        ──────────────────────────────────────── */
        .config-visual {
            background: #050505;
            position: relative;
            overflow: hidden;
            min-height: 480px;
        }

        /* Contenedor que ocupa todo el espacio visual */
        .img-stage {
            position: absolute;
            inset: 0;
        }

        /* Cada capa de imagen */
        .stage-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 40px;
            opacity: 0;
            transition: opacity 0.5s ease;
            will-change: opacity;
            /* SIN ningún filter CSS — el cambio es solo por imagen */
        }

        .stage-img.visible { opacity: 1; }

        /* Spinner que aparece mientras carga la nueva imagen */
        .img-loader {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 5;
        }
        .img-loader.loading { opacity: 1; }
        .spinner {
            width: 26px; height: 26px;
            border: 2px solid rgba(201,169,110,0.15);
            border-top-color: var(--gold);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Badge del color activo */
        .color-badge {
            position: absolute;
            bottom: 18px; left: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(7,7,7,0.88);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 7px 16px;
            font-size: 0.75rem;
            backdrop-filter: blur(8px);
            z-index: 10;
            transition: opacity 0.3s;
        }
        .badge-dot {
            width: 9px; height: 9px;
            border-radius: 50%;
            flex-shrink: 0;
            transition: background 0.3s, box-shadow 0.3s;
        }
        .badge-text { color: var(--text); letter-spacing: 0.05em; }

        /* ── PANEL DE OPCIONES ── */
        .config-options {
            padding: 40px;
            border-left: 1px solid var(--border);
            overflow-y: auto;
            max-height: 600px;
        }
        .config-model-name { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 300; margin-bottom: 4px; }
        .config-base-price { color: var(--gold); font-size: 0.9rem; margin-bottom: 24px; letter-spacing: 0.05em; }
        .ficha-tecnica { background: var(--bg); border-radius: var(--radius); padding: 16px; margin-bottom: 32px; font-size: 0.8rem; color: var(--muted); line-height: 1.9; }

        /* ── SECCIÓN TÍTULO ── */
        .section-label {
            display: block;
            font-size: 9px;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 14px;
        }

        /* ── 4 SWATCHES DE COLOR ── */
        .color-swatches {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 32px;
        }

        .swatch {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 9px;
            padding: 14px 6px;
            border-radius: var(--radius);
            border: 1px solid transparent;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
            position: relative;
            background: transparent;
        }

        .swatch:hover { background: rgba(255,255,255,0.03); border-color: #2a2a2a; }

        /* Borde dorado + fondo sutil cuando está activo */
        .swatch.active {
            border-color: var(--gold-dim);
            background: rgba(201,169,110,0.05);
        }

        /* Círculo de color */
        .swatch-circle {
            width: 44px; height: 44px;
            border-radius: 50%;
            position: relative;
            transition: transform 0.2s;
            flex-shrink: 0;
        }
        .swatch:hover .swatch-circle { transform: scale(1.08); }

        /* Anillo dorado exterior — visible solo cuando activo */
        .swatch-circle::after {
            content: '';
            position: absolute;
            inset: -5px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            opacity: 0;
            transform: scale(0.8);
            transition: opacity 0.25s, transform 0.25s;
        }
        .swatch.active .swatch-circle::after { opacity: 1; transform: scale(1); }

        /* Reflejo brillante en la esfera (siempre presente) */
        .swatch-circle::before {
            content: '';
            position: absolute;
            top: 6px; left: 8px;
            width: 40%; height: 35%;
            border-radius: 50%;
            background: rgba(255,255,255,0.18);
            pointer-events: none;
        }

        /* Check mark centrado — visible solo cuando activo */
        .swatch-check {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            color: rgba(255,255,255,0.9);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .swatch.active .swatch-check { opacity: 1; }

        /* Nombre del color */
        .swatch-name {
            font-size: 0.68rem;
            color: var(--muted);
            text-align: center;
            line-height: 1.3;
            letter-spacing: 0.03em;
            transition: color 0.2s;
        }
        .swatch.active .swatch-name { color: var(--text); }

        /* ── Colores de cada swatch ── */
        .sw-negro .swatch-circle {
            background: radial-gradient(circle at 35% 30%, #2a2a2a, #080808);
            box-shadow: inset 0 0 0 1px #3a3a3a;
        }
        .sw-rojo .swatch-circle {
            background: radial-gradient(circle at 35% 30%, #d42b2b, #7c0a0a);
        }
        .sw-gris .swatch-circle {
            background: radial-gradient(circle at 35% 30%, #9a9a9a, #404040);
        }
        .sw-azul .swatch-circle {
            background: radial-gradient(circle at 35% 30%, #2157c5, #0a1f6e);
        }

        /* ── OPCIONES EQUIPAMIENTO ── */
        .option-group { margin-bottom: 16px; }
        .option-group label { display: flex; justify-content: space-between; font-size: 0.82rem; color: var(--muted); margin-bottom: 8px; }
        .option-group label span { color: var(--text); }
        .option-select {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 11px 14px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            outline: none;
            cursor: pointer;
            transition: border-color 0.3s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23555' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }
        .option-select:focus { border-color: var(--gold-dim); }

        /* ── RESUMEN PRECIO ── */
        .price-summary { background: var(--bg); border-radius: var(--radius); padding: 20px; margin-top: 24px; border: 1px solid var(--border); }
        .price-row { display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--muted); margin-bottom: 10px; }
        .price-total-row { display: flex; justify-content: space-between; align-items: baseline; border-top: 1px solid var(--border); padding-top: 14px; margin-top: 4px; }
        .price-total-label { font-size: 0.85rem; color: var(--text); letter-spacing: 0.1em; text-transform: uppercase; }
        .price-total-value { font-family: 'Cormorant Garamond', serif; font-size: 2.2rem; font-weight: 600; color: var(--gold); line-height: 1; }

        /* ── CTA ── */
        .btn-cta { width: 100%; padding: 14px; background: transparent; border: 1px solid var(--gold-dim); border-radius: var(--radius); color: var(--gold); font-family: 'DM Sans', sans-serif; font-size: 10px; font-weight: 500; letter-spacing: 0.35em; text-transform: uppercase; cursor: pointer; transition: all 0.35s; position: relative; overflow: hidden; margin-top: 16px; }
        .btn-cta::before { content: ''; position: absolute; inset: 0; background: var(--gold); transform: scaleX(0); transform-origin: left; transition: transform 0.35s cubic-bezier(0.4,0,0.2,1); }
        .btn-cta span { position: relative; z-index: 1; }
        .btn-cta:hover::before { transform: scaleX(1); }
        .btn-cta:hover { color: var(--bg); }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .config-grid { grid-template-columns: 1fr; }
            .config-options { max-height: none; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">LUXE MOTORS <em>MX</em></a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto gap-2">
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="info.php">Información</a></li>
                <li class="nav-item"><a class="nav-link active" href="config.php">Configurador</a></li>
                <li class="nav-item"><a class="nav-link" href="plan_pagos.php">Financiamiento</a></li>
                <li class="nav-item"><a class="nav-link special" href="compraventa.php">Compra-Venta</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-0">

    <div class="page-header">
        <p class="eyebrow">Personaliza tu vehículo</p>
        <h1>Configurador<br>LUXE MOTORS</h1>
        <p>Elige tu modelo, selecciona el color y personaliza cada detalle de tu próximo auto.</p>
    </div>

    <!-- CATEGORY TABS -->
    <div class="category-tabs" id="categoryTabs">
        <?php
        $stmt = $pdo->query("SELECT DISTINCT categoria FROM autos ORDER BY categoria");
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $cat):
            ?>
            <button class="cat-tab" onclick="filtrarCategoria('<?= htmlspecialchars($cat, ENT_QUOTES) ?>', this)">
                <?= htmlspecialchars($cat) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- GRID DE AUTOS -->
    <div class="autos-grid" id="listaAutos"></div>

    <!-- PANEL CONFIGURADOR -->
    <div class="configurator-panel" id="detalleAuto">
        <div class="config-grid">

            <!-- VISOR DE IMAGEN (dos capas para crossfade) -->
            <div class="config-visual">
                <div class="img-stage">
                    <!--
                        imgA comienza visible (opacity 1).
                        imgB comienza oculta (opacity 0).
                        Al cambiar de color se carga la nueva imagen
                        en la capa inactiva; cuando termina onload
                        se intercambia cuál tiene la clase .visible.
                        CERO filtros CSS — solo cambio de src.
                    -->
                    <img id="imgA" class="stage-img visible" src="" alt="Vista del vehículo"
                         onerror="this.src='https://placehold.co/800x480/050505/222?text=Imagen+no+disponible'">
                    <img id="imgB" class="stage-img"         src="" alt="Vista del vehículo"
                         onerror="this.src='https://placehold.co/800x480/050505/222?text=Imagen+no+disponible'">
                </div>

                <!-- Spinner mientras carga -->
                <div class="img-loader" id="imgLoader">
                    <div class="spinner"></div>
                </div>

                <!-- Badge color activo -->
                <div class="color-badge" id="colorBadge">
                    <div class="badge-dot" id="badgeDot"></div>
                    <span class="badge-text" id="badgeText">—</span>
                </div>
            </div>

            <!-- OPCIONES -->
            <div class="config-options">
                <h2 class="config-model-name" id="modeloAuto"></h2>
                <p class="config-base-price"  id="precioBase"></p>

                <div class="ficha-tecnica" id="fichaTecnica"></div>

                <!-- 4 SWATCHES DE COLOR -->
                <span class="section-label">Color del vehículo</span>
                <div class="color-swatches">

                    <div class="swatch sw-negro" data-color="negro" onclick="cambiarColor(this)">
                        <div class="swatch-circle">
                            <div class="swatch-check"><i class="fas fa-check"></i></div>
                        </div>
                        <span class="swatch-name">Negro<br>Obsidiana</span>
                    </div>

                    <div class="swatch sw-rojo" data-color="rojo" onclick="cambiarColor(this)">
                        <div class="swatch-circle">
                            <div class="swatch-check"><i class="fas fa-check"></i></div>
                        </div>
                        <span class="swatch-name">Rojo<br>Carmín</span>
                    </div>

                    <div class="swatch sw-gris" data-color="gris" onclick="cambiarColor(this)">
                        <div class="swatch-circle">
                            <div class="swatch-check"><i class="fas fa-check"></i></div>
                        </div>
                        <span class="swatch-name">Gris<br>Grafito</span>
                    </div>

                    <div class="swatch sw-azul" data-color="azul" onclick="cambiarColor(this)">
                        <div class="swatch-circle">
                            <div class="swatch-check"><i class="fas fa-check"></i></div>
                        </div>
                        <span class="swatch-name">Azul<br>Zafiro</span>
                    </div>

                </div>

                <!-- OPCIONES DE EQUIPAMIENTO -->
                <span class="section-label">Personalización</span>

                <div class="option-group">
                    <label>Transmisión <span id="lbl_trans">+$0</span></label>
                    <select class="option-select" id="trans" onchange="calcularTotal(); actualizarLabel('trans','lbl_trans')">
                        <option value="0">Manual</option>
                        <option value="25000">Automática (+$25,000)</option>
                    </select>
                </div>

                <div class="option-group">
                    <label>Paquete Seguridad <span id="lbl_seg">+$0</span></label>
                    <select class="option-select" id="seg" onchange="calcularTotal(); actualizarLabel('seg','lbl_seg')">
                        <option value="0">Básico</option>
                        <option value="35000">Avanzado (+$35,000)</option>
                    </select>
                </div>

                <div class="option-group">
                    <label>Llantas <span id="lbl_llantas">+$0</span></label>
                    <select class="option-select" id="llantas" onchange="calcularTotal(); actualizarLabel('llantas','lbl_llantas')">
                        <option value="0">Standard</option>
                        <option value="18000">Premium (+$18,000)</option>
                    </select>
                </div>

                <div class="option-group">
                    <label>Interior <span id="lbl_interior">+$0</span></label>
                    <select class="option-select" id="interior" onchange="calcularTotal(); actualizarLabel('interior','lbl_interior')">
                        <option value="0">Tela</option>
                        <option value="28000">Cuero (+$28,000)</option>
                    </select>
                </div>

                <div class="option-group">
                    <label>Sistema de Audio <span id="lbl_audio">+$0</span></label>
                    <select class="option-select" id="audio" onchange="calcularTotal(); actualizarLabel('audio','lbl_audio')">
                        <option value="0">Básico</option>
                        <option value="42000">Premium (+$42,000)</option>
                    </select>
                </div>

                <div class="price-summary">
                    <div class="price-row"><span>Precio base</span><span id="ps_base">$0</span></div>
                    <div class="price-row"><span>Opcionales</span><span id="ps_opcionales">$0</span></div>
                    <div class="price-total-row">
                        <span class="price-total-label">Total</span>
                        <span class="price-total-value" id="precioTotal">$0</span>
                    </div>
                </div>

                <button class="btn-cta" onclick="irAPlanPagos()">
                    <span>Ver plan de financiamiento →</span>
                </button>
            </div>

        </div>
    </div>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ===========================================================
   DATOS GLOBALES
   autosData almacena, indexado por id, todos los datos de
   cada auto cargado. Así el onclick solo pasa el id y no
   necesitamos serializar JSON dentro de atributos HTML.
=========================================================== */
const autosData = {};   // { [id]: { modelo, precio_base, imagenes:{negro,rojo,gris,azul}, ficha } }

let autoActualId   = null;  // id del auto actualmente mostrado
let colorActual    = 'negro'; // clave del color activo
let precioBase     = 0;

/* Control del crossfade */
let capasActiva    = 'A';   // 'A' o 'B' — cuál img tiene .visible ahora
let switching      = false; // true mientras la transición está en curso
let pendienteSrc   = null;  // si llega otro cambio durante switching, se guarda aquí

/* Metadatos visuales por color (dot del badge) */
const colorInfo = {
    negro: { dot: '#1a1a1a', sombra: '0 0 0 1px #444', label: 'Negro Obsidiana' },
    rojo:  { dot: '#9c1515', sombra: 'none',            label: 'Rojo Carmín'    },
    gris:  { dot: '#6a6a6a', sombra: 'none',            label: 'Gris Grafito'   },
    azul:  { dot: '#0d2f7a', sombra: 'none',            label: 'Azul Zafiro'    },
};

/* ===========================================================
   CROSSFADE
   Carga newSrc en la capa inactiva; cuando termina onload
   intercambia cuál tiene .visible.  Sin ningún filter CSS.
=========================================================== */
function crossfadeTo(newSrc) {
    const imgA   = document.getElementById('imgA');
    const imgB   = document.getElementById('imgB');
    const loader = document.getElementById('imgLoader');
    const activa   = capasActiva === 'A' ? imgA : imgB;
    const inactiva = capasActiva === 'A' ? imgB : imgA;

    /* Si la capa activa ya muestra esa imagen, nada que hacer */
    if (activa.src.endsWith(newSrc)) return;

    if (switching) {
        /* Guardar para ejecutar al terminar la transición actual */
        pendienteSrc = newSrc;
        return;
    }

    switching = true;
    loader.classList.add('loading');

    inactiva.onload = () => {
        loader.classList.remove('loading');
        inactiva.classList.add('visible');
        activa.classList.remove('visible');
        capasActiva = capasActiva === 'A' ? 'B' : 'A';
        switching   = false;

        /* Si llegó un cambio mientras hacíamos el fade, ejecutarlo ahora */
        if (pendienteSrc) {
            const next = pendienteSrc;
            pendienteSrc = null;
            crossfadeTo(next);
        }
    };

    inactiva.onerror = () => {
        /* Imagen no encontrada: mostrar placeholder sin romperse */
        loader.classList.remove('loading');
        inactiva.src = 'https://placehold.co/800x480/050505/333?text=Sin+imagen';
        inactiva.classList.add('visible');
        activa.classList.remove('visible');
        capasActiva = capasActiva === 'A' ? 'B' : 'A';
        switching   = false;
        if (pendienteSrc) {
            const next = pendienteSrc;
            pendienteSrc = null;
            crossfadeTo(next);
        }
    };

    /* Asignar src DESPUÉS de definir los handlers */
    inactiva.src = newSrc;
}

/* ===========================================================
   CAMBIAR COLOR
   Llamado por cada swatch. Actualiza UI y dispara crossfade.
=========================================================== */
function cambiarColor(swatch) {
    const color = swatch.dataset.color;  // 'negro' | 'rojo' | 'gris' | 'azul'
    if (!autoActualId) return;

    const auto = autosData[autoActualId];
    const src  = auto.imagenes[color];
    if (!src) return;

    /* Marcar swatch activo */
    document.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
    swatch.classList.add('active');

    /* Actualizar badge */
    const info = colorInfo[color];
    document.getElementById('badgeDot').style.background  = info.dot;
    document.getElementById('badgeDot').style.boxShadow   = info.sombra;
    document.getElementById('badgeText').textContent       = info.label;

    colorActual = color;

    /* Crossfade a la nueva imagen */
    crossfadeTo(src);
}

/* ===========================================================
   SELECCIONAR AUTO
   Solo recibe el id; lee los datos de autosData[id].
   Carga directamente la imagen del color negro como punto
   de partida, sin pasar por la imagen base.
=========================================================== */
function seleccionarAuto(id) {
    const auto = autosData[id];
    if (!auto) return;

    autoActualId = id;
    precioBase   = auto.precio;
    colorActual  = 'negro';

    /* Resaltar card */
    document.querySelectorAll('.auto-card').forEach(c => c.classList.remove('selected'));
    const card = document.getElementById('card-' + id);
    if (card) card.classList.add('selected');

    /* Rellenar datos del panel */
    document.getElementById('modeloAuto').textContent  = auto.modelo;
    document.getElementById('precioBase').textContent  = 'Precio base: $' + precioBase.toLocaleString('es-MX');
    document.getElementById('fichaTecnica').innerHTML  = auto.ficha.replace(/\|/g, '<br>');

    /* ── Inicializar visor con la imagen Negro ── */
    switching    = false;
    pendienteSrc = null;
    capasActiva  = 'A';

    const imgA = document.getElementById('imgA');
    const imgB = document.getElementById('imgB');

    /* Poner imgA como visible con la imagen negro */
    imgA.src = auto.imagenes.negro || auto.imagenes.base;
    imgB.src = '';
    imgA.classList.add('visible');
    imgB.classList.remove('visible');

    /* Badge: Negro Obsidiana */
    const info = colorInfo['negro'];
    document.getElementById('badgeDot').style.background = info.dot;
    document.getElementById('badgeDot').style.boxShadow  = info.sombra;
    document.getElementById('badgeText').textContent      = info.label;

    /* Swatch activo: negro */
    document.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
    const swNegro = document.querySelector('.sw-negro');
    if (swNegro) swNegro.classList.add('active');

    /* Resetear selectores de opciones */
    ['trans','seg','llantas','interior','audio'].forEach(sid => {
        document.getElementById(sid).selectedIndex = 0;
    });
    ['lbl_trans','lbl_seg','lbl_llantas','lbl_interior','lbl_audio'].forEach(lid => {
        document.getElementById(lid).textContent = '+$0';
    });

    calcularTotal();

    /* Mostrar panel y hacer scroll */
    const panel = document.getElementById('detalleAuto');
    panel.style.display = 'block';
    setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
}

/* ===========================================================
   FILTRAR POR CATEGORÍA
   Hace fetch a get_autos.php, guarda cada auto en autosData
   y construye el HTML del grid sin ningún JSON en onclick.
=========================================================== */
function filtrarCategoria(cat, btn) {
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');

    /* Ocultar el panel si estaba abierto */
    document.getElementById('detalleAuto').style.display = 'none';
    document.getElementById('listaAutos').innerHTML =
        '<p style="color:var(--muted);text-align:center;grid-column:1/-1;padding:48px 0;font-size:.85rem">Cargando modelos…</p>';

    fetch('get_autos.php?categoria=' + encodeURIComponent(cat))
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(autos => {
            if (!autos.length) {
                document.getElementById('listaAutos').innerHTML =
                    '<p style="color:var(--muted);text-align:center;grid-column:1/-1;padding:48px 0">No hay modelos en esta categoría.</p>';
                return;
            }

            let html = '';

            autos.forEach(a => {
                /* Guardar datos en el objeto global — clave numérica */
                autosData[a.id] = {
                    modelo:   a.modelo,
                    precio:   a.precio_base,
                    ficha:    a.ficha_tecnica,
                    imagenes: {
                        base:  a.imagen,
                        negro: a.imagen_negro || a.imagen,
                        rojo:  a.imagen_rojo  || a.imagen,
                        gris:  a.imagen_gris  || a.imagen,
                        azul:  a.imagen_azul  || a.imagen,
                    }
                };

                /* El onclick solo pasa el id numérico — sin JSON en el HTML */
                html += `
                <div class="auto-card" id="card-${a.id}" onclick="seleccionarAuto(${a.id})">
                    <div class="card-img-wrap">
                        <img src="${a.imagen}"
                             alt="${escapeHtml(a.modelo)}"
                             loading="lazy"
                             onerror="this.src='https://placehold.co/400x225/0a0a0a/333?text=Sin+imagen'">
                    </div>
                    <div class="card-body-inner">
                        <div class="card-modelo">${escapeHtml(a.modelo)}</div>
                        <div class="card-precio">Desde $${parseFloat(a.precio_base).toLocaleString('es-MX')}</div>
                    </div>
                </div>`;
            });

            document.getElementById('listaAutos').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('listaAutos').innerHTML =
                '<p style="color:var(--muted);text-align:center;grid-column:1/-1;padding:48px 0">Error al cargar los modelos. Verifica la conexión.</p>';
        });
}

/* ===========================================================
   UTILIDADES
=========================================================== */

/* Escapa caracteres peligrosos para insertar en innerHTML */
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function calcularTotal() {
    const extras = ['trans','seg','llantas','interior','audio']
        .reduce((sum, id) => sum + parseFloat(document.getElementById(id).value || 0), 0);
    const total = precioBase + extras;

    document.getElementById('ps_base').textContent       = '$' + precioBase.toLocaleString('es-MX');
    document.getElementById('ps_opcionales').textContent = extras > 0 ? '+$' + extras.toLocaleString('es-MX') : '$0';
    document.getElementById('precioTotal').textContent   = '$' + total.toLocaleString('es-MX');
    return total;
}

function actualizarLabel(selectId, labelId) {
    const val = parseFloat(document.getElementById(selectId).value) || 0;
    document.getElementById(labelId).textContent = val > 0 ? '+$' + val.toLocaleString('es-MX') : '+$0';
}

function irAPlanPagos() {
    const total = calcularTotal();
    const color = document.getElementById('badgeText').textContent;
    window.location.href = `plan_pagos.php?precio=${total}&auto=${autoActualId}&color=${encodeURIComponent(color)}`;
}
</script>
</body>
</html>