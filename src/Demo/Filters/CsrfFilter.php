<?php

namespace Kpcrudy\Demo\Filters;

use CodeIgniter\Filters\CSRF;
use CodeIgniter\HTTP\RequestInterface;

/**
 * CodeIgniter's CSRF filter for the demo routes, so that they are protected
 * even if the host project has not enabled 'csrf' in its global filters.
 *
 * If another CSRF filter already runs for this request (usually the global
 * 'csrf'), it does nothing: CodeIgniter removes the token from the request
 * once verified, so verifying twice would always fail.
 *
 * Since it extends CodeIgniter\Filters\CSRF, kpcrudy sees it as the active
 * CSRF filter and does not verify the token again either.
 */
class CsrfFilter extends CSRF
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (self::verifiedByAnotherFilter()) {
            return null;
        }

        return parent::before($request, $arguments);
    }

    private static function verifiedByAnotherFilter(): bool
    {
        $filters = service('filters');

        // CodeIgniter 4.5+: the filter classes that run for this request
        if (method_exists($filters, 'getFiltersClass')) {
            foreach ($filters->getFiltersClass()['before'] ?? [] as $filter) {
                $class = is_array($filter) ? $filter[0] : $filter;

                if (is_a($class, CSRF::class, true) && ! is_a($class, self::class, true)) {
                    return true;
                }
            }

            return false;
        }

        // Older versions: global filters of app/Config/Filters.php
        $config = config('Filters');

        foreach ($config->globals['before'] ?? [] as $key => $value) {
            $alias = is_string($key) ? $key : $value;

            foreach ((array) ($config->aliases[$alias] ?? $alias) as $class) {
                if (is_string($class) && is_a($class, CSRF::class, true) && ! is_a($class, self::class, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
