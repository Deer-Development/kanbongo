<script setup>
import { ref, watch, computed, onMounted } from 'vue'

const props = defineProps({
  boardId: {
    type: Number,
    required: true,
  },
  isActive: {
    type: Boolean,
    default: false
  }
})

const activities = ref([])
const isLoading = ref(false)
const isLoadingMore = ref(false)
const currentPage = ref(1)
const hasMore = ref(true)
const filterType = ref('all')
const isInitialized = ref(false)

const filterTypes = [
  { label: 'All Activities', value: 'all' },
  { label: 'Task Updates', value: 'Task' },
  { label: 'Member Changes', value: 'member' },
  { label: 'Time Tracking', value: 'time' }
]

const filteredActivities = computed(() => {
  if (!activities.value?.length) return []
  if (filterType.value === 'all') return activities.value
  
  return activities.value.filter(activity => {
    switch (filterType.value) {
      case 'Task':
        return activity.subject?.type === 'App\\Models\\Task'
      case 'member':
        return ['member_added', 'member_removed'].includes(activity.event)
      case 'time':
        return activity.event === 'time_entry_completed'
      default:
        return true
    }
  })
})

const fetchActivities = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await $api(`/container/${props.boardId}/board-activities`, {
      params: { page }
    })
    
    if (page === 1) {
      activities.value = res.data.activities
    } else {
      activities.value = [...activities.value, ...res.data.activities]
    }
    
    hasMore.value = res.data.has_more
    currentPage.value = page
    isInitialized.value = true
  } catch (error) {
    console.error('Error fetching activities:', error)
    if (page === 1) activities.value = []
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

const loadMore = async () => {
  if (isLoadingMore.value || !hasMore.value) return
  
  isLoadingMore.value = true
  await fetchActivities(currentPage.value + 1)
}

const getEventIcon = (activity) => {
  switch (activity.event) {
    case 'created':
      return { icon: 'tabler-plus', color: 'text-success' }
    case 'updated':
      return { icon: 'tabler-edit', color: 'text-info' }
    case 'deleted':
      return { icon: 'tabler-trash', color: 'text-error' }
    case 'member_added':
      return { icon: 'tabler-user-plus', color: 'text-primary' }
    case 'member_removed':
      return { icon: 'tabler-user-minus', color: 'text-warning' }
    case 'time_entry_completed':
      return { icon: 'tabler-clock-check', color: 'text-success' }
    default:
      return { icon: 'tabler-activity', color: 'text-grey' }
  }
}

// Fetch la montarea componentei dacă tab-ul este activ
onMounted(() => {
  if (props.isActive) {
    fetchActivities()
  }
})

// Watch pentru schimbări ulterioare ale tab-ului
watch(() => props.isActive, (newValue) => {
  if (newValue && !isInitialized.value) {
    fetchActivities()
  }
})

defineExpose({ fetchActivities })
</script>

<template>
  <div class="activities-wrapper">
    <div class="activities-header">
      <VSelect
        v-model="filterType"
        :items="filterTypes"
        item-title="label"
        item-value="value"
        density="compact"
        hide-details
        variant="underlined"
        class="filter-select"
      />
    </div>

    <VDivider />

    <div class="activities-content">
      <VProgressLinear
        v-if="isLoading && !activities.length"
        indeterminate
        color="primary"
      />
      
      <div 
        v-if="activities.length > 0" 
        class="activities-list"
      >
        <div
          v-for="activity in filteredActivities"
          :key="activity.id"
          class="activity-item"
        >
          <div class="activity-user">
            <VAvatar
              :image="activity.user.avatar"
              :size="24"
              class="user-avatar"
            >
              <span v-if="!activity.user.avatar" class="text-xs">
                {{ activity.user.initials }}
              </span>
            </VAvatar>
          </div>
          
          <div class="activity-details">
            <div class="activity-content">
              <VIcon
                :icon="getEventIcon(activity).icon"
                :class="getEventIcon(activity).color"
                size="16"
                class="me-1"
              />
              <span class="activity-text">
                {{ activity.description.split('Task #')[0] }}
                <VChip
                  v-if="activity.properties?.attributes?.sequence_id"
                  size="x-small"
                  color="primary"
                  class="task-badge mx-1"
                >
                  Task #{{ activity.properties.attributes.sequence_id }}
                </VChip>
                {{ activity.description.split('Task #')[1]?.split(' ').slice(1).join(' ') }}
              </span>
            </div>
            
            <div class="activity-meta">
              <span class="activity-time">{{ activity.created_at }}</span>
            </div>
          </div>
        </div>

        <!-- Load More Button -->
        <div 
          v-if="hasMore" 
          class="load-more-wrapper"
        >
          <VBtn
            variant="text"
            :loading="isLoadingMore"
            :disabled="isLoadingMore"
            block
            @click="loadMore"
          >
            {{ isLoadingMore ? 'Loading...' : 'Load More' }}
          </VBtn>
        </div>
      </div>
      
      <div
        v-else-if="!isLoading"
        class="no-activities"
      >
        <VIcon
          icon="tabler-clock"
          size="24"
          class="text-medium-emphasis mb-2"
        />
        <span>No activities found</span>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.activities-wrapper {
  height: 100%;
  display: flex;
  flex-direction: column;
  background-color: rgb(var(--v-theme-background));

  .activities-header {
    padding: 12px 16px;
    background-color: rgb(var(--v-theme-surface));
    
    .filter-select {
      max-width: 180px;
      font-size: 0.875rem;
    }
  }

  .activities-content {
    flex: 1;
    overflow-y: auto;
    padding: 0;

    .activities-list {
      padding: 8px 16px;

      .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid rgba(var(--v-border-color), 0.08);

        &:last-child {
          border-bottom: none;
        }

        .activity-user {
          flex-shrink: 0;

          .user-avatar {
            border: 1px solid rgba(var(--v-border-color), 0.12);
          }
        }

        .activity-details {
          flex: 1;
          min-width: 0;
          font-size: 0.875rem;

          .activity-content {
            display: flex;
            align-items: center;
            color: rgb(var(--v-theme-on-surface));

            .activity-text {
              margin-left: 4px;
              line-height: 1.4;
            }
          }

          .activity-meta {
            margin-top: 2px;
            
            .activity-time {
              font-size: 0.75rem;
              color: rgba(var(--v-theme-on-surface), 0.6);
            }
          }
        }
      }
    }

    .no-activities {
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: rgba(var(--v-theme-on-surface), 0.6);
      font-size: 0.875rem;
      padding: 32px 16px;
    }
  }
}

// Responsive adjustments
@media (max-width: 600px) {
  .activities-wrapper {
    .activities-content {
      .activities-list {
        .activity-item {
          padding: 12px 0;
          
          .activity-details {
            font-size: 0.813rem;
          }
        }
      }
    }
  }
}

.activity-content {
  .task-badge {
    display: inline-flex;
    vertical-align: middle;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0 6px;
    height: 20px;
    background-color: rgba(var(--v-theme-primary), 0.12);
    color: rgb(var(--v-theme-primary));
  }
}

.load-more-wrapper {
  padding: 16px;
  text-align: center;
  
  .v-btn {
    color: rgb(var(--v-theme-primary));
    
    &:hover {
      background-color: rgba(var(--v-theme-primary), 0.04);
    }
  }
}

// Opțional: Adăugăm un indicator de loading pentru infinite scroll
.loading-indicator {
  display: flex;
  justify-content: center;
  padding: 16px;
  
  .v-progress-circular {
    color: rgb(var(--v-theme-primary));
  }
}
</style> 