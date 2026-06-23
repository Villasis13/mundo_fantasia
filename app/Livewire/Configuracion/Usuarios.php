<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Usuarios extends Component
{
    use WithPagination, WithFileUploads;

    // ── Propiedades del formulario ─────────────────────────────
    public string  $dni              = '';
    public string  $nombre           = '';
    public string  $apellidoPaterno  = '';
    public string  $apellidoMaterno  = '';
    public string  $email            = '';
    public string  $username         = '';
    public string  $password         = '';
    public string  $passwordConfirm  = '';
    public ?int    $rolId            = null;
    public         $fotoUsuario      = null;
    public array   $tiendasSeleccionadas    = [];

    // ── DNI lookup ─────────────────────────────────────────────
    public bool   $buscandoDni    = false;
    public string $dniMensaje     = '';
    public string $dniMensajeTipo = '';

    // ── Control modal CRUD ─────────────────────────────────────
    public bool $modoEdicion  = false;
    public ?int $idEditar     = null;
    public ?int $idPersona    = null;
    public int  $empresaIdModal = 0;

    // ── Modal cambio de estado ─────────────────────────────────
    public ?int $idCambiarEstado = null;
    public ?int $nuevoEstado     = null;

    // ── Búsqueda, filtros y paginación ─────────────────────────
    public string $buscar         = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'u.id_users';
    public string $ordenDireccion = 'asc';
    public int    $filtroSucursal = 0;
    public int    $filtroEmpresa  = 0;
    public int    $filtroRol      = 0;
    public string $filtroEstado   = '';

    private ?Logs $logs           = null;
    private int   $cachedRoleId   = 0;

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('gestion_usuarios.listar'), 403);
    }

    // ── Helpers de contexto del usuario autenticado ────────────
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }
    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');

        return $id ? (int) $id : null;
    }

    // ── Validación ─────────────────────────────────────────────
    protected function rules(): array
    {
        $exceptEmail    = $this->idEditar ? ",{$this->idEditar},id_users" : '';
        $exceptUsername = $this->idEditar ? ",{$this->idEditar},id_users" : '';

        $rules = [
            'dni'                  => 'required|string|max:20',
            'nombre'               => 'required|string|max:100',
            'apellidoPaterno'      => 'required|string|max:100',
            'apellidoMaterno'      => 'nullable|string|max:100',
            'email'                => "required|email|max:150|unique:users,email{$exceptEmail}",
            'username'             => "required|string|max:50|unique:users,username{$exceptUsername}",
            'rolId'                => 'required|integer|exists:roles,id',
            'fotoUsuario'          => 'nullable|image|max:2048',
            'tiendasSeleccionadas' => 'required|array|min:1',
        ];

        if ($this->esSuperAdmin()) {
            $rules['empresaIdModal'] = 'required|integer|min:1';
        }

        if (!$this->modoEdicion) {
            $rules['password']        = 'required|string|min:6';
            $rules['passwordConfirm'] = 'required|same:password';
        } else {
            $rules['password']        = 'nullable|string|min:6';
            $rules['passwordConfirm'] = 'nullable|same:password';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'dni.required'             => 'El DNI es obligatorio.',
            'nombre.required'          => 'El nombre es obligatorio.',
            'apellidoPaterno.required' => 'El apellido paterno es obligatorio.',
            'email.required'           => 'El correo es obligatorio.',
            'email.unique'             => 'Este correo ya está registrado.',
            'username.required'        => 'El nombre de usuario es obligatorio.',
            'username.unique'          => 'Este usuario ya está registrado.',
            'rolId.required'           => 'Debes asignar un rol.',
            'rolId.exists'             => 'El rol seleccionado no es válido.',
            'password.required'        => 'La contraseña es obligatoria.',
            'password.min'             => 'La contraseña debe tener al menos 6 caracteres.',
            'passwordConfirm.required' => 'Debes repetir la contraseña.',
            'passwordConfirm.same'     => 'Las contraseñas no coinciden.',
            'fotoUsuario.image'             => 'El archivo debe ser una imagen.',
            'fotoUsuario.max'               => 'La imagen no puede superar 2MB.',
            'tiendasSeleccionadas.required' => 'Debes seleccionar al menos una tienda.',
            'tiendasSeleccionadas.min'      => 'Debes seleccionar al menos una tienda.',
            'empresaIdModal.min'            => 'Debes seleccionar una empresa.',
        ];
    }

    // ── Consulta de DNI (API Migo) ─────────────────────────────
    public function updatedDni(string $valor): void
    {
        $this->dniMensaje     = '';
        $this->dniMensajeTipo = '';

        if (strlen($valor) === 8 && ctype_digit($valor)) {
            $this->buscarDni();
        }
    }

    public function buscarDni(): void
    {
        if (strlen($this->dni) !== 8 || !ctype_digit($this->dni)) {
            $this->dniMensaje     = 'El DNI debe tener exactamente 8 dígitos numéricos.';
            $this->dniMensajeTipo = 'error';
            return;
        }

        $this->buscandoDni    = true;
        $this->dniMensaje     = '';
        $this->dniMensajeTipo = '';

        try {
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->asForm()
                ->post('https://api.migo.pe/api/v1/dni', [
                    'token' => config('services.tokens.api_migo'),
                    'dni'   => $this->dni,
                ]);

            $data = $response->json();

            if ($data['success'] ?? false) {
                $nombreCompleto  = trim(preg_replace('/\s+/', ' ', $data['nombre']));
                $partes          = explode(' ', $nombreCompleto);

                $apellidoPaterno = $partes[0] ?? '';
                $apellidoMaterno = $partes[1] ?? '';
                $nombres         = implode(' ', array_slice($partes, 2));

                // Apellidos compuestos: DE, DEL, DELOS, SAN, SANTA…
                if (isset($partes[1]) && in_array(strtoupper($partes[1]), ['DE', 'DEL', 'DELA', 'DELOS', 'SAN', 'SANTA'])) {
                    $apellidoMaterno = ($partes[1] ?? '') . ' ' . ($partes[2] ?? '');
                    $nombres         = implode(' ', array_slice($partes, 3));
                }

                $this->apellidoPaterno = mb_convert_case(strtolower($apellidoPaterno), MB_CASE_TITLE, 'UTF-8');
                $this->apellidoMaterno = mb_convert_case(strtolower($apellidoMaterno), MB_CASE_TITLE, 'UTF-8');
                $this->nombre          = mb_convert_case(strtolower($nombres),         MB_CASE_TITLE, 'UTF-8');

                $this->dniMensaje     = 'Datos encontrados correctamente.';
                $this->dniMensajeTipo = 'success';
            } else {
                $this->dniMensaje     = $data['message'] ?? 'No se encontraron datos para este DNI.';
                $this->dniMensajeTipo = 'error';
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->dniMensaje     = 'Error al consultar el servicio. Intente nuevamente.';
            $this->dniMensajeTipo = 'error';
        } finally {
            $this->buscandoDni = false;
        }
    }

    // ── Lifecycle hooks de filtros y empresa modal ─────────────
    public function updatedEmpresaIdModal(): void
    {
        $this->tiendasSeleccionadas = [];
    }

    public function updatingFiltroEmpresa(): void
    {
        $this->filtroSucursal = 0;
        $this->resetPage();
    }

    // ── Render ──────────────────────────────────────────────────
    public function render()
    {
        $esAdmin        = $this->esAdmin();
        $esSuperAdmin   = $this->esSuperAdmin();
        $adminEmpresaId = $esAdmin ? $this->adminEmpresaId() : null;

        $columnasPermitidas = ['u.id_users', 'u.username', 'u.email', 'p.persona_nombre'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'u.id_users';
        $direccion = $this->ordenDireccion === 'desc' ? 'desc' : 'asc';

        $filtroEmpresa  = $this->filtroEmpresa;
        $filtroSucursal = $this->filtroSucursal;

        $usuarios = DB::table('users as u')
            ->join('persona as p', 'p.id_persona', '=', 'u.id_persona')
            ->leftJoin('model_has_roles as mr', 'mr.model_id', '=', 'u.id_users')
            ->leftJoin('roles as r', 'r.id', '=', 'mr.role_id')
            // Admin: solo usuarios de su empresa
            ->when($esAdmin && $adminEmpresaId, function ($q) use ($adminEmpresaId) {
                $q->whereExists(function ($sub) use ($adminEmpresaId) {
                    $sub->from('user_tienda as ut2')
                        ->join('tiendas as t2', 't2.id_tienda', '=', 'ut2.id_tienda')
                        ->whereColumn('ut2.id_users', 'u.id_users')
                        ->where('t2.id_empresa', $adminEmpresaId);
                });
            })
            // Superadmin: filtro por empresa seleccionada
            ->when($esSuperAdmin && $filtroEmpresa > 0, function ($q) use ($filtroEmpresa) {
                $q->whereExists(function ($sub) use ($filtroEmpresa) {
                    $sub->from('user_tienda as ut4')
                        ->join('tiendas as t4', 't4.id_tienda', '=', 'ut4.id_tienda')
                        ->whereColumn('ut4.id_users', 'u.id_users')
                        ->where('t4.id_empresa', $filtroEmpresa);
                });
            })
            // Ambos roles: filtro por sede específica
            ->when($filtroSucursal > 0, function ($q) use ($filtroSucursal) {
                $q->whereExists(function ($sub) use ($filtroSucursal) {
                    $sub->from('user_tienda as ut3')
                        ->whereColumn('ut3.id_users', 'u.id_users')
                        ->where('ut3.id_tienda', $filtroSucursal);
                });
            })
            ->when($this->filtroRol > 0, fn($q) => $q->where('mr.role_id', $this->filtroRol))
            ->when($this->filtroEstado !== '', fn($q) => $q->where('u.users_estado', (int) $this->filtroEstado))
            ->when($this->buscar, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('p.persona_nombre', 'like', "%{$this->buscar}%")
                          ->orWhere('u.username',      'like', "%{$this->buscar}%")
                          ->orWhere('u.email',          'like', "%{$this->buscar}%");
                });
            })
            ->select('u.id_users', 'u.username', 'u.email', 'u.users_estado', 'u.user_fotografia',
                     'p.id_persona', 'p.persona_nombre', 'p.persona_apellido_paterno',
                     'p.persona_apellido_materno', 'p.persona_dni', 'r.name as rol_nombre', 'r.id as rol_id')
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        // Roles para el formulario (admin no puede asignar superadmin/admin)
        $roles = DB::table('roles')
            ->where('rol_estado', 1)
            ->when($esAdmin, fn($q) => $q->whereNotIn('id', [1, 2]))
            ->get();

        // Roles para el filtro del listado (todos los activos)
        $rolesFiltro = DB::table('roles')->where('rol_estado', 1)->get();

        // Empresas (solo superadmin las necesita)
        $empresas = $esSuperAdmin
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        // Empresa a mostrar en el modal (checkboxes de tiendas)
        $empresaIdParaModal = $esAdmin
            ? $adminEmpresaId
            : ($esSuperAdmin && $this->empresaIdModal > 0 ? $this->empresaIdModal : null);

        $tiendasPorEmpresa = $empresaIdParaModal
            ? DB::table('tiendas as t')
                ->join('empresa as e', 'e.id_empresa', '=', 't.id_empresa')
                ->where('t.tienda_estado', '!=', 0)
                ->whereIn('t.tienda_tipo', [1, 2])
                ->whereNull('t.id_tienda_padre')
                ->where('t.id_empresa', $empresaIdParaModal)
                ->select('e.id_empresa', 'e.empresa_nombrecomercial', 't.id_tienda', 't.tienda_nombre', 't.tienda_tipo')
                ->orderBy('t.id_tienda')
                ->get()
                ->groupBy('id_empresa')
            : collect()->groupBy(fn() => 0);

        // Sucursales para el filtro del listado
        $empresaIdParaFiltro = $esAdmin
            ? $adminEmpresaId
            : ($esSuperAdmin && $this->filtroEmpresa > 0 ? $this->filtroEmpresa : null);

        $sucursalesParaFiltro = $empresaIdParaFiltro
            ? DB::table('tiendas as t')
                ->where('t.tienda_estado', 1)
                ->whereIn('t.tienda_tipo', [1, 2])
                ->whereNull('t.id_tienda_padre')
                ->where('t.id_empresa', $empresaIdParaFiltro)
                ->select('t.id_tienda as id_sucursal', 't.tienda_nombre as sucursal_nombre')
                ->orderBy('t.tienda_nombre')
                ->get()
            : collect();

        return view('livewire.configuracion.usuarios', compact(
            'usuarios', 'roles', 'rolesFiltro', 'empresas', 'tiendasPorEmpresa', 'sucursalesParaFiltro',
            'esAdmin', 'esSuperAdmin'
        ));
    }

    // ── Ordenar ────────────────────────────────────────────────
    public function ordenar(string $columna): void
    {
        if ($this->ordenColumna === $columna) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenColumna   = $columna;
            $this->ordenDireccion = 'asc';
        }
        $this->resetPage();
    }

    // ── Abrir modal nuevo ──────────────────────────────────────
    public function abrirModalNuevo(): void
    {
        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    // ── Abrir modal editar ─────────────────────────────────────
    public function abrirModalEditar(int $id): void
    {
        $this->limpiarFormulario();

        $usuario = DB::table('users as u')
            ->join('persona as p', 'p.id_persona', '=', 'u.id_persona')
            ->leftJoin('model_has_roles as mr', 'mr.model_id', '=', 'u.id_users')
            ->leftJoin('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('u.id_users', $id)
            ->select('u.*', 'p.*', 'r.id as rol_id')
            ->first();

        if (!$usuario) {
            session()->flash('error', 'Usuario no encontrado.');
            return;
        }

        $this->idEditar        = $usuario->id_users;
        $this->idPersona       = $usuario->id_persona;
        $this->dni             = $usuario->persona_dni              ?? '';
        $this->nombre          = $usuario->persona_nombre           ?? '';
        $this->apellidoPaterno = $usuario->persona_apellido_paterno ?? '';
        $this->apellidoMaterno = $usuario->persona_apellido_materno ?? '';
        $this->email           = $usuario->email;
        $this->username        = $usuario->username;
        $this->rolId           = $usuario->rol_id;
        $this->modoEdicion     = true;

        // Cargar tiendas actuales (strings para coincidir con los values HTML)
        $this->tiendasSeleccionadas = DB::table('user_tienda')
            ->where('id_users', $id)
            ->pluck('id_tienda')
            ->map(fn($t) => (string) $t)
            ->toArray();

        // Superadmin: preseleccionar empresa del modal según primera tienda
        if ($this->esSuperAdmin() && !empty($this->tiendasSeleccionadas)) {
            $this->empresaIdModal = (int) (DB::table('tiendas')
                ->where('id_tienda', (int) $this->tiendasSeleccionadas[0])
                ->value('id_empresa') ?? 0);
        }

        $this->dispatch('abrirModal');
    }

    // ── Guardar ────────────────────────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_usuarios.actualizar' : 'gestion_usuarios.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            $rutaFoto = null;

            if ($this->fotoUsuario) {
                $dirDestino    = public_path('usuarios');
                if (!file_exists($dirDestino)) { mkdir($dirDestino, 0755, true); }
                $nombreArchivo = time() . '-' . $this->fotoUsuario->getClientOriginalName();
                file_put_contents($dirDestino . DIRECTORY_SEPARATOR . $nombreArchivo, $this->fotoUsuario->get());
                $rutaFoto = 'usuarios/' . $nombreArchivo;
            }

            if ($this->modoEdicion) {
                DB::table('persona')
                    ->where('id_persona', $this->idPersona)
                    ->update([
                        'persona_nombre'            => $this->nombre,
                        'persona_apellido_paterno'  => $this->apellidoPaterno,
                        'persona_apellido_materno'  => $this->apellidoMaterno,
                        'persona_email'             => $this->email,
                        'persona_dni'               => $this->dni,
                        'updated_at'                => now(),
                    ]);

                $datosUser = [
                    'nombre_users' => $this->nombre,
                    'username'     => $this->username,
                    'email'        => $this->email,
                    'updated_at'   => now(),
                ];

                if ($this->password) {
                    $datosUser['password'] = Hash::make($this->password);
                }
                if ($rutaFoto) {
                    $datosUser['user_fotografia'] = $rutaFoto;
                }

                DB::table('users')->where('id_users', $this->idEditar)->update($datosUser);
                DB::table('model_has_roles')->where('model_id', $this->idEditar)->delete();
                User::find($this->idEditar)->assignRole($this->rolId);

                $idUserFinal = $this->idEditar;
                $mensaje     = 'Usuario actualizado correctamente.';

            } else {
                // Derivar empresa de la tienda seleccionada
                $idEmpresa = $this->empresaIdModal > 0 ? $this->empresaIdModal : 1;
                if ($idEmpresa === 1 && !empty($this->tiendasSeleccionadas)) {
                    $idEmpresaTienda = DB::table('tiendas')
                        ->whereIn('id_tienda', array_map('intval', $this->tiendasSeleccionadas))
                        ->orderBy('id_tienda')
                        ->value('id_empresa');
                    if ($idEmpresaTienda) {
                        $idEmpresa = (int) $idEmpresaTienda;
                    }
                }

                $persona                           = new Persona();
                $persona->id_empresa               = $idEmpresa;
                $persona->persona_nombre           = $this->nombre;
                $persona->persona_apellido_paterno = $this->apellidoPaterno;
                $persona->persona_apellido_materno = $this->apellidoMaterno;
                $persona->persona_email            = $this->email;
                $persona->persona_tipo_documento   = 1;
                $persona->persona_dni              = $this->dni;
                $persona->persona_blacklist        = 'NO';
                $persona->persona_empleado         = 1;
                $persona->person_codigo            = microtime(true);
                $persona->persona_estado           = 1;
                $persona->save();

                $user                  = new User();
                $user->nombre_users    = $this->nombre;
                $user->email           = $this->email;
                $user->password        = Hash::make($this->password);
                $user->username        = $this->username;
                $user->user_fotografia = $rutaFoto ?? 'sin-fotografia.png';
                $user->id_persona      = $persona->id_persona;
                $user->users_estado    = 1;
                $user->save();
                $user->syncRoles($this->rolId);

                $idUserFinal = $user->id_users;
                $mensaje     = 'Usuario creado correctamente.';
            }

            // Sincronizar asociaciones de tiendas
            DB::table('user_tienda')->where('id_users', $idUserFinal)->delete();
            foreach ($this->tiendasSeleccionadas as $idTienda) {
                DB::table('user_tienda')->insert([
                    'id_users'   => $idUserFinal,
                    'id_tienda'  => (int) $idTienda,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el usuario.');
        }
    }

    // ── Confirmar cambio de estado ─────────────────────────────
    public function confirmarCambiarEstado(int $id, int $estado): void
    {
        $this->idCambiarEstado = $id;
        $this->nuevoEstado     = $estado;
        $this->dispatch('abrirModalEstado');
    }

    public function cambiarEstado(): void
    {
        if (!auth()->user()->can('gestion_usuarios.cambiar_estado')) {
            $this->dispatch('cerrarModalEstado');
            session()->flash('error', 'No tienes permiso para cambiar el estado.');
            return;
        }

        try {
            DB::table('users')
                ->where('id_users', $this->idCambiarEstado)
                ->update(['users_estado' => $this->nuevoEstado]);

            $mensaje = $this->nuevoEstado === 1
                ? 'Usuario habilitado correctamente.'
                : 'Usuario deshabilitado correctamente.';

            $this->idCambiarEstado = null;
            $this->nuevoEstado     = null;
            $this->dispatch('cerrarModalEstado');
            session()->flash('success', $mensaje);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al cambiar el estado del usuario.');
        }
    }

    // ── Limpiar formulario ─────────────────────────────────────
    public function limpiarFormulario(): void
    {
        $this->reset([
            'dni', 'nombre', 'apellidoPaterno', 'apellidoMaterno',
            'email', 'username', 'password', 'passwordConfirm',
            'rolId', 'fotoUsuario', 'idEditar', 'idPersona', 'modoEdicion',
            'tiendasSeleccionadas', 'empresaIdModal',
            'buscandoDni', 'dniMensaje', 'dniMensajeTipo',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar(): void         { $this->resetPage(); }
    public function updatingPorPagina(): void      { $this->resetPage(); }
    public function updatingFiltroSucursal(): void { $this->resetPage(); }
    public function updatingFiltroRol(): void      { $this->resetPage(); }
    public function updatingFiltroEstado(): void   { $this->resetPage(); }
}
