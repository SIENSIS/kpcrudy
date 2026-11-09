/* kpcrudy: comportament de les llistes i formularis (Config\Kpcrudy::$assetsMode). */
(function () {
    if (window.kpcrudyReady) { return; }
    window.kpcrudyReady = true;

    /**
     * Demana confirmació. Abans de window.confirm() s'envia l'esdeveniment
     * cancel·lable "kpcrudy:confirm" (al document): si un listener el cancel·la
     * (preventDefault), mostra el seu propi diàleg i crida detail.proceed() si
     * l'usuari accepta. Exemple amb SweetAlert2:
     *
     *   document.addEventListener('kpcrudy:confirm', function (e) {
     *       e.preventDefault();
     *       Swal.fire({ text: e.detail.message, showCancelButton: true })
     *           .then(function (r) { if (r.isConfirmed) { e.detail.proceed(); } });
     *   });
     */
    function confirmThen(message, element, proceed) {
        var event = new CustomEvent('kpcrudy:confirm', {
            cancelable: true,
            detail: { message: message, element: element, proceed: proceed }
        });

        if (!document.dispatchEvent(event)) {
            return false;
        }

        return window.confirm(message);
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-kp-confirm]');
        if (!el) { return; }

        // Ja confirmat per un diàleg propi: segueix l'acció
        if (el.hasAttribute('data-kp-confirmed')) {
            el.removeAttribute('data-kp-confirmed');
            return;
        }

        var ok = confirmThen(el.getAttribute('data-kp-confirm'), el, function () {
            el.setAttribute('data-kp-confirmed', '1');
            el.click();
        });

        if (!ok) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    // Menú d'accions "⋯" (rowActionsMenu()): en obrir-ne un es tanquen els altres, i
    // un clic fora el tanca
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.kpcrudy details.kp-menu[open]').forEach(function (menu) {
            if (!menu.contains(e.target)) { menu.removeAttribute('open'); }
        });
    });

    // Relacions amb cerca AJAX (setRelation(..., ['ajax' => true])): el camp visible
    // cerca a base/lookup i el camp ocult guarda l'id triat
    var lookupTimer = null;

    function lookupClose(box) {
        var list = box.querySelector('.kp-lookup-list');
        list.hidden = true;
        list.innerHTML = '';
        box.querySelector('[data-kp-lookup-text]').setAttribute('aria-expanded', 'false');
    }

    function lookupShow(box, results) {
        var list = box.querySelector('.kp-lookup-list');
        list.innerHTML = '';

        results.forEach(function (item) {
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'kp-lookup-item';
            option.setAttribute('role', 'option');
            option.setAttribute('data-id', item.id);
            option.textContent = item.text;
            list.appendChild(option);
        });

        if (!results.length) {
            var empty = document.createElement('div');
            empty.className = 'kp-lookup-empty';
            empty.textContent = box.getAttribute('data-kp-empty') || '';
            list.appendChild(empty);
        }

        list.hidden = false;
        box.querySelector('[data-kp-lookup-text]').setAttribute('aria-expanded', 'true');
    }

    document.addEventListener('input', function (e) {
        if (!e.target.matches('[data-kp-lookup-text]')) { return; }

        var input = e.target;
        var box = input.closest('[data-kp-lookup]');
        var text = input.value.trim();
        var min = parseInt(box.getAttribute('data-kp-min') || '0', 10);

        // Escriure anul·la la tria anterior: només val un element de la llista
        box.querySelector('[data-kp-lookup-value]').value = '';
        clearTimeout(lookupTimer);

        if (text.length < min || text === '') {
            lookupClose(box);
            return;
        }

        lookupTimer = setTimeout(function () {
            fetch(box.getAttribute('data-kp-lookup') + '&q=' + encodeURIComponent(text), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.ok ? response.json() : { results: [] };
            }).then(function (data) {
                lookupShow(box, data.results || []);
            }).catch(function () {
                lookupClose(box);
            });
        }, 250);
    });

    document.addEventListener('click', function (e) {
        var item = e.target.closest('.kp-lookup-item');

        if (item) {
            var box = item.closest('[data-kp-lookup]');
            box.querySelector('[data-kp-lookup-value]').value = item.getAttribute('data-id');
            box.querySelector('[data-kp-lookup-text]').value = item.textContent;
            lookupClose(box);
            return;
        }

        document.querySelectorAll('.kpcrudy [data-kp-lookup]').forEach(function (box) {
            if (!box.contains(e.target)) { lookupClose(box); }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && e.target.matches('[data-kp-lookup-text]')) {
            lookupClose(e.target.closest('[data-kp-lookup]'));
        }
    });

    // Tancar avisos sense el JavaScript del framework (temes w3css i tailwind)
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-kp-dismiss]');
        var box = el ? el.closest('[data-kp-alert]') : null;
        if (box) { box.remove(); }
    });

    document.addEventListener('change', function (e) {
        if (e.target.matches('[data-kp-check-all]')) {
            e.target.form.querySelectorAll('input[name="ids[]"]').forEach(function (box) {
                box.checked = e.target.checked;
            });
        }

        // Desplegables que envien el formulari en canviar (p. ex. "per pàgina")
        if (e.target.matches('[data-kp-autosubmit]') && e.target.form) {
            e.target.form.submit();
        }
    });

    // Colors de les mostres (kp-swatch): sense style="" per a la CSP
    function paintSwatches() {
        document.querySelectorAll('.kpcrudy [data-kp-color]').forEach(function (el) {
            el.style.backgroundColor = el.getAttribute('data-kp-color');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', paintSwatches);
    } else {
        paintSwatches();
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches('[data-kp-bulk]') || e.submitter && e.submitter.hasAttribute('formaction')) { return; }

        var select = form.querySelector('select[name="action"]');
        var option = select.options[select.selectedIndex];
        var submitter = e.submitter;

        if (!select.value) { e.preventDefault(); return; }
        if (!form.querySelector('input[name="ids[]"]:checked')) {
            e.preventDefault();
            window.alert(form.getAttribute('data-kp-bulk'));
            return;
        }

        // Accions de descàrrega (exportar): per GET, sense consumir el token CSRF.
        // Retorna false si ha fet la descàrrega (el formulari no s'ha d'enviar).
        function submitOrDownload() {
            if (!option.dataset.url) { return true; }

            var params = new URLSearchParams();
            form.querySelectorAll('input[name="ids[]"]:checked').forEach(function (box) { params.append('ids[]', box.value); });
            if (form.querySelector('input[name="trash"]')) { params.append('trash', '1'); }
            // La URL pot portar ja paràmetres (p. ex. la signatura)
            window.location.href = option.dataset.url + (option.dataset.url.indexOf('?') === -1 ? '?' : '&') + params.toString();

            return false;
        }

        if (form.hasAttribute('data-kp-confirmed')) {
            form.removeAttribute('data-kp-confirmed');
        } else if (option.dataset.confirm) {
            var ok = confirmThen(option.dataset.confirm, form, function () {
                if (submitOrDownload()) {
                    form.setAttribute('data-kp-confirmed', '1');
                    form.requestSubmit(submitter || undefined);
                }
            });

            if (!ok) {
                e.preventDefault();
                return;
            }
        }

        if (!submitOrDownload()) {
            e.preventDefault();
        }
    });
})();
