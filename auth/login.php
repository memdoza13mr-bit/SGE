<?php
session_start();

/* inicializar control de intentos */
if (!isset($_SESSION['intentos_fallidos'])) {
    $_SESSION['intentos_fallidos'] = 0;
}

if (!isset($_SESSION['bloqueado_hasta'])) {
    $_SESSION['bloqueado_hasta'] = 0;
}

/* captcha */
$numero1 = rand(1, 9);
$numero2 = rand(1, 9);
$_SESSION['captcha_resultado'] = $numero1 + $numero2;

$bloqueado = false;
$segundosRestantes = 0;

if ($_SESSION['bloqueado_hasta'] > time()) {
    $bloqueado = true;
    $segundosRestantes = $_SESSION['bloqueado_hasta'] - time();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión</title>
<link rel="stylesheet" href="../assets/css/estilos.css">

<style>
*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Arial, Helvetica, sans-serif;
    background:#ede7e7;
    color:#111827;
}

.topbar-login{
    background:#ffffff;
    color:#fff;
    padding:18px 28px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.logo-area-login{
    display:flex;
    align-items:center;
    gap:16px;
    text-decoration:none;
    color:#fff;
}

.logo-login{
    width:56px;
    height:56px;
    object-fit:contain;
    background:#fff;
    border-radius:8px;
    padding:4px;
}

.logo-text-login{
    font-size:20px;
    font-weight:bold;
    color:#111827;
}

.login-wrapper{
    min-height:calc(100vh - 92px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px 20px;
}

.login-layout{
    width:100%;
    max-width:1100px;
    display:grid;
    grid-template-columns:1.1fr .9fr;
    gap:28px;
    align-items:stretch;
}

.login-info{
    background:linear-gradient(135deg, #17375e 0%, #2563eb 100%);
    color:#fff;
    border-radius:20px;
    padding:40px;
    box-shadow:0 10px 30px rgba(0,0,0,.12);
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items: center;
    min-height:520px;
    
}

.login-info-logo{
    width:95px;
    height:95px;
    object-fit:contain;
    background:#fff;
    border-radius:14px;
    padding:8px;
    margin-bottom:22px;
}

.login-info h1{
    margin:0 0 16px 0;
    font-size:40px;
    line-height:1.15;
}

.login-info p{
    margin:0 0 14px 0;
    font-size:17px;
    line-height:1.6;
    color:#fef2f2;
}

.login-info-list{
    margin-top:18px;
    display:grid;
    gap:12px;
}

.login-info-item{
    background:rgba(255,255,255,.14);
    border:1px solid rgba(255,255,255,.18);
    border-radius:12px;
    padding:14px 16px;
    font-size:15px;
}

.login-box{
    background:#fff;
    border-radius:20px;
    padding:34px;
    box-shadow:0 10px 30px rgba(0,0,0,.10);
    min-height:520px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}

.login-box h2{
    margin:0 0 24px 0;
    text-align:center;
    color:#1d4ed8;
    font-size:28px;
}

.login-box label{
    display:block;
    margin-top:14px;
    margin-bottom:8px;
    font-weight:bold;
    color:#111827;
    font-size:15px;
}

.login-box input{
    width:100%;
    padding:13px 14px;
    border:1px solid #cbd5e1;
    border-radius:10px;
    background:#f8fafc;
    font-size:15px;
    outline:none;
    transition:.2s;
}

.login-box input:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,.12);
    background:#fff;
}

.captcha-box{
    margin-top:16px;
    padding:14px;
    border-radius:12px;
    background:#f8fafc;
    border:1px solid #e5e7eb;
}

.captcha-question{
    margin:0 0 10px 0;
    font-weight:bold;
    color:#111827;
    font-size:16px;
}

.btn-login{
    width:100%;
    margin-top:22px;
    background:#1d4ed8;
    color:#fff;
    border:none;
    padding:14px;
    border-radius:10px;
    font-weight:bold;
    font-size:16px;
    cursor:pointer;
    transition:.2s;
}

.btn-login:hover{
    background:#1e40af;
}

.btn-login:disabled{
    background:#9ca3af;
    cursor:not-allowed;
}

.error{
    background:#fee2e2;
    color:#991b1b;
    padding:12px 14px;
    border-radius:10px;
    margin-bottom:14px;
    border:1px solid #fecaca;
    font-size:14px;
}

.info{
    background:#dbeafe;
    color:#1e40af;
    padding:12px 14px;
    border-radius:10px;
    margin-bottom:14px;
    border:1px solid #bfdbfe;
    font-size:14px;
}

.link-recuperar{
    margin-top:14px;
    text-align:center;
}

.link-recuperar a{
    color:#1d4ed8;
    font-weight:bold;
    text-decoration:none;
    font-size:14px;
}

.link-recuperar a:hover{
    text-decoration:underline;
}

.footer-login{
    margin-top:18px;
    text-align:center;
    color:#6b7280;
    font-size:13px;
}

@media (max-width: 950px){
    .login-layout{
        grid-template-columns:1fr;
    }

    .login-info{
        min-height:auto;
        padding:30px;
    }

    .login-box{
        min-height:auto;
    }

    .login-info h1{
        font-size:30px;
    }
}

@media (max-width: 520px){
    .topbar-login{
        padding:14px 16px;
    }

    .logo-text-login{
        font-size:18px;
    }

    .login-wrapper{
        padding:20px 14px;
    }

    .login-info,
    .login-box{
        padding:22px;
        border-radius:16px;
    }

    .login-box h2{
        font-size:24px;
    }
}
</style>
</head>
<body>

<header class="topbar-login">
    <a href="../index.php" class="logo-area-login">
        <img src="../assets/img/Logo.jpeg" class="logo-login" alt="Logo">
        <div class="logo-text-login">Centro universitario de valladolid</div>
    </a>
</header>

<div class="login-wrapper">
    <div class="login-layout">

        <section class="login-info">
            <img src="../assets/img/Logo.jpeg" class="login-info-logo" alt="Logo institucional" >

            <h1>Bienvenido al sistema</h1>


            <div class="login-info-list">
                <div class="login-info-item">Gestión de espacios y reservas en un solo lugar.</div>
                <div class="login-info-item">Consulta de disponibilidad por fecha y horario.</div>
                <div class="login-info-item">Acceso seguro para directivos y coordinadores.</div>
            </div>
        </section>

        <section class="login-box">
            <h2>Iniciar sesión</h2>

            <?php
            if ($bloqueado) {
                $minutos = ceil($segundosRestantes / 60);
                echo "<div class='error'>Demasiados intentos fallidos. Intenta nuevamente en {$minutos} minuto(s).</div>";
            } elseif (isset($_GET['error'])) {
                if ($_GET['error'] === 'captcha') {
                    echo "<div class='error'>La verificación de seguridad es incorrecta.</div>";
                } else {
                    echo "<div class='error'>Correo o contraseña incorrectos.</div>";
                }
            }

            if (!$bloqueado && $_SESSION['intentos_fallidos'] > 0) {
                echo "<div class='info'>Intentos fallidos acumulados: " . (int)$_SESSION['intentos_fallidos'] . " de 5.</div>";
            }

            if (isset($_SESSION['ok_password'])) {
                echo "<div class='info'>" . htmlspecialchars($_SESSION['ok_password']) . "</div>";
                unset($_SESSION['ok_password']);
            }
            ?>

            <form method="POST" action="login_process.php">
                <label>Correo</label>
                <input type="email" name="correo" required <?php echo $bloqueado ? 'disabled' : ''; ?>>

                <label>Contraseña</label>
                <input type="password" name="password" required <?php echo $bloqueado ? 'disabled' : ''; ?>>

                <div class="captcha-box">
                    <p class="captcha-question">
                        Verificación de seguridad: ¿Cuánto es <?php echo $numero1; ?> + <?php echo $numero2; ?>?
                    </p>
                    <input type="number" name="captcha" required <?php echo $bloqueado ? 'disabled' : ''; ?>>
                </div>

                <button type="submit" class="btn-login" <?php echo $bloqueado ? 'disabled' : ''; ?>>
                    Iniciar sesión
                </button>
            </form>

            <div class="link-recuperar">
                <a href="olvide_password.php">¿Olvidaste tu contraseña?</a>
            </div>

            <div class="footer-login">
                Centro universitario · Sistema institucional de reservas
            </div>
        </section>

    </div>
</div>

</body>
</html>