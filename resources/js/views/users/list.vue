<script setup>
import AddNewUserDrawer from '@/views/users/AddNewUserDrawer.vue'
import EditUserDrawer from "@/views/users/EditUserDrawer.vue"

const searchQuery = ref('')
const selectedRole = ref()
const selectedPlan = ref()
const selectedStatus = ref()

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const selectedRows = ref([])
const selectedUser = ref(null)
const deleteDialog = ref(false)
const addNewUser = ref(null)

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const headers = [
  {
    title: 'User',
    key: 'user',
  },
  {
    title: 'Status',
    key: 'status',
  },
  {
    title: 'From Registration',
    key: 'from_registration',
  },
  {
    title: 'Actions',
    key: 'actions',
    sortable: false,
  },
]

const {
  data: usersData,
  execute: fetchUsers,
} = await useApi(createUrl('/user', {
  method: 'GET',
  query: {
    q: searchQuery,
    status: selectedStatus,
    plan: selectedPlan,
    role: selectedRole,
    itemsPerPage,
    page,
    sortBy,
    orderBy,
  },
}))

const users = computed(() => usersData.value.items)
const totalUsers = computed(() => usersData.value.totalItems)

const status = [
  {
    title: 'Active',
    value: 'active',
  },
  {
    title: 'Inactive',
    value: 'inactive',
  },
]

const resolveUserStatusVariant = stat => {
  if (stat === true)
    return 'success'
  if (stat === 0)
    return 'secondary'

  return 'primary'
}

const isAddNewUserDrawerVisible = ref(false)
const isEditUserDrawerVisible = ref(false)

const editUser = async id => {
  selectedUser.value = id
  isEditUserDrawerVisible.value = true
}

const deleteUser = async id => {
  deleteDialog.value = true
}

const closeDelete = () => {
  deleteDialog.value = false
}

const confirmDelete = async id => {
  try {
    await $api(`/user/${id}`, {
      method: 'DELETE',
    })

    deleteDialog.value = false
    await fetchUsers()
  } catch (error) {
    console.error(error)
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
                { value: -1, title: 'All' },
              ]"
              style="inline-size: 6.25rem;"
              @update:model-value="itemsPerPage = parseInt($event, 10)"
            />
          </VCol>
          <VCol
            cols="6"
            sm="2"
          >
            <AppSelect
              v-model="selectedStatus"
              placeholder="Select Status"
              :items="status"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          <VCol
            cols="6"
            sm="8"
          >
            <AppTextField
              v-model="searchQuery"
              placeholder="Search..."
            />
          </VCol>
          <VCol
            cols="6"
            sm="1"
            class="d-flex justify-end"
          >
            <VBtn @click="isAddNewUserDrawerVisible = true">
              Invite
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:model-value="selectedRows"
        v-model:page="page"
        :items="users"
        item-value="id"
        :items-length="totalUsers"
        :headers="headers"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.user="{ item }">
          <div class="d-flex align-center gap-x-4">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.name }}
              </h6>
              <div class="text-sm">
                {{ item.email }}
              </div>
            </div>
          </div>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="resolveUserStatusVariant(item.is_active)"
            size="small"
            label
            class="text-capitalize"
          >
            {{ item.is_active ? 'active' : 'inactive' }}
          </VChip>
        </template>

        <template #item.from_registration="{ item }">
          <VChip
            :color="resolveUserStatusVariant(item.from_registration)"
            size="small"
            label
            class="text-capitalize"
          >
            {{ item.from_registration ? 'yes' : 'no' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <VBtn
            icon="tabler-trash"
            class="me-2"
            variant="tonal"
            size="x-small"
            color="error"
            rounded
            @click="deleteUser"
          />

          <!--          <VBtn -->
          <!--            icon="tabler-edit" -->
          <!--            variant="tonal" -->
          <!--            size="x-small" -->
          <!--            color="primary" -->
          <!--            rounded -->
          <!--            @click="editUser(item.id)" -->
          <!--          /> -->

          <VDialog
            v-model="deleteDialog"
            max-width="500px"
          >
            <VCard title="Are you sure you want to delete this user?">
              <VCardText>
                <div class="d-flex justify-center gap-4">
                  <VBtn
                    color="error"

                    variant="outlined"
                    @click="closeDelete"
                  >
                    Cancel
                  </VBtn>
                  <VBtn
                    color="success"
                    variant="elevated"
                    @click="confirmDelete(item.id)"
                  >
                    OK
                  </VBtn>
                </div>
              </VCardText>
            </VCard>
          </VDialog>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalUsers"
          />
        </template>
      </VDataTableServer>
    </VCard>
    <AddNewUserDrawer
      v-model:isDrawerOpen="isAddNewUserDrawerVisible"
      @user-data="addNewUser"
      @user-updated="fetchUsers"
    />
    <EditUserDrawer
      v-model:isDrawerOpen="isEditUserDrawerVisible"
      :selected-user="selectedUser"
      @user-data="addNewUser"
      @user-updated="fetchUsers"
    />
  </section>
</template>
