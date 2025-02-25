<script setup>
import { defineExpose, ref, watch } from "vue"
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
])

const chatLogPS = ref()
const filterOption = ref("with_unread_comments")
const ticketSearch = ref("")
const boardData = ref({ boards: [] })

const filtersContent = [
  { title: "All", value: "all" },
  { title: "Unread Comments", value: "with_unread_comments" },
  { title: "Mentions", value: "with_mentions" },
]

const handleDrawerModelValueUpdate = val => {
  emit("update:isDrawerOpen", val)
}

const fetchBoardActivities = async () => {
  const res = await $api(`/container/activities/${props.boardId}`, {
    method: "POST",
    body: { filters: filterOption.value, search: ticketSearch.value },
  })

  if (res) {
    boardData.value = res.data
  }
}

const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false)
  emit("refreshKanbanData")
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

defineExpose({ fetchBoardActivities })
</script>

<template>
  <VNavigationDrawer
    temporary
    persistent
    :width="750"
    location="end"
    class="scrollable-content elegant-shadow"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      title="Board Activities"
      class="drawer-header"
      @cancel="closeNavigationDrawer"
    />

    <div class="filter-search-container">
      <VSelect
        v-model="filterOption"
        :items="filtersContent"
        item-title="title"
        item-value="value"
        label="Filter"
        color="primary"
        @update:model-value="fetchBoardActivities"
      />
      <VTextField
        v-model="ticketSearch"
        label="Search Tickets"
        append-icon="mdi-magnify"
        clearable
      />
    </div>
    <VDivider />

    <div
      ref="chatLogPS"
      class="flex-grow-1 board-container"
    >
      <div v-if="boardData && boardData.boards.length">
        <div class="board">
          <div
            class="board-header"
            :style="{ backgroundColor: '#f6f8fa' }"
          >
            General Chat
          </div>
          <ul class="task-list">
            <li
              class="task-item"
              @click=""
            >
              <span class="task-icon">💬</span>
              <span class="task-text font-weight-bold">Access Board Chat</span>
              <span class="task-arrow">→</span>
            </li>
          </ul>
        </div>
        <div
          v-for="board in boardData.boards"
          :key="board.id"
          class="board"
        >
          <div
            class="board-header"
            :style="{ backgroundColor: `${board.color}25` || '#f6f8fa' }"
          >
            {{ board.name }}
          </div>
          <ul class="task-list">
            <li
              v-for="task in board.tasks"
              :key="task.id"
              class="task-item"
              @click=""
            >
              <span class="task-icon">💬</span>
              <span class="task-text">{{ task.name }}</span>
              <span class="task-arrow">→</span>
            </li>
          </ul>
        </div>
      </div>
      <div
        v-else
        class="no-activities"
      >
        No activities found.
      </div>
    </div>
  </VNavigationDrawer>
</template>

<style scoped>
:root {
  --primary-color: #24292e;
  --secondary-color: #586069;
  --border-color: #e1e4e8;
  --background-color: #ffffff;
  --hover-background: #f6f8fa;
  --accent-color: #0366d6;
  --shadow-color: rgba(27, 31, 35, 0.08);
}

.elegant-shadow {
  box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.1);
}

.drawer-header {
  background-color: var(--background-color);
  padding: 16px;
  font-size: 18px;
  font-weight: bold;
  border-bottom: 2px solid var(--border-color);
}

.filter-search-container {
  display: flex;
  gap: 12px;
  padding: 12px 16px;
  align-items: center;
}

.board-container {
  height: calc(100% - 64px);
  overflow-y: auto;
  padding: 16px;
}

.board {
  background-color: var(--background-color);
  border-radius: 8px;
  margin-bottom: 20px;
  border: 1px solid var(--border-color);
  transition: box-shadow 0.3s ease;
}

.board-header {
  background-color: var(--hover-background);
  padding: 12px 16px;
  font-weight: 600;
  font-size: 16px;
  border-bottom: 1px solid var(--border-color);
}

.task-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.task-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
  cursor: pointer;
  transition: background-color 0.3s ease, transform 0.1s ease;
  color: var(--primary-color);
  position: relative;
}

.task-item:hover {
  background-color: var(--hover-background);
  transform: translateX(6px);
}

.task-item:last-child {
  border-bottom: none;
}

.task-icon {
  font-size: 18px;
  margin-right: 10px;
  opacity: 0.7;
}

.task-text {
  flex-grow: 1;
}

.task-arrow {
  font-size: 14px;
  color: var(--accent-color);
  font-weight: bold;
  transition: transform 0.3s ease;
}

.task-item:hover .task-arrow {
  transform: translateX(4px);
}

.no-activities {
  text-align: center;
  color: var(--secondary-color);
  padding: 20px;
  font-style: italic;
}

.scrollable-content {
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}
</style>
