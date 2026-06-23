<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Password;

class ForgotPassword extends Component
{
    public string $email    = '';
    public bool   $enviado  = false;

    protected array $rules = [
        'email' => 'required|email',
    ];

    protected array $messages = [
        'email.required' => 'El correo es obligatorio.',
        'email.email'    => 'Ingresa un correo válido.',
    ];

    public function enviar(): void
    {
        $this->validate();

        $status = Password::broker()->sendResetLink(['email' => $this->email]);

        match ($status) {
            Password::RESET_LINK_SENT  => $this->enviado = true,
            Password::RESET_THROTTLED  => $this->addError('email', 'Ya enviamos un enlace hace menos de un minuto. Revisa tu bandeja o espera antes de intentarlo de nuevo.'),
            default                    => $this->addError('email', 'No encontramos ninguna cuenta con ese correo electrónico.'),
        };
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
