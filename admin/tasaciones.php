<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
include '../config/db.php';

$mensaje = '';

/* ── CAMBIAR ESTADO ── */
if (isset($_GET['status']) && isset($_GET['id'])) {
    $allowed = ['pendiente', 'revisando', 'aceptada', 'rechazada'];
    $newStatus = $_GET['status'];
    $id = (int) $_GET['id'];
    if (in_array($newStatus, $allowed)) {
        try {
            $pdo->prepare("UPDATE tasaciones SET status=? WHERE id=?")->execute([$newStatus, $id]);
            $mensaje = 'Estado actualizado correctamente.';
        } catch (Exception $e) {
            $mensaje = 'Error: tabla tasaciones no existe. Ejecuta schema_mejoras.sql primero.';
        }
    }
    header('Location: tasaciones.php' . ($mensaje ? '?ok=1' : ''));
    exit;
}

if (isset($_GET['ok']))
    $mensaje = 'Estado actualizado correctamente.';

/* ── ELIMINAR ── */
if (isset($_GET['eliminar'])) {
    try {
        $pdo->prepare("DELETE FROM tasaciones WHERE id=?")->execute([(int) $_GET['eliminar']]);
    } catch (Exception $e) {
    }
    header('Location: tasaciones.php');
    exit;
}

/* ── LEER DATOS ── */
$tasaciones = [];
$totales = ['pendiente' => 0, 'revisando' => 0, 'aceptada' => 0, 'rechazada' => 0, 'total' => 0];
$tablaOk = false;

try {
    $filtro = $_GET['filtro'] ?? 'todos';
    $whereOk = true;
    if ($filtro !== 'todos' && in_array($filtro, ['pendiente', 'revisando', 'aceptada', 'rechazada'])) {
        $stmt = $pdo->prepare("SELECT * FROM tasaciones WHERE status=? ORDER BY fecha_solicitud DESC");
        $stmt->execute([$filtro]);
    } else {
        $stmt = $pdo->query("SELECT * FROM tasaciones ORDER BY fecha_solicitud DESC");
    }
    $tasaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counts = $pdo->query("SELECT status, COUNT(*) as n FROM tasaciones GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($counts as $c) {
        $totales[$c['status']] = (int) $c['n'];
        $totales['total'] += (int) $c['n'];
    }
    $tablaOk = true;
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tasaciones — LUXE MOTORS MX</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--bg:#070707;--surface:#0f0f0f;--s2:#141414;--border:#1c1c1c;--gold:#C9A96E;--gold-dim:#7a6540;--text:#EBEBEB;--muted:#555;--success:#4caf7d;--warning:#e0a535;--error:#e05252;--radius:6px}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;font-weight:300;display:flex;min-height:100vh}

.sidebar{width:260px;min-height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:40px 0;position:fixed;top:0;left:0;bottom:0;z-index:100}
.sidebar-logo{padding:0 32px 40px;border-bottom:1px solid var(--border);margin-bottom:32px}
.sl-ey{font-size:9px;letter-spacing:.4em;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.sidebar-logo h1{font-family:'Cormorant Garamond',serif;font-weight:300;font-size:1.5rem}
.sidebar-logo h1 em{font-style:normal;color:var(--gold)}
.nav-section{padding:0 20px;margin-bottom:8px}
.nav-label{font-size:9px;letter-spacing:.35em;text-transform:uppercase;color:var(--muted);padding:0 12px;margin-bottom:8px}
.nav-item{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:var(--radius);color:#666;text-decoration:none;font-size:.875rem;transition:all .25s;margin-bottom:2px;position:relative}
.nav-item i{width:18px;text-align:center;font-size:.8rem}
.nav-item:hover{color:var(--text);background:var(--s2)}
.nav-item.active{color:var(--gold);background:rgba(201,169,110,.07)}
.nav-item.active::before{content:'';position:absolute;left:0;top:8px;bottom:8px;width:2px;background:var(--gold);border-radius:0 2px 2px 0}
.nav-item.danger{color:#7a3535}
.nav-item.danger:hover{color:var(--error);background:rgba(224,82,82,.06)}
.sidebar-footer{margin-top:auto;padding:24px 32px 0;border-top:1px solid var(--border)}
.admin-badge{display:flex;align-items:center;gap:10px}
.admin-avatar{width:36px;height:36px;border-radius:50%;background:rgba(201,169,110,.15);border:1px solid var(--gold-dim);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:.75rem}
.admin-info .name{font-size:.85rem;font-weight:500;color:var(--text)}
.admin-info .role{font-size:.7rem;color:var(--muted);letter-spacing:.1em;text-transform:uppercase}

.main{margin-left:260px;flex:1;padding:48px 56px;min-width:0}
.topbar{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:40px}
.page-title .ey{font-size:10px;letter-spacing:.4em;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.page-title h2{font-family:'Cormorant Garamond',serif;font-weight:300;font-size:2.8rem;line-height:1}

/* KPI row */
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:36px}
.kpi-mini{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;text-align:center;transition:border-color .3s;cursor:pointer;text-decoration:none;display:block}
.kpi-mini:hover{border-color:var(--gold-dim)}
.kpi-mini.active-filter{border-color:var(--gold);background:rgba(201,169,110,.04)}
.kpi-mini .km-val{font-family:'Cormorant Garamond',serif;font-size:2.2rem;font-weight:600;line-height:1;margin-bottom:4px}
.kpi-mini .km-label{font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted)}
.km-total .km-val{color:var(--text)}
.km-pendiente .km-val{color:var(--warning)}
.km-aceptada .km-val{color:var(--success)}
.km-rechazada .km-val{color:var(--error)}

/* Notif */
.notif{display:flex;align-items:center;gap:10px;padding:14px 20px;border-radius:var(--radius);margin-bottom:28px;font-size:.875rem;background:rgba(76,175,125,.08);border:1px solid rgba(76,175,125,.3);color:var(--success)}

/* Tabla */
.panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.p-head{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;border-bottom:1px solid var(--border)}
.p-title{font-size:10px;letter-spacing:.25em;text-transform:uppercase;color:var(--muted)}

/* Filter tabs */
.filter-tabs{display:flex;gap:6px;padding:14px 20px;border-bottom:1px solid var(--border);flex-wrap:wrap}
.ftab{padding:6px 14px;background:transparent;border:1px solid var(--border);border-radius:20px;color:var(--muted);font-family:'DM Sans',sans-serif;font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;text-decoration:none;transition:all .25s}
.ftab:hover{border-color:#333;color:var(--text)}
.ftab.active{border-color:var(--gold-dim);color:var(--gold);background:rgba(201,169,110,.05)}

.data-table{width:100%;border-collapse:collapse}
.data-table th{padding:11px 18px;text-align:left;font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);font-weight:400;border-bottom:1px solid var(--border)}
.data-table td{padding:14px 18px;font-size:.84rem;border-bottom:1px solid rgba(28,28,28,.6);vertical-align:top}
.data-table tr:last-child td{border-bottom:none}
.data-table tbody tr:hover td{background:var(--s2)}

.vehicle-name{font-weight:400;color:var(--text);margin-bottom:2px}
.vehicle-sub{font-size:.75rem;color:var(--muted)}
.seller-name{font-weight:400;color:var(--text);margin-bottom:2px}
.seller-contact{font-size:.75rem;color:var(--muted)}
.offer-val{font-family:'Cormorant Garamond',serif;font-size:1.3rem;color:var(--gold)}

/* Status badge */
.sb{display:inline-flex;padding:4px 10px;border-radius:12px;font-size:.65rem;letter-spacing:.08em;text-transform:uppercase}
.sb-pendiente{background:rgba(224,165,53,.1);color:var(--warning);border:1px solid rgba(224,165,53,.3)}
.sb-revisando{background:rgba(126,200,227,.1);color:#7ec8e3;border:1px solid rgba(126,200,227,.3)}
.sb-aceptada{background:rgba(76,175,125,.1);color:var(--success);border:1px solid rgba(76,175,125,.3)}
.sb-rechazada{background:rgba(224,82,82,.1);color:var(--error);border:1px solid rgba(224,82,82,.3)}

/* Status changer */
.status-select{background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:6px 10px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:.78rem;outline:none;cursor:pointer;transition:border-color .3s;appearance:none;padding-right:28px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23555' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center}
.status-select:focus{border-color:var(--gold-dim)}

.ac-cell{display:flex;gap:5px}
.btn-ic{width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;border-radius:var(--radius);border:1px solid var(--border);background:transparent;color:var(--muted);font-size:.72rem;text-decoration:none;cursor:pointer;transition:all .25s}
.btn-ic:hover{color:var(--error);border-color:rgba(224,82,82,.4);background:rgba(224,82,82,.05)}

.empty-msg{text-align:center;padding:56px 24px;color:var(--muted)}
.empty-msg i{font-size:1.8rem;margin-bottom:14px;display:block;color:#222}

.no-tabla{background:rgba(224,165,53,.06);border:1px solid rgba(224,165,53,.25);border-radius:var(--radius);padding:20px 24px;color:var(--warning);font-size:.88rem}
.no-tabla code{background:var(--bg);padding:2px 8px;border-radius:3px;font-size:.8rem;color:var(--gold)}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <p class="sl-ey">Panel Admin</p>
        <h1>LUXE MOTORS <em>MX</em></h1>
    </div>
    <nav class="nav-section">
        <p class="nav-label">Principal</p>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="clientes.php"  class="nav-item"><i class="fas fa-users"></i> Clientes</a>
        <a href="tasaciones.php"class="nav-item active"><i class="fas fa-tag"></i> Tasaciones</a>
        <a href="ventas.php"    class="nav-item"><i class="fas fa-file-invoice-dollar"></i> Cotizaciones</a>
    </nav>
    <nav class="nav-section" style="margin-top:16px">
        <p class="nav-label">Sitio Público</p>
        <a href="../config.php"       class="nav-item" target="_blank"><i class="fas fa-sliders-h"></i> Configurador</a>
        <a href="../compraventa.php"  class="nav-item" target="_blank"><i class="fas fa-exchange-alt"></i> Compra-Venta</a>
        <a href="../index.php"        class="nav-item" target="_blank"><i class="fas fa-external-link-alt"></i> Ver Sitio</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-badge">
            <div class="admin-avatar"><i class="fas fa-user fa-xs"></i></div>
            <div class="admin-info"><div class="name">Administrador</div><div class="role">Super Admin</div></div>
        </div>
        <a href="logout.php" class="nav-item danger" style="margin-top:16px"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div class="page-title">
            <p class="ey">Compra-Venta</p>
            <h2>Tasaciones</h2>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="notif"><i class="fas fa-check-circle fa-sm"></i><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if (!$tablaOk): ?>
        <div class="no-tabla">
            <i class="fas fa-exclamation-triangle fa-sm" style="margin-right:8px"></i>
            La tabla <code>tasaciones</code> no existe aún. Ejecuta <code>mejoras.sql</code> en phpMyAdmin para activar esta sección.
        </div>
    <?php else: ?>

        <!-- KPIs -->
        <div class="kpi-row">
            <?php $f = $_GET['filtro'] ?? 'todos'; ?>
            <a href="tasaciones.php?filtro=todos" class="kpi-mini km-total <?= $f === 'todos' ? 'active-filter' : '' ?>">
                <div class="km-val"><?= $totales['total'] ?></div>
                <div class="km-label">Total</div>
            </a>
            <a href="tasaciones.php?filtro=pendiente" class="kpi-mini km-pendiente <?= $f === 'pendiente' ? 'active-filter' : '' ?>">
                <div class="km-val"><?= $totales['pendiente'] ?></div>
                <div class="km-label">Pendientes</div>
            </a>
            <a href="tasaciones.php?filtro=aceptada" class="kpi-mini km-aceptada <?= $f === 'aceptada' ? 'active-filter' : '' ?>">
                <div class="km-val"><?= $totales['aceptada'] ?></div>
                <div class="km-label">Aceptadas</div>
            </a>
            <a href="tasaciones.php?filtro=rechazada" class="kpi-mini km-rechazada <?= $f === 'rechazada' ? 'active-filter' : '' ?>">
                <div class="km-val"><?= $totales['rechazada'] ?></div>
                <div class="km-label">Rechazadas</div>
            </a>
        </div>

        <div class="panel">
            <div class="p-head">
                <span class="p-title">Solicitudes de valuación</span>
                <span style="font-size:.78rem;color:var(--muted)"><?= count($tasaciones) ?> resultado<?= count($tasaciones) != 1 ? 's' : '' ?></span>
            </div>

            <div class="filter-tabs">
                <?php foreach (['todos' => 'Todos', 'pendiente' => 'Pendientes', 'revisando' => 'En revisión', 'aceptada' => 'Aceptadas', 'rechazada' => 'Rechazadas'] as $val => $lbl): ?>
                    <a href="tasaciones.php?filtro=<?= $val ?>" class="ftab <?= $f === $val ? 'active' : '' ?>"><?= $lbl ?></a>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($tasaciones)): ?>
                <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vehículo</th>
                            <th>Vendedor</th>
                            <th>Km / Condición</th>
                            <th>Oferta calculada</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tasaciones as $t): ?>
                        <tr>
                            <td>
                                <div class="vehicle-name"><?= htmlspecialchars($t['marca'] . ' ' . $t['modelo']) ?></div>
                                <div class="vehicle-sub">
                                    <?= $t['anio'] ?> ·
                                    <?= $t['num_duenos'] ?> dueño<?= $t['num_duenos'] > 1 ? 's' : '' ?> ·
                                    <?= $t['tiene_factura'] ? '<span style="color:var(--success)">Con factura</span>' : '<span style="color:var(--muted)">Sin factura</span>' ?>
                                </div>
                                <?php if (!empty($t['color'])): ?>
                                    <div class="vehicle-sub" style="margin-top:2px">Color: <?= htmlspecialchars($t['color']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="seller-name"><?= htmlspecialchars($t['nombre_vendedor']) ?></div>
                                <div class="seller-contact"><?= htmlspecialchars($t['email']) ?></div>
                                <?php if (!empty($t['telefono'])): ?>
                                    <div class="seller-contact"><?= htmlspecialchars($t['telefono']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size:.84rem"><?= number_format($t['kilometraje'], 0, '.', ',') ?> km</div>
                                <div class="vehicle-sub" style="margin-top:3px">
                                    <?php $condColors = ['excelente' => 'var(--success)', 'bueno' => '#7ec8e3', 'regular' => 'var(--warning)', 'deficiente' => 'var(--error)']; ?>
                                    <span style="color:<?= $condColors[$t['condicion']] ?? 'var(--muted)' ?>">
                                        <?= ucfirst($t['condicion']) ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="offer-val">$<?= number_format($t['valor_oferta'], 0, '.', ',') ?></div>
                            </td>
                            <td style="font-size:.78rem;color:var(--muted);white-space:nowrap">
                                <?= date('d/m/Y', strtotime($t['fecha_solicitud'])) ?><br>
                                <?= date('H:i', strtotime($t['fecha_solicitud'])) ?> hrs
                            </td>
                            <td>
                                <form method="GET" action="tasaciones.php" style="display:inline">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <?php foreach (['pendiente' => 'Pendiente', 'revisando' => 'Revisando', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada'] as $sv => $sl): ?>
                                            <option value="<?= $sv ?>" <?= $t['status'] === $sv ? 'selected' : '' ?>><?= $sl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div class="ac-cell">
                                    <a href="tasaciones.php?eliminar=<?= $t['id'] ?>"
                                       onclick="return confirm('¿Eliminar esta solicitud?')"
                                       class="btn-ic" title="Eliminar">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div class="empty-msg">
                    <i class="fas fa-tag"></i>
                    <p>No hay solicitudes<?= $f !== 'todos' ? ' con el filtro "' . $f . '"' : '' ?> aún.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>