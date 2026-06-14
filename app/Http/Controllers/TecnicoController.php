<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Solicitud;
use App\Models\Repuesto;
use App\Models\Asignacion;
use App\Models\Seguimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TecnicoController extends Controller
{
    private function getTecnicoActual(): ?Usuario
    {
        $user = Auth::user();
        if ($user && $user->cod_roles_usu === 3) {
            return $user;
        }
        return null;
    }

    // ── MIS ASIGNACIONES (pendientes / en proceso) ──────────────────
    public function asignaciones()
    {
        $user    = Auth::user();
        $tecnico = $this->getTecnicoActual();

        if (!$tecnico) {
            $asignaciones = collect();
        } else {
            $asignaciones = Asignacion::where('cod_usuarios_asi', $tecnico->cod_usuarios)
                ->whereHas('solicitud', fn($q) =>
                    $q->whereNotIn('est_sol', ['Finalizado', 'Cancelado'])
                )
                ->with([
                    'solicitud.vehiculo',
                    'solicitud.cliente',
                ])
                ->orderByDesc('fec_asi')
                ->get();
        }

        return view('technical.asignaciones', compact('user', 'asignaciones'));
    }

    // ── FINALIZADOS ──────────────────────────────────────────────────
    public function asignacionesFinalizadas()
    {
        $user    = Auth::user();
        $tecnico = $this->getTecnicoActual();

        if (!$tecnico) abort(403);

        $asignaciones = Asignacion::where('cod_usuarios_asi', $tecnico->cod_usuarios)
            ->whereHas('solicitud', fn($q) =>
                $q->whereIn('est_sol', ['Finalizado', 'Cancelado'])
            )
            ->with([
                'solicitud.vehiculo',
                'solicitud.cliente',
            ])
            ->orderByDesc('fec_asi')
            ->get();

        return view('technical.finalizados', compact('user', 'asignaciones'));
    }

    // ── FINALIZAR SOLICITUD ──────────────────────────────────────────
    public function finalizarAsignacion(string $cod)
    {
        $tecnico = $this->getTecnicoActual();
        if (!$tecnico) abort(403);

        $asignacion = Asignacion::where('cod_solicitudes_asi', $cod)
            ->where('cod_usuarios_asi', $tecnico->cod_usuarios)
            ->firstOrFail();

        $solicitud = Solicitud::findOrFail($cod);

        if (in_array($solicitud->est_sol, ['Finalizado', 'Cancelado'])) {
            return redirect()->route('tecnico.asignaciones')
                ->with('error', 'Esta solicitud ya no puede modificarse.');
        }

        $solicitud->est_sol = 'Finalizado';
        $solicitud->save();

        return redirect()->route('tecnico.asignaciones')
            ->with('success', 'Solicitud finalizada correctamente.');
    }

    // ── TABLÓN DE SEGUIMIENTO ────────────────────────────────────────
    public function tablon_seguimiento($cod_sol)
    {
        $tecnico = $this->getTecnicoActual();  // ← CORREGIDO
        if (!$tecnico) abort(403);

        $asignacion = Asignacion::where('cod_solicitudes_asi', $cod_sol)
            ->where('cod_usuarios_asi', $tecnico->cod_usuarios)
            ->firstOrFail();

        $solicitud = Solicitud::with(['vehiculo', 'cliente'])
            ->findOrFail($cod_sol);

        $seguimientos = Seguimiento::where('cod_solicitudes_seg', $cod_sol)
            ->with(['repuestosUsados.repuesto'])
            ->orderBy('fcs_seg')
            ->get();

        $eliminados = Seguimiento::withTrashed()
            ->where('cod_solicitudes_seg', $cod_sol)
            ->whereNotNull('deleted_at')
            ->orderBy('deleted_at', 'desc')
            ->get();

        $origen = in_array($solicitud->est_sol, ['Finalizado', 'Cancelado'])
            ? 'finalizados'
            : session('origen_mantenimiento', 'asignados');

        return view('technical.tablon-seguimiento', compact(  // ← ajusta al nombre real de tu vista
            'seguimientos', 'eliminados', 'solicitud', 'origen'
        ));
    }

    // ── NUEVO SEGUIMIENTO ────────────────────────────────────────────
    public function nuevoSeguimiento(string $cod_sol)
    {
        $user    = Auth::user();
        $tecnico = $this->getTecnicoActual();

        if (!$tecnico) abort(403);

        $solicitud = Solicitud::with(['vehiculo', 'cliente'])
            ->findOrFail($cod_sol);

        $repuestosDisponibles = Repuesto::where('stock', '>', 0)
            ->orderBy('nom_rep')
            ->get();

        return view('technical.new-seguimiento', compact(
            'user',
            'solicitud',
            'repuestosDisponibles'
        ));
    }

    // ── GUARDAR SEGUIMIENTO ──────────────────────────────────────────
    public function guardarSeguimiento(Request $request)
    {
        $request->validate([
            'cod_sol'     => 'required|string|exists:solicitudes,cod_solicitudes',
            'obs_avance'  => 'nullable|string|max:1000',
            'repuestos'   => 'nullable|array',
            'repuestos.*' => 'nullable|string|exists:repuestos,cod_repuestos',
            'qty'         => 'nullable|array',
            'qty.*'       => 'nullable|integer|min:1',
        ]);

        $tecnico = $this->getTecnicoActual();
        if (!$tecnico) abort(403);

        DB::beginTransaction();

        try {
            // 1. Crear el seguimiento
            // DESPUÉS
            Seguimiento::create([
                'cod_solicitudes_seg' => $request->cod_sol,
                'cod_usuarios_seg'    => $tecnico->cod_usuarios,
                'fcs_seg'             => now(),
                'obs_seg'             => $request->obs_avance,
            ]);

            // Recuperar el registro recién insertado directamente desde BD
            $seguimiento = Seguimiento::where('cod_solicitudes_seg', $request->cod_sol)
                ->where('cod_usuarios_seg', $tecnico->cod_usuarios)
                ->latest('created_at')
                ->first();

            if (!$seguimiento || !$seguimiento->cod_seg) {
                throw new \Exception('No se pudo recuperar el seguimiento tras la inserción.');
            }

            // 2. Registrar repuestos si se enviaron
            $reqRepuestos = $request->input('repuestos', []);
            $reqQtys      = $request->input('qty', []);

            foreach ($reqRepuestos as $i => $codRep) {
                if (!$codRep) continue;
                $qty = (int) ($reqQtys[$i] ?? 1);
                if ($qty <= 0) continue;

                // Verificar stock
                $rep = Repuesto::lockForUpdate()->find($codRep);
                if (!$rep) continue;

                if ($rep->stock < $qty) {
                    throw new \Exception(
                        "Stock insuficiente para {$rep->nom_rep}. Disponible: {$rep->stock}, Solicitado: {$qty}"
                    );
                }

                // Descontar stock
                $rep->stock -= $qty;
                $rep->save();

                // Registrar en solicitudes_repuestos vinculado al seguimiento
                // El trigger genera cod_solicitudesrep automáticamente
                DB::table('solicitudes_repuestos')->insert([
                    'can_sol'           => $qty,
                    'fec_sol_rep'       => now()->toDateString(),
                    'cod_repuestos_sol' => $codRep,
                    'cod_usuarios_sol'  => $tecnico->cod_usuarios,
                    'cod_seg'           => $seguimiento->cod_seg,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('tecnico.seguimiento', $request->cod_sol)
                ->with('success', 'Seguimiento registrado correctamente.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function reportesTecnico()
    {
        $user = Auth::user();
        return view('technical.reportesTecnico', compact('user'));
    }

    public function edit($cod_seg)
    {
        $seg = Seguimiento::findOrFail($cod_seg);
        abort_if(
            in_array($seg->solicitud->est_sol, ['Finalizado', 'Cancelado']),
            403
        );
        return view('tecnico.seguimiento.edit', compact('seg'));
    }

    public function update(Request $request, $cod_seg)
    {
        $seg = Seguimiento::findOrFail($cod_seg);
        abort_if(in_array($seg->solicitud->est_sol, ['Finalizado','Cancelado']), 403);

        $request->validate([
            'obs_seg' => 'required|string|max:1000',
            'fcs_seg' => 'required|date',
        ]);

        $seg->update($request->only('obs_seg', 'fcs_seg'));

        return redirect()
            ->route('tecnico.seguimiento.tablon', $seg->cod_solicitudes_seg)
            ->with('success', 'Seguimiento actualizado.');
    }

    public function destroy($cod_seg)
    {
        $tecnico = $this->getTecnicoActual();
        if (!$tecnico) abort(403);

        $seg = Seguimiento::findOrFail($cod_seg);

        // Verifica que la solicitud no esté cerrada
        abort_if(
            in_array($seg->solicitud->est_sol, ['Finalizado', 'Cancelado']),
            403
        );

        $seg->delete();

        return redirect()
            ->route('tecnico.seguimiento', $seg->cod_solicitudes_seg)
            ->with('success', 'Seguimiento eliminado.');
    }

    public function restore($cod_seg)
    {
        $tecnico = $this->getTecnicoActual();
        if (!$tecnico) abort(403);

        $seg = Seguimiento::withTrashed()->findOrFail($cod_seg);

        $seg->restore();
        $seg->update(['restored_at' => now()]);

        return redirect()
            ->route('tecnico.seguimiento', $seg->cod_solicitudes_seg)
            ->with('success', 'Seguimiento restaurado.');
    }

    // ── Abrir modal editar repuestos ─────────────────────────
public function editarRepuestos($cod_seg)
{
    $tecnico = $this->getTecnicoActual();
    if (!$tecnico) abort(403);

    $seg = Seguimiento::with(['repuestosUsados.repuesto', 'solicitud'])
        ->findOrFail($cod_seg);

    abort_if(in_array($seg->solicitud->est_sol, ['Finalizado', 'Cancelado']), 403);

    $repuestosDisponibles = Repuesto::where('stock', '>', 0)
        ->orderBy('nom_rep')
        ->get();

    return response()->json([
        'cod_seg'    => $seg->cod_seg,
        'obs_seg'    => $seg->obs_seg,
        'repuestos'  => $seg->repuestosUsados->map(fn($r) => [
            'cod_solicitudesrep' => $r->cod_solicitudesrep,
            'cod_rep'            => $r->cod_repuestos_sol,
            'nombre'             => ($r->repuesto->nom_rep ?? '—').' '.($r->repuesto->mod_rep ?? ''),
            'qty'                => $r->can_sol,
            'stock_disponible'   => $r->repuesto->stock ?? 0,
        ]),
        'disponibles' => $repuestosDisponibles->map(fn($r) => [
            'cod'    => $r->cod_repuestos,
            'nombre' => $r->nom_rep.' '.($r->mod_rep ?? ''),
            'stock'  => $r->stock,
        ]),
    ]);
}

// ── Guardar cambios de repuestos ─────────────────────────
public function actualizarRepuestos(Request $request, $cod_seg)
{
    $tecnico = $this->getTecnicoActual();
    if (!$tecnico) abort(403);

    $seg = Seguimiento::with(['repuestosUsados.repuesto', 'solicitud'])
        ->findOrFail($cod_seg);

    abort_if(in_array($seg->solicitud->est_sol, ['Finalizado', 'Cancelado']), 403);

    $request->validate([
        'obs_seg'                => 'nullable|string|max:1000',
        'repuestos'              => 'nullable|array',
        'repuestos.*.cod_rep'    => 'required|string|exists:repuestos,cod_repuestos',
        'repuestos.*.qty'        => 'required|integer|min:1',
        'repuestos.*.cod_sr'     => 'nullable|string', // cod_solicitudesrep si ya existe
        'eliminados'             => 'nullable|array',  // cod_solicitudesrep a eliminar
        'eliminados.*'           => 'nullable|string',
    ]);

    DB::beginTransaction();

    try {
        $seg->obs_seg = $request->input('obs_seg');
        $seg->save();
        // 1. Procesar eliminados → devolver stock
        foreach ($request->input('eliminados', []) as $codSR) {
            if (!$codSR) continue;

            $sr = DB::table('solicitudes_repuestos')
                ->where('cod_solicitudesrep', $codSR)
                ->where('cod_seg', $cod_seg)
                ->first();

            if (!$sr) continue;

            // Devolver stock
            DB::table('repuestos')
                ->where('cod_repuestos', $sr->cod_repuestos_sol)
                ->increment('stock', $sr->can_sol);

            DB::table('solicitudes_repuestos')
                ->where('cod_solicitudesrep', $codSR)
                ->delete();
        }

        // 2. Procesar repuestos enviados (nuevos o actualizados)
        foreach ($request->input('repuestos', []) as $item) {
            $codRep = $item['cod_rep'];
            $qty    = (int) $item['qty'];
            $codSR  = $item['cod_sr'] ?? null;

            $repuesto = Repuesto::lockForUpdate()->find($codRep);
            if (!$repuesto) continue;

            if ($codSR) {
                // ── Actualizar existente ──
                $srActual = DB::table('solicitudes_repuestos')
                    ->where('cod_solicitudesrep', $codSR)
                    ->first();

                if (!$srActual) continue;

                $diff = $qty - $srActual->can_sol; // positivo = pide más, negativo = devuelve

                if ($diff > 0 && $repuesto->stock < $diff) {
                    throw new \Exception(
                        "Stock insuficiente para {$repuesto->nom_rep}. ".
                        "Disponible: {$repuesto->stock}, necesario: {$diff}"
                    );
                }

                // Ajustar stock
                $repuesto->stock -= $diff;
                $repuesto->save();

                DB::table('solicitudes_repuestos')
                    ->where('cod_solicitudesrep', $codSR)
                    ->update(['can_sol' => $qty]);

            } else {
                // ── Nuevo repuesto en la nota ──
                if ($repuesto->stock < $qty) {
                    throw new \Exception(
                        "Stock insuficiente para {$repuesto->nom_rep}. ".
                        "Disponible: {$repuesto->stock}, solicitado: {$qty}"
                    );
                }

                $repuesto->stock -= $qty;
                $repuesto->save();

                DB::table('solicitudes_repuestos')->insert([
                    'can_sol'           => $qty,
                    'fec_sol_rep'       => now()->toDateString(),
                    'cod_repuestos_sol' => $codRep,
                    'cod_usuarios_sol'  => $tecnico->cod_usuarios,
                    'cod_seg'           => $cod_seg,
                ]);
            }
        }

        DB::commit();

        return response()->json(['ok' => true]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
    }
}
}