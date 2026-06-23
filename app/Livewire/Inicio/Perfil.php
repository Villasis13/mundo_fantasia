<?php

namespace App\Livewire\Inicio;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithFileUploads;

class Perfil extends Component
{
    use WithFileUploads;

    // ── Datos personales ──────────────────────────────────────
    public string $nombre          = '';
    public string $apellidoPaterno = '';
    public string $apellidoMaterno = '';
    public string $dni             = '';
    public string $email           = '';
    public string $username        = '';
    public        $fotoUsuario     = null;

    // ── Contraseña ────────────────────────────────────────────
    public string $passwordActual  = '';
    public string $password        = '';
    public string $passwordConfirm = '';

    // ── Tab activo ────────────────────────────────────────────
    public string $tab = 'datos';

    private ?Logs $logs = null;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(): void
    {
        $user    = auth()->user();
        $persona = DB::table('persona')->where('id_persona', $user->id_persona)->first();

        $this->nombre          = $persona->persona_nombre            ?? '';
        $this->apellidoPaterno = $persona->persona_apellido_paterno  ?? '';
        $this->apellidoMaterno = $persona->persona_apellido_materno  ?? '';
        $this->dni             = $persona->persona_dni               ?? '';
        $this->email           = $user->email                        ?? '';
        $this->username        = $user->username                     ?? '';
    }

    public function actualizarDatos(): void
    {
        $userId    = auth()->user()->id_users;
        $personaId = auth()->user()->id_persona;

        $this->validate([
            'nombre'          => 'required|string|max:100',
            'apellidoPaterno' => 'required|string|max:100',
            'apellidoMaterno' => 'nullable|string|max:100',
            'dni'             => 'nullable|string|max:20',
            'email'           => "required|email|max:150|unique:users,email,{$userId},id_users",
            'username'        => "required|string|max:50|unique:users,username,{$userId},id_users",
            'fotoUsuario'     => 'nullable|image|max:2048',
        ], [
            'nombre.required'          => 'El nombre es obligatorio.',
            'apellidoPaterno.required' => 'El apellido paterno es obligatorio.',
            'email.required'           => 'El correo es obligatorio.',
            'email.unique'             => 'Este correo ya está registrado.',
            'username.required'        => 'El nombre de usuario es obligatorio.',
            'username.unique'          => 'Este usuario ya está en uso.',
            'fotoUsuario.image'        => 'El archivo debe ser una imagen.',
            'fotoUsuario.max'          => 'La imagen no puede superar 2MB.',
        ]);

        try {
            DB::beginTransaction();

            DB::table('persona')
                ->where('id_persona', $personaId)
                ->update([
                    'persona_nombre'           => $this->nombre,
                    'persona_apellido_paterno' => $this->apellidoPaterno,
                    'persona_apellido_materno' => $this->apellidoMaterno,
                    'persona_email'            => $this->email,
                    'persona_dni'              => $this->dni,
                    'updated_at'               => now(),
                ]);

            $datosUser = [
                'nombre_users' => $this->nombre,
                'email'        => $this->email,
                'username'     => $this->username,
                'updated_at'   => now(),
            ];

//            if ($this->fotoUsuario) {
//                $dirDestino = public_path('usuarios');
//                if (!file_exists($dirDestino)) {
//                    mkdir($dirDestino, 0755, true);
//                }
//                $nombreArchivo               = time() . '-' . $this->fotoUsuario->getClientOriginalName();
//                $this->fotoUsuario->move(public_path('usuarios'), $nombreArchivo);
//                $datosUser['user_fotografia'] = 'usuarios/' . $nombreArchivo;
//                $this->fotoUsuario           = null;
//            }

            if ($this->fotoUsuario) {
                $dirDestino = public_path('usuarios');
                if (!file_exists($dirDestino)) {
                    mkdir($dirDestino, 0755, true);
                }
                $nombreArchivo = time() . '-' . $this->fotoUsuario->getClientOriginalName();
                file_put_contents($dirDestino . DIRECTORY_SEPARATOR . $nombreArchivo, $this->fotoUsuario->get());
                $datosUser['user_fotografia'] = 'usuarios/' . $nombreArchivo;
                $this->fotoUsuario = null;
            }

            DB::table('users')->where('id_users', $userId)->update($datosUser);

            DB::commit();
            $this->dispatch('notificar', mensaje: 'Perfil actualizado correctamente.', tipo: 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            $this->dispatch('notificar', mensaje: 'Ocurrió un error al guardar los cambios.', tipo: 'error');
        }
    }

    public function cambiarContrasena(): void
    {
        $this->validate([
            'passwordActual'  => 'required|string',
            'password'        => 'required|string|min:6',
            'passwordConfirm' => 'required|same:password',
        ], [
            'passwordActual.required'  => 'Ingresa tu contraseña actual.',
            'password.required'        => 'La nueva contraseña es obligatoria.',
            'password.min'             => 'La contraseña debe tener al menos 6 caracteres.',
            'passwordConfirm.required' => 'Confirma la nueva contraseña.',
            'passwordConfirm.same'     => 'Las contraseñas no coinciden.',
        ]);

        $user = auth()->user();

        if (!Hash::check($this->passwordActual, $user->password)) {
            $this->addError('passwordActual', 'La contraseña actual no es correcta.');
            return;
        }

        try {
            DB::table('users')
                ->where('id_users', $user->id_users)
                ->update(['password' => Hash::make($this->password), 'updated_at' => now()]);

            $this->passwordActual  = '';
            $this->password        = '';
            $this->passwordConfirm = '';

            $this->dispatch('notificar', mensaje: 'Contraseña actualizada correctamente.', tipo: 'success');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->dispatch('notificar', mensaje: 'Ocurrió un error al cambiar la contraseña.', tipo: 'error');
        }
    }

    public function render()
    {
        $user    = auth()->user();
        $persona = DB::table('persona')->where('id_persona', $user->id_persona)->first();

        $rolNombre = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('mr.model_id', $user->id_users)
            ->value('r.name') ?? '—';

        $sucursales = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', $user->id_users)
            ->pluck('s.sucursal_nombre');

        $fotoActual = $user->user_fotografia ?? 'sin-fotografia.png';

        return view('livewire.inicio.perfil', compact(
            'user', 'persona', 'rolNombre', 'sucursales', 'fotoActual'
        ));
    }
}
