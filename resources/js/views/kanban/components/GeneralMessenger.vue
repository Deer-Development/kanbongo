<script setup>
import { defineExpose, ref, watch, computed } from "vue"
import { watchDebounced } from '@vueuse/core'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  boardId: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits([
  "update:isDrawerOpen",
  "refreshKanbanData",
  "openBoardChat"
])

const chatLogPS = ref()
const filterOption = ref("with_unread_comments")
const ticketSearch = ref("")
const boardData = ref({ boards: [] })
const isLoading = ref(false)

const filtersContent = [
  { title: "All", value: "all" },
  { title: "Unread Comments", value: "with_unread_comments" },
  { title: "Mentions", value: "with_mentions" },
]

const activeTab = ref('messages')

const handleDrawerModelValueUpdate = val => {
  emit("update:isDrawerOpen", val)
}

const fetchBoardActivities = async () => {
  isLoading.value = true
  try {
    const res = await $api(`/container/activities/${props.boardId}`, {
      method: "POST",
      body: { filters: filterOption.value, search: ticketSearch.value },
    })

    if (res) {
      boardData.value = res.data
    }
  } finally {
    setTimeout(() => {
      isLoading.value = false
    }, 300)
  }
}

const closeNavigationDrawer = () => {
  activeTab.value = 'messages'
  filterOption.value = "with_unread_comments"
  ticketSearch.value = ""
  boardData.value = { boards: [] }
  
  emit("update:isDrawerOpen", false)
  
  emit("refreshKanbanData")
}

const openBoardChat = (boardId) => {
  emit('openBoardChat', boardId, 'board')
}

const openTaskChat = (task) => {
  emit('openBoardChat', task, 'task')
}

watch(() => props.isDrawerOpen, async val => {
  if (val) {
    await fetchBoardActivities()
  }
})

watchDebounced(
  ticketSearch,
  fetchBoardActivities,
  { debounce: 500, maxWait: 1000 },
)

const getTotalTasks = computed(() => {
  return boardData.value.boards?.reduce((acc, board) => 
    acc + (board.tasks?.length || 0), 0) || 0
})

const getTotalTrackedTime = computed(() => {
  return boardData.value.boards?.reduce((acc, board) => {
    return acc + board.tasks?.reduce((taskAcc, task) => 
      taskAcc + (task.tracked_time?.trackedTime || 0), 0) || 0
  }, 0) || 0
})

const getTotalComments = computed(() => {
  return boardData.value.boards?.reduce((acc, board) => {
    return acc + board.tasks?.reduce((taskAcc, task) => 
      taskAcc + (task.comments_count || 0), 0) || 0
  }, 0) || 0
})

const formatTime = (seconds) => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  if (hours >= 1000) {
    return `${(hours/1000).toFixed(1)}k h`
  }
  return `${hours}h`
}

defineExpose({ fetchBoardActivities })
</script>

<template>
  <VNavigationDrawer
    :model-value="isDrawerOpen"
    temporary
    location="right"
    width="750"
    class="messages-drawer"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <div class="drawer-header">
      <div class="d-flex justify-space-between align-center px-4 py-3">
        <h2 class="text-h6 font-weight-medium mb-0">Board Messages</h2>
        <div class="d-flex gap-2">
          <VBtn
            icon
            variant="text"
            size="small"
            @click="fetchBoardActivities"
          >
            <VIcon>tabler-refresh</VIcon>
          </VBtn>
          <VBtn
            icon
            variant="text"
            size="small"
            @click="closeNavigationDrawer"
          >
            <VIcon>tabler-x</VIcon>
          </VBtn>
        </div>
      </div>
      <VDivider />
    </div>

    <div class="drawer-content">
      <div class="messages-content">
        <!-- Filters Section -->
        <div class="filters-section px-4 py-3">
          <div class="d-flex gap-3 align-center">
            <VSelect
              v-model="filterOption"
              :items="filtersContent"
              item-title="title"
              item-value="value"
              density="compact"
              hide-details
              class="filter-select"
              @update:model-value="fetchBoardActivities"
            />
            <VTextField
              v-model="ticketSearch"
              placeholder="Search tasks..."
              prepend-inner-icon="tabler-search"
              density="compact"
              hide-details
              class="search-field"
            />
          </div>
        </div>

        <!-- Board Chat Section -->
        <div class="board-chat-section px-4 py-3">
          <div 
            class="board-chat-card"
            :class="{ 'skeleton': isLoading }"
            @click="openBoardChat(null, 'board')"
          >
            <div class="chat-content">
              <div class="chat-icon">
                <VIcon size="20" color="primary">tabler-messages</VIcon>
              </div>
              <div class="chat-info">
                <h3 class="chat-title">Board Chat</h3>
                <span class="chat-description">General discussion for all members</span>
              </div>
            </div>
            <VIcon size="16" class="arrow-icon">tabler-chevron-right</VIcon>
          </div>
        </div>

        <VDivider />

        <!-- Board Stats -->
        <div class="board-stats px-4 py-3">
          <div class="stats-grid">
            <template v-if="isLoading">
              <div v-for="n in 4" :key="n" class="stat-card skeleton">
                <div class="stat-icon skeleton-box" />
                <div class="stat-info">
                  <span class="stat-value skeleton-text" />
                  <span class="stat-label skeleton-text" />
                </div>
              </div>
            </template>
            <template v-else>
              <div class="stat-card">
                <div class="stat-icon" :style="{ backgroundColor: '#FFF3E0' }">
                  <VIcon color="primary">tabler-list-check</VIcon>
                </div>
                <div class="stat-info">
                  <span class="stat-value">{{ boardData.boards?.length || 0 }}</span>
                  <span class="stat-label">Lists</span>
                </div>
              </div>
              
              <div class="stat-card">
                <div class="stat-icon" :style="{ backgroundColor: '#F3E5F5' }">
                  <VIcon color="#800080">tabler-clipboard-list</VIcon>
                </div>
                <div class="stat-info">
                  <span class="stat-value">{{ getTotalTasks }}</span>
                  <span class="stat-label">Tasks</span>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon" :style="{ backgroundColor: '#E8F5E9' }">
                  <VIcon color="success">tabler-clock-play</VIcon>
                </div>
                <div class="stat-info">
                  <span class="stat-value time-value">{{ formatTime(getTotalTrackedTime) }}</span>
                  <span class="stat-label">Total Time</span>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon" :style="{ backgroundColor: '#FFF3E0' }">
                  <VIcon color="warning">tabler-messages</VIcon>
                </div>
                <div class="stat-info">
                  <span class="stat-value">{{ getTotalComments }}</span>
                  <span class="stat-label">Comments</span>
                </div>
              </div>
            </template>
          </div>
        </div>

        <VDivider />

        <!-- Tasks List -->
        <div class="tasks-section px-4 py-3">
          <template v-if="isLoading">
            <div v-for="n in 3" :key="n" class="board-section mb-4">
              <div class="board-header d-flex align-center gap-2 mb-2">
                <div class="color-dot skeleton" />
                <h3 class="skeleton-text" style="width: 120px" />
              </div>

              <div class="tasks-list">
                <div v-for="t in 2" :key="t" class="task-item skeleton">
                  <div class="task-content">
                    <div class="d-flex align-center gap-2">
                      <div class="skeleton-circle" />
                      <span class="skeleton-text" style="width: 70%" />
                    </div>
                    <div class="task-meta">
                      <div v-for="m in 3" :key="m" class="meta-item">
                        <div class="skeleton-circle" />
                        <span class="skeleton-text" style="width: 30px" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
          <template v-else>
            <template v-for="board in boardData.boards" :key="board.id">
              <div class="board-section mb-4">
                <div class="board-header d-flex align-center gap-2 mb-2">
                  <div 
                    class="color-dot" 
                    :style="{ backgroundColor: board.color }"
                  />
                  <h3 class="text-subtitle-2 font-weight-medium mb-0">
                    {{ board.name }}
                    <span class="text-caption text-medium-emphasis">
                      ({{ board.tasks?.length || 0 }} tasks)
                    </span>
                  </h3>
                </div>

                <div class="tasks-list">
                  <div 
                    v-for="task in board.tasks" 
                    :key="task.id"
                    class="task-item"
                    @click="openTaskChat(task)"
                  >
                    <div class="task-content">
                      <div class="d-flex align-center gap-2">
                        <VIcon 
                          size="16"
                          :color="task.has_unread_comments ? 'warning' : 'grey'"
                        >
                          {{ task.has_unread_comments ? 'tabler-message-circle-2-filled' : 'tabler-message-circle' }}
                        </VIcon>
                        <span class="task-name">{{ task.name }}</span>
                      </div>
                      
                      <div class="task-meta">
                        <div class="meta-item" v-if="task.tracked_time">
                          <VIcon size="14" color="success">tabler-clock</VIcon>
                          <span>{{ task.tracked_time.trackedTimeDisplay }}</span>
                        </div>
                        <div class="meta-item" v-if="task.comments_count">
                          <VIcon size="14">tabler-messages</VIcon>
                          <span>{{ task.comments_count }}</span>
                        </div>
                        <div class="meta-item" v-if="task.members?.length">
                          <VIcon size="14">tabler-users</VIcon>
                          <span>{{ task.members.length }}</span>
                        </div>
                      </div>
                    </div>

                    <VIcon size="16" class="arrow-icon">tabler-chevron-right</VIcon>
                  </div>
                </div>
              </div>
            </template>
          </template>
        </div>
      </div>
    </div>
  </VNavigationDrawer>
</template>

<style lang="scss" scoped>
.messages-drawer {
  display: flex;
  flex-direction: column;
  height: 100%;

  .drawer-header {
    background-color: #fff;
    border-bottom: 0;
    flex-shrink: 0;
  }

  .drawer-content {
    flex-grow: 1;
    overflow: hidden;
    height: calc(100% - 64px);
  }

  .messages-content {
    height: 100%;
    overflow-y: auto;

    .filters-section {
      .filter-select {
        max-width: 200px;
      }
    }

    .board-stats {
      .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 16px;
      }

      .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e5e7eb;

        .stat-icon {
          width: 40px;
          height: 40px;
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .stat-info {
          display: flex;
          flex-direction: column;

          .stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            line-height: 1;
          }

          .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
          }

          .time-value {
            font-size: 1.1rem;
            letter-spacing: -0.5px;
          }
        }
      }
    }

    .tasks-section {
      .board-header {
        .color-dot {
          width: 10px;
          height: 10px;
          border-radius: 50%;
        }
      }

      .task-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s ease;

        &:hover {
          background: #f9fafb;
          border-color: #d1d5db;
          transform: translateX(4px);

          .arrow-icon {
            opacity: 1;
            transform: translateX(0);
          }
        }

        .task-content {
          flex: 1;
          min-width: 0;

          .task-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }

          .task-meta {
            display: flex;
            gap: 12px;
            margin-top: 4px;

            .meta-item {
              display: flex;
              align-items: center;
              gap: 4px;
              font-size: 0.75rem;
              color: #6b7280;
            }
          }
        }

        .arrow-icon {
          opacity: 0;
          transform: translateX(-4px);
          transition: all 0.2s ease;
        }
      }
    }

    .board-chat-section {
      .board-chat-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        background: #ffffff;
        border: 1px solid #d0d7de;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;

        &:hover {
          border-color: #0969da;
          background: #f6f8fa;
          transform: translateX(4px);

          .arrow-icon {
            opacity: 1;
            transform: translateX(0);
          }
        }

        .chat-content {
          display: flex;
          align-items: center;
          gap: 1rem;

          .chat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #FFF3E0;
            border-radius: 8px;
          }

          .chat-info {
            .chat-title {
              font-size: 0.875rem;
              font-weight: 600;
              color: #24292f;
              margin: 0;
            }

            .chat-description {
              font-size: 0.75rem;
              color: #57606a;
            }
          }
        }

        .arrow-icon {
          opacity: 0;
          transform: translateX(-4px);
          transition: all 0.2s ease;
          color: #57606a;
        }
      }
    }
  }
}

@keyframes shimmer {
  0% {
    background-position: -1000px 0;
  }
  100% {
    background-position: 1000px 0;
  }
}

.skeleton {
  position: relative;
  overflow: hidden;
  background: #f6f7f8;
  
  &::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(
      90deg,
      rgba(255, 255, 255, 0) 0,
      rgba(255, 255, 255, 0.6) 50%,
      rgba(255, 255, 255, 0) 100%
    );
    animation: shimmer 2s infinite;
  }

  &.task-item {
    background: #fff;
    border: 1px solid #e5e7eb;
    
    .skeleton-circle {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background: #e5e7eb;
    }
  }
}

.skeleton-text {
  height: 14px;
  background: #e5e7eb;
  border-radius: 4px;
  display: inline-block;
}

.skeleton-box {
  background: #e5e7eb;
  border-radius: 8px;
}

.stat-card.skeleton {
  .stat-icon {
    width: 40px;
    height: 40px;
  }
  
  .stat-info {
    .stat-value {
      width: 40px;
      height: 20px;
      margin-bottom: 4px;
    }
    
    .stat-label {
      width: 60px;
      height: 14px;
    }
  }
}

.board-chat-card.skeleton {
  .chat-icon {
    background: #e5e7eb !important;
  }
  
  .chat-info {
    .chat-title, .chat-description {
      background: #e5e7eb;
      height: 14px;
      width: 120px;
      border-radius: 4px;
      margin: 4px 0;
    }
  }
}

.task-item, .stat-card, .board-chat-card {
  transition: all 0.3s ease;
}
</style>
