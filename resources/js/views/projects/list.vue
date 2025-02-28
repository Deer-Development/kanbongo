<script setup>
import CreateProject from "@/views/projects/dialogs/CreateProject.vue"
import EditProject from "@/views/projects/dialogs/EditProject.vue"
import { router } from "@/plugins/1.router/index"
import { watch } from "vue"

const searchQuery = ref('')

const itemsPerPage = ref(25)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const selectedItem = ref(null)
const isEditModalVisible = ref(false)
const isAddModalVisible = ref(false)
const isDeleteModalVisible = ref(false)
const statusFilter = ref('all')

const {
  data: data,
  execute: fetch,
} = await useApi(createUrl('/project', {
  method: 'GET',
  query: {
    q: searchQuery,
    status: statusFilter,
    itemsPerPage,
    page,
    sortBy,
    orderBy,
  },
}))

const items = computed(() => data.value.items)
const isSuperAdmin = computed(() => data.value.isSuperAdmin)
const userData = computed(() => useCookie('userData', { default: null }).value)

const editItem = item => {
  selectedItem.value = item
  isEditModalVisible.value = true
}

const itemToDelete = item => {
  selectedItem.value = item
  isDeleteModalVisible.value = true
}

const deleteItem = async () => {
  try {
    await $api(`/project/${selectedItem.value.id}`, {
      method: 'DELETE',
    })

    await fetch()
    isDeleteModalVisible.value = false
    selectedItem.value = null
  } catch (err) {
    console.error(err)
  }
}

const goToProject = item => {
  if (item.is_active) {
    // Prevent any race conditions by using nextTick
    nextTick(() => {
      router.push({ 
        name: 'project-view', 
        params: { id: item.id } 
      })
    })
  }
}

watch(statusFilter, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    fetch()
  }
})
</script>

<template>
  <div class="projects-list">
    <!-- Header Section -->
    <div class="header-section">
      <div class="search-bar">
        <VIcon size="20" color="text-medium-emphasis" class="search-icon">tabler-search</VIcon>
        <AppTextField
          v-model="searchQuery"
          placeholder="Search projects..."
          variant="plain"
          hide-details
          class="github-input"
        />
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
          color="primary"
          size="small"
          prepend-icon="tabler-plus"
          class="github-btn"
          @click="isAddModalVisible = true"
        >
          New Project
        </VBtn>
      </div>
    </div>

    <!-- Projects Grid -->
    <div class="projects-grid">
      <div
        v-for="item in items"
        :key="item.id"
        class="project-card cursor-pointer"
        :class="{ 'is-inactive': !item.is_active }"
        @click="goToProject(item)"
      >
        <div class="card-header">
          <div class="header-content">
            <div class="status-dot" :class="{ 'active': item.is_active }"></div>
            <h3 class="project-name text-truncate">{{ item.name }}</h3>
          </div>
          <div class="status-badge" :class="item.is_active ? 'active' : 'inactive'">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </div>
        </div>

        <div class="card-content">
          <div class="stats-grid">
            <div class="stat-item">
              <VIcon size="16" color="primary">tabler-layout-board</VIcon>
              <span>{{ item.stats.boards_count }} boards</span>
            </div>
            <div class="stat-item">
              <VIcon size="16" color="success">tabler-checklist</VIcon>
              <span>{{ item.stats.total_tasks }} tasks</span>
            </div>
          </div>
        </div>

        <div class="card-spacer"></div>

        <div v-if="isSuperAdmin || item.owner.id === userData.id" class="card-actions">
          <VBtn
            variant="text"
            size="small"
            color="primary"
            class="action-btn"
            prepend-icon="tabler-edit"
            @click.stop="editItem(item)"
          >
            Edit
          </VBtn>
          <VBtn
            variant="text"
            size="small"
            color="error"
            class="action-btn"
            prepend-icon="tabler-trash"
            @click.stop="itemToDelete(item)"
          >
            Delete
          </VBtn>
        </div>
      </div>
    </div>

    <CreateProject v-model:isDialogVisible="isAddModalVisible" @created="fetch" />
    <EditProject v-model:isDialogVisible="isEditModalVisible" :container="selectedItem" @updated="fetch" />
    <ConfirmDialog
      v-model:isDialogVisible="isDeleteModalVisible"
      cancel-title="Cancel"
      confirm-title="Delete project"
      confirm-msg="This project and all its data will be permanently deleted."
      confirmation-question="Are you sure you want to delete this project?"
      cancel-msg="Operation cancelled"
      @confirm="confirmed => confirmed && deleteItem()"
    />
  </div>
</template>

<style lang="scss" scoped>
.projects-list {
  .header-section {
    background: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1.5rem;

    .search-bar {
      display: flex;
      align-items: center;
      gap: 1rem;

      .search-icon {
        color: #57606a;
      }

      .github-input {
        flex: 1;
        
        :deep(.v-field) {
          border-radius: 6px;
          background: #f6f8fa;
          
          &:hover {
            background: #f3f4f6;
          }
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

  .projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }

  .project-card {
    display: flex;
    flex-direction: column;
    height: 160px;
    
    .card-header {
      flex: none;
      padding: 1rem;
      border-bottom: 1px solid #d0d7de;
      display: flex;
      justify-content: space-between;
      align-items: center;

      .header-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
        flex: 1;

        .status-dot {
          width: 8px;
          height: 8px;
          border-radius: 50%;
          background: #cf222e;

          &.active {
            background: #2da44e;
          }
        }

        .project-name {
          font-size: 0.875rem;
          font-weight: 600;
          color: #24292f;
          margin: 0;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          max-width: 180px;
        }
      }

      .status-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        margin-left: 0.75rem;
        flex-shrink: 0;

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
      padding: 0.75rem 1rem;
      
      .stats-grid {
        display: flex;
        gap: 1.5rem;
        
        .stat-item {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          font-size: 0.75rem;
          color: #57606a;

          .v-icon {
            opacity: 0.8;
          }

          span {
            white-space: nowrap;
            font-weight: 500;
          }
        }
      }
    }

    .card-spacer {
      flex: 1;
      min-height: 0;
    }

    .card-actions {
      flex: none;
      padding: 0.75rem;
      display: flex;
      justify-content: flex-end;
      gap: 0.5rem;
      border-top: 1px solid #d0d7de;
      background: #f6f8fa;
      margin-top: auto;

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
</style>
