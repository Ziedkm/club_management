document.addEventListener('DOMContentLoaded', function() {
    const togglePasswordButton = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    const eyeOffIcon = document.getElementById('eye-off-icon');

    if (togglePasswordButton && passwordInput && eyeIcon && eyeOffIcon) {
        togglePasswordButton.addEventListener('click', function() {
            // Toggle the type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle the icon visibility
            if (type === 'password') {
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            } else {
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
                passwordInput.style.width = '100%';
                passwordInput.style.paddingLeft="40px";
                passwordInput.style.paddingRight="15px";
                passwordInput.style.paddingTop="12px";
                passwordInput.style.paddingBottom="12px";
                passwordInput.style.borderRadius="6px";
                passwordInput.style.border="1px solid #ccc";
                passwordInput.style.fontSize="1rem";
                
            }
        });
    } else {
        console.error("Password toggle elements not found!");
    }
});