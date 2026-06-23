<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ResetPassword extends Component
{
    public string $token           = '';
    public string $email           = '';
    public string $password        = '';
    public string $passwordConfirm = '';

    protected array $rules = [
        'email'           => 'required|email',
        'password'        => 'required|min:8|same:passwordConfirm',
        'passwordConfirm' => 'required',
    ];

    protected array $messages = [
        'email.required'           => 'El correo es obligatorio.',
        'email.email'              => 'Ingresa un correo válido.',
        'password.required'        => 'La contraseña es obligatoria.',
        'password.min'             => 'Mínimo 8 caracteres.',
        'password.same'            => 'Las contraseñas no coinciden.',
        'passwordConfirm.required' => 'Confirma la contraseña.',
    ];

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function restablecer(): void
    {
        $this->validate();

        $status = Password::broker()->reset(
            [
                'email'                 => $this->email,
                'password'              => $this->password,
                'password_confirmation' => $this->passwordConfirm,
                'token'                 => $this->token,
            ],
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('exito', 'Contraseña actualizada. Ya puedes iniciar sesión.');
            $this->redirect(route('login'), navigate: false);
        } else {
            $this->addError('token', 'El enlace es inválido o ya expiró. Solicita uno nuevo.');
        }
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
