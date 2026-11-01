<?php
include 'config/db.php';

/* ══════════════════════════════════════════════════════════════
   PARÁMETROS FINANCIEROS (siempre vienen como GET)
══════════════════════════════════════════════════════════════ */
$precio_total = isset($_GET['precio']) ? floatval($_GET['precio']) : 0;
$auto_id = isset($_GET['auto']) ? intval($_GET['auto']) : 0;
$enganche = isset($_GET['enganche']) ? floatval($_GET['enganche']) : 0;
$monto_financiado = isset($_GET['financiado']) ? floatval($_GET['financiado']) : 0;
$tasa_mensual = isset($_GET['tasa']) ? floatval($_GET['tasa']) : 1.5;
$plazo_meses = isset($_GET['plazo']) ? intval($_GET['plazo']) : 24;
$pago_mensual = isset($_GET['mensual']) ? floatval($_GET['mensual']) : 0;
$total_intereses = isset($_GET['intereses']) ? floatval($_GET['intereses']) : 0;
$total_pagar = isset($_GET['total']) ? floatval($_GET['total']) : 0;
$color_elegido = isset($_GET['color']) ? trim($_GET['color']) : 'Original';

// Query string financiero — se preserva en el redirect POST→GET
$qs_fin = http_build_query([
    'precio' => $precio_total,
    'auto' => $auto_id,
    'enganche' => $enganche,
    'financiado' => $monto_financiado,
    'tasa' => $tasa_mensual,
    'plazo' => $plazo_meses,
    'mensual' => $pago_mensual,
    'intereses' => $total_intereses,
    'total' => $total_pagar,
    'color' => $color_elegido,
]);

// Lista de clientes para el selector
$clientes_lista = [];
try {
    $clientes_lista = $pdo->query(
        "SELECT id, nombre, apellido, email, telefono FROM clientes ORDER BY nombre ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

/* ══════════════════════════════════════════════════════════════
   MODO A — POST: procesar formulario del cliente
   1. Identifica o crea el cliente en BD
   2. Inserta la venta con cliente_id
   3. Redirige a GET con vid= para mostrar la factura
══════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_cliente'])) {

    $tipo = $_POST['cli_tipo'] ?? 'nuevo';
    $cliente_id = null;
    $cli_nombre = '';
    $cli_email = '';
    $cli_telefono = '';

    if ($tipo === 'existente') {
        $cid = intval($_POST['cli_id_existente'] ?? 0);
        if ($cid > 0) {
            $stmt = $pdo->prepare("SELECT id, nombre, apellido, email, telefono FROM clientes WHERE id = ?");
            $stmt->execute([$cid]);
            $c = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($c) {
                $cliente_id = $c['id'];
                $cli_nombre = trim($c['nombre'] . ' ' . $c['apellido']);
                $cli_email = $c['email'] ?? '';
                $cli_telefono = $c['telefono'] ?? '';
            }
        }
    } else {
        $cli_nombre = trim($_POST['cli_nombre'] ?? '');
        $cli_email = trim($_POST['cli_email'] ?? '');
        $cli_telefono = trim($_POST['cli_telefono'] ?? '');
        $guardar = isset($_POST['cli_guardar']);

        if ($guardar && $cli_nombre) {
            $partes = explode(' ', $cli_nombre, 2);
            $nom = $partes[0];
            $ape = $partes[1] ?? '';

            // Evitar duplicado por email
            $existe = null;
            if ($cli_email) {
                $chk = $pdo->prepare("SELECT id FROM clientes WHERE email = ? LIMIT 1");
                $chk->execute([$cli_email]);
                $existe = $chk->fetchColumn();
            }

            if ($existe) {
                $cliente_id = (int) $existe;
            } else {
                $ins = $pdo->prepare(
                    "INSERT INTO clientes (nombre, apellido, email, telefono) VALUES (?,?,?,?)"
                );
                $ins->execute([$nom, $ape, $cli_email, $cli_telefono]);
                $cliente_id = (int) $pdo->lastInsertId();
            }
        }
    }

    // Insertar venta
    $venta_id = 0;
    $no_factura = 'LUX-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));

    if ($precio_total > 0 && $auto_id > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ventas
                    (auto_id, cliente_id, precio_total, color_elegido, enganche,
                     monto_financiado, tasa_mensual, plazo_meses, pago_mensual,
                     total_intereses, total_a_pagar, no_factura, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'pendiente')
            ");
            $stmt->execute([
                $auto_id,
                $cliente_id,
                $precio_total,
                $color_elegido,
                $enganche,
                $monto_financiado,
                $tasa_mensual,
                $plazo_meses,
                $pago_mensual,
                $total_intereses,
                $total_pagar,
                $no_factura,
            ]);
            $venta_id = (int) $pdo->lastInsertId();
        } catch (Exception $e) {
        }
    }

    // Redirigir a factura GET (evita reenvío de formulario al recargar)
    $qs = $qs_fin
        . '&vid=' . $venta_id
        . '&cli_nombre=' . urlencode($cli_nombre)
        . '&cli_email=' . urlencode($cli_email)
        . '&cli_telefono=' . urlencode($cli_telefono);
    header("Location: factura.php?$qs");
    exit;
}

/* ══════════════════════════════════════════════════════════════
   MODO B — GET con vid: mostrar factura guardada
   Carga datos reales de BD; no inserta nada.
══════════════════════════════════════════════════════════════ */
$tiene_cliente = false;
$venta_guardada = false;
$no_factura = 'LUX-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
$modelo_auto = 'Vehículo configurado';
$auto_data = null;
$cliente_nombre = '';
$cliente_email = '';
$cliente_telefono = '';

if (isset($_GET['vid']) && (int) $_GET['vid'] > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT v.*, a.modelo, a.categoria,
                   CONCAT(COALESCE(c.nombre,''),' ',COALESCE(c.apellido,'')) AS c_nombre,
                   c.email AS c_email, c.telefono AS c_tel
            FROM ventas v
            LEFT JOIN autos    a ON a.id = v.auto_id
            LEFT JOIN clientes c ON c.id = v.cliente_id
            WHERE v.id = ?
        ");
        $stmt->execute([(int) $_GET['vid']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $no_factura = $row['no_factura'];
            $precio_total = (float) $row['precio_total'];
            $enganche = (float) $row['enganche'];
            $monto_financiado = (float) $row['monto_financiado'];
            $tasa_mensual = (float) $row['tasa_mensual'];
            $plazo_meses = (int) $row['plazo_meses'];
            $pago_mensual = (float) $row['pago_mensual'];
            $total_intereses = (float) $row['total_intereses'];
            $total_pagar = (float) $row['total_a_pagar'];
            $color_elegido = $row['color_elegido'] ?: 'Original';
            $modelo_auto = $row['modelo'] ?? 'Vehículo configurado';
            $auto_data = $row['categoria'] ? ['categoria' => $row['categoria']] : null;
            // Cliente: prioridad BD > GET
            $cliente_nombre = trim($row['c_nombre']) ?: trim($_GET['cli_nombre'] ?? '');
            $cliente_email = trim($row['c_email'] ?? '') ?: trim($_GET['cli_email'] ?? '');
            $cliente_telefono = trim($row['c_tel'] ?? '') ?: trim($_GET['cli_telefono'] ?? '');
            $tiene_cliente = true;
            $venta_guardada = true;
        }
    } catch (Exception $e) {
    }
}

/* ══════════════════════════════════════════════════════════════
   MODO C — GET sin vid: determinar si mostrar pre-form o factura
   (URL directa compartida con cli_nombre en query string)
══════════════════════════════════════════════════════════════ */
if (!$tiene_cliente) {
    $cliente_nombre = trim($_GET['cli_nombre'] ?? '');
    $cliente_email = trim($_GET['cli_email'] ?? '');
    $cliente_telefono = trim($_GET['cli_telefono'] ?? '');
    $tiene_cliente = ($cliente_nombre !== '');

    if (!$tiene_cliente && $auto_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT modelo, categoria FROM autos WHERE id = ?");
            $stmt->execute([$auto_id]);
            $auto_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($auto_data)
                $modelo_auto = $auto_data['modelo'];
        } catch (Exception $e) {
        }
    }
}

$fecha = date('d/m/Y');
$hora = date('H:i');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura
        <?= htmlspecialchars($no_factura) ?> — LUXE MOTORS MX
    </title>
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
            --border: #1e1e1e;
            --gold: #C9A96E;
            --gold-dim: #7a6540;
            --text: #EBEBEB;
            --muted: #555;
            --error: #e05252;
            --radius: 4px;
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
            padding: 48px 24px
        }

        .factura-wrap {
            max-width: 860px;
            margin: 0 auto
        }

        /* ── ACTIONS BAR ── */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            text-decoration: none;
            font-size: .82rem;
            transition: color .2s
        }

        .back-link:hover {
            color: var(--text)
        }

        .print-actions {
            display: flex;
            gap: 10px
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: var(--gold);
            color: #070707;
            border: none;
            border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .2em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background .3s
        }

        .btn-print:hover {
            background: #d4b47e
        }

        .btn-share {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            letter-spacing: .15em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all .3s;
            text-decoration: none
        }

        .btn-share:hover {
            color: var(--text);
            border-color: #333
        }

        /* ══════════════════════
           PRE-FORMULARIO
        ══════════════════════ */
        .pre-form-wrap {
            max-width: 600px;
            margin: 0 auto 48px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden
        }

        .pre-form-head {
            padding: 28px 36px 22px;
            border-bottom: 1px solid var(--border);
            position: relative
        }

        .pre-form-head::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), rgba(201, 169, 110, .15), transparent)
        }

        .pfh-eyebrow {
            font-size: 9px;
            letter-spacing: .4em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 8px
        }

        .pre-form-head h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 1.8rem;
            line-height: 1.1
        }

        .pre-form-head p {
            font-size: .82rem;
            color: var(--muted);
            margin-top: 6px
        }

        .pre-form-body {
            padding: 28px 36px 32px
        }

        /* Tabs */
        .cli-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 24px
        }

        .cli-tab {
            padding: 10px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--muted);
            font-family: 'DM Sans', sans-serif;
            font-size: .8rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all .25s;
            text-align: center
        }

        .cli-tab:hover {
            border-color: #333;
            color: var(--text)
        }

        .cli-tab.active {
            border-color: var(--gold-dim);
            color: var(--gold);
            background: rgba(201, 169, 110, .05)
        }

        /* Panels */
        .cli-panel {
            display: none
        }

        .cli-panel.visible {
            display: block
        }

        /* Fields */
        .pf-field {
            margin-bottom: 16px
        }

        .pf-field label {
            display: block;
            font-size: 9px;
            letter-spacing: .25em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 7px
        }

        .pf-field input,
        .pf-field select {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 11px 14px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: .875rem;
            font-weight: 300;
            outline: none;
            transition: border-color .3s
        }

        .pf-field input:focus,
        .pf-field select:focus {
            border-color: var(--gold-dim)
        }

        .pf-field input::placeholder {
            color: var(--muted)
        }

        .pf-field select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23555' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            cursor: pointer
        }

        .pf-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px
        }

        /* Preview cliente */
        .cliente-preview {
            display: none;
            background: rgba(201, 169, 110, .05);
            border: 1px solid var(--gold-dim);
            border-radius: var(--radius);
            padding: 14px 16px;
            margin-top: 10px;
            font-size: .84rem
        }

        .cliente-preview.visible {
            display: block
        }

        .cp-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.1rem;
            color: var(--text);
            margin-bottom: 3px
        }

        .cp-detail {
            color: var(--muted);
            font-size: .78rem
        }

        /* Checkbox */
        .pf-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 12px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            cursor: pointer;
            transition: border-color .25s
        }

        .pf-check:hover {
            border-color: #333
        }

        .pf-check input[type="checkbox"] {
            width: 15px;
            height: 15px;
            accent-color: var(--gold);
            cursor: pointer;
            flex-shrink: 0
        }

        .pf-check label {
            font-size: .83rem;
            color: var(--muted);
            cursor: pointer
        }

        /* Botón generar */
        .btn-generar {
            width: 100%;
            padding: 13px;
            background: transparent;
            border: 1px solid var(--gold-dim);
            border-radius: var(--radius);
            color: var(--gold);
            font-family: 'DM Sans', sans-serif;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: .3em;
            text-transform: uppercase;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: color .35s
        }

        .btn-generar::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gold);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .35s cubic-bezier(.4, 0, .2, 1)
        }

        .btn-generar span {
            position: relative;
            z-index: 1
        }

        .btn-generar:hover::before {
            transform: scaleX(1)
        }

        .btn-generar:hover {
            color: #070707
        }

        /* ══════════════════════
           DOCUMENTO FACTURA
        ══════════════════════ */
        .factura {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden
        }

        .fac-header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 32px;
            align-items: start;
            padding: 40px 48px;
            border-bottom: 1px solid var(--border);
            position: relative
        }

        .fac-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), rgba(201, 169, 110, .2), transparent)
        }

        .fac-brand h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 2rem;
            letter-spacing: .05em;
            margin-bottom: 4px
        }

        .fac-brand h1 em {
            font-style: normal;
            color: var(--gold)
        }

        .fac-brand .tagline {
            font-size: .8rem;
            color: var(--muted);
            margin-bottom: 4px
        }

        .fac-brand .rfc {
            font-size: .72rem;
            color: #333;
            letter-spacing: .1em
        }

        .fac-meta {
            text-align: right
        }

        .fac-meta .doc-type {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 1.8rem;
            color: var(--gold);
            margin-bottom: 12px
        }

        .fac-meta-row {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            font-size: .78rem;
            margin-bottom: 4px
        }

        .fac-meta-row .label {
            color: var(--muted)
        }

        .fac-meta-row .value {
            color: var(--text);
            font-weight: 500
        }

        .fac-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 32px 48px;
            border-bottom: 1px solid var(--border);
            gap: 32px
        }

        .info-block .block-label {
            font-size: 9px;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 12px
        }

        .info-block .client-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.3rem;
            font-weight: 300;
            margin-bottom: 4px
        }

        .info-block .client-detail {
            font-size: .82rem;
            color: var(--muted);
            margin-bottom: 2px
        }

        .info-block .vehicle-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem;
            font-weight: 300;
            color: var(--gold);
            margin-bottom: 6px
        }

        .info-block .vehicle-cat {
            font-size: .75rem;
            color: var(--muted);
            letter-spacing: .15em;
            text-transform: uppercase
        }

        .fac-table-wrap {
            padding: 0 48px;
            border-bottom: 1px solid var(--border)
        }

        .fac-table {
            width: 100%;
            border-collapse: collapse
        }

        .fac-table thead tr {
            border-bottom: 1px solid var(--border)
        }

        .fac-table th {
            padding: 14px 0;
            text-align: left;
            font-size: 9px;
            letter-spacing: .25em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 400
        }

        .fac-table th.num {
            text-align: right
        }

        .fac-table td {
            padding: 16px 0;
            border-bottom: 1px solid rgba(28, 28, 28, .6);
            font-size: .875rem;
            vertical-align: middle
        }

        .fac-table td.num {
            text-align: right
        }

        .fac-table tr:last-child td {
            border-bottom: none
        }

        .concept-main {
            font-weight: 400;
            color: var(--text)
        }

        .concept-sub {
            font-size: .75rem;
            color: var(--muted);
            margin-top: 2px
        }

        .rate-badge {
            display: inline-flex;
            padding: 3px 10px;
            background: rgba(201, 169, 110, .08);
            border: 1px solid rgba(201, 169, 110, .2);
            border-radius: 20px;
            font-size: .75rem;
            color: var(--gold)
        }

        .fac-totals {
            display: flex;
            justify-content: flex-end;
            padding: 32px 48px;
            border-bottom: 1px solid var(--border)
        }

        .totals-block {
            width: 300px
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: .82rem;
            color: var(--muted);
            padding: 8px 0;
            border-bottom: 1px solid rgba(28, 28, 28, .6)
        }

        .total-row:last-child {
            border-bottom: none
        }

        .total-row.final {
            border-top: 2px solid var(--gold);
            border-bottom: none;
            margin-top: 8px;
            padding-top: 16px
        }

        .total-row.final .r-label {
            font-size: .88rem;
            color: var(--text);
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase
        }

        .total-row.final .r-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--gold);
            line-height: 1
        }

        .fac-footer {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 32px;
            padding: 32px 48px;
            align-items: center
        }

        .fac-footer .thanks {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.2rem;
            font-weight: 300;
            font-style: italic;
            color: var(--gold);
            margin-bottom: 6px
        }

        .fac-footer .guarantees {
            font-size: .75rem;
            color: var(--muted)
        }

        .fac-stamp {
            width: 80px;
            height: 80px;
            border: 1px solid rgba(201, 169, 110, .3);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--gold);
            font-size: .55rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            position: relative
        }

        .fac-stamp::before {
            content: '';
            position: absolute;
            inset: 4px;
            border-radius: 50%;
            border: 1px solid rgba(201, 169, 110, .1)
        }

        .fac-stamp i {
            font-size: 1.2rem;
            margin-bottom: 4px
        }

        /* ── PRINT ── */
        @media print {
            @page {
                margin: 15mm;
                size: A4
            }

            body {
                background: #fff !important;
                color: #111 !important;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact
            }

            .no-print {
                display: none !important
            }

            .factura {
                border: 1px solid #ddd !important;
                background: #fff !important
            }

            .fac-header::before {
                background: #C9A96E !important
            }

            .fac-brand h1,
            .fac-brand h1 em,
            .fac-meta .doc-type,
            .info-block .vehicle-name,
            .total-row.final .r-value,
            .rate-badge,
            .fac-footer .thanks,
            .fac-stamp {
                color: #9a7a50 !important
            }

            .fac-header,
            .fac-info,
            .fac-table-wrap,
            .fac-totals,
            .fac-footer {
                border-color: #eee !important
            }

            .fac-table thead tr,
            .fac-table td,
            .total-row {
                border-color: #e5e5e5 !important
            }

            .total-row.final {
                border-top-color: #9a7a50 !important
            }

            .fac-brand .tagline,
            .fac-brand .rfc,
            .concept-sub,
            .client-detail,
            .vehicle-cat,
            .fac-table th,
            .total-row .r-label,
            .fac-footer .guarantees {
                color: #888 !important
            }

            .rate-badge {
                background: #f5f0e8 !important;
                border-color: #d4b47e !important
            }

            .fac-stamp {
                border-color: #d4b47e !important
            }

            .concept-main,
            .fac-table td {
                color: #222 !important
            }
        }
    </style>
</head>

<body>

    <!-- ACTIONS BAR -->
    <div class="actions-bar no-print">
        <a href="plan_pagos.php" class="back-link">
            <i class="fas fa-arrow-left fa-xs"></i> Volver al plan de pagos
        </a>
        <div class="print-actions">
            <a href="index.php" class="btn-share"><i class="fas fa-home fa-xs"></i> Inicio</a>
            <?php if ($tiene_cliente): ?>
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print fa-xs"></i> Imprimir factura
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="factura-wrap">

        <?php if (!$tiene_cliente): ?>
            <!-- ══════════════════════════════════════════════════
     PRE-FORMULARIO: elegir o crear cliente
══════════════════════════════════════════════════ -->
            <div class="pre-form-wrap">
                <div class="pre-form-head">
                    <p class="pfh-eyebrow">Paso final · Datos del cliente</p>
                    <h2>¿A quién emitimos<br>la cotización?</h2>
                    <p>Elige un cliente registrado o ingresa uno nuevo.</p>
                </div>
                <div class="pre-form-body">

                    <form method="POST" action="factura.php?<?= $qs_fin ?>" id="clienteForm">
                        <input type="hidden" name="submit_cliente" value="1">
                        <input type="hidden" name="cli_tipo" id="cli_tipo" value="existente">

                        <!-- Tabs -->
                        <div class="cli-tabs">
                            <button type="button" class="cli-tab active" id="tab-existente"
                                onclick="switchTab('existente')">
                                <i class="fas fa-users fa-xs" style="margin-right:6px"></i>Cliente registrado
                            </button>
                            <button type="button" class="cli-tab" id="tab-nuevo" onclick="switchTab('nuevo')">
                                <i class="fas fa-user-plus fa-xs" style="margin-right:6px"></i>Cliente nuevo
                            </button>
                        </div>

                        <!-- Panel: cliente existente -->
                        <div class="cli-panel visible" id="panel-existente">
                            <?php if (!empty($clientes_lista)): ?>
                                <div class="pf-field">
                                    <label>Seleccionar cliente</label>
                                    <select name="cli_id_existente" id="cli_select" onchange="previewCliente(this)">
                                        <option value="">— Elige un cliente —</option>
                                        <?php foreach ($clientes_lista as $cl): ?>
                                            <option value="<?= $cl['id'] ?>"
                                                data-nombre="<?= htmlspecialchars(trim($cl['nombre'] . ' ' . $cl['apellido'])) ?>"
                                                data-email="<?= htmlspecialchars($cl['email'] ?? '') ?>"
                                                data-tel="<?= htmlspecialchars($cl['telefono'] ?? '') ?>">
                                                <?= htmlspecialchars(trim($cl['nombre'] . ' ' . $cl['apellido'])) ?>
                                                <?= $cl['email'] ? ' — ' . htmlspecialchars($cl['email']) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="cliente-preview" id="clientePreview">
                                    <div class="cp-name" id="cp-nombre"></div>
                                    <div class="cp-detail" id="cp-email"></div>
                                    <div class="cp-detail" id="cp-tel"></div>
                                </div>
                            <?php else: ?>
                                <p
                                    style="font-size:.82rem;color:var(--muted);margin-bottom:16px;padding:12px;background:var(--bg);border-radius:var(--radius)">
                                    No hay clientes registrados aún.
                                    <button type="button" onclick="switchTab('nuevo')"
                                        style="background:none;border:none;color:var(--gold);cursor:pointer;font-size:.82rem;padding:0">
                                        Agregar cliente nuevo →
                                    </button>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Panel: cliente nuevo -->
                        <div class="cli-panel" id="panel-nuevo">
                            <div class="pf-field">
                                <label>Nombre completo *</label>
                                <input type="text" name="cli_nombre" id="cli_nombre" placeholder="Ej. Carlos Mendoza">
                            </div>
                            <div class="pf-row">
                                <div class="pf-field">
                                    <label>Email</label>
                                    <input type="email" name="cli_email" placeholder="correo@ejemplo.com">
                                </div>
                                <div class="pf-field">
                                    <label>Teléfono</label>
                                    <input type="tel" name="cli_telefono" placeholder="+52 55 1234 5678">
                                </div>
                            </div>
                            <label class="pf-check">
                                <input type="checkbox" name="cli_guardar" value="1" checked>
                                <span>
                                    <i class="fas fa-user-plus fa-xs" style="margin-right:6px;color:var(--gold)"></i>
                                    Guardar este cliente en la base de datos
                                </span>
                            </label>
                        </div>

                        <button type="submit" class="btn-generar">
                            <span>Generar cotización →</span>
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- ══════════════════════════════════════════════════
     FACTURA COMPLETA
══════════════════════════════════════════════════ -->
            <div class="factura">

                <div class="fac-header">
                    <div class="fac-brand">
                        <h1>LUXE MOTORS <em>MX</em></h1>
                        <p class="tagline">Compra · Venta · Configuración · Financiamiento Premium</p>
                        <p class="rfc">Ciudad de México · RFC: LUXM250416ABC · Tel: +52 55 1234 5678</p>
                    </div>
                    <div class="fac-meta">
                        <div class="doc-type">Cotización Final</div>
                        <div class="fac-meta-row">
                            <span class="label">Folio:</span>
                            <span class="value">
                                <?= htmlspecialchars($no_factura) ?>
                            </span>
                        </div>
                        <div class="fac-meta-row">
                            <span class="label">Fecha:</span>
                            <span class="value">
                                <?= $fecha ?>
                            </span>
                        </div>
                        <div class="fac-meta-row">
                            <span class="label">Hora:</span>
                            <span class="value">
                                <?= $hora ?> hrs
                            </span>
                        </div>
                    </div>
                </div>

                <div class="fac-info">
                    <div class="info-block">
                        <p class="block-label">Datos del cliente</p>
                        <div class="client-name">
                            <?= htmlspecialchars($cliente_nombre) ?>
                        </div>
                        <?php if (!empty($cliente_email)): ?>
                            <p class="client-detail">
                                <?= htmlspecialchars($cliente_email) ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($cliente_telefono)): ?>
                            <p class="client-detail">
                                <?= htmlspecialchars($cliente_telefono) ?>
                            </p>
                        <?php endif; ?>
                        <p class="client-detail" style="margin-top:6px;">Emisión:
                            <?= $fecha ?> ·
                            <?= $hora ?> hrs
                        </p>
                    </div>
                    <div class="info-block" style="text-align:right;">
                        <p class="block-label">Vehículo seleccionado</p>
                        <div class="vehicle-name">
                            <?= htmlspecialchars($modelo_auto) ?>
                        </div>
                        <?php if (!empty($auto_data['categoria'])): ?>
                            <p class="vehicle-cat">
                                <?= htmlspecialchars($auto_data['categoria']) ?>
                            </p>
                        <?php endif; ?>
                        <p class="client-detail" style="margin-top:6px;">Color:
                            <?= htmlspecialchars($color_elegido) ?>
                        </p>
                    </div>
                </div>

                <div class="fac-table-wrap">
                    <table class="fac-table">
                        <thead>
                            <tr>
                                <th style="width:60%;">Concepto</th>
                                <th class="num">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="concept-main">Precio total del vehículo configurado</div>
                                    <div class="concept-sub">
                                        <?= htmlspecialchars($modelo_auto) ?> ·
                                        <?= htmlspecialchars($color_elegido) ?>
                                    </div>
                                </td>
                                <td class="num">$
                                    <?= number_format($precio_total, 2) ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="concept-main">Enganche (anticipo)</div>
                                    <div class="concept-sub">
                                        <?= $precio_total > 0 ? number_format(($enganche / $precio_total) * 100, 1) : 0 ?>% del
                                        precio total
                                    </div>
                                </td>
                                <td class="num">$
                                    <?= number_format($enganche, 2) ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="concept-main">Monto financiado</div>
                                    <div class="concept-sub">Saldo a financiar tras el enganche</div>
                                </td>
                                <td class="num">$
                                    <?= number_format($monto_financiado, 2) ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="concept-main">Plan de financiamiento</div>
                                    <div class="concept-sub">
                                        <?= $plazo_meses ?> pagos mensuales ·
                                        <span class="rate-badge">
                                            <?= $tasa_mensual ?>% mensual
                                        </span>
                                    </div>
                                </td>
                                <td class="num">$
                                    <?= number_format($pago_mensual, 2) ?>/mes
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="concept-main">Total de intereses del crédito</div>
                                    <div class="concept-sub">Costo financiero total durante el plazo</div>
                                </td>
                                <td class="num">$
                                    <?= number_format($total_intereses, 2) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="fac-totals">
                    <div class="totals-block">
                        <div class="total-row">
                            <span class="r-label">Precio vehículo</span>
                            <span class="r-value">$
                                <?= number_format($precio_total, 2) ?>
                            </span>
                        </div>
                        <div class="total-row">
                            <span class="r-label">Intereses totales</span>
                            <span class="r-value">$
                                <?= number_format($total_intereses, 2) ?>
                            </span>
                        </div>
                        <div class="total-row final">
                            <span class="r-label">Total a pagar</span>
                            <span class="r-value">$
                                <?= number_format($total_pagar, 2) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="fac-footer">
                    <div>
                        <p class="thanks">¡Gracias por elegir LUXE MOTORS MX!</p>
                        <p class="guarantees">Garantía de fábrica · Servicio postventa certificado · Compra-Venta
                            transparente · Financiamiento flexible</p>
                        <?php if ($venta_guardada): ?>
                            <p style="font-size:.72rem;color:#2a2a2a;margin-top:8px;">
                                ✓ Cotización registrada · Folio:
                                <?= htmlspecialchars($no_factura) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="fac-stamp">
                        <i class="fas fa-car"></i>
                        <span>LUXE<br>MOTORS MX</span>
                    </div>
                </div>

            </div><!-- /factura -->
        <?php endif; ?>

    </div><!-- /factura-wrap -->

    <script>
        function switchTab(tipo) {
            document.getElementById('cli_tipo').value = tipo;
            document.querySelectorAll('.cli-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + tipo).classList.add('active');
            document.querySelectorAll('.cli-panel').forEach(p => p.classList.remove('visible'));
            document.getElementById('panel-' + tipo).classList.add('visible');
        }

        function previewCliente(sel) {
            const opt = sel.options[sel.selectedIndex];
            const preview = document.getElementById('clientePreview');
            if (!opt.value) { preview.classList.remove('visible'); return; }
            document.getElementById('cp-nombre').textContent = opt.dataset.nombre || '';
            document.getElementById('cp-email').textContent = opt.dataset.email || '';
            document.getElementById('cp-tel').textContent = opt.dataset.tel || '';
            preview.classList.add('visible');
        }

        document.getElementById('clienteForm')?.addEventListener('submit', function (e) {
            const tipo = document.getElementById('cli_tipo').value;
            if (tipo === 'existente') {
                const sel = document.getElementById('cli_select');
                if (sel && !sel.value) {
                    e.preventDefault();
                    sel.style.borderColor = '#e05252';
                    sel.focus();
                    setTimeout(() => sel.style.borderColor = '', 2500);
                }
            } else {
                const nom = document.getElementById('cli_nombre');
                if (nom && !nom.value.trim()) {
                    e.preventDefault();
                    nom.style.borderColor = '#e05252';
                    nom.focus();
                    setTimeout(() => nom.style.borderColor = '', 2500);
                }
            }
        });
    </script>
</body>

</html>