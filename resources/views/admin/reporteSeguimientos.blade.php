@extends('layouts.dashboard')

@section('contenido')

@php
    $total       = $seguimientos->count();
    $preventivos = $seguimientos->filter(fn($s) => str_contains(strtolower($s->solicitud?->tma_sol ?? ''), 'preventivo'))->count();
    $correctivos = $seguimientos->filter(fn($s) => str_contains(strtolower($s->solicitud?->tma_sol ?? ''), 'correctivo'))->count();
    $conRep      = $seguimientos->filter(fn($s) => $s->repuestosUsados->count() > 0)->count();

    // Agrupar por mes
    $porMes = $seguimientos
        ->filter(fn($s) => $s->fcs_seg)
        ->groupBy(fn($s) => \Carbon\Carbon::parse($s->fcs_seg)->format('Y-m'))
        ->map(fn($g) => $g->count())
        ->sortKeys();

    $mesesLabels = $porMes->keys()->map(fn($k) =>
        \Carbon\Carbon::createFromFormat('Y-m', $k)->translatedFormat('M Y')
    )->values()->toJson();
    $mesesData = $porMes->values()->toJson();
@endphp

<div class="reporte-content">

    {{-- ── HEADER ── --}}
    <div class="reporte-header no-print">
        <h2>
            <span class="material-symbols-outlined" style="vertical-align:middle;">sticky_note_2</span>
            Reporte de Seguimientos
        </h2>
        <div style="display:flex;gap:10px;">
            <button class="btn-excel" onclick="exportarExcel()">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">table_view</span>
                Descargar Excel
            </button>
            <button class="btn-imprimir" onclick="window.print()">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">print</span>
                Imprimir / PDF
            </button>
        </div>
    </div>

    {{-- ── FILA PRINCIPAL: gráficos | filtros ── --}}
    <div class="reporte-top-layout no-print">

        <div class="graficos-panel">
            <div class="grafico-card">
                <p class="grafico-titulo">Seguimientos por mes</p>
                <canvas id="graficaMes"></canvas>
            </div>
            <div class="grafico-card">
                <p class="grafico-titulo">Por tipo de mantenimiento</p>
                <canvas id="graficaTipo"></canvas>
            </div>
        </div>

        <form method="GET" action="{{ route('seguimientos.reporte') }}" class="filtros-reporte">
            <div class="filtro-grupo">
                <label>Tipo</label>
                <select name="tipo">
                    <option value="">Todos</option>
                    <option value="Preventivo" {{ request('tipo') === 'Preventivo' ? 'selected' : '' }}>Preventivo</option>
                    <option value="Correctivo" {{ request('tipo') === 'Correctivo' ? 'selected' : '' }}>Correctivo</option>
                </select>
            </div>
            <div class="filtro-grupo">
                <label>Técnico</label>
                <input type="text" name="tecnico"
                       value="{{ request('tecnico') }}"
                       placeholder="Nombre del técnico...">
            </div>
            <div class="filtro-grupo">
                <label>Placa</label>
                <input type="text" name="placa"
                       value="{{ request('placa') }}"
                       placeholder="Placa del vehículo...">
            </div>
            <div class="filtro-grupo">
                <label>Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}">
            </div>
            <div class="filtro-grupo">
                <label>Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}">
            </div>
            <div class="filtro-grupo">
                <button type="submit" class="btn-filtrar">Filtrar</button>
                <a href="{{ route('seguimientos.reporte') }}" class="btn-limpiar">Limpiar</a>
            </div>
        </form>

    </div>

    {{-- ── ENCABEZADO SOLO IMPRESIÓN ── --}}
    <div class="reporte-print-header solo-print">
        <h2>JHIRE Motors — Reporte de Seguimientos</h2>
        <p>Generado el: {{ now()->format('d/m/Y H:i') }}</p>
        <div class="filtros-aplicados">
            @if(request('tipo'))    <span>Tipo: {{ request('tipo') }}</span>       @endif
            @if(request('tecnico')) <span>Técnico: {{ request('tecnico') }}</span> @endif
            @if(request('placa'))   <span>Placa: {{ request('placa') }}</span>     @endif
            @if(request('desde'))   <span>Desde: {{ request('desde') }}</span>     @endif
            @if(request('hasta'))   <span>Hasta: {{ request('hasta') }}</span>     @endif
        </div>
    </div>

    {{-- ── TARJETAS RESUMEN ── --}}
    <div class="resumen-cards">
        <div class="resumen-card total">
            <span class="resumen-num">{{ $total }}</span>
            <span class="resumen-label">Total registros</span>
        </div>
        <div class="resumen-card preventivo">
            <span class="resumen-num">{{ $preventivos }}</span>
            <span class="resumen-label">Preventivos</span>
        </div>
        <div class="resumen-card correctivo">
            <span class="resumen-num">{{ $correctivos }}</span>
            <span class="resumen-label">Correctivos</span>
        </div>
        <div class="resumen-card con-rep">
            <span class="resumen-num">{{ $conRep }}</span>
            <span class="resumen-label">Con repuestos</span>
        </div>
    </div>

    {{-- ── TABLA ── --}}
    <div class="table-wrapper">
        <table id="tablaReporte">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Técnico</th>
                    <th>Placa</th>
                    <th>Tipo</th>
                    <th>Observación</th>
                    <th>Repuestos</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($seguimientos as $seg)
                    @php
                        $tecnico  = $seg->tecnico;
                        $solicitud= $seg->solicitud;
                        $vehiculo = $solicitud?->vehiculo;
                        $tipo     = $solicitud?->tma_sol ?? '—';
                    @endphp
                    <tr>
                        <td>{{ $seg->cod_seg }}</td>
                        <td>{{ $tecnico?->nom_usu ?? $tecnico?->nombre ?? '—' }}</td>
                        <td>
                            <span style="font-weight:700;color:#ff7b00;">
                                {{ $vehiculo?->pla_veh ?? $vehiculo?->cod_vehiculos ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge-tipo {{ str_contains(strtolower($tipo), 'correctivo') ? 'correctivo' : 'preventivo' }}">
                                {{ $tipo }}
                            </span>
                        </td>
                        <td class="desc-col">{{ $seg->obs_seg ?? '—' }}</td>
                        <td style="text-align:center;">
                            @php $cnt = $seg->repuestosUsados->count(); @endphp
                            {{ $cnt > 0 ? $cnt : '—' }}
                        </td>
                        <td>{{ $seg->fcs_seg ? \Carbon\Carbon::parse($seg->fcs_seg)->format('d/m/Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#aaa;padding:30px;">
                            No hay resultados con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="reporte-footer solo-print">
        Total de registros: <strong>{{ $total }}</strong>
    </div>

</div>

<style>
.reporte-content { padding-bottom:40px; }
.reporte-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.reporte-header h2 { color:#ff7b00; font-size:20px; margin:0; display:flex; align-items:center; gap:8px; }

.btn-imprimir { background:#ff7b00; color:#fff; border:none; padding:9px 20px; border-radius:6px;
    cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
.btn-imprimir:hover { background:#e06a00; }
.btn-excel { background:#1a6b3c; color:#fff; border:none; padding:9px 20px; border-radius:6px;
    cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
.btn-excel:hover { background:#157a36; }

.reporte-top-layout { display:flex; flex-direction:row; gap:20px; align-items:flex-start; margin-bottom:24px; }
.graficos-panel { display:flex; flex-direction:row; gap:14px; flex:1; min-width:0; }
.grafico-card { flex:1; min-width:0; background:#2a2a2a; border:1px solid #3a3a3a; border-radius:10px; padding:14px 16px; }
.grafico-titulo { color:#aaa; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin:0 0 10px; }

.filtros-reporte { flex:1; display:flex; flex-direction:column; gap:12px; background:#2a2a2a;
    border:1px solid #3a3a3a; border-radius:10px; padding:18px 20px; box-sizing:border-box; }
.filtro-grupo { display:flex; flex-direction:column; gap:5px; }
.filtro-grupo label { color:#ccc; font-size:12px; font-weight:500; }
.filtro-grupo input,
.filtro-grupo select { width:100%; padding:7px 10px; background:#3a3a3a; border:1px solid #555;
    border-radius:6px; color:#fff; font-size:13px; box-sizing:border-box; }
.btn-filtrar { background:#ff7b00; color:#fff; border:none; padding:9px 20px; border-radius:6px;
    cursor:pointer; font-weight:600; font-size:13px; width:100%; transition:all .2s; }
.btn-filtrar:hover { background:#e06a00; }
.btn-limpiar { color:#aaa; font-size:13px; text-decoration:none; text-align:center;
    display:block; padding:4px 0; transition:color .2s; }
.btn-limpiar:hover { color:#fff; }

.resumen-cards { display:flex; gap:12px; margin-bottom:24px; flex-wrap:wrap; }
.resumen-card { flex:1; min-width:120px; background:#2a2a2a; border-radius:10px;
    padding:16px; text-align:center; border-top:3px solid #444; }
.resumen-card.total      { border-top-color:#ff7b00; }
.resumen-card.preventivo { border-top-color:#3a8fd4; }
.resumen-card.correctivo { border-top-color:#9b59b6; }
.resumen-card.con-rep    { border-top-color:#28a745; }
.resumen-num   { display:block; font-size:24px; font-weight:700; color:#fff; }
.resumen-label { display:block; font-size:12px; color:#aaa; margin-top:4px; }

.badge-tipo { padding:3px 10px; border-radius:8px; font-size:12px; font-weight:600;
    background:transparent; white-space:nowrap; }
.badge-tipo.preventivo { border:1px solid #3a8fd4; color:#3a8fd4; }
.badge-tipo.correctivo { border:1px solid #9b59b6; color:#9b59b6; }

.desc-col { max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#aaa; font-size:13px; }

.solo-print { display:none; }
.reporte-print-header { margin-bottom:20px; }
.reporte-print-header h2 { font-size:18px; margin-bottom:4px; }
.reporte-print-header p  { font-size:12px; color:#555; }
.filtros-aplicados { display:flex; gap:12px; flex-wrap:wrap; margin-top:6px; font-size:11px; }
.filtros-aplicados span { background:#eee; padding:2px 8px; border-radius:4px; color:#333; }
.reporte-footer { margin-top:16px; font-size:12px; text-align:right; }

@media print {
    body * { visibility:hidden; }
    .reporte-content, .reporte-content * { visibility:visible; }
    .reporte-content { position:absolute; top:0; left:0; width:100%; }
    .no-print   { display:none !important; }
    .solo-print { display:block !important; }
    table { border-collapse:collapse; width:100%; font-size:11px; }
    th, td { border:1px solid #999; padding:5px 8px; color:#000 !important; }
    th { background:#f0f0f0 !important; font-weight:bold; }
    tr:nth-child(even) td { background:#f9f9f9; }
    .badge-tipo { border:1px solid #999 !important; color:#000 !important; padding:1px 4px; }
    .resumen-cards { display:flex !important; }
    .resumen-card  { border:1px solid #ccc; background:#fff; }
    .resumen-num   { color:#000 !important; }
    .resumen-label { color:#555 !important; }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
Chart.defaults.color       = '#aaa';
Chart.defaults.borderColor = '#3a3a3a';

new Chart(document.getElementById('graficaMes'), {
    type: 'bar',
    data: {
        labels: {!! $mesesLabels !!},
        datasets: [{
            label: 'Seguimientos',
            data:  {!! $mesesData !!},
            backgroundColor: '#ff7b00',
            borderRadius: 6,
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.raw} seguimientos` } }
        },
        scales: {
            x: { grid: { color:'#3a3a3a' } },
            y: { beginAtZero:true, ticks:{ stepSize:1 }, grid:{ color:'#3a3a3a' } }
        }
    }
});

new Chart(document.getElementById('graficaTipo'), {
    type: 'bar',
    data: {
        labels: ['Preventivo', 'Correctivo'],
        datasets: [{
            data: [{{ $preventivos }}, {{ $correctivos }}],
            backgroundColor: ['#3a8fd4', '#9b59b6'],
            borderRadius: 6,
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.raw} seguimientos` } }
        },
        scales: {
            x: { beginAtZero:true, ticks:{ stepSize:1 }, grid:{ color:'#3a3a3a' } },
            y: { grid:{ display:false } }
        }
    }
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function exportarExcel() {
    const tabla = document.getElementById('tablaReporte');
    const clon  = tabla.cloneNode(true);
    clon.querySelectorAll('span.badge-tipo').forEach(span => {
        span.replaceWith(document.createTextNode(span.textContent.trim()));
    });

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(clon, { raw: false });
    ws['!cols'] = [
        { wch: 14 }, // ID
        { wch: 25 }, // Técnico
        { wch: 14 }, // Placa
        { wch: 18 }, // Tipo
        { wch: 40 }, // Observación
        { wch: 10 }, // Repuestos
        { wch: 18 }, // Fecha
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Seguimientos');
    const hoy = new Date().toISOString().split('T')[0];
    XLSX.writeFile(wb, `Reporte_Seguimientos_${hoy}.xlsx`);
}
</script>

@endsection