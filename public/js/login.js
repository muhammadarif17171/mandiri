'use strict';

const passwordToggle = document.querySelector('[data-password-toggle]');

if (passwordToggle) {
    const password = document.getElementById(passwordToggle.dataset.passwordToggle);
    const eyeOpen = passwordToggle.querySelector('[data-eye-open]');
    const eyeClosed = passwordToggle.querySelector('[data-eye-closed]');

    if (!password || !eyeOpen || !eyeClosed) {
        throw new Error('Elemen tombol visibilitas kata sandi tidak lengkap.');
    }

    passwordToggle.addEventListener('click', () => {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        passwordToggle.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        passwordToggle.setAttribute('aria-pressed', String(visible));
        eyeOpen.hidden = visible;
        eyeClosed.hidden = !visible;
    });
}
