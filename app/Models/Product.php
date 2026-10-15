<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'cost',
        'image',
        'current_stock',
        'minimum_stock',
        'status',
        'location_details',
        'warehouse_id',
        'supplier_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'current_stock' => 'integer',
            'minimum_stock' => 'integer',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query->where('products.name', 'like', "%{$search}%")
                ->orWhere('products.sku', 'like', "%{$search}%")
                ->orWhereHas('warehouse', fn (Builder $warehouse): Builder => $warehouse->where('name', 'like', "%{$search}%"));
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function getStockLevelAttribute(): string
    {
        if ($this->current_stock <= $this->minimum_stock) {
            return 'critical';
        }

        if ($this->minimum_stock > 0 && $this->current_stock <= $this->minimum_stock * 1.5) {
            return 'running_low';
        }

        if ($this->minimum_stock > 0 && $this->current_stock >= $this->minimum_stock * 5) {
            return 'abundant';
        }

        if ($this->minimum_stock > 0 && $this->current_stock <= $this->minimum_stock * 3) {
            return 'optimal';
        }

        return $this->minimum_stock > 0 ? 'regular' : 'abundant';
    }
}
