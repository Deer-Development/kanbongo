<script setup>
import { ref, computed, watch } from 'vue'
import { useToast } from "vue-toastification"
import MoveBoardDialog from "@/views/projects/dialogs/MoveBoardDialog.vue"
import DeleteConfirmDialog from "@/components/dialogs/DeleteConfirmDialog.vue"

const props = defineProps({
  boardDetails: {
    type: Object,
    required: false,
    default: () => ({
      name: '',
      is_active: true,
      members: [],
    }),
  },
  projectId: {
    type: Number,
    required: true,
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  isOwner: { type: Boolean, required: false, default: false },
  isAdmin: { type: Boolean, required: false, default: false },
  isSuperAdmin: { type: Boolean, required: false, default: false },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'update:boardDetails',
  'formSubmitted',
  'moveBoard',
  'deleteBoard',
])

const toast = useToast()
const boardDataLocal = ref(null)
const isSubmitting = ref(false)
const name = ref('')
const isActive = ref(true)
const isHeaderHovered = ref(false)
const refBoardForm = ref()

// Transfer Ownership
const isTransferOwnershipDialogVisible = ref(false)
const selectedNewOwner = ref(null)
const isTransferringOwnership = ref(false)
const confirmationEmail = ref('')

// Move Board
const isMoveDialogVisible = ref(false)

// Delete Board
const isDeleteDialogVisible = ref(false)

watch(() => props.boardDetails, (newVal) => {
  if (newVal) {
    boardDataLocal.value = { ...newVal }
    name.value = newVal.name
    isActive.value = newVal.is_active
  }
}, { immediate: true })

const confirmationError = computed(() => {
  if (!confirmationEmail.value) return ''
  if (confirmationEmail.value !== boardDataLocal.value.members.find(m => m.user_id === selectedNewOwner.value)?.user.email) {
    return 'Email does not match'
  }
  return ''
})

const canTransfer = computed(() => {
  return selectedNewOwner.value && 
         confirmationEmail.value === boardDataLocal.value.members.find(m => m.user_id === selectedNewOwner.value)?.user.email
})

const onSubmit = async () => {
  const { valid } = await refBoardForm.value.validate()
  if (!valid) return

  isSubmitting.value = true
  
  try {
    const response = await $api(`/container/${boardDataLocal.value.id}`, {
      method: 'PUT',
      body: {
        name: name.value,
        is_active: isActive.value,
        project_id: props.projectId,
        owner_id: boardDataLocal.value.owner_id,
      },
    })

    if (response) {
      toast.success('Board settings updated successfully!')
      emit('formSubmitted')
      emit('update:isDialogVisible', false)
    }
  } catch (error) {
    toast.error('Failed to update board settings')
  } finally {
    isSubmitting.value = false
  }
}

const transferOwnership = async () => {
  if (!canTransfer.value) return
  
  isTransferringOwnership.value = true
  
  try {
    await $api(`/container/${boardDataLocal.value.id}/transfer-ownership`, {
      method: 'POST',
      body: {
        new_owner_id: selectedNewOwner.value
      }
    })
    
    toast.success('Board ownership transferred successfully!')
    boardDataLocal.value.owner_id = selectedNewOwner.value
    isTransferOwnershipDialogVisible.value = false

    emit('formSubmitted')
    emit('update:isDialogVisible', false)
  } catch (error) {
    toast.error('Failed to transfer ownership')
  } finally {
    isTransferringOwnership.value = false
    selectedNewOwner.value = null
    confirmationEmail.value = ''
  }
}

const handleMoveBoard = (result) => {
  console.log(result)
  if (result.confirmed) {
    emit('moveBoard', {
      boardId: boardDataLocal.value.id,
      targetProjectId: result.targetProjectId
    })
  }

  emit('update:isDialogVisible', false)
}

const handleDeleteBoard = (confirmed) => {
  if (confirmed) {
    emit('deleteBoard', boardDataLocal.value.id)
  }

  emit('update:isDialogVisible', false)
}
</script>

<template>
  <VDialog
    :model-value="isDialogVisible"
    @update:model-value="emit('update:isDialogVisible', $event)"
    max-width="600"
    persistent
    class="board-settings-dialog"
  >
    <VCard>
      <VCardTitle class="dialog-header pa-4">
        <div class="d-flex align-center">
          <div class="icon-container">
            <span class="emoji" :class="{ 'is-hovered': isHeaderHovered }" @mouseenter="isHeaderHovered = true" @mouseleave="isHeaderHovered = false">
              {{ isHeaderHovered ? '⚡' : '⚙️' }}
            </span>
          </div>
          <span class="title-text">Board Settings</span>
        </div>
        <VBtn
          icon
          variant="text"
          size="small"
          @click="emit('update:isDialogVisible', false)"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-4">
        <VForm
          ref="refBoardForm"
          @submit.prevent="onSubmit"
        >
          <!-- Basic Settings -->
          <div class="settings-section">
            <div class="section-header">
              <VIcon icon="tabler-info-circle" size="18" class="me-2" />
              <span class="text-h6">Basic Settings</span>
            </div>

            <VTextField
              v-model="name"
              label="Board Name"
              placeholder="Enter board name"
              :rules="[
                v => !!v || 'Board name is required',
                v => v.length >= 3 || 'Name must be at least 3 characters'
              ]"
              variant="outlined"
              density="comfortable"
              class="mb-4"
            />

            <VSwitch
              v-model="isActive"
              label="Board Status"
              color="success"
              :true-value="true"
              :false-value="false"
              hide-details
            >
              <template #label>
                <span class="d-flex align-center">
                  Active
                  <VChip
                    :color="isActive ? 'success' : 'warning'"
                    size="x-small"
                    class="ms-2"
                  >
                    {{ isActive ? 'Active' : 'Inactive' }}
                  </VChip>
                </span>
              </template>
            </VSwitch>
          </div>

          <!-- Advanced Actions -->
          <div class="settings-section mt-6">
            <div class="section-header mb-4">
              <VIcon icon="tabler-tool" size="18" class="me-2" />
              <span class="text-h6">Advanced Actions (Require Owner Access)</span>
            </div>

            <div class="actions-grid">
              <!-- Transfer Ownership -->
              <VBtn
                color="warning"
                variant="tonal"
                block
                prepend-icon="tabler-exchange"
                @click="isTransferOwnershipDialogVisible = true"
                :disabled="!boardDataLocal?.members.length || boardDataLocal?.members.length === 1 || !isOwner"
              >
                Transfer Ownership
              </VBtn>

              <!-- Move Board -->
              <VBtn
                color="info"
                variant="tonal"
                block
                prepend-icon="tabler-arrows-transfer-up"
                @click="isMoveDialogVisible = true"
                :disabled="!isOwner"
              >
                Move Board
              </VBtn>

              <!-- Delete Board -->
              <VBtn
                color="error"
                variant="tonal"
                block
                prepend-icon="tabler-trash"
                @click="isDeleteDialogVisible = true"
                :disabled="!isOwner"
              >
                Delete Board
              </VBtn>
            </div>
          </div>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="tonal"
          @click="emit('update:isDialogVisible', false)"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          :loading="isSubmitting"
          @click="onSubmit"
        >
          Save Changes
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Transfer Ownership Dialog -->
  <VDialog
    v-model="isTransferOwnershipDialogVisible"
    max-width="500"
    persistent
  >
    <VCard>
      <VCardTitle class="dialog-header pa-4 d-flex justify-space-between">
        <div class="d-flex align-center">
          <VIcon icon="tabler-exchange" color="warning" class="me-2" />
          Transfer Board Ownership
        </div>
        <VBtn
          icon
          variant="text"
          size="small"
          @click="isTransferOwnershipDialogVisible = false"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pt-4">
        <VAlert
          type="warning"
          variant="tonal"
          border="start"
          class="mb-4"
        >
          <template #prepend>
            <VIcon icon="tabler-alert-triangle" />
          </template>
          <p class="text-body-2 mb-0">
            Transferring ownership will give another member full control over this board.
            This action cannot be undone.
          </p>
        </VAlert>

        <VSelect
          v-model="selectedNewOwner"
          :items="boardDataLocal?.members.filter(m => m.user_id !== boardDataLocal.owner_id)"
          item-title="user.full_name"
          item-value="user.id"
          label="Select New Owner"
          variant="outlined"
          density="comfortable"
          class="mb-4"
        >
          <template #item="{ props, item }">
            <VListItem
              v-bind="props"
              :title="item?.raw?.user.full_name"
              :subtitle="item?.raw?.user.email"
            >
              <template #prepend>
                <VAvatar size="32">
                  <VImg
                    v-if="item.raw?.user.avatar"
                    :src="item.raw?.user.avatar"
                  />
                  <span v-else>{{ item.raw?.user.avatarOrInitials }}</span>
                </VAvatar>
              </template>
            </VListItem>
          </template>
        </VSelect>

        <div class="confirmation-text mt-4">
          <p class="text-body-2">
            To confirm, please type the email address of the new owner:
            <strong class="text-warning">{{ boardDataLocal?.members.find(m => m.user_id === selectedNewOwner)?.user.email }}</strong>
          </p>
          <VTextField
            v-model="confirmationEmail"
            placeholder="Type email to confirm"
            variant="outlined"
            density="comfortable"
            :error-messages="confirmationError"
          />
        </div>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="tonal"
          @click="isTransferOwnershipDialogVisible = false"
        >
          Cancel
        </VBtn>
        <VBtn
          color="warning"
          :loading="isTransferringOwnership"
          :disabled="!canTransfer"
          @click="transferOwnership"
        >
          Transfer Ownership
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Move Board Dialog -->
  <MoveBoardDialog
    v-model:isDialogVisible="isMoveDialogVisible"
    :board-details="boardDataLocal"
    @confirm="handleMoveBoard"
  />

  <!-- Delete Board Dialog -->
  <DeleteConfirmDialog
    v-model:isDialogVisible="isDeleteDialogVisible"
    title="Delete board"
    :item-name="boardDataLocal?.name"
    item-type="board"
    message="This action cannot be undone. This will permanently delete this board and remove all associated data, including tasks, comments, and files."
    :confirmation-text="boardDataLocal?.name"
    @confirm="handleDeleteBoard"
  />
</template>

<style lang="scss">
.board-settings-dialog {
  .dialog-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .icon-container {
      width: 32px;
      height: 32px;
      background: rgba(var(--v-theme-primary), 0.1);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 12px;

      .emoji {
        font-size: 18px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        
        &.is-hovered {
          animation: bounce 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
      }
    }

    .title-text {
      font-size: 1.25rem;
      font-weight: 600;
      color: rgb(var(--v-theme-on-surface));
    }
  }

  .settings-section {
    .section-header {
      display: flex;
      align-items: center;
      margin-bottom: 16px;
      color: rgb(var(--v-theme-on-surface));

      .v-icon {
        color: rgba(var(--v-theme-primary), 0.9);
      }
    }

    .actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
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
</style> 