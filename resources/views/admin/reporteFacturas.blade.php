@extends('layouts.dashboard')

@section('contenido')

@php
    // Total facturado = suma de (cantidad * precio_unitario) de cada detalle, de cada factura
    $totalFacturado = $facturas->sum(fn($f) => $f->detalles->sum(fn($d) => $d->can_det * $d->pun_det));
    $totalFacturas  = $facturas->count();
    $promedioFac    = $totalFacturas > 0 ? $totalFacturado / $totalFacturas : 0;

    // Facturas agrupadas por mes (cantidad emitida, no monto)
    $facturasPorMes = $facturas
        ->groupBy(fn($f) => \Carbon\Carbon::parse($f->fec_fac)->format('Y-m'))
        ->sortKeys();

    $mesesLabels = $facturasPorMes->keys()->map(fn($k) =>
        \Carbon\Carbon::createFromFormat('Y-m', $k)->translatedFormat('M Y')
    )->values()->toJson();

    // Ingresos por mes (usa la colección ya calculada en el controlador, ordenada por clave)
    $ingresosOrdenados = $ingresosPorMes->sortKeys();
    $ingresosData      = $ingresosOrdenados->values()->toJson();

    // Cantidad de facturas emitidas por mes (mismo orden de meses que ingresos)
    $cantidadPorMes = $facturasPorMes->map(fn($g) => $g->count());
    $cantidadData   = $cantidadPorMes->values()->toJson();

    // Top conceptos (dona) ya viene resuelto desde el controlador
    $topLabels = $topConceptos->pluck('con_det')->toJson();
    $topData   = $topConceptos->pluck('total')->toJson();

    // Top 5 clientes por monto facturado (calculado aquí a partir de $facturas, sin tocar el controlador)
    $topClientes = $facturas
        ->filter(fn($f) => $f->cliente)
        ->groupBy(fn($f) => $f->cliente->cod_clientes)
        ->map(function ($grupo) {
            $cliente = $grupo->first()->cliente;
            return [
                'nombre' => trim($cliente->nom_cli.' '.$cliente->app_cli),
                'total'  => $grupo->sum(fn($f) => $f->detalles->sum(fn($d) => $d->can_det * $d->pun_det)),
                'cant'   => $grupo->count(),
            ];
        })
        ->sortByDesc('total')
        ->take(5)
        ->values();
@endphp

<div class="reporte-content">

    {{-- ── HEADER ── --}}
    <div class="reporte-header no-print">
        <h2>
            <span class="material-symbols-outlined" style="vertical-align:middle;">receipt_long</span>
            Reporte de Facturas
        </h2>
        <div style="display:flex; gap:10px;">
            <button class="btn-excel" onclick="exportarExcel()">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">table_view</span>
                Descargar Excel
            </button>
            <button class="btn-imprimir" onclick="window.print()">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">print</span>
                Imprimir / Exportar PDF
            </button>
        </div>
    </div>

    {{-- ── FILA PRINCIPAL: gráficos | filtros ── --}}
    <div class="reporte-top-layout no-print">

        {{-- Gráficos lado a lado --}}
        <div class="graficos-panel">

            {{-- Barras: ingresos y cantidad por mes --}}
            <div class="grafico-card">
                <p class="grafico-titulo">Facturación por mes</p>
                <canvas id="graficaMes"></canvas>
            </div>

            {{-- Dona: top conceptos facturados --}}
            <div class="grafico-card">
                <p class="grafico-titulo">Top 5 conceptos facturados</p>
                <canvas id="graficaTop"></canvas>
            </div>

        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('facturas.reporte') }}"
              class="filtros-reporte">

            <div class="filtro-grupo">
                <label>N° Factura</label>
                <input type="text" name="nfa"
                       value="{{ request('nfa') }}"
                       placeholder="Ej: 000123">
            </div>

            <div class="filtro-grupo">
                <label>Cliente</label>
                <input type="text" name="cliente"
                       value="{{ request('cliente') }}"
                       placeholder="Nombre del cliente...">
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
                <a href="{{ route('facturas.reporte') }}" class="btn-limpiar">Limpiar</a>
            </div>

        </form>

    </div>{{-- /reporte-top-layout --}}

    {{-- ── ENCABEZADO SOLO IMPRESIÓN ── --}}
    <div class="reporte-print-header solo-print">
        <h2>JHIRE Motors — Reporte de Facturas</h2>
        <p>Generado el: {{ now()->format('d/m/Y H:i') }}</p>
        <div class="filtros-aplicados">
            @if(request('nfa'))     <span>N° Factura: {{ request('nfa') }}</span> @endif
            @if(request('cliente')) <span>Cliente: {{ request('cliente') }}</span> @endif
            @if(request('desde'))   <span>Desde: {{ request('desde') }}</span>     @endif
            @if(request('hasta'))   <span>Hasta: {{ request('hasta') }}</span>     @endif
        </div>
    </div>

    {{-- ── TARJETAS DE TOTALES ── --}}
    <div class="resumen-cards">
        <div class="resumen-card total">
            <span class="resumen-num">{{ $totalFacturas }}</span>
            <span class="resumen-label">Facturas emitidas</span>
        </div>
        <div class="resumen-card monto">
            <span class="resumen-num">{{ number_format($totalFacturado, 2) }}</span>
            <span class="resumen-label">Total facturado (Bs.)</span>
        </div>
        <div class="resumen-card promedio">
            <span class="resumen-num">{{ number_format($promedioFac, 2) }}</span>
            <span class="resumen-label">Promedio por factura (Bs.)</span>
        </div>
        <div class="resumen-card meses">
            <span class="resumen-num">{{ $facturasPorMes->count() }}</span>
            <span class="resumen-label">Meses con facturación</span>
        </div>
    </div>

    {{-- ── RANKING DE CLIENTES (tarjeta destacada, dato creativo) ── --}}
    @if($topClientes->isNotEmpty())
        <div class="ranking-clientes no-print">
            <p class="grafico-titulo">Top 5 clientes por monto facturado</p>
            <div class="ranking-lista">
                @foreach($topClientes as $i => $c)
                    <div class="ranking-item">
                        <span class="ranking-pos">{{ $i + 1 }}</span>
                        <span class="ranking-nombre">{{ $c['nombre'] ?: 'Sin nombre' }}</span>
                        <span class="ranking-cant">{{ $c['cant'] }} factura{{ $c['cant'] == 1 ? '' : 's' }}</span>
                        <span class="ranking-monto">Bs. {{ number_format($c['total'], 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── TABLA ── --}}
    <div class="table-wrapper">
        <table id="tablaReporte">
            <thead>
                <tr>
                    <th>N° Factura</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Vehículo</th>
                    <th>Total (Bs.)</th>
                    <th class="no-print">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facturas as $f)
                    @php
                        $cliente  = $f->cliente;
                        $vehiculo = $f->mantenimiento?->solicitud?->vehiculo;
                        $totalFac = $f->detalles->sum(fn($d) => $d->can_det * $d->pun_det);
                    @endphp
                    <tr>
                        <td>{{ $f->nfa_fac }}</td>
                        <td>{{ \Carbon\Carbon::parse($f->fec_fac)->format('d/m/Y') }}</td>
                        <td>{{ $cliente ? $cliente->nom_cli.' '.$cliente->app_cli : '—' }}</td>
                        <td>{{ $vehiculo ? $vehiculo->mar_veh.' '.$vehiculo->mod_veh : '—' }}</td>
                        <td style="font-weight:600;">{{ number_format($totalFac, 2) }}</td>
                        <td class="no-print">
                            <a href="{{ route('facturas.ver', $f->cod_facturas) }}"
                               class="btn-accion" title="Ver / Imprimir">
                                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">visibility</span>
                                Ver
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; color:#aaa; padding:30px;">
                            No hay resultados con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="reporte-footer solo-print">
        Total de facturas: <strong>{{ $totalFacturas }}</strong> —
        Total facturado: <strong>Bs. {{ number_format($totalFacturado, 2) }}</strong>
    </div>

</div>

<style>
    .reporte-content { padding-bottom: 40px; }

    /* ── Header ── */
    .reporte-header {
        display: flex; justify-content: space-between;
        align-items: center; margin-bottom: 20px;
    }
    .reporte-header h2 {
        color: #ff7b00; font-size: 20px; margin: 0;
        display: flex; align-items: center; gap: 8px;
    }
    .btn-imprimir {
        background: #ff7b00; color: #fff; border: none;
        padding: 9px 20px; border-radius: 6px; cursor: pointer;
        font-size: 13px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 6px;
        transition: all 0.2s;
    }
    .btn-imprimir:hover { background: #e06a00; }

    /* ── Fila principal ── */
    .reporte-top-layout {
        display: flex; flex-direction: row;
        gap: 20px; align-items: flex-start;
        margin-bottom: 24px;
    }

    /* ── Gráficos ── */
    .graficos-panel {
        display: flex; flex-direction: row;
        gap: 14px; flex: 1; min-width: 0;
    }
    .grafico-card {
        flex: 1; min-width: 0;
        background: #2a2a2a; border: 1px solid #3a3a3a;
        border-radius: 10px; padding: 14px 16px;
    }
    .grafico-titulo {
        color: #aaa; font-size: 12px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 10px;
    }

    /* ── Filtros ── */
    .filtros-reporte {
        flex: 1; display: flex; flex-direction: column; gap: 12px;
        background: #2a2a2a; border: 1px solid #3a3a3a;
        border-radius: 10px; padding: 18px 20px;
        box-sizing: border-box; margin-bottom: 0;
    }
    .filtro-grupo { display: flex; flex-direction: column; gap: 5px; }
    .filtro-grupo label { color: #ccc; font-size: 12px; font-weight: 500; }
    .filtro-grupo input,
    .filtro-grupo select {
        width: 100%; padding: 7px 10px;
        background: #3a3a3a; border: 1px solid #555;
        border-radius: 6px; color: #fff; font-size: 13px;
        box-sizing: border-box;
    }
    .btn-filtrar {
        background: #ff7b00; color: #fff; border: none;
        padding: 9px 20px; border-radius: 6px; cursor: pointer;
        font-weight: 600; font-size: 13px; width: 100%; transition: all 0.2s;
    }
    .btn-filtrar:hover { background: #e06a00; }
    .btn-limpiar {
        color: #aaa; font-size: 13px; text-decoration: none;
        text-align: center; display: block; padding: 4px 0; transition: color 0.2s;
    }
    .btn-limpiar:hover { color: #fff; }

    /* ── Tarjetas totales ── */
    .resumen-cards { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
    .resumen-card {
        flex: 1; min-width: 120px;
        background: #2a2a2a; border-radius: 10px;
        padding: 16px; text-align: center; border-top: 3px solid #444;
    }
    .resumen-card.total      { border-top-color: #ff7b00; }
    .resumen-card.monto      { border-top-color: #28a745; }
    .resumen-card.promedio   { border-top-color: #17a2b8; }
    .resumen-card.meses      { border-top-color: #3a8fd4; }
    .resumen-num   { display:block; font-size:24px; font-weight:700; color:#fff; }
    .resumen-label { display:block; font-size:12px; color:#aaa; margin-top:4px; }

    /* ── Ranking de clientes ── */
    .ranking-clientes {
        background: #2a2a2a; border: 1px solid #3a3a3a;
        border-radius: 10px; padding: 16px 18px; margin-bottom: 24px;
    }
    .ranking-lista { display: flex; flex-direction: column; gap: 8px; }
    .ranking-item {
        display: flex; align-items: center; gap: 14px;
        background: #1e1e1e; border-radius: 8px; padding: 10px 14px;
    }
    .ranking-pos {
        width: 24px; height: 24px; border-radius: 50%;
        background: #ff7b00; color: #fff; font-size: 12px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ranking-nombre { flex: 1; color: #fff; font-size: 13px; font-weight: 500; }
    .ranking-cant   { color: #aaa; font-size: 12px; }
    .ranking-monto  { color: #28a745; font-size: 13px; font-weight: 700; min-width: 110px; text-align: right; }

    /* ── Tabla ── */
    .btn-accion {
        color: #ff7b00; text-decoration: none; font-size: 12px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 4px;
        border: 1px solid #ff7b00; border-radius: 6px; padding: 4px 10px;
        transition: all 0.2s;
    }
    .btn-accion:hover { background: #ff7b00; color: #fff; }

    /* ── Solo impresión ── */
    .solo-print { display: none; }
    .reporte-print-header { margin-bottom: 20px; }
    .reporte-print-header h2 { font-size: 18px; margin-bottom: 4px; }
    .reporte-print-header p  { font-size: 12px; color: #555; }
    .filtros-aplicados { display:flex; gap:12px; flex-wrap:wrap; margin-top:6px; font-size:11px; }
    .filtros-aplicados span { background:#eee; padding:2px 8px; border-radius:4px; color:#333; }
    .reporte-footer { margin-top:16px; font-size:12px; text-align:right; }

    /* ════════════════════════════════════
       IMPRESIÓN
    ════════════════════════════════════ */
    @media print {
        body * { visibility: hidden; }
        .reporte-content, .reporte-content * { visibility: visible; }
        .reporte-content { position: absolute; top: 0; left: 0; width: 100%; }
        .no-print   { display: none !important; }
        .solo-print { display: block !important; }
        table { border-collapse: collapse; width: 100%; font-size: 11px; }
        th, td { border: 1px solid #999; padding: 5px 8px; color: #000 !important; }
        th { background: #f0f0f0 !important; font-weight: bold; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .resumen-cards { display: flex !important; }
        .resumen-card  { border: 1px solid #ccc; background: #fff; }
        .resumen-num   { color: #000 !important; }
        .resumen-label { color: #555 !important; }
    }

    .btn-excel {
        background: #1a6b3c;
        color: #fff;
        border: none;
        padding: 9px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .btn-excel:hover { background: #157a36; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
Chart.defaults.color       = '#aaa';
Chart.defaults.borderColor = '#3a3a3a';

/* ── Barras combinadas: ingresos (Bs.) + cantidad de facturas por mes ── */
new Chart(document.getElementById('graficaMes'), {
    type: 'bar',
    data: {
        labels: {!! $mesesLabels !!},
        datasets: [
            {
                label: 'Ingresos (Bs.)',
                data: {!! $ingresosData !!},
                backgroundColor: '#ff7b00',
                borderRadius: 6,
                borderWidth: 0,
                yAxisID: 'y'
            },
            {
                label: 'N° facturas',
                data: {!! $cantidadData !!},
                type: 'line',
                borderColor: '#3a8fd4',
                backgroundColor: '#3a8fd4',
                tension: 0.3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, labels: { boxWidth: 10, font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label === 'Ingresos (Bs.)'
                        ? ` Bs. ${Number(ctx.raw).toFixed(2)}`
                        : ` ${ctx.raw} facturas`
                }
            }
        },
        scales: {
            x: { grid: { color: '#3a3a3a' } },
            y:  { beginAtZero: true, position: 'left',  grid: { color: '#3a3a3a' } },
            y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { stepSize: 1 } }
        }
    }
});

/* ── Dona: top 5 conceptos facturados ── */
new Chart(document.getElementById('graficaTop'), {
    type: 'doughnut',
    data: {
        labels: {!! $topLabels !!},
        datasets: [{
            data: {!! $topData !!},
            backgroundColor: ['#ff7b00', '#3a8fd4', '#9b59b6', '#28a745', '#17a2b8'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: Bs. ${Number(ctx.raw).toFixed(2)}` } }
        }
    }
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function exportarExcel() {
    const tabla = document.getElementById('tablaReporte');

    // Clonar tabla y quitar la columna de Acciones antes de exportar
    const clon = tabla.cloneNode(true);
    clon.querySelectorAll('tr').forEach(fila => {
        const ultimaCelda = fila.lastElementChild;
        if (ultimaCelda) ultimaCelda.remove();
    });

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(clon, { raw: false });

    // Ancho de columnas
    ws['!cols'] = [
        { wch: 14 },  // N° Factura
        { wch: 12 },  // Fecha
        { wch: 25 },  // Cliente
        { wch: 22 },  // Vehículo
        { wch: 14 },  // Total Bs.
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Facturas');

    const hoy = new Date().toISOString().split('T')[0];
    XLSX.writeFile(wb, `Reporte_Facturas_${hoy}.xlsx`);
}
</script>
@endsection