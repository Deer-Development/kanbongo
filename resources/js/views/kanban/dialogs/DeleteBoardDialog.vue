<script setup>
import { useToast } from "vue-toastification"

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  boardDetails: {
    type: Object,
    required: true,
  },
  availableBoards: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'confirm',
  'cancel',
])

const selectedBoard = ref(null)
const toast = useToast()
const searchQuery = ref('')
const verificationText = ref('')

const filteredBoards = computed(() => {
  if (!searchQuery.value) return props.availableBoards
  
  return props.availableBoards.filter(board => 
    board.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const hasTasksButNoAvailableBoards = computed(() => {
  return props.boardDetails?.tasks?.length > 0 && (!props.availableBoards?.length || props.availableBoards.length === 0)
})

const updateModelValue = val => {
  emit('update:isDialogVisible', val)
  if (!val) {
    selectedBoard.value = null
    searchQuery.value = ''
  }
}

const onConfirmation = () => {
  if (hasTasksButNoAvailableBoards.value) {
    toast.error('Cannot delete board with tasks when no other columns are available')
    return
  }

  if (!selectedBoard.value && props.boardDetails?.tasks?.length > 0) {
    toast.error('Please select a board to move existing tasks')
    return
  }

  emit('confirm', {
    targetBoardId: selectedBoard.value,
    confirmed: true,
  })
  updateModelValue(false)
}

const onCancel = () => {
  emit('cancel')
  updateModelValue(false)
}

// Reset selection when dialog opens
watch(() => props.isDialogVisible, (newVal) => {
  if (newVal) {
    selectedBoard.value = null
    searchQuery.value = ''
  }
})

// Adăugăm funcția pentru convertirea hex în rgb
const hexToRgb = (hex) => {
  // Remove # if present
  hex = hex.replace('#', '')
  
  // Convert 3-digit hex to 6-digits
  if (hex.length === 3) {
    hex = hex.split('').map(char => char + char).join('')
  }
  
  const r = parseInt(hex.substring(0, 2), 16)
  const g = parseInt(hex.substring(2, 4), 16)
  const b = parseInt(hex.substring(4, 6), 16)
  
  return `${r}, ${g}, ${b}`
}
</script>

<template>
  <VDialog
    :model-value="isDialogVisible"
    @update:model-value="updateModelValue"
    max-width="550"
    persistent
    class="delete-board-dialog"
  >
    <VCard class="delete-board-card">
      <VCardItem class="dialog-header">
        <template #prepend>
          <div class="warning-icon">
            <VIcon
              icon="tabler-alert-triangle"
              color="warning"
              size="28"
            />
          </div>
        </template>
        <VCardTitle class="dialog-title">
          Delete "{{ boardDetails.name }}"
        </VCardTitle>
      </VCardItem>

      <VDivider />

      <VCardText class="pt-6">
        <p class="text-body-1 mb-4">
          <span class="font-weight-medium">Warning:</span>
          This action cannot be undone. This will permanently delete the
          <span class="font-weight-medium">"{{ boardDetails.name }}"</span> column
          <template v-if="boardDetails?.tasks?.length">
            and move its {{ boardDetails.tasks.length }} task(s) to another column.
          </template>
        </p>

        <template v-if="hasTasksButNoAvailableBoards">
          <VAlert
            type="error"
            variant="tonal"
            border="start"
            class="mb-4"
          >
            <template #prepend>
              <VIcon
                icon="tabler-alert-circle"
                start
              />
            </template>
            <p class="text-body-1 mb-1">Cannot Delete Column</p>
            <p class="text-body-2 mb-0">
              This column contains {{ boardDetails.tasks.length }} task(s) but there are no other columns available to move them to.
              Please create another column first or move the tasks manually.
            </p>
          </VAlert>
        </template>

        <template v-else-if="boardDetails?.tasks?.length">
          <div class="board-selection-container">
            <p class="text-subtitle-2 mb-3">
              Please select a destination column for the tasks:
            </p>

            <VTextField
              v-model="searchQuery"
              density="comfortable"
              placeholder="Search columns..."
              variant="outlined"
              prepend-inner-icon="tabler-search"
              hide-details
              class="mb-3"
            />

            <div class="boards-list">
              <VRadioGroup
                v-model="selectedBoard"
                class="board-radio-group"
              >
                <div
                  v-for="board in filteredBoards"
                  :key="board.id"
                  class="board-option"
                  :class="{ 'selected': selectedBoard === board.id }"
                  :style="{
                    '--board-color': board.color,
                    '--board-color-rgb': hexToRgb(board.color)
                  }"
                  @click="selectedBoard = board.id"
                >
                  <VRadio
                    :value="board.id"
                    class="board-radio"
                    :color="board.color"
                  >
                    <template #label>
                      <div class="d-flex align-center board-label">
                        <div 
                          class="color-indicator"
                          :style="{ backgroundColor: board.color }"
                        />
                        <span class="board-name">{{ board.name }}</span>
                      </div>
                    </template>
                  </VRadio>
                </div>
              </VRadioGroup>
            </div>
          </div>
        </template>

        <div 
          v-if="!hasTasksButNoAvailableBoards"
          class="verification-container mt-6"
        >
          <p class="text-subtitle-2 mb-2 text-error">
            Please type <strong>{{ boardDetails.name }}</strong> to confirm.
          </p>
          <VTextField
            v-model="verificationText"
            density="comfortable"
            placeholder="Enter column name"
            variant="outlined"
            :rules="[v => v === boardDetails.name || 'Column name does not match']"
            :error-messages="verificationText && verificationText !== boardDetails.name ? 'Column name does not match' : ''"
            persistent-hint
          />
        </div>
      </VCardText>

      <VDivider class="mt-4" />

      <VCardActions class="dialog-actions pa-4">
        <VSpacer />
        <VBtn
          variant="tonal"
          color="default"
          @click="onCancel"
        >
          Cancel
        </VBtn>
        <VBtn
          color="error"
          :disabled="hasTasksButNoAvailableBoards || 
            verificationText !== boardDetails.name || 
            (boardDetails?.tasks?.length > 0 && !selectedBoard)"
          @click="onConfirmation"
        >
          I understand, delete this column
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>
.delete-board-dialog {
  .delete-board-card {
    border-radius: 6px;
    
    .dialog-header {
      padding: 16px 20px;
      
      .warning-icon {
        background: rgba(var(--v-theme-warning), 0.1);
        padding: 8px;
        border-radius: 6px;
      }
      
      .dialog-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: rgb(var(--v-theme-on-surface));
      }
    }

    .board-selection-container {
      background: rgb(var(--v-theme-surface));
      border-radius: 6px;
      border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
      padding: 16px;
      margin-top: 16px;

      .boards-list {
        max-height: 240px;
        overflow-y: auto;
        border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
        border-radius: 6px;
        background: rgb(var(--v-theme-background));

        .board-radio-group {
          padding: 4px;
        }

        .board-option {
          border-radius: 6px;
          transition: all 0.2s ease;
          cursor: pointer;
          margin: 4px;
          position: relative;
          overflow: hidden;
          background: rgba(var(--board-color-rgb), 0.05);
          border: 1px solid rgba(var(--board-color-rgb), 0.1);

          &::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: var(--board-color);
            opacity: 0.5;
            transition: width 0.2s ease;
          }

          &:hover {
            background: rgba(var(--board-color-rgb), 0.08);
            border-color: rgba(var(--board-color-rgb), 0.2);

            &::before {
              width: 6px;
              opacity: 0.7;
            }

            .color-indicator {
              transform: scale(1.1);
            }
          }

          &.selected {
            background: rgba(var(--board-color-rgb), 0.15);
            border-color: var(--board-color);

            &::before {
              width: 8px;
              opacity: 1;
            }

            .board-name {
              color: var(--board-color);
            }

            .color-indicator {
              transform: scale(1.2);
              box-shadow: 0 0 0 2px rgba(var(--board-color-rgb), 0.2);
            }
          }

          .board-radio {
            margin: 0;
            padding: 12px 16px;
            width: 100%;
          }

          .board-label {
            width: 100%;
            font-size: 0.875rem;
            gap: 12px;
          }

          .color-indicator {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            transition: all 0.2s ease;
          }

          .board-name {
            font-weight: 500;
            transition: color 0.2s ease;
          }
        }
      }
    }

    .verification-container {
      background: rgba(var(--v-theme-error), 0.05);
      border-radius: 6px;
      padding: 16px;

      :deep(.v-text-field) {
        .v-messages {
          color: rgb(var(--v-theme-error));
          font-size: 0.75rem;
          min-height: 0;
          padding-top: 4px;
        }
      }
    }

    .dialog-actions {
      background: rgb(var(--v-theme-surface));
    }

    :deep(.v-alert) {
      .v-alert-title {
        margin-bottom: 8px;
      }

      .v-icon {
        opacity: 1;
      }
    }
  }
}

// Custom scrollbar for the boards list
.boards-list {
  scrollbar-width: thin;
  scrollbar-color: rgba(var(--v-theme-on-surface), 0.2) transparent;

  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: transparent;
  }

  &::-webkit-scrollbar-thumb {
    background-color: rgba(var(--v-theme-on-surface), 0.2);
    border-radius: 3px;

    &:hover {
      background-color: rgba(var(--v-theme-on-surface), 0.3);
    }
  }
}
</style> 