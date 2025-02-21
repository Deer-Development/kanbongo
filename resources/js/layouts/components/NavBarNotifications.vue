<script setup>
import avatar3 from '@images/avatars/avatar-3.png'
import avatar4 from '@images/avatars/avatar-4.png'
import avatar5 from '@images/avatars/avatar-5.png'

const userData = useCookie('userData')

const notifications = ref([
  {
    id: 1,
    img: avatar4,
    title: `⏳ Time waits for no one, ${userData.value.full_name}!`,
    subtitle: 'You just tracked 5 hours on "Cards Of War"! 🔥 Keep up the grind!',
    time: 'Today',
    isSeen: true,
  },
  {
    id: 2,
    img: avatar5,
    title: `📊 Kanban Update: "In Progress" is looking full!`,
    subtitle: `Alex just moved 10 tasks to "In Progress"—are we in crunch mode? 🏃‍♂️`,
    time: '5 hours ago',
    isSeen: false,
  },
  {
    id: 3,
    img: avatar3,
    title: `⏰ Break Reminder!`,
    subtitle: `Hey ${userData.value.full_name}, you've been tracking time non-stop! Maybe a coffee break? ☕`,
    time: '1 hour ago',
    isSeen: false,
  },
  {
    id: 4,
    img: avatar4,
    title: `🛠️ Tzvi just assigned you a new task!`,
    subtitle: `"Optimize time-tracking module"—looks like an urgent one! 🚀`,
    time: 'Yesterday',
    isSeen: true,
  },
  {
    id: 5,
    img: avatar5,
    title: `🔔 Notifications in progress...`,
    subtitle: `Just a heads up, ${userData.value.full_name}, the time-tracking machine is running at full speed! ⚡`,
    time: 'Just now',
    isSeen: false,
  },
])

const removeNotification = notificationId => {
  notifications.value.forEach((item, index) => {
    if (notificationId === item.id)
      notifications.value.splice(index, 1)
  })
}

const markRead = notificationId => {
  notifications.value.forEach(item => {
    notificationId.forEach(id => {
      if (id === item.id)
        item.isSeen = true
    })
  })
}

const markUnRead = notificationId => {
  notifications.value.forEach(item => {
    notificationId.forEach(id => {
      if (id === item.id)
        item.isSeen = false
    })
  })
}

const handleNotificationClick = notification => {
  if (!notification.isSeen)
    markRead([notification.id])
}
</script>

<template>
  <Notifications
    :notifications="notifications"
    @remove="removeNotification"
    @read="markRead"
    @unread="markUnRead"
    @click:notification="handleNotificationClick"
  />
</template>
