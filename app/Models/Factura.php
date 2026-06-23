<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Factura extends Model
{
    protected $table = 'facturas';
    protected $primaryKey = 'cod_facturas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'fec_fac',
        'nfa_fac',
        'pto_fac',
        'cod_clientes_fac',
        'cod_entregas_fac', // queda nullable, sin problema dejarlo
    ];

    protected $casts = [
        'fec_fac' => 'datetime',
    ];

    // =========================================================
    // OVERRIDE save() para usar RETURNING y capturar la PK
    // generada por trigger (igual que Mantenimiento)
    // =========================================================
    public function save(array $options = [])
    {
        if (!$this->exists) {
            $attributes = $this->getDirty();
            unset($attributes['cod_facturas']);

            $attributes = array_filter($attributes, fn($v) => $v !== null);

            $columns      = implode(', ', array_map(fn($c) => "\"$c\"", array_keys($attributes)));
            $placeholders = implode(', ', array_fill(0, count($attributes), '?'));
            $values       = array_values($attributes);

            $sql    = "INSERT INTO facturas ($columns) VALUES ($placeholders) RETURNING cod_facturas";
            $result = DB::selectOne($sql, $values);

            $this->setAttribute($this->primaryKey, $result->cod_facturas);
            $this->exists = true;
            $this->syncOriginal();

            return true;
        }

        return parent::save($options);
    }

    public function getMontoPagadoAttribute(): float
    {
        return (float) $this->pagos->sum('mon_pag');
    }

    public function getEstadoPagoAttribute(): string
    {
        $total  = $this->total;
        $pagado = $this->monto_pagado;

        if ($pagado <= 0)     return 'Pendiente';
        if ($pagado < $total) return 'Parcial';
        return 'Pagado';
    }
    
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cod_clientes_fac', 'cod_clientes');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleFactura::class, 'cod_facturas_det', 'cod_facturas');
    }

    public function mantenimiento()
    {
        return $this->hasOne(Mantenimiento::class, 'cod_facturas_man', 'cod_facturas');
    }
    // ... resto de relaciones igual
}