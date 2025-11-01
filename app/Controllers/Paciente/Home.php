<?php
namespace App\Controllers\Paciente;

use App\Controllers\BaseController;

class Home extends BaseController
{
    public function index()
    {
        return view('paciente/home', $this->layoutData() + [
            'title' => 'Panel del Paciente',
        ]);
    }
}
