<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';
    protected $primaryKey = 'cod_facturas';
    public $timestamps = false;
 
    protected $fillable = [
        'fec_fac',          // Fecha de emisión
        'nfa_fac',          // Número de factura (único)
        'pto_fac',          // Punto de emisión
        'cod_clientes_fac', // FK al cliente
        'cod_entregas_fac', // FK a la entrega (único, 1:1)
    ];
 
    protected $casts = [
        'fec_fac' => 'datetime',
    ];
 
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cod_clientes_fac', 'cod_clientes');
    }
 
    public function entrega()
    {
        return $this->belongsTo(Entrega::class, 'cod_entregas_fac', 'cod_entregas');
    }
 
    public function detalles()
    {
        return $this->hasMany(DetalleFactura::class, 'cod_facturas_det', 'cod_facturas');
    }
 
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'cod_facturas_pag', 'cod_facturas');
    }

    public function mantenimiento()
    {
        return $this->hasOne(Mantenimiento::class, 'cod_facturas_man', 'cod_facturas');
    }
 
    /**
     * Total calculado sumando los detalles (cantidad × precio unitario).
     */
    public function getTotalAttribute(): float
    {
        return $this->detalles->sum(fn($d) => $d->can_det * $d->pun_det);
    }
}