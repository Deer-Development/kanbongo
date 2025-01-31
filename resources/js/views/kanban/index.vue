<script setup>
import KanbanBoardComp from './components/KanbanBoard.vue'
import { differenceInSeconds, format, formatDistanceToNow, parse, parseISO } from 'date-fns'
import { watch } from "vue"
import PaymentDetails from "@/views/projects/dialogs/PaymentDetails.vue"
import AddEditBoard from "@/views/projects/dialogs/AddEditBoard.vue"

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

const updateUserTimers = () => {
  kanbanData.value?.active_users?.forEach(user => {
    if (user.active_time_entry?.start) {
      if (!userTimers[user.id]) {
        userTimers[user.id] = calculateTrackedTime(user.active_time_entry.start)

        const intervalId = setInterval(() => {
          userTimers[user.id] = calculateTrackedTime(user.active_time_entry.start)
        }, 1000)

        user.intervalId = intervalId
      }
    }
  })
}

const clearUserTimers = () => {
  kanbanData.value?.active_users?.forEach(user => {
    if (user.intervalId) {
      clearInterval(user.intervalId)
      user.intervalId = null
    }
  })
  Object.keys(userTimers).forEach(key => delete userTimers[key])
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

onMounted(() => {
  if (kanbanData.value?.active_users) {
    updateUserTimers()
  }
})

onBeforeUnmount(() => {
  clearUserTimers()
})
</script>

<template>
  <section>
    <div
      v-if="kanban"
      class="d-flex justify-space-between align-center mb-2"
    >
      <h4 class="text-h4 mb-1">
        <VChip color="primary">
          Board: {{ kanban?.name }}
        </VChip>
        <VChip
          color="warning"
          class="ml-2"
        >
          <span class="font-weight-bold mr-1"> Owner: </span> {{ kanban?.owner?.full_name }}
        </VChip>
      </h4>
      <div class="d-flex gap-1 align-content-center">
        <VMenu
          v-if="kanban?.active_users?.length"
          v-model="activeUsersMenu"
          offset-y
        >
          <template #activator="{ props }">
            <VChip
              v-bind="props"
              size="small"
              color="rgb(56, 161, 105)"
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
          <div class="p-4">
            <div v-if="kanban?.active_users?.length">
              <div
                v-for="user in kanbanData.active_users"
                :key="user.id"
                class="d-flex flex-column align-items-start mb-3 bg-success px-2 py-1 rounded"
              >
                <div class="font-weight-bold">
                  {{ user.full_name }}
                </div>
                <div class="text-sm text-muted">
                  Timer: {{ userTimers[user.id] || 'Loading...' }}
                </div>
              </div>
            </div>
            <div v-else>
              <p>No active users found.</p>
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
