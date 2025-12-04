<?php
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $publicPos  = strpos($scriptName, '/public/');
    if ($publicPos === false) {
        $baseUrl = '/';
    } else {
        $baseUrl = substr($scriptName, 0, $publicPos + strlen('/public/'));
    }
    $error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Agenda Escolar</title>
    <base href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <script>
        const BASE_URL = "<?php echo $baseUrl; ?>";
    </script>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>styles/login.css" />
</head>
<body>
    <div class="auth-container" id="container">
        <div class="form-container sign-up-container">
            <form id="signUpForm" action="auth/register" method="POST">
                <h1>Crear Cuenta</h1>
                <p class="subtitle">Crea una cuenta y comienza</p>
                <div id="mensajeSignUp"
                    class="mensaje <?php echo !empty($signup_error) ? 'error' : ''; ?>">
                    <?php if (!empty($signup_error)): ?>
                        <?php echo htmlspecialchars($signup_error, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <input  class="form-control" type="text" id="suNombre" name="nombre"  placeholder="Nombre"  required>
                </div>

                <div class="form-group">
                    <input class="form-control" type="email" id="suCorreo" name="correo" placeholder="Correo electrónico" required>
                </div>

                <div class="form-group">
                    <input class="form-control" type="password" id="suPassword" name="password" placeholder="Contraseña" minlength="6" required>
                </div>

                <button class="btn-primary" type="submit">Registrarme</button>
            </form>
        </div>
        <div class="form-container sign-in-container">
            <form id="signInForm" action="auth/login" method="POST">
                <h1>Iniciar Sesión</h1>
                <p class="subtitle">Cada avance cuenta. Inicia sesión y sigue con tu progreso</p>
                
                <div id="mensajeLogin" class="mensaje <?php echo !empty($error) ? 'error' : ''; ?>">
                    <?php if (!empty($error)): ?>
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <input  class="form-control" type="email" id="correo" name="correo"  placeholder="Correo electrónico" required autocomplete="email">
                </div>

                <div class="form-group">
                    <input class="form-control" type="password" id="password" name="password" placeholder="Contraseña" required autocomplete="current-password">
                </div>

                <button class="btn-primary" type="submit" id="btnLogin">
                    <span class="spinner-border" id="loginSpinner" style="display:none;"></span>
                    <span id="loginText">Entrar</span>
                </button>

            </form>

        </div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>¡Bienvenido!</h1>
                    <p>Inicia sesión para continuar con tu organización escolar.</p>
                    <button class="btn-ghost" id="signIn">Iniciar sesión</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>¡Hola!</h1>
                    <p>Empieza hoy mismo a construir tu mejor versión académica</p>
                    <button class="btn-ghost" id="signUp">Crear cuenta</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo $baseUrl; ?>js/login.js"></script>

</body>
</html>
