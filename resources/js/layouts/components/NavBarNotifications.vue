<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import Notifications from '@core/components/Notifications.vue'
import { $api } from '@/utils/api'

const router = useRouter()
const userData = useCookie('userData')
const notifications = ref([])
let channel = null

// Fetch initial notifications
const fetchNotifications = async () => {
  try {
    const res = await $api('/notifications', {
      method: 'GET',
    })
    
    if (res?.data) {
      notifications.value = res.data.map(mapNotification)
    }
  } catch (error) {
    console.error('Error fetching notifications:', error)
  }
}

const mapNotification = (notification) => ({
  id: notification.id,
  title: notification.title,
  subtitle: notification.content,
  time: notification.created_at,
  color: notification.type === 'mention' ? 'info' : 'primary',
  isSeen: notification.is_seen,
  icon: notification.type === 'mention' ? 'tabler-at' : 'tabler-message',
  data: notification.data,
  type: notification.type
})

const subscribeToNotifications = () => {
  if (!window.Echo || !userData.value?.id) {
    console.warn('Echo or userData not available')
    return
  }

  try {
    // Unsubscribe from any existing subscription
    if (channel) {
      channel.unsubscribe()
    }

    // Create new subscription
    channel = window.Echo.private(`notifications.${userData.value.id}`)

    channel
      .listen('.NewNotification', (data) => {
        console.log('Raw event data:', data);
        console.log('Channel:', channel.name);
        console.log('Event name:', '.NewNotification');
        
        if (!data.notification) {
          console.error('Invalid notification data received:', data);
          return;
        }
        
        const newNotification = mapNotification(data.notification);
        notifications.value = [newNotification, ...notifications.value];
        
        console.log('Notifications updated:', notifications.value);
      })
      .error((error) => {
        console.error('Echo channel error:', error);
        setTimeout(subscribeToNotifications, 5000);
      });

    channel.subscribed(() => {
      console.log('Successfully subscribed to notifications channel');
    });

    console.log('Echo listener setup complete');
  } catch (error) {
    console.error('Error setting up Echo listener:', error);
    setTimeout(subscribeToNotifications, 5000);
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
  fetchNotifications()
  
  if (window.Echo) {
    // Subscribe initially
    subscribeToNotifications()

    // Listen for connection state changes
    window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
      handleConnectionStateChange(current)
    })
  }
})

onBeforeUnmount(() => {
  // Cleanup subscriptions
  if (channel) {
    channel.unsubscribe()
    channel = null
  }

  if (window.Echo) {
    window.Echo.connector.pusher.connection.unbind('state_change')
  }
})

const handleRemove = async (notificationId) => {
  try {
    await $api(`/notifications/${notificationId}`, {
      method: 'DELETE',
    })
    notifications.value = notifications.value.filter(n => n.id !== notificationId)
  } catch (error) {
    console.error('Error removing notification:', error)
  }
}

const handleMarkAsRead = async (notification) => {
  try {
    await $api(`/notifications/${notification.id}/mark-as-read`, {
      method: 'PATCH',
    })
    const notif = notifications.value.find(n => n.id === notification.id)
    if (notif) {
      notif.isSeen = true
    }
  } catch (error) {
    console.error('Error marking notification as read:', error)
  }
}

const handleMarkAllAsRead = async () => {
  try {
    await $api('/notifications/mark-all-as-read', {
      method: 'PATCH',
    })
    notifications.value.forEach(notification => {
      notification.isSeen = true
    })
  } catch (error) {
    console.error('Error marking all notifications as read:', error)
  }
}

const handleNotificationClick = async (notification) => {
  if (!notification.isSeen) {
    await handleMarkAsRead(notification)
  }
  
  if (notification.data?.task_id) {
    router.push(`/tasks/${notification.data.task_id}`)
  }
}

// Adăugăm o metodă pentru a forța actualizarea notificărilor
const updateNotifications = (newNotification) => {
  notifications.value = [newNotification, ...notifications.value];
}
</script>

<template>
  <Notifications
    :notifications="notifications"
    @remove="handleRemove"
    @mark-as-read="handleMarkAsRead"
    @mark-all-as-read="handleMarkAllAsRead"
    @click:notification="handleNotificationClick"
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
