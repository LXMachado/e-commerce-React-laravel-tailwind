<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'compare_at_price',
        'is_active',
        'kit_type',
        'base_weight_g',
        'available_options',
        'default_configuration',
        'sku_prefix',
        'weight_threshold_compatibility',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_active' => 'boolean',
        'base_weight_g' => 'integer',
        'available_options' => 'array',
        'default_configuration' => 'array',
        'weight_threshold_compatibility' => 'array',
    ];

    /**
     * Get the bundle items for this bundle
     */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class);
    }

    /**
     * Get the product variants in this bundle
     */
    public function productVariants()
    {
        return $this->belongsToMany(ProductVariant::class, 'bundle_items')
                    ->withPivot('quantity', 'sort_order');
    }

    /**
     * Get the configurations for this bundle
     */
    public function configurations()
    {
        return $this->hasMany(BundleConfiguration::class);
    }

    /**
     * Get the current price (sale price if available, otherwise regular price)
     */
    public function getCurrentPrice(): float
    {
        return $this->compare_at_price ?? $this->price;
    }

    /**
     * Check if bundle is on sale
     */
    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    /**
     * Calculate the total value of all items in the bundle
     */
    public function getTotalValue(): float
    {
        return $this->bundleItems->sum(function ($item) {
            return $item->productVariant->getCurrentPrice() * $item->quantity;
        });
    }

    /**
     * Get the savings amount if on sale
     */
    public function getSavings(): float
    {
        if (!$this->isOnSale()) {
            return 0;
        }

        return $this->compare_at_price - $this->price;
    }

    /**
     * Get the savings percentage if on sale
     */
    public function getSavingsPercentage(): float
    {
        if (!$this->isOnSale()) {
            return 0;
        }

        return round(($this->getSavings() / $this->compare_at_price) * 100, 1);
    }

    /**
     * Check if this is a kit bundle
     */
    public function isKit(): bool
    {
        return !is_null($this->kit_type);
    }

    /**
     * Check if this is a weekender kit
     */
    public function isWeekenderKit(): bool
    {
        return $this->kit_type === 'weekender';
    }

    /**
     * Get available configuration options
     */
    public function getAvailableOptions(): array
    {
        return $this->available_options ?? [
            'espresso_module' => [
                'name' => 'Espresso Module',
                'description' => 'Hot water system for coffee and tea',
                'price' => 150.00,
                'weight_g' => 800,
                'available' => true
            ],
            'filter_attachment' => [
                'name' => 'Filter Attachment',
                'description' => 'Water purification system',
                'price' => 75.00,
                'weight_g' => 300,
                'available' => true
            ],
            'fan_accessory' => [
                'name' => 'Fan Accessory',
                'description' => 'Air circulation system',
                'price' => 45.00,
                'weight_g' => 200,
                'available' => true
            ],
            'solar_panel_sizes' => [
                '10W' => ['name' => '10W Solar Panel', 'price' => 25.00, 'weight_g' => 250],
                '15W' => ['name' => '15W Solar Panel', 'price' => 50.00, 'weight_g' => 400],
                '20W' => ['name' => '20W Solar Panel', 'price' => 100.00, 'weight_g' => 600]
            ]
        ];
    }

    /**
     * Get default configuration
     */
    public function getDefaultConfiguration(): array
    {
        return $this->default_configuration ?? [
            'espresso_module' => false,
            'filter_attachment' => false,
            'fan_accessory' => false,
            'solar_panel_size' => '10W'
        ];
    }

    /**
     * Get weight threshold compatibility info
     */
    public function getWeightThresholds(): array
    {
        return $this->weight_threshold_compatibility ?? [
            'day_pack' => ['max_weight_g' => 5000, 'description' => 'Day-pack compatible'],
            'overnight_pack' => ['max_weight_g' => 10000, 'description' => 'Overnight pack compatible'],
            'base_camp' => ['max_weight_g' => 999999, 'description' => 'Base camp setup']
        ];
    }

    /**
     * Calculate minimum possible weight for this kit
     */
    public function getMinimumWeight(): int
    {
        return ($this->base_weight_g ?? 0) + 250; // Base weight + smallest solar panel
    }

    /**
     * Calculate maximum possible weight for this kit
     */
    public function getMaximumWeight(): int
    {
        $baseWeight = $this->base_weight_g ?? 0;
        $options = $this->getAvailableOptions();

        $maxOptionWeight = 0;
        if (isset($options['espresso_module'])) {
            $maxOptionWeight += $options['espresso_module']['weight_g'];
        }
        if (isset($options['filter_attachment'])) {
            $maxOptionWeight += $options['filter_attachment']['weight_g'];
        }
        if (isset($options['fan_accessory'])) {
            $maxOptionWeight += $options['fan_accessory']['weight_g'];
        }
        if (isset($options['solar_panel_sizes']['20W'])) {
            $maxOptionWeight += $options['solar_panel_sizes']['20W']['weight_g'];
        }

        return $baseWeight + $maxOptionWeight;
    }

    /**
     * Create a new configuration with given options
     */
    public function createConfiguration(array $options, ?int $userId = null, ?string $name = null): BundleConfiguration
    {
        $configurationData = array_merge($this->getDefaultConfiguration(), $options);

        return $this->configurations()->create([
            'user_id' => $userId,
            'name' => $name,
            'configuration_data' => $configurationData,
        ]);
    }

    /**
     * Get formatted base weight
     */
    public function getFormattedBaseWeight(): string
    {
        if (!$this->base_weight_g) {
            return 'N/A';
        }

        return round($this->base_weight_g / 1000, 2) . 'kg';
    }
}