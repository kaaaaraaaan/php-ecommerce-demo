// Security utilities for CSRF protection and XSS prevention

class SecurityManager {
    constructor() {
        this.csrfToken = null;
        this.init();
    }

    init() {
        // Get CSRF token from server on page load
        this.fetchCSRFToken();
        
        // Set up automatic token refresh
        setInterval(() => {
            this.refreshCSRFToken();
        }, 30 * 60 * 1000); // Refresh every 30 minutes
    }

    async fetchCSRFToken() {
        try {
            const response = await fetch('api.php?action=get_csrf_token');
            if (response.ok) {
                const data = await response.json();
                this.csrfToken = data.csrf_token;
            }
        } catch (error) {
            console.error('Failed to fetch CSRF token:', error);
        }
    }

    async refreshCSRFToken() {
        await this.fetchCSRFToken();
    }

    getCSRFToken() {
        return this.csrfToken;
    }

    // Add CSRF token to request data
    addCSRFToken(data = {}) {
        if (this.csrfToken) {
            data.csrf_token = this.csrfToken;
        }
        return data;
    }

    // Add CSRF token to FormData
    addCSRFTokenToFormData(formData) {
        if (this.csrfToken) {
            formData.append('csrf_token', this.csrfToken);
        }
        return formData;
    }

    // Add CSRF token to headers
    getCSRFHeaders() {
        return this.csrfToken ? { 'X-CSRF-Token': this.csrfToken } : {};
    }

    // Sanitize HTML content to prevent XSS
    sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Escape HTML entities
    escapeHTML(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Validate input length
    validateLength(input, maxLength) {
        return input && input.length <= maxLength;
    }

    // Validate email format
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Secure fetch wrapper with CSRF protection
    async secureFetch(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                ...this.getCSRFHeaders(),
                ...(options.headers || {})
            }
        };

        // Add CSRF token to body if it's a POST request with JSON data
        if (options.method === 'POST' && options.body && typeof options.body === 'string') {
            try {
                const bodyData = JSON.parse(options.body);
                options.body = JSON.stringify(this.addCSRFToken(bodyData));
            } catch (e) {
                // If body is not JSON, keep it as is
            }
        }

        const finalOptions = { ...defaultOptions, ...options };
        return fetch(url, finalOptions);
    }

    // Secure form submission
    async secureFormSubmit(form, url, options = {}) {
        const formData = new FormData(form);
        this.addCSRFTokenToFormData(formData);

        const defaultOptions = {
            method: 'POST',
            body: formData,
            headers: this.getCSRFHeaders()
        };

        const finalOptions = { ...defaultOptions, ...options };
        return fetch(url, finalOptions);
    }
}

// Global security manager instance
const securityManager = new SecurityManager();

// Export for use in other scripts
window.securityManager = securityManager;
