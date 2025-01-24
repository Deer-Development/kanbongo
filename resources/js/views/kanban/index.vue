<script setup>
import KanbanBoardComp from './components/KanbanBoard.vue'

const route = useRoute()
const isDeleteModalVisible = ref(false)
const deleteItem = ref(null)
const kanbanBoard = ref(null)

const {
  data: kanban,
  execute: refetchKanban,
} = useApi(createUrl(`/container/${ route.params.containerId }`, {
  method: 'GET',
}))

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
  await $api(`/task/toggle-timer/${taskId}`, {
    method: 'POST',
    body: memberData,
  })
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

const persistDelete = item => {
  deleteItem.value = item

  console.log(deleteItem.value)
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
</script>

<template>
  <section>
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
  </section>
</template>
