<?php
namespace App\Controllers\Medico;

use App\Controllers\BaseController;

class Home extends BaseController
{
    public function index()
    {
        return view('medico/home', $this->layoutData() + [
            'title' => 'Dashboard del Médico',
        ]);
    }
}
