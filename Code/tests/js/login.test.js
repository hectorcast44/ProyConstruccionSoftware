const { initLogin } = require('../../public/js/login');

describe('login.js', () => {
    beforeEach(() => {
        document.body.innerHTML = `
      <div id="container"></div>
      <button id="signUp"></button>
      <button id="signIn"></button>
      <form id="signInForm"></form>
      <button id="btnLogin"></button>
      <span id="loginSpinner"></span>
      <span id="loginText"></span>
    `;
    });

    test('initLogin should attach click listeners', () => {
        initLogin();

        const container = document.getElementById('container');
        const btnSignUp = document.getElementById('signUp');
        const btnSignIn = document.getElementById('signIn');

        btnSignUp.click();
        expect(container.classList.contains('right-panel-active')).toBe(true);

        btnSignIn.click();
        expect(container.classList.contains('right-panel-active')).toBe(false);
    });
});
