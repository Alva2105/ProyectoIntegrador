<?php

namespace App\Http\Controllers;

use App\Models\Seguimiento;
use App\Models\Repuesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeguimientoDashboardController extends Controller
{
    // ── DASHBOARD PRINCIPAL ─────────────────────────────────────────
    public function index()
    {
        $seguimientos = Seguimiento::with([
                'solicitud.vehiculo',
                'solicitud.cliente',
                'tecnico',
                'repuestosUsados.repuesto',
            ])
            ->whereNull('deleted_at')
            ->orderByDesc('fcs_seg')
            ->paginate(15);

        $eliminados = Seguimiento::onlyTrashed()
            ->with([
                'solicitud.vehiculo',
                'solicitud.cliente',
                'tecnico',
                'repuestosUsados.repuesto',
            ])
            ->orderByDesc('deleted_at')
            ->get();

        return view('dashboard.admin.dashboardSeguimientos', compact('seguimientos', 'eliminados'));
    }

    // ── DETALLE (modal Ver) ─────────────────────────────────────────
    public function detalle(string $cod_seg)
    {
        $seg = Seguimiento::with([
                'solicitud.vehiculo',
                'solicitud.cliente',
                'tecnico',
                'repuestosUsados.repuesto',
            ])
            ->withTrashed()
            ->findOrFail($cod_seg);

        $solicitud = $seg->solicitud;
        $vehiculo  = $solicitud?->vehiculo;
        $tecnico   = $seg->tecnico;
        $tipo      = $solicitud?->tma_sol ?? '—';
        $tipoCss   = str_contains(strtolower($tipo), 'correctivo') ? 'correctivo' : 'preventivo';

        return response()->json([
            'cod_seg'     => $seg->cod_seg,
            'tecnico'     => $tecnico?->nom_usu ?? $tecnico?->nombre ?? '—',
            'placa'       => $vehiculo?->pla_veh ?? $vehiculo?->cod_vehiculos ?? '—',
            'tipo'        => $tipo,
            'tipo_css'    => $tipoCss,
            'fecha'       => $seg->fcs_seg
                                ? \Carbon\Carbon::parse($seg->fcs_seg)->format('d/m/Y H:i')
                                : '—',
            'solicitud'   => $solicitud?->cod_solicitudes ?? '—',
            'observacion' => $seg->obs_seg ?? '',
            'repuestos'   => $seg->repuestosUsados->map(fn($r) => [
                'nombre' => ($r->repuesto?->nom_rep ?? '—') . ' ' . ($r->repuesto?->mod_rep ?? ''),
                'qty'    => $r->can_sol,
            ])->values(),
        ]);
    }

    // ── ELIMINAR (soft delete) ──────────────────────────────────────
    public function eliminar(string $cod_seg)
    {
        $seg = Seguimiento::findOrFail($cod_seg);
        $seg->delete();

        return redirect()->route('dashboard.seguimientos')
            ->with('success', 'Seguimiento movido a la papelera.');
    }

    // ── RESTAURAR ───────────────────────────────────────────────────
    public function restaurar(string $cod_seg)
    {
        $seg = Seguimiento::withTrashed()->findOrFail($cod_seg);
        $seg->restore();
        $seg->update(['restored_at' => now()]);

        return redirect()->route('dashboard.seguimientos')
            ->with('success', 'Seguimiento restaurado correctamente.');
    }

    // ── REPORTE ─────────────────────────────────────────────────────
    public function reporte(Request $request)
    {
        $query = Seguimiento::with([
                'solicitud.vehiculo',
                'solicitud.cliente',
                'tecnico',
                'repuestosUsados.repuesto',
            ])
            ->whereNull('seguimientos.deleted_at');

        // Filtro tipo de mantenimiento
        if ($request->filled('tipo')) {
            $query->whereHas('solicitud', fn($q) =>
                $q->where('tma_sol', 'ilike', '%' . $request->tipo . '%')
            );
        }

        // Filtro técnico
        if ($request->filled('tecnico')) {
            $query->whereHas('tecnico', fn($q) =>
                $q->where('nom_usu', 'ilike', '%' . $request->tecnico . '%')
                  ->orWhere('nombre', 'ilike', '%' . $request->tecnico . '%')
            );
        }

        // Filtro placa
        if ($request->filled('placa')) {
            $query->whereHas('solicitud.vehiculo', fn($q) =>
                $q->where('pla_veh', 'ilike', '%' . $request->placa . '%')
                  ->orWhere('cod_vehiculos', 'ilike', '%' . $request->placa . '%')
            );
        }

        // Filtro desde / hasta
        if ($request->filled('desde')) {
            $query->whereDate('fcs_seg', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fcs_seg', '<=', $request->hasta);
        }

        $seguimientos = $query->orderByDesc('fcs_seg')->get();

        return view('dashboard.admin.reporteSeguimientos', compact('seguimientos'));
    }
}