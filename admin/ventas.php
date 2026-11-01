<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
include '../config/db.php';

/* ── CAMBIAR STATUS ── */
if (isset($_POST['cambiar_status'])) {
    try {
        $pdo->prepare("UPDATE ventas SET status=? WHERE id=?")->execute([$_POST['status'], (int) $_POST['id']]);
    } catch (Exception $e) {
    }
    header('Location: ventas.php');
    exit;
}

/* ── LEER DATOS ── */
$ventas = [];
$totales = ['total' => 0, 'pendiente' => 0, 'confirmada' => 0, 'cancelada' => 0, 'monto' => 0];
$tablaOk = false;
$filtro = $_GET['filtro'] ?? 'todos';

try {
    $joinBase = "
        SELECT v.*,
               a.modelo, a.categoria,
               TRIM(CONCAT(COALESCE(c.nombre,''),' ',COALESCE(c.apellido,''))) AS cli_nombre,
               c.email    AS cli_email,
               c.telefono AS cli_telefono
        FROM ventas v
        LEFT JOIN autos    a ON a.id = v.auto_id
        LEFT JOIN clientes c ON c.id = v.cliente_id
    ";
    if ($filtro !== 'todos' && in_array($filtro, ['pendiente', 'confirmada', 'cancelada'])) {
        $stmt = $pdo->prepare($joinBase . " WHERE v.status=? ORDER BY v.fecha_venta DESC");
        $stmt->execute([$filtro]);
    } else {
        $stmt = $pdo->query($joinBase . " ORDER BY v.fecha_venta DESC");
    }
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $c2 = $pdo->query("SELECT status, COUNT(*) as n, SUM(total_a_pagar) as monto FROM ventas GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($c2 as $c) {
        $totales[$c['status']] = (int) $c['n'];
        $totales['total'] += (int) $c['n'];
        $totales['monto'] += (float) $c['monto'];
    }
    $tablaOk = true;
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cotizaciones — LUXE MOTORS MX</title>
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
.admin-info .name{font-size:.85rem;font-weight:500}
.admin-info .role{font-size:.7rem;color:var(--muted);letter-spacing:.1em;text-transform:uppercase}

.main{margin-left:260px;flex:1;padding:48px 56px;min-width:0}
.topbar{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:40px}
.page-title .ey{font-size:10px;letter-spacing:.4em;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.page-title h2{font-family:'Cormorant Garamond',serif;font-weight:300;font-size:2.8rem;line-height:1}

/* KPI */
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:36px}
.kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:22px;text-align:center;cursor:pointer;text-decoration:none;display:block;transition:border-color .3s}
.kpi-card:hover{border-color:var(--gold-dim)}
.kpi-card.active-filter{border-color:var(--gold);background:rgba(201,169,110,.04)}
.kv{font-family:'Cormorant Garamond',serif;font-size:2rem;font-weight:600;line-height:1;margin-bottom:4px}
.kl{font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted)}
.kv-default{color:var(--text)}
.kv-monto{color:var(--gold)}
.kv-conf{color:var(--success)}
.kv-cancel{color:var(--error)}

/* Panel */
.panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.p-head{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;border-bottom:1px solid var(--border)}
.p-title{font-size:10px;letter-spacing:.25em;text-transform:uppercase;color:var(--muted)}

.filter-tabs{display:flex;gap:6px;padding:14px 20px;border-bottom:1px solid var(--border);flex-wrap:wrap}
.ftab{padding:6px 14px;background:transparent;border:1px solid var(--border);border-radius:20px;color:var(--muted);font-family:'DM Sans',sans-serif;font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;text-decoration:none;transition:all .25s}
.ftab:hover{border-color:#333;color:var(--text)}
.ftab.active{border-color:var(--gold-dim);color:var(--gold);background:rgba(201,169,110,.05)}

/* Search */
.search-wrap{padding:14px 20px;border-bottom:1px solid var(--border)}
.search-inner{position:relative}
.search-inner i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.72rem}
.search-inner input{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:9px 13px 9px 36px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:.84rem;outline:none;transition:border-color .3s}
.search-inner input:focus{border-color:var(--gold-dim)}
.search-inner input::placeholder{color:var(--muted)}

.data-table{width:100%;border-collapse:collapse}
.data-table th{padding:11px 18px;text-align:left;font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);font-weight:400;border-bottom:1px solid var(--border)}
.data-table td{padding:13px 18px;font-size:.84rem;border-bottom:1px solid rgba(28,28,28,.6);vertical-align:top}
.data-table tr:last-child td{border-bottom:none}
.data-table tbody tr:hover td{background:var(--s2)}

.folio{font-size:.72rem;color:var(--muted);font-family:monospace;display:block;margin-bottom:3px}
.model-name{font-weight:400;color:var(--text)}
.cat-pill{display:inline-block;font-size:.65rem;padding:2px 8px;border-radius:3px;background:rgba(201,169,110,.07);color:var(--gold-dim);border:1px solid rgba(201,169,110,.15);margin-top:3px}

.price-main{font-family:'Cormorant Garamond',serif;font-size:1.2rem;color:var(--gold)}
.price-sub{font-size:.72rem;color:var(--muted);margin-top:2px}

.fin-detail{font-size:.78rem;color:var(--muted);line-height:1.6}

.sb{display:inline-flex;padding:4px 10px;border-radius:12px;font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;white-space:nowrap}
.sb-pendiente{background:rgba(224,165,53,.1);color:var(--warning);border:1px solid rgba(224,165,53,.3)}
.sb-confirmada{background:rgba(76,175,125,.1);color:var(--success);border:1px solid rgba(76,175,125,.3)}
.sb-cancelada{background:rgba(224,82,82,.1);color:var(--error);border:1px solid rgba(224,82,82,.3)}

.status-form select{background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:5px 26px 5px 9px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:.78rem;outline:none;cursor:pointer;transition:border-color .3s;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23555' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 7px center}
.status-form select:focus{border-color:var(--gold-dim)}

.btn-factura{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:transparent;border:1px solid var(--border);border-radius:var(--radius);color:var(--muted);font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;text-decoration:none;transition:all .25s;white-space:nowrap}
.btn-factura:hover{color:var(--gold);border-color:var(--gold-dim)}

/* ── celda cliente ── */
.cli-cell .cli-name{font-weight:400;color:var(--text);font-size:.84rem}
.cli-cell .cli-sub{font-size:.73rem;color:var(--muted);margin-top:2px}
.cli-cell .no-cli{font-size:.78rem;color:#2a2a2a;font-style:italic}

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
        <a href="tasaciones.php"class="nav-item"><i class="fas fa-tag"></i> Tasaciones</a>
        <a href="ventas.php"    class="nav-item active"><i class="fas fa-file-invoice-dollar"></i> Cotizaciones</a>
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
            <p class="ey">Finanzas</p>
            <h2>Cotizaciones</h2>
        </div>
    </div>

    <?php if (!$tablaOk): ?>
        <div class="no-tabla">
            <i class="fas fa-exclamation-triangle fa-sm" style="margin-right:8px"></i>
            La tabla <code>ventas</code> no existe aún. Ejecuta <code>schema_mejoras.sql</code> en phpMyAdmin para activar esta sección.
        </div>
    <?php else: ?>

        <!-- KPIs -->
        <div class="kpi-row">
            <?php $f = $_GET['filtro'] ?? 'todos'; ?>
            <a href="ventas.php?filtro=todos" class="kpi-card <?= $f === 'todos' ? 'active-filter' : '' ?>">
                <div class="kv kv-default"><?= $totales['total'] ?></div>
                <div class="kl">Total generadas</div>
            </a>
            <a href="ventas.php?filtro=confirmada" class="kpi-card <?= $f === 'confirmada' ? 'active-filter' : '' ?>">
                <div class="kv kv-conf"><?= $totales['confirmada'] ?></div>
                <div class="kl">Confirmadas</div>
            </a>
            <a href="ventas.php?filtro=pendiente" class="kpi-card <?= $f === 'pendiente' ? 'active-filter' : '' ?>">
                <div class="kv" style="color:var(--warning)"><?= $totales['pendiente'] ?></div>
                <div class="kl">Pendientes</div>
            </a>
            <div class="kpi-card" style="cursor:default">
                <div class="kv kv-monto">$<?= number_format($totales['monto'] / 1000000, 1) ?>M</div>
                <div class="kl">Monto total MXN</div>
            </div>
        </div>

        <div class="panel">
            <div class="p-head">
                <span class="p-title">Registro de cotizaciones</span>
                <span style="font-size:.78rem;color:var(--muted)"><?= count($ventas) ?> registro<?= count($ventas) != 1 ? 's' : '' ?></span>
            </div>

            <div class="filter-tabs">
                <?php foreach (['todos' => 'Todos', 'pendiente' => 'Pendientes', 'confirmada' => 'Confirmadas', 'cancelada' => 'Canceladas'] as $val => $lbl): ?>
                    <a href="ventas.php?filtro=<?= $val ?>" class="ftab <?= $f === $val ? 'active' : '' ?>"><?= $lbl ?></a>
                <?php endforeach; ?>
            </div>

            <div class="search-wrap">
                <div class="search-inner">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchVentas" placeholder="Buscar por folio, modelo, cliente…" oninput="filtrarVentas()">
                </div>
            </div>

            <?php if (!empty($ventas)): ?>
                <div style="overflow-x:auto">
                <table class="data-table" id="ventasTable">
                    <thead>
                        <tr>
                            <th>Vehículo</th>
                            <th>Cliente</th>
                            <th>Precio configurado</th>
                            <th>Financiamiento</th>
                            <th>Total a pagar</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td>
                                <span class="folio"><?= htmlspecialchars($v['no_factura']) ?></span>
                                <span class="model-name"><?= htmlspecialchars($v['modelo'] ?? 'N/A') ?></span>
                                <?php if (!empty($v['categoria'])): ?>
                                    <span class="cat-pill"><?= htmlspecialchars($v['categoria']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($v['color_elegido']) && $v['color_elegido'] !== 'Original'): ?>
                                    <div style="font-size:.72rem;color:var(--muted);margin-top:3px">Color: <?= htmlspecialchars($v['color_elegido']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $cn = trim($v['cli_nombre'] ?? ''); ?>
                                <?php if ($cn): ?>
                                    <div class="cli-cell">
                                        <div class="cli-name"><?= htmlspecialchars($cn) ?></div>
                                        <?php if (!empty($v['cli_email'])): ?>
                                            <div class="cli-sub"><?= htmlspecialchars($v['cli_email']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($v['cli_telefono'])): ?>
                                            <div class="cli-sub"><?= htmlspecialchars($v['cli_telefono']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="cli-cell"><span class="no-cli">Sin cliente</span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="price-main">$<?= number_format($v['precio_total'], 0, '.', ',') ?></div>
                                <div class="price-sub">Enganche: $<?= number_format($v['enganche'], 0, '.', ',') ?></div>
                            </td>
                            <td>
                                <div class="fin-detail">
                                    Financiado: $<?= number_format($v['monto_financiado'], 0, '.', ',') ?><br>
                                    <?= $v['plazo_meses'] ?> meses · <?= $v['tasa_mensual'] ?>% mensual<br>
                                    Cuota: <strong style="color:var(--text)">$<?= number_format($v['pago_mensual'], 0, '.', ',') ?>/mes</strong>
                                </div>
                            </td>
                            <td>
                                <div class="price-main" style="font-size:1.1rem">$<?= number_format($v['total_a_pagar'], 0, '.', ',') ?></div>
                                <div class="price-sub">Intereses: $<?= number_format($v['total_intereses'], 0, '.', ',') ?></div>
                            </td>
                            <td style="font-size:.78rem;color:var(--muted);white-space:nowrap">
                                <?= date('d/m/Y', strtotime($v['fecha_venta'])) ?><br>
                                <?= date('H:i', strtotime($v['fecha_venta'])) ?> hrs
                            </td>
                            <td>
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                    <input type="hidden" name="cambiar_status" value="1">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="pendiente"  <?= $v['status'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                        <option value="confirmada" <?= $v['status'] === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                                        <option value="cancelada"  <?= $v['status'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <?php
                                $cli_n = urlencode(trim($v['cli_nombre'] ?? ''));
                                $cli_e = urlencode($v['cli_email'] ?? '');
                                $cli_t = urlencode($v['cli_telefono'] ?? '');
                                ?>
                                <a href="../factura.php?precio=<?= $v['precio_total'] ?>&auto=<?= $v['auto_id'] ?>&enganche=<?= $v['enganche'] ?>&financiado=<?= $v['monto_financiado'] ?>&tasa=<?= $v['tasa_mensual'] ?>&plazo=<?= $v['plazo_meses'] ?>&mensual=<?= $v['pago_mensual'] ?>&intereses=<?= $v['total_intereses'] ?>&total=<?= $v['total_a_pagar'] ?>&color=<?= urlencode($v['color_elegido']) ?>&vid=<?= $v['id'] ?>&cli_nombre=<?= $cli_n ?>&cli_email=<?= $cli_e ?>&cli_telefono=<?= $cli_t ?>"
                                   target="_blank" class="btn-factura">
                                    <i class="fas fa-file-invoice fa-xs"></i> Ver factura
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div class="empty-msg">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <p>Sin cotizaciones<?= $f !== 'todos' ? ' con el filtro "' . $f . '"' : '' ?> aún.</p>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</main>

<script>
function filtrarVentas() {
    const q = document.getElementById('searchVentas').value.toLowerCase();
    document.querySelectorAll('#ventasTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>