<script setup>
import CreateProject from "@/views/projects/dialogs/CreateProject.vue"
import EditProject from "@/views/projects/dialogs/EditProject.vue"

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

const headers = [
  {
    title: 'Name',
    key: 'name',
  },
  {
    title: 'Is Active',
    key: 'is_active',
  },
  {
    title: 'Created At',
    key: 'created_at',
  },
  {
    title: 'Actions',
    key: 'actions',
    sortable: false,
  },
]

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
const totalItems = computed(() => data.value.totalItems)
const isSuperAdmin = computed(() => data.value.isSuperAdmin)

const status = [
  {
    title: 'Active',
    value: true,
  },
  {
    title: 'Inactive',
    value: false,
  },
]

const resolveStatusVariant = stat => {
  if (stat === true)
    return 'success'
  if (stat === false)
    return 'error'

  return 'primary'
}

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
    await $api(`/container/${selectedItem.value.id}`, {
      method: 'DELETE',
    })

    await fetch()
    isDeleteModalVisible.value = false
    selectedItem.value = null
  } catch (err) {
    console.error(err)
  }
}
</script>

<template>
  <section>
    <VCard class="mb-6">
      <VCardText class="d-flex flex-wrap gap-4">
        <VRow>
          <VCol
            cols="6"
            sm="1"
          >
            <AppSelect
              :model-value="itemsPerPage"
              :items="[
                { value: 10, title: '10' },
                { value: 25, title: '25' },
                { value: 50, title: '50' },
                { value: 100, title: '100' },
              ]"
              style="inline-size: 6.25rem;"
              @update:model-value="itemsPerPage = parseInt($event, 10)"
            />
          </VCol>
          <VCol
            cols="6"
            sm="3"
          >
            <AppSelect
              v-model="selectedStatus"
              placeholder="Is Active"
              :items="status"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          <VCol
            cols="6"
            sm="4"
          >
            <AppTextField
              v-model="searchQuery"
              placeholder="Search..."
            />
          </VCol>
          <VCol
            v-if="isSuperAdmin"
            cols="6"
            sm="1"
            class="d-flex justify-end"
          >
            <VBtn
              color="primary"
              @click="isAddModalVisible = true"
            >
              Add New
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="items"
        item-value="id"
        :items-length="totalItems"
        :headers="headers"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.name }}
              </h6>
            </div>
          </div>
        </template>

        <template #item.is_active="{ item }">
          <VChip
            :color="resolveStatusVariant(item.is_active)"
            size="small"
            label
            class="text-capitalize"
          >
            {{ item.is_active ? 'yes' : 'no' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-2">
            <VBtn
              v-tooltip:top="'View Containers'"
              icon="tabler-layout-kanban"
              variant="tonal"
              size="x-small"
              color="info"
              rounded
              :to="{ name: 'project-view', params: { id: item.id } }"
            />
            <VBtn
              v-if="isSuperAdmin"
              v-tooltip:top="'Edit'"
              icon="tabler-edit"
              variant="tonal"
              size="x-small"
              color="primary"
              rounded
              @click="editItem(item)"
            />
            <VBtn
              v-if="isSuperAdmin"
              v-tooltip:top="'Delete'"
              icon="tabler-trash"
              variant="tonal"
              size="x-small"
              color="error"
              rounded
              @click="itemToDelete(item)"
            />
          </div>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalItems"
          />
        </template>
      </VDataTableServer>
    </VCard>
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
      confirm-msg="Container deleted permanently."
      confirmation-question="Are you sure to delete this Container?"
      cancel-msg="Delete action cancelled."
      @confirm="confirmed => confirmed && deleteItem()"
    />
  </section>
</template>
