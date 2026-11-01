<?php
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$mensaje = '';
$error = false;
if (isset($_POST['login'])) {
    $user = trim($_POST['user']);
    $pass = trim($_POST['pass']);
    if ($user === 'admin' && $pass === '1234') {
        $_SESSION['admin'] = true;
        $_SESSION['admin_user'] = $user;
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        header('Location: dashboard.php');
        exit;
    } else {
        $mensaje = 'Credenciales incorrectas. Verifica tu usuario y contraseña.';
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — LUXE MOTORS MX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg:        #080808;
            --surface:   #101010;
            --border:    #1e1e1e;
            --gold:      #C9A96E;
            --gold-dim:  #8a7248;
            --text:      #EDEDED;
            --muted:     #5a5a5a;
            --error:     #e05252;
            --radius:    4px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 300;
            height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
        }

        /* Left panel — brand visual */
        .brand-panel {
            position: relative;
            background: linear-gradient(160deg, #0d0d0d 0%, #050505 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 56px 64px;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 30% 70%, rgba(201,169,110,0.08) 0%, transparent 70%),
                radial-gradient(ellipse 40% 40% at 80% 20%, rgba(201,169,110,0.04) 0%, transparent 60%);
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold-dim), transparent);
        }

        .brand-lines {
            position: absolute;
            inset: 0;
            overflow: hidden;
            opacity: 0.06;
        }

        .brand-lines span {
            position: absolute;
            width: 1px;
            background: linear-gradient(to bottom, transparent, var(--gold), transparent);
            animation: lineFloat 8s ease-in-out infinite;
        }

        .brand-lines span:nth-child(1) { height: 300px; left: 15%; top: 10%; animation-delay: 0s; }
        .brand-lines span:nth-child(2) { height: 200px; left: 40%; top: 40%; animation-delay: 2s; }
        .brand-lines span:nth-child(3) { height: 400px; left: 70%; top: 5%; animation-delay: 4s; }
        .brand-lines span:nth-child(4) { height: 150px; left: 85%; top: 60%; animation-delay: 1s; }

        @keyframes lineFloat {
            0%, 100% { transform: translateY(0); opacity: 0.4; }
            50% { transform: translateY(-20px); opacity: 1; }
        }

        .brand-logo {
            position: relative;
            z-index: 1;
        }

        .brand-logo .label {
            font-size: 10px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 12px;
        }

        .brand-logo h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(2rem, 3vw, 2.8rem);
            line-height: 1.1;
            letter-spacing: 0.05em;
        }

        .brand-logo h1 em {
            font-style: normal;
            color: var(--gold);
        }

        .brand-center {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .brand-emblem {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 1px solid rgba(201,169,110,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
            position: relative;
        }

        .brand-emblem::before {
            content: '';
            position: absolute;
            inset: 6px;
            border-radius: 50%;
            border: 1px solid rgba(201,169,110,0.15);
        }

        .brand-emblem i {
            font-size: 2.5rem;
            color: var(--gold);
        }

        .brand-tagline {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(1.6rem, 2.5vw, 2.2rem);
            font-weight: 300;
            font-style: italic;
            color: var(--text);
            line-height: 1.4;
        }

        .brand-tagline em {
            font-style: normal;
            color: var(--gold);
        }

        .brand-footer {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 32px;
        }

        .brand-stat {
            text-align: left;
        }

        .brand-stat .num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--gold);
            display: block;
        }

        .brand-stat .label {
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
        }

        /* Right panel — login form */
        .login-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 64px 72px;
            background: var(--surface);
            border-left: 1px solid var(--border);
            animation: panelIn 0.8s ease forwards;
        }

        @keyframes panelIn {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .form-header {
            margin-bottom: 56px;
        }

        .form-header .eyebrow {
            font-size: 10px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 12px;
        }

        .form-header h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 2.6rem;
            line-height: 1.1;
        }

        .form-header p {
            color: var(--muted);
            font-size: 0.875rem;
            margin-top: 8px;
        }

        .field-group {
            margin-bottom: 28px;
        }

        .field-group label {
            display: block;
            font-size: 10px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .field-wrap {
            position: relative;
        }

        .field-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 0.8rem;
            transition: color 0.3s;
        }

        .field-wrap input {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 16px 14px 42px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            outline: none;
        }

        .field-wrap input:focus {
            border-color: var(--gold-dim);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.08);
        }

        .field-wrap input:focus + i,
        .field-wrap:has(input:focus) i {
            color: var(--gold);
        }

        .error-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(224,82,82,0.08);
            border: 1px solid rgba(224,82,82,0.3);
            border-radius: var(--radius);
            padding: 12px 16px;
            margin-bottom: 28px;
            font-size: 0.85rem;
            color: var(--error);
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: transparent;
            border: 1px solid var(--gold-dim);
            border-radius: var(--radius);
            color: var(--gold);
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gold);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-submit span {
            position: relative;
            z-index: 1;
        }

        .btn-submit:hover::before { transform: scaleX(1); }
        .btn-submit:hover { color: var(--bg); }

        .form-divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 28px 0;
        }

        .form-divider::before, .form-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .form-divider span {
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .demo-badge {
            background: rgba(201,169,110,0.08);
            border: 1px solid rgba(201,169,110,0.2);
            border-radius: var(--radius);
            padding: 10px 16px;
            font-size: 0.8rem;
            color: var(--muted);
            text-align: center;
        }

        .demo-badge code {
            color: var(--gold);
            font-family: 'DM Mono', monospace;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 0.8rem;
            text-decoration: none;
            margin-top: 32px;
            transition: color 0.3s;
        }

        .back-link:hover { color: var(--text); }

        @media (max-width: 768px) {
            body { grid-template-columns: 1fr; }
            .brand-panel { display: none; }
            .login-panel { padding: 40px 32px; }
        }
    </style>
</head>
<body>

    <!-- Left: Brand Visual -->
    <div class="brand-panel">
        <div class="brand-lines">
            <span></span><span></span><span></span><span></span>
        </div>

        <div class="brand-logo">
            <p class="label">Panel de Administración</p>
            <h1>LUXE<br>MOTORS <em>MX</em></h1>
        </div>

        <div class="brand-center">
            <div class="brand-emblem">
                <i class="fas fa-car"></i>
            </div>
            <p class="brand-tagline">
                Compra · Vende<br>
                <em>Configura</em> tu lujo
            </p>
        </div>

        <div class="brand-footer">
            <div class="brand-stat">
                <span class="num">12+</span>
                <span class="label">Modelos</span>
            </div>
            <div class="brand-stat">
                <span class="num">4</span>
                <span class="label">Categorías</span>
            </div>
            <div class="brand-stat">
                <span class="num">CDMX</span>
                <span class="label">Sede</span>
            </div>
        </div>
    </div>

    <!-- Right: Login Form -->
    <div class="login-panel">
        <div class="form-header">
            <p class="eyebrow">Acceso restringido</p>
            <h2>Iniciar<br>Sesión</h2>
            <p>Ingresa tus credenciales para acceder al panel de control.</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="field-group">
                <label for="user">Usuario</label>
                <div class="field-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" id="user" name="user" placeholder="Nombre de usuario"
                           value="<?= isset($_POST['user']) ? htmlspecialchars($_POST['user']) : '' ?>"
                           required autocomplete="username">
                </div>
            </div>

            <div class="field-group">
                <label for="pass">Contraseña</label>
                <div class="field-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="pass" name="pass" placeholder="••••••••"
                           required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" name="login" class="btn-submit">
                <span>Acceder al panel</span>
            </button>
        </form>

        <div class="form-divider">
            <span>demo</span>
        </div>

        <div class="demo-badge">
            Usuario: <code>admin</code> &nbsp;·&nbsp; Contraseña: <code>1234</code>
        </div>

        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left fa-xs"></i> Volver al sitio principal
        </a>
    </div>

</body>
</html>