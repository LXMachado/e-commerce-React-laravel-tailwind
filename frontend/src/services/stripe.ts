import { loadStripe } from '@stripe/stripe-js'

// Initialize Stripe with publishable key from environment
const stripePublishableKey = import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY || 'pk_test_51234567890abcdefghijklmnopqrstuvwxyz1234567890abcdefghijklmnopqrs'

export const stripePromise = loadStripe(stripePublishableKey)

export default stripePromise