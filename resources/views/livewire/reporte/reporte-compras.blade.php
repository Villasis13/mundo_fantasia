<div class="container-fluid py-3">

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header py-2 fw-semibold"><i class="fa fa-file-invoice me-1"></i> Reporte de Compras</div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Fecha desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model="filtroDesde">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Fecha hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model="filtroHasta">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Transportista</label>
                    <select class="form-select form-select-sm" wire:model="filtroTransportista">
                        <option value="">Todos</option>
                        @foreach($transportistas as $t)
                            <option value="{{ $t->transportista_nombre }}">{{ $t->transportista_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Tipo de reporte</label>
                    <select class="form-select form-select-sm" wire:model="tipoReporte">
                        <option value="registro">Registro de compras</option>
                        <option value="estudio">Para estudio</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 text-center">
                @can('reporte_compras.exportar')
                <button wire:click="descargarExcel" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                    <span wire:loading.remove wire:target="descargarExcel">
                        <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" alt="Excel" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Excel
                    </span>
                    <span wire:loading wire:target="descargarExcel">
                        <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                    </span>
                </button>
                @endcan
                <p class="text-muted small mt-2 mb-0">
                    <i class="fa fa-circle-info me-1"></i>El reporte se descarga en Excel según los filtros seleccionados.
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (e) => { const d = Array.isArray(e) ? e[0] : e; if (d && d.url) window.open(d.url, '_blank'); });
        });
    </script>
</div>
