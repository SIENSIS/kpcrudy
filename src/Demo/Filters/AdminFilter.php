<?php

namespace Kpcrudy\Demo\Filters;

use CodeIgniter\HTTP\RequestInterface;
use Kpcrudy\Demo\Demo;
use Kpcrudy\Demo\Libraries\Auth;

/**
 * Requires a logged-in demo administrator.
 *
 * A class of its own instead of AuthFilter::class . ':admin': filter
 * arguments with class names do not work the same way in every
 * CodeIgniter 4 version.
 */
class AdminFilter extends AuthFilter
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $result = parent::before($request, $arguments);

        if ($result !== null) {
            return $result;
        }

        if (! Auth::isAdmin()) {
            return redirect()->to(Demo::url())->with('error', 'This page is for administrators only.');
        }

        return null;
    }
}
