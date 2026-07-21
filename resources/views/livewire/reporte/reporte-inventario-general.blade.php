<div class="container-fluid py-3">

    {{-- Título --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0 fw-bold">
            <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>Reporte de Inventario General
        </h5>
    </div>

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header py-2 fw-semibold"><i class="fa fa-warehouse me-1"></i> Inventario General</div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Familia</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroFamilia">
                        <option value="0">Todas</option>
                        @foreach($familias as $fa)
                            <option value="{{ $fa->id_fa }}">{{ $fa->fa_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Categoría</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroCategoria">
                        <option value="0">Todas</option>
                        @foreach($categorias as $ca)
                            <option value="{{ $ca->id_ca }}">{{ $ca->ca_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Código o descripción</label>
                    <input type="text" class="form-control form-control-sm" wire:model="filtroBusqueda" placeholder="Buscar por código o nombre">
                </div>
                <div class="col-12 col-md-3"></div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Fecha desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model="filtroDesde">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Fecha hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model="filtroHasta">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end">
                    @can('reporte_inventario_general.exportar')
                    <button wire:click="descargarExcel" wire:loading.attr="disabled" wire:target="descargarExcel"
                            class="btn btn-outline-success btn-sm">
                        <span wire:loading.remove wire:target="descargarExcel">
                            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" alt="Excel" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Exportar Excel
                        </span>
                        <span wire:loading wire:target="descargarExcel">
                            <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                        </span>
                    </button>
                    @endcan
                </div>
            </div>
            <div class="mt-3 text-muted small">
                <i class="fa fa-circle-info me-1"></i>El reporte lista los productos con sus presentaciones (una fila por presentación), stock, costo y precios.
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (e) => { const d = Array.isArray(e) ? e[0] : e; if (d && d.url) window.open(d.url, '_blank'); });
        });
    </script>
</div>
