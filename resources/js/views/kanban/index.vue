<script setup>
import KanbanBoardComp from './components/KanbanBoard.vue'
import { differenceInSeconds, format, formatDistanceToNow, parse, parseISO } from 'date-fns'
import { watch } from "vue"
import PaymentDetails from "@/views/projects/dialogs/PaymentDetails.vue"
import AddEditBoard from "@/views/projects/dialogs/AddEditBoard.vue"
import { useToast } from "vue-toastification"

const route = useRoute()
const isDeleteModalVisible = ref(false)
const isPaymentDetailsDialogVisible = ref(false)
const isEditContainerDialogVisible = ref(false)
const activeUsersMenu = ref(false)
const deleteItem = ref(null)
const kanbanBoard = ref(null)
const userData = computed(() => useCookie('userData', { default: null }).value)
const userTimers = reactive({})

const {
  data: kanban,
  execute: refetchKanban,
} = useApi(createUrl(`/container/${ route.params.containerId }`, {
  method: 'GET',
}))

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

const deleteBoard = async boardId => {
  await $api(`/apps/kanban/board/${ boardId }`, { method: 'DELETE' })
  refetchKanban()
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
  console.log(editItem)

  // await $api('/apps/kanban/item/update', {
  //   method: 'PUT',
  //   body: editItem,
  // })
  // refetchKanban()
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

const calculateTrackedTime = start => {
  try {
    const startDate = parseISO(start)
    const now = new Date()
    const seconds = differenceInSeconds(now, startDate)

    if (seconds < 0) return 'Invalid Time'

    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const remainingSeconds = seconds % 60

    return `${hours}h ${minutes}m ${remainingSeconds}s`
  } catch (error) {

    return 'Error calculating time'
  }
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

  console.log('Remaining seconds:', remainingSeconds)
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

    // Ștergem orice interval anterior
    if (userTimers[entry.user.id]?.intervalId) {
      clearInterval(userTimers[entry.user.id].intervalId)
    }

    // Calculăm timpul inițial
    userTimers[entry.user.id] = {
      time: calculateTrackedTime(entry.time_entry.start),
      intervalId: null,
    }

    // Creăm noul interval
    const intervalId = setInterval(() => {
      userTimers[entry.user.id].time = calculateTrackedTime(entry.time_entry.start)
      checkWeeklyLimitAndToggle(entry)
    }, 1000)

    // Salvăm intervalId
    userTimers[entry.user.id].intervalId = intervalId
  })
}


const clearUserTimers = () => {
  Object.keys(userTimers).forEach(userId => {
    if (userTimers[userId]?.intervalId) {
      clearInterval(userTimers[userId].intervalId)
    }
    delete userTimers[userId]
    delete userToggleStatus[userId]
  })
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

onBeforeUnmount(() => {
  clearUserTimers()
})
</script>

<template>
  <section>
    <div
      v-if="kanban"
      class="d-flex justify-space-between align-center"
    >
      <VBreadcrumbs :items="breadcumItems">
        <template #divider>
          <VIcon>
            tabler-chevron-right
          </VIcon>
        </template>
      </VBreadcrumbs>
      <div class="d-flex gap-1 align-content-center">
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
                  <VAvatar size="24" class="avatar">
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
                  <span class="user-time">{{ userTimers[entry.user.id]?.time || 'Loading...' }}</span>
                  <span
                    v-if="entry.time_entry"
                    class="user-task"
                  >Task #{{ entry.time_entry.task_id }}</span>
                  <span v-if="!entry.has_weekly_limit" class="progress-text">
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
                <VAvatar size="24" class="avatar">
                  {{ entry.user.avatar_or_initials }}
                </VAvatar>
                <div class="user-details">
                  <span class="user-name">{{ entry.user.full_name }}</span>
                  <span class="user-email">{{ entry.user.email }}</span>
                  <span
                    v-if="entry.last_time_entry"
                    class="user-task"
                  >
                    Last Task #{{ entry.last_time_entry.task_id }}
                    <span v-if="entry.last_time_entry.end">
                      (Ended {{ formatDistanceToNow(parseISO(entry.last_time_entry.end)) }} ago)
                      {{ format(new Date(entry.last_time_entry.end), "MMM d, yyyy h:mm:ss a") }}
                    </span>
                  </span>
                  <span v-if="!entry.has_weekly_limit" class="progress-text">
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
          v-if="kanban.auth.is_super_admin || kanban.owner_id === userData.id"
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
    <KanbanBoardComp
      v-if="kanban"
      ref="kanbanBoard"
      :kanban-data="kanban"
      @add-new-board="addNewBoard"
      @delete-board="deleteBoard"
      @rename-board="renameTheBoard"
      @add-new-item="addNewItem"
      @edit-item="editItemFn"
      @delete-item="persistDelete"
      @toggle-timer="toggleTimerFn"
      @refresh-data="refetchKanban"
      @update-items-state="updateItemState"
      @update-board-state="updateBoardState"
    />
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
  </section>
</template>

<style lang="scss" scoped>
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
