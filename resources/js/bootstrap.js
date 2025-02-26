import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

console.log('VITE_PUSHER_APP_KEY:', import.meta.env.VITE_PUSHER_APP_KEY)
console.log('VITE_PUSHER_APP_CLUSTER:', import.meta.env.VITE_PUSHER_APP_CLUSTER)
console.log('useCookie:', useCookie('accessToken', { default: null }).value)
if (import.meta.env.VITE_PUSHER_APP_KEY) {
    try {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            encrypted: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${useCookie('accessToken', { default: null }).value}`
                }
            }
        })

        console.log('Echo initialized successfully')

        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('Connected to Pusher')
            console.log('Pusher connection state:', window.Echo.connector.pusher.connection.state)
        })

        window.Echo.connector.pusher.connection.bind('error', (error) => {
            console.error('Pusher connection error:', error)
        })

        window.Echo.connector.pusher.connection.bind('state_change', (states) => {
            console.log('Pusher state changed from', states.previous, 'to', states.current)
        })

        window.Echo.connector.pusher.connection.bind('subscription_succeeded', (channel) => {
            console.log('Successfully subscribed to channel:', channel)
        })

        window.Echo.connector.pusher.connection.bind('subscription_error', (error) => {
            console.error('Subscription error:', error)
        })

        window.Echo.connector.pusher.connection.bind('message', (message) => {
            console.log('Pusher message received:', message)
        })

        window.Echo.connector.pusher.connection.bind('subscription_succeeded', (data) => {
            console.log('Subscription succeeded:', data)
        })

        window.Echo.connector.pusher.connection.bind('subscription_error', (error) => {
            console.error('Subscription error:', error)
        })

    } catch (error) {
        console.error('Error initializing Echo:', error)
    }
} else {
    console.warn('Pusher configuration missing')
} 