<?php

namespace App\Livewire\Gestion;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\WithPagination;

class Proveedores extends Component
{
    use WithPagination;

    // ── Formulario ────────────────────────────────────────────
    public ?int   $idTipoDocumento   = null;
    public string $numeroDocumento   = '';
    public string $proveedorNombre   = '';
    public string $proveedorDireccion = '';
    public string $proveedorContacto = '';
    public string $proveedorCargo    = '';
    public string $proveedorTelefono = '';
    public string $proveedorCorreo   = '';
    public int    $empresaIdModal    = 0;

    // ── Documento lookup ──────────────────────────────────────
    public bool   $buscandoDocumento    = false;
    public string $documentoMensaje     = '';
    public string $documentoMensajeTipo = '';

    // ── Control modal CRUD ────────────────────────────────────
    public bool $modoEdicion = false;
    public ?int $idEditar    = null;
    public ?int $idEliminar  = null;

    // ── Búsqueda, filtros y paginación ────────────────────────
    public string $buscar         = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'p.id_proveedores';
    public string $ordenDireccion = 'desc';
    public int    $filtroEmpresa  = 0;

    private ?Logs $logs         = null;
    private int   $cachedRoleId = 0;

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
        abort_if(!auth()->user()->can('gestion_proveedores.listar'), 403);
    }

    // ── Helpers de rol ────────────────────────────────────────
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }
    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')
            ->value('s.id_empresa');
        return $id ? (int) $id : null;
    }

    // ── Validación ────────────────────────────────────────────
    protected function rules(): array
    {
        $exceptDocumento = $this->idEditar
            ? ",{$this->idEditar},id_proveedores"
            : '';

        return [
            'idTipoDocumento'    => 'required|integer|exists:tipo_documento,id_tipo_documento',
            'numeroDocumento'    => "required|string|max:20|unique:proveedores,proveedores_numero_documento{$exceptDocumento}",
            'proveedorNombre'    => 'required|string|max:255',
            'proveedorDireccion' => 'nullable|string|max:255',
            'proveedorContacto'  => 'nullable|string|max:255',
            'proveedorCargo'     => 'nullable|string|max:255',
            'proveedorTelefono'  => 'nullable|string|max:20',
            'proveedorCorreo'    => 'nullable|email|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'idTipoDocumento.required'  => 'Selecciona el tipo de documento.',
            'idTipoDocumento.exists'    => 'El tipo de documento no es válido.',
            'numeroDocumento.required'  => 'El número de documento es obligatorio.',
            'numeroDocumento.unique'    => 'Este número de documento ya está registrado.',
            'proveedorNombre.required'  => 'El nombre o razón social es obligatorio.',
            'proveedorCorreo.email'     => 'Ingresa un correo electrónico válido.',
        ];
    }

    // ── Consulta de documento (DNI / RUC) ─────────────────────
    public function updatedNumeroDocumento(string $valor): void
    {
        $this->documentoMensaje     = '';
        $this->documentoMensajeTipo = '';
        $longitud = strlen(trim($valor));

        if (ctype_digit(trim($valor))) {
            if ($longitud === 8)  $this->buscarDocumento();
            if ($longitud === 11) $this->buscarDocumento();
        }
    }

    public function buscarDocumento(): void
    {
        $numero   = trim($this->numeroDocumento);
        $longitud = strlen($numero);

        if (!ctype_digit($numero) || !in_array($longitud, [8, 11])) {
            $this->documentoMensaje     = 'Ingresa un DNI (8 dígitos) o RUC (11 dígitos) válido.';
            $this->documentoMensajeTipo = 'error';
            return;
        }

        $this->buscandoDocumento    = true;
        $this->documentoMensaje     = '';
        $this->documentoMensajeTipo = '';

        try {
            if ($longitud === 8) {
                $this->consultarDni($numero);
            } else {
                $this->consultarRuc($numero);
            }
        } finally {
            $this->buscandoDocumento = false;
        }
    }

    private function consultarDni(string $dni): void
    {
        try {
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->asForm()
                ->post('https://api.migo.pe/api/v1/dni', [
                    'token' => config('services.tokens.api_migo'),
                    'dni'   => $dni,
                ]);
            $data = $response->json();

            if ($data['success'] ?? false) {
                $this->proveedorNombre      = mb_convert_case(strtolower(trim($data['nombre'])), MB_CASE_TITLE, 'UTF-8');
                $this->documentoMensaje     = 'Datos del DNI encontrados correctamente.';
                $this->documentoMensajeTipo = 'success';
            } else {
                $this->documentoMensaje     = $data['message'] ?? 'DNI no encontrado.';
                $this->documentoMensajeTipo = 'error';
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->documentoMensaje     = 'Error al consultar el servicio de DNI.';
            $this->documentoMensajeTipo = 'error';
        }
    }

    private function consultarRuc(string $ruc): void
    {
        try {
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->asForm()
                ->post('https://api.migo.pe/api/v1/ruc', [
                    'token' => config('services.tokens.api_migo'),
                    'ruc'   => $ruc,
                ]);
            $data = $response->json();

            if ($data['success'] ?? false) {
                $this->proveedorNombre    = mb_convert_case(strtolower($data['nombre_o_razon_social'] ?? ''), MB_CASE_TITLE, 'UTF-8');
                $this->proveedorDireccion = mb_convert_case(strtolower($data['direccion_simple']       ?? ''), MB_CASE_TITLE, 'UTF-8');
                $this->documentoMensaje     = 'Datos del RUC encontrados correctamente.';
                $this->documentoMensajeTipo = 'success';
            } else {
                $this->documentoMensaje     = $data['message'] ?? 'RUC no encontrado.';
                $this->documentoMensajeTipo = 'error';
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->documentoMensaje     = 'Error al consultar el servicio de RUC.';
            $this->documentoMensajeTipo = 'error';
        }
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $esAdmin        = $this->esAdmin();
        $esSuperAdmin   = $this->esSuperAdmin();
        $adminEmpresaId = $esAdmin ? $this->adminEmpresaId() : null;
        $filtroEmpresa  = $this->filtroEmpresa;

        $columnasPermitidas = ['p.id_proveedores', 'p.proveedores_nombre', 'p.proveedores_numero_documento'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'p.id_proveedores';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $proveedores = DB::table('proveedores as p')
            ->join('tipo_documento as td', 'td.id_tipo_documento', '=', 'p.id_tipo_documento')
            ->leftJoin('empresa as e', 'e.id_empresa', '=', 'p.id_empresa')
            ->where('p.proveedores_estado', 1)
            ->when($esSuperAdmin && $filtroEmpresa > 0, fn($q) => $q->where('p.id_empresa', $filtroEmpresa))
            ->when($this->buscar, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('p.proveedores_nombre',           'like', "%{$this->buscar}%")
                          ->orWhere('p.proveedores_numero_documento', 'like', "%{$this->buscar}%");
                });
            })
            ->select('p.*', 'td.tipo_documento_identidad_abr', 'e.empresa_nombrecomercial')
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        $tiposDocumento = DB::table('tipo_documento')
            ->where('tipo_documento_estado', 1)
            ->orderBy('id_tipo_documento')
            ->get();

        $empresas = $esSuperAdmin
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        return view('livewire.gestion.proveedores', compact(
            'proveedores', 'tiposDocumento', 'empresas', 'esAdmin', 'esSuperAdmin'
        ));
    }

    // ── Ordenar ───────────────────────────────────────────────
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

    // ── Abrir modal nuevo ─────────────────────────────────────
    public function abrirModalNuevo(): void
    {
        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    // ── Abrir modal editar ────────────────────────────────────
    public function abrirModalEditar(int $id): void
    {
        $this->limpiarFormulario();
        $proveedor = DB::table('proveedores')->where('id_proveedores', $id)->first();
        if (!$proveedor) {
            session()->flash('error', 'Proveedor no encontrado.');
            return;
        }
        $this->idEditar           = $proveedor->id_proveedores;
        $this->idTipoDocumento    = $proveedor->id_tipo_documento;
        $this->numeroDocumento    = $proveedor->proveedores_numero_documento;
        $this->proveedorNombre    = $proveedor->proveedores_nombre;
        $this->proveedorDireccion = $proveedor->proveedores_direccion ?? '';
        $this->proveedorContacto  = $proveedor->proveedores_nombre_contacto ?? '';
        $this->proveedorCargo     = $proveedor->proveedores_cargo ?? '';
        $this->proveedorTelefono  = $proveedor->proveedores_telefono ?? '';
        $this->proveedorCorreo    = $proveedor->proveedores_correo ?? '';
        $this->empresaIdModal     = (int) ($proveedor->id_empresa ?? 0);
        $this->modoEdicion        = true;
        $this->dispatch('abrirModal');
    }

    // ── Confirmar eliminar ────────────────────────────────────
    public function confirmarEliminar(int $id): void
    {
        $this->idEliminar = $id;
        $this->dispatch('abrirModalEliminar');
    }

    // ── Eliminar (lógico) ─────────────────────────────────────
    public function eliminar(): void
    {
        if (!auth()->user()->can('gestion_proveedores.cambiar_estado')) {
            $this->dispatch('cerrarModalEliminar');
            session()->flash('error', 'No tienes permiso para desactivar proveedores.');
            return;
        }

        try {
            DB::table('proveedores')
                ->where('id_proveedores', $this->idEliminar)
                ->update(['proveedores_estado' => 0, 'updated_at' => now()]);
            $this->idEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Proveedor eliminado correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al eliminar el proveedor.');
        }
    }

    // ── Guardar ───────────────────────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_proveedores.actualizar' : 'gestion_proveedores.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            $datos = [
                'id_empresa'                     => null,
                'id_tipo_documento'              => $this->idTipoDocumento,
                'proveedores_numero_documento'   => $this->numeroDocumento,
                'proveedores_nombre'             => $this->proveedorNombre,
                'proveedores_direccion'          => $this->proveedorDireccion ?: null,
                'proveedores_nombre_contacto'    => $this->proveedorContacto  ?: null,
                'proveedores_cargo'              => $this->proveedorCargo     ?: null,
                'proveedores_telefono'           => $this->proveedorTelefono  ?: null,
                'proveedores_correo'             => $this->proveedorCorreo    ?: null,
                'updated_at'                     => now(),
            ];

            if ($this->modoEdicion) {
                DB::table('proveedores')->where('id_proveedores', $this->idEditar)->update($datos);
                $mensaje = 'Proveedor actualizado correctamente.';
            } else {
                $datos['proveedores_estado'] = 1;
                $datos['created_at']         = now();
                DB::table('proveedores')->insert($datos);
                $mensaje = 'Proveedor creado correctamente.';
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el proveedor.');
        }
    }

    // ── Limpiar formulario ────────────────────────────────────
    public function limpiarFormulario(): void
    {
        $this->reset([
            'idTipoDocumento', 'numeroDocumento', 'proveedorNombre', 'proveedorDireccion',
            'proveedorContacto', 'proveedorCargo', 'proveedorTelefono', 'proveedorCorreo',
            'empresaIdModal', 'idEditar', 'modoEdicion',
            'documentoMensaje', 'documentoMensajeTipo', 'buscandoDocumento',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar(): void        { $this->resetPage(); }
    public function updatingPorPagina(): void     { $this->resetPage(); }
    public function updatingFiltroEmpresa(): void { $this->resetPage(); }
}
