@extends('layouts.dashboard')

@section('contenido')

@if(session('success'))
    <div style="background:#28a745;color:#fff;padding:14px 20px;border-radius:8px;margin-bottom:16px;">
        <strong>✓</strong> {{ session('success') }}
    </div>
@endif
@if(session('info'))
    <div style="background:#17a2b8;color:#fff;padding:14px 20px;border-radius:8px;margin-bottom:16px;">
        ℹ {{ session('info') }}
    </div>
@endif

<div class="Dmant-content">
    <div class="header-content">
        <h2>
            <span class="material-symbols-outlined" style="vertical-align:middle;color:#ff7b00;">receipt_long</span>
            Facturas emitidas
            <a href="{{ route('facturas.reporte') }}" class="btn-reporte">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">summarize</span>
                Reporte
            </a>
        </h2>
    </div>
    
    <div class="search-container" style="margin-bottom:16px;">
        <form method="GET" action="{{ route('dashboard.facturas') }}">
            <div class="search-box">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Buscar por N° factura o cliente...">
                <div class="search-icon">
                    <span class="material-symbols-outlined buscador">search</span>
                </div>
            </div>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>N° Factura</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Vehículo</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facturas as $fac)
                    @php
                        $cliente  = $fac->cliente;
                        $vehiculo = $fac->mantenimiento?->solicitud?->vehiculo;
                    @endphp
                    <tr>
                        <td class="id-usuario">
                            <span class="material-symbols-outlined icono-perfil">receipt_long</span>
                            {{ $fac->nfa_fac }}
                        </td>
                        <td>{{ \Carbon\Carbon::parse($fac->fec_fac)->format('d/m/Y') }}</td>
                        <td>
                            @if($cliente)
                                <div class="perfil-conductor">
                                    <span class="material-symbols-outlined icono-perfil">account_circle</span>
                                    <span>{{ $cliente->nom_cli }} {{ $cliente->app_cli }}</span>
                                </div>
                            @else
                                <span style="color:#777;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($vehiculo)
                                <div class="perfil-conductor">
                                    <span class="material-symbols-outlined icono-perfil">directions_car</span>
                                    <span>
                                        {{ $vehiculo->mar_veh }} {{ $vehiculo->mod_veh }}
                                        @if($vehiculo->ani_veh)({{ $vehiculo->ani_veh }})@endif
                                    </span>
                                </div>
                            @else
                                <span style="color:#777;">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge-total">
                                Bs {{ number_format($fac->detalles->sum(fn($d) => $d->can_det * $d->pun_det), 2) }}
                            </span>
                        </td>
                        <td class="acciones">
                            <a href="{{ route('facturas.ver', $fac->cod_facturas) }}"
                               class="btn-fac-ver">
                                <span class="material-symbols-outlined">visibility</span>
                                Ver
                            </a>
                            <a href="{{ route('facturas.ver', $fac->cod_facturas) }}"
                               target="_blank"
                               class="btn-fac-imprimir">
                                <span class="material-symbols-outlined">print</span>
                                Imprimir
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:#aaa;padding:40px;">
                            <span class="material-symbols-outlined" style="font-size:40px;display:block;margin-bottom:10px;opacity:.3;">receipt_long</span>
                            No hay facturas emitidas aún.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($facturas->hasPages())
    <div class="pagination-container">
        @if($facturas->onFirstPage())
            <span class="page-btn disabled">&#10094;</span>
        @else
            <a href="{{ $facturas->previousPageUrl() }}" class="page-btn">&#10094;</a>
        @endif
        @for($i = max(1,$facturas->currentPage()-2); $i <= min($facturas->lastPage(),$facturas->currentPage()+2); $i++)
            @if($i == $facturas->currentPage())
                <span class="page-btn active">{{ $i }}</span>
            @else
                <a href="{{ $facturas->url($i) }}" class="page-btn">{{ $i }}</a>
            @endif
        @endfor
        @if($facturas->hasMorePages())
            <a href="{{ $facturas->nextPageUrl() }}" class="page-btn">&#10095;</a>
        @else
            <span class="page-btn disabled">&#10095;</span>
        @endif
    </div>
    @endif

</div>

<style>
.badge-total {
    color: #4caf50;
    font-weight: 700;
    font-size: 14px;
}

.btn-fac-ver {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: transparent;
    color: #17a2b8;
    border: 1px solid #17a2b8;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s;
    cursor: pointer;
}
.btn-fac-ver .material-symbols-outlined { font-size: 15px; }
.btn-fac-ver:hover {
    background: #17a2b8;
    color: #fff;
}

.btn-fac-imprimir {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: transparent;
    color: #ff7b00;
    border: 1px solid #ff7b00;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s;
    cursor: pointer;
}
.btn-fac-imprimir .material-symbols-outlined { font-size: 15px; }
.btn-fac-imprimir:hover {
    background: #ff7b00;
    color: #fff;
}

.btn-reporte {
    background: transparent;
    color: #ff7b00;
    border: 1px solid #ff7b00;
    padding: 8px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    margin-left: 10px;
    text-decoration: none;
}
.btn-reporte:hover { background: #ff7b00; color: #fff; }
</style>

@endsection