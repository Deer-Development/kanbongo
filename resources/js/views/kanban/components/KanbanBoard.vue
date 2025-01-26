<script setup>
import {
  animations,
  remapNodes,
} from '@formkit/drag-and-drop'
import { dragAndDrop } from '@formkit/drag-and-drop/vue'
import { VForm } from 'vuetify/components/VForm'
import KanbanBoardEditDrawer from './KanbanBoardEditDrawer.vue'
import KanbanItems from './KanbanItems.vue'
import ConfettiExplosion from "vue-confetti-explosion"
import { useMediaQuery } from "@vueuse/core"
import { ref, defineExpose, defineProps, defineEmits, watch } from 'vue'
import EditTimerDialog from "@/views/kanban/components/dialogs/EditTimer.vue"

const props = defineProps({
  kanbanData: {
    type: null,
    required: true,
  },
  groupName: {
    type: String,
    required: false,
    default: 'kanban',
  },
})

const emit = defineEmits([
  'addNewBoard',
  'renameBoard',
  'deleteBoard',
  'addNewItem',
  'editItem',
  'deleteItem',
  'toggleTimer',
  'updateItemsState',
  'updateBoardState',
  'refreshData',
])

const colors = [
  { name: 'Red', value: '#ef5350' },
  { name: 'Pink', value: '#ec407a' },
  { name: 'Purple', value: '#ab47bc' },
  { name: 'Deep Purple', value: '#7e57c2' },
  { name: 'Blue', value: '#42a5f5' },
  { name: 'Cyan', value: '#26c6da' },
  { name: 'Teal', value: '#26a69a' },
  { name: 'Green', value: '#66bb6a' },
  { name: 'Yellow', value: '#ffee58' },
  { name: 'Amber', value: '#ffca28' },
  { name: 'Orange', value: '#ffa726' },
  { name: 'Deep Orange', value: '#ff7043' },
  { name: 'Brown', value: '#8d6e63' },
  { name: 'Blue Grey', value: '#78909c' },
]

const kanbanWrapper = ref()
const localKanbanData = ref(props.kanbanData.boards)
const hasActiveTimer = ref(props.kanbanData.auth.has_active_time_entries)
const localAvailableMembers = ref(props.kanbanData.members)
const isKanbanBoardEditVisible = ref(false)
const isAddNewFormVisible = ref(false)
const confettiVisible = ref(false)
const refAddNewBoard = ref()
const boardColor = ref('#ef5350')
const boardTitle = ref('')
const editKanbanItem = ref()
const isEditTimerDialogVisible = ref(false)
const memberDetails = ref(null)
const taskId = ref(null)
const isMobile = useMediaQuery('(max-width: 768px)')

const addNewBoard = () => {
  refAddNewBoard.value?.validate().then(valid => {
    if (valid.valid) {
      emit('addNewBoard', boardTitle.value, boardColor.value)
      isAddNewFormVisible.value = false
      boardTitle.value = ''
      boardColor.value = '#ef5350'
    }
  })
}

const deleteBoard = boardId => {
  emit('deleteBoard', boardId)
}

const refreshData = () => {
  emit('refreshData')
}

const toggleTimer = (member, taskId) => {
  emit('toggleTimer', member, taskId)
}

const editTimer = (member, id) => {
  memberDetails.value = member
  taskId.value = id
  isEditTimerDialogVisible.value = true
}

const closeDialog = () => {
  isEditTimerDialogVisible.value = false
  taskId.value = null
  memberDetails.value = null

  refreshData()
}

const renameBoard = boardName => {
  emit('renameBoard', boardName)
}

const addNewItem = item => {
  emit('addNewItem', item)
}

const editKanbanItemFn = item => {
  if (item) {
    editKanbanItem.value = item
    isKanbanBoardEditVisible.value = true
  }
}

const updateStateFn = kanbanState => {
  emit('updateItemsState', kanbanState)
}

// 👉 initialize the drag and drop
dragAndDrop({
  parent: kanbanWrapper,
  values: localKanbanData,
  dragHandle: '.drag-handler',
  disabled: isMobile.value,
  plugins: [animations()],
})

// assign the new kanban data to the local kanban data
watch(() => props, () => {
  localKanbanData.value = props.kanbanData.boards
  hasActiveTimer.value = props.kanbanData.auth.has_active_time_entries

  // 👉 remap the nodes when we rename the board: https://github.com/formkit/drag-and-drop/discussions/52#discussioncomment-8995203
  remapNodes(kanbanWrapper.value)
}, { deep: true })

const emitUpdatedTaskFn = item => {
  emit('editItem', item)
}

const deleteKanbanItemFn = item => {
  emit('deleteItem', item)
}

let initialBoardOrder = props.kanbanData.boards.map(board => board.id)

watch(localKanbanData, () => {
  const getIds = localKanbanData.value.map(board => board.id)

  const isOrderChanged = !getIds.every((id, index) => id === initialBoardOrder[index])

  if (isOrderChanged) {
    emit('updateBoardState', getIds)
    initialBoardOrder = [...getIds]
  }
}, { deep: true })

const validateBoardTitle = () => {
  return props.kanbanData.boards.some(board => boardTitle.value && board.name.toLowerCase() === boardTitle.value.toLowerCase()) ? 'Board title already exists' : true
}

const hideAddNewForm = () => {
  isAddNewFormVisible.value = false
  refAddNewBoard.value?.reset()
}

onClickOutside(refAddNewBoard, hideAddNewForm)

defineExpose({
  isKanbanBoardEditVisible,
})
</script>

<template>
  <div class="kanban-main-wrapper d-flex gap-4">
    <div
      ref="kanbanWrapper"
      class="d-flex ga-6"
    >
      <template
        v-for="kb in localKanbanData"
        :key="kb.id"
      >
        <KanbanItems
          :has-active-timer="hasActiveTimer"
          :group-name="groupName"
          :kanban-ids="kb.tasks?.map(task => task.id)"
          :board-name="kb.name"
          :board-color="kb.color"
          :board-id="kb.id"
          :kanban-items="kb.tasks"
          :kanban-data="kb"
          :is-super-admin="props.kanbanData.auth.is_super_admin"
          :is-owner="props.kanbanData.auth.is_owner"
          :is-member="props.kanbanData.auth.is_member"
          :is-mobile="isMobile"
          :auth-id="props.kanbanData.auth.id"
          :available-members="localAvailableMembers"
          :colors="colors"
          @delete-board="deleteBoard"
          @toggle-timer="toggleTimer"
          @edit-timer="editTimer"
          @rename-board="renameBoard"
          @add-new-item="addNewItem"
          @edit-item="editKanbanItemFn"
          @update-items-state="updateStateFn"
          @delete-item="deleteKanbanItemFn"
          @refresh-kanban-data="refreshData"
        />
      </template>
    </div>

    <div
      class="add-new-form text-no-wrap"
      style="inline-size: 21rem;"
    >
      <VChip
        class="mb-4"
        color="primary"
        @click="isAddNewFormVisible = !isAddNewFormVisible"
      >
        <VIcon
          size="18"
          icon="tabler-plus"
        />
        Add New
      </VChip>

      <VForm
        v-if="isAddNewFormVisible"
        ref="refAddNewBoard"
        class="mt-4"
        validate-on="submit"
        @submit.prevent="addNewBoard"
      >
        <div class="mb-4">
          <VTextField
            v-model="boardTitle"
            :rules="[requiredValidator, validateBoardTitle]"
            autofocus
            placeholder="Add Board Title"
            @keydown.esc="hideAddNewForm"
          />
        </div>
        <div class="d-flex mb-4">
          <VRadioGroup
            v-model="boardColor"
            class="d-flex gap-3"
            inline
          >
            <VRadio
              v-for="color in colors"
              :key="color.name"
              :value="color.value"
              :color="color.value"
              class="custom-radio-checkbox"
              :class="[boardColor === color.value ? 'selected' : '']"
              :style="{ color: color.value }"
              ripple
            />
          </VRadioGroup>
        </div>
        <div class="d-flex gap-3 justify-end">
          <VBtn
            size="small"
            type="submit"
          >
            Add
          </VBtn>
          <VBtn
            size="small"
            variant="tonal"
            color="secondary"
            type="reset"
            @click="hideAddNewForm"
          >
            Cancel
          </VBtn>
        </div>
      </VForm>
    </div>
  </div>

  <KanbanBoardEditDrawer
    v-model:is-drawer-open="isKanbanBoardEditVisible"
    :kanban-item="editKanbanItem"
    :available-members="localAvailableMembers"
    :is-super-admin="props.kanbanData.auth.is_super_admin"
    :has-active-timer="props.kanbanData.auth.has_active_time_entries"
    :is-owner="props.kanbanData.auth.is_owner"
    :is-member="props.kanbanData.auth.is_member"
    :auth-id="props.kanbanData.auth.id"
    @update:kanban-item="emitUpdatedTaskFn"
    @delete-kanban-item="deleteKanbanItemFn"
    @refresh-kanban-data="refreshData"
  />

  <EditTimerDialog
    v-model:is-dialog-visible="isEditTimerDialogVisible"
    v-model:member-details="memberDetails"
    v-model:task-id="taskId"
    @form-submitted="closeDialog"
  />
</template>

<style lang="scss">
@use "@styles/variables/_vuetify.scss" as vuetify;

.custom-radio-checkbox {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 2px solid transparent;
  border-radius: 8px;
  background-color: var(--v-theme-surface);
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.custom-radio-checkbox:hover {
  background-color: var(--v-theme-primary-lighten5);
  border-color: var(--v-theme-primary);
}

.custom-radio-checkbox.selected {
  background-color: var(--v-theme-primary);
  border-color: var(--v-theme-primary-darken1);
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.custom-radio-checkbox .icon {
  font-size: 18px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.custom-radio-checkbox.selected .icon {
  opacity: 1;
}

.kanban-main-wrapper {
  overflow: auto hidden;
  margin-inline-start: -0.6rem;
  min-block-size: calc(100vh - 17rem);
  max-height: calc(100vh - 10rem);
  padding-inline-start: 0.6rem;

  .kanban-board {
    overflow: auto;
    inline-size: 17.875rem;
    min-inline-size: 17.875rem;
    padding: 0 0.1rem;
    border-radius: vuetify.$border-radius-root;

    .kanban-board-header {
      position: sticky;
      top: 0;
      z-index: 10;
      padding: 0.5rem;
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
    }

    .kanban-board-drop-zone {
      overflow: auto;
      padding: 0.2rem 0 0.1rem 0.1rem;
      min-block-size: 100%;
      background-color: #fbf8f8;
      scroll-behavior: smooth;
    }
  }

  .add-new-form {
    .v-field__field {
      border-radius: vuetify.$border-radius-root;
      background-color: rgb(var(--v-theme-surface));
    }
  }
}
</style>
