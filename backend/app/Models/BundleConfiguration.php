<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BundleConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'bundle_id',
        'user_id',
        'name',
        'configuration_data',
        'total_price',
        'total_weight_g',
        'sku',
        'share_token',
        'is_active',
        'weight_compatibility',
    ];

    protected $casts = [
        'configuration_data' => 'array',
        'total_price' => 'decimal:2',
        'weight_compatibility' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($configuration) {
            $configuration->generateSku();
            $configuration->generateShareToken();
            $configuration->calculateTotals();
        });

        static::updating(function ($configuration) {
            $configuration->calculateTotals();
        });
    }

    /**
     * Get the bundle that owns this configuration
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    /**
     * Get the user that owns this configuration
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique SKU for this configuration
     */
    public function generateSku(): void
    {
        if ($this->sku) {
            return;
        }

        $baseSku = $this->bundle->sku_prefix ?? 'WEEKENDER';
        $uniqueId = strtoupper(Str::random(8));

        $this->sku = "{$baseSku}-{$uniqueId}";
    }

    /**
     * Generate a share token for this configuration
     */
    public function generateShareToken(): void
    {
        if ($this->share_token) {
            return;
        }

        $this->share_token = Str::random(32);
    }

    /**
     * Calculate total price and weight for this configuration
     */
    public function calculateTotals(): void
    {
        $totalPrice = $this->bundle->price; // Base price
        $totalWeight = $this->bundle->base_weight_g ?? 0; // Base weight

        if ($this->configuration_data) {
            // Add espresso module price/weight if selected
            if (isset($this->configuration_data['espresso_module']) && $this->configuration_data['espresso_module']) {
                $totalPrice += 150.00; // Example price - should come from actual product
                $totalWeight += 800; // Example weight in grams
            }

            // Add filter attachment price/weight if selected
            if (isset($this->configuration_data['filter_attachment']) && $this->configuration_data['filter_attachment']) {
                $totalPrice += 75.00;
                $totalWeight += 300;
            }

            // Add fan accessory price/weight if selected
            if (isset($this->configuration_data['fan_accessory']) && $this->configuration_data['fan_accessory']) {
                $totalPrice += 45.00;
                $totalWeight += 200;
            }

            // Add solar panel price/weight based on size
            $solarPanelSize = $this->configuration_data['solar_panel_size'] ?? '10W';
            switch ($solarPanelSize) {
                case '15W':
                    $totalPrice += 50.00;
                    $totalWeight += 400;
                    break;
                case '20W':
                    $totalPrice += 100.00;
                    $totalWeight += 600;
                    break;
                case '10W':
                default:
                    $totalPrice += 25.00;
                    $totalWeight += 250;
                    break;
            }
        }

        $this->total_price = $totalPrice;
        $this->total_weight_g = $totalWeight;
        $this->calculateWeightCompatibility();
    }

    /**
     * Calculate weight compatibility thresholds
     */
    public function calculateWeightCompatibility(): void
    {
        $weight = $this->total_weight_g / 1000; // Convert to kg

        if ($weight < 5) {
            $this->weight_compatibility = [
                'threshold' => '<5kg',
                'description' => 'Day-pack compatible',
                'compatible' => true
            ];
        } elseif ($weight <= 10) {
            $this->weight_compatibility = [
                'threshold' => '5-10kg',
                'description' => 'Overnight pack compatible',
                'compatible' => true
            ];
        } else {
            $this->weight_compatibility = [
                'threshold' => '>10kg',
                'description' => 'Base camp setup',
                'compatible' => false
            ];
        }
    }

    /**
     * Get weight compatibility description
     */
    public function getWeightCompatibilityDescription(): string
    {
        return $this->weight_compatibility['description'] ?? 'Unknown';
    }

    /**
     * Check if configuration is day-pack compatible
     */
    public function isDayPackCompatible(): bool
    {
        return ($this->total_weight_g / 1000) < 5;
    }

    /**
     * Check if configuration is overnight pack compatible
     */
    public function isOvernightPackCompatible(): bool
    {
        $weight = $this->total_weight_g / 1000;
        return $weight >= 5 && $weight <= 10;
    }

    /**
     * Check if configuration is base camp setup
     */
    public function isBaseCampSetup(): bool
    {
        return ($this->total_weight_g / 1000) > 10;
    }

    /**
     * Get configuration summary for display
     */
    public function getConfigurationSummary(): array
    {
        $summary = [
            'base_kit' => 'Weekender Solar Kit',
            'options' => [],
            'specifications' => [
                'total_weight' => round($this->total_weight_g / 1000, 2) . 'kg',
                'total_price' => '$' . number_format($this->total_price, 2),
                'compatibility' => $this->getWeightCompatibilityDescription()
            ]
        ];

        if ($this->configuration_data) {
            if (isset($this->configuration_data['espresso_module']) && $this->configuration_data['espresso_module']) {
                $summary['options'][] = 'Espresso Module (+$150.00)';
            }

            if (isset($this->configuration_data['filter_attachment']) && $this->configuration_data['filter_attachment']) {
                $summary['options'][] = 'Filter Attachment (+$75.00)';
            }

            if (isset($this->configuration_data['fan_accessory']) && $this->configuration_data['fan_accessory']) {
                $summary['options'][] = 'Fan Accessory (+$45.00)';
            }

            $solarPanelSize = $this->configuration_data['solar_panel_size'] ?? '10W';
            $summary['options'][] = "Solar Panel ({$solarPanelSize})";
        }

        return $summary;
    }

    /**
     * Find configuration by share token
     */
    public static function findByShareToken(string $token): ?self
    {
        return static::where('share_token', $token)->where('is_active', true)->first();
    }

    /**
     * Get the formatted total weight in kg
     */
    public function getFormattedWeight(): string
    {
        return round($this->total_weight_g / 1000, 2) . 'kg';
    }

    /**
     * Get the formatted total price
     */
    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->total_price, 2);
    }
}
