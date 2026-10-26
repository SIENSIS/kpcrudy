<?php
/**
 * Estils i JavaScript propis del CRUD. S'inclouen al fragment perquè
 * funcioni també dins d'un layout del projecte.
 *
 * @var \Kpcrudy\Renderer $this
 */
?>
<style>
    .kpcrudy .kp-thumb { max-height: 48px; max-width: 96px; padding: 2px; }
    .kpcrudy .kp-preview { max-height: 160px; max-width: 240px; }
    .kpcrudy .kp-swatch { display: inline-block; width: 1em; height: 1em; border-radius: .2em; border: 1px solid rgba(0,0,0,.2); vertical-align: middle; }
    .kpcrudy th a { color: inherit; text-decoration: none; white-space: nowrap; }
    .kpcrudy th a:hover { text-decoration: underline; }
    .kpcrudy .kp-actions { white-space: nowrap; }
    .kpcrudy .kp-check { width: 1%; }
</style>
<script>
(function () {
    if (window.kpcrudyReady) { return; }
    window.kpcrudyReady = true;

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-kp-confirm]');
        if (el && !window.confirm(el.getAttribute('data-kp-confirm'))) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.matches('[data-kp-check-all]')) {
            e.target.form.querySelectorAll('input[name="ids[]"]').forEach(function (box) {
                box.checked = e.target.checked;
            });
        }
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches('[data-kp-bulk]') || e.submitter && e.submitter.hasAttribute('formaction')) { return; }

        var select = form.querySelector('select[name="action"]');
        var option = select.options[select.selectedIndex];

        if (!select.value) { e.preventDefault(); return; }
        if (!form.querySelector('input[name="ids[]"]:checked')) {
            e.preventDefault();
            window.alert(form.getAttribute('data-kp-bulk'));
            return;
        }
        if (option.dataset.confirm && !window.confirm(option.dataset.confirm)) {
            e.preventDefault();
            return;
        }

        // Accions de descàrrega (exportar): per GET, sense consumir el token CSRF
        if (option.dataset.url) {
            e.preventDefault();
            var params = new URLSearchParams();
            form.querySelectorAll('input[name="ids[]"]:checked').forEach(function (box) { params.append('ids[]', box.value); });
            if (form.querySelector('input[name="trash"]')) { params.append('trash', '1'); }
            window.location.href = option.dataset.url + '?' + params.toString();
        }
    });
})();
</script>
