<script setup>
import ContainersCards from '@/views/projects/components/ContainersCards.vue'
import AddEditBoard from "@/views/projects/dialogs/AddEditBoard.vue"

const route = useRoute()
const projectData = ref(null)
const isSuperAdmin = ref(false)
const boardDetails = ref()
const statusFilter = ref('all')

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
      <div class="breadcrumbs-wrapper">
        <VBreadcrumbs :items="breadcumItems" class="github-breadcrumbs">
          <template #divider>
            <VIcon size="16" color="text-disabled">tabler-chevron-right</VIcon>
          </template>
        </VBreadcrumbs>
        <div class="d-flex gap-2 align-center">
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
            New Board
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
        height: 34px;
        min-width: 76px;
        border: none;
        border-radius: 6px;
        font-size: 0.8125rem;
        text-transform: none;
        letter-spacing: normal;
        font-weight: 600;
        padding: 0 12px;
        color: #24292f;
        
        &:not(:last-child) {
          border-right: 1px solid #d0d7de;
        }
        
        &:hover {
          background: #f3f4f6;
        }

        &.v-btn--variant-tonal {
          background: #ffffff;
          
          &:hover {
            opacity: 0.95;
          }

          &.text-primary {
            background: #ddf4ff;
          }

          &.text-success {
            background: #dafbe1;
          }

          &.text-warning {
            background: #fff8c5;
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
