<?php include 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LUXE MOTORS MX | Compra · Vende · Configura</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg:       #070707;
            --surface:  #0f0f0f;
            --border:   #1c1c1c;
            --gold:     #C9A96E;
            --gold-dim: #7a6540;
            --text:     #EBEBEB;
            --muted:    #555;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 300;
            overflow-x: hidden;
        }

        /* ── HERO ── */
        .lm-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .lm-hero__bg {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(175deg, #050505 0%, #0a0a0a 40%, #060606 100%);
        }

        /* Geometric accent lines */
        .lm-hero__lines {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .lm-hero__lines::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -5%;
            width: 60vw;
            height: 130vh;
            border: 1px solid rgba(201,169,110,0.06);
            border-radius: 50% 0 0 50%;
            transform: rotate(-8deg);
        }

        .lm-hero__lines::after {
            content: '';
            position: absolute;
            top: 10%;
            right: 5%;
            width: 40vw;
            height: 80vh;
            border: 1px solid rgba(201,169,110,0.04);
            border-radius: 50% 0 0 50%;
            transform: rotate(-5deg);
        }

        .lm-hero__glow {
            position: absolute;
            top: 30%;
            right: 10%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,169,110,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .lm-hero__inner {
            position: relative;
            z-index: 2;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 60px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 80px;
        }

        .lm-hero__content { animation: heroIn 1s ease 0.2s both; }

        @keyframes heroIn {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .lm-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 10px;
            letter-spacing: 0.45em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 28px;
        }

        .lm-hero__eyebrow::before {
            content: '';
            display: block;
            width: 40px;
            height: 1px;
            background: var(--gold);
        }

        .lm-hero__title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(3.5rem, 6vw, 6rem);
            line-height: 0.95;
            letter-spacing: -0.01em;
            margin-bottom: 32px;
        }

        .lm-hero__title em {
            font-style: italic;
            color: var(--gold);
        }

        .lm-hero__subtitle {
            font-size: 1rem;
            color: var(--muted);
            line-height: 1.8;
            max-width: 420px;
            margin-bottom: 48px;
        }

        .lm-hero__actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-primary-hero {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: var(--gold);
            color: #070707;
            text-decoration: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            border-radius: 3px;
            transition: all 0.3s;
        }

        .btn-primary-hero:hover {
            background: #d4b47e;
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(201,169,110,0.25);
        }

        .btn-outline-hero {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: transparent;
            color: var(--text);
            text-decoration: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 400;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            border-radius: 3px;
            border: 1px solid #2a2a2a;
            transition: all 0.3s;
        }

        .btn-outline-hero:hover {
            border-color: #444;
            color: var(--gold);
        }

        /* Hero stats */
        .lm-hero__stats {
            display: flex;
            gap: 40px;
            margin-top: 56px;
            padding-top: 48px;
            border-top: 1px solid #1a1a1a;
        }

        .hero-stat .num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem;
            font-weight: 600;
            color: var(--gold);
            display: block;
            line-height: 1;
        }

        .hero-stat .lbl {
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            margin-top: 4px;
        }

        /* Hero visual side */
        .lm-hero__visual {
            position: relative;
            animation: heroVisualIn 1.2s ease 0.5s both;
        }

        @keyframes heroVisualIn {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .hero-car-showcase {
            position: relative;
        }

        .hero-car-main {
            width: 100%;
            aspect-ratio: 4/3;
            background: linear-gradient(135deg, #0f0f0f, #0a0a0a);
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #1a1a1a;
        }

        .hero-car-main img {
            width: 100%;
            height: 80%;
            object-fit: cover;
            opacity: 0.85;
            transition: opacity 0.5s;
        }

        .hero-car-main:hover img { opacity: 1; }

        .hero-car-badge {
            position: absolute;
            top: 20px; left: 20px;
            background: rgba(7,7,7,0.85);
            border: 1px solid #1c1c1c;
            border-radius: 4px;
            padding: 10px 16px;
            backdrop-filter: blur(8px);
        }

        .hero-car-badge .modelo { font-family: 'Cormorant Garamond', serif; font-size: 1.1rem; font-weight: 300; }
        .hero-car-badge .precio { font-size: 0.75rem; color: var(--gold); margin-top: 2px; }

        .hero-scroll-indicator {
            position: absolute;
            bottom: -48px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .hero-scroll-indicator span {
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .scroll-line {
            width: 1px;
            height: 48px;
            background: linear-gradient(to bottom, var(--muted), transparent);
            animation: scrollPulse 2s ease-in-out infinite;
        }

        @keyframes scrollPulse {
            0%, 100% { opacity: 0.3; transform: scaleY(1); }
            50% { opacity: 0.8; transform: scaleY(1.2); }
        }

        /* ── SECTION BASE ── */
        .section {
            padding: 100px 0;
        }

        .container-lm {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 60px;
        }

        .section-head {
            margin-bottom: 56px;
        }

        .section-eyebrow {
            font-size: 10px;
            letter-spacing: 0.45em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-eyebrow::before {
            content: '';
            display: block;
            width: 32px;
            height: 1px;
            background: var(--gold);
        }

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(2rem, 3.5vw, 3rem);
            line-height: 1.05;
        }

        /* ── CATEGORIES SECTION ── */
        .cat-section { background: var(--surface); }

        .cat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
        }

        .cat-tile {
            background: var(--surface);
            padding: 48px 32px;
            text-decoration: none;
            color: var(--text);
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: background 0.3s;
            cursor: pointer;
        }

        .cat-tile:hover { background: #141414; }

        .cat-tile:hover .cat-arrow { transform: translateX(4px); opacity: 1; }

        .cat-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: rgba(201,169,110,0.08);
            border: 1px solid rgba(201,169,110,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 1rem;
        }

        .cat-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem;
            font-weight: 300;
        }

        .cat-count { font-size: 0.8rem; color: var(--muted); }

        .cat-arrow {
            color: var(--gold);
            font-size: 0.8rem;
            margin-top: auto;
            opacity: 0;
            transition: all 0.3s;
        }

        /* ── FEATURED CARS ── */
        .cars-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .car-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            text-decoration: none;
            color: var(--text);
            display: block;
            transition: transform 0.35s, border-color 0.35s, box-shadow 0.35s;
        }

        .car-card:hover {
            transform: translateY(-6px);
            border-color: var(--gold-dim);
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
        }

        .car-card__img {
            aspect-ratio: 16/10;
            background: #0a0a0a;
            overflow: hidden;
        }

        .car-card__img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s, opacity 0.4s;
            opacity: 0.85;
        }

        .car-card:hover .car-card__img img {
            transform: scale(1.05);
            opacity: 1;
        }

        .car-card__body {
            padding: 24px;
        }

        .car-card__cat {
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 6px;
        }

        .car-card__modelo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.4rem;
            font-weight: 300;
            margin-bottom: 16px;
        }

        .car-card__footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .car-card__price {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.2rem;
            color: var(--gold);
        }

        .car-card__cta {
            font-size: 0.75rem;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.25s;
        }

        .car-card:hover .car-card__cta { color: var(--gold); }

        /* ── SERVICES SECTION ── */
        .services-section { border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 48px;
        }

        .service-item { text-align: center; }

        .service-icon {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: rgba(201,169,110,0.06);
            border: 1px solid rgba(201,169,110,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 1.3rem;
            margin: 0 auto 24px;
        }

        .service-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.4rem;
            font-weight: 300;
            margin-bottom: 12px;
        }

        .service-text {
            font-size: 0.875rem;
            color: var(--muted);
            line-height: 1.8;
        }

        /* ── CTA BANNER ── */
        .cta-banner {
            background: linear-gradient(135deg, #0d0d0d, #0a0a0a);
            border-top: 1px solid var(--border);
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 60% 80% at 50% 50%, rgba(201,169,110,0.05) 0%, transparent 70%);
        }

        .cta-banner h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(2rem, 4vw, 3.5rem);
            margin-bottom: 16px;
            position: relative;
        }

        .cta-banner p { color: var(--muted); margin-bottom: 40px; position: relative; }

        .cta-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; position: relative; }

        /* ── FOOTER ── */
        footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 48px 0 32px;
        }

        .footer-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 60px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px;
        }

        .footer-brand-name {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 1.4rem;
            margin-bottom: 12px;
        }

        .footer-brand-name em { font-style: normal; color: var(--gold); }

        .footer-desc { font-size: 0.82rem; color: var(--muted); line-height: 1.8; }

        .footer-col-title {
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 16px;
        }

        .footer-link {
            display: block;
            font-size: 0.82rem;
            color: #444;
            text-decoration: none;
            margin-bottom: 8px;
            transition: color 0.2s;
        }

        .footer-link:hover { color: var(--text); }

        .footer-bottom {
            max-width: 1280px;
            margin: 32px auto 0;
            padding: 24px 60px 0;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: var(--muted);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .lm-hero__inner { grid-template-columns: 1fr; gap: 48px; padding: 60px 32px; }
            .lm-hero__visual { display: none; }
            .cat-grid { grid-template-columns: 1fr 1fr; }
            .cars-grid { grid-template-columns: 1fr 1fr; }
            .footer-inner { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 640px) {
            .container-lm { padding: 0 20px; }
            .cars-grid { grid-template-columns: 1fr; }
            .services-grid { grid-template-columns: 1fr; gap: 32px; }
            .cat-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- ── HERO ── -->
<section class="lm-hero">
    <div class="lm-hero__bg"></div>
    <div class="lm-hero__lines"></div>
    <div class="lm-hero__glow"></div>

    <div class="lm-hero__inner">
        <div class="lm-hero__content">
            <p class="lm-hero__eyebrow">Agencia Premium · CDMX</p>
            <h1 class="lm-hero__title">
                Vive el<br>
                <em>lujo</em> que<br>
                mereces
            </h1>
            <p class="lm-hero__subtitle">
                Compra, vende y configura tu próximo auto de lujo con total transparencia.
                Financiamiento a tu medida, catálogo premium y valuación inmediata.
            </p>
            <div class="lm-hero__actions">
                <a href="config.php" class="btn-primary-hero">
                    <i class="fas fa-sliders-h fa-xs"></i> Configurar mi auto
                </a>
                <a href="compraventa.php" class="btn-outline-hero">
                    Vender mi auto
                </a>
            </div>

            <div class="lm-hero__stats">
                <?php
                $totalModelos = $pdo->query("SELECT COUNT(*) FROM autos")->fetchColumn();
                $totalCats = $pdo->query("SELECT COUNT(DISTINCT categoria) FROM autos")->fetchColumn();
                $precioPromedio = $pdo->query("SELECT AVG(precio_base) FROM autos")->fetchColumn();
                ?>
                <div class="hero-stat">
                    <span class="num"><?= $totalModelos ?>+</span>
                    <span class="lbl">Modelos</span>
                </div>
                <div class="hero-stat">
                    <span class="num"><?= $totalCats ?></span>
                    <span class="lbl">Categorías</span>
                </div>
                <div class="hero-stat">
                    <span class="num">100%</span>
                    <span class="lbl">Garantía</span>
                </div>
                <div class="hero-stat">
                    <span class="num">CDMX</span>
                    <span class="lbl">Sede</span>
                </div>
            </div>
        </div>

        <!-- Visual: carousel del auto destacado -->
        <div class="lm-hero__visual">
            <?php
            $featured = $pdo->query("SELECT modelo, precio_base, imagen FROM autos ORDER BY precio_base DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="hero-car-showcase">
                <div class="hero-car-main">
                    <img src="<?= htmlspecialchars($featured['imagen'] ?? '') ?>"
                         alt="<?= htmlspecialchars($featured['modelo'] ?? '') ?>"
                         onerror="this.src='https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&q=80'">
                </div>
                <div class="hero-car-badge">
                    <div class="modelo"><?= htmlspecialchars($featured['modelo'] ?? 'Modelo Destacado') ?></div>
                    <div class="precio">Desde $<?= number_format($featured['precio_base'] ?? 0, 0, '.', ',') ?></div>
                </div>
            </div>
            <div class="hero-scroll-indicator">
                <div class="scroll-line"></div>
                <span></span>
            </div>
        </div>
    </div>
</section>

<!-- ── CATEGORIES ── -->
<section class="section cat-section">
    <div class="container-lm">
        <div class="section-head">
            <p class="section-eyebrow">Catálogo</p>
            <h2 class="section-title">Explora por categoría</h2>
        </div>

        <?php
        $cats = $pdo->query("SELECT categoria, COUNT(*) as total FROM autos GROUP BY categoria ORDER BY categoria")->fetchAll(PDO::FETCH_ASSOC);
        $catIcons = ['Deportivos' => 'fa-bolt', 'SUVs' => 'fa-mountain', 'Sedanes' => 'fa-car', 'Eléctricos' => 'fa-leaf'];
        ?>
        <div class="cat-grid">
            <?php foreach ($cats as $cat): ?>
                <a class="cat-tile" href="config.php?cat=<?= urlencode($cat['categoria']) ?>">
                    <div class="cat-icon">
                        <i class="fas <?= $catIcons[$cat['categoria']] ?? 'fa-car' ?>"></i>
                    </div>
                    <div class="cat-name"><?= htmlspecialchars($cat['categoria']) ?></div>
                    <div class="cat-count"><?= $cat['total'] ?> modelos disponibles</div>
                    <div class="cat-arrow">→</div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── FEATURED CARS ── -->
<section class="section">
    <div class="container-lm">
        <div class="section-head" style="display:flex;justify-content:space-between;align-items:flex-end;">
            <div>
                <p class="section-eyebrow">Selección</p>
                <h2 class="section-title">Modelos destacados</h2>
            </div>
            <a href="config.php" style="font-size:0.82rem;color:var(--gold);text-decoration:none;">Ver todos →</a>
        </div>

        <?php
        $destacados = $pdo->query("SELECT id, modelo, categoria, precio_base, imagen FROM autos ORDER BY precio_base DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="cars-grid">
            <?php foreach ($destacados as $auto): ?>
                <a class="car-card" href="config.php">
                    <div class="car-card__img">
                        <img src="<?= htmlspecialchars($auto['imagen']) ?>"
                             alt="<?= htmlspecialchars($auto['modelo']) ?>"
                             onerror="this.src='https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=600&q=80'">
                    </div>
                    <div class="car-card__body">
                        <p class="car-card__cat"><?= htmlspecialchars($auto['categoria']) ?></p>
                        <h3 class="car-card__modelo"><?= htmlspecialchars($auto['modelo']) ?></h3>
                        <div class="car-card__footer">
                            <span class="car-card__price">$<?= number_format($auto['precio_base'], 0, '.', ',') ?></span>
                            <span class="car-card__cta">Configurar <i class="fas fa-arrow-right fa-xs"></i></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── SERVICES ── -->
<section class="section services-section">
    <div class="container-lm">
        <div class="section-head" style="text-align:center; margin-bottom:64px;">
            <p class="section-eyebrow" style="justify-content:center;">¿Por qué elegirnos?</p>
            <h2 class="section-title">Tres razones para confiar<br>en LUXE MOTORS MX</h2>
        </div>

        <div class="services-grid">
            <div class="service-item">
                <div class="service-icon"><i class="fas fa-sliders-h"></i></div>
                <h3 class="service-title">Configuración total</h3>
                <p class="service-text">Personaliza cada detalle de tu auto: color, transmisión, interior, audio y paquetes de seguridad. Obtén el precio en tiempo real.</p>
            </div>
            <div class="service-item">
                <div class="service-icon"><i class="fas fa-percent"></i></div>
                <h3 class="service-title">Financiamiento a tu medida</h3>
                <p class="service-text">Calcula tu plan de pagos con cualquier enganche y plazo. Tasas competitivas, tabla de amortización completa y factura digital.</p>
            </div>
            <div class="service-item">
                <div class="service-icon"><i class="fas fa-exchange-alt"></i></div>
                <h3 class="service-title">Compra-Venta confiable</h3>
                <p class="service-text">Valúa tu auto en minutos con nuestra herramienta de tasación. Oferta en efectivo al instante, sin sorpresas ni letras pequeñas.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA BANNER ── -->
<section class="cta-banner">
    <div class="container-lm">
        <h2>¿Listo para tu próximo auto<br>de <em style="font-style:italic;color:var(--gold)">lujo</em>?</h2>
        <p>Más de <?= $totalModelos ?> modelos te esperan. Configura, financia y maneja.</p>
        <div class="cta-actions">
            <a href="config.php" class="btn-primary-hero">Ir al Configurador</a>
            <a href="compraventa.php" class="btn-outline-hero">Valuar mi auto actual</a>
        </div>
    </div>
</section>

<!-- ── FOOTER ── -->
<footer>
    <div class="footer-inner">
        <div>
            <div class="footer-brand-name">LUXE MOTORS <em>MX</em></div>
            <p class="footer-desc">Agencia premium de compra, venta, configuración y financiamiento de autos en Ciudad de México. RFC: LUXM250416ABC</p>
        </div>
        <div>
            <p class="footer-col-title">Navegación</p>
            <a href="index.php"      class="footer-link">Inicio</a>
            <a href="info.php"       class="footer-link">Nosotros</a>
            <a href="config.php"     class="footer-link">Configurador</a>
            <a href="plan_pagos.php" class="footer-link">Financiamiento</a>
        </div>
        <div>
            <p class="footer-col-title">Servicios</p>
            <a href="compraventa.php" class="footer-link">Compra-Venta</a>
            <a href="plan_pagos.php"  class="footer-link">Plan de Pagos</a>
            <a href="factura.php"     class="footer-link">Facturación</a>
        </div>
        <div>
            <p class="footer-col-title">Contacto</p>
            <span class="footer-link">📍 Av. Insurgentes Sur, CDMX</span>
            <span class="footer-link">📞 +52 55 1234 5678</span>
            <span class="footer-link">✉️ ventas@luxemotors.mx</span>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© 2026 LUXE MOTORS MX. Todos los derechos reservados.</span>
        <span>Diseñado con precisión alemana 🚗</span>
    </div>
</footer>

</body>
</html>