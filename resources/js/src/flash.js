(function () {
    const CONTAINER_ID = 'js-flash-stack';

    function ensureContainer() {
        let el = document.getElementById(CONTAINER_ID);
        if (el) return el;
        el = document.createElement('div');
        el.id = CONTAINER_ID;
        el.setAttribute('aria-live', 'polite');
        el.setAttribute('aria-atomic', 'true');
        el.style = 'position: fixed; z-index: 9999; top: 1rem; right: 1rem; width: auto; max-width: 400px; display: flex; flex-direction: column; gap: 0.5rem;';
        document.body.appendChild(el);
        return el;
    }

    function makeToast(opts) {
        const {
            message,
            type = 'success',
            autoClose = true,
            duration = 4000,
            allowHtml = false,
            html = ''
        } = opts || {};

        const container = ensureContainer();

        const wrap = document.createElement('div');
        wrap.className = [
            'pointer-events-auto rounded-md shadow-lg px-4 py-3 text-sm flex items-start gap-2 transition-all transform duration-300 translate-x-full',
            type === 'success' ? 'bg-green-600 text-white'
                : type === 'info' ? 'bg-blue-600 text-white'
                    : type === 'warning' ? 'bg-yellow-500 text-white'
                        : 'bg-red-600 text-white'
        ].join(' ');

        const body = document.createElement('div');
        body.className = 'flex-1 break-words';

        // Prefer HTML content if allowHtml is true, otherwise plain text message
        if (allowHtml) {
            body.innerHTML = html || message; // Use message as fallback for HTML if simple string passed
        } else {
            body.textContent = message || '';
        }

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('aria-label', 'Close');
        btn.className = 'ml-2 text-white/80 hover:text-white focus:outline-none';
        btn.textContent = '✕';
        btn.onclick = (e) => { e.stopPropagation(); remove(); };

        wrap.appendChild(body);
        wrap.appendChild(btn);
        container.appendChild(wrap);

        // Animate in
        requestAnimationFrame(() => {
            wrap.classList.remove('translate-x-full');
            wrap.classList.add('translate-x-0');
        });

        let timer = null;
        if (autoClose !== false && duration > 0) {
            timer = setTimeout(remove, duration);
        }

        function remove() {
            if (timer) clearTimeout(timer);
            wrap.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
            }, 300);
        }
    }

    // Standard API
    window.flash = (message, type = 'success', autoClose = true, duration = 4000) =>
        makeToast({ message, type, autoClose, duration });

    // HTML API
    window.flashHTML = (html, type = 'success', autoClose = true, duration = 4000) =>
        makeToast({ allowHtml: true, html, type, autoClose, duration });

    // Helpers
    window.flashSuccess = (msg, autoClose = true, duration = 4000) => window.flash(msg, 'success', autoClose, duration);
    window.flashInfo = (msg, autoClose = true, duration = 4000) => window.flash(msg, 'info', autoClose, duration);
    window.flashWarning = (msg, autoClose = true, duration = 4000) => window.flash(msg, 'warning', autoClose, duration);
    window.flashError = (msg, autoClose = true, duration = 4000) => window.flash(msg, 'error', autoClose, duration);
})();
