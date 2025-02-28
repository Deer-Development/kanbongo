<script setup>
import {
  animations,
  handleEnd,
  performTransfer, remapNodes,
} from '@formkit/drag-and-drop'
import { dragAndDrop } from '@formkit/drag-and-drop/vue'
import { VForm } from 'vuetify/components/VForm'
import KanbanCard from './KanbanCard.vue'
import { ref, watch } from "vue"

const props = defineProps({
  kanbanIds: {
    type: Array,
    required: true,
  },
  availableBoards: {
    type: Array,
    required: false,
  },
  colors: {
    type: Array,
    required: false,
  },
  groupName: {
    type: String,
    required: true,
  },
  boardName: {
    type: String,
    required: true,
  },
  boardColor: {
    type: String,
    required: false,
  },
  boardId: {
    type: Number,
    required: true,
  },
  kanbanData: {
    type: null,
    required: true,
  },
  availableMembers: {
    type: Array,
    required: false,
    default: () => [],
  },
  activeUsers: {
    type: Array,
    required: false,
    default: () => [],
  },
  isSuperAdmin: { type: Boolean, required: false, default: false },
  isMobile: { type: Boolean, required: false, default: false },
  hasActiveTimer: { type: Boolean, required: false, default: false },
  isOwner: { type: Boolean, required: false, default: false },
  isMember: { type: Boolean, required: false, default: false },
  isAdmin: { type: Boolean, required: false, default: false },
  auth: {
    type: Object,
    required: false,
    default: () => ({}),
  },
})

const emit = defineEmits([
  'renameBoard',
  'deleteBoard',
  'addNewItem',
  'editItem',
  'updateItemsState',
  'deleteItem',
  'toggleTimer',
  'editTimer',
  'refreshKanbanData',
])

const refKanbanBoard = ref()
const localKanbanData = ref(props.kanbanData.tasks)
const localBoardName = ref(props.boardName)
const localBoardColor = ref(props.boardColor)
const localAvailableBoards = ref(props.availableBoards)
const localAvailableMembers = ref(props.availableMembers)
const localActiveUsers = ref(props.activeUsers)
const localAuthDetails = ref(props.auth)
const isAddNewFormVisible = ref(false)
const isBoardNameEditing = ref(false)
const refForm = ref()
const newTaskTitle = ref('')
const refKanbanBoardTitle = ref()

const itemsContainer = ref()

const scrollToBottomInChatLog = () => {
  const scrollEl = itemsContainer.value.$el || itemsContainer.value

  scrollEl.scrollTop = scrollEl.scrollHeight
}

const renameBoard = () => {
  refKanbanBoardTitle.value?.validate().then(valid => {
    if (valid.valid) {
      emit('renameBoard', {
        oldName: props.boardName,
        name: localBoardName.value,
        oldColor: props.boardColor,
        color: localBoardColor.value,
        boardId: props.boardId,
      })
      isBoardNameEditing.value = false
    }
  })
}

const isSubmitting = ref(false)

const addNewItem = async () => {
  try {
    isSubmitting.value = true
    await refForm.value?.validate()
    
    const item = {
      name: newTaskTitle.value,
      boardId: props.boardId,
    }

    await emit('addNewItem', item)
    newTaskTitle.value = ''
    isAddNewFormVisible.value = false
  } finally {
    isSubmitting.value = false
  }
}

// 👉 watch kanbanIds its is useful when you add new task
watch(() => props, () => {
  localKanbanData.value = props.kanbanData.tasks
}, {
  deep: true,
})

watch(() => props.availableMembers, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    localAvailableMembers.value = [...props.availableMembers]
  }
}, { deep: true, immediate: true })

watch(() => props.activeUsers, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    localActiveUsers.value = [...props.activeUsers]
  }
}, { deep: true, immediate: true })

watch(() => props.auth, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    localAuthDetails.value = props.auth
  }
}, { deep: true, immediate: true })

watch(() => props.availableBoards, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    localAvailableBoards.value = [...props.availableBoards]
  }
}, { deep: true, immediate: true })

dragAndDrop({
  parent: refKanbanBoard,
  values: localKanbanData,
  group: props.groupName,
  draggable: child => child.classList.contains('kanban-card'),
  dragHandle: '.card-handler',
  disabled: props.isMobile,
  plugins: [animations()],
  performTransfer: (state, data) => {
    performTransfer(state, data)

    // emit('updateItemsState', {
    //   boardId: props.boardId,
    //   ids: localKanbanData.value.map(task => task.id),
    // })
  },
  handleEnd: data => {
    handleEnd(data)

    emit('updateItemsState', {
      boardId: props.boardId,
      ids: localKanbanData.value.map(task => task.id),
    })
  },
})

const resolveItemUsingId = id => localKanbanData.value.find(item => item.id === id)

const deleteItem = item => {
  emit('deleteItem', item)
}

const deleteBoard = boardId => {
  emit('deleteBoard', boardId, localAvailableBoards.value)
}

const toggleTimer = (member, taskId) => {
  emit('toggleTimer', member, taskId)
}

const editTimerFn = (member, taskId, taskName) => {
  emit('editTimer', member, taskId, taskName)
}

// 👉 reset add new item form when esc or close
const hideAddNewForm = () => {
  isAddNewFormVisible.value = false
  refForm.value?.reset()
}

// close add new item form when you loose focus from the form
onClickOutside(refForm, hideAddNewForm)

// close board name form when you loose focus from the form
onClickOutside(refKanbanBoardTitle, () => {
  isBoardNameEditing.value = false
})

// 👉 reset board rename form when esc or close
const hideResetBoardNameForm = () => {
  isBoardNameEditing.value = false
  localBoardName.value = props.boardName
  localBoardColor.value = props.boardColor
}

const handleEnterKeydown = event => {
  if (event.key === 'Enter' && !event.shiftKey)
    addNewItem()
}

const refreshData = () => {
  emit('refreshKanbanData')
}
</script>

<template>
  <div class="kanban-board d-flex flex-column h-100">
    <div
      class="kanban-board-header"
      :style="{
        backgroundColor: props.boardColor && !isBoardNameEditing
          ? `${props.boardColor}93`
          : '#fff'
      }"
    >
      <VForm
        v-if="isBoardNameEditing"
        ref="refKanbanBoardTitle"
        class="board-edit-form"
        @submit.prevent="renameBoard"
      >
        <div class="edit-form-content">
          <div class="edit-header">
            <span class="text-subtitle-2">Edit column</span>
            <VIcon
              icon="tabler-x"
              size="16"
              class="close-icon"
              @click="hideResetBoardNameForm"
            />
          </div>

          <div class="edit-body">
            <VTextField
              v-model="localBoardName"
              label="Column name"
              placeholder="Enter column name"
              variant="outlined"
              density="comfortable"
              :rules="[requiredValidator]"
              autofocus
              class="mb-4"
              hide-details="auto"
              @keydown.esc="hideResetBoardNameForm"
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
              <label class="text-subtitle-2 mb-2 d-block">Column color</label>
              <div class="color-grid">
                <div
                  v-for="color in props.colors"
                  :key="color.name"
                  class="color-option"
                  :class="{ selected: localBoardColor === color.value }"
                  :style="{ '--color': color.value }"
                  @click="localBoardColor = color.value"
                >
                  <div class="color-preview" />
                  <VIcon
                    v-if="localBoardColor === color.value"
                    icon="tabler-check"
                    size="16"
                    color="white"
                    class="check-icon"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="edit-actions">
            <VBtn
              size="small"
              color="primary"
              type="submit"
              prepend-icon="tabler-check"
            >
              Save changes
            </VBtn>
            <VBtn
              size="small"
              variant="text"
              color="secondary"
              @click="hideResetBoardNameForm"
            >
              Cancel
            </VBtn>
          </div>
        </div>
      </VForm>

      <div
        v-else
        class="d-flex align-center justify-space-between"
      >
        <div class="d-flex align-center">
          <VIcon
            class="drag-handler"
            size="20"
            icon="tabler-grip-vertical"
          />
          <VChip
            class="text-md font-weight-medium text-truncate text-center"
            :color="props.boardColor"
            size="small"
            variant="elevated"
            pill
            prepend-icon="tabler-layout-kanban"
          >
            {{ boardName }}
          </VChip>
          <VChip
            v-if="localKanbanData.length"
            class="text-md font-weight-medium text-truncate text-center ml-1"
            :color="props.boardColor"
            rounded
            size="small"
            variant="elevated"
          >
            {{ localKanbanData.length }}
          </VChip>
        </div>
        <div class="d-flex align-center gap-2">
          <VIcon
            v-tooltip="'Add New Task'"
            class="text-high-emphasis"
            size="20"
            icon="tabler-square-rounded-plus"
            @click="isAddNewFormVisible = !isAddNewFormVisible"
          />
          <VMenu>
            <template #activator="{ props }">
              <VIcon
                v-bind="props"
                v-tooltip="'Actions'"
                class="text-high-emphasis cursor-pointer"
                size="16"
                icon="tabler-dots-vertical"
              />
            </template>

            <VList density="compact">
              <VListItem
                @click="isBoardNameEditing = true"
              >
                <template #prepend>
                  <VIcon
                    size="20"
                    icon="tabler-edit"
                    class="me-2"
                  />
                </template>
                <VListItemTitle>Edit</VListItemTitle>
              </VListItem>

              <VDivider />

              <VListItem
                v-if="isSuperAdmin || isOwner || isAdmin"
                color="error"
                @click="deleteBoard(boardId)"
              >
                <template #prepend>
                  <VIcon
                    size="20"
                    icon="tabler-trash"
                    color="error"
                    class="me-2"
                  />
                </template>
                <VListItemTitle>Delete</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>
        </div>
      </div>

      <div 
        v-if="isAddNewFormVisible"
        class="add-new-form-container"
      >
        <VForm
          ref="refForm"
          validate-on="submit"
          @submit.prevent="addNewItem"
        >
          <div class="form-header">
            <span class="text-subtitle-2">Add new task</span>
            <VIcon
              icon="tabler-x"
              size="16"
              class="close-icon"
              @click="hideAddNewForm"
            />
          </div>

          <VTextarea
            v-model="newTaskTitle"
            :rules="[requiredValidator]"
            placeholder="Enter task description..."
            variant="outlined"
            density="comfortable"
            rows="3"
            hide-details
            class="task-textarea"
            autofocus
            @keydown.enter="handleEnterKeydown"
            @keydown.esc="hideAddNewForm"
          >
            <template #prepend-inner>
              <VIcon
                icon="tabler-file-text"
                size="16"
                class="text-medium-emphasis"
              />
            </template>
          </VTextarea>

          <div class="form-actions">
            <VBtn
              size="small"
              color="primary"
              type="submit"
              prepend-icon="tabler-plus"
              :loading="isSubmitting"
            >
              Create task
            </VBtn>
            <VBtn
              size="small"
              variant="text"
              color="secondary"
              @click="hideAddNewForm"
            >
              Cancel
            </VBtn>
          </div>
        </VForm>
      </div>
    </div>
    <div
      ref="itemsContainer"
      style="height: 100%; overflow-y: auto;"
      class="flex-grow-1"
    >
      <div
        v-if="localKanbanData"
        ref="refKanbanBoard"
        class="kanban-board-drop-zone d-flex flex-column gap-2"
        :style="{
          backgroundColor: props.boardColor
            ? `${props.boardColor}33`
            : '#f1f1f3'
        }"
        :class="localKanbanData.length ? 'mb-4' : ''"
      >
        <template
          v-for="item in localKanbanData"
          :key="item.id"
        >
          <KanbanCard
            :item="item"
            :board-id="props.boardId"
            :board-name="props.boardName"
            :is-super-admin="props.isSuperAdmin"
            :has-active-timer="props.hasActiveTimer"
            :is-owner="props.isOwner"
            :is-member="props.isMember"
            :is-admin="props.isAdmin"
            :auth="localAuthDetails"
            :available-members="localAvailableMembers"
            :active-users="localActiveUsers"
            @delete-kanban-item="deleteItem"
            @toggle-timer="toggleTimer"
            @edit-timer="editTimerFn"
            @refresh-kanban-data="refreshData"
            @edit-kanban-item="emit('editItem', { item: item, boardId: props.boardId, boardName: props.boardName })"
          />
        </template>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
.kanban-board-header {
  .drag-handler {
    cursor: grab;
    opacity: 0.6;
    transition: opacity 0.2s ease;

    &:active {
      cursor: grabbing;
    }

    &:hover {
      opacity: 1;
    }
  }
}

.dragging {
  transform: scale(1.05);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border-radius: 8px;
}

.drop-zone:hover,
.drop-zone.over {
  background-color: rgba(0, 128, 255, 0.15);
  border: 2px dashed rgba(0, 128, 255, 0.4);
  transition: background-color 0.3s ease, border 0.3s ease;
}

.kanban-board .flex-grow-1 {
  scrollbar-width: thin;
  scrollbar-color: rgba(191, 191, 191, 0.5) rgba(240, 240, 240, 0.5);
}

.kanban-board .flex-grow-1::-webkit-scrollbar {
  width: 8px;
  border-radius: 10px;
}

.kanban-board .flex-grow-1::-webkit-scrollbar-track {
  background: rgba(191, 191, 191, 0.5);
  border-radius: 10px;
}

.kanban-board .flex-grow-1::-webkit-scrollbar-thumb {
  background: rgba(191, 191, 191, 0.5);
  border-radius: 10px;
  transition: background 0.3s ease;
}

.kanban-board .flex-grow-1::-webkit-scrollbar-thumb:hover {
  background: rgba(191, 191, 191, 0.5);
}

.add-new-form-container {
  margin-top: 8px;
  margin-left: 8px;
  background: rgb(var(--v-theme-background));
  border-radius: 6px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;

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

  .task-textarea {
    padding: 12px 16px;

    :deep(.v-field) {
      border-radius: 4px;
      background: rgb(var(--v-theme-surface));
      box-shadow: none;
      transition: all 0.2s ease;

      &:hover {
        border-color: rgba(var(--v-theme-primary), 0.5);
      }

      &.v-field--focused {
        border-color: rgb(var(--v-theme-primary));
        box-shadow: 0 0 0 1px rgb(var(--v-theme-primary));
      }

      .v-field__input {
        padding-top: 0;
        min-height: auto;
        font-size: 0.875rem;
      }

      .v-field__prepend-inner {
        padding-inline-end: 8px;
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

// Adăugăm și un mic efect de animație pentru apariția formularului
.add-new-form-container {
  animation: slideDown 0.2s ease-out;
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

.board-edit-form {
  margin-top: 2px;
  margin-left: 8px;
  background: rgb(var(--v-theme-background));
  border-radius: 6px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  animation: slideDown 0.2s ease-out;

  &:hover {
    border-color: rgba(var(--v-theme-on-surface), 0.2);
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.12);
  }

  .edit-form-content {
    .edit-header {
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

    .edit-body {
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

    .edit-actions {
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
}

// Folosim aceeași animație ca la add-new-form
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
</style>
