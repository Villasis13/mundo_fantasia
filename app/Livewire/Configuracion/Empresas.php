<?php

namespace App\Livewire\Configuracion;

use App\Models\General;
use App\Models\Logs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class Empresas extends Component
{
    use WithPagination;
    use WithFileUploads;

    private $logs;
    private $general;

    public function boot()
    {
        $this->logs    = new Logs();
        $this->general = new General();
    }

    // ── Filtro por grupo ──────────────────────────────────────
    public $idGrupo     = null;
    public $nombreGrupo = null;

    public function mount($idGrupo = null, $nombreGrupo = null): void
    {
        abort_if(!auth()->user()->can('opcion_gestion_empresas.listar'), 403);
        $this->idGrupo     = $idGrupo;
        $this->nombreGrupo = $nombreGrupo;
    }

    // ══════════════════════════════════════════════════════════
    //  PROPIEDADES — Formulario empresa
    // ══════════════════════════════════════════════════════════
    public $empresaRazonSocial      = '';
    public $empresaNombreComercial  = '';
    public $empresaDescripcion      = '';
    public $empresaRuc              = '';
    public $empresaDomicilioFiscal  = '';
    public $empresaPais             = 'Perú';
    public $empresaTelefono1        = '';
    public $empresaTelefono2        = '';
    public $empresaFoto             = null;
    public $empresaFotoActual       = '';
    public $empresaCorreo           = '';
    public $empresaUsuarioSol       = '';
    public $empresaClaveSol         = '';
    public $empresaRutaCertificado  = '';
    public $empresaClaveCertificado = '';
    // Certificado .pfx
    public $archivoCert           = null;
    public string $certPassword   = '';
    public string $certVencimiento = '';
    public string $certPfxActual  = '';
    public string $certErrorMsg   = '';
    public string $certContenido  = '';  // base64 del archivo

    // Credenciales SIRE
    public string $empresaSireClientId     = '';
    public string $empresaSireClientSecret = '';
    public $empresaEstado           = '1';
    public $idUbigeo                = null;

    // ── Ubigeo buscador ───────────────────────────────────────
    public $buscarUbigeo       = '';
    public $ubigeoSeleccionado = null;
    public $mostrarListaUbigeo = false;

    // ── Control modal empresa ─────────────────────────────────
    public $modoEdicion     = false;
    public $idEmpresaEditar = null;
    public $idEmpresaEliminar = null;

    // ── Consulta RUC ──────────────────────────────────────────
    public $consultandoRuc     = false;
    public $mensajeConsultaRuc = '';
    public $tipoMensajeRuc     = '';

    // ══════════════════════════════════════════════════════════
    //  PROPIEDADES — Gestión de planes
    // ══════════════════════════════════════════════════════════
    public $idEmpresaPlan      = null;
    public $nombreEmpresaPlan  = '';
    public $idPlanSeleccionado = null;
    public $fechaInicioPlan    = '';
    public $montoPagadoPlan    = '';
    public $observacionPlan    = '';

    // ── Búsqueda y paginación ─────────────────────────────────
    public $buscar         = '';
    public $porPagina      = 10;
    public $ordenColumna   = 'id_empresa';
    public $ordenDireccion = 'asc';

    // ══════════════════════════════════════════════════════════
    //  VALIDACIONES
    // ══════════════════════════════════════════════════════════
    protected function rules(): array
    {
        $reglasImagen = $this->modoEdicion
            ? ['nullable', 'image', 'max:2048']
            : ['required', 'image', 'max:2048'];

        return [
            'empresaRazonSocial'      => 'required|string|max:255',
            'empresaNombreComercial'  => 'required|string|max:255',
            'empresaDescripcion'      => 'nullable|string|max:255',
            'empresaRuc'              => [
                'required', 'string', 'size:11', 'regex:/^[0-9]+$/',
                $this->modoEdicion
                    ? \Illuminate\Validation\Rule::unique('empresa', 'empresa_ruc')->ignore($this->idEmpresaEditar, 'id_empresa')->whereNot('empresa_estado', '0')
                    : \Illuminate\Validation\Rule::unique('empresa', 'empresa_ruc')->whereNot('empresa_estado', '0'),
            ],
            'empresaDomicilioFiscal'  => 'required|string|max:255',
            'empresaPais'             => 'required|string|max:255',
            'empresaTelefono1'        => 'nullable|string|max:50',
            'empresaTelefono2'        => 'nullable|string|max:50',
            'empresaFoto'             => $reglasImagen,
            'empresaCorreo'           => 'nullable|email|max:255',
            'empresaUsuarioSol'       => 'required|string|max:50',
            'empresaClaveSol'         => 'required|string|max:50',
            'empresaRutaCertificado'  => 'nullable|string',
            'empresaClaveCertificado' => 'nullable|string',
            'idUbigeo'                => 'nullable|exists:ubigeo,id_ubigeo',
        ];
    }

    protected function messages(): array
    {
        return [
            'empresaRazonSocial.required'     => 'La razón social es obligatoria.',
            'empresaNombreComercial.required'  => 'El nombre comercial es obligatorio.',
            'empresaRuc.required'              => 'El RUC es obligatorio.',
            'empresaRuc.size'                  => 'El RUC debe tener exactamente 11 dígitos.',
            'empresaRuc.regex'                 => 'El RUC debe contener solo números.',
            'empresaRuc.unique'                => 'Ya existe una empresa registrada con ese RUC.',
            'empresaDomicilioFiscal.required'  => 'El domicilio fiscal es obligatorio.',
            'empresaPais.required'             => 'El país es obligatorio.',
            'empresaFoto.required'             => 'El logo de la empresa es obligatorio.',
            'empresaFoto.image'                => 'El logo debe ser una imagen.',
            'empresaFoto.max'                  => 'El logo no puede superar 2MB.',
            'empresaUsuarioSol.required'       => 'El usuario SOL es obligatorio.',
            'empresaClaveSol.required'         => 'La clave SOL es obligatoria.',
            'empresaCorreo.email'              => 'El correo no tiene un formato válido.',
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  RENDER
    // ══════════════════════════════════════════════════════════
    public function render()
    {
        $columnasPermitidas = ['id_empresa', 'empresa_razon_social', 'empresa_ruc'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'id_empresa';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $empresas = DB::table('empresa as e')
            ->select(
                'e.*',
                'u.ubigeo_departamento', 'u.ubigeo_provincia', 'u.ubigeo_distrito',
                DB::raw('(SELECT COUNT(*) FROM sucursals WHERE id_empresa = e.id_empresa AND deleted_at IS NULL AND sucursal_tipo = 1) as count_tiendas_suc'),
                DB::raw('(SELECT COUNT(*) FROM sucursals WHERE id_empresa = e.id_empresa AND deleted_at IS NULL AND sucursal_tipo = 2) as count_sucursales_suc'),
                DB::raw('(SELECT COUNT(*) FROM sucursals WHERE id_empresa = e.id_empresa AND deleted_at IS NULL AND sucursal_tipo = 3) as count_almacenes_suc'),
                DB::raw('(SELECT COUNT(*) FROM tiendas WHERE id_empresa = e.id_empresa AND tienda_estado != 0) as count_tiendas')
            )
            ->leftJoin('ubigeo as u', 'u.id_ubigeo', '=', 'e.id_ubigeo')
            ->when($this->idGrupo, fn($q) => $q->where('e.id_grupo', $this->idGrupo))
            ->where(function ($q) {
                $q->where('e.empresa_razon_social',    'like', "%{$this->buscar}%")
                    ->orWhere('e.empresa_ruc',            'like', "%{$this->buscar}%")
                    ->orWhere('e.empresa_nombrecomercial','like', "%{$this->buscar}%");
            })
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        // Ubigeos filtrados para el buscador
        $ubigeosFiltrados = collect();
        if (strlen($this->buscarUbigeo) >= 2) {
            $ubigeosFiltrados = DB::table('ubigeo')
                ->where(function ($q) {
                    $q->where('ubigeo_departamento', 'like', "%{$this->buscarUbigeo}%")
                        ->orWhere('ubigeo_provincia',   'like', "%{$this->buscarUbigeo}%")
                        ->orWhere('ubigeo_distrito',    'like', "%{$this->buscarUbigeo}%");
                })
                ->limit(10)
                ->get();
        }

        // Planes activos disponibles para asignar
        $planes = DB::table('planes')
            ->where('plan_estado', 1)
            ->orderBy('plan_nombre')
            ->get();

        return view('livewire.configuracion.empresas', compact(
            'empresas', 'ubigeosFiltrados', 'planes'
        ));
    }

    // ══════════════════════════════════════════════════════════
    //  UBIGEO BUSCADOR
    // ══════════════════════════════════════════════════════════
    public function updatingBuscarUbigeo()
    {
        $this->mostrarListaUbigeo = true;
    }

    public function seleccionarUbigeo($idUbigeo, $label)
    {
        $this->idUbigeo           = $idUbigeo;
        $this->ubigeoSeleccionado = ['id' => $idUbigeo, 'label' => $label];
        $this->buscarUbigeo       = '';
        $this->mostrarListaUbigeo = false;
    }

    public function limpiarUbigeo()
    {
        $this->idUbigeo           = null;
        $this->ubigeoSeleccionado = null;
        $this->buscarUbigeo       = '';
        $this->mostrarListaUbigeo = false;
    }

    // ══════════════════════════════════════════════════════════
    //  CONSULTA RUC
    // ══════════════════════════════════════════════════════════
    public function consultarRuc()
    {
        $this->mensajeConsultaRuc = '';
        $this->tipoMensajeRuc     = '';
        $this->validateOnly('empresaRuc');
        $this->consultandoRuc = true;

        try {
            $respuesta = $this->general->consultar_documento_migo(4, $this->empresaRuc);

            if (empty($respuesta) || ($respuesta['success'] ?? false) !== true || empty($respuesta['data'])) {
                $this->mensajeConsultaRuc = $respuesta['message'] ?? 'No se encontró información para el RUC ingresado.';
                $this->tipoMensajeRuc     = 'error';
            } else {
                $data = $respuesta['data'];
                $this->empresaRazonSocial     = $data['nombre']    ?? $this->empresaRazonSocial;
                $this->empresaDomicilioFiscal = $data['direccion'] ?? $this->empresaDomicilioFiscal;
                $this->mensajeConsultaRuc     = $respuesta['message'] ?? 'Datos encontrados.';
                $this->tipoMensajeRuc         = 'success';

                if (isset($respuesta['warning'])) {
                    $this->mensajeConsultaRuc = $respuesta['warning'];
                    $this->tipoMensajeRuc     = 'warning';
                }
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->mensajeConsultaRuc = 'Ocurrió un error al consultar el RUC.';
            $this->tipoMensajeRuc     = 'error';
        }

        $this->consultandoRuc = false;
    }

    // ══════════════════════════════════════════════════════════
    //  CRUD EMPRESA
    // ══════════════════════════════════════════════════════════
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

    public function abrirModalNuevo()
    {
        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    public function abrirModalEditar($idEmpresa)
    {
        $this->limpiarFormulario();

        $empresa = DB::table('empresa')->where('id_empresa', $idEmpresa)->first();
        if (!$empresa) {
            session()->flash('error', 'Empresa no encontrada.');
            return;
        }

        $this->idEmpresaEditar         = $empresa->id_empresa;
        $this->empresaRazonSocial      = $empresa->empresa_razon_social;
        $this->empresaNombreComercial  = $empresa->empresa_nombrecomercial ?? '';
        $this->empresaDescripcion      = $empresa->empresa_descripcion ?? '';
        $this->empresaRuc              = $empresa->empresa_ruc;
        $this->empresaDomicilioFiscal  = $empresa->empresa_domiciliofiscal;
        $this->empresaPais             = $empresa->empresa_pais;
        $this->empresaTelefono1        = $empresa->empresa_telefono1 ?? '';
        $this->empresaTelefono2        = $empresa->empresa_telefono2 ?? '';
        $this->empresaFotoActual       = $empresa->empresa_foto ?? '';
        $this->empresaCorreo           = $empresa->empresa_correo ?? '';
        $this->empresaUsuarioSol       = $empresa->empresa_usuario_sol;
        $this->empresaClaveSol         = $empresa->empresa_clave_sol;
        $this->empresaRutaCertificado  = $empresa->empresa_ruta_certificado ?? '';
        $this->empresaClaveCertificado = $empresa->empresa_clave_certificado ?? '';
        $this->certPfxActual           = $empresa->empresa_cert_pfx ?? '';
        $this->certPassword            = $empresa->empresa_cert_password ?? '';
        $this->certVencimiento         = $empresa->empresa_cert_vencimiento ?? '';
        $this->empresaSireClientId     = $empresa->empresa_sire_client_id ?? '';
        $this->empresaSireClientSecret = $empresa->empresa_sire_client_secret ?? '';
        $this->empresaEstado           = $empresa->empresa_estado;
        $this->idUbigeo                = $empresa->id_ubigeo;

        if ($empresa->id_ubigeo) {
            $ub = DB::table('ubigeo')->where('id_ubigeo', $empresa->id_ubigeo)->first();
            if ($ub) {
                $this->ubigeoSeleccionado = [
                    'id'    => $ub->id_ubigeo,
                    'label' => "{$ub->ubigeo_departamento} / {$ub->ubigeo_provincia} / {$ub->ubigeo_distrito}",
                ];
            }
        }

        $this->modoEdicion = true;
        $this->dispatch('abrirModal');
    }

    public function confirmarEliminar($idEmpresa)
    {
        $this->idEmpresaEliminar = $idEmpresa;
        $this->dispatch('abrirModalEliminar');
    }

    public function eliminar()
    {
        try {
            if (!auth()->user()->can('opcion_gestion_empresas.cambiar_estado')) {
                $this->dispatch('cerrarModalEliminar');
                session()->flash('error', 'No tienes permiso para desactivar empresas.');
                return;
            }

            DB::beginTransaction();

            // Obtener sucursales activas de la empresa
            $idsSucursales = DB::table('sucursals')
                ->where('id_empresa', $this->idEmpresaEliminar)
                ->whereNull('deleted_at')
                ->pluck('id_sucursal');

            // Cascada: desactivar cajas de esas sucursales
            if ($idsSucursales->isNotEmpty()) {
                DB::table('caja_numero')
                    ->whereIn('id_sucursal', $idsSucursales)
                    ->update(['caja_numero_estado' => 0, 'updated_at' => now()]);
            }

            // Cascada: soft-delete sucursales
            DB::table('sucursals')
                ->where('id_empresa', $this->idEmpresaEliminar)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            // Cascada: desactivar tiendas
            DB::table('tiendas')
                ->where('id_empresa', $this->idEmpresaEliminar)
                ->where('tienda_estado', '!=', 0)
                ->update(['tienda_estado' => 0, 'updated_at' => now()]);

            // Desactivar la empresa
            DB::table('empresa')
                ->where('id_empresa', $this->idEmpresaEliminar)
                ->update(['empresa_estado' => '0', 'updated_at' => now()]);

            DB::commit();

            $this->idEmpresaEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Empresa desactivada correctamente junto con sus sucursales, tiendas y cajas.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al eliminar la empresa.');
        }
    }

    public function habilitarEmpresa($idEmpresa)
    {
        try {
            if (!auth()->user()->can('opcion_gestion_empresas.cambiar_estado')) {
                session()->flash('error', 'No tienes permiso para habilitar empresas.');
                return;
            }

            DB::table('empresa')
                ->where('id_empresa', $idEmpresa)
                ->update(['empresa_estado' => '1', 'updated_at' => now()]);

            session()->flash('success', 'Empresa habilitada correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al habilitar la empresa.');
        }
    }

    public function updatedArchivoCert(): void
    {
        $this->certContenido = '';
        if (!$this->archivoCert) return;

        $contenido = file_get_contents($this->archivoCert->getRealPath());
        if ($contenido !== false) {
            $this->certContenido = base64_encode($contenido);
        }
    }

    public function eliminarCertificado(int $idEmpresa): void
    {
        if (!auth()->user()->can('opcion_gestion_empresas.actualizar')) {
            session()->flash('error', 'No tienes permiso para esta acción.');
            return;
        }

        $empresa = DB::table('empresa')->where('id_empresa', $idEmpresa)->first();
        if (!$empresa) {
            session()->flash('error', 'Empresa no encontrada.');
            return;
        }

        if ($empresa->empresa_cert_pfx && \Storage::disk('local')->exists($empresa->empresa_cert_pfx)) {
            \Storage::disk('local')->delete($empresa->empresa_cert_pfx);
        }

        DB::table('empresa')->where('id_empresa', $idEmpresa)->update([
            'empresa_cert_pfx'        => null,
            'empresa_cert_password'   => null,
            'empresa_cert_vencimiento'=> null,
            'updated_at'              => now(),
        ]);

        session()->flash('success', 'Certificado eliminado correctamente.');
    }

    public function descargarCertificado(int $idEmpresa)
    {
        if (!auth()->user()->can('opcion_gestion_empresas.actualizar')) {
            session()->flash('error', 'No tienes permiso para esta acción.');
            return;
        }

        $empresa = DB::table('empresa')->where('id_empresa', $idEmpresa)->first();
        if (!$empresa || !$empresa->empresa_cert_pfx) {
            session()->flash('error', 'Esta empresa no tiene certificado registrado.');
            return;
        }

        if (!\Storage::disk('local')->exists($empresa->empresa_cert_pfx)) {
            session()->flash('error', 'El archivo del certificado no se encontró en el servidor.');
            return;
        }

        return \Storage::disk('local')->download(
            $empresa->empresa_cert_pfx,
            'certificado_' . $empresa->empresa_ruc . '.pfx'
        );
    }

    public function guardar()
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'opcion_gestion_empresas.actualizar' : 'opcion_gestion_empresas.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            $rutaFoto = $this->empresaFotoActual;
            if ($this->empresaFoto) {
                $rutaFoto = $this->general->save_files($this->empresaFoto, 'configuration/empresas');
            }

            // Guardar .pfx en storage/app/certificados/ (fuera del webroot)
            $pfxPath = $this->certPfxActual;
            if ($this->certContenido !== '') {
                $ruc      = preg_replace('/\D/', '', $this->empresaRuc);
                $filename = 'cert_' . $ruc . '_' . time() . '.pfx';
                \Storage::disk('local')->put('certificados/' . $filename, base64_decode($this->certContenido));
                $pfxPath = 'certificados/' . $filename;
            }

            $datos = [
                'empresa_razon_social'      => $this->empresaRazonSocial,
                'empresa_nombrecomercial'   => $this->empresaNombreComercial,
                'empresa_descripcion'       => $this->empresaDescripcion,
                'empresa_ruc'               => $this->empresaRuc,
                'empresa_domiciliofiscal'   => $this->empresaDomicilioFiscal,
                'empresa_pais'              => $this->empresaPais,
                'empresa_telefono1'         => $this->empresaTelefono1,
                'empresa_telefono2'         => $this->empresaTelefono2,
                'empresa_foto'              => $rutaFoto,
                'empresa_foto_ticket'       => $rutaFoto,
                'empresa_correo'            => $this->empresaCorreo,
                'empresa_usuario_sol'       => $this->empresaUsuarioSol,
                'empresa_clave_sol'         => $this->empresaClaveSol,
                'empresa_ruta_certificado'  => $this->empresaRutaCertificado ?: null,
                'empresa_clave_certificado' => $this->empresaClaveCertificado ?: null,
                'empresa_cert_pfx'              => $pfxPath ?: null,
                'empresa_cert_password'         => $this->certPassword ?: null,
                'empresa_cert_vencimiento'      => $this->certVencimiento ?: null,
                'empresa_sire_client_id'        => $this->empresaSireClientId ?: null,
                'empresa_sire_client_secret'    => $this->empresaSireClientSecret ?: null,
                'empresa_estado'            => '1',
                'id_ubigeo'                 => $this->idUbigeo,
                'id_grupo'                  => 1,
                'updated_at'                => now(),
            ];

            if ($this->modoEdicion) {
                DB::table('empresa')->where('id_empresa', $this->idEmpresaEditar)->update($datos);
            } else {
                $datos['created_at'] = now();
                $idEmpresa = DB::table('empresa')->insertGetId($datos);
                $this->generarSeriesParaEmpresa($idEmpresa);
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $this->modoEdicion
                ? 'Empresa actualizada correctamente.'
                : 'Empresa registrada correctamente con sus series de comprobantes.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la empresa.');
        }
    }

    // ── Generar las 6 series estándar al crear una empresa ────────
    private function generarSeriesParaEmpresa(int $idEmpresa): void
    {
        $fecha = now()->format('Ymd');

        $series = [
            ['tipocomp' => '07', 'serie' => 'FN01'],
            ['tipocomp' => '07', 'serie' => 'BN01'],
            ['tipocomp' => '08', 'serie' => 'FD01'],
            ['tipocomp' => '08', 'serie' => 'BD01'],
            ['tipocomp' => 'RC', 'serie' =>  $fecha],
            ['tipocomp' => 'RA', 'serie' =>  $fecha],
        ];

        foreach ($series as $s) {
            DB::table('serie')->insert([
                'id_empresa'     => $idEmpresa,
                'id_caja_numero' => null,
                'tipocomp'       => $s['tipocomp'],
                'serie'          => $s['serie'],
                'correlativo'    => 0,
                'estado'         => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function limpiarFormulario()
    {
        $this->reset([
            'empresaRazonSocial', 'empresaNombreComercial', 'empresaDescripcion',
            'empresaRuc', 'empresaDomicilioFiscal', 'empresaTelefono1', 'empresaTelefono2',
            'empresaFoto', 'empresaFotoActual', 'empresaCorreo',
            'empresaUsuarioSol', 'empresaClaveSol',
            'empresaRutaCertificado', 'empresaClaveCertificado',
            'archivoCert', 'certPassword', 'certVencimiento', 'certPfxActual', 'certErrorMsg', 'certContenido',
            'empresaSireClientId', 'empresaSireClientSecret',
            'idUbigeo', 'ubigeoSeleccionado', 'buscarUbigeo', 'mostrarListaUbigeo',
            'idEmpresaEditar', 'modoEdicion',
            'mensajeConsultaRuc', 'tipoMensajeRuc', 'consultandoRuc',
        ]);
        $this->empresaPais   = 'Perú';
        $this->empresaEstado = '1';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // ══════════════════════════════════════════════════════════
    //  GESTIÓN DE PLANES
    // ══════════════════════════════════════════════════════════
    public function abrirModalPlan($idEmpresa, $nombreEmpresa)
    {
        $this->limpiarFormularioPlan();
        $this->idEmpresaPlan     = $idEmpresa;
        $this->nombreEmpresaPlan = $nombreEmpresa;
        $this->fechaInicioPlan   = now()->format('Y-m-d');
        $this->dispatch('abrirModalPlan');
    }

    public function asignarPlan()
    {
        $this->validate([
            'idPlanSeleccionado' => 'required|exists:planes,id_plan',
            'fechaInicioPlan'    => 'required|date',
            'montoPagadoPlan'    => 'required|numeric|min:0',
            'observacionPlan'    => 'nullable|string|max:255',
        ], [
            'idPlanSeleccionado.required' => 'Selecciona un plan.',
            'idPlanSeleccionado.exists'   => 'El plan seleccionado no existe.',
            'fechaInicioPlan.required'    => 'La fecha de inicio es obligatoria.',
            'fechaInicioPlan.date'        => 'Formato de fecha inválido.',
            'montoPagadoPlan.required'    => 'El monto pagado es obligatorio.',
            'montoPagadoPlan.numeric'     => 'El monto debe ser un número válido.',
            'montoPagadoPlan.min'         => 'El monto no puede ser negativo.',
        ]);

        if (!auth()->user()->can('opcion_gestion_empresas.crear')) {
            $this->dispatch('cerrarModalPlan');
            session()->flash('error', 'No tienes permiso para asignar planes a empresas.');
            return;
        }

        DB::beginTransaction();
        try {
            $plan = DB::table('planes')->where('id_plan', $this->idPlanSeleccionado)->first();
            if (!$plan) {
                session()->flash('error', 'Plan no encontrado.');
                return;
            }

            $fechaFin = \Carbon\Carbon::parse($this->fechaInicioPlan)
                ->addDays($plan->plan_duracion_dias)
                ->toDateString();

            // Desactivar plan activo anterior (historial)
            DB::table('empresa_planes')
                ->where('id_empresa', $this->idEmpresaPlan)
                ->where('estado', 1)
                ->update(['estado' => 0, 'updated_at' => now()]);

            // Insertar nuevo plan
            DB::table('empresa_planes')->insert([
                'id_empresa'   => $this->idEmpresaPlan,
                'id_plan'      => $this->idPlanSeleccionado,
                'fecha_inicio' => $this->fechaInicioPlan,
                'fecha_fin'    => $fechaFin,
                'estado'       => 1,
                'monto_pagado' => $this->montoPagadoPlan,
                'observacion'  => $this->observacionPlan ?: null,
                'id_users'     => Auth::id(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::commit();
            $this->limpiarFormularioPlan();
            $this->dispatch('cerrarModalPlan');
            session()->flash('success', 'Plan asignado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al asignar el plan.');
        }
    }

    public function limpiarFormularioPlan()
    {
        $this->reset([
            'idEmpresaPlan', 'nombreEmpresaPlan',
            'idPlanSeleccionado', 'fechaInicioPlan',
            'montoPagadoPlan', 'observacionPlan',
        ]);
        $this->resetErrorBag();
    }

    public function updatingBuscar()    { $this->resetPage(); }
    public function updatingPorPagina() { $this->resetPage(); }
}
