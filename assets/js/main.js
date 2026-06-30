// assets/js/main.js
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= htmlspecialchars(session_id()) ?>';

    function csrfSafe(method, url) {
        return fetch(url, {
            method: method,
            headers: {'Content-Type': 'application/json'},
            body: method !== 'GET' ? JSON.stringify({csrf: csrfToken}) : undefined,
            credentials: 'same-origin'
        });
    }

    window.fetchAPI = function(method, url, data) {
        return fetch(url, {
            method: method,
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data || {}),
            credentials: 'same-origin'
        }).then(r => r.json());
    };
});
