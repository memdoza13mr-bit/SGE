<?php
if (!isset($titulo_pagina)) {
    $titulo_pagina = 'Sistema de Reservas';
}

if (!isset($menu_activo)) {
    $menu_activo = '';
}
?>

<header class="topbar">
    <div class="topbar-left">
        <button id="menuToggleTop" class="menu-btn" type="button" aria-label="Abrir menú">
            <i class="fa-solid fa-bars"></i>
        </button>

        <a href="../coordinador/dashboard.php" class="logo-area">
            <img src="../assets/img/Logo.jpeg" class="logo-img" alt="Logo">
            <div class="logo-text"><?php echo htmlspecialchars($titulo_pagina); ?></div>
        </a>
    </div>
</header>

<div class="layout sidebar-collapsed" id="layoutPrincipal">
    <aside class="sidebar" id="sidebarPrincipal">
        <div class="sidebar-user">
            <span><?php echo htmlspecialchars($_SESSION['nombre'] ?? ''); ?></span>
        </div>

        <nav class="menu-iconos">
            <a href="../coordinador/dashboard.php" class="<?php echo $menu_activo === 'inicio' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-house"></i>
                <span>Inicio</span>
            </a>

            <a href="../coordinador/nueva_reserva.php" class="<?php echo $menu_activo === 'nueva_reserva' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Hacer reserva</span>
            </a>

            <a href="../coordinador/mis_reservas.php" class="<?php echo $menu_activo === 'mis_reservas' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-file-lines"></i>
                <span>Mis reservas</span>
            </a>

            <a href="../coordinador/calendario.php" class="<?php echo $menu_activo === 'calendario' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Calendario</span>
            </a>

            <a href="../coordinador/solicitar_cambio_password.php" class="<?php echo $menu_activo === 'solicitar_password' ? 'activo' : ''; ?>">
                <i class="fa-solid fa-key"></i>
                <span>Cambiar contraseña</span>
            </a>

            <a href="../auth/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Cerrar sesión</span>
            </a>
        </nav>
    </aside>