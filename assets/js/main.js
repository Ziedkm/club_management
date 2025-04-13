
// Main JavaScript file for ISG Clubs

document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
    
    // Form validation for auth forms
    const authForms = document.querySelectorAll('.auth-form');
    
    authForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Get all required inputs
            const requiredInputs = form.querySelectorAll('[required]');
            
            requiredInputs.forEach(input => {
                // Clear previous error
                const errorElement = input.nextElementSibling;
                if (errorElement && errorElement.classList.contains('error-message')) {
                    errorElement.remove();
                }
                
                // Check if input is empty
                if (!input.value.trim()) {
                    isValid = false;
                    
                    // Create error message
                    const error = document.createElement('p');
                    error.classList.add('error-message');
                    error.textContent = 'This field is required.';
                    
                    // Insert error after input
                    input.parentNode.insertBefore(error, input.nextSibling);
                }
                
                // Validate email format if it's an email input
                if (input.type === 'email' && input.value.trim()) {
                    const emailRegex = /\S+@\S+\.\S+/;
                    if (!emailRegex.test(input.value)) {
                        isValid = false;
                        
                        // Create error message
                        const error = document.createElement('p');
                        error.classList.add('error-message');
                        error.textContent = 'Please enter a valid email address.';
                        
                        // Insert error after input
                        input.parentNode.insertBefore(error, input.nextSibling);
                    }
                }
            });
            
            // Check password match if it's a register form
            const password = form.querySelector('#password');
            const confirmPassword = form.querySelector('#confirm_password');
            
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                isValid = false;
                
                // Create error message
                const error = document.createElement('p');
                error.classList.add('error-message');
                error.textContent = 'Passwords do not match.';
                
                // Insert error after input
                confirmPassword.parentNode.insertBefore(error, confirmPassword.nextSibling);
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
});
