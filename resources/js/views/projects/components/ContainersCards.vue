<script setup>
import AddEditBoard from "@/views/projects/dialogs/AddEditBoard.vue"
import PaymentDetails from "@/views/projects/dialogs/PaymentDetails.vue"
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
const boardDetails = ref()
const boardId = ref(0)
const isOwner = computed(() => projectDataLocal.value.owner.id === props.userData.id)
const isAdmin = ref(false)

const editBoard = board => {
  boardDetails.value = board
  isBoardDialogVisible.value = true
}

const paymentBoard = board => {
  boardId.value = board.id
  isAdmin.value = board.members.some(member => member.user.id === props.userData.id && member.is_admin)
  isPaymentDetailsDialogVisible.value = true
}

const goToBoard = board => {
  if(board.is_active)
    router.push({ name: 'container-view', params: { id: projectDataLocal.value.id, containerId: board.id } })
}

watch(() => isPaymentDetailsDialogVisible.value, value => {
  if (!value) {
    boardId.value = 0
  }
})
</script>

<template>
  <div>
    <VRow>
      <VCol
        v-for="item in projectDataLocal.containers"
        :key="item.id"
        cols="12"
        sm="6"
        lg="4"
      >
        <VCard
          :class="{
            'cursor-pointer': item.is_active,
            'cursor-not-allowed': !item.is_active,
          }"
          @click="goToBoard(item)"
        >
          <VCardTitle>
            <VChip color="warning">
              <span>{{ item.name }}</span>
            </VChip>
          </VCardTitle>
          <VCardText class="d-flex align-center pb-4">
            <div class="text-body-1">
              Total {{ item.members.length }} members
            </div>

            <VSpacer />

            <div class="v-avatar-group">
              <template
                v-for="(member, index) in item.members"
                :key="member.id"
              >
                <VAvatar
                  v-if="item.members.length > 0 && item.members.length !== 4 && index < 3"
                  v-tooltip="member.user.full_name"
                  size="40"
                  :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
                >
                  <template v-if="member.user.avatar">
                    <img
                      :src="member.user.avatar"
                      alt="Avatar"
                    >
                  </template>
                  <template v-else>
                    <span>{{ member.user.avatar_or_initials }}</span>
                  </template>
                </VAvatar>

                <VAvatar
                  v-if="item.members.length === 4"
                  size="40"
                  :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
                >
                  <template v-if="member.user.avatar">
                    <img
                      :src="member.user.avatar"
                      alt="Avatar"
                    >
                  </template>
                  <template v-else>
                    <span>{{ member.user.avatar_or_initials }}</span>
                  </template>
                </VAvatar>
              </template>

              <VAvatar
                v-if="item.members.length > 4"
                :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
              >
                <span>
                  +{{ item.members.length - 3 }}
                </span>
              </VAvatar>
            </div>
          </VCardText>

          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="custom-badge">
                  <VIcon
                    :icon="item.is_active ? 'tabler-circle-check' : 'tabler-circle-x'"
                    :color="item.is_active ? 'success' : 'error'"
                    size="18"
                  />
                  <span>{{ item.is_active ? 'Active' : 'Inactive' }}</span>
                </div>
              </div>
              <div class="d-flex gap-4">
                <VBtn
                  icon
                  color="info"
                  size="x-small"
                  @click.stop="paymentBoard(item)"
                >
                  <VIcon
                    size="14"
                    icon="tabler-credit-card"
                  />
                </VBtn>
                <VBtn
                  v-if="props.isSuperAdmin || projectDataLocal.owner.id === props.userData.id || item.members.find(member => member.user.id === props.userData.id && member.is_admin)"
                  icon
                  size="x-small"
                  @click.stop="editBoard(item)"
                >
                  <VIcon
                    icon="tabler-edit"
                    size="14"
                  />
                </VBtn>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

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
  </div>
</template>
