<?php

namespace App\Http\Controllers;

use App\Models\Logs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    private $logs;
    public function __construct()
    {
        $this->logs = new Logs();
    }
    public function login(){
        try{
            if (auth()->check()) {
                return redirect('admin');
            }
            return view('auth/login');
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
        }
    }
    public function forgotPassword()
    {
        if (auth()->check()) {
            return redirect('admin');
        }
        return view('auth/forgot-password');
    }

    public function resetPassword(string $token)
    {
        if (auth()->check()) {
            return redirect('admin');
        }
        return view('auth/reset-password', compact('token'));
    }

    public function cerrar_session(){
        Session::flush();
        Auth::logout();
        return redirect('/');
    }
}
