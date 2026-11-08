<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = ['nombre', 'precio', 'activo'];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function oportunidades(): BelongsToMany
    {
        return $this->belongsToMany(Oportunidad::class, 'oportunidad_producto')
            ->withPivot(['cantidad', 'precio_unitario'])
            ->withTimestamps();
    }
}
