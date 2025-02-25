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
  <div class="project-view">
    <!-- Header Section -->
    <div class="header-section">
      <div class="breadcrumbs-wrapper">
        <VBreadcrumbs :items="breadcumItems" class="github-breadcrumbs">
          <template #divider>
            <VIcon size="16" color="text-disabled">tabler-chevron-right</VIcon>
          </template>
        </VBreadcrumbs>
        <VBtn
          v-if="projectData"
          color="primary"
          size="small"
          prepend-icon="tabler-plus"
          class="github-btn"
          @click="isAddBoardDialogVisible = true"
        >
          New Board
        </VBtn>
      </div>
    </div>

    <!-- Content Section -->
    <div v-if="projectData" class="content-section">
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
    </div>
  </div>
</template>

<style lang="scss" scoped>
.project-view {
  .header-section {
    background: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1.5rem;

    .breadcrumbs-wrapper {
      display: flex;
      justify-content: space-between;
      align-items: center;

      :deep() {
        .v-breadcrumbs-item {
          color: #57606a;
          font-size: 0.875rem;
        }

        .v-breadcrumbs-item--disabled {
          color: #24292f;
          font-weight: 600;
        }
      }

      .github-btn {
        background: #2da44e;
        
        &:hover {
          background: #2c974b;
        }
      }
    }
  }

  .content-section {
    padding: 0.5rem;
  }
}
</style>
