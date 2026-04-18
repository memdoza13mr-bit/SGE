<?php
if (!isset($titulo_pagina)) {
    $titulo_pagina = 'Panel Directivo';
}

if (!isset($menu_activo)) {
    $menu_activo = '';
}

$nombreMostrar = trim($_SESSION['nombre_completo'] ?? ($_SESSION['nombre'] ?? 'Usuario'));
?>

<header class="topbar">
    <div class="topbar-left">
        <button id="menuToggleTop" class="menu-btn" type="button" aria-label="Abrir menú">
            <i class="fa-solid fa-bars"></i>
        </button>

        <a href="../directivo/dashboard.php" class="logo-area">
            <img src="../assets/img/Logo.jpeg" class="logo-img" alt="Logo">
            <div class="logo-text"><?php echo htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8'); ?></div>
        </a>
    </div>
</header>

<div class="layout sidebar-collapsed" id="layoutPrincipal">
    <aside class="sidebar" id="sidebarPrincipal">
        <div class="sidebar-user" title="<?php echo htmlspecialchars($nombreMostrar, ENT_QUOTES, 'UTF-8'); ?>">
            <span class="sidebar-user-saludo">Bienvenido</span>
            <span class="sidebar-user-nombre"><?php echo htmlspecialchars($nombreMostrar, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <nav class="menu-iconos">
            <a href="../directivo/dashboard.php" class="<?php echo $menu_activo === 'inicio' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-house"></i>
                <span>Inicio</span>
            </a>

            <a href="../directivo/reservas.php" class="<?php echo $menu_activo === 'reservas' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-file-lines"></i>
                <span>Reservas</span>
            </a>

            <a href="../directivo/calendario.php" class="<?php echo $menu_activo === 'calendario' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Calendario</span>
            </a>

            <a href="../directivo/coordinadores.php" class="<?php echo $menu_activo === 'coordinadores' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-users"></i>
                <span>Coordinadores</span>
            </a>

            <a href="../directivo/espacios.php" class="<?php echo $menu_activo === 'espacios' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-building"></i>
                <span>Espacios</span>
            </a>

            <a href="../directivo/anuncios.php" class="<?php echo $menu_activo === 'anuncios' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Anuncios</span>
            </a>

            <a href="../directivo/solicitudes_password.php" class="<?php echo $menu_activo === 'solicitudes_password' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-key"></i>
                <span>Solicitud contraseñas</span>
            </a>

            <a href="../auth/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Cerrar sesión</span>
            </a>
        </nav>
    </aside>