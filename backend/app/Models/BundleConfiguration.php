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
        $totalPrice = $this->bundle->price ?? 0; // Base price
        $totalWeight = $this->bundle->base_weight_g ?? 0; // Base weight

        if ($this->configuration_data) {
            $availableOptions = $this->bundle->getAvailableOptions();

            // Add espresso module price/weight if selected
            if (isset($this->configuration_data['espresso_module']) && $this->configuration_data['espresso_module']) {
                $espressoModule = $availableOptions['espresso_module'] ?? null;
                if ($espressoModule) {
                    $totalPrice += $espressoModule['price'];
                    $totalWeight += $espressoModule['weight_g'];
                }
            }

            // Add filter attachment price/weight if selected
            if (isset($this->configuration_data['filter_attachment']) && $this->configuration_data['filter_attachment']) {
                $filterAttachment = $availableOptions['filter_attachment'] ?? null;
                if ($filterAttachment) {
                    $totalPrice += $filterAttachment['price'];
                    $totalWeight += $filterAttachment['weight_g'];
                }
            }

            // Add fan accessory price/weight if selected
            if (isset($this->configuration_data['fan_accessory']) && $this->configuration_data['fan_accessory']) {
                $fanAccessory = $availableOptions['fan_accessory'] ?? null;
                if ($fanAccessory) {
                    $totalPrice += $fanAccessory['price'];
                    $totalWeight += $fanAccessory['weight_g'];
                }
            }

            // Add solar panel price/weight based on size
            $solarPanelSize = $this->configuration_data['solar_panel_size'] ?? '10W';
            $solarPanelSizes = $availableOptions['solar_panel_sizes'] ?? [];
            $selectedPanel = $solarPanelSizes[$solarPanelSize] ?? null;

            if ($selectedPanel) {
                $totalPrice += $selectedPanel['price'];
                $totalWeight += $selectedPanel['weight_g'];
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
        $weight = $this->total_weight_g; // Keep in grams for accurate comparison
        $weightThresholds = $this->bundle->getWeightThresholds();

        // Find the appropriate threshold based on total weight
        $compatibleThreshold = null;
        foreach ($weightThresholds as $threshold) {
            if ($weight <= $threshold['max_weight_g']) {
                $compatibleThreshold = $threshold;
                break;
            }
        }

        // If no threshold found (weight exceeds all thresholds), use the last one
        if (!$compatibleThreshold && !empty($weightThresholds)) {
            $compatibleThreshold = end($weightThresholds);
        }

        if ($compatibleThreshold) {
            $this->weight_compatibility = [
                'threshold' => array_search($compatibleThreshold, $weightThresholds) ?: 'unknown',
                'description' => $compatibleThreshold['description'],
                'compatible' => $weight <= $compatibleThreshold['max_weight_g'],
                'max_weight_g' => $compatibleThreshold['max_weight_g']
            ];
        } else {
            // Fallback if no thresholds are defined
            $this->weight_compatibility = [
                'threshold' => 'unknown',
                'description' => 'Compatibility unknown',
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
