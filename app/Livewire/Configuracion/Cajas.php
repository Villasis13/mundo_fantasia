<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Cajas extends Component
{
    use WithPagination;

    // ── Contexto de sucursal padre ────────────────────────────
    public int    $idSucursal     = 0;
    public string $nombreSucursal = '';
    public int    $tipoSucursal   = 0; // 1=Tienda, 2=Sucursal, 3=Almacén

    // ── Contexto de tienda padre ──────────────────────────────
    public int    $idTienda     = 0;
    public string $nombreTienda = '';
    public int    $tipoTienda   = 0; // 1=Tienda, 2=Sucursal, 3=Almacén

    // ── Propiedades formulario caja ───────────────────────────
    public $cajaNombre     = '';
    public $cajaImpresora  = '';

    // ── Control modal caja ────────────────────────────────────
    public $modoEdicion    = false;
    public $idCajaEditar   = null;

    // ── Búsqueda y paginación ─────────────────────────────────
    public $buscar         = '';
    public $porPagina      = 10;
    public $ordenColumna   = 'id_caja_numero';
    public $ordenDireccion = 'desc';

    // ── Modal eliminar caja ───────────────────────────────────
    public $idCajaEliminar = null;

    // ── Modal series ──────────────────────────────────────────
    public $idCajaSeries     = null;
    public $nombreCajaSeries = '';
    public $series           = [];

    // ── Modelos ───────────────────────────────────────────────
    private $logs;

    public function boot()
    {
        $this->logs = new Logs();
    }

    public function mount(int $idSucursal = 0, int $idTienda = 0): void
    {
        abort_if(!auth()->user()->can('gestion_de_cajas.listar'), 403);

        $this->idSucursal = $idSucursal;
        if ($idSucursal > 0) {
            $suc = DB::table('sucursals')->where('id_sucursal', $idSucursal)->first();
            $this->nombreSucursal = $suc?->sucursal_nombre ?? '';
            $this->tipoSucursal   = (int) ($suc?->sucursal_tipo ?? 0);
        }

        $this->idTienda = $idTienda;
        if ($idTienda > 0) {
            $tienda = DB::table('tiendas')->where('id_tienda', $idTienda)->first();
            $this->nombreTienda = $tienda?->tienda_nombre ?? '';
            $this->tipoTienda   = (int) ($tienda?->tienda_tipo ?? 0);
        }
    }

    // ── Reglas de validación ──────────────────────────────────
    protected function rules(): array
    {
        return [
            'cajaNombre' => [
                'required', 'string', 'max:100',
                function ($attribute, $value, $fail) {
                    $q = DB::table('caja_numero')
                        ->where('caja_numero_nombre', $value)
                        ->where('caja_numero_estado', '!=', 0);
                    if ($this->idTienda > 0) {
                        $q->where('id_tienda', $this->idTienda);
                    } elseif ($this->idSucursal > 0) {
                        $q->where('id_sucursal', $this->idSucursal);
                    }
                    if ($this->modoEdicion && $this->idCajaEditar) {
                        $q->where('id_caja_numero', '!=', $this->idCajaEditar);
                    }
                    if ($q->exists()) {
                        $fail('Ya existe una caja con ese nombre en esta sede.');
                    }
                },
            ],
            'cajaImpresora' => 'required|string|max:100',
        ];
    }

    protected function messages(): array
    {
        return [
            'cajaNombre.required'    => 'El nombre de la caja es obligatorio.',
            'cajaImpresora.required' => 'El nombre de la ticketera es obligatorio.',
        ];
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $columnasPermitidas = ['id_caja_numero', 'caja_numero_nombre', 'caja_numero_impresora'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'id_caja_numero';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $cajas = DB::table('caja_numero')
            ->when($this->idSucursal > 0, fn($q) => $q->where('id_sucursal', $this->idSucursal))
            ->when($this->idTienda > 0, fn($q) => $q->where('id_tienda', $this->idTienda))
            ->where(function ($q) {
                $q->where('caja_numero_nombre',    'like', "%{$this->buscar}%")
                    ->orWhere('caja_numero_impresora','like', "%{$this->buscar}%");
            })
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        return view('livewire.configuracion.cajas', compact('cajas'));
    }

    // ── Ordenar ───────────────────────────────────────────────
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

    // ── Abrir modal nueva caja ────────────────────────────────
    public function abrirModalNuevo()
    {
        if ($this->tipoSucursal === 3 || $this->tipoTienda === 3) {
            session()->flash('error', 'No se pueden crear cajas en un Almacén.');
            return;
        }

        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    // ── Abrir modal editar caja ───────────────────────────────
    public function abrirModalEditar($idCaja)
    {
        $this->limpiarFormulario();

        $caja = DB::table('caja_numero')->where('id_caja_numero', $idCaja)->first();
        if (!$caja) {
            session()->flash('error', 'Caja no encontrada.');
            return;
        }

        $this->idCajaEditar   = $caja->id_caja_numero;
        $this->cajaNombre     = $caja->caja_numero_nombre;
        $this->cajaImpresora  = $caja->caja_numero_impresora;
        $this->modoEdicion    = true;
        $this->dispatch('abrirModal');
    }

    // ── Confirmar eliminar caja ───────────────────────────────
    public function confirmarEliminar($idCaja)
    {
        $this->idCajaEliminar = $idCaja;
        $this->dispatch('abrirModalEliminar');
    }

    // ── Eliminar caja (lógico) ────────────────────────────────
    public function eliminar()
    {
        try {
            if (!auth()->user()->can('gestion_de_cajas.cambiar_estado')) {
                $this->dispatch('cerrarModalEliminar');
                session()->flash('error', 'No tienes permiso para deshabilitar cajas.');
                return;
            }

            $caja = DB::table('caja_numero')->where('id_caja_numero', $this->idCajaEliminar)->first();
            if (!$caja) {
                session()->flash('error', 'Caja no encontrada.');
                return;
            }

            DB::table('caja_numero')
                ->where('id_caja_numero', $this->idCajaEliminar)
                ->update(['caja_numero_estado' => 0]);

            $this->idCajaEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Caja deshabilitada correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al deshabilitar la caja.');
        }
    }

    public function habilitarCaja($idCaja)
    {
        try {
            if (!auth()->user()->can('gestion_de_cajas.cambiar_estado')) {
                session()->flash('error', 'No tienes permiso para habilitar cajas.');
                return;
            }
            DB::table('caja_numero')
                ->where('id_caja_numero', $idCaja)
                ->update(['caja_numero_estado' => 1]);
            session()->flash('success', 'Caja habilitada correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al habilitar la caja.');
        }
    }

    // ── Guardar caja (crear o editar) ─────────────────────────
    public function guardar()
    {
        $this->validate();

        // Verificar permisos antes de abrir transacción
        $permiso = $this->modoEdicion ? 'gestion_de_cajas.actualizar' : 'gestion_de_cajas.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            if ($this->modoEdicion) {
                // Solo actualizar datos de la caja, las series se editan aparte
                DB::table('caja_numero')
                    ->where('id_caja_numero', $this->idCajaEditar)
                    ->update([
                        'caja_numero_nombre'     => $this->cajaNombre,
                        'caja_numero_impresora'  => $this->cajaImpresora,
                        'updated_at'             => now(),
                    ]);
            } else {
                // Crear caja
                $idCaja = DB::table('caja_numero')->insertGetId([
                    'id_sucursal'            => $this->idSucursal > 0 ? $this->idSucursal : null,
                    'id_tienda'              => $this->idTienda > 0 ? $this->idTienda : null,
                    'caja_numero_nombre'     => $this->cajaNombre,
                    'caja_numero_impresora'  => $this->cajaImpresora,
                    'caja_numero_estado'     => 1,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);

                // Generar las 3 series automáticamente
                $this->generarSeriesParaCaja($idCaja);
            }

            DB::commit();

            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $this->modoEdicion
                ? 'Caja actualizada correctamente.'
                : 'Caja creada correctamente con sus series.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la caja.');
        }
    }

    // ── Generar series automáticamente ───────────────────────
    private function generarSeriesParaCaja(int $idCaja): void
    {
        $tiposComprobante = [
            '01' => 'F',  // Factura
            '03' => 'B',  // Boleta
            '20' => 'NV', // Nota de venta
        ];

        $idEmpresa = $this->idTienda > 0
            ? DB::table('tiendas')->where('id_tienda', $this->idTienda)->value('id_empresa')
            : ($this->idSucursal > 0
                ? DB::table('sucursals')->where('id_sucursal', $this->idSucursal)->value('id_empresa')
                : null);

        foreach ($tiposComprobante as $tipocomp => $prefijo) {
            $nuevaSerie = $this->calcularSiguienteSerie($tipocomp, $prefijo);

            DB::table('serie')->insert([
                'id_caja_numero' => $idCaja,
                'id_empresa'     => $idEmpresa,
                'id_sucursal'    => $this->idSucursal > 0 ? $this->idSucursal : null,
                'tipocomp'       => $tipocomp,
                'serie'          => $nuevaSerie,
                'correlativo'    => 0,
                'estado'         => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    // ── Calcular siguiente serie ──────────────────────────────
    // Lógica:
    // 1. Busca la última serie registrada para ese tipocomp
    // 2. Extrae el prefijo y número de la serie
    // 3. Incrementa el número en +1 manteniendo el prefijo y formato
    // Ejemplo: F001 → F002 | FF01 → FF02 | NV01 → NV02
    private function calcularSiguienteSerie(string $tipocomp, string $prefijoPorDefecto): string
    {
        $ultimaSerie = DB::table('serie')
            ->where('tipocomp', $tipocomp)
            ->orderByDesc('id_serie')
            ->value('serie');

        if (!$ultimaSerie) {
            // Primera serie — usar prefijo por defecto con 001
            return $prefijoPorDefecto . '001';
        }

        // Separar prefijo (letras) y número (dígitos) de la última serie
        preg_match('/^([A-Za-z]+)(\d+)$/', $ultimaSerie, $partes);

        if (count($partes) !== 3) {
            // Si no matchea el patrón esperado, usar el prefijo por defecto
            return $prefijoPorDefecto . '001';
        }

        $prefijo = $partes[1];               // Ej: "F", "NV", "FF"
        $numero  = (int) $partes[2];         // Ej: 1, 2, 10
        $largo   = strlen($partes[2]);       // Ej: 3 (para "001")

        $siguienteNumero = $numero + 1;

        // Mantener el mismo número de dígitos con ceros a la izquierda
        return $prefijo . str_pad($siguienteNumero, $largo, '0', STR_PAD_LEFT);
    }

    // ── Abrir modal series ────────────────────────────────────
    public function abrirModalSeries($idCaja)
    {
        $caja = DB::table('caja_numero')->where('id_caja_numero', $idCaja)->first();
        if (!$caja) {
            session()->flash('error', 'Caja no encontrada.');
            return;
        }

        $this->idCajaSeries     = $idCaja;
        $this->nombreCajaSeries = $caja->caja_numero_nombre;

        // Cargar las 3 series de la caja como array editable
        $seriesDb = DB::table('serie')
            ->where('id_caja_numero', $idCaja)
            ->whereIn('tipocomp', ['01', '03', '20'])
            ->orderBy('tipocomp')
            ->get();

        $this->series = $seriesDb->map(function ($s) {
            return [
                'id_serie'    => $s->id_serie,
                'tipocomp'    => $s->tipocomp,
                'serie'       => $s->serie,
                'correlativo' => $s->correlativo,
            ];
        })->toArray();

        $this->dispatch('abrirModalSeries');
    }

    // ── Guardar series editadas ───────────────────────────────
    public function guardarSeries()
    {
        // Validar series
        $this->validate([
            'series.*.serie'       => 'required|string|max:10|regex:/^[A-Za-z]+\d+$/',
            'series.*.correlativo' => 'required|integer|min:0',
        ], [
            'series.*.serie.required'       => 'La serie es obligatoria.',
            'series.*.serie.regex'          => 'La serie debe tener letras seguidas de números (ej: F001).',
            'series.*.correlativo.required' => 'El correlativo es obligatorio.',
            'series.*.correlativo.integer'  => 'El correlativo debe ser un número entero.',
            'series.*.correlativo.min'      => 'El correlativo no puede ser negativo.',
        ]);

        if (!auth()->user()->can('gestion_de_cajas.actualizar')) {
            $this->dispatch('cerrarModalSeries');
            session()->flash('error', 'No tienes permiso para editar las series.');
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($this->series as $s) {
                DB::table('serie')
                    ->where('id_serie', $s['id_serie'])
                    ->update([
                        'serie'       => strtoupper($s['serie']),
                        'correlativo' => $s['correlativo'],
                        'updated_at'  => now(),
                    ]);
            }

            DB::commit();

            $this->series         = [];
            $this->idCajaSeries   = null;
            $this->nombreCajaSeries = '';
            $this->dispatch('cerrarModalSeries');
            session()->flash('success', 'Series actualizadas correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar las series.');
        }
    }

    // ── Etiqueta legible del tipo de comprobante ──────────────
    public function tipoCompLabel(string $tipocomp): string
    {
        return match($tipocomp) {
            '01' => 'Factura',
            '03' => 'Boleta',
            '20' => 'Nota de Venta',
            default => $tipocomp,
        };
    }

    // ── Limpiar formulario caja ───────────────────────────────
    public function limpiarFormulario()
    {
        $this->reset([
            'cajaNombre', 'cajaImpresora',
            'idCajaEditar', 'modoEdicion',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // ── Reset paginación ──────────────────────────────────────
    public function updatingBuscar()
    {
        $this->resetPage();
    }

    public function updatingPorPagina()
    {
        $this->resetPage();
    }
}
