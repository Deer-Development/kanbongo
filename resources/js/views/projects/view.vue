<script setup>
import ContainersCards from '@/views/projects/components/ContainersCards.vue'
import AddEditBoard from "@/views/projects/dialogs/AddEditBoard.vue"

const route = useRoute()
const projectData = ref(null)
const isSuperAdmin = ref(false)
const boardDetails = ref()

const fetchContainer = async () => {
  const { data } = await $api(`/project/${route.params.id}`)

  projectData.value = data.project
  isSuperAdmin.value = data.isSuperAdmin
}

const isAddBoardDialogVisible = ref(false)
const isPaymentDetailsDialogVisible = ref(false)

onMounted(() => {
  fetchContainer()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <div
        v-if="projectData"
        class="d-flex justify-space-between align-center"
      >
        <h4 class="text-h4 mb-1">
          <VChip color="primary">
            {{ projectData?.name }}
          </VChip>
          <VChip
            color="secondary"
            class="ml-2"
          >
            Boards List
          </VChip>
          <VChip
            color="warning"
            class="ml-2"
          >
            <span class="font-weight-bold mr-1"> Owner: </span> {{ projectData?.owner?.full_name }}
          </VChip>
        </h4>

        <VBtn
          v-if="isSuperAdmin"
          @click="isAddBoardDialogVisible = true"
        >
          Add New Board
        </VBtn>
            
        <AddEditBoard
          v-model:is-dialog-visible="isAddBoardDialogVisible"
          v-model:board-details="boardDetails"
          v-model:project-id="projectData.id"
          @form-submitted="fetchContainer"
        />
      </div>

      <VCol
        v-if="projectData"
        cols="12"
      >
        <ContainersCards
          :project-data="projectData"
          :is-super-admin="isSuperAdmin"
          @form-submitted="fetchContainer"
        />
      </VCol>
    </vcol>
  </VRow>
</template>
