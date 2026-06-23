<?php

namespace App\Livewire\GestionVentas;

use App\Models\Cliente;
use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Clientes extends Component
{
    use WithPagination;

    // ── Propiedades del formulario ────────────────────────────
    public $idTipoDocumento  = '';
    public $clienteNumero    = '';
    public $clienteNombre    = '';
    public $clienteTelefono  = '';
    public $clienteDireccion = '';

    // ── Control de modal y edición ────────────────────────────
    public $modoEdicion     = false;
    public $idClienteEditar = null;

    // ── Búsqueda y paginación ─────────────────────────────────
    public $buscar          = '';
    public $porPagina       = 10;
    public $ordenColumna    = 'id_clientes';
    public $ordenDireccion  = 'desc';

    // ── Filtro empresa (superadmin) ───────────────────────────
    public int $filtroEmpresa = 0;

    // ── Empresa en modal (superadmin) ─────────────────────────
    public int $idEmpresaCliente = 0;

    // ── Modal confirmación eliminar ───────────────────────────
    public $idClienteEliminar = null;

    // ── Estado de consulta de documento ───────────────────────
    public $consultandoDocumento = false;
    public $mensajeConsulta      = '';
    public $tipoMensajeConsulta  = '';

    // ── Modelos ───────────────────────────────────────────────
    private $logs;
    private $general;
    private int $cachedRoleId = 0;

    public function boot()
    {
        $this->logs    = new Logs();
        $this->general = new \App\Models\General();

        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverEmpresaActual(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('gestion_de_clientes.listar'), 403);
    }

    // ── Reglas de validación dinámicas ───────────────────────
    protected function rules(): array
    {
        $reglasNumero = ['required'];

        if ($this->idTipoDocumento == 2) {
            $reglasNumero[] = 'digits:8';
        } elseif ($this->idTipoDocumento == 4) {
            $reglasNumero[] = 'digits:11';
        }

        $reglaDireccion = $this->idTipoDocumento == 4
            ? 'required|string|max:255'
            : 'nullable|string|max:255';

        return [
            'idTipoDocumento'  => 'required',
            'clienteNumero'    => $reglasNumero,
            'clienteNombre'    => 'required|string|max:255',
            'clienteTelefono'  => 'nullable|string|max:20',
            'clienteDireccion' => $reglaDireccion,
        ];
    }

    protected function messages(): array
    {
        return [
            'idTipoDocumento.required'  => 'Seleccione un tipo de documento.',
            'clienteNumero.required'    => 'El número de documento es obligatorio.',
            'clienteNumero.digits'      => 'El número debe tener exactamente :digits dígitos.',
            'clienteNombre.required'    => 'El nombre es obligatorio.',
            'clienteDireccion.required' => 'La dirección es obligatoria para RUC.',
        ];
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $tiposDocumento = DB::table('tipo_documento')
            ->where('tipo_documento_estado', 1)
            ->get();

        $columnasPermitidas = [
            'id_clientes', 'cliente_numero', 'cliente_nombre',
            'cliente_razonsocial', 'cliente_telefono', 'cliente_direccion',
        ];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'id_clientes';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $query = DB::table('clientes as c')
            ->select(
                'c.*',
                'td.tipo_documento_identidad',
                'td.tipo_documento_identidad_abr',
                DB::raw("COALESCE(e.empresa_nombrecomercial, e.empresa_razon_social) as empresa_nombre")
            )
            ->join('tipo_documento as td', 'td.id_tipo_documento', '=', 'c.id_tipo_documento')
            ->leftJoin('empresa as e', 'e.id_empresa', '=', 'c.id_empresa')
            ->where('c.cliente_estado', 1)
            ->where(function ($q) {
                $q->where('c.cliente_nombre',       'like', "%{$this->buscar}%")
                  ->orWhere('c.cliente_razonsocial', 'like', "%{$this->buscar}%")
                  ->orWhere('c.cliente_numero',      'like', "%{$this->buscar}%")
                  ->orWhere('c.cliente_telefono',    'like', "%{$this->buscar}%");
            });

        // Filtro por empresa
        if ($this->esSuperAdmin()) {
            if ($this->filtroEmpresa > 0) {
                $query->where('c.id_empresa', $this->filtroEmpresa);
            }
            // filtroEmpresa === 0 → mostrar todos sin filtrar
        } elseif ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($empresaId) {
                $query->where('c.id_empresa', $empresaId);
            }
        }

        $clientes = $query->orderBy("c.{$columna}", $direccion)->paginate($this->porPagina);

        $empresas = $this->esSuperAdmin()
            ? DB::table('empresa')->orderBy('empresa_razon_social')->get()
            : collect();

        return view('livewire.gestion-ventas.clientes', [
            'tiposDocumento' => $tiposDocumento,
            'clientes'       => $clientes,
            'empresas'       => $empresas,
            'esSuperAdmin'   => $this->esSuperAdmin(),
            'esAdmin'        => $this->esAdmin(),
        ]);
    }

    // ── Ordenar columna ───────────────────────────────────────
    public function ordenar($columna)
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
    public function abrirModalNuevo()
    {
        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    // ── Abrir modal edición ───────────────────────────────────
    public function abrirModalEditar($idCliente)
    {
        $this->limpiarFormulario();

        $cliente = Cliente::find($idCliente);
        if (!$cliente) {
            session()->flash('error', 'Cliente no encontrado.');
            return;
        }

        $this->idClienteEditar  = $cliente->id_clientes;
        $this->idTipoDocumento  = $cliente->id_tipo_documento;
        $this->clienteNumero    = $cliente->cliente_numero;
        $this->clienteNombre    = $cliente->cliente_nombre;
        $this->clienteTelefono  = $cliente->cliente_telefono;
        $this->clienteDireccion = $cliente->cliente_direccion;
        $this->idEmpresaCliente = (int) ($cliente->id_empresa ?? 0);
        $this->modoEdicion      = true;
        $this->dispatch('abrirModal');
    }

    // ── Confirmar eliminación ─────────────────────────────────
    public function confirmarEliminar($idCliente)
    {
        $this->idClienteEliminar = $idCliente;
        $this->dispatch('abrirModalEliminar');
    }

    // ── Eliminar (lógico) ─────────────────────────────────────
    public function eliminar()
    {
        try {
            if (!auth()->user()->can('gestion_de_clientes.cambiar_estado')) {
                $this->dispatch('cerrarModalEliminar');
                session()->flash('error', 'No tienes permiso para desactivar clientes.');
                return;
            }
            $cliente = Cliente::find($this->idClienteEliminar);
            if (!$cliente) {
                session()->flash('error', 'Cliente no encontrado.');
                return;
            }

            $cliente->cliente_estado = 0;
            $cliente->save();

            $this->idClienteEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Cliente eliminado correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al eliminar el cliente.');
        }
    }

    // ── Consultar documento ───────────────────────────────────
    public function consultarDocumento()
    {
        $this->mensajeConsulta     = '';
        $this->tipoMensajeConsulta = '';

        $this->validateOnly('idTipoDocumento');
        $this->validateOnly('clienteNumero');

        $this->consultandoDocumento = true;

        try {
            $respCliente = $this->general->consultar_documento_migo(
                $this->idTipoDocumento,
                $this->clienteNumero
            );

            if (empty($respCliente) || ($respCliente['success'] ?? false) !== true || empty($respCliente['data'])) {
                $this->mensajeConsulta     = $respCliente['message'] ?? 'No se encontró información para el número de documento ingresado.';
                $this->tipoMensajeConsulta = 'error';
            } else {
                $data = $respCliente['data'];
                $this->clienteNombre    = $data['nombre']    ?? $this->clienteNombre;
                $this->clienteDireccion = $data['direccion'] ?? $this->clienteDireccion;

                $this->mensajeConsulta     = $respCliente['message'] ?? 'Datos encontrados.';
                $this->tipoMensajeConsulta = 'success';

                if (isset($respCliente['warning'])) {
                    $this->mensajeConsulta     = $respCliente['warning'];
                    $this->tipoMensajeConsulta = 'warning';
                }
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->mensajeConsulta     = 'Ocurrió un error al consultar el documento.';
            $this->tipoMensajeConsulta = 'error';
        }

        $this->consultandoDocumento = false;
    }

    // ── Guardar (crear o editar) ──────────────────────────────
    public function guardar()
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_de_clientes.actualizar' : 'gestion_de_clientes.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            if ($this->modoEdicion) {
                $cliente = Cliente::find($this->idClienteEditar);
                if (!$cliente) {
                    DB::rollBack();
                    session()->flash('error', 'Cliente no encontrado.');
                    return;
                }
            } else {
                $cliente = new Cliente();
                $cliente->cliente_fecha  = now();
                $cliente->cliente_estado = 1;
            }

            $cliente->id_tipo_documento   = $this->idTipoDocumento;
            $cliente->cliente_numero      = $this->clienteNumero;
            $cliente->cliente_nombre      = $this->clienteNombre;
            $cliente->cliente_razonsocial = $this->clienteNombre;
            $cliente->cliente_telefono    = $this->clienteTelefono ?: null;
            $cliente->cliente_direccion   = $this->clienteDireccion;

            // Auto-asignar empresa solo al crear (no sobreescribir en edición)
            if (!$this->modoEdicion) {
                $cliente->id_empresa = $this->resolverEmpresaActual();
            }

            $cliente->save();

            DB::commit();

            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $this->modoEdicion
                ? 'Cliente actualizado correctamente.'
                : 'Cliente registrado correctamente.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el cliente.');
        }
    }

    // ── Limpiar formulario ────────────────────────────────────
    public function limpiarFormulario()
    {
        $this->reset([
            'idTipoDocumento', 'clienteNumero', 'clienteNombre',
            'clienteTelefono', 'clienteDireccion', 'idClienteEditar',
            'modoEdicion', 'mensajeConsulta', 'tipoMensajeConsulta',
            'consultandoDocumento', 'idEmpresaCliente',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // ── Exportar Excel ────────────────────────────────────────
    public function exportarExcel(): mixed
    {
        $params = http_build_query([
            'buscar'        => $this->buscar,
            'filtroEmpresa' => $this->filtroEmpresa,
        ]);
        return $this->redirect(route('Gestionventas.exportar_clientes_excel') . '?' . $params, navigate: false);
    }

    // ── Reset paginación al buscar o cambiar por página ───────
    public function updatingBuscar(): void      { $this->resetPage(); }
    public function updatingPorPagina(): void   { $this->resetPage(); }
    public function updatingFiltroEmpresa(): void { $this->resetPage(); }
}
