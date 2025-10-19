import React, { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../services/api'

interface BundleOption {
  name: string
  description: string
  price: number
  weight_g: number
  available: boolean
}

interface BundleData {
  id: number
  name: string
  slug: string
  description: string
  price: number
  base_weight_g: number
  available_options: {
    espresso_module: BundleOption
    filter_attachment: BundleOption
    fan_accessory: BundleOption
    solar_panel_sizes: {
      [key: string]: {
        name: string
        price: number
        weight_g: number
      }
    }
  }
  weight_thresholds: {
    day_pack: { max_weight_g: number; description: string }
    overnight_pack: { max_weight_g: number; description: string }
    base_camp: { max_weight_g: number; description: string }
  }
}

interface Configuration {
  espresso_module: boolean
  filter_attachment: boolean
  fan_accessory: boolean
  solar_panel_size: string
}

const BundleBuilder: React.FC = () => {
  const navigate = useNavigate()
  const [bundle, setBundle] = useState<BundleData | null>(null)
  const [configuration, setConfiguration] = useState<Configuration>({
    espresso_module: false,
    filter_attachment: false,
    fan_accessory: false,
    solar_panel_size: '10W'
  })
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [savedConfiguration, setSavedConfiguration] = useState<any>(null)
  const [error, setError] = useState<string>('')

  useEffect(() => {
    loadBundle()
  }, [])

  const loadBundle = async () => {
    try {
      const response = await api.get('/bundles/weekender-solar-kit')
      if (response.data.success) {
        setBundle(response.data.data)
      } else {
        setError('Failed to load bundle information')
      }
    } catch (err: any) {
      setError('Failed to load bundle information')
    } finally {
      setLoading(false)
    }
  }

  const updateConfiguration = (key: keyof Configuration, value: any) => {
    setConfiguration(prev => ({
      ...prev,
      [key]: value
    }))
  }

  const calculateTotals = () => {
    if (!bundle) return { price: 0, weight: 0, weight_kg: 0 }

    let totalPrice = bundle.price
    let totalWeight = bundle.base_weight_g

    if (configuration.espresso_module && bundle.available_options.espresso_module.available) {
      totalPrice += bundle.available_options.espresso_module.price
      totalWeight += bundle.available_options.espresso_module.weight_g
    }

    if (configuration.filter_attachment && bundle.available_options.filter_attachment.available) {
      totalPrice += bundle.available_options.filter_attachment.price
      totalWeight += bundle.available_options.filter_attachment.weight_g
    }

    if (configuration.fan_accessory && bundle.available_options.fan_accessory.available) {
      totalPrice += bundle.available_options.fan_accessory.price
      totalWeight += bundle.available_options.fan_accessory.weight_g
    }

    const selectedPanel = bundle.available_options.solar_panel_sizes[configuration.solar_panel_size]
    if (selectedPanel) {
      totalPrice += selectedPanel.price
      totalWeight += selectedPanel.weight_g
    }

    return {
      price: totalPrice,
      weight: totalWeight,
      weight_kg: totalWeight / 1000
    }
  }

  const getWeightCompatibility = () => {
    const { weight } = calculateTotals()

    if (!bundle?.weight_thresholds) {
      return {
        threshold: 'unknown',
        description: 'Compatibility unknown',
        compatible: true
      }
    }

    if (weight < bundle.weight_thresholds.day_pack.max_weight_g) {
      return {
        threshold: 'day_pack',
        description: bundle.weight_thresholds.day_pack.description,
        compatible: true
      }
    } else if (weight < bundle.weight_thresholds.overnight_pack.max_weight_g) {
      return {
        threshold: 'overnight_pack',
        description: bundle.weight_thresholds.overnight_pack.description,
        compatible: true
      }
    } else {
      return {
        threshold: 'base_camp',
        description: bundle.weight_thresholds.base_camp.description,
        compatible: false
      }
    }
  }

  const saveConfiguration = async () => {
    if (!bundle) return

    setSaving(true)
    setError('')

    try {
      const response = await api.post(`/bundles/${bundle.slug}/configure`, {
        configuration,
        name: `Weekender Kit - ${new Date().toLocaleDateString()}`
      })

      if (response.data.success) {
        setSavedConfiguration(response.data.data)
      } else {
        setError(response.data.message || 'Failed to save configuration')
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to save configuration')
    } finally {
      setSaving(false)
    }
  }

  const addToCart = async () => {
    if (!savedConfiguration) {
      await saveConfiguration()
      return
    }

    try {
      const response = await api.post(`/bundles/configurations/${savedConfiguration.id}/add-to-cart`, {
        quantity: 1
      })

      if (response.data.success) {
        navigate('/checkout')
      } else {
        setError(response.data.message || 'Failed to add to cart')
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to add to cart')
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div className="text-center">
          <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
            <svg className="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
          </div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Loading Bundle Builder...
          </h2>
        </div>
      </div>
    )
  }

  if (!bundle) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div className="text-center">
          <h2 className="text-3xl font-extrabold text-gray-900">Bundle Not Found</h2>
          <p className="mt-2 text-gray-600">The requested bundle configuration is not available.</p>
          <button
            onClick={() => navigate('/products')}
            className="mt-6 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
          >
            Browse Products
          </button>
        </div>
      </div>
    )
  }

  const totals = calculateTotals()
  const weightCompatibility = getWeightCompatibility()

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto">
        <div className="bg-white shadow-lg rounded-lg overflow-hidden">
          {/* Header */}
          <div className="px-6 py-4 border-b border-gray-200">
            <h1 className="text-3xl font-bold text-gray-900">{bundle.name}</h1>
            <p className="mt-2 text-gray-600">{bundle.description}</p>
          </div>

          <div className="p-6">
            {/* Configuration Options */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
              {/* Left Column - Options */}
              <div className="space-y-6">
                <h2 className="text-xl font-semibold text-gray-900">Configure Your Kit</h2>

                {/* Solar Panel Size */}
                <div className="space-y-3">
                  <label className="block text-sm font-medium text-gray-700">
                    Solar Panel Size *
                  </label>
                  <div className="space-y-2">
                    {Object.entries(bundle.available_options.solar_panel_sizes).map(([size, panel]) => (
                      <label key={size} className="flex items-center justify-between p-3 border border-gray-200 rounded-md hover:bg-gray-50">
                        <div className="flex items-center">
                          <input
                            type="radio"
                            name="solar_panel_size"
                            value={size}
                            checked={configuration.solar_panel_size === size}
                            onChange={(e) => updateConfiguration('solar_panel_size', e.target.value)}
                            className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                          />
                          <div className="ml-3">
                            <span className="text-sm font-medium text-gray-900">{panel.name}</span>
                            <p className="text-xs text-gray-500">{panel.weight_g}g • +${panel.price}</p>
                          </div>
                        </div>
                      </label>
                    ))}
                  </div>
                </div>

                {/* Optional Add-ons */}
                <div className="space-y-3">
                  <label className="block text-sm font-medium text-gray-700">
                    Optional Add-ons
                  </label>

                  {/* Espresso Module */}
                  <label className="flex items-center justify-between p-3 border border-gray-200 rounded-md hover:bg-gray-50">
                    <div className="flex items-center">
                      <input
                        type="checkbox"
                        checked={configuration.espresso_module}
                        onChange={(e) => updateConfiguration('espresso_module', e.target.checked)}
                        className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded-md"
                      />
                      <div className="ml-3">
                        <span className="text-sm font-medium text-gray-900">
                          {bundle.available_options.espresso_module.name}
                        </span>
                        <p className="text-xs text-gray-500">
                          {bundle.available_options.espresso_module.description}
                        </p>
                      </div>
                    </div>
                    <span className="text-sm font-medium text-gray-900">
                      +${bundle.available_options.espresso_module.price}
                    </span>
                  </label>

                  {/* Filter Attachment */}
                  <label className="flex items-center justify-between p-3 border border-gray-200 rounded-md hover:bg-gray-50">
                    <div className="flex items-center">
                      <input
                        type="checkbox"
                        checked={configuration.filter_attachment}
                        onChange={(e) => updateConfiguration('filter_attachment', e.target.checked)}
                        className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded-md"
                      />
                      <div className="ml-3">
                        <span className="text-sm font-medium text-gray-900">
                          {bundle.available_options.filter_attachment.name}
                        </span>
                        <p className="text-xs text-gray-500">
                          {bundle.available_options.filter_attachment.description}
                        </p>
                      </div>
                    </div>
                    <span className="text-sm font-medium text-gray-900">
                      +${bundle.available_options.filter_attachment.price}
                    </span>
                  </label>

                  {/* Fan Accessory */}
                  <label className="flex items-center justify-between p-3 border border-gray-200 rounded-md hover:bg-gray-50">
                    <div className="flex items-center">
                      <input
                        type="checkbox"
                        checked={configuration.fan_accessory}
                        onChange={(e) => updateConfiguration('fan_accessory', e.target.checked)}
                        className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded-md"
                      />
                      <div className="ml-3">
                        <span className="text-sm font-medium text-gray-900">
                          {bundle.available_options.fan_accessory.name}
                        </span>
                        <p className="text-xs text-gray-500">
                          {bundle.available_options.fan_accessory.description}
                        </p>
                      </div>
                    </div>
                    <span className="text-sm font-medium text-gray-900">
                      +${bundle.available_options.fan_accessory.price}
                    </span>
                  </label>
                </div>
              </div>

              {/* Right Column - Summary */}
              <div className="space-y-6">
                <h2 className="text-xl font-semibold text-gray-900">Configuration Summary</h2>

                {/* Weight Compatibility */}
                <div className="bg-gray-50 rounded-lg p-4">
                  <h3 className="text-sm font-medium text-gray-900 mb-2">Pack Compatibility</h3>
                  <div className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
                    weightCompatibility.compatible
                      ? 'bg-green-100 text-green-800'
                      : 'bg-yellow-100 text-yellow-800'
                  }`}>
                    {weightCompatibility.description}
                  </div>
                  <p className="text-xs text-gray-500 mt-1">
                    Total Weight: {totals.weight_kg.toFixed(2)}kg
                  </p>
                </div>

                {/* Price Breakdown */}
                <div className="bg-gray-50 rounded-lg p-4">
                  <h3 className="text-sm font-medium text-gray-900 mb-3">Price Breakdown</h3>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span>Base Kit:</span>
                      <span>${bundle.price.toFixed(2)}</span>
                    </div>

                    {configuration.espresso_module && (
                      <div className="flex justify-between">
                        <span>Espresso Module:</span>
                        <span>+${bundle.available_options.espresso_module.price.toFixed(2)}</span>
                      </div>
                    )}

                    {configuration.filter_attachment && (
                      <div className="flex justify-between">
                        <span>Filter Attachment:</span>
                        <span>+${bundle.available_options.filter_attachment.price.toFixed(2)}</span>
                      </div>
                    )}

                    {configuration.fan_accessory && (
                      <div className="flex justify-between">
                        <span>Fan Accessory:</span>
                        <span>+${bundle.available_options.fan_accessory.price.toFixed(2)}</span>
                      </div>
                    )}

                    <div className="flex justify-between">
                      <span>Solar Panel ({configuration.solar_panel_size}):</span>
                      <span>+${bundle.available_options.solar_panel_sizes[configuration.solar_panel_size].price.toFixed(2)}</span>
                    </div>

                    <div className="border-t border-gray-200 pt-2 mt-3">
                      <div className="flex justify-between text-lg font-bold">
                        <span>Total:</span>
                        <span>${totals.price.toFixed(2)}</span>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Error Message */}
                {error && (
                  <div className="bg-red-50 border border-red-200 rounded-md p-3">
                    <div className="text-sm text-red-700">{error}</div>
                  </div>
                )}

                {/* Action Buttons */}
                <div className="space-y-3">
                  <button
                    onClick={saveConfiguration}
                    disabled={saving}
                    className="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    {saving ? 'Saving...' : 'Save Configuration'}
                  </button>

                  {savedConfiguration && (
                    <button
                      onClick={addToCart}
                      className="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                      Add to Cart - ${totals.price.toFixed(2)}
                    </button>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default BundleBuilder