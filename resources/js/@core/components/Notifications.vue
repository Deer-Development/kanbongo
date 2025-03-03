<script setup>
import { computed } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useRouter } from 'vue-router'

const props = defineProps({
  notifications: {
    type: Array,
    required: true,
    default: () => []
  },
  location: {
    type: String,
    required: false,
    default: 'bottom end',
  },
  modelValue: {
    type: Boolean,
    required: false,
    default: false
  }
})

const emit = defineEmits([
  'remove',
  'toggle-read-status',
  'mark-all-as-read',
  'click:notification',
  'update:modelValue'
])

const totalUnseenNotifications = computed(() => {
  return props.notifications.filter(item => !item.isSeen).length
})

const isAllMarkRead = computed(() => {
  return props.notifications.some(item => !item.isSeen)
})

const router = useRouter();

const handleNotificationClick = (notification) => {
  if (!notification?.data?.project_id || !notification?.data?.container_id) {
    return
  }

  if(!notification.isSeen) {
    emit('toggle-read-status', notification)
  }
  emit('update:modelValue', false)

  router.push({
    path: `/projects/${notification.data.project_id}/container/${notification.data.container_id}`,
    query: { 
      openMessenger: 'true',
      taskId: notification.data?.task_id,
      taskName: notification.data?.task_name
    },
    replace: true
  })
}

const handleToggleReadStatus = (notification, event) => {
  event.stopPropagation();
  emit('toggle-read-status', notification);
}
</script>

<template>
  <IconBtn id="notification-btn">
    <VBadge
      :model-value="totalUnseenNotifications > 0"
      color="error"
      dot
      location="top end"
      offset-x="2"
      offset-y="2"
    >
      <VIcon icon="tabler-bell" />
    </VBadge>

    <VMenu
      activator="parent"
      width="420"
      :location="location"
      offset="12"
      :close-on-content-click="false"
      :model-value="modelValue"
      @update:model-value="$emit('update:modelValue', $event)"
    >
      <VCard class="notifications-card">
        <!-- Header -->
        <VCardItem class="notification-header px-4 py-3">
          <VCardTitle class="d-flex justify-space-between align-center">
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-bell"
                size="20"
                color="primary"
                class="me-2"
              />
              <span class="text-h6 font-weight-medium">Notifications</span>
            </div>
            <div class="d-flex align-center gap-2">
              <VChip
                v-if="totalUnseenNotifications > 0"
                size="small"
                color="primary"
                variant="flat"
                class="font-weight-medium"
              >
                {{ totalUnseenNotifications }} New
              </VChip>
              <IconBtn
                v-if="notifications.length"
                size="small"
                variant="text"
                color="primary"
                @click="$emit('mark-all-as-read')"
              >
                <VIcon
                  size="20"
                  :icon="isAllMarkRead ? 'tabler-mail' : 'tabler-mail-opened'"
                />
                <VTooltip activator="parent">
                  {{ isAllMarkRead ? 'Mark all as read' : 'All read' }}
                </VTooltip>
              </IconBtn>
            </div>
          </VCardTitle>
        </VCardItem>

        <VDivider />

        <!-- Notifications list -->
        <PerfectScrollbar
          :options="{ wheelPropagation: false }"
          class="notification-scroll"
        >
          <VList class="notification-list py-0">
            <template
              v-for="notification in notifications"
              :key="notification.id"
            >
              <VListItem
                class="notification-item"
                :class="{ 'unseen': !notification.isSeen }"
                @click="handleNotificationClick(notification)"
              >
                <template #prepend>
                  <div class="notification-icon-wrapper">
                    <VAvatar
                      :color="notification.color"
                      :class="[`bg-${notification.color}-subtle`]"
                      variant="tonal"
                      size="40"
                    >
                      <VIcon
                        :size="22"
                        :icon="notification.icon"
                        :color="notification.color"
                      />
                    </VAvatar>
                  </div>
                </template>

                <div class="notification-content">
                  <div class="notification-header d-flex align-center mb-2">
                    <div class="notification-emoji me-2">
                      {{ notification.data?.context?.emoji || '📌' }}
                    </div>
                    <VListItemTitle class="title-wrapper">
                      <span class="notification-title">{{ notification.title }}</span>
                    </VListItemTitle>
                  </div>
                  
                  <VListItemSubtitle class="subtitle-wrapper mb-2">
                    {{ notification.subtitle }}
                  </VListItemSubtitle>

                  <div class="notification-context mb-2">
                    <VChip
                      size="small"
                      variant="flat"
                      class="task-id-chip me-2"
                    >
                      #{{ notification.data?.task_sequence_id }}
                    </VChip>
                    <span class="text-medium-emphasis">{{ notification.data?.container_name }}</span>
                  </div>

                  <div class="notification-preview pa-2 rounded mb-2" v-if="notification.data?.comment_preview">
                    <div class="text-body-2">{{ notification.data.comment_preview }}</div>
                  </div>

                  <div class="notification-actions d-flex align-center justify-space-between mt-2">
                    <div class="time-wrapper d-flex align-center">
                      <VIcon
                        size="14"
                        icon="tabler-clock"
                        color="medium-emphasis"
                        class="me-1"
                      />
                      <span class="text-medium-emphasis">{{ notification.time }}</span>
                    </div>
                    
                    <div class="d-flex align-center gap-2">
                      <VBtn
                        size="x-small"
                        :color="notification.isSeen ? 'medium-emphasis' : 'primary'"
                        variant="text"
                        class="read-status-btn"
                        @click.stop="handleToggleReadStatus(notification, $event)"
                      >
                        <VIcon 
                          size="16" 
                          :icon="notification.isSeen ? 'tabler-mail-opened' : 'tabler-mail'" 
                          class="me-1"
                        />
                        {{ notification.isSeen ? 'Mark unread' : 'Mark read' }}
                      </VBtn>

                      <VBtn
                        icon
                        size="x-small"
                        variant="text"
                        color="error"
                        @click.stop="$emit('remove', notification.id)"
                      >
                        <VIcon size="16" icon="tabler-trash" />
                        <VTooltip activator="parent">
                          Delete notification
                        </VTooltip>
                      </VBtn>
                    </div>
                  </div>
                </div>
              </VListItem>
              <VDivider />
            </template>

            <VListItem
              v-if="!notifications.length"
              class="empty-state text-center py-8"
            >
              <VListItemTitle>
                <VIcon
                  icon="tabler-bell-off"
                  size="44"
                  class="mb-3"
                  color="medium-emphasis"
                />
                <div class="text-h6 text-medium-emphasis font-weight-medium mb-1">
                  No notifications
                </div>
                <div class="text-body-2 text-disabled">
                  We'll notify you when something arrives
                </div>
              </VListItemTitle>
            </VListItem>
          </VList>
        </PerfectScrollbar>

        <VDivider />

        <!-- Footer -->
        <VCardText class="notification-footer pa-4">
          <VBtn
            block
            to="/notifications"
            variant="tonal"
            color="primary"
            class="view-all-btn"
            prepend-icon="tabler-external-link"
          >
            View All Notifications
          </VBtn>
        </VCardText>
      </VCard>
    </VMenu>
  </IconBtn>
</template>

<style lang="scss" scoped>
.notifications-card {
  border: 1px solid rgba(var(--v-border-color), 0.12);
  box-shadow: 0 8px 24px rgba(27, 31, 35, 0.12);
  border-radius: 6px;
  overflow: hidden;
}

.notification-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
  padding: 12px 16px !important;

  .text-h6 {
    font-size: 1rem !important;
    letter-spacing: -0.25px;
  }
}

.notification-scroll {
  max-height: 480px;
  background-color: #ffffff;
}

.notification-item {
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  border-left: 2px solid transparent;

  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.04);
    
    .notification-actions {
      opacity: 1;
      transform: translateY(0);
    }
  }

  &.unseen {
    background-color: #f1f8ff;
    border-left-color: #0969da;

    .notification-title {
      color: #0969da;
      font-weight: 600;
    }

    &:hover::after {
      background-color: rgba(var(--v-theme-primary), 0.8);
    }
  }

  .notification-icon-wrapper {
    margin-right: 16px;

    .v-avatar {
      border: 1px solid rgba(var(--v-border-color), 0.12);
    }
  }

  .notification-content {
    flex: 1;
    min-width: 0;

    .notification-header {
      background: transparent;
      border: none;
      padding: 0 !important;
      margin-bottom: 8px;

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
        font-size: 0.875rem;
        font-weight: 500;
        color: #24292f;
        line-height: 1.4;
      }
    }

    .subtitle-wrapper {
      font-size: 0.8125rem;
      line-height: 1.4;
      color: #57606a;
      margin-bottom: 8px;
    }

    .notification-context {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;

      .task-id-chip {
        height: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background-color: #f6f8fa !important;
        border: 1px solid #d0d7de;
        color: #24292f !important;
        padding: 0 8px;

        &:hover {
          background-color: #f3f4f6 !important;
          border-color: #1b1f2326;
        }
      }

      .text-medium-emphasis {
        font-size: 0.75rem;
        color: #57606a;
        font-weight: 500;
      }
    }

    .notification-preview {
      background-color: #f6f8fa;
      border: 1px solid #d0d7de;
      border-radius: 6px;
      padding: 8px 12px;
      font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
      font-size: 0.8125rem;
      color: #57606a;
      margin-bottom: 12px;
    }

    .notification-actions {
      opacity: 0.8;
      transform: translateY(4px);
      transition: all 0.2s ease;

      .read-status-btn {
        font-size: 0.75rem;
        letter-spacing: 0;
        
        &:hover {
          background-color: rgba(var(--v-theme-primary), 0.08);
        }
      }
    }
  }
}

.notification-footer {
  background-color: #f8f9fa;
  border-top: 1px solid rgba(var(--v-border-color), 0.12);
  padding: 12px 16px !important;

  .view-all-btn {
    text-transform: none;
    font-size: 0.875rem;
    font-weight: 500;
    letter-spacing: 0;
    height: 32px;
    background-color: #f6f8fa;
    box-shadow: 0 1px 0 rgba(27, 31, 35, 0.04);
    
    &:hover {
      background-color: #f3f4f6;
      border-color: rgba(27, 31, 35, 0.15);
    }
  }
}

.empty-state {
  padding: 32px 16px;
  background-color: #ffffff;
  text-align: center;

  .v-icon {
    color: #8b949e;
    margin-bottom: 8px;
  }

  .text-h6 {
    font-size: 0.875rem !important;
    color: #24292f;
    margin-bottom: 4px;
  }

  .text-body-2 {
    font-size: 0.8125rem;
    color: #57606a;
  }
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
