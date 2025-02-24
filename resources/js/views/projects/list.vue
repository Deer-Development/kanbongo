<script setup>
import CreateProject from "@/views/projects/dialogs/CreateProject.vue"
import EditProject from "@/views/projects/dialogs/EditProject.vue"
import { router } from "@/plugins/1.router/index"

const searchQuery = ref('')
const selectedStatus = ref()

const itemsPerPage = ref(25)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const selectedItem = ref(null)
const isEditModalVisible = ref(false)
const isAddModalVisible = ref(false)
const isDeleteModalVisible = ref(false)

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const {
  data: data,
  execute: fetch,
} = await useApi(createUrl('/project', {
  method: 'GET',
  query: {
    q: searchQuery,
    is_active: selectedStatus,
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
  if(item.is_active)
    router.push({ name: 'project-view', params: { id: item.id } })
}
</script>

<template>
  <div>
    <VCard class="mb-2">
      <VCardText>
        <div class="d-flex gap-4 justify-space-between">
          <div class="w-100">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search..."
            />
          </div>
          <div class="d-flex align-self-end">
            <VBtn
              color="primary"
              @click="isAddModalVisible = true"
            >
              Add New
            </VBtn>
          </div>
        </div>
      </VCardText>
    </VCard>
    <VRow class="my-2">
      <VCol
        v-for="item in items"
        :key="item.id"
        cols="12"
        sm="6"
        lg="3"
      >
        <VCard
          class="elegant-card"
          elevation="3"
          :class="{
            'cursor-pointer': item.is_active,
            'cursor-not-allowed': !item.is_active,
          }"
          @click="goToProject(item)"
        >
          <VCardTitle class="card-header">
            <VChip
              color="primary"
              variant="elevated"
              class="chip-title"
            >
              {{ item.name }}
            </VChip>
            <div class="custom-badge">
              <VIcon
                :icon="item.is_active ? 'tabler-circle-check' : 'tabler-circle-x'"
                :color="item.is_active ? 'success' : 'error'"
                size="18"
              />
              <span>{{ item.is_active ? 'Active' : 'Inactive' }}</span>
            </div>
          </VCardTitle>

          <VCardText
            v-if="isSuperAdmin || item.owner.id === userData.id"
            class="card-content"
          >
            <div class="d-flex justify-space-between align-center">
              <div class="d-flex gap-2">
                <VBtn
                  v-if="isSuperAdmin || item.owner.id === userData.id"
                  icon
                  size="x-small"
                  color="error"
                  variant="text"
                  @click.stop="itemToDelete(item)"
                >
                  <VIcon
                    icon="tabler-trash"
                    size="16"
                  />
                </VBtn>
                <VBtn
                  v-if="isSuperAdmin || item.owner.id === userData.id"
                  icon
                  size="x-small"
                  color="info"
                  variant="text"
                  @click.stop="editItem(item)"
                >
                  <VIcon
                    icon="tabler-edit"
                    size="16"
                  />
                </VBtn>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    <CreateProject
      v-model:isDialogVisible="isAddModalVisible"
      @created="fetch"
    />
    <EditProject
      v-model:isDialogVisible="isEditModalVisible"
      :container="selectedItem"
      @updated="fetch"
    />
    <ConfirmDialog
      v-model:isDialogVisible="isDeleteModalVisible"
      cancel-title="❌ Cancel"
      confirm-title="⚠️ Delete Forever!"
      confirm-msg="🚨 Project and all its data have been permanently deleted! ⛔️ This action cannot be undone."
      confirmation-question="⚠️ Are you sure you want to delete this project? 🚨 All tracked hours, tasks, and data will be lost forever!"
      cancel-msg="✅ Delete action cancelled. Your project is safe!"
      @confirm="confirmed => confirmed && deleteItem()"
    />
  </div>
</template>

<style scoped>
.elegant-card {
  border-radius: 12px;
  transition: transform 0.2s ease-in-out, box-shadow 0.3s ease-in-out;
}

.elegant-card:hover {
  transform: translateY(-3px);
  box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  font-weight: bold;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.chip-title {
  font-size: 14px;
  font-weight: bold;
}

.card-content {
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
