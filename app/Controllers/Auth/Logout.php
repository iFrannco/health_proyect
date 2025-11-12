<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;

class Logout extends BaseController
{
    public function index()
    {
        $session = session();
        $session->destroy();

        return redirect()->to(site_url('auth/login'));
    }
}
