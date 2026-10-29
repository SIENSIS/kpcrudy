<?php

namespace Kpcrudy\Demo\Filters;

use CodeIgniter\HTTP\RequestInterface;
use Kpcrudy\Demo\Demo;
use Kpcrudy\Demo\Libraries\Auth;

/**
 * Requires a logged-in demo user (and verifies the CSRF token, see
 * CsrfFilter). StaffFilter restricts the route to administrators and
 * editors, and AdminFilter to administrators.
 *
 * Routes use the class name, so nothing has to be added to the host
 * project's app/Config/Filters.php.
 */
class AuthFilter extends CsrfFilter
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $csrf = parent::before($request, $arguments);

        if ($csrf !== null) {
            return $csrf;
        }

        if (Auth::user() === null) {
            session()->remove(Demo::SESSION_KEY);

            return redirect()->to(Demo::url('login'))->with('error', 'Please log in to access the demo.');
        }

        return null;
    }
}
