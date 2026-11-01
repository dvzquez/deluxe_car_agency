<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
include '../config/db.php';

$totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$totalAutos = $pdo->query("SELECT COUNT(*) FROM autos")->fetchColumn();
$totalVentas = 0;
$totalTasaciones = 0;
$ultimasVentas = [];
$ultimasTasaciones = [];

try {
    $totalVentas = $pdo->query("SELECT COUNT(*) FROM ventas")->fetchColumn();
    $ultimasVentas = $pdo->query("SELECT v.no_factura,v.total_a_pagar,v.status,v.fecha_venta,a.modelo FROM ventas v LEFT JOIN autos a ON a.id=v.auto_id ORDER BY v.fecha_venta DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}
try {
    $totalTasaciones = $pdo->query("SELECT COUNT(*) FROM tasaciones WHERE status='pendiente'")->fetchColumn();
    $ultimasTasaciones = $pdo->query("SELECT nombre_vendedor,marca,modelo,anio,valor_oferta,status FROM tasaciones ORDER BY fecha_solicitud DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

$autosPorCat = $pdo->query("SELECT categoria, COUNT(*) as total FROM autos GROUP BY categoria")->fetchAll(PDO::FETCH_ASSOC);
$ultimosClientes = $pdo->query("SELECT id,nombre,apellido,fecha_registro FROM clientes ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard — LUXE MOTORS MX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #070707;
            --surface: #0f0f0f;
            --s2: #141414;
            --border: #1c1c1c;
            --gold: #C9A96E;
            --gold-dim: #7a6540;
            --text: #EBEBEB;
            --muted: #555;
            --success: #4caf7d;
            --warning: #e0a535;
            --radius: 6px
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 300;
            display: flex;
            min-height: 100vh
        }

        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 40px 0;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100
        }

        .sidebar-logo {
            padding: 0 32px 40px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 32px
        }

        .sidebar-logo .eyebrow {
            font-size: 9px;
            letter-spacing: .4em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px
        }

        .sidebar-logo h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 1.5rem;
            letter-spacing: .05em
        }

        .sidebar-logo h1 em {
            font-style: normal;
            color: var(--gold)
        }

        .nav-section {
            padding: 0 20px;
            margin-bottom: 8px
        }

        .nav-label {
            font-size: 9px;
            letter-spacing: .35em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 0 12px;
            margin-bottom: 8px
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: var(--radius);
            color: #666;
            text-decoration: none;
            font-size: .875rem;
            font-weight: 400;
            transition: all .25s;
            margin-bottom: 2px;
            position: relative
        }

        .nav-item i {
            width: 18px;
            text-align: center;
            font-size: .8rem
        }

        .nav-item:hover {
            color: var(--text);
            background: var(--s2)
        }

        .nav-item.active {
            color: var(--gold);
            background: rgba(201, 169, 110, .07)
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 2px;
            background: var(--gold);
            border-radius: 0 2px 2px 0
        }

        .nav-item.danger {
            color: #7a3535
        }

        .nav-item.danger:hover {
            color: #e05252;
            background: rgba(224, 82, 82, .06)
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 24px 32px 0;
            border-top: 1px solid var(--border)
        }

        .admin-badge {
            display: flex;
            align-items: center;
            gap: 10px
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(201, 169, 110, .15);
            border: 1px solid var(--gold-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: .75rem
        }

        .admin-info .name {
            font-size: .85rem;
            font-weight: 500;
            color: var(--text)
        }

        .admin-info .role {
            font-size: .7rem;
            color: var(--muted);
            letter-spacing: .1em;
            text-transform: uppercase
        }

        .main {
            margin-left: 260px;
            flex: 1;
            padding: 48px 56px;
            min-width: 0
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 48px
        }

        .page-title .eyebrow {
            font-size: 10px;
            letter-spacing: .4em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px
        }

        .page-title h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 2.8rem;
            line-height: 1
        }

        .topbar-meta {
            text-align: right;
            font-size: .8rem;
            color: var(--muted)
        }

        .topbar-meta strong {
            color: var(--text);
            font-weight: 500
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 40px
        }

        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            position: relative;
            overflow: hidden;
            animation: fadeUp .5s ease forwards;
            opacity: 0;
            transform: translateY(12px)
        }

        .kpi-card:nth-child(1) {
            animation-delay: .05s
        }

        .kpi-card:nth-child(2) {
            animation-delay: .1s
        }

        .kpi-card:nth-child(3) {
            animation-delay: .15s
        }

        .kpi-card:nth-child(4) {
            animation-delay: .2s
        }

        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .kpi-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            opacity: 0;
            transition: opacity .3s
        }

        .kpi-card:hover::after {
            opacity: 1
        }

        .kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--radius);
            background: rgba(201, 169, 110, .08);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: .85rem;
            margin-bottom: 16px
        }

        .kpi-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.6rem;
            font-weight: 600;
            line-height: 1;
            color: var(--text);
            margin-bottom: 4px
        }

        .kpi-label {
            font-size: .72rem;
            color: var(--muted);
            letter-spacing: .1em;
            text-transform: uppercase
        }

        .kpi-alert {
            position: absolute;
            top: 20px;
            right: 16px;
            font-size: .62rem;
            padding: 3px 8px;
            border-radius: 12px
        }

        .badge-pending {
            background: rgba(224, 165, 53, .1);
            color: var(--warning);
            border: 1px solid rgba(224, 165, 53, .3)
        }

        .badge-ok {
            background: rgba(76, 175, 125, .1);
            color: var(--success);
            border: 1px solid rgba(76, 175, 125, .3)
        }

        .badge-red {
            background: rgba(224, 82, 82, .1);
            color: #e05252;
            border: 1px solid rgba(224, 82, 82, .3)
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 12px;
            font-size: .62rem;
            letter-spacing: .08em;
            text-transform: uppercase
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px
        }

        .grid-b {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            animation: fadeUp .5s ease .25s forwards;
            opacity: 0;
            transform: translateY(12px)
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px;
            border-bottom: 1px solid var(--border)
        }

        .panel-title {
            font-size: 10px;
            letter-spacing: .25em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 400
        }

        .panel-action {
            font-size: .78rem;
            color: var(--gold);
            text-decoration: none
        }

        .panel-action:hover {
            opacity: .7
        }

        .mini-table {
            width: 100%;
            border-collapse: collapse
        }

        .mini-table th {
            padding: 10px 20px;
            text-align: left;
            font-size: 9px;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 400;
            border-bottom: 1px solid var(--border)
        }

        .mini-table td {
            padding: 12px 20px;
            font-size: .82rem;
            border-bottom: 1px solid rgba(28, 28, 28, .6)
        }

        .mini-table tr:last-child td {
            border-bottom: none
        }

        .mini-table tbody tr:hover td {
            background: var(--s2)
        }

        .av {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(201, 169, 110, .1);
            border: 1px solid rgba(201, 169, 110, .2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .62rem;
            font-weight: 500;
            color: var(--gold);
            vertical-align: middle;
            margin-right: 8px
        }

        .tm {
            color: var(--muted)
        }

        .tg {
            color: var(--gold)
        }

        .cat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 22px;
            border-bottom: 1px solid rgba(28, 28, 28, .5);
            font-size: .85rem
        }

        .cat-item:last-child {
            border-bottom: none
        }

        .cat-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--gold);
            margin-right: 10px;
            flex-shrink: 0;
            display: inline-block
        }

        .cat-n {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--gold)
        }

        .ag {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 16px
        }

        .ab {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 16px 10px;
            background: var(--s2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--muted);
            text-decoration: none;
            font-size: .7rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            transition: all .3s;
            text-align: center
        }

        .ab i {
            font-size: 1rem
        }

        .ab:hover {
            color: var(--gold);
            border-color: var(--gold-dim);
            background: rgba(201, 169, 110, .05)
        }

        .empty-msg {
            padding: 28px 20px;
            text-align: center;
            color: var(--muted);
            font-size: .82rem
        }

        .empty-msg small {
            display: block;
            color: #2a2a2a;
            margin-top: 6px;
            font-size: .72rem
        }

        @media(max-width:1200px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr)
            }

            .grid-2,
            .grid-b {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <p class="eyebrow">Panel Admin</p>
            <h1>LUXE MOTORS <em>MX</em></h1>
        </div>
        <nav class="nav-section">
            <p class="nav-label">Principal</p>
            <a href="dashboard.php" class="nav-item active"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="clientes.php" class="nav-item"><i class="fas fa-users"></i> Clientes</a>
            <a href="#" class="nav-item"><i class="fas fa-tag"></i> Tasaciones
                <?php if ($totalTasaciones > 0): ?><span
                        style="margin-left:auto;background:var(--warning);color:#000;font-size:.58rem;padding:2px 6px;border-radius:10px">
                        <?= $totalTasaciones ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="#" class="nav-item"><i class="fas fa-file-invoice-dollar"></i> Cotizaciones</a>
        </nav>
        <nav class="nav-section" style="margin-top:16px">
            <p class="nav-label">Sitio Público</p>
            <a href="../config.php" class="nav-item" target="_blank"><i class="fas fa-sliders-h"></i> Configurador</a>
            <a href="../compraventa.php" class="nav-item" target="_blank"><i class="fas fa-exchange-alt"></i>
                Compra-Venta</a>
            <a href="../index.php" class="nav-item" target="_blank"><i class="fas fa-external-link-alt"></i> Ver Sitio
                Web</a>
        </nav>
        <div class="sidebar-footer">
            <div class="admin-badge">
                <div class="admin-avatar"><i class="fas fa-user fa-xs"></i></div>
                <div class="admin-info">
                    <div class="name">Administrador</div>
                    <div class="role">Super Admin</div>
                </div>
            </div>
            <a href="logout.php" class="nav-item danger" style="margin-top:16px"><i class="fas fa-sign-out-alt"></i>
                Cerrar Sesión</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="page-title">
                <p class="eyebrow">Resumen general</p>
                <h2>Dashboard</h2>
            </div>
            <div class="topbar-meta">
                <strong>
                    <?= date('l, d \d\e F \d\e Y') ?>
                </strong><br>
                <?= date('H:i') ?> hrs · CDMX
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-users"></i></div>
                <div class="kpi-value">
                    <?= $totalClientes ?>
                </div>
                <div class="kpi-label">Clientes registrados</div>
                <span class="kpi-alert badge-ok">activo</span>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-car"></i></div>
                <div class="kpi-value">
                    <?= $totalAutos ?>
                </div>
                <div class="kpi-label">Modelos en catálogo</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="kpi-value">
                    <?= $totalVentas ?>
                </div>
                <div class="kpi-label">Cotizaciones generadas</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-tag"></i></div>
                <div class="kpi-value">
                    <?= $totalTasaciones ?>
                </div>
                <div class="kpi-label">Tasaciones pendientes</div>
                <?php if ($totalTasaciones > 0): ?><span class="kpi-alert badge-pending">revisar</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="panel">
                <div class="panel-header"><span class="panel-title">Últimas cotizaciones (ventas)</span></div>
                <?php if (!empty($ultimasVentas)): ?>
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Modelo</th>
                                <th>Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasVentas as $v): ?>
                                <tr>
                                    <td class="tm" style="font-size:.73rem">
                                        <?= htmlspecialchars($v['no_factura']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($v['modelo'] ?? 'N/A') ?>
                                    </td>
                                    <td class="tg">$
                                        <?= number_format($v['total_a_pagar'], 0, '.', ',') ?>
                                    </td>
                                    <td><span
                                            class="badge <?= $v['status'] === 'confirmada' ? 'badge-ok' : ($v['status'] === 'cancelada' ? 'badge-red' : 'badge-pending') ?>">
                                            <?= $v['status'] ?>
                                        </span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-msg">Sin cotizaciones aún.<small>Ejecuta schema_mejoras.sql para activar.</small>
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Solicitudes de valuación</span>
                    <?php if ($totalTasaciones > 0): ?><span class="badge badge-pending">
                            <?= $totalTasaciones ?> pendientes
                        </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($ultimasTasaciones)): ?>
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Vehículo</th>
                                <th>Oferta</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasTasaciones as $t): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($t['nombre_vendedor']) ?>
                                    </td>
                                    <td class="tm" style="font-size:.78rem">
                                        <?= htmlspecialchars($t['marca'] . ' ' . $t['modelo'] . ' ' . $t['anio']) ?>
                                    </td>
                                    <td class="tg">$
                                        <?= number_format($t['valor_oferta'], 0, '.', ',') ?>
                                    </td>
                                    <td><span
                                            class="badge <?= $t['status'] === 'aceptada' ? 'badge-ok' : ($t['status'] === 'rechazada' ? 'badge-red' : 'badge-pending') ?>">
                                            <?= $t['status'] ?>
                                        </span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-msg">Sin solicitudes de compra-venta.<small>Ejecuta schema_mejoras.sql para
                            activar.</small></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-b">
            <div class="panel">
                <div class="panel-header"><span class="panel-title">Últimos clientes</span><a href="clientes.php"
                        class="panel-action">Ver todos →</a></div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosClientes as $c): ?>
                            <tr>
                                <td><span class="av">
                                        <?= strtoupper(substr($c['nombre'], 0, 1) . substr($c['apellido'], 0, 1)) ?>
                                    </span>
                                    <?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?>
                                </td>
                                <td class="tm" style="font-size:.78rem">
                                    <?= date('d/m/Y', strtotime($c['fecha_registro'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="display:flex;flex-direction:column;gap:20px">
                <div class="panel">
                    <div class="panel-header"><span class="panel-title">Autos por categoría</span></div>
                    <?php foreach ($autosPorCat as $cat): ?>
                        <div class="cat-item">
                            <span><span class="cat-dot"></span>
                                <?= htmlspecialchars($cat['categoria']) ?>
                            </span>
                            <span class="cat-n">
                                <?= $cat['total'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="panel">
                    <div class="panel-header"><span class="panel-title">Acciones rápidas</span></div>
                    <div class="ag">
                        <a href="clientes.php" class="ab"><i class="fas fa-user-plus"></i>Nuevo cliente</a>
                        <a href="../config.php" class="ab" target="_blank"><i
                                class="fas fa-sliders-h"></i>Configurar</a>
                        <a href="../compraventa.php" class="ab" target="_blank"><i
                                class="fas fa-tag"></i>Compra-Venta</a>
                        <a href="../index.php" class="ab" target="_blank"><i class="fas fa-globe"></i>Ver sitio</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>