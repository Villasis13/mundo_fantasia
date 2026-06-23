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

class StockEstablecimiento extends Component
{
    use WithPagination;

    public int    $filtroFamilia    = 0;
    public int    $filtroCategoria  = 0;
    public string $filtroEstado     = 'todos';
    public string $buscar           = '';
    public int    $porPagina        = 20;
    public string $ordenColumna     = 'p.pro_nombre';
    public string $ordenDireccion   = 'asc';

    private int   $cachedRoleId = 0;
    private ?Logs $logs         = null;

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
        abort_if(!auth()->user()->can('stock_establecimiento.listar'), 403);
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

    public function updatedFiltroFamilia(): void   { $this->filtroCategoria = 0; $this->resetPage(); }
    public function updatedFiltroCategoria(): void { $this->resetPage(); }
    public function updatedFiltroEstado(): void    { $this->resetPage(); }
    public function updatingBuscar(): void         { $this->resetPage(); }
    public function updatingPorPagina(): void      { $this->resetPage(); }

    public function ordenar(string $columna): void
    {
        $columnasOk = ['p.pro_nombre', 'p.pro_codigo'];
        if (!in_array($columna, $columnasOk)) return;
        if ($this->ordenColumna === $columna) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenColumna   = $columna;
            $this->ordenDireccion = 'asc';
        }
        $this->resetPage();
    }

    // ── Exportar Excel ───────────────────────────────────────────
    public function exportarExcel(): mixed
    {
        if (!auth()->user()->can('stock_establecimiento.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return null;
        }

        $esAdmin    = $this->esAdmin();
        $adminEmpId = $esAdmin ? $this->adminEmpresaId() : null;

        $sortCol = in_array($this->ordenColumna, ['p.pro_nombre', 'p.pro_codigo'])
            ? $this->ordenColumna : 'p.pro_nombre';

        $productos = $this->buildProductQuery($esAdmin, $adminEmpId)
            ->orderBy($sortCol, $this->ordenDireccion)
            ->get();

        $idsPro = $productos->pluck('id_pro')->all();

        $stockSedes = $idsPro ? DB::table('producto_sucursal as ps')
            ->whereIn('ps.id_pro', $idsPro)->where('ps.ps_estado', 1)
            ->selectRaw('ps.id_pro, COALESCE(ps.id_tienda, ps.id_sucursal) as id_tienda, ps.ps_stock')
            ->get()->groupBy('id_pro') : collect();

        $tiendasConfig = DB::table('tiendas as t')
            ->join('empresa as e', 'e.id_empresa', '=', 't.id_empresa')
            ->where('t.tienda_estado', '!=', 0)->whereNull('t.id_tienda_padre')
            ->select('t.id_tienda', 't.tienda_nombre', 'e.empresa_nombrecomercial')
            ->orderBy('e.id_empresa')->orderBy('t.id_tienda')->get();

        $numSede    = $tiendasConfig->count();
        $lastColIdx = 2 + $numSede;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock');

        $coord = fn(int $col) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

        // ── Fila 1: cabeceras de grupo ───────────────────────────
        $sheet->setCellValue('A1', 'Código');
        $sheet->setCellValue('B1', 'Producto');

        // Encabezado de grupo "Sedes / Tiendas" en fila 1
        if ($numSede > 0) {
            $sedeStartIdx = 3;
            $sedeEndIdx   = 2 + $numSede;
            $sheet->setCellValue("{$coord($sedeStartIdx)}1", 'Sedes / Tiendas');
            if ($numSede > 1) {
                $sheet->mergeCells("{$coord($sedeStartIdx)}1:{$coord($sedeEndIdx)}1");
            }
            $sheet->getStyle("{$coord($sedeStartIdx)}1:{$coord($sedeEndIdx)}1")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5A6268']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Código y Producto se fusionan verticalmente (filas 1 y 2)
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->getStyle('A1:B2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Fila 2: nombre de cada sede
        $col = 3;
        foreach ($tiendasConfig as $tienda) {
            $cellAddr = "{$coord($col)}2";
            $sheet->setCellValue($cellAddr, $tienda->tienda_nombre . "\n(" . $tienda->empresa_nombrecomercial . ")");
            $sheet->getStyle($cellAddr)->getAlignment()->setWrapText(true);
            $col++;
        }

        if ($numSede > 0) {
            $sheet->getStyle("{$coord(3)}2:{$coord(2 + $numSede)}2")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5A6268']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        $sheet->getRowDimension(2)->setRowHeight(32);

        $excelRow = 3;
        foreach ($productos as $prod) {
            $sedeMap = collect($stockSedes[$prod->id_pro] ?? [])->keyBy('id_tienda');

            $row = [$prod->pro_codigo, $prod->pro_nombre];
            foreach ($tiendasConfig as $tienda) {
                $row[] = isset($sedeMap[$tienda->id_tienda]) ? (float) $sedeMap[$tienda->id_tienda]->ps_stock : 0;
            }

            $sheet->fromArray([$row], null, "A{$excelRow}");
            $excelRow++;
        }

        foreach (range(1, $lastColIdx) as $colIdx) {
            $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }

        $filename = 'stock_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ── Query base de productos ──────────────────────────────────
    private function buildProductQuery(bool $esAdmin, ?int $adminEmpId)
    {
        $query = DB::table('productos as p')
            ->leftJoin('categorias as c', 'c.id_ca', '=', 'p.id_ca')
            ->leftJoin('familias as f',   'f.id_fa', '=', 'c.id_fa')
            ->select('p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'p.pro_codigo_interno',
                     'c.ca_nombre', 'f.fa_nombre')
            ->where('p.pro_estado', 1);

        if ($esAdmin && $adminEmpId) {
            $query->whereExists(function ($sub) use ($adminEmpId) {
                $sub->selectRaw(1)->from('producto_sucursal as ps2')
                    ->join('tiendas as t2', 't2.id_tienda', '=', 'ps2.id_tienda')
                    ->whereColumn('ps2.id_pro', 'p.id_pro')
                    ->where('t2.id_empresa', $adminEmpId)
                    ->where('ps2.ps_estado', 1);
            });
        }

        if ($this->filtroCategoria > 0) {
            $query->where('p.id_ca', $this->filtroCategoria);
        } elseif ($this->filtroFamilia > 0) {
            $query->where('f.id_fa', $this->filtroFamilia);
        }

        if ($this->buscar !== '') {
            $b = $this->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('p.pro_nombre',          'like', "%{$b}%")
                  ->orWhere('p.pro_codigo',         'like', "%{$b}%")
                  ->orWhere('p.pro_codigo_interno', 'like', "%{$b}%");
            });
        }

        if ($this->filtroEstado === 'con_stock') {
            $query->whereExists(function ($sub) {
                $sub->selectRaw(1)->from('producto_sucursal as ps2')
                    ->whereColumn('ps2.id_pro', 'p.id_pro')
                    ->where('ps2.ps_estado', 1)->where('ps2.ps_stock', '>', 0);
            });
        } elseif ($this->filtroEstado === 'sin_stock') {
            $query->whereNotExists(function ($sub) {
                $sub->selectRaw(1)->from('producto_sucursal as ps2')
                    ->whereColumn('ps2.id_pro', 'p.id_pro')
                    ->where('ps2.ps_estado', 1)->where('ps2.ps_stock', '>', 0);
            });
        }

        return $query;
    }

    // ── Render ───────────────────────────────────────────────────
    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();
        $adminEmpId   = $esAdmin ? $this->adminEmpresaId() : null;

        $sortCol = in_array($this->ordenColumna, ['p.pro_nombre', 'p.pro_codigo'])
            ? $this->ordenColumna : 'p.pro_nombre';

        $productos = $this->buildProductQuery($esAdmin, $adminEmpId)
            ->orderBy($sortCol, $this->ordenDireccion)
            ->paginate($this->porPagina);

        $idsPro = $productos->pluck('id_pro')->all();

        $stockSedes = $idsPro ? DB::table('producto_sucursal as ps')
            ->whereIn('ps.id_pro', $idsPro)->where('ps.ps_estado', 1)
            ->selectRaw('ps.id_pro, COALESCE(ps.id_tienda, ps.id_sucursal) as id_tienda, ps.ps_stock, ps.ps_stock_minimo')
            ->get()->groupBy('id_pro') : collect();

        $tiendasConfig = DB::table('tiendas as t')
            ->join('empresa as e', 'e.id_empresa', '=', 't.id_empresa')
            ->where('t.tienda_estado', '!=', 0)
            ->whereNull('t.id_tienda_padre')
            ->select('t.id_tienda', 't.tienda_nombre', 'e.empresa_nombrecomercial')
            ->orderBy('e.id_empresa')->orderBy('t.id_tienda')
            ->get();

        $familias = DB::table('familias')->where('fa_estado', 1)->orderBy('fa_nombre')->get();

        $categorias = $this->filtroFamilia > 0
            ? DB::table('categorias')
                ->where('id_fa', $this->filtroFamilia)
                ->where('ca_estado', 1)->orderBy('ca_nombre')->get()
            : collect();

        return view('livewire.logistica.stock-establecimiento', compact(
            'productos', 'familias', 'categorias',
            'esSuperAdmin', 'esAdmin',
            'stockSedes', 'tiendasConfig'
        ));
    }
}
