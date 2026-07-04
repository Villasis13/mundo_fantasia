{{-- Detalle de un producto en el panel de unión. Recibe $d (array) y $color --}}
<div class="d-flex justify-content-between align-items-start mb-2">
    <div>
        <div class="fw-bold">{{ $d['nombre'] }}</div>
        <span class="badge bg-{{ $color }}">#{{ $d['id_pro'] }} · {{ $d['codigo'] }}</span>
    </div>
    <div class="text-end">
        <div class="small text-muted">Stock total</div>
        <div class="fw-bold text-{{ $color }}">{{ number_format($d['stock'], 2) }}</div>
    </div>
</div>

<div class="row g-1 small mb-2">
    <div class="col-6"><span class="text-muted">Código SUNAT:</span> <b>{{ $d['codigo_sunat'] }}</b></div>
    <div class="col-6"><span class="text-muted">Unidad Matriz:</span> <b>{{ $d['unidad'] }}</b></div>
    <div class="col-6"><span class="text-muted">Familia:</span> <b>{{ $d['familia'] }}</b></div>
    <div class="col-6"><span class="text-muted">Sub Familia:</span> <b>{{ $d['subfamilia'] }}</b></div>
</div>

<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.75rem;">
        <thead class="table-light">
            <tr>
                <th>Unidad</th>
                <th class="text-center">Cant. contiene</th>
                <th class="text-end">P. Costo</th>
                <th class="text-end">P. Público</th>
                <th class="text-end">P. Mayorista</th>
            </tr>
        </thead>
        <tbody>
            @forelse($d['filas'] as $f)
            <tr>
                <td>{{ $f['unidad'] }}</td>
                <td class="text-center">{{ number_format($f['cant'], 2) }}</td>
                <td class="text-end">{{ number_format($f['costo'], 2) }}</td>
                <td class="text-end">{{ number_format($f['publico'], 2) }}</td>
                <td class="text-end">{{ number_format($f['mayorista'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted">Sin presentaciones.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
