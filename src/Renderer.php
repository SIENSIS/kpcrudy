<?php

declare(strict_types=1);

namespace Kpcrudy;

use Kpcrudy\Config\Kpcrudy as KpcrudyConfig;
use RuntimeException;
use Throwable;

/**
 * Renderitza les plantilles PHP d'un tema.
 *
 * Cerca cada plantilla als directoris en ordre, de manera que un tema
 * personalitzat només ha de contenir les plantilles que vol canviar.
 * Dins de les plantilles, $this és aquesta classe.
 */
class Renderer
{
    /**
     * @param list<string> $paths Directoris de plantilles, del més prioritari al menys
     */
    public function __construct(protected array $paths, protected ?KpcrudyConfig $config = null)
    {
    }

    /**
     * Directori dels fitxers CSS i JavaScript de la llibreria (kpcrudy:publish assets).
     */
    public static function assetsDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Assets';
    }

    /**
     * Inclou un fitxer CSS o JavaScript de la llibreria (src/Assets/) segons
     * Config\Kpcrudy::$assetsMode: 'inline' (per defecte) el posa dins d'un
     * <style>/<script> amb el nonce de la CSP; 'external' l'enllaça des de
     * Config::$assetsUrl (copiat amb "php spark kpcrudy:publish assets"), amb
     * un paràmetre de versió perquè els canvis no quedin a la memòria cau.
     */
    public function asset(string $file): string
    {
        if (preg_match('/^[a-z0-9_-]+\.(css|js)$/', $file) !== 1) {
            throw new RuntimeException(lang('Kpcrudy.exceptions.assetNotFound', [$file]));
        }

        $source = self::assetsDir() . DIRECTORY_SEPARATOR . $file;

        if (! is_file($source)) {
            throw new RuntimeException(lang('Kpcrudy.exceptions.assetNotFound', [$file]));
        }

        $css = str_ends_with($file, '.css');

        if (($this->config?->assetsMode ?? 'inline') === 'external') {
            $base = (string) ($this->config?->assetsUrl ?? 'assets/kpcrudy');
            $base = preg_match('#^(https?:)?//|^/#', $base) === 1 ? $base : base_url($base);
            $url  = rtrim($base, '/') . '/' . $file . '?v=' . substr((string) md5_file($source), 0, 10);

            return $css
                ? '<link rel="stylesheet"' . $this->nonce('style') . ' href="' . esc($url, 'attr') . '">'
                : '<script' . $this->nonce() . ' src="' . esc($url, 'attr') . '" defer></script>';
        }

        $content = (string) file_get_contents($source);

        return $css
            ? '<style' . $this->nonce('style') . ">\n" . $content . '</style>'
            : '<script' . $this->nonce() . ">\n" . $content . '</script>';
    }

    /**
     * Plantilles que s'estan renderitzant: [vista, índex del directori].
     * Permet que renderParent() sàpiga de quin tema ve cada crida.
     *
     * @var list<array{0: string, 1: int}>
     */
    protected array $stack = [];

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        [$file, $index] = $this->find($view, 0);

        return $this->renderFile($view, $file, $index, $data);
    }

    /**
     * Renderitza la mateixa plantilla del tema pare: la que hi ha als temes
     * següents al de la plantilla que fa la crida. Serveix per ampliar una
     * plantilla en lloc de copiar-la, p. ex. afegir CSS a partials/assets.
     * Funciona amb diversos nivells (tema -> pare -> bootstrap5).
     *
     * @param array<string, mixed> $data
     */
    public function renderParent(string $view, array $data = []): string
    {
        $current = null;

        foreach (array_reverse($this->stack) as [$rendering, $index]) {
            if ($rendering === $view) {
                $current = $index;
                break;
            }
        }

        // Cridat des d'una altra plantilla: el "pare" és el següent a la primera que es troba
        $current ??= $this->find($view, 0)[1];

        $parent = $this->find($view, $current + 1, false);

        if ($parent === null) {
            throw new RuntimeException(lang('Kpcrudy.exceptions.noParentTemplate', [$view]));
        }

        return $this->renderFile($view, $parent[0], $parent[1], $data);
    }

    public function locate(string $view): string
    {
        return $this->find($view, 0)[0];
    }

    /**
     * Primer directori, a partir de $from, que té la plantilla.
     *
     * @return array{0: string, 1: int}|null [fitxer, índex]
     */
    protected function find(string $view, int $from, bool $required = true): ?array
    {
        for ($i = $from, $count = count($this->paths); $i < $count; $i++) {
            $file = rtrim($this->paths[$i], '/\\') . DIRECTORY_SEPARATOR . $view . '.php';

            if (is_file($file)) {
                return [$file, $i];
            }
        }

        if ($required) {
            throw new RuntimeException(lang('Kpcrudy.exceptions.templateNotFound', [$view]));
        }

        return null;
    }

    /**
     * @param array<string, mixed> $__data
     */
    protected function renderFile(string $__view, string $__file, int $__index, array $__data): string
    {
        $this->stack[] = [$__view, $__index];

        extract($__data, EXTR_SKIP);
        ob_start();

        try {
            include $__file;
        } catch (Throwable $e) {
            ob_end_clean();

            throw $e;
        } finally {
            array_pop($this->stack);
        }

        return (string) ob_get_clean();
    }

    /**
     * Converteix un array (fins i tot niat) en camps ocults de formulari.
     *
     * @param array<array-key, mixed> $values
     */
    public function hidden(array $values, string $prefix = ''): string
    {
        $html = '';

        foreach ($values as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';

            if (is_array($value)) {
                $html .= $this->hidden($value, $name);

                continue;
            }

            $html .= '<input type="hidden" name="' . esc($name, 'attr') . '" value="' . esc((string) $value, 'attr') . '">';
        }

        return $html;
    }

    /**
     * Atribut nonce de la Content-Security-Policy de CodeIgniter per a un
     * <script> o un <style> (o <link>) de les plantilles, amb un espai al
     * davant: <script<?= $this->nonce() ?>>. Buit si la CSP no està activa.
     */
    public function nonce(string $type = 'script'): string
    {
        $function = $type === 'style' ? 'csp_style_nonce' : 'csp_script_nonce';

        if (! function_exists($function)) {
            return '';
        }

        $nonce = (string) $function();

        return $nonce === '' ? '' : ' ' . $nonce;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function attrs(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if ($value === false || $value === null) {
                continue;
            }

            $html .= ' ' . esc($name, 'attr');

            if ($value !== true) {
                $html .= '="' . esc((string) $value, 'attr') . '"';
            }
        }

        return $html;
    }
}
