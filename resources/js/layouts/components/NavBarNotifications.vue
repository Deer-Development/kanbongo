<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import Notifications from '@core/components/Notifications.vue'
import { useNotificationStore } from '@/stores/useNotificationStore'

const userData = useCookie('userData')
let channel = null

const notificationStore = useNotificationStore()
const isMenuOpen = ref(false)

const subscribeToNotifications = () => {
  if (!window.Echo || !userData.value?.id) {
    console.warn('Echo or userData not available')
    return
  }

  try {
    if (channel) {
      channel.unsubscribe()
    }

    channel = window.Echo.private(`notifications.${userData.value.id}`)

    channel
      .listen('.NewNotification', (data) => {
        console.log('Raw event data:', data)
        console.log('Channel:', channel.name)
        console.log('Event name:', '.NewNotification')
        
        if (!data.notification) {
          console.error('Invalid notification data received:', data)
          return
        }
        
        notificationStore.addNotification(data.notification)
        
        console.log('Notifications updated:', notificationStore.notifications)
      })
      .error((error) => {
        console.error('Echo channel error:', error)
        setTimeout(subscribeToNotifications, 5000)
      })

    channel.subscribed(() => {
      console.log('Successfully subscribed to notifications channel')
    })

    console.log('Echo listener setup complete')
  } catch (error) {
    console.error('Error setting up Echo listener:', error)
    setTimeout(subscribeToNotifications, 5000)
  }
}

// Handle connection state changes
const handleConnectionStateChange = (state) => {
  console.log('Pusher connection state changed:', state)
  if (state === 'connected') {
    subscribeToNotifications()
  }
}

onMounted(() => {
  notificationStore.fetchNotifications()
  
  if (window.Echo) {
    subscribeToNotifications()
    window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
      handleConnectionStateChange(current)
    })
  }
})

onBeforeUnmount(() => {
  if (channel) {
    channel.unsubscribe()
    channel = null
  }

  if (window.Echo) {
    window.Echo.connector.pusher.connection.unbind('state_change')
  }
})
</script>

<template>
  <Notifications
    :notifications="notificationStore.notifications"
    v-model="isMenuOpen"
    @remove="notificationStore.removeNotification"
    @toggle-read-status="notificationStore.toggleReadStatus"
    @mark-all-as-read="notificationStore.markAllAsRead"
  />
</template>

<style lang="scss" scoped>
.notification-scroll {
  max-height: 420px;
}

.notification-section {
  padding: 16px;
}
</style>
