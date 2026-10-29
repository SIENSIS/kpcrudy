<?php
/**
 * Estils i JavaScript propis del CRUD. S'inclouen al fragment perquè
 * funcioni també dins d'un layout del projecte.
 *
 * Compatible amb una Content-Security-Policy estricta: els blocs porten el
 * nonce de CodeIgniter i les plantilles no fan servir atributs on*="" ni
 * style="" (el comportament va amb atributs data-kp-*).
 *
 * @var \Kpcrudy\Renderer $this
 */
?>
<style<?= $this->nonce('style') ?>>
    .kpcrudy .kp-thumb { max-height: 48px; max-width: 96px; padding: 2px; }
    .kpcrudy .kp-preview { max-height: 160px; max-width: 240px; }
    .kpcrudy .kp-swatch { display: inline-block; width: 1em; height: 1em; border-radius: .2em; border: 1px solid rgba(0,0,0,.2); vertical-align: middle; }
    .kpcrudy th a { color: inherit; text-decoration: none; white-space: nowrap; }
    .kpcrudy th a:hover { text-decoration: underline; }
    .kpcrudy .kp-actions { white-space: nowrap; }
    .kpcrudy .kp-check { width: 1%; }
    .kpcrudy .kp-search { max-width: 28rem; }
</style>
<script<?= $this->nonce() ?>>
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
</script>
