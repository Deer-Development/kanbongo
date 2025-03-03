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
import ActivityDrawer from './components/ActivityDrawer.vue'

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
const isDeleteBoardDialogVisible = ref(false)
const deleteBoardDetails = ref(null)
const isFromGeneralMessenger = ref(false)
const progressUpdateInterval = ref(null)
const toast = useToast()
const isActivityDrawerOpen = ref(false)

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
  // Verificăm dacă există deja un request în curs pentru acest utilizator și task
  // Dar permitem requesturile pentru oprirea automată a timer-ului
  const isAutoStop = memberData.stopped_by_system === true
  
  if (!isAutoStop && timerStore.isRequestPending(memberData.user_id, taskId)) {
    console.log('Request already pending for this user and task')
    return
  }
  
  // Resetăm starea utilizatorului în store înainte de a face cererea
  if (memberData.user_id) {
    timerStore.resetUserState(memberData.user_id)
  }
  
  // Marcăm requestul ca fiind în curs
  timerStore.markRequestPending(memberData.user_id, taskId)
  
  try {
    const res = await $api(`/task/toggle-timer/${taskId}`, {
      method: 'POST',
      body: memberData,
    })

    if(res) {
      // Afișăm notificarea corespunzătoare în funcție de acțiune
      if (res.data.action === 'started') {
        toast.success(`Timer started for task #${res.data.task_sequence_id}`)
      } else if (res.data.action === 'stopped') {
        // Verificăm dacă a fost oprit de sistem sau manual
        if (memberData.stopped_by_system) {
          // Notificarea pentru oprirea de către sistem este deja afișată în checkWeeklyLimit
        } else {
          toast.info(`Timer stopped for task #${res.data.task_sequence_id}`)
        }
      }
      
      refetchKanban()
    }
  } catch (error) {
    console.error('Error toggling timer:', error)
  } finally {
    // Marcăm requestul ca fiind finalizat, indiferent de rezultat
    timerStore.markRequestCompleted(memberData.user_id, taskId)
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

const checkWeeklyLimitAndToggle = entry => {
  if (entry.user.id !== userData.value.id) return
  
  try {
    if (typeof timerStore.checkWeeklyLimit === 'function') {
      timerStore.checkWeeklyLimit(entry, toggleTimerFn)
      console.log('checkWeeklyLimit is a function in timerStore', timerStore)
    } else {
      console.error('checkWeeklyLimit is not a function in timerStore', timerStore)
      
      if (entry.has_weekly_limit && entry.time_entry?.start) {
        const startDate = parseISO(entry.time_entry.start)
        const now = new Date()
        const elapsedSeconds = differenceInSeconds(now, startDate)
        
        const totalTracked = entry.weekly_tracked.total_seconds + elapsedSeconds
        const remainingSeconds = entry.weekly_limit_seconds - totalTracked
        
        if (remainingSeconds <= 0) {
          toggleTimerFn({
            user_id: entry.user.id,
            task_id: entry.time_entry.task_id,
            stopped_by_system: true,
          }, entry.time_entry.task_id)
          console.log('toggleTimerFn called for auto stop')
        } else if (remainingSeconds > 0) {
          setTimeout(() => {
            toggleTimerFn({
              user_id: entry.user.id,
              task_id: entry.time_entry.task_id,
              stopped_by_system: true,
            }, entry.time_entry.task_id)
            console.log('toggleTimerFn called for auto stop with remaining seconds', remainingSeconds)
          }, remainingSeconds * 1000)
        }
      }
    }
  } catch (error) {
    console.error('Error in checkWeeklyLimitAndToggle:', error)
  }
}

const updateUserTimers = () => {
  try {
    kanbanData.value?.active_users?.forEach(entry => {
      if (!entry.time_entry?.start) {
        return
      }

      // Inițializează progress bar-ul pentru utilizatorul activ
      if (entry.has_weekly_limit) {
        timerStore.initWeeklyProgress(entry.user.id, entry)
      }

      timerStore.startTimer(entry.user.id, entry.time_entry, true)
      
      if (entry.user.id === userData.value.id) {
        if (typeof timerStore.checkWeeklyLimit === 'function') {
          timerStore.checkWeeklyLimit(entry, toggleTimerFn)
          console.log('checkWeeklyLimit is a function in timerStore', timerStore)
        } else {
          checkWeeklyLimitAndToggle(entry)
          console.log('checkWeeklyLimit is not a function in timerStore', timerStore)
        }
      }
    })
  } catch (error) {
    console.error('Error in updateUserTimers:', error)
  }
}

const clearUserTimers = () => {
  timerStore.clearAllTimers(true)
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

const startProgressUpdates = () => {
  // Oprim orice interval existent
  if (progressUpdateInterval.value) {
    clearInterval(progressUpdateInterval.value)
  }
  
  // Creăm un nou interval care actualizează progress bar-urile la fiecare secundă
  progressUpdateInterval.value = setInterval(() => {
    if (activeUsersMenu.value && kanbanData.value?.active_users) {
      kanbanData.value.active_users.forEach(entry => {
        if (entry.has_weekly_limit && entry.time_entry?.start) {
          // Forțăm o actualizare a progress bar-ului
          const progress = timerStore.getWeeklyProgress(entry.user.id)
          if (progress) {
            // Actualizăm direct valorile reactive
            const startDate = parseISO(entry.time_entry.start)
            const now = new Date()
            const elapsedSeconds = differenceInSeconds(now, startDate)
            timerStore.updateWeeklyProgress(entry.user.id, elapsedSeconds)
          }
        }
      })
    }
  }, 1000)
}

watch(
  () => activeUsersMenu.value,
  isOpen => {
    if (isOpen) {
      updateUserTimers()
      startProgressUpdates() // Pornim actualizarea progress bar-urilor
    } else {
      clearUserTimers()
      // Oprim actualizarea progress bar-urilor
      if (progressUpdateInterval.value) {
        clearInterval(progressUpdateInterval.value)
        progressUpdateInterval.value = null
      }
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
  if (progressUpdateInterval.value) {
    clearInterval(progressUpdateInterval.value)
    progressUpdateInterval.value = null
  }
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

const getWeeklyProgressPercent = (entry) => {
  if (!entry.has_weekly_limit) return 0
  
  if (entry.time_entry?.start) {
    const progress = timerStore.getWeeklyProgress(entry.user.id)
    if (progress) {
      return progress.percentValue
    }
  }
  
  // Fallback la calculul static
  return (entry.weekly_tracked.total_seconds / (entry.weekly_limit_hours * 3600)) * 100
}

const getWeeklyProgressDisplay = (entry) => {
  if (!entry.has_weekly_limit) return entry.weekly_tracked.total_display
  
  if (entry.time_entry?.start) {
    const progress = timerStore.getWeeklyProgress(entry.user.id)
    if (progress) {
      return progress.displayValue
    }
  }
  
  // Formatăm manual pentru utilizatorii inactivi sau când nu avem progress în store
  const hours = Math.floor(entry.weekly_tracked.total_seconds / 3600)
  const minutes = Math.floor((entry.weekly_tracked.total_seconds % 3600) / 60)
  
  // Formatăm limita cu o precizie de 1 zecimală pentru limitele mici
  const limitDisplay = entry.weekly_limit_hours < 1 ? 
    `${(entry.weekly_limit_hours * 60).toFixed(0)}m` : 
    `${entry.weekly_limit_hours}h`
  
  return `${hours}h ${minutes}m / ${limitDisplay}`
}
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

          <VMenu
            v-model="activeUsersMenu"
            offset-y
          >
            <template #activator="{ props }">
              <button
                v-bind="props"
                variant="elevated"
                class="cursor-pointer github-style-badge"
              >
                <VIcon
                  left
                  size="16"
                  :color="kanban?.active_users?.length ? 'rgb(56, 161, 105)' : ''"
                >
                  {{ kanban?.active_users?.length ? 'tabler-hourglass' : 'tabler-hourglass-empty' }}
                </VIcon>
              </button>
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
                        :model-value="getWeeklyProgressPercent(entry)"
                        height="4"
                        color="success"
                        class="progress-bar"
                      />
                      <span class="progress-text">
                        {{ getWeeklyProgressDisplay(entry) }}
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
                        :model-value="getWeeklyProgressPercent(entry)"
                        height="4"
                        color="success"
                        class="progress-bar"
                      />
                      <span class="progress-text">
                        {{ getWeeklyProgressDisplay(entry) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </VMenu>

          <button
            v-if="kanban.auth.is_super_admin || kanban.owner_id === userData.id || kanban.members.find(member => member.user.id === userData.id && member.is_admin)"
            variant="elevated"
            class="cursor-pointer github-style-badge"
            @click="isEditContainerDialogVisible = true"
          >
            <VIcon
              left
              size="16"
              color="primary"
            >
              tabler-user-edit
            </VIcon>
          </button>

          <button
            variant="elevated"
            class="cursor-pointer github-style-badge"
            @click="isMessagesDialogVisible = true"
          >
            <VIcon
              left
              size="16"
              color="warning"
            >
              tabler-message
            </VIcon>
          </button>

          <button
            variant="elevated"
            class="cursor-pointer github-style-badge"
            @click="isPaymentDetailsDialogVisible = true"
          >
            <VIcon
              left
              size="16"
              color="info"
            >
              tabler-credit-card
            </VIcon>
          </button>

          <button
            variant="elevated"
            class="cursor-pointer github-style-badge"
            @click="isActivityDrawerOpen = true"
          >
            <VIcon
              left
              size="16"
              color="success"
            >
              tabler-activity
            </VIcon>
          </button>
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
    <ActivityDrawer
      v-if="kanban"
      v-model:is-drawer-open="isActivityDrawerOpen"
      :board-id="kanban.id"
      @refresh-kanban-data="refetchKanban"
    />
  </div>
</template>

<style lang="scss">
.kanban-page {
  background: #f8f9fa;

  .kanban-header {
    background: #ffffff;
    padding-right: 0.55rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #e1e4e8;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    
    .kanban-header-content {
      display: flex;
      flex-direction: column;
      padding: 0.5rem 1rem;
      
      @media (min-width: 768px) {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
      }
    }
    
    .kanban-breadcrumbs {
      flex-grow: 1;
      overflow-x: auto;
      white-space: nowrap;
      
      .breadcrumbs-container {
        padding: 0.25rem 0;
      }
      
      .v-breadcrumbs-item {
        font-size: 0.875rem;
      }
    }
    
    .kanban-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-top: 0.5rem;
      
      @media (min-width: 768px) {
        margin-top: 0;
        justify-content: flex-end;
      }
    }
    
    // .filters-container {
    //   display: flex;
    //   flex-wrap: wrap;
    //   align-items: center;
    //   gap: 0.25rem;
    //   // background: #f6f8fa;
    //   border-radius: 6px;
    //   border: 1px solid #d0d7de;
    //   overflow: hidden;

    // }
    
    .filter-icon {
      color: #57606a;
      position: absolute;
      left: 8px;
      top: 50%;
      transform: translateY(-50%);
      opacity: 0.7;
      pointer-events: none; /* Make it non-interactive */
    }

    /* Force filter components to match height */
    :deep(.filters-container .v-input) {
      height: 24px !important;
      min-height: 24px !important;
    }

    :deep(.filters-container .v-field) {
      height: 24px !important;
      min-height: 24px !important;
      padding-top: 0 !important;
      padding-bottom: 0 !important;
    }

    :deep(.filters-container .v-select__selection) {
      margin-top: 0 !important;
      margin-bottom: 0 !important;
    }

    :deep(.filters-container .v-field__input) {
      padding-top: 0 !important;
      padding-bottom: 0 !important;
      min-height: 24px !important;
    }
    
    .action-btn {
      height: 32px;
      font-size: 0.8125rem;
      color: #24292f;
      background: #f6f8fa;
      border: 1px solid #d0d7de;
      border-radius: 6px;
      padding: 0 0.75rem;
      font-weight: 500;
      letter-spacing: 0;
      text-transform: none;
      transition: all 0.2s ease;
      
      &:hover {
        background: #f3f4f6;
        border-color: #bbb;
        color: #0969da; /* GitHub blue on hover */
      }
      
      &:active {
        background: #ebecf0;
        border-color: #aaa;
      }
      
      &.active-users-btn {
        color: #1a7f37;
        
        &:hover {
          color: #116329; /* Darker green on hover */
        }
      }
      
      /* GitHub-style icon alignment */
      :deep(.v-btn__prepend) {
        margin-right: 4px;
        opacity: 0.8;
      }
    }
    
    .active-count {
      font-size: 0.75rem;
      height: 18px;
      min-width: 18px;
      padding: 0 4px;
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
