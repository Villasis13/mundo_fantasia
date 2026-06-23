<?php

namespace App\Http\Controllers;

use App\Models\Logs;

class InicioController extends Controller
{
    private $logs;
    public function __construct()
    {
        $this->logs = new Logs();
    }

    public function inicio(){
        try {

            if (auth()->check()) {
                return redirect('admin');
            }
            return view('auth/login');

        }catch (\Exception $e){
            $this->logs->insertarLog($e);
        }
    }







}
