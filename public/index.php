<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

if (
    ($_SERVER['REQUEST_URI'] ?? '/') === '/'
    && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Test App for suggestion thru mapy API</title>
        <style>
            body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 2rem; background: #f5f7fb; }
            h1 { margin-bottom: 1rem; }
            .container { max-width: 640px; background: #ffffff; border-radius: 8px; padding: 1.5rem 2rem; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08); }
            label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
            input[type="text"] { width: 100%; padding: 0.5rem 0.75rem; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 1rem; }
            button { margin-top: 0.75rem; padding: 0.5rem 1.25rem; border-radius: 4px; border: none; background: #2563eb; color: #ffffff; font-size: 0.95rem; cursor: pointer; }
            button:disabled { opacity: 0.6; cursor: default; }
            .status { margin-top: 0.75rem; font-size: 0.9rem; color: #64748b; }
            .error { color: #b91c1c; }
            ul { margin-top: 1rem; padding-left: 1.25rem; }
        </style>
    </head>
    <body>
    <div class="container">
        <h1>Test App for suggestion thru mapy API</h1>
        <form id="suggest-form">
            <label for="query">Address query</label>
            <input type="text" id="query" name="query" placeholder="Start typing e.g. Prague" autocomplete="off">
            <button type="submit">Get suggestions</button>
        </form>
        <div id="status" class="status"></div>
        <ul id="results"></ul>
    </div>

    <script>
        const form = document.getElementById('suggest-form');
        const input = document.getElementById('query');
        const statusEl = document.getElementById('status');
        const resultsEl = document.getElementById('results');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const query = input.value.trim();
            resultsEl.innerHTML = '';
            statusEl.textContent = '';
            statusEl.classList.remove('error');

            if (!query) {
                statusEl.textContent = 'Please enter a query.';
                statusEl.classList.add('error');
                return;
            }

            const button = form.querySelector('button');
            button.disabled = true;
            statusEl.textContent = 'Loading suggestions...';

            try {
                const params = new URLSearchParams({ q: query, limit: '5' });
                const response = await fetch('/api/mapy/suggest?' + params.toString());

                const contentType = response.headers.get('content-type') || '';
                let data;

                if (contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    statusEl.textContent = 'Non‑JSON response from server: ' + text.slice(0, 120) + '…';
                    statusEl.classList.add('error');
                    return;
                }

                if (!response.ok) {
                    statusEl.textContent = data.error || 'Request failed.';
                    statusEl.classList.add('error');
                    return;
                }

                statusEl.textContent = '';
                const suggestions = data.suggestions || data.results || [];

                if (!Array.isArray(suggestions) || suggestions.length === 0) {
                    statusEl.textContent = 'No suggestions returned.';
                    return;
                }

                for (const item of suggestions) {
                    const li = document.createElement('li');
                    li.textContent = typeof item === 'string' ? item : (item.label || JSON.stringify(item));
                    resultsEl.appendChild(li);
                }
            } catch (error) {
                statusEl.textContent = 'Error: ' + (error && error.message ? error.message : 'Unknown error');
                statusEl.classList.add('error');
            } finally {
                button.disabled = false;
            }
        });
    </script>
    </body>
    </html>
    <?php
    exit(0);
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
