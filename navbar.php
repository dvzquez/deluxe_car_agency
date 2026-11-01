<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<nav id="mainNav" class="lm-nav">
    <div class="lm-nav__inner">

        <!-- Logo -->
        <a class="lm-nav__brand" href="index.php">
            <span class="lm-nav__brand-main">LUXE MOTORS</span>
            <em class="lm-nav__brand-mx">MX</em>
        </a>

        <!-- Desktop links -->
        <ul class="lm-nav__links">
            <li><a href="index.php"       class="lm-nav__link <?= $currentPage === 'index.php' ? 'is-active' : '' ?>">Inicio</a></li>
            <li><a href="info.php"        class="lm-nav__link <?= $currentPage === 'info.php' ? 'is-active' : '' ?>">Nosotros</a></li>
            <li><a href="config.php"      class="lm-nav__link <?= $currentPage === 'config.php' ? 'is-active' : '' ?>">Configurador</a></li>
            <li><a href="plan_pagos.php"  class="lm-nav__link <?= $currentPage === 'plan_pagos.php' ? 'is-active' : '' ?>">Financiamiento</a></li>
            <li><a href="compraventa.php" class="lm-nav__link lm-nav__link--accent <?= $currentPage === 'compraventa.php' ? 'is-active' : '' ?>">Compra-Venta</a></li>
            <li><a href="admin/login.php" class="lm-nav__link lm-nav__link--admin">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px;margin-right:5px"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>Admin
            </a></li>
        </ul>

        <!-- Mobile hamburger -->
        <button class="lm-nav__burger" id="navBurger" aria-label="Menú">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Mobile drawer -->
    <div class="lm-nav__drawer" id="navDrawer">
        <a href="index.php"       class="lm-nav__drawer-link <?= $currentPage === 'index.php' ? 'is-active' : '' ?>">Inicio</a>
        <a href="info.php"        class="lm-nav__drawer-link <?= $currentPage === 'info.php' ? 'is-active' : '' ?>">Nosotros</a>
        <a href="config.php"      class="lm-nav__drawer-link <?= $currentPage === 'config.php' ? 'is-active' : '' ?>">Configurador</a>
        <a href="plan_pagos.php"  class="lm-nav__drawer-link <?= $currentPage === 'plan_pagos.php' ? 'is-active' : '' ?>">Financiamiento</a>
        <a href="compraventa.php" class="lm-nav__drawer-link lm-nav__drawer-link--accent">Compra-Venta</a>
        <a href="admin/login.php" class="lm-nav__drawer-link">Admin</a>
    </div>
</nav>

<style>
:root {
    --nav-h: 68px;
    --gold:  #C9A96E;
    --gold-dim: #7a6540;
}

.lm-nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 900;
    background: rgba(7,7,7,0);
    border-bottom: 1px solid transparent;
    transition: background 0.4s, border-color 0.4s;
}

.lm-nav.scrolled {
    background: rgba(7,7,7,0.97);
    border-color: #1c1c1c;
    backdrop-filter: blur(14px);
}

.lm-nav__inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 40px;
    height: var(--nav-h);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.lm-nav__brand {
    display: flex;
    align-items: baseline;
    gap: 4px;
    text-decoration: none;
}

.lm-nav__brand-main {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: 1.25rem;
    letter-spacing: 0.06em;
    color: #EBEBEB;
}

.lm-nav__brand-mx {
    font-style: normal;
    font-family: 'Cormorant Garamond', serif;
    font-weight: 600;
    font-size: 1.25rem;
    color: var(--gold);
}

.lm-nav__links {
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
    margin: 0; padding: 0;
}

.lm-nav__link {
    display: block;
    padding: 6px 14px;
    color: #666;
    text-decoration: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.8rem;
    font-weight: 400;
    letter-spacing: 0.05em;
    border-radius: 4px;
    transition: color 0.25s, background 0.25s;
    position: relative;
}

.lm-nav__link:hover { color: #EBEBEB; }

.lm-nav__link.is-active {
    color: #EBEBEB;
}

.lm-nav__link.is-active::after {
    content: '';
    position: absolute;
    bottom: -2px; left: 14px; right: 14px;
    height: 1px;
    background: var(--gold);
}

.lm-nav__link--accent {
    color: var(--gold) !important;
    border: 1px solid rgba(201,169,110,0.25);
    padding: 6px 16px;
    transition: all 0.3s;
}

.lm-nav__link--accent:hover {
    background: rgba(201,169,110,0.08);
    border-color: var(--gold-dim);
}

.lm-nav__link--admin {
    color: #3a3a3a !important;
    font-size: 0.75rem;
}

.lm-nav__link--admin:hover { color: #666 !important; }

/* Burger */
.lm-nav__burger {
    display: none;
    flex-direction: column;
    gap: 5px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
}

.lm-nav__burger span {
    display: block;
    width: 22px; height: 1px;
    background: #666;
    transition: all 0.3s;
}

.lm-nav__burger.open span:nth-child(1) { transform: translateY(6px) rotate(45deg); background: var(--gold); }
.lm-nav__burger.open span:nth-child(2) { opacity: 0; }
.lm-nav__burger.open span:nth-child(3) { transform: translateY(-6px) rotate(-45deg); background: var(--gold); }

/* Drawer */
.lm-nav__drawer {
    display: none;
    flex-direction: column;
    padding: 16px 24px 24px;
    border-top: 1px solid #1c1c1c;
    background: rgba(7,7,7,0.98);
}

.lm-nav__drawer.open { display: flex; }

.lm-nav__drawer-link {
    display: block;
    padding: 13px 0;
    color: #666;
    text-decoration: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    border-bottom: 1px solid #111;
    transition: color 0.2s;
}

.lm-nav__drawer-link:hover,
.lm-nav__drawer-link.is-active { color: #EBEBEB; }

.lm-nav__drawer-link--accent { color: var(--gold) !important; }

/* Offset for fixed nav */
body { padding-top: var(--nav-h); }

@media (max-width: 900px) {
    .lm-nav__links { display: none; }
    .lm-nav__burger { display: flex; }
    .lm-nav__inner { padding: 0 20px; }
}
</style>

<script>
(function(){
    const nav    = document.getElementById('mainNav');
    const burger = document.getElementById('navBurger');
    const drawer = document.getElementById('navDrawer');

    // Scroll behavior
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });

    // Always scrolled on non-hero pages
    if (!document.querySelector('.lm-hero')) {
        nav.classList.add('scrolled');
    }

    // Mobile toggle
    burger.addEventListener('click', () => {
        burger.classList.toggle('open');
        drawer.classList.toggle('open');
    });
})();
</script>