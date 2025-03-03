<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/useNotificationStore'
import { storeToRefs } from 'pinia'

const router = useRouter()
const notificationStore = useNotificationStore()

const { notifications, loading } = storeToRefs(notificationStore)

const handleToggleReadStatus = async (notification) => {
  try {
    const endpoint = notification.isSeen 
      ? `/notifications/${notification.id}/mark-as-unread`
      : `/notifications/${notification.id}/mark-as-read`
    
    await $api(endpoint, {
      method: 'PATCH'
    })
    
    notification.isSeen = !notification.isSeen
  } catch (error) {
    console.error('Error toggling notification status:', error)
  }
}

const handleRemove = async (notificationId) => {
  try {
    await $api(`/notifications/${notificationId}`, {
      method: 'DELETE'
    })
    notificationStore.notifications = notificationStore.notifications.filter(n => n.id !== notificationId)
  } catch (error) {
    console.error('Error removing notification:', error)
  }
}

const handleMarkAllAsRead = async () => {
  try {
    await $api('/notifications/mark-all-as-read', {
      method: 'PATCH'
    })
    notificationStore.notifications.forEach(notification => {
      notification.isSeen = true
    })
  } catch (error) {
    console.error('Error marking all as read:', error)
  }
}

const handleActionClick = (notification) => {
  if (!notification?.data?.project_id || !notification?.data?.container_id) {
    return
  }

  if(!notification.isSeen) {
    notificationStore.toggleReadStatus(notification)
  }

  router.push({
    path: `/projects/${notification.data.project_id}/container/${notification.data.container_id}`,
    query: { 
      openMessenger: 'true',
      taskId: notification.data?.task_id,
      taskName: notification.data?.task_name
    }
  })
}

onMounted(() => {
  notificationStore.fetchNotifications()
})
</script>

<template>
  <VCard class="notifications-page">
    <!-- Header -->
    <VCardItem class="header px-6 py-4">
      <template #prepend>
        <VIcon
          icon="tabler-bell"
          size="24"
          color="primary"
          class="me-3"
        />
      </template>
      
      <VCardTitle class="text-h5">
        Notifications
      </VCardTitle>

      <template #append>
        <div class="d-flex align-center gap-3">
          <VBtn
            v-if="notifications.length"
            variant="tonal"
            color="primary"
            size="small"
            prepend-icon="tabler-mail"
            @click="handleMarkAllAsRead"
          >
            Mark all as read
          </VBtn>
        </div>
      </template>
    </VCardItem>

    <VDivider />

    <!-- Content -->
    <VCardText class="pa-0">
      <div v-if="loading" class="d-flex justify-center align-center pa-4">
        <VProgressCircular indeterminate />
      </div>

      <div v-else-if="!notifications.length" class="empty-state text-center py-8">
        <VIcon
          icon="tabler-bell-off"
          size="48"
          class="mb-4"
          color="medium-emphasis"
        />
        <div class="text-h6 text-medium-emphasis font-weight-medium mb-2">
          No notifications
        </div>
        <div class="text-body-2 text-disabled">
          We'll notify you when something arrives
        </div>
      </div>

      <template v-else>
        <VList class="notification-list py-0">
          <template
            v-for="notification in notifications"
            :key="notification.id"
          >
            <VListItem
              class="notification-item px-6"
              :class="{ 'unseen': !notification.isSeen }"
              @click="handleActionClick(notification)"
            >
              <template #prepend>
                <div class="notification-icon-wrapper">
                  <VAvatar
                    :color="notification.data?.context?.color || 'primary'"
                    :class="[`bg-${notification.data?.context?.color || 'primary'}-subtle`]"
                    variant="tonal"
                    size="44"
                  >
                    <VIcon
                      :size="24"
                      :icon="notification.data?.action?.icon || 'tabler-bell'"
                    />
                  </VAvatar>
                </div>
              </template>

              <div class="notification-content">
                <div class="d-flex align-center mb-1">
                  <span class="notification-emoji me-2">
                    {{ notification.data?.context?.emoji || '📌' }}
                  </span>
                  <span class="notification-title">{{ notification.title }}</span>
                </div>

                <div class="notification-subtitle mb-2">
                  {{ notification.content }}
                </div>

                <div class="notification-meta d-flex align-center gap-4">
                  <div class="d-flex align-center">
                    <VChip
                      size="small"
                      variant="flat"
                      class="task-id-chip me-2"
                    >
                      #{{ notification.data?.task_sequence_id }}
                    </VChip>
                    <span class="board-name">{{ notification.data?.container_name }}</span>
                  </div>

                  <div class="time-wrapper d-flex align-center">
                    <VIcon
                      size="14"
                      icon="tabler-clock"
                      color="medium-emphasis"
                      class="me-1"
                    />
                    <span class="text-medium-emphasis">{{ notification.time }}</span>
                  </div>
                </div>

                <div 
                  v-if="notification.data?.comment_preview"
                  class="notification-preview mt-2 pa-3"
                >
                  {{ notification.data.comment_preview }}
                </div>
              </div>

              <template #append>
                <div class="d-flex align-center gap-2">
                  <VBtn
                    variant="text"
                    :color="notification.isSeen ? 'medium-emphasis' : 'primary'"
                    size="small"
                    @click.stop="handleToggleReadStatus(notification)"
                  >
                    <VIcon 
                      size="20" 
                      :icon="notification.isSeen ? 'tabler-mail-opened' : 'tabler-mail'" 
                      class="me-1"
                    />
                    {{ notification.isSeen ? 'Mark as unread' : 'Mark as read' }}
                  </VBtn>
                  
                  <VBtn
                    icon
                    variant="text"
                    color="error"
                    size="small"
                    @click.stop="handleRemove(notification.id)"
                  >
                    <VIcon size="20" icon="tabler-trash" />
                    <VTooltip activator="parent">
                      Delete notification
                    </VTooltip>
                  </VBtn>
                </div>
              </template>
            </VListItem>
            <VDivider />
          </template>
        </VList>
      </template>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.notifications-page {
  .header {
    background-color: #f8f9fa;
  }

  .notification-item {
    padding-top: 20px;
    padding-bottom: 20px;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
      background-color: rgba(var(--v-theme-primary), 0.04);
    }

    &.unseen {
      background-color: #f1f8ff;
      border-left: 3px solid rgb(var(--v-theme-primary));

      .notification-title {
        color: rgb(var(--v-theme-primary));
        font-weight: 600;
      }
    }

    .notification-icon-wrapper {
      margin-right: 20px;
    }

    .notification-content {
      flex: 1;
      min-width: 0;

      .notification-emoji {
        font-size: 16px;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f6f8fa;
        border-radius: 6px;
        border: 1px solid rgba(27, 31, 35, 0.15);
      }

      .notification-title {
        font-size: 0.9375rem;
        font-weight: 500;
        color: #24292f;
      }

      .notification-subtitle {
        font-size: 0.875rem;
        color: #57606a;
        line-height: 1.4;
      }

      .notification-meta {
        font-size: 0.8125rem;

        .task-id-chip {
          height: 20px;
          font-size: 0.75rem;
          font-weight: 600;
          background-color: #f6f8fa !important;
          border: 1px solid #d0d7de;
          color: #24292f !important;
        }

        .board-name {
          color: #57606a;
          font-weight: 500;
        }

        .time-wrapper {
          color: #6e7781;
        }
      }

      .notification-preview {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-family: 'Monaco', 'Menlo', monospace;
        line-height: 1.5;
        max-height: 100px;
        overflow-y: auto;
      }
    }
  }
}

.empty-state {
  min-height: 300px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

// Utility classes
.bg-primary-subtle {
  background-color: #f1f8ff !important;
  border: 1px solid #d1e5f9 !important;
}

.bg-info-subtle {
  background-color: #f6f8fa !important;
  border: 1px solid #d0d7de !important;
}
</style>