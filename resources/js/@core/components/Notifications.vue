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
})

const emit = defineEmits([
  'remove',
  'mark-as-read',
  'mark-all-as-read',
  'click:notification',
])

const totalUnseenNotifications = computed(() => {
  return props.notifications.filter(item => !item.isSeen).length
})

const isAllMarkRead = computed(() => {
  return props.notifications.some(item => !item.isSeen)
})

const router = useRouter();

const handleNotificationClick = (notification) => {
  emit('mark-as-read', notification);
}

const handleActionClick = (notification) => {
  if (!notification?.data?.project_id || !notification?.data?.container_id) {
    return;
  }

  const route = `/project/${notification.data.project_id}/container/${notification.data.container_id}`;
  router.push(route);
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
                      <span class="font-weight-medium">{{ notification.title }}</span>
                    </VListItemTitle>
                  </div>
                  
                  <VListItemSubtitle class="subtitle-wrapper mb-2">
                    {{ notification.subtitle }}
                  </VListItemSubtitle>

                  <div class="notification-context mb-2">
                    <VChip
                      size="small"
                      :color="notification.data?.context?.color || 'primary'"
                      variant="flat"
                      class="me-2"
                    >
                      #{{ notification.data?.task_sequence_id }}
                    </VChip>
                    <span class="text-medium-emphasis">{{ notification.data?.container_name }}</span>
                  </div>

                  <div class="notification-preview pa-2 rounded mb-2" v-if="notification.data?.comment_preview">
                    <div class="text-body-2">{{ notification.data.comment_preview }}</div>
                  </div>

                  <div class="notification-actions d-flex align-center justify-space-between">
                    <div class="time-wrapper d-flex align-center">
                      <VIcon
                        size="14"
                        icon="tabler-clock"
                        color="medium-emphasis"
                        class="me-1"
                      />
                      <span class="text-medium-emphasis">{{ notification.time }}</span>
                    </div>
                    
                    <div class="d-flex align-center">
                      <VBtn
                        size="small"
                        :color="notification.data?.context?.color || 'primary'"
                        variant="text"
                        class="me-2"
                        :prepend-icon="notification.data?.action?.icon || 'tabler-eye'"
                        @click.stop="handleActionClick(notification)"
                      >
                        {{ notification.data?.action?.text || 'View' }}
                      </VBtn>
                      <VBtn
                        size="x-small"
                        variant="text"
                        color="medium-emphasis"
                        class="remove-btn"
                        @click.stop="$emit('remove', notification.id)"
                      >
                        <VIcon size="16" icon="tabler-x" />
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
  border: 1px solid rgba(var(--v-border-color), 0.08);
  box-shadow: 0 8px 24px rgba(var(--v-shadow-key-umbra-color), 0.12);
  overflow: hidden;
}

.notification-header {
  background-color: rgb(var(--v-theme-background));
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
}

.notification-scroll {
  max-height: 420px;
  background-color: rgb(var(--v-theme-background));
}

.notification-item {
  padding: 16px;
  cursor: pointer;
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;

  &::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background-color: transparent;
    transition: all 0.25s ease;
  }

  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.04);
    transform: translateX(4px);

    .remove-btn {
      opacity: 1;
      transform: translateX(0);
    }

    .notification-icon-wrapper {
      transform: scale(1.05);
    }
  }

  &.unseen {
    background-color: rgba(var(--v-theme-primary), 0.04);

    &::before {
      background-color: rgb(var(--v-theme-primary));
    }

    .title-wrapper {
      font-weight: 600;
      color: rgb(var(--v-theme-primary));
    }
  }

  .notification-icon-wrapper {
    margin-right: 16px;
    transition: transform 0.25s ease;
  }

  .notification-content {
    flex: 1;
    min-width: 0;

    .title-wrapper {
      font-size: 0.9375rem;
      line-height: 1.3;
      color: rgb(var(--v-theme-on-background));
    }

    .subtitle-wrapper {
      font-size: 0.875rem;
      line-height: 1.5;
      color: rgba(var(--v-theme-on-background), 0.7);
    }

    .notification-meta {
      .time-wrapper {
        display: flex;
        align-items: center;
        font-size: 0.8125rem;
      }

      .remove-btn {
        opacity: 0;
        transform: translateX(-8px);
        transition: all 0.25s ease;
        
        &:hover {
          color: rgb(var(--v-theme-error)) !important;
          background-color: rgba(var(--v-theme-error), 0.1);
        }
      }
    }
  }
}

.notification-footer {
  background-color: rgb(var(--v-theme-background));
  border-top: 1px solid rgba(var(--v-border-color), 0.08);

  .view-all-btn {
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: none;
    
    &:hover {
      transform: translateY(-1px);
    }
  }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem 1rem;
  background-color: rgb(var(--v-theme-background));
}

// Utility classes for notification icons
.bg-primary-subtle {
  background-color: rgba(var(--v-theme-primary), 0.12) !important;
}

.bg-success-subtle {
  background-color: rgba(var(--v-theme-success), 0.12) !important;
}

.bg-warning-subtle {
  background-color: rgba(var(--v-theme-warning), 0.12) !important;
}

.bg-error-subtle {
  background-color: rgba(var(--v-theme-error), 0.12) !important;
}

.bg-info-subtle {
  background-color: rgba(var(--v-theme-info), 0.12) !important;
}

.notification-preview {
  background-color: rgba(var(--v-theme-surface-variant), 0.5);
  border: 1px solid rgba(var(--v-border-color), 0.08);
  font-family: monospace;
}

.notification-emoji {
  font-size: 1.25rem;
  line-height: 1;
}

.notification-context {
  font-size: 0.875rem;
}
</style>
