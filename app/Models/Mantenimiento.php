<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Mantenimiento extends Model
{
    protected $table      = 'mantenimientos';
    protected $primaryKey = 'cod_mantenimientos';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'des_man',
        'fec_ini_man',
        'fec_fin_man',
        'cod_solicitudes_man',
        'total_man',
        'deleted_at',
        'restored_at',
        'cod_facturas_man',
    ];

    protected $casts = [
        'fec_ini_man' => 'datetime',
        'fec_fin_man' => 'datetime',
        'deleted_at'  => 'datetime',
        'restored_at' => 'datetime',
    ];

    // =========================================================
    // OVERRIDE save() para usar RETURNING y capturar la PK
    // =========================================================
    public function save(array $options = [])
    {
        // Solo aplicar en INSERT (registro nuevo)
        if (!$this->exists) {
            $attributes = $this->getDirty();
            unset($attributes['cod_mantenimientos']);

            // Filtrar nulls para no insertar columnas vacías
            $attributes = array_filter($attributes, fn($v) => $v !== null);

            $columns      = implode(', ', array_map(fn($c) => "\"$c\"", array_keys($attributes)));
            $placeholders = implode(', ', array_fill(0, count($attributes), '?'));
            $values       = array_values($attributes);

            $sql    = "INSERT INTO mantenimientos ($columns) VALUES ($placeholders) RETURNING cod_mantenimientos";
            $result = DB::selectOne($sql, $values);

            $this->setAttribute($this->primaryKey, $result->cod_mantenimientos);
            $this->exists   = true;
            $this->syncOriginal();

            return true;
        }

        // UPDATE normal
        return parent::save($options);
    }

    // =========================================================
    // RELACIONES
    // =========================================================

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'cod_solicitudes_man', 'cod_solicitudes');
    }

    public function ordenServicios()
    {
        return $this->hasMany(OrdenServicio::class, 'cod_mantenimientos', 'cod_mantenimientos');
    }

    public function servicios()
    {
        return $this->belongsToMany(
            Servicio::class,
            'orden_servicios',
            'cod_mantenimientos',
            'cod_servicios'
        )->withPivot('cantidad');
    }

    public function repuestos()
    {
        return $this->belongsToMany(
            Repuesto::class,
            'mantenimiento_repuestos',
            'cod_mantenimientos',
            'cod_repuestos'
        )->withPivot('cantidad', 'pre_uni');
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'cod_facturas_man', 'cod_facturas');
    }
}