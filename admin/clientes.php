<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
include '../config/db.php';

$mensaje = '';
$tipo_mensaje = 'success';
$errores = [];

/* ────────────────────────────────────────────────────────────
   EXPORTAR CSV
────────────────────────────────────────────────────────────── */
if (isset($_GET['exportar'])) {
    $todos = $pdo->query("SELECT id, nombre, apellido, email, rfc, telefono, direccion, notas, fecha_registro FROM clientes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="clientes_luxe_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Nombre', 'Apellido', 'Email', 'RFC', 'Teléfono', 'Dirección', 'Notas', 'Fecha registro']);
    foreach ($todos as $r)
        fputcsv($out, $r);
    fclose($out);
    exit;
}

/* ────────────────────────────────────────────────────────────
   VALIDACIÓN COMPARTIDA
────────────────────────────────────────────────────────────── */
function validarCliente(array $p, PDO $pdo, int $excludeId = 0): array
{
    $e = [];
    if (empty(trim($p['nombre'])))
        $e[] = 'El nombre es obligatorio.';
    if (empty(trim($p['apellido'])))
        $e[] = 'El apellido es obligatorio.';
    if (!filter_var(trim($p['email']), FILTER_VALIDATE_EMAIL))
        $e[] = 'El email no tiene un formato válido.';

    // RFC: vacío está bien; si viene, validar formato mexicano básico (12 o 13 chars)
    $rfc = strtoupper(trim($p['rfc'] ?? ''));
    if ($rfc && !preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc))
        $e[] = 'El RFC no tiene el formato correcto (ej. PEPJ800101H09).';

    // Email único
    if (empty($e)) {
        $q = $pdo->prepare("SELECT id FROM clientes WHERE email = ? AND id != ?");
        $q->execute([trim($p['email']), $excludeId]);
        if ($q->fetch())
            $e[] = 'Ese email ya está registrado para otro cliente.';
    }
    return $e;
}

/* ────────────────────────────────────────────────────────────
   AGREGAR
────────────────────────────────────────────────────────────── */
if (isset($_POST['agregar'])) {
    $errores = validarCliente($_POST, $pdo);
    if (empty($errores)) {
        $stmt = $pdo->prepare("
            INSERT INTO clientes (nombre, apellido, email, rfc, telefono, direccion, notas)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($_POST['nombre']),
            trim($_POST['apellido']),
            trim($_POST['email']),
            strtoupper(trim($_POST['rfc'] ?? '')),
            trim($_POST['telefono'] ?? ''),
            trim($_POST['direccion'] ?? ''),
            trim($_POST['notas'] ?? ''),
        ]);
        $mensaje = 'Cliente <strong>' . htmlspecialchars($_POST['nombre'] . ' ' . $_POST['apellido']) . '</strong> agregado correctamente.';
    }
}

/* ────────────────────────────────────────────────────────────
   EDITAR
────────────────────────────────────────────────────────────── */
if (isset($_POST['editar'])) {
    $errores = validarCliente($_POST, $pdo, (int) $_POST['id']);
    if (empty($errores)) {
        $stmt = $pdo->prepare("
            UPDATE clientes SET nombre=?, apellido=?, email=?, rfc=?, telefono=?, direccion=?, notas=?
            WHERE id=?
        ");
        $stmt->execute([
            trim($_POST['nombre']),
            trim($_POST['apellido']),
            trim($_POST['email']),
            strtoupper(trim($_POST['rfc'] ?? '')),
            trim($_POST['telefono'] ?? ''),
            trim($_POST['direccion'] ?? ''),
            trim($_POST['notas'] ?? ''),
            (int) $_POST['id'],
        ]);
        $mensaje = 'Cliente actualizado correctamente.';
    }
}

/* ────────────────────────────────────────────────────────────
   ELIMINAR
────────────────────────────────────────────────────────────── */
if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([(int) $_GET['eliminar']]);
    $mensaje = 'Cliente eliminado.';
    $tipo_mensaje = 'warning';
}

/* ────────────────────────────────────────────────────────────
   LECTURA
────────────────────────────────────────────────────────────── */
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalClientes = count($clientes);

$clienteEdit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $clienteEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* Historial de ventas del cliente (modal) */
$perfilId = isset($_GET['perfil']) ? (int) $_GET['perfil'] : 0;
$perfilData = null;
$perfilVentas = [];
if ($perfilId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$perfilId]);
    $perfilData = $stmt->fetch(PDO::FETCH_ASSOC);

    try {
        $stmt = $pdo->prepare("
            SELECT v.no_factura, v.precio_total, v.total_a_pagar, v.status, v.fecha_venta, a.modelo
            FROM ventas v
            LEFT JOIN autos a ON a.id = v.auto_id
            WHERE v.cliente_id = ?
            ORDER BY v.fecha_venta DESC
        ");
        $stmt->execute([$perfilId]);
        $perfilVentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes — LUXE MOTORS MX</title>
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
            --error: #e05252;
            --success: #4caf7d;
            --warning: #e0a535;
            --radius: 6px;
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

        /* ── SIDEBAR ── */
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

        .sidebar-logo .ey {
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
            color: var(--error);
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

        /* ── MAIN ── */
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
            margin-bottom: 40px
        }

        .page-title .ey {
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

        .topbar-actions {
            display: flex;
            gap: 10px;
            align-items: center
        }

        /* Buttons */
        .btn-export {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--muted);
            font-family: 'DM Sans', sans-serif;
            font-size: 10px;
            letter-spacing: .2em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all .3s;
            cursor: pointer
        }

        .btn-export:hover {
            color: var(--text);
            border-color: #333
        }

        .btn-export i {
            font-size: .8rem
        }

        /* Notification */
        .notif {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 20px;
            border-radius: var(--radius);
            margin-bottom: 32px;
            font-size: .875rem;
            animation: slideIn .3s ease
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-8px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .notif.success {
            background: rgba(76, 175, 125, .08);
            border: 1px solid rgba(76, 175, 125, .3);
            color: var(--success)
        }

        .notif.warning {
            background: rgba(224, 165, 53, .08);
            border: 1px solid rgba(224, 165, 53, .3);
            color: var(--warning)
        }

        .notif i {
            margin-top: 2px;
            flex-shrink: 0
        }

        /* Error list */
        .error-box {
            background: rgba(224, 82, 82, .06);
            border: 1px solid rgba(224, 82, 82, .25);
            border-radius: var(--radius);
            padding: 14px 18px;
            margin-bottom: 20px
        }

        .error-box ul {
            list-style: none
        }

        .error-box li {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .82rem;
            color: var(--error);
            padding: 3px 0
        }

        /* Layout */
        .layout {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 24px;
            align-items: start
        }

        /* ── FORM PANEL ── */
        .form-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            position: sticky;
            top: 24px
        }

        .fp-head {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .fp-head .ttl {
            font-size: 10px;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--muted)
        }

        .mode-badge {
            font-size: 9px;
            letter-spacing: .15em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 20px
        }

        .mode-badge.add {
            background: rgba(76, 175, 125, .1);
            color: var(--success);
            border: 1px solid rgba(76, 175, 125, .3)
        }

        .mode-badge.edit {
            background: rgba(201, 169, 110, .1);
            color: var(--gold);
            border: 1px solid var(--gold-dim)
        }

        .fp-body {
            padding: 24px
        }

        /* Section separator inside form */
        .form-section {
            font-size: 9px;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 0 0 10px;
            border-bottom: 1px solid var(--border);
            margin: 20px 0 16px
        }

        .form-section:first-child {
            margin-top: 0
        }

        .frow {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px
        }

        .frow-1 {
            margin-bottom: 14px
        }

        .field label {
            display: block;
            font-size: 9px;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 7px
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 10px 13px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: .875rem;
            font-weight: 300;
            outline: none;
            transition: border-color .3s, box-shadow .3s;
            resize: vertical
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: var(--gold-dim);
            box-shadow: 0 0 0 3px rgba(201, 169, 110, .06)
        }

        .field input.invalid {
            border-color: rgba(224, 82, 82, .5)
        }

        .field .hint {
            font-size: .72rem;
            color: var(--muted);
            margin-top: 5px
        }

        .field select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23555' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            cursor: pointer
        }

        .char-count {
            font-size: .7rem;
            color: var(--muted);
            text-align: right;
            margin-top: 4px
        }

        .btn-submit {
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
            transition: all .35s;
            position: relative;
            overflow: hidden
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gold);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .35s cubic-bezier(.4, 0, .2, 1)
        }

        .btn-submit span {
            position: relative;
            z-index: 1
        }

        .btn-submit:hover::before {
            transform: scaleX(1)
        }

        .btn-submit:hover {
            color: var(--bg)
        }

        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 10px;
            padding: 11px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--muted);
            font-family: 'DM Sans', sans-serif;
            font-size: 10px;
            letter-spacing: .2em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all .3s
        }

        .btn-cancel:hover {
            color: var(--text);
            border-color: #333
        }

        /* ── TABLE PANEL ── */
        .table-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden
        }

        .tp-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px;
            border-bottom: 1px solid var(--border)
        }

        .tp-title {
            font-size: 10px;
            letter-spacing: .25em;
            text-transform: uppercase;
            color: var(--muted)
        }

        .count-badge {
            background: rgba(201, 169, 110, .1);
            border: 1px solid var(--gold-dim);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: .75rem;
            color: var(--gold);
            font-family: 'Cormorant Garamond', serif
        }

        .search-wrap {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 10px
        }

        .search-inner {
            position: relative;
            flex: 1
        }

        .search-inner i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .72rem
        }

        .search-inner input {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 9px 13px 9px 36px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: .84rem;
            outline: none;
            transition: border-color .3s
        }

        .search-inner input:focus {
            border-color: var(--gold-dim)
        }

        .search-inner input::placeholder {
            color: var(--muted)
        }

        /* Sort pills */
        .sort-pills {
            display: flex;
            gap: 6px;
            align-items: center
        }

        .sort-pill {
            padding: 6px 12px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 20px;
            color: var(--muted);
            font-size: .72rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all .25s;
            font-family: 'DM Sans', sans-serif
        }

        .sort-pill:hover {
            border-color: #333;
            color: var(--text)
        }

        .sort-pill.active {
            border-color: var(--gold-dim);
            color: var(--gold);
            background: rgba(201, 169, 110, .05)
        }

        /* Data table */
        .data-table {
            width: 100%;
            border-collapse: collapse
        }

        .data-table th {
            padding: 11px 18px;
            text-align: left;
            font-size: 9px;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 400;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            user-select: none;
            white-space: nowrap
        }

        .data-table th:hover {
            color: var(--text)
        }

        .data-table th .sort-icon {
            margin-left: 4px;
            opacity: .3;
            font-size: .65rem
        }

        .data-table th.sorted .sort-icon {
            opacity: 1;
            color: var(--gold)
        }

        .data-table td {
            padding: 13px 18px;
            font-size: .84rem;
            border-bottom: 1px solid rgba(28, 28, 28, .7);
            vertical-align: middle
        }

        .data-table tr:last-child td {
            border-bottom: none
        }

        .data-table tbody tr {
            transition: background .2s;
            cursor: pointer
        }

        .data-table tbody tr:hover td {
            background: rgba(20, 20, 20, .8)
        }

        .client-cell {
            display: flex;
            align-items: center;
            gap: 11px
        }

        .av {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(201, 169, 110, .1);
            border: 1px solid rgba(201, 169, 110, .2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .68rem;
            font-weight: 500;
            color: var(--gold);
            flex-shrink: 0
        }

        .cn {
            font-weight: 400;
            color: var(--text);
            line-height: 1.3
        }

        .cid {
            font-size: .68rem;
            color: var(--muted)
        }

        .em {
            color: #666;
            font-size: .8rem
        }

        .ph {
            color: var(--muted);
            font-size: .8rem
        }

        .dt {
            color: var(--muted);
            font-size: .76rem
        }

        /* RFC badge */
        .rfc-badge {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(201, 169, 110, .07);
            border: 1px solid rgba(201, 169, 110, .2);
            border-radius: 3px;
            font-size: .7rem;
            color: var(--gold);
            letter-spacing: .05em;
            font-family: monospace
        }

        .action-cell {
            display: flex;
            gap: 5px;
            justify-content: flex-end
        }

        .btn-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            font-size: .72rem;
            text-decoration: none;
            cursor: pointer;
            transition: all .25s
        }

        .btn-icon.view:hover {
            color: #7ec8e3;
            border-color: rgba(126, 200, 227, .4);
            background: rgba(126, 200, 227, .05)
        }

        .btn-icon.edit:hover {
            color: var(--gold);
            border-color: var(--gold-dim);
            background: rgba(201, 169, 110, .07)
        }

        .btn-icon.delete:hover {
            color: var(--error);
            border-color: rgba(224, 82, 82, .4);
            background: rgba(224, 82, 82, .05)
        }

        .empty-state {
            text-align: center;
            padding: 56px 24px;
            color: var(--muted)
        }

        .empty-state i {
            font-size: 1.8rem;
            margin-bottom: 14px;
            display: block;
            color: #222
        }

        .empty-state p {
            font-size: .875rem
        }

        /* ── MODAL PERFIL ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .75);
            z-index: 500;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            backdrop-filter: blur(4px)
        }

        .modal-overlay.open {
            display: flex
        }

        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            width: 100%;
            max-width: 680px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalIn .3s ease
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(.96)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        .modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 28px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            background: var(--surface);
            z-index: 1
        }

        .modal-head .ttl {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 1.6rem
        }

        .modal-close {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transition: all .2s;
            font-size: .8rem
        }

        .modal-close:hover {
            color: var(--text);
            border-color: #333
        }

        .modal-body {
            padding: 28px
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px
        }

        .profile-field .lbl {
            font-size: 9px;
            letter-spacing: .25em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 5px
        }

        .profile-field .val {
            font-size: .9rem;
            color: var(--text)
        }

        .profile-field .val.empty {
            color: var(--muted);
            font-style: italic
        }

        .profile-field.full {
            grid-column: 1/-1
        }

        .modal-section-title {
            font-size: 9px;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--muted);
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px
        }

        /* Historial table inside modal */
        .hist-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem
        }

        .hist-table th {
            padding: 8px 14px;
            text-align: left;
            font-size: 9px;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 400;
            border-bottom: 1px solid var(--border)
        }

        .hist-table td {
            padding: 10px 14px;
            border-bottom: 1px solid rgba(28, 28, 28, .5)
        }

        .hist-table tr:last-child td {
            border-bottom: none
        }

        .status-badge {
            display: inline-flex;
            padding: 3px 9px;
            border-radius: 12px;
            font-size: .65rem;
            letter-spacing: .08em;
            text-transform: uppercase
        }

        .s-pendiente {
            background: rgba(224, 165, 53, .1);
            color: var(--warning);
            border: 1px solid rgba(224, 165, 53, .3)
        }

        .s-confirmada {
            background: rgba(76, 175, 125, .1);
            color: var(--success);
            border: 1px solid rgba(76, 175, 125, .3)
        }

        .s-cancelada {
            background: rgba(224, 82, 82, .1);
            color: var(--error);
            border: 1px solid rgba(224, 82, 82, .3)
        }

        .no-hist {
            text-align: center;
            padding: 28px;
            color: var(--muted);
            font-size: .82rem
        }

        @media(max-width:1200px) {
            .layout {
                grid-template-columns: 1fr
            }

            .form-panel {
                position: static
            }
        }

        @media(max-width:768px) {
            .sidebar {
                display: none
            }

            .main {
                margin-left: 0;
                padding: 24px
            }

            .frow {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <p class="ey">Panel Admin</p>
            <h1>LUXE MOTORS <em>MX</em></h1>
        </div>
        <nav class="nav-section">
            <p class="nav-label">Principal</p>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="clientes.php" class="nav-item active"><i class="fas fa-users"></i> Clientes</a>
            <a href="tasaciones.php" class="nav-item"><i class="fas fa-tag"></i> Tasaciones</a>
            <a href="ventas.php" class="nav-item"><i class="fas fa-file-invoice-dollar"></i> Cotizaciones</a>
        </nav>
        <nav class="nav-section" style="margin-top:16px">
            <p class="nav-label">Sitio Público</p>
            <a href="../config.php" class="nav-item" target="_blank"><i class="fas fa-sliders-h"></i> Configurador</a>
            <a href="../compraventa.php" class="nav-item" target="_blank"><i class="fas fa-exchange-alt"></i>
                Compra-Venta</a>
            <a href="../index.php" class="nav-item" target="_blank"><i class="fas fa-external-link-alt"></i> Ver
                Sitio</a>
        </nav>
        <div class="sidebar-footer">
            <div class="admin-badge">
                <div class="admin-avatar"><i class="fas fa-user fa-xs"></i></div>
                <div class="admin-info">
                    <div class="name">Administrador</div>
                    <div class="role">Super Admin</div>
                </div>
            </div>
            <a href="logout.php" class="nav-item danger" style="margin-top:16px">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <!-- ── MAIN ── -->
    <main class="main">

        <div class="topbar">
            <div class="page-title">
                <p class="ey">Administración</p>
                <h2>Clientes</h2>
            </div>
            <div class="topbar-actions">
                <a href="clientes.php?exportar=1" class="btn-export">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </a>
            </div>
        </div>

        <!-- Notificación -->
        <?php if ($mensaje && empty($errores)): ?>
            <div class="notif <?= $tipo_mensaje ?>">
                <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <span>
                    <?= $mensaje ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="layout">

            <!-- ── FORMULARIO ── -->
            <div class="form-panel">
                <div class="fp-head">
                    <span class="ttl">
                        <?= $clienteEdit ? 'Editar' : 'Nuevo' ?> Cliente
                    </span>
                    <span class="mode-badge <?= $clienteEdit ? 'edit' : 'add' ?>">
                        <?= $clienteEdit ? 'Edición' : 'Nuevo' ?>
                    </span>
                </div>
                <div class="fp-body">

                    <!-- Errores de validación -->
                    <?php if (!empty($errores)): ?>
                        <div class="error-box">
                            <ul>
                                <?php foreach ($errores as $err): ?>
                                    <li><i class="fas fa-exclamation-circle fa-xs"></i>
                                        <?= htmlspecialchars($err) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="clienteForm" novalidate>
                        <?php if ($clienteEdit): ?>
                            <input type="hidden" name="id" value="<?= $clienteEdit['id'] ?>">
                            <input type="hidden" name="editar" value="1">
                        <?php else: ?>
                            <input type="hidden" name="agregar" value="1">
                        <?php endif; ?>

                        <!-- Datos personales -->
                        <p class="form-section">Datos personales</p>

                        <div class="frow">
                            <div class="field">
                                <label for="nombre">Nombre *</label>
                                <input type="text" id="nombre" name="nombre"
                                    value="<?= htmlspecialchars($clienteEdit['nombre'] ?? ($_POST['nombre'] ?? '')) ?>"
                                    placeholder="Juan" required>
                            </div>
                            <div class="field">
                                <label for="apellido">Apellido *</label>
                                <input type="text" id="apellido" name="apellido"
                                    value="<?= htmlspecialchars($clienteEdit['apellido'] ?? ($_POST['apellido'] ?? '')) ?>"
                                    placeholder="Pérez" required>
                            </div>
                        </div>

                        <div class="frow-1 field">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email"
                                value="<?= htmlspecialchars($clienteEdit['email'] ?? ($_POST['email'] ?? '')) ?>"
                                placeholder="juan@ejemplo.com" required>
                        </div>

                        <div class="frow">
                            <div class="field">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono"
                                    value="<?= htmlspecialchars($clienteEdit['telefono'] ?? ($_POST['telefono'] ?? '')) ?>"
                                    placeholder="+52 55 1234 5678">
                            </div>
                            <div class="field">
                                <label for="rfc">RFC</label>
                                <input type="text" id="rfc" name="rfc"
                                    value="<?= htmlspecialchars($clienteEdit['rfc'] ?? ($_POST['rfc'] ?? '')) ?>"
                                    placeholder="PEPJ800101H09" maxlength="13"
                                    oninput="this.value=this.value.toUpperCase()">
                                <span class="hint">12 o 13 caracteres · opcional</span>
                            </div>
                        </div>

                        <!-- Ubicación -->
                        <p class="form-section">Dirección</p>

                        <div class="frow-1 field">
                            <label for="direccion">Dirección completa</label>
                            <textarea id="direccion" name="direccion" rows="2"
                                placeholder="Calle, colonia, municipio, CP…"><?= htmlspecialchars($clienteEdit['direccion'] ?? ($_POST['direccion'] ?? '')) ?></textarea>
                        </div>

                        <!-- Notas internas -->
                        <p class="form-section">Notas internas</p>

                        <div class="frow-1 field">
                            <label for="notas">Observaciones del asesor</label>
                            <textarea id="notas" name="notas" rows="3" maxlength="500"
                                placeholder="Preferencias, historial, observaciones importantes…"
                                oninput="document.getElementById('charCount').textContent=this.value.length"><?= htmlspecialchars($clienteEdit['notas'] ?? ($_POST['notas'] ?? '')) ?></textarea>
                            <div class="char-count"><span id="charCount">
                                    <?= strlen($clienteEdit['notas'] ?? '') ?>
                                </span> / 500</div>
                        </div>

                        <button type="submit" class="btn-submit">
                            <span>
                                <?= $clienteEdit ? '✓  Guardar cambios' : '+  Agregar cliente' ?>
                            </span>
                        </button>

                        <?php if ($clienteEdit): ?>
                            <a href="clientes.php" class="btn-cancel">Cancelar edición</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- ── TABLA ── -->
            <div class="table-panel">
                <div class="tp-head">
                    <span class="tp-title">Todos los clientes</span>
                    <span class="count-badge">
                        <?= $totalClientes ?> registros
                    </span>
                </div>

                <div class="search-wrap">
                    <div class="search-inner">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Buscar por nombre, email, RFC o teléfono…"
                            oninput="filtrar()">
                    </div>
                    <div class="sort-pills">
                        <button class="sort-pill active" onclick="sortBy('fecha')" id="pill-fecha">Recientes</button>
                        <button class="sort-pill" onclick="sortBy('nombre')" id="pill-nombre">A–Z</button>
                    </div>
                </div>

                <?php if ($totalClientes > 0): ?>
                    <div style="overflow-x:auto">
                        <table class="data-table" id="clientesTable">
                            <thead>
                                <tr>
                                    <th onclick="sortBy('nombre')" data-col="nombre">
                                        Cliente <i class="fas fa-chevron-down sort-icon"></i>
                                    </th>
                                    <th>Email</th>
                                    <th>RFC</th>
                                    <th>Teléfono</th>
                                    <th onclick="sortBy('fecha')" data-col="fecha">
                                        Registro <i class="fas fa-chevron-down sort-icon"></i>
                                    </th>
                                    <th style="text-align:right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientes as $c): ?>
                                    <tr data-nombre="<?= htmlspecialchars(strtolower($c['nombre'] . ' ' . $c['apellido'])) ?>"
                                        data-email="<?= htmlspecialchars(strtolower($c['email'])) ?>"
                                        data-rfc="<?= htmlspecialchars(strtolower($c['rfc'] ?? '')) ?>"
                                        data-telefono="<?= htmlspecialchars($c['telefono'] ?? '') ?>"
                                        data-fecha="<?= $c['fecha_registro'] ?>" onclick="verPerfil(<?= $c['id'] ?>, event)">
                                        <td>
                                            <div class="client-cell">
                                                <div class="av">
                                                    <?= strtoupper(substr($c['nombre'], 0, 1) . substr($c['apellido'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="cn">
                                                        <?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?>
                                                    </div>
                                                    <div class="cid">#
                                                        <?= str_pad($c['id'], 4, '0', STR_PAD_LEFT) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="em">
                                            <?= htmlspecialchars($c['email']) ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($c['rfc'])): ?>
                                                <span class="rfc-badge">
                                                    <?= htmlspecialchars($c['rfc']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color:#2a2a2a">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="ph">
                                            <?= htmlspecialchars($c['telefono'] ?: '—') ?>
                                        </td>
                                        <td class="dt">
                                            <?= date('d/m/Y', strtotime($c['fecha_registro'])) ?>
                                        </td>
                                        <td>
                                            <div class="action-cell">
                                                <a href="clientes.php?perfil=<?= $c['id'] ?>" class="btn-icon view"
                                                    title="Ver perfil" onclick="event.stopPropagation()">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="clientes.php?edit=<?= $c['id'] ?>" class="btn-icon edit" title="Editar"
                                                    onclick="event.stopPropagation()">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <a href="clientes.php?eliminar=<?= $c['id'] ?>"
                                                    onclick="event.stopPropagation(); return confirm('¿Eliminar a <?= addslashes($c['nombre'] . ' ' . $c['apellido']) ?>? Esta acción no se puede deshacer.')"
                                                    class="btn-icon delete" title="Eliminar">
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
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <p>Ningún cliente registrado aún.<br>Usa el formulario para agregar el primero.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- ── MODAL PERFIL ── -->
    <div class="modal-overlay <?= $perfilData ? 'open' : '' ?>" id="modalOverlay" onclick="cerrarModal(event)">
        <div class="modal" id="modalBox">
            <?php if ($perfilData): ?>
                <div class="modal-head">
                    <span class="ttl">
                        <?= htmlspecialchars($perfilData['nombre'] . ' ' . $perfilData['apellido']) ?>
                    </span>
                    <a href="clientes.php" class="modal-close" title="Cerrar"><i class="fas fa-times"></i></a>
                </div>
                <div class="modal-body">

                    <div class="profile-grid">
                        <div class="profile-field">
                            <div class="lbl">ID</div>
                            <div class="val">#
                                <?= str_pad($perfilData['id'], 4, '0', STR_PAD_LEFT) ?>
                            </div>
                        </div>
                        <div class="profile-field">
                            <div class="lbl">Registro</div>
                            <div class="val">
                                <?= date('d/m/Y H:i', strtotime($perfilData['fecha_registro'])) ?>
                            </div>
                        </div>
                        <div class="profile-field">
                            <div class="lbl">Email</div>
                            <div class="val">
                                <?= htmlspecialchars($perfilData['email']) ?>
                            </div>
                        </div>
                        <div class="profile-field">
                            <div class="lbl">Teléfono</div>
                            <div class="val <?= empty($perfilData['telefono']) ? 'empty' : '' ?>">
                                <?= htmlspecialchars($perfilData['telefono'] ?: 'No registrado') ?>
                            </div>
                        </div>
                        <div class="profile-field">
                            <div class="lbl">RFC</div>
                            <div class="val <?= empty($perfilData['rfc']) ? 'empty' : '' ?>">
                                <?php if (!empty($perfilData['rfc'])): ?>
                                    <span class="rfc-badge">
                                        <?= htmlspecialchars($perfilData['rfc']) ?>
                                    </span>
                                <?php else: ?> No registrado
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="profile-field full">
                            <div class="lbl">Dirección</div>
                            <div class="val <?= empty($perfilData['direccion']) ? 'empty' : '' ?>">
                                <?= htmlspecialchars($perfilData['direccion'] ?: 'No registrada') ?>
                            </div>
                        </div>
                        <?php if (!empty($perfilData['notas'])): ?>
                            <div class="profile-field full">
                                <div class="lbl">Notas internas</div>
                                <div class="val"
                                    style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:12px;font-size:.85rem;line-height:1.7;color:var(--muted)">
                                    <?= nl2br(htmlspecialchars($perfilData['notas'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Historial de cotizaciones -->
                    <p class="modal-section-title">Historial de cotizaciones</p>

                    <?php if (!empty($perfilVentas)): ?>
                        <table class="hist-table">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Modelo</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($perfilVentas as $v): ?>
                                    <tr>
                                        <td style="font-size:.73rem;color:var(--muted)">
                                            <?= htmlspecialchars($v['no_factura']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($v['modelo'] ?? 'N/A') ?>
                                        </td>
                                        <td style="color:var(--gold)">$
                                            <?= number_format($v['total_a_pagar'], 0, '.', ',') ?>
                                        </td>
                                        <td>
                                            <span class="status-badge s-<?= $v['status'] ?>">
                                                <?= $v['status'] ?>
                                            </span>
                                        </td>
                                        <td style="font-size:.78rem;color:var(--muted)">
                                            <?= date('d/m/Y', strtotime($v['fecha_venta'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-hist">
                            <i class="fas fa-file-invoice"
                                style="font-size:1.4rem;margin-bottom:8px;display:block;color:#222"></i>
                            Este cliente no tiene cotizaciones registradas aún.
                        </div>
                    <?php endif; ?>

                    <div style="display:flex;gap:10px;margin-top:24px">
                        <a href="clientes.php?edit=<?= $perfilData['id'] ?>"
                            style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border:1px solid var(--gold-dim);border-radius:var(--radius);color:var(--gold);text-decoration:none;font-size:10px;letter-spacing:.25em;text-transform:uppercase;transition:all .3s">
                            <i class="fas fa-pen fa-xs"></i> Editar cliente
                        </a>
                        <a href="clientes.php"
                            style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border:1px solid var(--border);border-radius:var(--radius);color:var(--muted);text-decoration:none;font-size:10px;letter-spacing:.2em;text-transform:uppercase;transition:all .3s">
                            Cerrar
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        /* ── FILTRO DE BÚSQUEDA ── */
        function filtrar() {
            const q = document.getElementById('searchInput').value.toLowerCase().trim();
            document.querySelectorAll('#clientesTable tbody tr').forEach(row => {
                const texto = (row.dataset.nombre + row.dataset.email + row.dataset.rfc + row.dataset.telefono).toLowerCase();
                row.style.display = texto.includes(q) ? '' : 'none';
            });
        }

        /* ── ORDENAMIENTO ── */
        let sortDir = { nombre: 1, fecha: -1 };
        let currentSort = 'fecha';

        function sortBy(col) {
            currentSort = col;
            sortDir[col] *= -1;

            document.querySelectorAll('.sort-pill').forEach(p => p.classList.remove('active'));
            const pill = document.getElementById('pill-' + col);
            if (pill) pill.classList.add('active');

            const tbody = document.querySelector('#clientesTable tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort((a, b) => {
                let va = col === 'nombre' ? a.dataset.nombre : a.dataset.fecha;
                let vb = col === 'nombre' ? b.dataset.nombre : b.dataset.fecha;
                return va < vb ? -sortDir[col] : va > vb ? sortDir[col] : 0;
            });

            rows.forEach(r => tbody.appendChild(r));
        }

        /* ── MODAL PERFIL ── */
        function verPerfil(id, e) {
            if (e && e.target.closest('.action-cell')) return;
            window.location.href = 'clientes.php?perfil=' + id;
        }

        function cerrarModal(e) {
            if (e.target === document.getElementById('modalOverlay')) {
                window.location.href = 'clientes.php';
            }
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && document.getElementById('modalOverlay').classList.contains('open')) {
                window.location.href = 'clientes.php';
            }
        });

        /* ── VALIDACIÓN CLIENTE JS ── */
        document.getElementById('clienteForm').addEventListener('submit', function (e) {
            const nombre = document.getElementById('nombre').value.trim();
            const apellido = document.getElementById('apellido').value.trim();
            const email = document.getElementById('email').value.trim();
            const rfc = document.getElementById('rfc').value.trim();
            let ok = true;

            [nombre, apellido, email].forEach((v, i) => {
                const fields = ['nombre', 'apellido', 'email'];
                document.getElementById(fields[i]).classList.toggle('invalid', !v);
                if (!v) ok = false;
            });

            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('email').classList.add('invalid');
                ok = false;
            }

            if (rfc && !/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/.test(rfc)) {
                document.getElementById('rfc').classList.add('invalid');
                ok = false;
            }

            if (!ok) e.preventDefault();
        });
    </script>

</body>

</html>