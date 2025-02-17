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

const breadcumItems = computed(() => {
  return [
    {
      title: 'Projects',
      disabled: false,
      href: '/projects',
    },
    {
      title: `${ projectData.value?.name }`,
      disabled: true,
    },
  ]
})

const userData = computed(() => useCookie('userData', { default: null }).value)

const isAddBoardDialogVisible = ref(false)

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
        <VBreadcrumbs :items="breadcumItems">
          <template #divider>
            <VIcon>
              tabler-chevron-right
            </VIcon>
          </template>
        </VBreadcrumbs>
        <h4 class="text-h4 mb-1">
          <VBtn
            class="ml-2"
            size="small"
            @click="isAddBoardDialogVisible = true"
          >
            Add New Board
          </VBtn>
        </h4>
      </div>

      <VCol
        v-if="projectData"
        cols="12"
      >
        <AddEditBoard
          v-model:is-dialog-visible="isAddBoardDialogVisible"
          v-model:board-details="boardDetails"
          v-model:project-id="projectData.id"
          @form-submitted="fetchContainer"
        />
        <ContainersCards
          :project-data="projectData"
          :is-super-admin="isSuperAdmin"
          :user-data="userData"
          @form-submitted="fetchContainer"
        />
      </VCol>
    </VCol>
  </VRow>
</template>
