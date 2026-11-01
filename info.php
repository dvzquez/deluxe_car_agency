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
    <title>Nosotros — LUXE MOTORS MX</title>
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
            --radius:   6px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); font-family: 'DM Sans', sans-serif; font-weight: 300; }

        .container-lm { max-width: 1100px; margin: 0 auto; padding: 0 40px; }

        /* PAGE HEADER */
        .page-header {
            padding: 90px 40px 72px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .page-header .eyebrow {
            font-size: 10px;
            letter-spacing: 0.45em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header .eyebrow::before {
            content: ''; display: block;
            width: 40px; height: 1px;
            background: var(--gold);
        }

        .page-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(3rem, 6vw, 5.5rem);
            line-height: 0.95;
            margin-bottom: 32px;
        }

        .page-header h1 em { font-style: italic; color: var(--gold); }

        .page-header .lead {
            font-size: 1.05rem;
            color: var(--muted);
            line-height: 1.9;
            max-width: 600px;
        }

        /* INTRO SPLIT */
        .intro-section {
            border-top: 1px solid var(--border);
            padding: 80px 0;
        }

        .intro-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .intro-img {
            aspect-ratio: 4/3;
            border-radius: var(--radius);
            overflow: hidden;
            position: relative;
        }

        .intro-img img {
            width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0.7;
            transition: opacity 0.5s;
        }

        .intro-img:hover img { opacity: 0.9; }

        .intro-img::after {
            content: '';
            position: absolute;
            inset: 0;
            border: 1px solid rgba(201,169,110,0.15);
            border-radius: var(--radius);
            pointer-events: none;
        }

        .intro-content .section-eyebrow {
            font-size: 10px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
        }

        .intro-content h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 2.4rem;
            line-height: 1.1;
            margin-bottom: 24px;
        }

        .intro-content p {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.9;
            margin-bottom: 16px;
        }

        /* STATS */
        .stats-section {
            background: var(--surface);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 64px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .stat-item {
            background: var(--surface);
            padding: 40px 32px;
            text-align: center;
        }

        .stat-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 3rem;
            font-weight: 600;
            color: var(--gold);
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--muted);
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }

        /* VALUES */
        .values-section { padding: 100px 0; }

        .section-header {
            margin-bottom: 56px;
        }

        .section-eyebrow {
            font-size: 10px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-eyebrow::before {
            content: ''; display: block;
            width: 32px; height: 1px;
            background: var(--gold);
        }

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 2.4rem;
            line-height: 1.1;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .value-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 36px 28px;
            transition: border-color 0.3s;
        }

        .value-card:hover { border-color: var(--gold-dim); }

        .value-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: rgba(201,169,110,0.07);
            border: 1px solid rgba(201,169,110,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 1rem;
            margin-bottom: 24px;
        }

        .value-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.4rem;
            font-weight: 300;
            margin-bottom: 12px;
        }

        .value-text { font-size: 0.85rem; color: var(--muted); line-height: 1.8; }

        /* CONTACT / LOCATION */
        .contact-section {
            border-top: 1px solid var(--border);
            padding: 80px 0;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
        }

        .contact-info { }

        .contact-row {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            padding: 20px 0;
            border-bottom: 1px solid var(--border);
        }

        .contact-row:first-child { padding-top: 0; }
        .contact-row:last-child { border-bottom: none; }

        .contact-row-icon {
            width: 40px; height: 40px;
            border-radius: var(--radius);
            background: rgba(201,169,110,0.07);
            border: 1px solid rgba(201,169,110,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .contact-row-label {
            font-size: 9px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .contact-row-value { font-size: 0.9rem; color: var(--text); }

        .map-placeholder {
            aspect-ratio: 4/3;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--muted);
        }

        .map-placeholder i { font-size: 2rem; color: var(--gold); }
        .map-placeholder p { font-size: 0.85rem; }

        @media (max-width: 768px) {
            .container-lm { padding: 0 20px; }
            .page-header { padding: 60px 20px 48px; }
            .intro-grid, .contact-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .values-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- PAGE HEADER -->
<div class="page-header">
    <p class="eyebrow">Quiénes somos</p>
    <h1>
        Más de una <em>década</em><br>
        en el lujo automotriz
    </h1>
    <p class="lead">
        LUXE MOTORS MX nació con una misión clara: llevar la experiencia de compra de un auto premium a un nivel completamente nuevo, con transparencia, tecnología y un servicio personalizado que ninguna otra agencia ofrece en México.
    </p>
</div>

<!-- INTRO -->
<section class="intro-section">
    <div class="container-lm">
        <div class="intro-grid">
            <div class="intro-img">
                <img src="https://images.unsplash.com/photo-1580274455191-1c62238fa333?w=800&q=80"
                     alt="Showroom LUXE MOTORS MX">
            </div>
            <div class="intro-content">
                <p class="section-eyebrow">Nuestra historia</p>
                <h2>Nacimos para redefinir la compra de autos de lujo</h2>
                <p>Fundada en 2012 en Ciudad de México, LUXE MOTORS MX comenzó como una pequeña agencia especializada en vehículos deportivos. Hoy somos la agencia premium más innovadora de la CDMX, con un catálogo de <?= $pdo->query("SELECT COUNT(*) FROM autos")->fetchColumn() ?> modelos y cientos de clientes satisfechos.</p>
                <p>Nuestro diferenciador es claro: tecnología al servicio del comprador. Fuimos los primeros en ofrecer un configurador de autos en tiempo real, financiamiento transparente y valuación instantánea de autos usados directamente desde la web.</p>
                <p>Cada visita a LUXE MOTORS MX — presencial o digital — es una experiencia diseñada para el exigente comprador mexicano que no quiere compromisos, sino certeza.</p>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats-section">
    <div class="container-lm">
        <?php
        $totalModelos = $pdo->query("SELECT COUNT(*) FROM autos")->fetchColumn();
        $totalCats = $pdo->query("SELECT COUNT(DISTINCT categoria) FROM autos")->fetchColumn();
        $totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
        ?>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-num">12+</div>
                <div class="stat-label">Años de experiencia</div>
            </div>
            <div class="stat-item">
                <div class="stat-num"><?= $totalModelos ?></div>
                <div class="stat-label">Modelos en catálogo</div>
            </div>
            <div class="stat-item">
                <div class="stat-num"><?= $totalClientes ?>+</div>
                <div class="stat-label">Clientes satisfechos</div>
            </div>
            <div class="stat-item">
                <div class="stat-num"><?= $totalCats ?></div>
                <div class="stat-label">Categorías de vehículos</div>
            </div>
        </div>
    </div>
</section>

<!-- VALUES -->
<section class="values-section">
    <div class="container-lm">
        <div class="section-header">
            <p class="section-eyebrow">Filosofía</p>
            <h2 class="section-title">Nuestros valores</h2>
        </div>

        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-eye"></i></div>
                <h3 class="value-title">Transparencia total</h3>
                <p class="value-text">Sin costos ocultos ni letras pequeñas. El precio que ves en el configurador es el precio real, con todas las opciones desglosadas.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-bolt"></i></div>
                <h3 class="value-title">Agilidad digital</h3>
                <p class="value-text">Configura tu auto, obtén tu plan de financiamiento y genera tu cotización sin pisar la agencia. Todo en minutos desde cualquier dispositivo.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-shield-alt"></i></div>
                <h3 class="value-title">Confianza garantizada</h3>
                <p class="value-text">Garantía de fábrica, servicio postventa certificado y asesor personal para cada cliente. Tu tranquilidad es nuestra responsabilidad.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-leaf"></i></div>
                <h3 class="value-title">Movilidad sustentable</h3>
                <p class="value-text">Amplia oferta de vehículos eléctricos e híbridos. Creemos en una movilidad de lujo que también cuide el planeta.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-user-tie"></i></div>
                <h3 class="value-title">Servicio personalizado</h3>
                <p class="value-text">Cada comprador tiene un asesor dedicado que lo acompaña desde la primera búsqueda hasta la entrega de las llaves.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-handshake"></i></div>
                <h3 class="value-title">Compra-venta justa</h3>
                <p class="value-text">Valuación de mercado en tiempo real para tu auto usado. Oferta en efectivo, proceso simple y pago inmediato garantizado.</p>
            </div>
        </div>
    </div>
</section>

<!-- CONTACT -->
<section class="contact-section">
    <div class="container-lm">
        <div class="section-header">
            <p class="section-eyebrow">Visítanos</p>
            <h2 class="section-title">Encuéntranos en CDMX</h2>
        </div>

        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-row">
                    <div class="contact-row-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <div class="contact-row-label">Dirección</div>
                        <div class="contact-row-value">Av. Insurgentes Sur 1602, Col. Crédito Constructor<br>Benito Juárez, CDMX, 03940</div>
                    </div>
                </div>
                <div class="contact-row">
                    <div class="contact-row-icon"><i class="fas fa-phone"></i></div>
                    <div>
                        <div class="contact-row-label">Teléfono</div>
                        <div class="contact-row-value">+52 55 1234 5678</div>
                    </div>
                </div>
                <div class="contact-row">
                    <div class="contact-row-icon"><i class="fas fa-envelope"></i></div>
                    <div>
                        <div class="contact-row-label">Email</div>
                        <div class="contact-row-value">ventas@luxemotors.mx</div>
                    </div>
                </div>
                <div class="contact-row">
                    <div class="contact-row-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="contact-row-label">Horario</div>
                        <div class="contact-row-value">Lunes–Viernes: 9:00 – 19:00 hrs<br>Sábado: 10:00 – 16:00 hrs</div>
                    </div>
                </div>
            </div>

            <div class="map-placeholder">
                <i class="fas fa-map-marked-alt"></i>
                <p>Av. Insurgentes Sur · CDMX</p>
                <p style="font-size:0.75rem;color:#333;">Integra aquí Google Maps Embed API</p>
            </div>
        </div>
    </div>
</section>

</body>
</html>