<?php
include 'config/db.php';
$precio_total = isset($_GET['precio']) ? floatval($_GET['precio']) : 0;
$auto_id = isset($_GET['auto']) ? intval($_GET['auto']) : 0;
$auto = null;
if ($auto_id > 0) {
    $stmt = $pdo->prepare("SELECT modelo FROM autos WHERE id = ?");
    $stmt->execute([$auto_id]);
    $auto = $stmt->fetch(PDO::FETCH_ASSOC);
}
$modelo_auto = $auto ? $auto['modelo'] : 'Auto configurado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Financiamiento — LUXE MOTORS MX</title>
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

        .navbar { background: rgba(7,7,7,0.96); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); padding: 16px 0; }
        .navbar-brand { font-family: 'Cormorant Garamond', serif; font-weight: 300; font-size: 1.4rem; letter-spacing: 0.05em; color: var(--text) !important; }
        .navbar-brand em { font-style: normal; color: var(--gold); }
        .nav-link { color: #666 !important; font-size: 0.85rem; font-weight: 400; transition: color 0.25s; }
        .nav-link:hover { color: var(--text) !important; }
        .nav-link.special { color: var(--gold) !important; }

        .page-header {
            text-align: center;
            padding: 72px 24px 56px;
        }

        .page-header .eyebrow {
            font-size: 10px;
            letter-spacing: 0.45em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 12px;
        }

        .page-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(2.2rem, 4vw, 3.5rem);
            line-height: 1;
        }

        .plan-container { max-width: 1000px; margin: 0 auto; padding: 0 24px 80px; }

        /* Main card */
        .plan-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .plan-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .plan-section {
            padding: 40px;
        }

        .plan-section + .plan-section {
            border-left: 1px solid var(--border);
        }

        .section-title {
            font-size: 9px;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 24px;
        }

        .vehicle-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.8rem;
            font-weight: 300;
            margin-bottom: 4px;
        }

        .vehicle-price {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.4rem;
            font-weight: 600;
            color: var(--gold);
            line-height: 1;
            margin-bottom: 32px;
        }

        /* Input groups */
        .input-group-custom {
            margin-bottom: 20px;
        }

        .input-group-custom label {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .input-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
        }

        .input-styled {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 11px 14px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.3s;
            width: 100%;
            -webkit-appearance: none;
        }

        .input-styled:focus { border-color: var(--gold-dim); }

        select.input-styled {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23555' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            cursor: pointer;
        }

        /* KPI mini */
        .kpi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        .kpi-mini {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            text-align: center;
        }

        .kpi-mini .label {
            font-size: 9px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .kpi-mini .value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gold);
        }

        .total-block {
            background: rgba(201,169,110,0.06);
            border: 1px solid var(--gold-dim);
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
        }

        .total-block .label {
            font-size: 10px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .total-block .value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.8rem;
            font-weight: 600;
            color: var(--gold);
            line-height: 1;
        }

        /* Amortization table */
        .amort-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .amort-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .amort-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .amort-table th {
            padding: 10px 20px;
            text-align: right;
            font-size: 9px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 400;
            border-bottom: 1px solid var(--border);
        }

        .amort-table th:first-child { text-align: center; }

        .amort-table td {
            padding: 10px 20px;
            text-align: right;
            border-bottom: 1px solid rgba(28,28,28,0.6);
            color: var(--text);
        }

        .amort-table td:first-child { text-align: center; color: var(--muted); }
        .amort-table tr:last-child td { border-bottom: none; }
        .amort-table tbody tr:hover td { background: var(--s2); }

        .amort-table .td-capital { color: var(--text); }
        .amort-table .td-interes  { color: #888; }
        .amort-table .td-saldo    { color: var(--muted); }
        .amort-table .td-mensual  { color: var(--gold); font-weight: 500; }

        .btn-cta {
            width: 100%;
            padding: 14px;
            background: transparent;
            border: 1px solid var(--gold-dim);
            border-radius: var(--radius);
            color: var(--gold);
            font-family: 'DM Sans', sans-serif;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.35s;
            position: relative;
            overflow: hidden;
        }

        .btn-cta::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gold);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
        }

        .btn-cta span { position: relative; z-index: 1; }
        .btn-cta:hover::before { transform: scaleX(1); }
        .btn-cta:hover { color: var(--bg); }

        @media (max-width: 768px) {
            .plan-grid { grid-template-columns: 1fr; }
            .plan-section + .plan-section { border-left: none; border-top: 1px solid var(--border); }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page-header">
    <p class="eyebrow">Opciones de financiamiento</p>
    <h1>Plan de Pagos</h1>
</div>

<div class="plan-container">

    <div class="plan-card">
        <div class="plan-grid">

            <!-- Left: inputs -->
            <div class="plan-section">
                <p class="section-title">Parámetros del crédito</p>

                <div class="vehicle-name"><?= htmlspecialchars($modelo_auto) ?></div>
                <div class="vehicle-price">$<?= number_format($precio_total, 0, '.', ',') ?></div>

                <div class="input-group-custom">
                    <label>Enganche</label>
                    <div class="input-row">
                        <select class="input-styled" id="porcentaje_enganche" onchange="sincronizarEnganche()">
                            <option value="0">0%</option>
                            <option value="10" selected>10%</option>
                            <option value="20">20%</option>
                            <option value="30">30%</option>
                        </select>
                        <input type="number" class="input-styled" id="enganche_manual"
                               style="max-width:140px;"
                               placeholder="Monto"
                               oninput="calcularPlan()">
                    </div>
                </div>

                <div class="input-group-custom">
                    <label>Tasa mensual</label>
                    <div class="input-row">
                        <input type="number" class="input-styled" id="tasa_mensual" value="1.5" step="0.1" min="0.1" max="5" oninput="calcularPlan()">
                        <span style="display:flex;align-items:center;padding:0 14px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);color:var(--muted);font-size:0.8rem;">%</span>
                    </div>
                </div>

                <div class="input-group-custom">
                    <label>Plazo (meses)</label>
                    <select class="input-styled" id="plazo_meses" onchange="calcularPlan()">
                        <option value="12">12 meses</option>
                        <option value="24" selected>24 meses</option>
                        <option value="36">36 meses</option>
                        <option value="48">48 meses</option>
                        <option value="60">60 meses</option>
                    </select>
                </div>
            </div>

            <!-- Right: results -->
            <div class="plan-section">
                <p class="section-title">Resumen del crédito</p>

                <div class="kpi-grid">
                    <div class="kpi-mini">
                        <div class="label">Enganche</div>
                        <div class="value" id="res_enganche">$0</div>
                    </div>
                    <div class="kpi-mini">
                        <div class="label">Financiado</div>
                        <div class="value" id="res_financiado">$0</div>
                    </div>
                    <div class="kpi-mini">
                        <div class="label">Pago Mensual</div>
                        <div class="value" id="res_mensual">$0</div>
                    </div>
                    <div class="kpi-mini">
                        <div class="label">Total Intereses</div>
                        <div class="value" id="res_intereses">$0</div>
                    </div>
                </div>

                <div class="total-block">
                    <div class="label">Total a pagar</div>
                    <div class="value" id="res_total_pagar">$0</div>
                </div>

                <button class="btn-cta" onclick="generarFactura()">
                    <span>Generar factura final →</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Amortization table -->
    <div class="amort-card">
        <div class="amort-header">Tabla de amortización</div>
        <div id="tablaAmortizacion" style="overflow-x:auto;"></div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const precioTotal = <?= json_encode($precio_total) ?>;
const autoId      = <?= json_encode($auto_id) ?>;

function fmt(n) { return '$' + Math.round(n).toLocaleString('es-MX'); }

function sincronizarEnganche() {
    const pct = parseFloat(document.getElementById('porcentaje_enganche').value) || 0;
    document.getElementById('enganche_manual').value = (precioTotal * pct / 100).toFixed(0);
    calcularPlan();
}

function calcularPlan() {
    const enganche = parseFloat(document.getElementById('enganche_manual').value) || 0;
    const tasa     = parseFloat(document.getElementById('tasa_mensual').value)   || 1.5;
    const plazo    = parseInt(document.getElementById('plazo_meses').value)      || 24;

    const financiado = Math.max(0, precioTotal - enganche);
    const tasaMes    = tasa / 100;

    let mensual = 0;
    if (tasaMes > 0 && financiado > 0) {
        mensual = financiado * (tasaMes * Math.pow(1 + tasaMes, plazo)) / (Math.pow(1 + tasaMes, plazo) - 1);
    } else {
        mensual = financiado / plazo;
    }

    const totalPagar   = enganche + mensual * plazo;
    const totalInteres = totalPagar - precioTotal;

    document.getElementById('res_enganche').textContent   = fmt(enganche);
    document.getElementById('res_financiado').textContent = fmt(financiado);
    document.getElementById('res_mensual').textContent    = fmt(mensual);
    document.getElementById('res_intereses').textContent  = fmt(Math.max(0, totalInteres));
    document.getElementById('res_total_pagar').textContent = fmt(totalPagar);

    // Amortization table
    let saldo = financiado;
    let html = `
        <table class="amort-table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Capital</th>
                    <th>Interés</th>
                    <th>Cuota</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>`;

    for (let i = 1; i <= plazo; i++) {
        const interes = saldo * tasaMes;
        const capital = mensual - interes;
        saldo = Math.max(0, saldo - capital);

        html += `<tr>
            <td>${i}</td>
            <td class="td-capital">${fmt(capital)}</td>
            <td class="td-interes">${fmt(interes)}</td>
            <td class="td-mensual">${fmt(mensual)}</td>
            <td class="td-saldo">${fmt(saldo)}</td>
        </tr>`;
    }

    html += '</tbody></table>';
    document.getElementById('tablaAmortizacion').innerHTML = html;
}

function generarFactura() {
    const enganche   = parseFloat(document.getElementById('enganche_manual').value) || 0;
    const financiado = Math.max(0, precioTotal - enganche);
    const tasa       = parseFloat(document.getElementById('tasa_mensual').value) || 1.5;
    const plazo      = parseInt(document.getElementById('plazo_meses').value) || 24;
    const mensual    = parseFloat(document.getElementById('res_mensual').textContent.replace(/[^0-9.]/g,'')) || 0;
    const intereses  = parseFloat(document.getElementById('res_intereses').textContent.replace(/[^0-9.]/g,'')) || 0;
    const total      = parseFloat(document.getElementById('res_total_pagar').textContent.replace(/[^0-9.]/g,'')) || 0;

    window.location.href = `factura.php?precio=${precioTotal}&auto=${autoId}&enganche=${enganche}&financiado=${financiado}&tasa=${tasa}&plazo=${plazo}&mensual=${mensual}&intereses=${intereses}&total=${total}`;
}

// Init
window.onload = () => {
    sincronizarEnganche();
};
</script>
</body>
</html>