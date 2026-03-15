import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['token']
    
    connect() {
        this.nameCheck = /^[-_a-zA-Z0-9]{4,22}$/;
        this.tokenCheck = /^[-_/+a-zA-Z0-9]{24,}$/;
        
        // Set up global event listeners for CSRF protection
        this.setupGlobalListeners();
    }
    
    setupGlobalListeners() {
        // Generate and double-submit a CSRF token in a form field and a cookie
        document.addEventListener('submit', (event) => {
            // Skip CSRF modification for login and register forms
            if (event.target.getAttribute('data-turbo') === 'false') {
                return;
            }
            this.generateCsrfToken(event.target);
        }, true);

        // When @hotwired/turbo handles form submissions, send the CSRF token in a header
        document.addEventListener('turbo:submit-start', (event) => {
            const headers = this.generateCsrfHeaders(event.detail.formSubmission.formElement);
            Object.keys(headers).forEach(key => {
                event.detail.formSubmission.fetchRequest.headers[key] = headers[key];
            });
        });

        // Remove the CSRF cookie once a form has been submitted
        document.addEventListener('turbo:submit-end', (event) => {
            this.removeCsrfToken(event.detail.formSubmission.formElement);
        });
    }
    
    generateCsrfToken(formElement) {
        const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');

        if (!csrfField) {
            return;
        }

        let csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');
        let csrfToken = csrfField.value;

        if (!csrfCookie && this.nameCheck.test(csrfToken)) {
            csrfField.setAttribute('data-csrf-protection-cookie-value', csrfCookie = csrfToken);
            csrfField.defaultValue = csrfToken = btoa(String.fromCharCode.apply(null, (window.crypto || window.msCrypto).getRandomValues(new Uint8Array(18))));
        }
        csrfField.dispatchEvent(new Event('change', { bubbles: true }));

        if (csrfCookie && this.tokenCheck.test(csrfToken)) {
            const cookie = csrfCookie + '_' + csrfToken + '=' + csrfCookie + '; path=/; samesite=strict';
            document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
        }
    }

    generateCsrfHeaders(formElement) {
        const headers = {};
        const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');

        if (!csrfField) {
            return headers;
        }

        const csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');

        if (this.tokenCheck.test(csrfField.value) && this.nameCheck.test(csrfCookie)) {
            headers[csrfCookie] = csrfField.value;
        }

        return headers;
    }

    removeCsrfToken(formElement) {
        const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');

        if (!csrfField) {
            return;
        }

        const csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');

        if (this.tokenCheck.test(csrfField.value) && this.nameCheck.test(csrfCookie)) {
            const cookie = csrfCookie + '_' + csrfField.value + '=0; path=/; samesite=strict; max-age=0';
            document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
        }
    }
}
