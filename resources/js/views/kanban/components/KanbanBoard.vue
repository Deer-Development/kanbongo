<script setup>
import {
  animations,
  remapNodes,
} from '@formkit/drag-and-drop'
import { dragAndDrop } from '@formkit/drag-and-drop/vue'
import { VForm } from 'vuetify/components/VForm'
import KanbanItems from './KanbanItems.vue'
import { useMediaQuery } from "@vueuse/core"
import { ref, defineExpose, defineProps, defineEmits, watch, computed } from 'vue'
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
  'openMessenger',
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
const localActiveUsers = ref(props.kanbanData.active_users)
const isKanbanBoardEditVisible = ref(false)
const isAddNewFormVisible = ref(false)
const refAddNewBoard = ref()
const boardColor = ref('#ef5350')
const boardTitle = ref('')
const editKanbanItem = ref()
const isEditTimerDialogVisible = ref(false)
const memberDetails = ref(null)
const taskId = ref(null)
const taskName = ref(null)
const editDialog = ref(null)
const isMobile = useMediaQuery('(max-width: 768px)')

const isSubmitting = ref(false)
const addBoardForm = ref(null)
const isAddNewBoardFormVisible = ref(false)
const newBoardName = ref('')
const newBoardColor = ref('')
const isHovered = ref(false)

const formRules = {
  name: [
    v => !!v || 'Column name is required',
    v => (v && v.length >= 3) || 'Name must be at least 3 characters',
    v => !props.kanbanData.boards.some(board => 
      board.name.toLowerCase() === v?.toLowerCase()
    ) || 'Column name already exists'
  ],
  color: [
    v => !!v || 'Please select a color for the column'
  ]
}

// Add a computed property to check if form is valid
const isFormValid = computed(() => {
  return newBoardName.value.length >= 3 && 
         newBoardColor.value && 
         !props.kanbanData.boards.some(board => 
           board.name.toLowerCase() === newBoardName.value?.toLowerCase()
         )
})

const addNewBoard = async () => {
  try {
    const { valid } = await addBoardForm.value?.validate()
    
    if (!valid) return
    
    isSubmitting.value = true
    await emit('addNewBoard', newBoardName.value, newBoardColor.value)
    hideAddNewBoardForm()
  } catch (error) {
    console.error('Form validation failed:', error)
  } finally {
    isSubmitting.value = false
  }
}

const deleteBoard = (boardId, availableBoards) => {
  emit('deleteBoard', boardId, availableBoards)
}

const refreshData = () => {
  emit('refreshData')
}

const toggleTimer = (member, taskId) => {
  emit('toggleTimer', member, taskId)
}

const editTimer = (member, id, name) => {
  memberDetails.value = member
  taskId.value = id
  taskName.value = name
  isEditTimerDialogVisible.value = true
}

const closeDialog = () => {
  isEditTimerDialogVisible.value = false
  taskId.value = null
  memberDetails.value = null
  taskName.value = null

  refreshData()
}

const memberUnassigned = () => {
  isEditTimerDialogVisible.value = false
  taskId.value = null
  memberDetails.value = null
  taskName.value = null

  if(isKanbanBoardEditVisible.value) {
    editDialog.value.fetchKanbanItem()
  }

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
    emit('openMessenger', item)
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
  localAvailableMembers.value = props.kanbanData.members
  localActiveUsers.value = props.kanbanData.active_users

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

const hideAddNewBoardForm = () => {
  isAddNewBoardFormVisible.value = false
  newBoardName.value = ''
  newBoardColor.value = ''
  addBoardForm.value?.reset()
}

onClickOutside(refAddNewBoard, hideAddNewForm)
onClickOutside(addBoardForm, hideAddNewBoardForm)

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
          :available-boards="localKanbanData.filter(board => board.id !== kb.id).map(board => ({
            id: board.id,
            name: board.name,
            color: board.color,
          }))"
          :kanban-ids="kb.tasks?.map(task => task.id)"
          :board-name="kb.name"
          :board-color="kb.color"
          :board-id="kb.id"
          :kanban-items="kb.tasks"
          :kanban-data="kb"
          :is-super-admin="props.kanbanData.auth.is_super_admin"
          :is-owner="props.kanbanData.auth.is_owner"
          :is-member="props.kanbanData.auth.is_member"
          :is-admin="props.kanbanData.auth.is_admin"
          :is-mobile="isMobile"
          :auth="props.kanbanData.auth"
          :available-members="localAvailableMembers"
          :active-users="localActiveUsers"
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
      v-if="!isAddNewBoardFormVisible"
      class="empty-board-column"
      @click="isAddNewBoardFormVisible = true"
      @mouseenter="isHovered = true"
      @mouseleave="isHovered = false"
    >
      <div class="empty-board-content">
        <div class="icon-container">
          <span class="emoji" :class="{ 'is-hovered': isHovered }">
            {{ isHovered ? '🎯' : '🎨' }}
          </span>
          <VIcon
            icon="tabler-plus"
            size="18"
            class="plus-icon"
          />
        </div>
        <span class="add-text">Add another column</span>
        <span class="helper-text">Click to add</span>
      </div>
    </div>

    <div
      v-if="isAddNewBoardFormVisible"
      class="add-new-board-container"
    >
      <VForm
        ref="addBoardForm"
        validate-on="submit"
        @submit.prevent="addNewBoard"
      >
        <div class="form-header">
          <span class="text-subtitle-2">Add new column</span>
          <VIcon
            icon="tabler-x"
            size="16"
            class="close-icon"
            @click="hideAddNewBoardForm"
          />
        </div>

        <div class="form-body">
          <VTextField
            v-model="newBoardName"
            label="Column name"
            placeholder="Enter column name"
            variant="outlined"
            density="comfortable"
            :rules="formRules.name"
            autofocus
            class="mb-4"
            persistent-hint
            @keydown.esc="hideAddNewBoardForm"
          >
            <template #prepend-inner>
              <VIcon
                icon="tabler-layout-board"
                size="16"
                class="text-medium-emphasis"
              />
            </template>
          </VTextField>

          <div class="color-selection">
            <label class="text-subtitle-2 mb-2 d-block">
              Column color
              <span 
                v-if="!newBoardColor" 
                class="text-error text-caption ms-1"
              >
                *Required
              </span>
            </label>
            <div 
              class="color-grid"
              :class="{ 'error-border': !newBoardColor }"
            >
              <div
                v-for="color in colors"
                :key="color.name"
                class="color-option"
                :class="{ 
                  selected: newBoardColor === color.value,
                  'error-shake': !newBoardColor && isSubmitting
                }"
                :style="{ '--color': color.value }"
                @click="newBoardColor = color.value"
              >
                <div class="color-preview" />
                <VIcon
                  v-if="newBoardColor === color.value"
                  icon="tabler-check"
                  size="16"
                  color="white"
                  class="check-icon"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <VBtn
            size="small"
            color="primary"
            type="submit"
            :disabled="!newBoardName || !newBoardColor"
            prepend-icon="tabler-plus"
            :loading="isSubmitting"
          >
            Create column
          </VBtn>
          <VBtn
            size="small"
            variant="text"
            color="secondary"
            @click="hideAddNewBoardForm"
          >
            Cancel
          </VBtn>
        </div>
      </VForm>
    </div>
  </div>

  <!-- <EditTimerDialog
    v-model:is-dialog-visible="isEditTimerDialogVisible"
    v-model:member-details="memberDetails"
    v-model:task-id="taskId"
    v-model:task-name="taskName"
    @form-submitted="closeDialog"
    @unassign-member="memberUnassigned"
  /> -->
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
  margin-bottom: 0 !important;
  margin-inline-start: -0.6rem;
  min-block-size: calc(100vh - 17rem);
  max-height: calc(100vh - 10rem);
  padding-inline-start: 0.6rem;
  position: relative;

  .kanban-board {
    inline-size: 17.875rem;
    min-inline-size: 17.875rem;
    padding: 0 0.1rem;
    border-radius: vuetify.$border-radius-root;

    .kanban-board-header {
      position: sticky;
      top: 0;
      z-index: 10;
      padding: 0.5rem 0.5rem 0.5rem 1px;
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
    }

    .kanban-board-drop-zone {
      padding: 0.2rem 0.1rem 0.1rem 0.1rem;
      background-color: #fbf8f8;
      min-height: 20%;
    }
  }

  .add-new-form {
    .v-field__field {
      border-radius: vuetify.$border-radius-root;
      background-color: rgb(var(--v-theme-surface));
    }
  }
}

.add-new-board-container {
  margin: 8px;
  background: rgb(var(--v-theme-background));
  border-radius: 6px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  animation: slideDown 0.2s ease-out;
  max-width: 400px;

  &:hover {
    border-color: rgba(var(--v-theme-on-surface), 0.2);
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.12);
  }

  .form-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);

    .text-subtitle-2 {
      font-weight: 500;
      color: rgb(var(--v-theme-on-surface));
    }

    .close-icon {
      opacity: 0.6;
      cursor: pointer;
      transition: all 0.2s ease;

      &:hover {
        opacity: 1;
        transform: scale(1.1);
      }
    }
  }

  .form-body {
    padding: 16px;

    .color-selection {
      label {
        color: rgba(var(--v-theme-on-surface), 0.7);
        font-size: 0.875rem;
      }

      .color-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
        gap: 8px;
        margin-top: 8px;

        .color-option {
          position: relative;
          width: 36px;
          height: 36px;
          border-radius: 6px;
          cursor: pointer;
          transition: all 0.2s ease;
          border: 2px solid transparent;
          padding: 3px;

          &:hover {
            transform: scale(1.1);
          }

          &.selected {
            border-color: var(--color);
            transform: scale(1.1);

            .check-icon {
              opacity: 1;
              transform: translate(-50%, -50%) scale(1);
            }
          }

          .color-preview {
            width: 100%;
            height: 100%;
            border-radius: 4px;
            background-color: var(--color);
          }

          .check-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            opacity: 0;
            transition: all 0.2s ease;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
          }
        }
      }
    }
  }

  .form-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: rgba(var(--v-theme-on-surface), 0.02);
    border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
  }
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.empty-board-column {
  inline-size: 17.875rem;
  min-inline-size: 17.875rem;
  height: 120px;
  margin: 8px 0;
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;

  &::before {
    content: '';
    position: absolute;
    inset: 0;
    border: 2px dashed rgba(var(--v-theme-primary), 0.15);
    border-radius: 7px;
    opacity: 0;
    transition: opacity 0.25s ease;
  }

  &:hover {
    background: rgba(var(--v-theme-surface), 0.9);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--v-theme-on-surface), 0.08);

    &::before {
      opacity: 1;
    }

    .empty-board-content {
      transform: scale(1.02);

      .icon-container {
        background: rgba(var(--v-theme-primary), 0.08);
        transform: scale(1.1);

        .emoji {
          transform: rotate(15deg);
        }

        .plus-icon {
          color: rgb(var(--v-theme-primary));
          opacity: 1;
          transform: translate(-50%, -50%) scale(1);
        }
      }

      .add-text {
        color: rgb(var(--v-theme-primary));
      }

      .helper-text {
        opacity: 1;
        transform: translateY(0);
      }
    }
  }

  .empty-board-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);

    .icon-container {
      position: relative;
      width: 48px;
      height: 48px;
      background: rgba(var(--v-theme-on-surface), 0.04);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 12px;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);

      .emoji {
        font-size: 24px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: center;
        
        &.is-hovered {
          animation: bounce 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
      }

      .plus-icon {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 0;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgb(var(--v-theme-surface));
        border-radius: 4px;
        padding: 3px;
        box-shadow: 0 2px 4px rgba(var(--v-theme-on-surface), 0.1);
      }
    }

    .add-text {
      font-size: 0.9375rem;
      font-weight: 600;
      color: rgba(var(--v-theme-on-surface), 0.9);
      margin-bottom: 4px;
      transition: color 0.25s ease;
    }

    .helper-text {
      font-size: 0.75rem;
      color: rgba(var(--v-theme-on-surface), 0.6);
      opacity: 0;
      transform: translateY(5px);
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
  }
}

@keyframes bounce {
  0%, 100% {
    transform: scale(1) rotate(0);
  }
  50% {
    transform: scale(1.2) rotate(10deg);
  }
}

// Ajustăm poziționarea formularului când este vizibil
.add-new-board-container {
  inline-size: 17.875rem;
  min-inline-size: 17.875rem;
  margin: 8px 0;
  // ... restul stilurilor rămân la fel
}

.color-selection {
  .color-grid {
    &.error-border {
      border: 1px solid rgb(var(--v-theme-error));
      border-radius: 8px;
      padding: 4px;
    }
  }

  .color-option {
    &.error-shake {
      animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
    }
  }
}

@keyframes shake {
  10%, 90% {
    transform: translate3d(-1px, 0, 0);
  }
  20%, 80% {
    transform: translate3d(2px, 0, 0);
  }
  30%, 50%, 70% {
    transform: translate3d(-2px, 0, 0);
  }
  40%, 60% {
    transform: translate3d(2px, 0, 0);
  }
}

// Add styles for disabled button
.v-btn.v-btn--disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
