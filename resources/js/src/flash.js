(function () {
    const CONTAINER_ID = 'js-flash-stack';

    function ensureContainer() {
        let el = document.getElementById(CONTAINER_ID);
        if (el) return el;
        el = document.createElement('div');
        el.id = CONTAINER_ID;
        el.setAttribute('aria-live', 'polite');
        el.setAttribute('aria-atomic', 'true');
        el.style = 'position: fixed; z-index: 9999; top: 1rem; left: 50%; transform: translateX(-50%); width: auto; max-width: 90vw;';
        document.body.appendChild(el);
        return el;
    }

    function makeToast(opts) {
        const {
            message,    // string (used when allowHtml=false)
            type = 'success',
            autoClose = true,
            duration = 2500,
            allowHtml = false,  // set true to render html
            html = ''   // html string (used when allowHtml=true)
        } = opts || {};

        const container = ensureContainer();

        const wrap = document.createElement('div');
        wrap.className = [
            'pointer-events-auto rounded-md mb-2 shadow px-4 py-3 text-sm flex items-start gap-2 transition-opacity',
            type === 'success' ? 'bg-green-700 text-white'
                : type === 'info' ? 'bg-blue-700 text-white'
                    : type === 'warning' ? 'bg-yellow-600 text-white'
                        : 'bg-red-700 text-white'
        ].join(' ');
        wrap.style.opacity = '0';

        const body = document.createElement('div');
        body.className = 'flex-1';

        if (allowHtml && html) {
            // TRUSTED HTML ONLY!
            body.innerHTML = html;
        } else {
            body.textContent = message || '';
        }

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('aria-label', 'Close');
        btn.className = 'ml-2 text-white/80 hover:text-white';
        btn.textContent = '✕';
        btn.onclick = (e) => { e.stopPropagation(); remove(); };

        wrap.appendChild(body);
        wrap.appendChild(btn);
        container.appendChild(wrap);

        requestAnimationFrame(() => { wrap.style.opacity = '1'; });

        let timer = null;
        if (autoClose !== false) {
            if (duration > 0) timer = setTimeout(remove, duration);
        }

        function remove() {
            if (timer) clearTimeout(timer);
            wrap.style.opacity = '0';
            setTimeout(() => wrap.parentNode && wrap.parentNode.removeChild(wrap), 200);
        }
    }

    window.flash = (message, type = 'success', autoClose = true, duration = 2500) =>
        makeToast({ message, type, autoClose, duration });

    window.flashHTML = (html, type = 'success', autoClose = true, duration = 2500) =>
        makeToast({ allowHtml: true, html, type, autoClose, duration });

    window.flashSuccess = (msg, autoClose = true, duration = 2500) => window.flash(msg, 'success', autoClose, duration);
    window.flashInfo = (msg, autoClose = true, duration = 2500) => window.flash(msg, 'info', autoClose, duration);
    window.flashWarning = (msg, autoClose = true, duration = 3000) => window.flash(msg, 'warning', autoClose, duration);
    window.flashError = (msg, autoClose = true, duration = 4000) => window.flash(msg, 'error', autoClose, duration);
})();
