/**
 * Inicializa la funcionalidad de la página de login/registro.
 * Gestiona la animación de cambio entre paneles y el spinner de carga.
 */
function initLogin() {
    const container = document.getElementById('container');
    const btnSignUp = document.getElementById('signUp');
    const btnSignIn = document.getElementById('signIn');

    if (btnSignUp && btnSignIn && container) {
        btnSignUp.addEventListener('click', () => {
            container.classList.add('right-panel-active');
        });

        btnSignIn.addEventListener('click', () => {
            container.classList.remove('right-panel-active');
        });
    }

    // Abrir panel de registro automáticamente si está configurado
    if (window.SIGNUP_OPEN === true && container) {
        container.classList.add('right-panel-active');
    }

    // Gestión del spinner y estado de carga al enviar el formulario
    const loginForm = document.getElementById('signInForm');
    const loginBtn = document.getElementById('btnLogin');
    const loginSpinner = document.getElementById('loginSpinner');
    const loginText = document.getElementById('loginText');

    if (loginForm && loginBtn && loginSpinner && loginText) {
        loginForm.addEventListener('submit', function () {
            const delay = 500;
            setTimeout(() => {
                loginBtn.disabled = true;
                loginSpinner.style.display = 'inline-block';
                loginText.textContent = 'Ingresando...';
            }, delay);
        });
    }
}

document.addEventListener('DOMContentLoaded', initLogin);

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { initLogin };
}
