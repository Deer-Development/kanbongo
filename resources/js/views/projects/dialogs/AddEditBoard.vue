<script setup>
import { VForm } from 'vuetify/components/VForm'
import { defineProps, nextTick, ref, watch, onMounted } from 'vue'
import { useToast } from "vue-toastification"

const props = defineProps({
  boardDetails: {
    type: Object,
    required: false,
    default: () => ({
      name: '',
      is_active: true,
      members: [],
    }),
  },
  projectId: {
    type: Number,
    required: true,
    default: null,
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'update:boardDetails',
  'formSubmitted',
])

const name = ref('')
const toast = useToast()
const searchQuery = ref('')
const first_name = ref('')
const last_name = ref('')
const email = ref('')
const emailError = ref('')
const selectedUser = ref(null)
const boardDataLocal = ref(null)
const isActive = ref(true)
const isFormValid = ref(false)
const refAddUserForm = ref()
const isAddUserFormValid = ref(false)
const refBoardForm = ref()
const members = ref([])
const excludedUsers = ref([])
const usersOptions = ref([])
const userData = computed(() => useCookie('userData', { default: null }).value)
const isInviteDialogVisible = ref(false)

const fetchUsersOptions = async () => {
  const memberIds = members.value.map(member => member.user_id).join(',')

  const { data } = await useApi(
    createUrl('/user/options', {
      method: 'GET',
      query: {
        q: searchQuery.value,
        exclude_ids: memberIds,
      },
    }),
  )

  usersOptions.value = data.value

  if(!props.boardDetails?.id){
    addUser(userData.value.id)
  }
}

const openInviteDialog = () => {
  isInviteDialogVisible.value = true
}

watch(() => props.isDialogVisible, value => {
  const data = JSON.parse(JSON.stringify(props.boardDetails))

  boardDataLocal.value = data

  name.value = data.name
  isActive.value = !!data.is_active
  members.value = data.members.map(member => ({
    id: member.id,
    user_id: member.user.id,
    name: member.user.full_name,
    email: member.user.email,
    avatar: member.user.avatar,
    role: member.user.role,
    avatarOrInitials: member.user.avatar_or_initials,
    can_timing: member.can_timing,
    billable: member.billable,
    rate: member.billable_rate,
    weekly_limit_enabled: member.weekly_limit_enabled,
    weekly_limit_hours: member.weekly_limit_hours,
  }))

  fetchUsersOptions()
})

const addUser = userId => {
  const user = usersOptions.value.find(option => option.id === userId)

  if (!user) {
    return
  }

  members.value.push({
    user_id: user.id,
    avatarOrInitials: user.avatarOrInitials,
    name: user.name,
    email: user.email,
    avatar: user.avatar,
    role: user.role,
    can_timing: false,
    billable: false,
    rate: 0,
    weekly_limit_enabled: false,
    weekly_limit_hours: null,
  })

  excludedUsers.value.push({
    id: user.id,
  })

  selectedUser.value = null

  fetchUsersOptions()
}

const removeMember = member => {
  const index = members.value.findIndex(m => m.id === member.id)
  if (index !== -1) {
    members.value.splice(index, 1)[0]
    fetchUsersOptions()
  }
}

const onSubmit = async () => {
  try {
    const method = boardDataLocal.value?.id ? 'PUT' : 'POST'

    const endpoint = boardDataLocal.value?.id
      ? `/container/${boardDataLocal.value.id}`
      : `/container`

    const res = await $api(endpoint, {
      method,
      body: {
        name: name.value,
        is_active: !!isActive.value,
        members: members.value.map(member => ({
          user_id: member.user_id,
          can_timing: member.can_timing,
          billable: member.billable,
          billable_rate: member.rate,
          weekly_limit_enabled: member.weekly_limit_enabled,
          weekly_limit_hours: member.weekly_limit_enabled ? Number(member.weekly_limit_hours) : null,
        })),
        project_id: props.projectId,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors
      },
    })

    if (res) {
      toast.success(
        `Board ${boardDataLocal.value?.id ? 'updated' : 'added'} successfully!`,
      )
    }

    onReset()

    await nextTick(() => {
      emit('formSubmitted')
    })
  } catch (err) {
    console.error(err)
  }
}

const submitForm = () => {
  refBoardForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid)
      onSubmit()
  })
}

const sendInvite = () => {
  refAddUserForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid)
      applyInvite()
  })
}

const applyInvite = async () => {
  try {
    const response = await $api('/user', {
      method: 'POST',
      body: {
        first_name: first_name.value,
        last_name: last_name.value,
        email: email.value,
      },
      onResponseError({ response }) {
        emailError.value = 'Email already exists'
      },
    })

    if (response?.data) {
      members.value.push({
        user_id: response.data.id,
        name: response.data.name,
        email: response.data.email,
        avatar: response.data.avatar,
        avatarOrInitials: response.data.avatarOrInitials,
        role: response.data.role,
        can_timing: false,
        billable: false,
        rate: 0,
      })

      toast.success('User added successfully!')
      isInviteDialogVisible.value = false

      first_name.value = ''
      last_name.value = ''
      email.value = ''
      emailError.value = ''

      await fetchUsersOptions()
    }
  } catch (err) {
    toast.error('Something went wrong!')
  }
}

const onReset = () => {
  emit('update:isDialogVisible', false)
  refBoardForm.value?.resetValidation()
  refAddUserForm.value?.resetValidation()
}

onMounted(() => {
  fetchUsersOptions()
})
</script>

<template>
  <div>
    <VDialog
      :width="$vuetify.display.smAndDown ? 'auto' : 1200"
      :model-value="props.isDialogVisible"
      @update:model-value="onReset"
    >
      <DialogCloseBtn @click="onReset" />
      <VCard class="pa-sm-10 pa-2">
        <VCardText>
          <h4 class="text-h4 text-center mb-2">
            {{ props.boardDetails.name ? 'Edit' : 'Add New' }} Board {{ props.boardDetails.name }}
          </h4>
          <p class="text-body-1 text-center mb-6">
            Set Your Board Details
          </p>

          <VForm
            ref="refBoardForm"
            v-model="isFormValid"
            @submit.prevent="submitForm"
          >
            <VSwitch
              v-model="isActive"
              label="Active"
            />

            <AppTextField
              v-model="name"
              label="Board Name"
              :rules="[requiredValidator]"
              placeholder="Enter Board Name"
            />

            <h5 class="text-h5 mt-5 mb-1">
              Board Members
            </h5>

            <AppAutocomplete
              v-model="selectedUser"
              v-model:items="usersOptions"
              chips
              item-text="name"
              item-value="id"
              clearable
              label="Select User"
              no-filter
              autocomplete="off"
              @update:model-value="addUser"
            >
              <template #chip="{ props, item }">
                <VChip
                  v-bind="props"
                  :text="item.raw.name"
                  class="gap-2 py-2 px-2"
                >
                  <template #prepend>
                    <VAvatar
                      size="22"
                      :color="$vuetify.theme.current.dark ? '#373B50' : '#631eed'"
                    >
                      <VImg
                        v-if="item.raw?.avatar"
                        :src="item.raw?.avatar"
                      />
                      <template v-else>
                        <span class="text-xs font-weight-bold px-2 py-2">{{ item.raw?.avatarOrInitials }}</span>
                      </template>
                    </VAvatar>
                  </template>
                </VChip>
              </template>
              <template #item="{ props, item }">
                <VListItem
                  v-bind="props"
                  :title="item?.raw?.name"
                  :subtitle="`Email: ${item?.raw?.email}`"
                >
                  <template #prepend>
                    <VAvatar
                      size="26"
                      :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
                    >
                      <VImg
                        v-if="item.raw?.avatar"
                        :src="item.raw?.avatar"
                      />
                      <template v-else>
                        <span class="text-xs font-weight-medium">{{ item.raw?.avatarOrInitials }}</span>
                      </template>
                    </VAvatar>
                  </template>
                </VListItem>
              </template>
              <template #append>
                <VChip
                  icon
                  color="success"
                  variant="elevated"
                  @click="openInviteDialog"
                >
                  <VIcon
                    icon="tabler-user-plus"
                  />
                </VChip>
              </template>
              <template #no-data>
                <VListItem>
                  <VListItemTitle>
                    No data found
                  </VListItemTitle>
                </VListItem>
              </template>
              <template #loading>
                <VListItem>
                  <VListItemTitle>
                    Loading...
                  </VListItemTitle>
                </VListItem>
              </template>
            </AppAutocomplete>
            <VTable class="permission-table text-no-wrap mb-6 mt-4">
              <template
                v-for="member in members"
                :key="member.user_id"
              >
                <tr>
                  <td>
                    <div class="d-flex align-center gap-x-4">
                      <VAvatar
                        size="36"
                        :color="$vuetify.theme.current.dark ? '#373B50' : '#631eed'"
                      >
                        <VImg
                          v-if="member.avatar"
                          :src="member.avatar"
                        />
                        <template v-else>
                          <span class="text-xs font-weight-bold px-2 py-2">{{ member.avatarOrInitials }}</span>
                        </template>
                      </VAvatar>
                      <div class="d-flex flex-column">
                        <h6 class="text-base">
                          {{ member.name }}
                          <VChip
                            v-if="member.role && member.role === 'Super-Admin'"
                            size="x-small"
                            color="info"
                          >
                            SA
                          </VChip>
                        </h6>
                        <div class="text-sm">
                          {{ member.email }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex justify-end">
                      <VCheckbox
                        v-model="member.can_timing"
                        label="Can Timing"
                      />
                    </div>
                  </td>
                  <td>
                    <div class="d-flex justify-end">
                      <VTextField
                        v-model="member.rate"
                        label="Rate"
                        type="number"
                        min="0"
                      />
                    </div>
                  </td>
                  <td>
                    <div class="d-flex justify-end">
                      <VCheckbox
                        v-model="member.weekly_limit_enabled"
                        label="Weekly Limit"
                      />
                    </div>
                  </td>
                  <td>
                    <div class="d-flex justify-end">
                      <VTextField
                        v-model="member.weekly_limit_hours"
                        label="Limit"
                        type="number"
                        :rules="[member.weekly_limit_enabled ? requiredValidator : () => true]"
                        step="0.01"
                        min="0"
                        :disabled="!member.weekly_limit_enabled"
                      />
                    </div>
                  </td>
                  <td>
                    <div
                      v-if="(!boardDataLocal && userData.id === member.user_id) || (boardDataLocal && boardDataLocal.owner_id === member.user_id)"
                      class="d-flex justify-end"
                    >
                      <VChip
                        icon
                        size="x-small"
                        color="warning"
                      >
                        <VIcon
                          size="12"
                          icon="tabler-user"
                        /> Owner
                      </VChip>
                    </div>
                    <div
                      v-else
                      class="d-flex justify-end"
                    >
                      <VBtn
                        icon
                        size="18"
                        color="error"
                        @click="removeMember(member)"
                      >
                        <VIcon
                          size="12"
                          icon="tabler-x"
                        />
                      </VBtn>
                    </div>
                  </td>

                </tr>
              </template>
            </VTable>
            <div class="d-flex align-center justify-center gap-4">
              <VBtn type="submit">
                Submit
              </VBtn>

              <VBtn
                color="secondary"
                variant="tonal"
                @click="onReset"
              >
                Cancel
              </VBtn>
            </div>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>
    <VDialog v-model="isInviteDialogVisible" width="500">
      <VCard class="pa-6">
        <VCardTitle class="text-h5 text-center"> Invite new user </VCardTitle>
        <VCardText>
          <VForm
            ref="refAddUserForm"
            v-model="isAddUserFormValid"
            @submit.prevent="sendInvite"
          >
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="first_name"
                  label="First Name"
                  placeholder="Tzvi"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="last_name"
                  label="Last Name"
                  placeholder="Gettenberg"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="email"
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="emailError"
                  label="Email"
                  placeholder="tzvi@email.com"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Submit
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  @click="isInviteDialogVisible = false"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<style lang="scss">
.permission-table {
  td {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.5rem;

    .v-checkbox {
      min-inline-size: 4.75rem;
    }

    .v-input {
      min-inline-size: 6.75rem;
    }

    &:not(:first-child) {
      padding-inline: 0.5rem;
    }

    .v-label {
      white-space: nowrap;
    }
  }
}
</style>
