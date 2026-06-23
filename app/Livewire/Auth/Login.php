<?php

namespace App\Livewire\Auth;

use App\Models\Logs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Login extends Component
{
    private $logs;

    public function boot()
    {
        $this->logs = new Logs();
    }

    // ── Propiedades del formulario ────────────────────────────
    public $username  = '';
    public $password  = '';
    public $remember  = false;

    // ── Mensajes de error / aviso ─────────────────────────────
    public $errorLogin = '';

    // ── Autocompletado de usuario ─────────────────────────────
    public array $sugerenciasUsuarios = [];
    public bool  $mostrarSugerencias  = false;

    // ── Validaciones ─────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }

    protected function messages(): array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    // ── Autocompletado ────────────────────────────────────────
    public function updatedUsername(string $value): void
    {
        $this->errorLogin = '';
        $trimmed = trim($value);
        if (strlen($trimmed) < 1) {
            $this->sugerenciasUsuarios = [];
            $this->mostrarSugerencias  = false;
            return;
        }
        $this->sugerenciasUsuarios = DB::table('users')
            ->where('username', 'like', $trimmed . '%')
            ->where('users_estado', 1)
            ->orderBy('username')
            ->limit(8)
            ->pluck('username')
            ->toArray();
        $this->mostrarSugerencias = count($this->sugerenciasUsuarios) > 0;
    }

    public function seleccionarUsuario(string $username): void
    {
        $this->username            = $username;
        $this->sugerenciasUsuarios = [];
        $this->mostrarSugerencias  = false;
        $this->dispatch('focusPassword');
    }

    // ── Iniciar sesión ────────────────────────────────────────
    public function iniciarSesion()
    {
        $this->errorLogin = '';
        $this->validate();

        try {
            // 1. Intentar autenticación
            $credentials = [
                'username' => $this->username,
                'password' => $this->password,
            ];

            $dbHash = \DB::table('users')->where('id_users', 1)->value('password');
            $attemptResult = Auth::attempt($credentials, $this->remember);
            \Log::info('LOGIN_DEBUG', [
                'base_path'    => app()->basePath(),
                'env_file'     => app()->environmentFilePath(),
                'db_name'      => \DB::getDatabaseName(),
                'ENV_DB'       => $_ENV['DB_DATABASE'] ?? 'no env',
                'SERVER_DB'    => $_SERVER['DB_DATABASE'] ?? 'no server',
                'username'     => $this->username,
                'result'       => $attemptResult,
            ]);

            if (!$attemptResult) {
                $this->errorLogin = 'Usuario o contraseña incorrectos.';
                return;
            }

            $user = Auth::user();

            // 2. Verificar que el usuario esté activo
            if (!$user->users_estado) {
                Auth::logout();
                $this->errorLogin = 'Tu cuenta está inactiva. Contacta al administrador.';
                return;
            }

            session()->regenerate();

            $roleId = (int) DB::table('model_has_roles')
                ->where('model_id', $user->id_users)
                ->value('role_id');

            $destino = match ($roleId) {
                4 => route('Gestionventas.caja_pedidos'),
                5 => route('Gestionventas.pedidos'),
                default => route('admin'),
            };

            $this->redirect($destino, navigate: false);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            Auth::logout();
            $this->errorLogin = 'Ocurrió un error inesperado. Intenta nuevamente.';
        }
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
