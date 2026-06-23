<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\Repuesto;
use App\Models\Servicio;
use App\Models\Solicitud;
use App\Models\OrdenServicio;
use App\Models\MantenimientoRepuesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MantenimientoController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ══════════════════════════════════════════════════════════════

    private function datosComunes(): array
    {
        return [
            'servicios'              => Servicio::orderBy('nom_ser')->get(),
            'repuestos'              => Repuesto::orderBy('nom_rep')->get(),
            'solicitudesFinalizadas' => Solicitud::with(['cliente', 'vehiculo'])
                                            ->where('est_sol', 'Finalizado')
                                            ->whereNull('deleted_at')
                                            ->orderBy('fec_sol', 'desc')
                                            ->get(),
        ];
    }

    private function queryActivos()
    {
        return Mantenimiento::with(['solicitud.cliente', 'solicitud.vehiculo'])
            ->whereNull('deleted_at');
    }

    private function queryEliminados()
    {
        return Mantenimiento::with(['solicitud.cliente', 'solicitud.vehiculo'])
            ->whereNotNull('deleted_at')
            ->orderBy('deleted_at', 'desc');
    }

    // ══════════════════════════════════════════════════════════════
    //  LISTADOS
    // ══════════════════════════════════════════════════════════════

    public function listar()
    {
        $mantenimientos = $this->queryActivos()
            ->orderBy('cod_mantenimientos', 'asc')
            ->paginate(10);

        $eliminados = $this->queryEliminados()->get();

        return view('admin.dashboardMantenimientos',
            array_merge($this->datosComunes(), compact('mantenimientos', 'eliminados'))
        );
    }

    public function listarCorrectivos()
    {
        $mantenimientos = $this->queryActivos()
            ->whereHas('solicitud', fn($q) => $q->where('tma_sol', 'Mantenimiento Correctivo'))
            ->orderBy('cod_mantenimientos', 'asc')
            ->paginate(10);

        $eliminados = $this->queryEliminados()
            ->whereHas('solicitud', fn($q) => $q->where('tma_sol', 'Mantenimiento Correctivo'))
            ->get();

        return view('admin.dashboardMantenimientos',
            array_merge($this->datosComunes(), compact('mantenimientos', 'eliminados'))
        );
    }

    public function listarPreventivos()
    {
        $mantenimientos = $this->queryActivos()
            ->whereHas('solicitud', fn($q) => $q->where('tma_sol', 'Mantenimiento Preventivo'))
            ->orderBy('cod_mantenimientos', 'asc')
            ->paginate(10);

        $eliminados = $this->queryEliminados()
            ->whereHas('solicitud', fn($q) => $q->where('tma_sol', 'Mantenimiento Preventivo'))
            ->get();

        return view('admin.dashboardMantenimientos',
            array_merge($this->datosComunes(), compact('mantenimientos', 'eliminados'))
        );
    }

    // ══════════════════════════════════════════════════════════════
    //  BUSCAR (AJAX)
    // ══════════════════════════════════════════════════════════════

    public function buscar(Request $request)
    {
        $q = $request->input('q');

        $mantenimientos = $this->queryActivos()
            ->where(function ($query) use ($q) {
                $query->where('des_man', 'ILIKE', "%$q%")
                      ->orWhereHas('solicitud.cliente',
                            fn($sq) => $sq->where('nom_cli', 'ILIKE', "%$q%")
                                          ->orWhere('app_cli', 'ILIKE', "%$q%")
                      );
            })
            ->orderBy('cod_mantenimientos', 'asc')
            ->get();

        return response()->json($mantenimientos);
    }

    // ══════════════════════════════════════════════════════════════
    //  VER ORDEN
    // ══════════════════════════════════════════════════════════════

    public function verOrden($id)
    {
        $m = Mantenimiento::with([
            'solicitud.cliente',
            'solicitud.vehiculo',
            'ordenServicios.servicio',
            'repuestos',
        ])->findOrFail($id);

        $sol      = $m->solicitud;
        $cliente  = $sol?->cliente;
        $vehiculo = $sol?->vehiculo;

        $servicios = $m->ordenServicios->map(function ($os) {
            return [
                'cod'      => $os->cod_servicios,
                'nom'      => $os->servicio?->nom_ser ?? '—',
                'cantidad' => $os->cantidad,
                'pre_uni'  => (float) ($os->servicio?->pre_ser ?? 0),
            ];
        });

        $repuestos = $m->repuestos->map(function ($rep) {
            $pre = (float) $rep->pivot->pre_uni;
            if ($pre == 0) {
                $pre = (float) $rep->pre_rep;
            }
            return [
                'cod'      => $rep->cod_repuestos,
                'nom'      => $rep->nom_rep,
                'cantidad' => (int) $rep->pivot->cantidad,
                'pre_uni'  => $pre,
            ];
        });
        return response()->json([
            'cliente'     => $cliente ? "{$cliente->nom_cli} {$cliente->app_cli}" : '—',
            'telefono'    => $cliente?->tel_cli ?? '—',
            'vehiculo'    => $vehiculo ? "{$vehiculo->mar_veh} {$vehiculo->mod_veh}" : '—',
            'placa'       => $vehiculo?->cod_vehiculos ?? '—',
            'tipo'        => $sol?->tma_sol ?? '—',
            'descripcion' => $m->des_man,
            'fec_ini'     => $m->fec_ini_man?->format('Y-m-d H:i:s'),
            'fec_fin'     => $m->fec_fin_man?->format('Y-m-d H:i:s'),
            'total'       => (float) $m->total_man,
            'servicios'   => $servicios,
            'repuestos'   => $repuestos,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  ACTUALIZAR ORDEN
    // ══════════════════════════════════════════════════════════════

    public function actualizarOrden(Request $request, $id)
    {
        try {
            $request->validate([
                'servicios'            => 'required|array|min:1',
                'servicios.*.cod'      => 'required|exists:servicios,cod_servicios',
                'servicios.*.cantidad' => 'required|integer|min:1',
                'servicios.*.pre_uni'  => 'required|numeric|min:0',
                'repuestos'            => 'nullable|array',
                'repuestos.*.cod'      => 'nullable|string',
                'repuestos.*.cantidad' => 'nullable|integer|min:1',
                'repuestos.*.pre_uni'  => 'nullable|numeric|min:0',
                'total_man'            => 'nullable|numeric|min:0',
            ]);

            $mantenimiento = Mantenimiento::findOrFail($id);

            DB::transaction(function () use ($request, $mantenimiento, $id) {

                OrdenServicio::where('cod_mantenimientos', $id)->delete();
                foreach ($request->servicios as $srv) {
                    if (empty($srv['cod'])) continue;
                    OrdenServicio::create([
                        'cod_mantenimientos' => $id,
                        'cod_servicios'      => $srv['cod'],
                        'cantidad'           => (int) $srv['cantidad'],
                    ]);
                }

                MantenimientoRepuesto::where('cod_mantenimientos', $id)->delete();
                if ($request->filled('repuestos')) {
                    foreach ($request->repuestos as $rep) {
                        if (empty($rep['cod'])) continue;
                        MantenimientoRepuesto::create([
                            'cod_mantenimientos' => $id,
                            'cod_repuestos'      => $rep['cod'],
                            'cantidad'           => (int) $rep['cantidad'],
                            'pre_uni'            => (float) ($rep['pre_uni'] ?? 0),
                        ]);
                    }
                }

                $mantenimiento->total_man = $request->total_man ?? 0;
                $mantenimiento->des_man    = $request->des_man;
                $mantenimiento->fec_ini_man = $request->fec_ini_man;
                $mantenimiento->fec_fin_man = $request->fec_fin_man;
                $mantenimiento->save();
            });

            return redirect()->back()->with('success', 'Orden de trabajo actualizada correctamente.');

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  GUARDAR — crea mantenimiento + servicios + repuestos
    // ══════════════════════════════════════════════════════════════

    public function guardar(Request $request)
    {
        try {
            $request->validate([
                'cod_solicitudes_man'       => 'required|exists:solicitudes,cod_solicitudes',
                'fec_ini_man'               => 'required|date',
                'fec_fin_man'               => 'nullable|date|after_or_equal:fec_ini_man',
                'des_man'                   => 'nullable|string',
                'total_man'                 => 'nullable|numeric|min:0',
                'servicios'                 => 'required|array|min:1',
                'servicios.*.cod'           => 'required|exists:servicios,cod_servicios',
                'servicios.*.cantidad'      => 'required|integer|min:1',
                'servicios.*.pre_uni'       => 'required|numeric|min:0',
                'repuestos'                 => 'nullable|array',
                'repuestos.*.cod'           => 'nullable|string',
                'repuestos.*.cantidad'      => 'nullable|integer|min:1',
                'repuestos.*.pre_uni'       => 'nullable|numeric|min:0',
            ]);

            DB::transaction(function () use ($request) {

                $mantenimiento = Mantenimiento::create([
                    'cod_solicitudes_man' => $request->cod_solicitudes_man,
                    'fec_ini_man'         => $request->fec_ini_man,
                    'fec_fin_man'         => $request->fec_fin_man,
                    'des_man'             => $request->des_man,
                    'total_man'           => $request->total_man ?? 0,
                ]);

                $codMan = $mantenimiento->cod_mantenimientos;

                foreach ($request->servicios as $srv) {
                    if (empty($srv['cod'])) continue;
                    OrdenServicio::create([
                        'cod_mantenimientos' => $codMan,
                        'cod_servicios'      => $srv['cod'],
                        'cantidad'           => (int) $srv['cantidad'],
                    ]);
                }

                if ($request->filled('repuestos')) {
                    foreach ($request->repuestos as $rep) {
                        if (empty($rep['cod'])) continue;
                        MantenimientoRepuesto::create([
                            'cod_mantenimientos' => $codMan,
                            'cod_repuestos'      => $rep['cod'],
                            'cantidad'           => (int) $rep['cantidad'],
                            'pre_uni'            => (float) ($rep['pre_uni'] ?? 0),
                        ]);
                    }
                }
            });

            return redirect()->back()->with('success', 'Mantenimiento registrado correctamente.');

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  ACTUALIZAR — datos generales del mantenimiento
    // ══════════════════════════════════════════════════════════════

    public function actualizar(Request $request, $id)
    {
        try {
            $mantenimiento = Mantenimiento::findOrFail($id);

            $request->validate([
                'fec_ini_man' => 'required|date',
                'fec_fin_man' => 'nullable|date|after_or_equal:fec_ini_man',
                'des_man'     => 'nullable|string',
            ]);

            $mantenimiento->update([
                'fec_ini_man' => $request->fec_ini_man,
                'fec_fin_man' => $request->fec_fin_man,
                'des_man'     => $request->des_man,
            ]);

            return redirect()->back()->with('success', 'Mantenimiento actualizado correctamente.');

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  ELIMINAR — borrado lógico
    // ══════════════════════════════════════════════════════════════

    public function eliminar($id)
    {
        try {
            $mantenimiento = Mantenimiento::findOrFail($id);
            $mantenimiento->deleted_at  = now();
            $mantenimiento->restored_at = null;
            $mantenimiento->save();

            return redirect()->back()->with('success', 'Mantenimiento movido a la papelera.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  RESTAURAR
    // ══════════════════════════════════════════════════════════════

    public function restaurar($id)
    {
        try {
            $mantenimiento = Mantenimiento::whereNotNull('deleted_at')->findOrFail($id);
            $mantenimiento->deleted_at  = null;
            $mantenimiento->restored_at = now();
            $mantenimiento->save();

            return redirect()->back()->with('success', 'Mantenimiento restaurado correctamente.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  REPORTE
    // ══════════════════════════════════════════════════════════════
   
    public function reporte(Request $request)
    {
        $query = Mantenimiento::with([
                'solicitud.cliente',
                'solicitud.vehiculo',
                'ordenServicios.servicio',
                'repuestos',
            ])
            ->whereNull('deleted_at')
            ->orderBy('cod_mantenimientos', 'asc');
    
        // Filtro por tipo
        if ($request->filled('tipo')) {
            $query->whereHas('solicitud', fn($q) =>
                $q->where('tma_sol', 'ilike', '%' . $request->tipo . '%')
            );
        }
    
        // Filtro por cliente
        if ($request->filled('cliente')) {
            $query->whereHas('solicitud.cliente', fn($q) =>
                $q->where('nom_cli', 'ilike', '%' . $request->cliente . '%')
                ->orWhere('app_cli', 'ilike', '%' . $request->cliente . '%')
            );
        }
    
        // Filtro por rango de fecha de finalización
        if ($request->filled('desde')) {
            $query->whereDate('fec_fin_man', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fec_fin_man', '<=', $request->hasta);
        }
    
        $mantenimientos = $query->get();
    
        return view('admin.reporteMantenimientos', compact('mantenimientos'));
    }

    public function eliminarServicioOrden($id, $codServicio)
    {
        try {
            OrdenServicio::where('cod_mantenimientos', $id)
                ->where('cod_servicios', $codServicio)
                ->delete();

            // Recalcular total
            $this->recalcularTotal($id);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function eliminarRepuestoOrden($id, $codRepuesto)
    {
        try {
            MantenimientoRepuesto::where('cod_mantenimientos', $id)
                ->where('cod_repuestos', $codRepuesto)
                ->delete();

            $this->recalcularTotal($id);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function recalcularTotal($id)
    {
        $totalServicios = OrdenServicio::where('cod_mantenimientos', $id)
            ->join('servicios', 'orden_servicios.cod_servicios', '=', 'servicios.cod_servicios')
            ->sum(\DB::raw('orden_servicios.cantidad * servicios.pre_ser'));

        $totalRepuestos = MantenimientoRepuesto::where('cod_mantenimientos', $id)
            ->sum(\DB::raw('cantidad * pre_uni'));

        Mantenimiento::where('cod_mantenimientos', $id)
            ->update(['total_man' => $totalServicios + $totalRepuestos]);
    }

    public function irAFacturar($id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        if ($mantenimiento->factura) {
            return redirect()
                ->route('facturas.ver', $mantenimiento->factura->cod_facturas)
                ->with('info', 'Este mantenimiento ya tiene la factura #'
                    . $mantenimiento->factura->nfa_fac . '.');
        }

        return redirect()->route('facturas.crear', $id);
    }
}