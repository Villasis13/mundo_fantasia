<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Gastos extends Component
{
    use WithPagination;

    // ── Contexto de rol ────────────────────────────────────────
    public int $cachedRoleId         = 0;
    public int $empresaSeleccionada  = 0;
    public int $sucursalSeleccionada = 0;
    public     $sucursalesDisponibles = [];

    // ── Filtros historial ──────────────────────────────────────
    public string $buscar           = '';
    public int    $porPagina        = 10;
    public string $filtroTipoGasto  = '';
    public string $filtroFechaDesde = '';
    public string $filtroFechaHasta = '';

    // ── Vista activa ───────────────────────────────────────────
    public string $vista = 'historial'; // historial | nuevo

    // ── Formulario ────────────────────────────────────────────
    public int     $tipoMovimiento     = 1; // 1=gasto, 2=ingreso
    public int     $idTipoGasto        = 0;
    public int     $idCajaSeleccionada = 0;
    public string  $gastoDetalle       = '';
    public string  $gastoMonto         = '';
    public string  $gastoFecha         = '';
    public string  $gastoObservacion   = '';
    public ?int    $idEditar           = null;
    public ?int    $idAnular           = null;
    public string  $motivoAnulacion    = '';
    private ?int   $idCambiarEstado    = null;
    private ?int   $nuevoEstado        = null;

    private ?Logs $logs = null;

    // ── Boot ───────────────────────────────────────────────────
    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    // ── Mount ──────────────────────────────────────────────────
    public function mount(): void
    {
        abort_if(!auth()->user()->can('gastos.listar'), 403);
        $this->gastoFecha       = now()->format('Y-m-d');
        $this->filtroFechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroFechaHasta = now()->format('Y-m-d');
        $this->preSeleccionarUbicacion();
    }

    // ── Helpers de rol ─────────────────────────────────────────
    private function esSuperAdmin(): bool   { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool        { return $this->cachedRoleId === 2; }
    private function esPrivilegiado(): bool { return in_array($this->cachedRoleId, [1, 2, 3]); }

    private function resolverIdEmpresa(): ?int
    {
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
    }

    private function resolverIdSucursal(): int
    {
        return $this->sucursalSeleccionada;
    }

    private function resolverIdTienda(): int
    {
        return $this->sucursalSeleccionada;
    }

    private function preSeleccionarUbicacion(): void
    {
        $idTienda = 0;
        $sesion   = (int) session('sucursal_activa_id', 0);

        // Sesión negativa = -id_tienda (patrón Dashboard: tiendas se guardan negativas)
        if ($sesion < 0) {
            $idTienda = abs($sesion);
        }

        // Sin sesión tienda válida: buscar primera tienda asignada en BD
        if (!$idTienda) {
            $idTienda = (int) DB::table('user_tienda')
                ->where('id_users', auth()->user()->id_users)
                ->orderBy('id_tienda')
                ->value('id_tienda');
        }

        if ($idTienda > 0) {
            $this->sucursalSeleccionada = $idTienda;
            $empId = (int) DB::table('tiendas')->where('id_tienda', $idTienda)->value('id_empresa');
            if ($empId) {
                $this->empresaSeleccionada = $empId;
                $this->cargarSucursales();
            }
        }
    }

    private function cargarSucursales(): void
    {
        $this->sucursalesDisponibles = DB::table('tiendas')
            ->where('id_empresa', $this->empresaSeleccionada)
            ->where('tienda_estado', 1)
            ->orderBy('tienda_nombre')
            ->get(['id_tienda', 'tienda_nombre']);
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada  = 0;
        $this->idCajaSeleccionada    = 0;
        $this->sucursalesDisponibles = [];
        if ($this->empresaSeleccionada > 0) {
            $this->cargarSucursales();
        }
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->idCajaSeleccionada = 0;
    }

    // ── Resolución de caja activa del usuario ──────────────────
    private function resolverCajaActiva(): ?object
    {
        return DB::table('caja')
            ->where('caja_estado', 1)
            ->where('id_users_apertura', auth()->user()->id_users)
            ->orderByDesc('id_caja')
            ->first();
    }

    private function resolverIdCajaNumero(): ?int
    {
        $caja = $this->resolverCajaActiva();
        return $caja ? (int) $caja->id_caja_numero : null;
    }

    private function resolverIdTiendaDesdeCaja(): int
    {
        $idCajaNumero = $this->resolverIdCajaNumero();
        if ($idCajaNumero) {
            $tienda = DB::table('caja_numero')
                ->where('id_caja_numero', $idCajaNumero)
                ->value('id_tienda');
            if ($tienda) return (int) $tienda;
        }
        return $this->resolverIdTienda();
    }

    // ── Navegación ─────────────────────────────────────────────
    public function nuevaGasto(): void
    {
        $this->resetFormulario();
        $this->gastoFecha = now()->format('Y-m-d');
        $this->vista = 'nuevo';
    }

    public function volverHistorial(): void
    {
        $this->resetFormulario();
        $this->vista = 'historial';
    }

    // ── Guardar ────────────────────────────────────────────────
    public function guardar(): void
    {
        if (!auth()->user()->can('gastos.crear')) {
            session()->flash('error', 'No tienes permiso para registrar gastos.');
            return;
        }

        $this->validate([
            'tipoMovimiento'     => 'required|integer|in:1,2',
            'idTipoGasto'        => 'required|integer|min:1',
            'idCajaSeleccionada' => 'required|integer|min:1',
            'gastoFecha'         => 'required|date',
            'gastoDetalle'       => 'required|string|max:5000',
            'gastoMonto'         => 'required|numeric|min:0.01',
        ], [
            'tipoMovimiento.in'          => 'Seleccione Ingreso o Egreso.',
            'idTipoGasto.required'       => 'Seleccione un tipo.',
            'idTipoGasto.min'            => 'Seleccione un tipo.',
            'idCajaSeleccionada.required'=> 'Seleccione una caja.',
            'idCajaSeleccionada.min'     => 'Seleccione una caja.',
            'gastoFecha.required'        => 'La fecha es obligatoria.',
            'gastoDetalle.required'      => 'El detalle es obligatorio.',
            'gastoMonto.required'        => 'El monto es obligatorio.',
            'gastoMonto.min'             => 'El monto debe ser mayor a cero.',
        ]);

        DB::beginTransaction();
        try {
            $cajaNro      = DB::table('caja_numero')->where('id_caja_numero', $this->idCajaSeleccionada)->first();
            $idCajaNumero = $this->idCajaSeleccionada;
            $idTienda     = $cajaNro ? (int) $cajaNro->id_tienda : 0;
            $idEmpresa    = $idTienda
                ? (int) DB::table('tiendas')->where('id_tienda', $idTienda)->value('id_empresa')
                : 0;

            DB::table('gastos')->insert([
                'id_empresa'       => $idEmpresa,
                'id_tienda'        => $idTienda ?: null,
                'id_caja_numero'   => $idCajaNumero,
                'id_tipo_gasto'    => $this->idTipoGasto,
                'id_users'         => auth()->user()->id_users,
                'gasto_tipo'       => $this->tipoMovimiento,
                'gasto_detalle'    => trim($this->gastoDetalle),
                'gasto_monto'      => (float) $this->gastoMonto,
                'gasto_fecha'      => $this->gastoFecha,
                'gasto_observacion'=> trim($this->gastoObservacion) ?: null,
                'gasto_estado'     => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::commit();
            $this->resetFormulario();
            $this->vista = 'historial';
            $label = $this->tipoMovimiento === 2 ? 'Ingreso' : 'Egreso';
            session()->flash('success', "{$label} registrado correctamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al registrar el gasto.');
        }
    }

    // ── Editar ─────────────────────────────────────────────────
    public function editar(int $id): void
    {
        if (!auth()->user()->can('gastos.actualizar')) {
            session()->flash('error', 'No tienes permiso para editar gastos.');
            return;
        }

        $gasto = DB::table('gastos')->where('id_gasto', $id)->first();
        if (!$gasto) {
            session()->flash('error', 'Gasto no encontrado.');
            return;
        }

        $this->idEditar           = $id;
        $this->tipoMovimiento     = (int) ($gasto->gasto_tipo ?? 1);
        $this->idTipoGasto        = (int) $gasto->id_tipo_gasto;
        $this->idCajaSeleccionada = (int) ($gasto->id_caja_numero ?? 0);
        $this->gastoDetalle       = $gasto->gasto_detalle;
        $this->gastoMonto         = (string) $gasto->gasto_monto;
        $this->gastoFecha         = $gasto->gasto_fecha;
        $this->gastoObservacion   = $gasto->gasto_observacion ?? '';

        // Cargar empresa/sede del gasto
        if ($gasto->id_empresa) {
            $this->empresaSeleccionada = (int) $gasto->id_empresa;
            $this->cargarSucursales();
        }
        if ($gasto->id_tienda) {
            $this->sucursalSeleccionada = (int) $gasto->id_tienda;
        }

        $this->vista = 'nuevo';
    }

    // ── Actualizar ─────────────────────────────────────────────
    public function actualizar(): void
    {
        if (!auth()->user()->can('gastos.actualizar')) {
            session()->flash('error', 'No tienes permiso para editar gastos.');
            return;
        }

        $this->validate([
            'tipoMovimiento'     => 'required|integer|in:1,2',
            'idTipoGasto'        => 'required|integer|min:1',
            'idCajaSeleccionada' => 'required|integer|min:1',
            'gastoFecha'         => 'required|date',
            'gastoDetalle'       => 'required|string|max:5000',
            'gastoMonto'         => 'required|numeric|min:0.01',
        ], [
            'tipoMovimiento.in'          => 'Seleccione Ingreso o Egreso.',
            'idTipoGasto.required'       => 'Seleccione un tipo.',
            'idTipoGasto.min'            => 'Seleccione un tipo.',
            'idCajaSeleccionada.required'=> 'Seleccione una caja.',
            'idCajaSeleccionada.min'     => 'Seleccione una caja.',
            'gastoFecha.required'        => 'La fecha es obligatoria.',
            'gastoDetalle.required'      => 'El detalle es obligatorio.',
            'gastoMonto.required'        => 'El monto es obligatorio.',
            'gastoMonto.min'             => 'El monto debe ser mayor a cero.',
        ]);

        DB::beginTransaction();
        try {
            $cajaNro     = DB::table('caja_numero')->where('id_caja_numero', $this->idCajaSeleccionada)->first();
            $idTiendaUpd = $cajaNro ? (int) $cajaNro->id_tienda : 0;

            DB::table('gastos')
                ->where('id_gasto', $this->idEditar)
                ->update([
                    'gasto_tipo'       => $this->tipoMovimiento,
                    'id_tipo_gasto'    => $this->idTipoGasto,
                    'id_caja_numero'   => $this->idCajaSeleccionada,
                    'id_tienda'        => $idTiendaUpd ?: null,
                    'id_empresa'       => $idTiendaUpd
                        ? (int) DB::table('tiendas')->where('id_tienda', $idTiendaUpd)->value('id_empresa')
                        : null,
                    'gasto_detalle'    => trim($this->gastoDetalle),
                    'gasto_monto'      => (float) $this->gastoMonto,
                    'gasto_fecha'      => $this->gastoFecha,
                    'gasto_observacion'=> trim($this->gastoObservacion) ?: null,
                    'updated_at'       => now(),
                ]);

            DB::commit();
            $this->resetFormulario();
            $this->vista = 'historial';
            $label = $this->tipoMovimiento === 2 ? 'Ingreso' : 'Egreso';
            session()->flash('success', "{$label} actualizado correctamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al actualizar el gasto.');
        }
    }

    // ── Anular ─────────────────────────────────────────────────
    public function confirmarAnular(int $id): void
    {
        $this->idAnular        = $id;
        $this->motivoAnulacion = '';
        $this->resetValidation('motivoAnulacion');
        $this->dispatch('abrirModalAnularGasto');
    }

    public function anular(): void
    {
        if (!auth()->user()->can('gastos.cambiar_estado')) {
            $this->dispatch('cerrarModalAnularGasto');
            session()->flash('error', 'No tienes permiso para anular gastos.');
            return;
        }

        $this->validate(
            ['motivoAnulacion' => 'required|string|min:5|max:1000'],
            ['motivoAnulacion.required' => 'El motivo de anulación es obligatorio.',
             'motivoAnulacion.min'      => 'El motivo debe tener al menos 5 caracteres.']
        );

        DB::beginTransaction();
        try {
            DB::table('gastos')
                ->where('id_gasto', $this->idAnular)
                ->update([
                    'gasto_estado'            => 0,
                    'gasto_motivo_anulacion'  => trim($this->motivoAnulacion),
                    'updated_at'              => now(),
                ]);

            DB::commit();
            $this->idAnular        = null;
            $this->motivoAnulacion = '';
            $this->dispatch('cerrarModalAnularGasto');
            session()->flash('success', 'Gasto anulado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            $this->dispatch('cerrarModalAnularGasto');
            session()->flash('error', 'Ocurrió un error al anular el gasto.');
        }
    }

    // ── Reset formulario ───────────────────────────────────────
    public function resetFormulario(): void
    {
        $this->idEditar              = null;
        $this->idAnular              = null;
        $this->motivoAnulacion       = '';
        $this->idCambiarEstado       = null;
        $this->nuevoEstado           = null;
        $this->tipoMovimiento        = 1;
        $this->idTipoGasto           = 0;
        $this->idCajaSeleccionada    = 0;
        $this->gastoDetalle          = '';
        $this->gastoMonto            = '';
        $this->gastoFecha            = now()->format('Y-m-d');
        $this->gastoObservacion      = '';
        $this->resetValidation();
    }

    // ── Exportar Excel ─────────────────────────────────────────
    public function exportarExcel(): mixed
    {
        if (!auth()->user()->can('gastos.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return null;
        }

        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $query = DB::table('gastos as g')
            ->join('tipo_gasto as tg', 'tg.id_tipo_gasto', '=', 'g.id_tipo_gasto')
            ->join('users as u', 'u.id_users', '=', 'g.id_users')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'g.id_tienda')
            ->leftJoin('caja_numero as cn', 'cn.id_caja_numero', '=', 'g.id_caja_numero')
            ->select(
                'g.id_gasto', 'g.gasto_tipo', 'g.gasto_fecha',
                'g.gasto_detalle', 'g.gasto_monto', 'g.gasto_estado',
                'g.gasto_observacion', 'tg.tipo_gasto_nombre',
                'u.nombre_users', 't.tienda_nombre', 'cn.caja_numero_nombre'
            );

        if ($idSucursal > 0) {
            $query->where('g.id_tienda', $idSucursal);
        } elseif ($idEmpresa) {
            $query->where('g.id_empresa', $idEmpresa);
        }

        if ($this->buscar !== '') {
            $term = $this->buscar;
            $query->where(function ($q) use ($term) {
                $q->where('g.gasto_detalle', 'like', "%{$term}%")
                  ->orWhere('tg.tipo_gasto_nombre', 'like', "%{$term}%");
            });
        }
        if ($this->filtroTipoGasto !== '') {
            $query->where('g.id_tipo_gasto', (int) $this->filtroTipoGasto);
        }
        if ($this->filtroFechaDesde !== '') {
            $query->where('g.gasto_fecha', '>=', $this->filtroFechaDesde);
        }
        if ($this->filtroFechaHasta !== '') {
            $query->where('g.gasto_fecha', '<=', $this->filtroFechaHasta);
        }

        $rows = $query->orderByDesc('g.gasto_fecha')->orderByDesc('g.id_gasto')->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Gastos - Ingresos');

        $headers = ['#', 'Tipo', 'Fecha', 'Clasificación', 'Detalle', 'Monto (S/)', 'Tienda', 'Caja', 'Registrado por', 'Estado'];
        $sheet->fromArray([$headers], null, 'A1');
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B1892']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $dataRows = [];
        foreach ($rows as $idx => $row) {
            $dataRows[] = [
                $idx + 1,
                ($row->gasto_tipo ?? 1) == 2 ? 'Ingreso' : 'Gasto',
                \Carbon\Carbon::parse($row->gasto_fecha)->format('d/m/Y'),
                $row->tipo_gasto_nombre,
                $row->gasto_detalle,
                number_format((float) $row->gasto_monto, 2, '.', ''),
                $row->tienda_nombre ?? '—',
                $row->caja_numero_nombre ?? '—',
                $row->nombre_users,
                $row->gasto_estado == 1 ? 'Activo' : 'Anulado',
            ];
        }
        $sheet->fromArray($dataRows, null, 'A2');

        foreach ($rows as $idx => $row) {
            $excelRow = $idx + 2;
            if ($row->gasto_estado == 0) {
                $color = 'E5E7EB';
            } elseif (($row->gasto_tipo ?? 1) == 2) {
                $color = 'DCFCE7';
            } else {
                $color = 'FEE2E2';
            }
            $sheet->getStyle("A{$excelRow}:{$lastCol}{$excelRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            ]);
        }

        foreach (range(1, count($headers)) as $colIdx) {
            $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }

        $filename = 'gastos_ingresos_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ── Render ─────────────────────────────────────────────────
    public function render()
    {
        $tiposGasto = DB::table('tipo_gasto')
            ->where('tipo_gasto_estado', 1)
            ->orderBy('tipo_gasto_nombre')
            ->get();

        $empresas = DB::table('empresa')
            ->where('empresa_estado', '!=', 0)
            ->orderBy('empresa_nombrecomercial')
            ->get(['id_empresa', 'empresa_nombrecomercial']);

        $tiendaActual  = $this->sucursalSeleccionada
            ? DB::table('tiendas')->where('id_tienda', $this->sucursalSeleccionada)->first()
            : null;
        $empresaActual = $this->empresaSeleccionada
            ? DB::table('empresa')->where('id_empresa', $this->empresaSeleccionada)->first()
            : null;

        $cajasDisponibles = DB::table('caja_numero as cn')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'cn.id_tienda')
            ->where('cn.caja_numero_estado', 1)
            ->orderBy('t.tienda_nombre')
            ->orderBy('cn.caja_numero_nombre')
            ->get(['cn.id_caja_numero', 'cn.caja_numero_nombre', 't.tienda_nombre']);

        $query = DB::table('gastos as g')
            ->join('tipo_gasto as tg', 'tg.id_tipo_gasto', '=', 'g.id_tipo_gasto')
            ->join('users as u', 'u.id_users', '=', 'g.id_users')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'g.id_tienda')
            ->leftJoin('caja_numero as cn', 'cn.id_caja_numero', '=', 'g.id_caja_numero');

        // Filtro empresa/sucursal según rol
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        if ($idSucursal > 0) {
            $query->where('g.id_tienda', $idSucursal);
        } elseif ($idEmpresa) {
            $query->where('g.id_empresa', $idEmpresa);
        }

        // Filtros de búsqueda
        if ($this->buscar !== '') {
            $term = $this->buscar;
            $query->where(function ($q) use ($term) {
                $q->where('g.gasto_detalle', 'like', "%{$term}%")
                  ->orWhere('tg.tipo_gasto_nombre', 'like', "%{$term}%");
            });
        }

        if ($this->filtroTipoGasto !== '') {
            $query->where('g.id_tipo_gasto', (int) $this->filtroTipoGasto);
        }

        if ($this->filtroFechaDesde !== '') {
            $query->where('g.gasto_fecha', '>=', $this->filtroFechaDesde);
        }

        if ($this->filtroFechaHasta !== '') {
            $query->where('g.gasto_fecha', '<=', $this->filtroFechaHasta);
        }

        $gastos = $query
            ->select(
                'g.id_gasto',
                'g.gasto_tipo',
                'g.gasto_fecha',
                'g.gasto_detalle',
                'g.gasto_monto',
                'g.gasto_estado',
                'g.gasto_observacion',
                'tg.tipo_gasto_nombre',
                'u.nombre_users',
                't.tienda_nombre',
                'cn.caja_numero_nombre'
            )
            ->orderByDesc('g.gasto_fecha')
            ->orderByDesc('g.id_gasto')
            ->paginate($this->porPagina);

        $infoCaja       = $this->calcularSaldoCaja();
        $esPrivilegiado = $this->esPrivilegiado();
        return view('livewire.logistica.gastos', compact(
            'gastos', 'tiposGasto', 'cajasDisponibles',
            'tiendaActual', 'empresaActual', 'esPrivilegiado', 'empresas',
            'infoCaja'
        ));
    }

    private function calcularSaldoCaja(): array
    {
        $base = ['abierta' => false, 'saldo' => null, 'apertura' => null, 'nombre' => null];

        if ($this->idCajaSeleccionada <= 0) return $base;

        $hoy      = now()->toDateString();
        $apertura = DB::table('caja as c')
            ->join('caja_numero as cn', 'cn.id_caja_numero', '=', 'c.id_caja_numero')
            ->where('c.id_caja_numero', $this->idCajaSeleccionada)
            ->where('c.caja_fecha', $hoy)
            ->where('c.caja_estado', 1)
            ->select('c.id_caja', 'c.caja_apertura', 'cn.caja_numero_nombre')
            ->first();

        if (!$apertura) return $base;

        $idCaja       = $apertura->id_caja;
        $idCajaNumero = $this->idCajaSeleccionada;

        $tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get(['id_tipo_pago', 'tipo_pago_nombre']);
        $idsEfQr     = $tiposPago->filter(fn($t) =>
            stripos($t->tipo_pago_nombre, 'efectivo') !== false ||
            stripos($t->tipo_pago_nombre, 'qr')       !== false
        )->pluck('id_tipo_pago');

        $ventas = (float) DB::table('ventas_detalle_pagos as vdp')
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->where('v.id_caja', $idCaja)
            ->where('vdp.venta_detalle_pago_estado', 1)
            ->when($idsEfQr->isNotEmpty(), fn($q) => $q->whereIn('vdp.id_tipo_pago', $idsEfQr))
            ->sum('vdp.venta_detalle_pago_monto');

        $cobros = (float) DB::table('pagos_cuotas as pc')
            ->join('ventas_cuotas as vc', 'vc.id_ventas_cuotas', '=', 'pc.id_ventas_cuotas')
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->whereNull('pc.deleted_at')
            ->where('v.id_caja', $idCaja)
            ->sum('pc.pagos_cuota_monto');

        $ingresosMov = (float) DB::table('caja_movimientos')->whereNull('deleted_at')->where('id_caja', $idCaja)->where('tipo', 1)->sum('monto');
        $egresosMov  = (float) DB::table('caja_movimientos')->whereNull('deleted_at')->where('id_caja', $idCaja)->where('tipo', 2)->sum('monto');

        $nc = (float) DB::table('ventas as v')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->where('v.venta_tipo', '07')
            ->where('v.id_caja', $idCaja)
            ->sum('v.venta_total');

        $gastosHoy = (float) DB::table('gastos')
            ->where('gasto_estado', 1)->where('gasto_tipo', 1)
            ->where('id_caja_numero', $idCajaNumero)
            ->whereDate('gasto_fecha', $hoy)->sum('gasto_monto');

        $ingresosHoy = (float) DB::table('gastos')
            ->where('gasto_estado', 1)->where('gasto_tipo', 2)
            ->where('id_caja_numero', $idCajaNumero)
            ->whereDate('gasto_fecha', $hoy)->sum('gasto_monto');

        $saldo = round(
            (float)$apertura->caja_apertura + $ventas + $cobros + $ingresosMov + $ingresosHoy
            - $egresosMov - $nc - $gastosHoy,
            2
        );

        return [
            'abierta'  => true,
            'saldo'    => $saldo,
            'apertura' => (float) $apertura->caja_apertura,
            'nombre'   => $apertura->caja_numero_nombre,
        ];
    }
}
