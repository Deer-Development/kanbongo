<script setup>
import KanbanBoardComp from './components/KanbanBoard.vue'
import { differenceInSeconds, format, formatDistanceToNow, parse, parseISO } from 'date-fns'
import { watch, onMounted, onBeforeUnmount } from "vue"
import PaymentDetails from "@/views/projects/dialogs/PaymentDetails.vue"
import AddEditBoard from "@/views/projects/dialogs/AddEditBoard.vue"
import { useToast } from "vue-toastification"
import PriorityFilterDropdown from "@core/components/app-form-elements/PriorityFilterDropdown.vue"
import GeneralMessenger from "@/views/kanban/components/GeneralMessenger.vue"
import Messenger from "@/views/kanban/components/Messenger.vue"
import { useRouter } from "vue-router"
import { useTimerStore } from '@/stores/useTimerStore'
import DeleteBoardDialog from './dialogs/DeleteBoardDialog.vue'

const route = useRoute()
const isDeleteModalVisible = ref(false)
const isPaymentDetailsDialogVisible = ref(false)
const isMessagesDialogVisible = ref(false)
const isEditContainerDialogVisible = ref(false)
const activeUsersMenu = ref(false)
const deleteItem = ref(null)
const kanbanBoard = ref(null)
const kanban = ref(null)
const priorityFilter = ref([])
const usersFilter = ref([])
const tagsFilter = ref([])
const searchFilter = ref('')
const saveFilters = ref(false)
const userData = computed(() => useCookie('userData', { default: null }).value)
const isMessengerDrawerOpen = ref(false)
const selectedKanbanItem = ref(null)
const router = useRouter()
const initialQueryParams = ref(null)
const timerStore = useTimerStore()
const deleteBoardDialog = ref(null)
const isDeleteBoardDialogVisible = ref(false)
const deleteBoardDetails = ref(null)
const isFromGeneralMessenger = ref(false)

const refetchKanban = async () => {
  const wasOpen = isMessengerDrawerOpen.value
  const currentItem = selectedKanbanItem.value

  const res = await $api(`/container/${route.params.containerId}`, {
    method: 'POST',
    body: {
      filters: {
        priority: priorityFilter.value,
        users: usersFilter.value,
        tags: tagsFilter.value,
        search: searchFilter.value,
      },
      save: saveFilters.value,
    },
  })

  kanban.value = res.data.container

  if (res.data.filters) {
    priorityFilter.value = res.data.filters.priority || []
    usersFilter.value = res.data.filters.users
    tagsFilter.value = res.data.filters.tags
    searchFilter.value = res.data.filters.search
  }

  if (wasOpen && currentItem && !initialQueryParams.value) {
    nextTick(() => {
      const updatedTask = kanban.value.boards?.flatMap(board => board.tasks)
        .find(item => item.id === currentItem.item.id)
      
      if (updatedTask) {
        selectedKanbanItem.value = {
          item: updatedTask,
          boardId: updatedTask.board_id,
          boardName: kanban.value.boards.find(b => b.id === updatedTask.board_id)?.name
        }
        isMessengerDrawerOpen.value = true
      }
    })
  }
}

const kanbanData = computed(() => kanban.value)
const isOwner = computed(() => kanbanData.value?.owner_id === userData.value.id)
const boardId = computed(() => kanban.value?.id)
const isSuperAdmin = computed(() => kanbanData.value?.auth.is_super_admin)

const breadcumItems = computed(() => {
  return [
    {
      title: 'Projects',
      disabled: false,
      href: '/projects',
    },
    {
      title: `${ kanbanData.value?.project_name }`,
      disabled: false,
      href: `/projects/${ kanbanData.value?.project_id }`,
    },
    {
      title: `${ kanbanData.value?.name }`,
      disabled: true,
      href: `/projects/${ kanbanData.value?.project_id }/container/${ kanbanData.value?.id }`,
    },
  ]
})

const addNewBoard = async (newBoardName, newBoardColor) => {
  await $api('/board', {
    method: 'POST',
    body: { name: newBoardName, color: newBoardColor, container_id: route.params.containerId },
  })
  refetchKanban()
}

const openDeleteBoardDialog = (boardId, availableBoards) => {
  const board = kanban.value.boards.find(b => b.id === boardId)
  const filteredBoards = availableBoards.filter(b => b.id !== boardId)
  
  deleteBoardDetails.value = {
    boardId,
    board,
    availableBoards: filteredBoards.map(b => ({
      id: b.id,
      name: b.name,
      color: b.color,
      icon: 'tabler-layout-board'
    }))
  }
  isDeleteBoardDialogVisible.value = true
}

const deleteBoard = async (result) => {
  if (!result.confirmed || !deleteBoardDetails.value) return
  
  await $api(`/board/${deleteBoardDetails.value.boardId}`, { 
    method: 'DELETE',
    body: { 
      targetBoardId: result.targetBoardId
    }
  })
  
  refetchKanban()
  isDeleteBoardDialogVisible.value = false
  deleteBoardDetails.value = null
}

const cancelDeleteBoard = () => {
  isDeleteBoardDialogVisible.value = false
  deleteBoardDetails.value = null
}

const toggleTimerFn = async (memberData, taskId) => {
  const res = await $api(`/task/toggle-timer/${taskId}`, {
    method: 'POST',
    body: memberData,
  })

  if(res) {
    refetchKanban()
  }
}

const renameTheBoard = async kanbanBoard => {
  await $api(`/board/${ kanbanBoard.boardId }`, {
    method: 'PUT',
    body: kanbanBoard,
  })
  refetchKanban()
}

const addNewItem = async newItem => {
  await $api('/task', {
    method: 'POST',
    body: newItem,
  })

  refetchKanban()
}

const editItemFn = async editItem => {
  if (editItem) {
    selectedKanbanItem.value = editItem
    isMessengerDrawerOpen.value = true
  }
}

const membersEdited = async () => {
  refetchKanban()
}

const persistDelete = item => {
  deleteItem.value = item

  isDeleteModalVisible.value = true
}

const deleteItemFn = async () => {
  if (deleteItem.value.item && deleteItem.value.item.id) {
    await $api(`/task/${ deleteItem.value.item.id }`, {
      method: 'DELETE',
    })

    refetchKanban()
    isDeleteModalVisible.value = false
    deleteItem.value = null
    kanbanBoard.value.isKanbanBoardEditVisible = false
  }
}

const updateItemState = async kanbanState => {
  await $api(`/board/state-update/${kanbanState.boardId}`, {
    method: 'PUT',
    body: kanbanState.ids,
  })

  refetchKanban()
}

const updateBoardState = async kanbanBoardIds => {
  await $api(`/container/boards-state-update/${ route.params.containerId }`, {
    method: 'PUT',
    body: kanbanBoardIds,
  })
}

const userToggleStatus = reactive({})
const toast = useToast()

const checkWeeklyLimitAndToggle = entry => {
  if (!entry.has_weekly_limit || entry.user.id !== userData.value.id || !entry.time_entry?.start) return

  const startDate = parseISO(entry.time_entry.start)
  const now = new Date()
  const elapsedSeconds = differenceInSeconds(now, startDate)

  const totalTracked = entry.weekly_tracked.total_seconds + elapsedSeconds
  const remainingSeconds = entry.weekly_limit_seconds - totalTracked

  if (remainingSeconds <= 0 && !userToggleStatus[entry.user.id]) {
    userToggleStatus[entry.user.id] = true
    toggleTimerFn({
      user_id: entry.user.id,
      task_id: entry.time_entry.task_id,
      stopped_by_system: true,
    }, entry.time_entry.task_id)
    toast.error('Weekly limit reached. Timer stopped.')
    clearUserTimers()
  } else if (remainingSeconds > 0) {
    setTimeout(() => {
      if (!userToggleStatus[entry.user.id]) {
        userToggleStatus[entry.user.id] = true
        toggleTimerFn({
          user_id: entry.user.id,
          task_id: entry.time_entry.task_id,
          stopped_by_system: true,
        }, entry.time_entry.task_id)
        toast.error('Weekly limit reached. Timer stopped.')
        clearUserTimers()
      }
    }, remainingSeconds * 1000)
  }
}

const updateUserTimers = () => {
  kanbanData.value?.active_users?.forEach(entry => {
    if (!entry.time_entry?.start) {
      return
    }

    timerStore.startTimer(entry.user.id, entry.time_entry, true)
  })
}

const clearUserTimers = () => {
  timerStore.clearAllTimers(true)
  Object.keys(userToggleStatus).forEach(userId => {
    delete userToggleStatus[userId]
  })
}

const setPriority = data => {
  priorityFilter.value = data
  saveFilters.value = true
  refetchKanban()
}

const setUsersFilter = users => {
  usersFilter.value = users
  saveFilters.value = true
  refetchKanban()
}

const setTagsFilter = tags => {
  tagsFilter.value = tags
  saveFilters.value = true
  refetchKanban()
}

const setSearchFilter = search => {
  searchFilter.value = search
  saveFilters.value = true
  refetchKanban()
}

watch(
  () => activeUsersMenu.value,
  isOpen => {
    if (isOpen) {
      updateUserTimers()
    } else {
      clearUserTimers()
    }
  },
)

watch(
  () => kanbanData.value,
  () => {
    updateUserTimers()
  }, { deep: true, immediate: true })

onMounted(() => {
  if (route.query.openMessenger === 'true' && route.query.taskId) {
    initialQueryParams.value = { ...route.query }
    
    router.replace({
      path: route.path,
      query: {}
    })
  }
  
  refetchKanban()
})

onBeforeUnmount(() => {
  clearUserTimers()
})

const openMessenger = (item) => {
  selectedKanbanItem.value = item

  isMessengerDrawerOpen.value = true
}

const openBoardMessenger = (itemOpen, type) => {
  if(type === 'board') {
    selectedKanbanItem.value = null
  } else if(type === 'task') {
    selectedKanbanItem.value = {
      item: itemOpen
    }
  }
  isFromGeneralMessenger.value = true
  isMessagesDialogVisible.value = false
  isMessengerDrawerOpen.value = true
}

const handleBackToGeneral = () => {
  isMessengerDrawerOpen.value = false
  isFromGeneralMessenger.value = false
  isMessagesDialogVisible.value = true
}

const handleMessengerUpdate = (updatedItem) => {
  if (updatedItem) {
    refetchKanban()
  }
}

const editTimer = (member, id, name) => {
  memberDetails.value = member
  taskId.value = id
  taskName.value = name
  isEditTimerDialogVisible.value = true
}

const deleteKanbanItemFn = async (item) => {
  if (item?.item?.id) {
    await $api(`/task/${item.item.id}`, {
      method: 'DELETE',
    })
    refetchKanban()
    isMessengerDrawerOpen.value = false
  }
}

watch(() => kanban.value, (newVal) => {
  if (newVal && initialQueryParams.value) {
    const task = newVal.boards?.flatMap(board => board.tasks)
      .find(item => item.id === parseInt(initialQueryParams.value.taskId))

    if (task) {
      selectedKanbanItem.value = {
        item: task,
        boardId: task.board_id,
        boardName: newVal.boards.find(b => b.id === task.board_id)?.name
      }
      
      nextTick(() => {
        isMessengerDrawerOpen.value = true
        initialQueryParams.value = null
      })
    }
  }
}, { immediate: true })

watch(() => route.query, (newQuery) => {
  if (newQuery.openMessenger === 'true' && newQuery.taskId) {
    initialQueryParams.value = { ...newQuery }
    
    const taskId = parseInt(newQuery.taskId)

    if (taskId) {
      const task = kanban.value.boards?.flatMap(board => board.tasks)
        .find(item => item.id === taskId)
        
      if (task) {
        selectedKanbanItem.value = {
          item: task,
          boardId: task.board_id,
          boardName: kanban.value.boards.find(b => b.id === task.board_id)?.name
        }
      }

      nextTick(() => {
        isMessengerDrawerOpen.value = true
        initialQueryParams.value = null
      })
    }
  }
},{ deep: true })
</script>

<template>
  <div class="kanban-page">
    <!-- Header Section -->
    <div class="kanban-header">
      <div 
        v-if="kanban"
        class="d-flex justify-md-space-between flex-column flex-md-row align-center mb-1 mb-md-0"
      >
        <VBreadcrumbs :items="breadcumItems">
          <template #divider>
            <VIcon>
              tabler-chevron-right
            </VIcon>
          </template>
        </VBreadcrumbs>
        <div class="d-flex gap-1 align-center">
          <VBadge
            location="top start"
            bordered
            :color="priorityFilter.length || usersFilter.length || tagsFilter.length || searchFilter ? 'primary' : ''"
          >
            <template #badge>
              <VIcon
                icon="tabler-filter"
                size="12"
              />
            </template>

            <div class="d-flex align-center gap-2 filters-container">
              <PriorityFilterDropdown
                :model-value="priorityFilter"
                @update:model-value="setPriority($event)"
              />

              <UserFilterDropdown
                :model-value="usersFilter"
                @update:model-value="setUsersFilter($event)"
              />

              <TagsFilterDropdown
                :model-value="tagsFilter"
                @update:model-value="setTagsFilter($event)"
              />

              <SearchFilterDropdown
                :model-value="searchFilter"
                @update:model-value="setSearchFilter($event)"
              />
            </div>
          </VBadge>

          <VMenu
            v-model="activeUsersMenu"
            offset-y
          >
            <template #activator="{ props }">
              <VChip
                v-bind="props"
                size="small"
                :color="kanban?.active_users?.length ? 'rgb(56, 161, 105)' : ''"
                variant="elevated"
                class="cursor-pointer"
              >
                <VIcon
                  left
                  size="16"
                >
                  tabler-hourglass
                </VIcon>
              </VChip>
            </template>

            <div class="users-box">
              <!-- Active Users -->
              <div
                v-if="kanban?.active_users?.length"
                class="users-section active-users"
              >
                <h4>Active Users</h4>
                <div
                  v-for="entry in kanbanData.active_users"
                  :key="entry.user.id"
                  class="user-item"
                >
                  <div class="d-flex flex-column">
                    <VAvatar
                      size="24"
                      class="avatar"
                    >
                      {{ entry.user.avatar_or_initials }}
                    </VAvatar>
                    <div
                      v-if="entry.user.id === userData.id"
                      class="mt-1 pl-1 mb-1"
                    >
                      <button
                        class="timer-btn"
                        :class="{
                          'timer-btn-active': entry.time_entry?.start,
                        }"
                        @click="toggleTimerFn({
                          user_id: entry.user.id,
                          task_id: entry.time_entry.task_id,
                        }, entry.time_entry.task_id)"
                      >
                        <VIcon
                          :icon="entry.time_entry?.start ? 'tabler-pause' : 'tabler-play'"
                          size="14"
                        />
                      </button>
                    </div>
                  </div>
                  <div class="user-details">
                    <span class="user-name">{{ entry.user.full_name }}</span>
                    <span class="user-time">{{ timerStore.getTimer(entry.user.id, true) || 'Loading...' }}</span>
                    <span
                      v-if="entry.time_entry"
                      class="user-task"
                    >Task #{{ entry.time_entry.task_sequence_id }}</span>
                    <span
                      v-if="!entry.has_weekly_limit"
                      class="progress-text"
                    >
                      Worked this week: {{ entry.weekly_tracked.total_display }}
                    </span>
                    <div
                      v-if="entry.has_weekly_limit"
                      class="weekly-limit"
                    >
                      <span class="weekly-limit-label">Weekly limit active</span>
                      <VProgressLinear
                        :model-value="(entry.weekly_tracked.total_seconds / (entry.weekly_limit_hours * 3600)) * 100"
                        height="4"
                        color="success"
                        class="progress-bar"
                      />
                      <span class="progress-text">
                        {{ entry.weekly_tracked.total_display }} / {{ entry.weekly_limit_hours }}h
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Inactive Users -->
              <div
                v-if="kanban?.inactive_users?.length"
                class="users-section inactive-users"
              >
                <h4>Inactive Users</h4>
                <div
                  v-for="entry in kanbanData.inactive_users"
                  :key="entry.user.id"
                  class="user-item"
                >
                  <VAvatar
                    size="24"
                    class="avatar"
                  >
                    {{ entry.user.avatar_or_initials }}
                  </VAvatar>
                  <div class="user-details">
                    <span class="user-name">{{ entry.user.full_name }}</span>
                    <span class="user-email">{{ entry.user.email }}</span>
                    <span
                      v-if="entry.last_time_entry"
                      class="user-task"
                    >
                      Last Task #{{ entry.last_time_entry.task_sequence_id }}
                      <span v-if="entry.last_time_entry.task_deleted_at">
                        (Deleted {{ formatDistanceToNow(parseISO(entry.last_time_entry.task_deleted_at)) }} ago)
                      </span>
                      <span v-if="entry.last_time_entry.end">
                        (Ended {{ formatDistanceToNow(parseISO(entry.last_time_entry.end)) }} ago)
                        {{ format(new Date(entry.last_time_entry.end), "MMM d, yyyy h:mm:ss a") }}
                      </span>
                    </span>
                    <span
                      v-if="!entry.has_weekly_limit"
                      class="progress-text"
                    >
                      Worked this week: {{ entry.weekly_tracked.total_display }}
                    </span>
                    <div
                      v-if="entry.has_weekly_limit"
                      class="weekly-limit"
                    >
                      <span class="weekly-limit-label">Weekly limit active</span>
                      <VProgressLinear
                        :model-value="(entry.weekly_tracked.total_seconds / (entry.weekly_limit_hours * 3600)) * 100"
                        height="4"
                        color="info"
                        class="progress-bar"
                      />
                      <span class="progress-text">
                        {{ entry.weekly_tracked.total_display }} / {{ entry.weekly_limit_hours }}h
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </VMenu>

          <VChip
            v-if="kanban.auth.is_super_admin || kanban.owner_id === userData.id || kanban.members.find(member => member.user.id === userData.id && member.is_admin)"
            size="small"
            variant="elevated"
            @click="isEditContainerDialogVisible = true"
          >
            <VIcon
              left
              size="16"
            >
              tabler-user-edit
            </VIcon>
          </VChip>

          <VChip
            size="small"
            variant="elevated"
            class="comments-chip"
            @click="isMessagesDialogVisible = true"
          >
            <VIcon
              left
              size="16"
            >
              tabler-message
            </VIcon>
          </VChip>

          <VChip
            size="small"
            variant="elevated"
            class="comments-chip"
            @click="isPaymentDetailsDialogVisible = true"
          >
            <VIcon
              left
              size="16"
            >
              tabler-credit-card
            </VIcon>
          </VChip>
        </div>
      </div>
    </div>

    <!-- Kanban Board Section -->
    <div class="kanban-container">
      <KanbanBoardComp
        v-if="kanban"
        ref="kanbanBoard"
        :kanban-data="kanban"
        @add-new-board="addNewBoard"
        @delete-board="openDeleteBoardDialog"
        @rename-board="renameTheBoard"
        @add-new-item="addNewItem"
        @edit-item="editItemFn"
        @delete-item="persistDelete"
        @toggle-timer="toggleTimerFn"
        @refresh-data="refetchKanban"
        @update-items-state="updateItemState"
        @update-board-state="updateBoardState"
        @open-messenger="openMessenger"
      />
    </div>

    <ConfirmDialog
      v-model:isDialogVisible="isDeleteModalVisible"
      cancel-title="Cancel"
      confirm-title="Delete!"
      confirm-msg="Task deleted permanently."
      confirmation-question="Are you sure to delete this Task?"
      cancel-msg="Delete action cancelled."
      @confirm="confirmed => confirmed && deleteItemFn()"
    />
    <div v-if="kanban">
      <GeneralMessenger
        v-model:is-drawer-open="isMessagesDialogVisible"
        :board-id="kanban.id"
        @open-board-chat="openBoardMessenger"
      />
      <PaymentDetails
        v-model:board-id="boardId"
        v-model:is-super-admin="isSuperAdmin"
        v-model:is-owner="isOwner"
        v-model:is-dialog-visible="isPaymentDetailsDialogVisible"
      />
      <AddEditBoard
        v-model:is-dialog-visible="isEditContainerDialogVisible"
        v-model:board-details="kanban"
        v-model:project-id="kanbanData.project_id"
        @form-submitted="membersEdited"
      />
    </div>
    <Messenger
      v-if="kanban"
      v-model:is-drawer-open="isMessengerDrawerOpen"
      :kanban-item="selectedKanbanItem || { item: { id: null, name: 'Board Chat' } }"
      :container-id="selectedKanbanItem?.item?.id ? null : kanban.id"
      :available-members="kanban.members"
      :is-super-admin="kanban.auth.is_super_admin"
      :is-owner="kanban.auth.is_owner"
      :is-member="kanban.auth.is_member"
      :auth-id="kanban.auth.id"
      :show-back-button="isFromGeneralMessenger"
      @update:kanban-item="handleMessengerUpdate"
      @edit-timer="editTimer"
      @delete-kanban-item="deleteKanbanItemFn"
      @refresh-kanban-data="refetchKanban"
      @back-to-general="handleBackToGeneral"
    />
    <DeleteBoardDialog
      v-if="deleteBoardDetails"
      v-model:is-dialog-visible="isDeleteBoardDialogVisible"
      :board-details="deleteBoardDetails.board"
      :available-boards="deleteBoardDetails.availableBoards"
      @confirm="deleteBoard"
      @cancel="cancelDeleteBoard"
    />
  </div>
</template>

<style lang="scss">
.kanban-page {
  background: #f8f9fa;

  .kanban-header {
    background: #ffffff;
    padding: 0.2rem 0.85rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #e1e4e8;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);

    .v-chip {
      height: 28px;
      font-size: 0.8125rem;
      background: #f6f8fa;
      border: 1px solid #d0d7de;
      color: #57606a;
      transition: all 0.2s ease;

      &:hover {
        border-color: #0969da;
        color: #0969da;
        background: #f3f4f6;
      }
    }
  }
}

.filters-container {
  background: #ffffff;
  padding: 4px;
  border-radius: 6px;
  border: 1px solid #d0d7de;
}

.filter-chip {
  cursor: pointer;
  font-size: 13px;
  transition: background 0.3s;
  &:hover {
    background: rgba(0, 0, 0, 0.05);
  }
}

.filter-icon {
  color: #6c757d;
}

.users-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f6f8fa;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.users-box {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
  min-width: 300px;
}

.users-section {
  margin-bottom: 1rem;
}

h4 {
  font-size: 14px;
  font-weight: bold;
  color: #333;
  margin-bottom: 8px;
}

.user-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  border-radius: 6px;
  transition: background 0.2s;

  &:hover {
    background: #f0f0f0;
  }
}

.user-details {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  font-size: 12px;
}

.user-name {
  font-weight: bold;
}

.user-time {
  color: #28a745;
  font-size: 12px;
}

.user-email {
  color: #777;
  font-size: 12px;
}

.user-task {
  color: #999;
  font-size: 11px;
}

.weekly-limit {
  margin-top: 6px;
}

.weekly-limit-label {
  font-size: 12px;
  font-weight: bold;
  color: #007bff;
}

.progress-bar {
  width: 100%;
  margin-top: 4px;
}

.progress-text {
  font-size: 11px;
  color: #555;
}
</style>
