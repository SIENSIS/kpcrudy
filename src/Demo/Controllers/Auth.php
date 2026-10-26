<?php

namespace Kpcrudy\Demo\Controllers;

use Kpcrudy\Demo\Demo;
use Kpcrudy\Demo\Libraries\Auth as AuthService;

class Auth extends BaseController
{
    /**
     * Demo login: a list of users, one click logs in as that user.
     */
    public function login()
    {
        if (AuthService::user() !== null) {
            return redirect()->to(Demo::url());
        }

        return view('Kpcrudy\Demo\Views\auth\login', ['users' => AuthService::loginable()]);
    }

    public function attempt()
    {
        if (AuthService::loginAs((int) $this->request->getPost('user_id')) === null) {
            return redirect()->to(Demo::url('login'))->with('error', 'This user cannot log in (deactivated or in the trash).');
        }

        return redirect()->to(Demo::url());
    }

    public function logout()
    {
        AuthService::logout();

        return redirect()->to(Demo::url('login'))->with('message', 'You have logged out.');
    }
}
