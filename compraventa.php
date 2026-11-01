<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include 'config/db.php';

$exito = false;
$errores = [];
$oferta = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validación
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $anio = intval($_POST['anio'] ?? 0);
    $kilometraje = intval($_POST['kilometraje'] ?? 0);
    $condicion = $_POST['condicion'] ?? '';
    $color = trim($_POST['color'] ?? '');
    $num_duenos = intval($_POST['num_duenos'] ?? 1);
    $tiene_factura = isset($_POST['tiene_factura']) ? 1 : 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $notas = trim($_POST['notas'] ?? '');

    if (!$marca)
        $errores[] = "La marca es obligatoria.";
    if (!$modelo)
        $errores[] = "El modelo es obligatorio.";
    if ($anio < 2000 || $anio > date('Y'))
        $errores[] = "Año inválido.";
    if ($kilometraje < 0)
        $errores[] = "Kilometraje inválido.";
    if (!in_array($condicion, ['excelente', 'bueno', 'regular', 'deficiente']))
        $errores[] = "Selecciona la condición.";
    if (!$nombre)
        $errores[] = "El nombre es obligatorio.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errores[] = "Email inválido.";

    if (empty($errores)) {
        // ── Algoritmo de valuación ──
        $anioActual = (int) date('Y');
        $antiguedad = $anioActual - $anio;

        // Base: promedio ponderado del catálogo LUXE MOTORS MX
        // (Ferrari, Lamborghini, Porsche, Bentley, Rolls-Royce, etc.)
        $baseValor = 3_500_000;  // MXN

        // Factor antigüedad: los autos de lujo deprecian ~10% anual
        // con un piso del 35% (coleccionables y exóticos retienen valor)
        $factorAnio = max(0.35, 1 - ($antiguedad * 0.10));

        // Factor kilometraje: umbrales propios del segmento premium
        // (un superdeportivo con 40 000 km ya es "alto uso")
        $factorKm = 1.0;
        if ($kilometraje < 15000)
            $factorKm = 1.08;  // prácticamente nuevo
        elseif ($kilometraje < 40000)
            $factorKm = 0.96;  // uso bajo
        elseif ($kilometraje < 80000)
            $factorKm = 0.85;  // uso moderado
        elseif ($kilometraje < 120000)
            $factorKm = 0.72;  // uso alto
        else
            $factorKm = 0.58;  // kilometraje muy elevado

        // Factor condición: pesa más en autos de alta gama
        $factoresCondicion = [
            'excelente' => 1.12,
            'bueno' => 1.00,
            'regular' => 0.78,
            'deficiente' => 0.55,
        ];
        $factorCond = $factoresCondicion[$condicion];

        // Factor dueños: cada propietario impacta más en exclusivos
        $factorDuenos = max(0.80, 1 - (($num_duenos - 1) * 0.09));

        // Factor factura: crítico en el segmento de lujo
        $factorFactura = $tiene_factura ? 1.0 : 0.82;

        $oferta = round($baseValor * $factorAnio * $factorKm * $factorCond * $factorDuenos * $factorFactura, -3);
        $oferta = max(500000, $oferta); // mínimo $500,000 MXN

        // Guardar en BD
        $stmt = $pdo->prepare("
            INSERT INTO tasaciones
                (marca, modelo, anio, kilometraje, condicion, color, num_duenos, tiene_factura, nombre_vendedor, email, telefono, valor_oferta, notas_vendedor)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$marca, $modelo, $anio, $kilometraje, $condicion, $color, $num_duenos, $tiene_factura, $nombre, $email, $telefono, $oferta, $notas]);

        $exito = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compra-Venta — LUXE MOTORS MX</title>
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
            --success: #4caf7d;
            --error: #e05252;
            --radius: 6px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 300;
        }

        /* PAGE HEADER */
        .page-header {
            padding: 80px 24px 56px;
            text-align: center;
            position: relative;
        }

        .page-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 50% 80% at 50% 0%, rgba(201, 169, 110, 0.05) 0%, transparent 70%);
            pointer-events: none;
        }

        .page-header .eyebrow {
            font-size: 10px;
            letter-spacing: 0.45em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
        }

        .page-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(2.4rem, 5vw, 4rem);
            line-height: 1;
            margin-bottom: 16px;
        }

        .page-header p {
            color: var(--muted);
            font-size: 0.95rem;
        }

        .main-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px 100px;
        }

        /* HOW IT WORKS */
        .steps-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 56px;
        }

        .step {
            background: var(--surface);
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .step-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            font-weight: 600;
            color: rgba(201, 169, 110, 0.25);
            line-height: 1;
        }

        .step-title {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .step-text {
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.6;
        }

        /* LAYOUT */
        .form-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
            align-items: start;
        }

        /* FORM CARD */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .form-card-header {
            padding: 20px 28px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-card-header .title {
            font-size: 10px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .form-card-body {
            padding: 32px 28px;
        }

        /* ERRORS */
        .error-list {
            background: rgba(224, 82, 82, 0.06);
            border: 1px solid rgba(224, 82, 82, 0.25);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 28px;
        }

        .error-list p {
            font-size: 0.82rem;
            color: var(--error);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* FORM ELEMENTS */
        .form-section-title {
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .field-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .field-full {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: 9px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 7px;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 11px 14px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 300;
            outline: none;
            transition: border-color 0.3s;
            resize: vertical;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: var(--gold-dim);
        }

        .field select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23555' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            cursor: pointer;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--gold);
            flex-shrink: 0;
        }

        .checkbox-group label {
            font-size: 0.85rem;
            color: var(--text);
            cursor: pointer;
        }

        .form-divider {
            height: 1px;
            background: var(--border);
            margin: 28px 0;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: transparent;
            border: 1px solid var(--gold-dim);
            border-radius: var(--radius);
            color: var(--gold);
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.35s;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gold);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-submit span {
            position: relative;
            z-index: 1;
        }

        .btn-submit:hover::before {
            transform: scaleX(1);
        }

        .btn-submit:hover {
            color: var(--bg);
        }

        /* SIDEBAR CARDS */
        .side-col {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .side-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
        }

        .side-card-title {
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 16px;
        }

        .side-card p {
            font-size: 0.85rem;
            color: var(--muted);
            line-height: 1.7;
            margin-bottom: 8px;
        }

        .benefit-list {
            list-style: none;
        }

        .benefit-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.85rem;
            color: var(--muted);
            padding: 10px 0;
            border-bottom: 1px solid rgba(28, 28, 28, 0.6);
        }

        .benefit-list li:last-child {
            border-bottom: none;
        }

        .benefit-list i {
            color: var(--gold);
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* SUCCESS PANEL */
        .success-panel {
            text-align: center;
            padding: 64px 32px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(76, 175, 125, 0.1);
            border: 1px solid rgba(76, 175, 125, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--success);
            font-size: 1.8rem;
            margin: 0 auto 28px;
        }

        .success-panel h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .offer-amount {
            font-family: 'Cormorant Garamond', serif;
            font-size: 3.5rem;
            font-weight: 600;
            color: var(--gold);
            line-height: 1;
            margin: 28px 0;
        }

        .offer-disclaimer {
            font-size: 0.78rem;
            color: var(--muted);
            margin-bottom: 32px;
            line-height: 1.7;
        }

        .success-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-gold {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: var(--gold);
            color: var(--bg);
            text-decoration: none;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-gold:hover {
            background: #d4b47e;
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: transparent;
            color: var(--muted);
            text-decoration: none;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 400;
        }

        .btn-ghost:hover {
            color: var(--text);
            border-color: #333;
        }

        @media (max-width: 900px) {
            .form-layout {
                grid-template-columns: 1fr;
            }

            .steps-bar {
                grid-template-columns: 1fr 1fr;
            }

            .field-row,
            .field-row-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="page-header">
        <p class="eyebrow">Valuación gratuita</p>
        <h1>Vende tu auto<br>al mejor precio</h1>
        <p>Oferta en efectivo en minutos. Sin compromisos, sin letras pequeñas.</p>
    </div>

    <div class="main-wrap">

        <!-- STEPS -->
        <div class="steps-bar">
            <div class="step">
                <div class="step-num">01</div>
                <div class="step-title">Ingresa los datos</div>
                <div class="step-text">Marca, modelo, año y kilometraje de tu vehículo.</div>
            </div>
            <div class="step">
                <div class="step-num">02</div>
                <div class="step-title">Recibe tu valuación</div>
                <div class="step-text">Nuestro algoritmo calcula el valor de mercado al instante.</div>
            </div>
            <div class="step">
                <div class="step-num">03</div>
                <div class="step-title">Revisamos el auto</div>
                <div class="step-text">Un asesor te contacta para confirmar la oferta.</div>
            </div>
            <div class="step">
                <div class="step-num">04</div>
                <div class="step-title">Cobras en efectivo</div>
                <div class="step-text">Depósito o transferencia el mismo día de la entrega.</div>
            </div>
        </div>

        <?php if ($exito): ?>
            <!-- SUCCESS -->
            <div class="success-panel">
                <div class="success-icon"><i class="fas fa-check"></i></div>
                <h2>¡Valuación completada!</h2>
                <p style="color:var(--muted); font-size:0.9rem;">Nuestra oferta por tu <strong style="color:var(--text)">
                        <?= htmlspecialchars($_POST['marca'] . ' ' . $_POST['modelo']) ?>
                    </strong></p>

                <div class="offer-amount">$
                    <?= number_format($oferta, 0, '.', ',') ?>
                </div>

                <p class="offer-disclaimer">
                    Esta oferta está basada en los datos proporcionados y es válida por <strong style="color:var(--text)">72
                        horas</strong>.<br>
                    Un asesor de LUXE MOTORS MX se comunicará al <strong style="color:var(--text)">
                        <?= htmlspecialchars($_POST['telefono'] ?? $_POST['email']) ?>
                    </strong> para agendar la inspección física.<br>
                    El monto final puede ajustarse tras la revisión presencial.
                </p>

                <div class="success-actions">
                    <a href="index.php" class="btn-gold"><i class="fas fa-home fa-xs"></i> Ir al Inicio</a>
                    <a href="config.php" class="btn-ghost"><i class="fas fa-sliders-h fa-xs"></i> Ver autos nuevos</a>
                    <a href="compraventa.php" class="btn-ghost">Nueva valuación</a>
                </div>
            </div>

        <?php else: ?>
            <!-- FORM -->
            <div class="form-layout">
                <div>
                    <div class="form-card">
                        <div class="form-card-header">
                            <span class="title">Formulario de valuación</span>
                            <span style="font-size:0.75rem;color:var(--muted);">Campos obligatorios*</span>
                        </div>
                        <div class="form-card-body">

                            <?php if (!empty($errores)): ?>
                                <div class="error-list">
                                    <?php foreach ($errores as $e): ?>
                                        <p><i class="fas fa-exclamation-circle fa-xs"></i>
                                            <?= htmlspecialchars($e) ?>
                                        </p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <p class="form-section-title">Datos del vehículo</p>

                                <div class="field-row">
                                    <div class="field">
                                        <label>Marca *</label>
                                        <input type="text" name="marca" placeholder="Ferrari, Lamborghini, Porsche…"
                                            value="<?= htmlspecialchars($_POST['marca'] ?? '') ?>" required>
                                    </div>
                                    <div class="field">
                                        <label>Modelo *</label>
                                        <input type="text" name="modelo" placeholder="488 Pista, Huracán EVO, 911 Turbo S…"
                                            value="<?= htmlspecialchars($_POST['modelo'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <div class="field-row">
                                    <div class="field">
                                        <label>Año *</label>
                                        <input type="number" name="anio" placeholder="<?= date('Y') ?>" min="2000"
                                            max="<?= date('Y') ?>" value="<?= htmlspecialchars($_POST['anio'] ?? '') ?>"
                                            required>
                                    </div>
                                    <div class="field">
                                        <label>Kilometraje *</label>
                                        <input type="number" name="kilometraje" placeholder="15000" min="0"
                                            value="<?= htmlspecialchars($_POST['kilometraje'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <div class="field-row">
                                    <div class="field">
                                        <label>Condición general *</label>
                                        <select name="condicion" required>
                                            <option value="">Seleccionar…</option>
                                            <option value="excelente" <?= ($_POST['condicion'] ?? '') === 'excelente' ? 'selected' : '' ?>>Excelente — Como nuevo</option>
                                            <option value="bueno" <?= ($_POST['condicion'] ?? '') === 'bueno' ? 'selected' : '' ?>>Bueno — Detalles menores</option>
                                            <option value="regular" <?= ($_POST['condicion'] ?? '') === 'regular' ? 'selected' : '' ?>>Regular — Necesita atención</option>
                                            <option value="deficiente" <?= ($_POST['condicion'] ?? '') === 'deficiente' ? 'selected' : '' ?>>Deficiente — Daños mayores</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Color</label>
                                        <input type="text" name="color" placeholder="Blanco, Negro, Rojo…"
                                            value="<?= htmlspecialchars($_POST['color'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="field-row">
                                    <div class="field">
                                        <label>Número de dueños anteriores</label>
                                        <select name="num_duenos">
                                            <option value="1" <?= ($_POST['num_duenos'] ?? 1) == 1 ? 'selected' : '' ?>>1 —
                                                Primer dueño</option>
                                            <option value="2" <?= ($_POST['num_duenos'] ?? 1) == 2 ? 'selected' : '' ?>>2 —
                                                Segundo dueño</option>
                                            <option value="3" <?= ($_POST['num_duenos'] ?? 1) == 3 ? 'selected' : '' ?>>3 o más
                                                dueños</option>
                                        </select>
                                    </div>
                                    <div class="field" style="display:flex;align-items:flex-end;">
                                        <div class="checkbox-group" style="margin-bottom:0;">
                                            <input type="checkbox" name="tiene_factura" id="factura"
                                                <?= isset($_POST['tiene_factura']) ? 'checked' : '' ?>>
                                            <label for="factura">Cuenta con factura original</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-divider"></div>
                                <p class="form-section-title">Tus datos de contacto</p>

                                <div class="field-row">
                                    <div class="field">
                                        <label>Nombre completo *</label>
                                        <input type="text" name="nombre" placeholder="Juan Pérez"
                                            value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                                    </div>
                                    <div class="field">
                                        <label>Teléfono</label>
                                        <input type="tel" name="telefono" placeholder="+52 55 1234 5678"
                                            value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="field field-full">
                                    <label>Email *</label>
                                    <input type="email" name="email" placeholder="juan@ejemplo.com"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                </div>

                                <div class="field field-full">
                                    <label>Comentarios adicionales</label>
                                    <textarea name="notas" rows="3"
                                        placeholder="Accesorios, historial de servicio, detalles a mencionar…"><?= htmlspecialchars($_POST['notas'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" class="btn-submit">
                                    <span><i class="fas fa-calculator" style="margin-right:8px;"></i>Calcular
                                        valuación</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- SIDEBAR -->
                <div class="side-col">
                    <div class="side-card">
                        <p class="side-card-title">¿Qué evaluamos?</p>
                        <ul class="benefit-list">
                            <li><i class="fas fa-calendar-alt fa-xs"></i> Año de fabricación</li>
                            <li><i class="fas fa-tachometer-alt fa-xs"></i> Kilometraje acumulado</li>
                            <li><i class="fas fa-star fa-xs"></i> Condición general</li>
                            <li><i class="fas fa-users fa-xs"></i> Historial de propietarios</li>
                            <li><i class="fas fa-file-invoice fa-xs"></i> Documentación completa</li>
                        </ul>
                    </div>

                    <div class="side-card">
                        <p class="side-card-title">Garantías LUXE MOTORS</p>
                        <ul class="benefit-list">
                            <li><i class="fas fa-check-circle fa-xs"></i> Oferta en tiempo real — sin esperas</li>
                            <li><i class="fas fa-check-circle fa-xs"></i> Sin intermediarios ni comisiones ocultas</li>
                            <li><i class="fas fa-check-circle fa-xs"></i> Pago el mismo día de la entrega</li>
                            <li><i class="fas fa-check-circle fa-xs"></i> Asesor personal asignado a tu caso</li>
                            <li><i class="fas fa-check-circle fa-xs"></i> Proceso 100% transparente</li>
                        </ul>
                    </div>

                    <div class="side-card" style="text-align:center;">
                        <p class="side-card-title">¿Prefierres comprar?</p>
                        <p>Tenemos más de
                            <?php echo $pdo->query("SELECT COUNT(*) FROM autos")->fetchColumn(); ?> modelos listos para
                            configurar y financiar.
                        </p>
                        <a href="config.php"
                            style="display:inline-block;margin-top:16px;padding:11px 24px;border:1px solid var(--gold-dim);color:var(--gold);text-decoration:none;font-size:10px;letter-spacing:0.2em;text-transform:uppercase;border-radius:var(--radius);transition:all 0.3s;">
                            Ver catálogo →
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>