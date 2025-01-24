<script setup>
import AddEditBoard from "@/views/projects/dialogs/AddEditBoard.vue"
import PaymentDetails from "@/views/projects/dialogs/PaymentDetails.vue"

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

const editBoard = board => {
  boardDetails.value = board
  isBoardDialogVisible.value = true
}

const paymentBoard = board => {
  boardId.value = board.id
  isPaymentDetailsDialogVisible.value = true
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
        <VCard>
          <VCardTitle>
            <VChip color="info">
              Owner: {{ item.owner.full_name }}
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
                <h5 class="text-h5">
                  {{ item.name }}
                </h5>
                <div class="d-flex align-center text-center mt-1">
                  <RouterLink :to="{ name: 'container-view', params: { id: projectDataLocal.id, containerId: item.id } }">
                    <VIcon
                      icon="tabler-clipboard-list"
                      class="text-high-emphasis"
                    />
                    Access Board
                  </RouterLink>
                </div>
              </div>
              <div class="d-flex gap-4">
                <VBtn
                  v-if="props.isSuperAdmin || projectDataLocal.owner.id === props.userData.id"
                  icon
                  color="info"
                  @click="paymentBoard(item)"
                >
                  <VIcon icon="tabler-credit-card" />
                </VBtn>
                <VBtn
                  v-if="props.isSuperAdmin || projectDataLocal.owner.id === props.userData.id"
                  icon
                  @click="editBoard(item)"
                >
                  <VIcon icon="tabler-edit" />
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
      v-model:is-dialog-visible="isPaymentDetailsDialogVisible"
      @form-submitted="handleFormSubmitted"
    />
  </div>
</template>
