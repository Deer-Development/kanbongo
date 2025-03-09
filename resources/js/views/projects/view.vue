<script setup>
import ContainersCards from '@/views/projects/components/ContainersCards.vue'
import AddEditBoard from "@/views/projects/dialogs/AddEditBoard.vue"
import { useToast } from 'vue-toastification'

const route = useRoute()
const projectData = ref(null)
const isSuperAdmin = ref(false)
const boardDetails = ref()
const statusFilter = ref('all')
const toast = useToast()

const fetchContainer = async () => {
  const { data } = await $api(`/project/${route.params.id}`,
    {
      method: 'GET',
      query: {
        status: statusFilter.value,
      },
    }
  )

  projectData.value = data.project
  isSuperAdmin.value = data.isSuperAdmin
}

const deleteContainer = async (boardId) => {
  const { data } = await $api(`/container/${boardId}`, {
    method: 'DELETE',
  })

  nextTick(() => {  
    fetchContainer()
  })
}

const handleMoveContainer = async (data) => {
  console.log(data)
  try {
    const response = await $api(`/container/${data.boardId}/move`, {
      method: 'POST',
      body: {
        target_project_id: data.targetProjectId
      }
    })
    
    if (response) {
      fetchContainer()
    }
  } catch (error) {
    console.error('Error moving container:', error)
  }
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

watch(statusFilter, () => {
  fetchContainer()
})

onMounted(() => {
  fetchContainer()
})
</script>

<template>
  <div class="project-view">
    <!-- Header Section -->
    <div class="header-section">
      <div class="header-content">
        <div class="breadcrumbs-section">
          <VBreadcrumbs :items="breadcumItems" class="github-breadcrumbs">
            <template #divider>
              <VIcon size="16" color="text-disabled">tabler-chevron-right</VIcon>
            </template>
          </VBreadcrumbs>
        </div>
        <div class="actions-section">
          <VBtnGroup 
            class="btn-group-status-filter"
            density="compact"
          >
            <VBtn
              :color="statusFilter === 'all' ? 'primary' : undefined"
              :variant="statusFilter === 'all' ? 'tonal' : 'outlined'"
              size="x-small"
              class="filter-btn"
              @click="statusFilter = 'all'"
            >
              <VIcon 
                size="14" 
                class="mr-1"
                :color="statusFilter === 'all' ? undefined : '#57606a'"
              >
                tabler-filter
              </VIcon>
              All
            </VBtn>
            <VBtn
              :color="statusFilter === 'active' ? 'success' : undefined"
              :variant="statusFilter === 'active' ? 'tonal' : 'outlined'"
              size="x-small"
              class="filter-btn"
              @click="statusFilter = 'active'"
            >
              <VIcon 
                size="14" 
                class="mr-1"
                :color="statusFilter === 'active' ? undefined : '#57606a'"
              >
                tabler-circle-check
              </VIcon>
              Active
            </VBtn>
            <VBtn
              :color="statusFilter === 'inactive' ? 'error' : undefined"
              :variant="statusFilter === 'inactive' ? 'tonal' : 'outlined'"
              size="x-small"
              class="filter-btn"
              @click="statusFilter = 'inactive'"
            >
              <VIcon 
                size="14" 
                class="mr-1"
                :color="statusFilter === 'inactive' ? undefined : 'error'"
              >
                tabler-circle-x
              </VIcon>
              Inactive
            </VBtn>
          </VBtnGroup>
          <VBtn
            v-if="projectData"
            color="primary"
            size="small"
            prepend-icon="tabler-plus"
            class="github-btn"
            @click="isAddBoardDialogVisible = true"
          >
            New
          </VBtn>
        </div>
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
        @delete-container="deleteContainer"
        @move-container="handleMoveContainer"
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

    .header-content {
      display: flex;
      flex-direction: column;
      gap: 1rem;

      @media (min-width: 768px) {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
      }

      .breadcrumbs-section {
        width: 100%;
        overflow-x: auto;

        :deep() {
          .v-breadcrumbs {
            flex-wrap: nowrap;
          }

          .v-breadcrumbs-item {
            color: #57606a;
            font-size: 0.875rem;
            white-space: nowrap;
          }

          .v-breadcrumbs-item--disabled {
            color: #24292f;
            font-weight: 600;
          }
        }
      }

      .actions-section {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        width: 100% !important;
        align-items: center;

        @media (min-width: 768px) {
          width: auto;
          justify-content: flex-end;
        }

        .btn-group-status-filter {
          max-width: 240px;
          @media (min-width: 768px) {
            flex: 0 0 auto;
          }
        }

        .github-btn {
          flex: 1;
          
          @media (min-width: 768px) {
            flex: 0 0 auto;
          }
        }
      }
    }
  }

  .content-section {
    padding: 0.5rem;
  }
}
</style>
