<script setup>
import { ref, computed, watch } from 'vue'
import { useToast } from 'vue-toastification'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    default: 'Delete confirmation',
  },
  itemName: {
    type: String,
    default: '',
  },
  itemType: {
    type: String,
    default: 'item',
  },
  message: {
    type: String,
    default: 'This action cannot be undone. This will permanently delete this item and remove all associated data.',
  },
  confirmationText: {
    type: String,
    default: '',
  },
  dangerousAction: {
    type: Boolean,
    default: true,
  },
  loading: {
    type: Boolean,
    default: false,
  }
})

const emit = defineEmits(['update:isDialogVisible', 'confirm', 'cancel'])

const toast = useToast()
const verificationText = ref('')
const isConfirmButtonDisabled = computed(() => {
  if (props.confirmationText) {
    return verificationText.value !== props.confirmationText
  }
  return false
})

const displayName = computed(() => {
  return props.itemName || `this ${props.itemType}`
})

const updateModelValue = (val) => {
  emit('update:isDialogVisible', val)
  if (!val) {
    resetDialog()
  }
}

const confirmDelete = () => {
  emit('confirm', true)
  updateModelValue(false)
  toast.success('Deleted successfully')
}

const cancelDelete = () => {
  emit('cancel')
  updateModelValue(false)
}

const resetDialog = () => {
  verificationText.value = ''
}

// Reset dialog when it opens
watch(() => props.isDialogVisible, (newVal) => {
  if (newVal) {
    resetDialog()
  }
})
</script>

<template>
  <VDialog
    :model-value="isDialogVisible"
    @update:model-value="updateModelValue"
    max-width="480"
    persistent
    class="delete-confirm-dialog"
  >
    <VCard class="delete-card">
      <VCardItem class="dialog-header">
        <template #prepend>
          <div class="warning-icon">
            <VIcon
              icon="tabler-alert-triangle"
              color="error"
              size="28"
            />
          </div>
        </template>
        <VCardTitle class="dialog-title">
          {{ title }}
        </VCardTitle>
      </VCardItem>

      <VDivider />

      <VCardText class="dialog-content">
        <p class="text-body-1 mb-4">
          Are you sure you want to delete <strong>"{{ displayName }}"</strong>?
        </p>
        
        <VAlert
          type="error"
          variant="tonal"
          border="start"
          class="mb-4 warning-alert"
          density="compact"
        >
          <template #prepend>
            <VIcon
              icon="tabler-alert-circle"
              start
            />
          </template>
          <p class="text-body-2 mb-0">
            {{ message }}
          </p>
        </VAlert>

        <div v-if="confirmationText" class="verification-container">
          <p class="text-body-2 mb-2">
            Please type <strong>{{ confirmationText }}</strong> to confirm.
          </p>
          <VTextField
            v-model="verificationText"
            variant="outlined"
            density="compact"
            placeholder="Type to confirm"
            hide-details="auto"
            :error-messages="isConfirmButtonDisabled ? 'Please enter the confirmation text exactly as shown above' : ''"
            class="verification-field"
          />
        </div>
      </VCardText>

      <VDivider />

      <VCardActions class="dialog-actions">
        <VSpacer />
        
        <VBtn
          color="secondary"
          variant="outlined"
          :disabled="loading"
          @click="cancelDelete"
        >
          Cancel
        </VBtn>
        
        <VBtn
          color="error"
          variant="elevated"
          :disabled="isConfirmButtonDisabled || loading"
          :loading="loading"
          @click="confirmDelete"
        >
          Delete
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>
.delete-confirm-dialog {
  .delete-card {
    border-radius: 6px;
    overflow: hidden;
    
    .dialog-header {
      padding: 16px 20px;
      
      .warning-icon {
        background: rgba(var(--v-theme-error), 0.1);
        padding: 8px;
        border-radius: 6px;
      }
      
      .dialog-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: rgb(var(--v-theme-on-surface));
      }
    }
    
    .dialog-content {
      padding: 20px;
      
      .warning-alert {
        background-color: rgba(var(--v-theme-error), 0.05);
        border-color: rgb(var(--v-theme-error));
        
        :deep(.v-alert__prepend) {
          .v-icon {
            color: rgb(var(--v-theme-error));
          }
        }
      }
      
      .verification-container {
        background: rgba(var(--v-theme-error), 0.05);
        border-radius: 6px;
        padding: 16px;
        margin-top: 16px;
        
        p {
          color: rgba(var(--v-theme-on-surface), 0.87);
        }
        
        .verification-field {
          margin-top: 8px;
          
          :deep(.v-field) {
            border-color: rgba(var(--v-theme-error), 0.3);
            
            &:hover, &:focus-within {
              border-color: rgb(var(--v-theme-error));
            }
          }
          
          :deep(.v-messages) {
            color: rgb(var(--v-theme-error));
            font-size: 0.75rem;
            min-height: 0;
            padding-top: 4px;
          }
        }
      }
    }
    
    .dialog-actions {
      padding: 12px 20px;
      background: rgb(var(--v-theme-surface));
    }
  }
}

// Animation for the dialog
:deep(.v-dialog-transition-enter-active) {
  transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
}

:deep(.v-dialog-transition-leave-active) {
  transition: all 0.15s cubic-bezier(0.25, 0.8, 0.25, 1);
}

:deep(.v-dialog-transition-enter-from),
:deep(.v-dialog-transition-leave-to) {
  opacity: 0;
  transform: scale(0.95);
}

// Responsive adjustments
@media (max-width: 600px) {
  .delete-confirm-dialog {
    .delete-card {
      .dialog-header {
        padding: 12px 16px;
        
        .dialog-title {
          font-size: 1.125rem;
        }
      }
      
      .dialog-content {
        padding: 16px;
      }
      
      .dialog-actions {
        padding: 12px 16px;
        flex-wrap: wrap;
        gap: 8px;
        
        .v-btn {
          flex: 1;
        }
      }
    }
  }
}
</style> 