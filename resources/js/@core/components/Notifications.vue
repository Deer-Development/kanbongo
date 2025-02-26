<script setup>
import { computed } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

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
      width="380"
      :location="location"
      offset="12"
      :close-on-content-click="false"
    >
      <VCard class="d-flex flex-column">
        <!-- 👉 Header -->
        <VCardItem class="notification-section">
          <VCardTitle class="text-h6">
            Notifications
          </VCardTitle>

          <template #append>
            <VChip
              v-if="totalUnseenNotifications > 0"
              size="small"
              color="primary"
              class="me-2"
            >
              {{ totalUnseenNotifications }} New
            </VChip>
            <IconBtn
              v-if="notifications.length"
              size="small"
              variant="text"
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
          </template>
        </VCardItem>

        <VDivider />

        <!-- 👉 Notifications list -->
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
                @click="$emit('click:notification', notification)"
              >
                <template #prepend>
                  <VAvatar
                    :color="notification.color"
                    variant="tonal"
                    size="38"
                  >
                    <VIcon
                      :size="20"
                      :icon="notification.icon"
                    />
                  </VAvatar>
                </template>

                <VListItemTitle class="font-weight-semibold text-sm mb-1">
                  {{ notification.title }}
                </VListItemTitle>
                <VListItemSubtitle class="text-xs">
                  {{ notification.subtitle }}
                </VListItemSubtitle>
                <div class="d-flex align-center mt-1">
                  <span class="text-xs text-disabled">{{ notification.time }}</span>
                  <VSpacer />
                  <IconBtn
                    size="x-small"
                    variant="text"
                    @click.stop="$emit('remove', notification.id)"
                  >
                    <VIcon size="16" icon="tabler-x" />
                  </IconBtn>
                </div>
              </VListItem>
              <VDivider />
            </template>

            <VListItem
              v-if="!notifications.length"
              class="text-center py-6"
            >
              <VListItemTitle>No notifications found</VListItemTitle>
            </VListItem>
          </VList>
        </PerfectScrollbar>

        <VDivider />

        <!-- 👉 Footer -->
        <VCardText class="text-center pa-4">
          <VBtn
            block
            size="small"
            to="/notifications"
          >
            View All Notifications
          </VBtn>
        </VCardText>
      </VCard>
    </VMenu>
  </IconBtn>
</template>

<style lang="scss" scoped>
.notification-scroll {
  max-height: 420px;
}

.notification-item {
  padding: 16px;
  cursor: pointer;
  transition: background-color 0.2s;

  &:hover {
    background-color: rgb(var(--v-theme-surface-variant));
  }

  &.unseen {
    background-color: rgb(var(--v-theme-primary), 0.05);
  }
}

.notification-section {
  padding: 16px;
}
</style>
