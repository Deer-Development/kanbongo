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
  router.push({ name: 'project-view', params: { id: item.id } })
}
</script>

<template>
  <section>
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
        lg="4"
      >
        <VCard @click="goToProject(item)">
          <VCardTitle>
            <VChip color="info">
              Owner: {{ item.owner.full_name }}
            </VChip>
          </VCardTitle>
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <h5 class="text-h6">
                  {{ item.name }}
                </h5>
                <div class="d-flex align-center text-center mt-1">
                  <RouterLink :to="{ name: 'project-view', params: { id: item.id } }">
                    <VIcon
                      icon="tabler-clipboard-list"
                      class="text-high-emphasis"
                    />
                    Access Project
                  </RouterLink>
                </div>
              </div>
              <div class="d-flex gap-4">
                <VBtn
                  v-if="isSuperAdmin || item.owner.id === userData.id"
                  icon
                  size="x-small"
                  color="error"
                  @click="itemToDelete(item)"
                >
                  <VIcon
                    icon="tabler-trash"
                    size="14"
                  />
                </VBtn>
                <VBtn
                  v-if="isSuperAdmin || item.owner.id === userData.id"
                  icon
                  size="x-small"
                  @click="editItem(item)"
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
      cancel-title="Cancel"
      confirm-title="Delete!"
      confirm-msg="Project deleted permanently."
      confirmation-question="Are you sure to delete this Project?"
      cancel-msg="Delete action cancelled."
      @confirm="confirmed => confirmed && deleteItem()"
    />
  </section>
</template>
