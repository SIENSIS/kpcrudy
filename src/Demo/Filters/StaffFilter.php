<?php

namespace Kpcrudy\Demo\Filters;

use CodeIgniter\HTTP\RequestInterface;
use Kpcrudy\Demo\Demo;
use Kpcrudy\Demo\Libraries\Auth;

/**
 * Requires a logged-in administrator or editor. The filter only opens the
 * page: what each role can do inside the CRUD is decided in one place,
 * Libraries\DemoPermissions.
 */
class StaffFilter extends AuthFilter
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $result = parent::before($request, $arguments);

        if ($result !== null) {
            return $result;
        }

        if (! Auth::isStaff()) {
            return redirect()->to(Demo::url())->with('error', 'This page is for administrators and editors only.');
        }

        return null;
    }
}
