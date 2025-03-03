<script setup>
import AddEditBoard from "@/views/projects/dialogs/AddEditBoard.vue"
import PaymentDetails from "@/views/projects/dialogs/PaymentDetails.vue"
import MoveBoardDialog from "@/views/projects/dialogs/MoveBoardDialog.vue"
import DeleteConfirmDialog from "@/components/dialogs/DeleteConfirmDialog.vue"
import { router } from "@/plugins/1.router/index"

const props = defineProps({
  projectData: {
    type: null,
    required: true,
  },
  isSuperAdmin: {
    type: Boolean,
    required: false,
    default: false,
  },
  userData: {
    type: null,
    required: true,
  },
})

const emit = defineEmits([
  'formSubmitted',
  'deleteContainer',
  'moveContainer',
])

const projectDataLocal = ref(JSON.parse(JSON.stringify(props.projectData)))

watch(() => props.projectData, value => {
  projectDataLocal.value = JSON.parse(JSON.stringify(value))
})

const handleFormSubmitted = () => {
  emit('formSubmitted')
}

const isBoardDialogVisible = ref(false)
const isPaymentDetailsDialogVisible = ref(false)
const isDeleteConfirmDialogVisible = ref(false)
const isMoveDialogVisible = ref(false)
const boardDetails = ref()
const boardId = ref(0)
const isOwner = computed(() => projectDataLocal.value.owner.id === props.userData.id)
const isAdmin = ref(false)
const userData = useCookie('userData')

const editBoard = board => {
  boardDetails.value = board
  isBoardDialogVisible.value = true
}

const deleteBoard = board => {
  boardDetails.value = board
  isDeleteConfirmDialogVisible.value = true
}

const handleDeleteConfirm = (confirmed) => {
  if (confirmed) {
    emit('deleteContainer', boardDetails.value.id)
  }
}

const moveBoard = board => {
  boardDetails.value = board
  isMoveDialogVisible.value = true
}

const handleMoveConfirm = (result) => {
  if (result.confirmed) {
    emit('moveContainer', {
      boardId: result.boardId,
      targetProjectId: result.targetProjectId
    })
  }
}

const paymentBoard = board => {
  boardId.value = board.id
  isAdmin.value = board.members.some(member => member.user.id === props.userData.id && member.is_admin)
  isPaymentDetailsDialogVisible.value = true
}

const goToBoard = board => {
  if (board.is_active) {
    // Prevent any race conditions by using nextTick
    nextTick(() => {
      router.push({ 
        name: 'container-view', 
        params: { 
          id: projectDataLocal.value.id, 
          containerId: board.id 
        }
      })
    })
  }
}

watch(() => isPaymentDetailsDialogVisible.value, value => {
  if (!value) {
    boardId.value = 0
  }
})

const canEditBoard = board => {
  return props.isSuperAdmin || projectDataLocal.value.owner.id === props.userData.id || board.members.find(member => member.user.id === props.userData.id && member.is_admin)
}
</script>

<template>
  <div class="boards-grid">
    <div
      v-for="board in projectDataLocal.containers"
      :key="board.id"
      class="board-card"
      :class="{ 'is-inactive': !board.is_active }"
      @click="goToBoard(board)"
    >
      <!-- Card Header -->
      <div class="card-header">
        <div class="header-content">
          <div class="status-dot" :class="{ 'active': board.is_active }"></div>
          <h3 class="board-name">{{ board.name }}</h3>
        </div>
        <div class="status-badge" :class="board.is_active ? 'active' : 'inactive'">
          {{ board.is_active ? 'Active' : 'Inactive' }}
        </div>
      </div>

      <!-- Card Content -->
      <div class="card-content">
        <div class="members-count">
          <VIcon size="16" color="text-medium-emphasis">tabler-users</VIcon>
          <span>{{ board.members.length }} members</span>
        </div>

        <!-- Members Avatars -->
        <div class="members-avatars">
          <template v-for="(member, index) in board.members.slice(0, 3)" :key="member.id">
            <VAvatar
              :size="32"
              :color="member.user.avatar ? 'transparent' : '#f3f4f6'"
              class="member-avatar"
              v-tooltip="member.user.name"
            >
              <VImg v-if="member.user.avatar" :src="member.user.avatar" />
              <span v-else class="text-caption">{{ member.user.avatarOrInitials }}</span>
            </VAvatar>
          </template>
          <VAvatar
            v-if="board.members.length > 3"
            :size="32"
            color="#f3f4f6"
            class="member-avatar"
          >
            <span class="text-caption">+{{ board.members.length - 3 }}</span>
          </VAvatar>
        </div>
      </div>

      <!-- Card Actions -->
      <div class="card-actions">
        <VBtn
          variant="text"
          size="small"
          color="primary"
          class="action-btn"
          prepend-icon="tabler-credit-card"
          @click.stop="paymentBoard(board)"
        >
          Payments
        </VBtn>
        
        <VMenu
          v-if="canEditBoard(board)"
          location="bottom end"
          :close-on-content-click="true"
          transition="scale-transition"
          :offset="[0, 8]"
        >
          <template #activator="{ props }">
            <VBtn
              variant="text"
              size="small"
              color="primary"
              class="action-btn"
              prepend-icon="tabler-dots-vertical"
              v-bind="props"
              @click.stop
            >
              Actions
            </VBtn>
          </template>
          
          <VList density="compact" class="action-menu">
            <VListItem
              prepend-icon="tabler-edit"
              title="Edit"
              class="action-item edit-action"
              @click.stop="editBoard(board)"
            />
            <VDivider class="my-1" />
            <VListItem
              prepend-icon="tabler-trash"
              title="Delete"
              class="action-item delete-action"
              @click.stop="deleteBoard(board)"
            />
            <VDivider class="my-1" />
            <VListItem
              prepend-icon="tabler-arrows-transfer-up"
              title="Move to another project"
              class="action-item move-action"
              @click.stop="moveBoard(board)"
            />
          </VList>
        </VMenu>
      </div>
    </div>
  </div>

  <!-- Dialogs -->
  <AddEditBoard
    v-model:board-details="boardDetails"
    v-model:is-dialog-visible="isBoardDialogVisible"
    v-model:project-id="projectDataLocal.id"
    @form-submitted="handleFormSubmitted"
  />

  <PaymentDetails
    v-model:board-id="boardId"
    v-model:is-super-admin="props.isSuperAdmin"
    v-model:is-owner="isOwner"
    v-model:is-admin="isAdmin"
    v-model:is-dialog-visible="isPaymentDetailsDialogVisible"
    @form-submitted="handleFormSubmitted"
  />

  <DeleteConfirmDialog
    v-model:isDialogVisible="isDeleteConfirmDialogVisible"
    title="Delete board"
    :item-name="boardDetails?.name"
    item-type="board"
    message="This action cannot be undone. This will permanently delete this board and remove all associated data, including tasks, comments, and files."
    :confirmation-text="boardDetails?.name"
    @confirm="handleDeleteConfirm"
  />
  
  <MoveBoardDialog
    v-model:isDialogVisible="isMoveDialogVisible"
    :board-details="boardDetails"
    @confirm="handleMoveConfirm"
  />
</template>

<style lang="scss" scoped>
.boards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;

  .board-card {
    background: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    transition: all 0.2s ease;
    cursor: pointer;

    &:hover {
      border-color: #0969da;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transform: translateY(-2px);
    }

    &.is-inactive {
      opacity: 0.7;
      cursor: not-allowed;

      &:hover {
        transform: none;
        border-color: #d0d7de;
        box-shadow: none;
      }
    }

    .card-header {
      padding: 1rem;
      border-bottom: 1px solid #d0d7de;
      display: flex;
      justify-content: space-between;
      align-items: center;

      .header-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;

        .status-dot {
          width: 8px;
          height: 8px;
          border-radius: 50%;
          background: #cf222e;

          &.active {
            background: #2da44e;
          }
        }

        .board-name {
          font-size: 0.875rem;
          font-weight: 600;
          color: #24292f;
          margin: 0;
        }
      }

      .status-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;

        &.active {
          background: #dafbe1;
          color: #1a7f37;
        }

        &.inactive {
          background: #ffebe9;
          color: #cf222e;
        }
      }
    }

    .card-content {
      padding: 1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;

      .members-count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #57606a;
        font-size: 0.875rem;
      }

      .members-avatars {
        display: flex;
        align-items: center;

        .member-avatar {
          margin-left: -0.5rem;
          border: 2px solid #ffffff;

          &:first-child {
            margin-left: 0;
          }
        }
      }
    }

    .card-actions {
      padding: 0.75rem;
      display: flex;
      justify-content: flex-end;
      gap: 0.5rem;
      border-top: 1px solid #d0d7de;
      background: #f6f8fa;

      .action-btn {
        font-size: 0.75rem;
        height: 28px;
        
        .v-icon {
          font-size: 1rem;
        }
      }
    }
  }
}

.action-menu {
  min-width: 220px;
  border-radius: 6px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  padding: 4px;
  
  .action-item {
    border-radius: 4px;
    margin: 2px 0;
    transition: all 0.2s ease;
    
    &:hover {
      background-color: #f6f8fa;
    }
    
    &.edit-action {
      :deep(.v-list-item__prepend) {
        .v-icon {
          color: #0969da;
        }
      }
      
      &:hover {
        background-color: rgba(9, 105, 218, 0.08);
      }
    }
    
    &.delete-action {
      :deep(.v-list-item__prepend) {
        .v-icon {
          color: #cf222e;
        }
      }
      
      &:hover {
        background-color: rgba(207, 34, 46, 0.08);
      }
    }
    
    &.move-action {
      :deep(.v-list-item__prepend) {
        .v-icon {
          color: #8250df;
        }
      }
      
      &:hover {
        background-color: rgba(130, 80, 223, 0.08);
      }
    }
    
    :deep(.v-list-item__content) {
      .v-list-item-title {
        font-size: 0.875rem;
        font-weight: 500;
      }
    }
  }
}
</style>
