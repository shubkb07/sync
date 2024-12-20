document.addEventListener('DOMContentLoaded', function() {
    const dbConfigForm = document.getElementById('dbConfigForm');
    const siteConfigForm = document.getElementById('siteConfigForm');

    if (dbConfigForm) {
        dbConfigForm.addEventListener('submit', function(e) {
            e.preventDefault();
            validateDBConfig();
        });
    }

    if (siteConfigForm) {
        siteConfigForm.addEventListener('submit', function(e) {
            e.preventDefault();
            validateSiteConfig();
        });
    }

    function validateDBConfig() {
        const dbHost = document.getElementById('dbHost');
        const dbUsername = document.getElementById('dbUsername');
        const dbPassword = document.getElementById('dbPassword');
        const dbTable = document.getElementById('dbTable');

        // Clear previous errors
        clearErrors();

        let isValid = true;

        // DB Host validation
        if (!dbHost.value.trim()) {
            showError(dbHost, 'Database host is required');
            isValid = false;
        }

        // Other DB fields validation
        if (!dbUsername.value.trim()) {
            showError(dbUsername, 'Database username is required');
            isValid = false;
        }

        if (!dbPassword.value.trim()) {
            showError(dbPassword, 'Database password is required');
            isValid = false;
        }

        if (!dbTable.value.trim()) {
            showError(dbTable, 'Database name is required');
            isValid = false;
        }

        if (isValid) {
            // Submit form or proceed to next step
            dbConfigForm.submit();
        }
    }

    function validateSiteConfig() {
        const siteName = document.getElementById('siteName');
        const loginUsername = document.getElementById('loginUsername');
        const userPassword = document.getElementById('userPassword');
        const userEmail = document.getElementById('userEmail');

        // Clear previous errors
        clearErrors();

        let isValid = true;

        // Site Name validation
        if (!siteName.value.trim()) {
            showError(siteName, 'Site name cannot be empty');
            isValid = false;
        }

        // Username validation (A-Z, a-z, 0-9, _)
        const usernameRegex = /^[A-Za-z0-9_]+$/;
        if (!usernameRegex.test(loginUsername.value)) {
            showError(loginUsername, 'Username can only contain A-Z, a-z, 0-9, and _');
            isValid = false;
        }

        // Password validation (minimum 10 characters)
        if (userPassword.value.length < 10) {
            showError(userPassword, 'Password must be at least 10 characters');
            isValid = false;
        }

        // Email validation
        const emailRegex = /^[A-Za-z0-9]+(?:\.[A-Za-z0-9]+)*@[A-Za-z0-9]+(?:\.[A-Za-z0-9]+)*\.[a-z]{2,20}$/;
        if (!emailRegex.test(userEmail.value)) {
            showError(userEmail, 'Invalid email format');
            isValid = false;
        }

        if (isValid) {
            // Submit form or process next steps
            siteConfigForm.submit();
        }
    }

    function showError(element, message) {
        element.classList.add('error');
        const errorSpan = document.createElement('span');
        errorSpan.classList.add('error-message');
        errorSpan.textContent = message;
        element.parentNode.insertBefore(errorSpan, element.nextSibling);
    }

    function clearErrors() {
        const errorElements = document.querySelectorAll('.error');
        const errorMessages = document.querySelectorAll('.error-message');
        
        errorElements.forEach(el => el.classList.remove('error'));
        errorMessages.forEach(msg => msg.remove());
    }
});