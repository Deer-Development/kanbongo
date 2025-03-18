<script setup>
import { VForm } from 'vuetify/components/VForm'
import { defineProps, nextTick, ref, watch, onMounted, computed } from 'vue'
import { useToast } from "vue-toastification"
import { PaymentType } from '../../../enums/PaymentType'
import { SalaryPaymentType } from '../../../enums/SalaryPaymentType'

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
const emailError = ref('')
const selectedUser = ref(null)
const boardDataLocal = ref(null)
const memberToRemove = ref(null)
const myCombobox = ref()
const isActive = ref(true)
const isLoading = ref(false)
const isFormDirty = ref(false)
const isRemoveModalVisible = ref(false)
const refAddUserForm = ref()
const refBoardForm = ref()
const members = ref([])
const excludedUsers = ref([])
const usersOptions = ref([])
const temporaryUsersAdded = ref([])
const userData = computed(() => useCookie('userData', { default: null }).value)
const isHeaderHovered = ref(false)
const isSubmitting = ref(false)
const showBasicInfo = computed(() => !boardDataLocal.value?.id)

const isValidEmail = email => {
  const emailRegex = /^[^\s@]+@[^\s@][^\s.@]*\.[^\s@]+$/
  
  return emailRegex.test(email)
}

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
}

const addTemporaryUser = async () => {
  if (!searchQuery.value.trim()) return

  if (!isValidEmail(searchQuery.value.trim())) {
    emailError.value = 'Please enter a valid email address'
    selectedUser.value = null
    myCombobox.value.search = searchQuery.value

    return
  }

  emailError.value = null

  try {
    const response = await $api('/user', {
      method: 'POST',
      body: {
        email: searchQuery.value.trim(),
        is_temporary: true,
        invited_by: userData.value.id,
      },
    })

    if (response?.data) {
      members.value.push({
        user_id: response.data.id,
        name: response.data.name || response.data.email,
        email: response.data.email,
        avatar: response.data.avatar,
        avatarOrInitials: response.data.avatarOrInitials,
        role: response.data.role,
        is_temporary: true,
        can_timing: false,
        billable: false,
        billable_rate: 0,
        weekly_limit_enabled: false,
        weekly_limit_hours: null,
        is_admin: false,
        payment_type: PaymentType.HOURLY,
        salary: null,
        salary_payment_type: SalaryPaymentType.MONTHLY,
      })

      toast.success(`User ${response.data.email} invited successfully!`)

      selectedUser.value = null
      searchQuery.value = ''
      isFormDirty.value = true

      temporaryUsersAdded.value.push(response.data.id)
    }
  } catch (err) {
    selectedUser.value = null
    searchQuery.value = ''
    toast.error('Error creating user. This email already exists.')
  }
}

watch(() => props.isDialogVisible, async value => {
  const data = JSON.parse(JSON.stringify(props.boardDetails))

  boardDataLocal.value = data

  name.value = data.name
  isActive.value = !!data.is_active
  members.value = data.members.map(member => ({
    id: member.id,
    user_id: member.user.id,
    name: member.user.name,
    email: member.user.email,
    avatar: member.user.avatar,
    role: member.user.role,
    is_admin: member.is_admin,
    avatarOrInitials: member.user.avatarOrInitials,
    can_timing: member.can_timing,
    billable: member.billable,
    billable_rate: member.billable_rate,
    is_temporary: member.user.is_temporary,
    weekly_limit_enabled: member.weekly_limit_enabled,
    weekly_limit_hours: member.weekly_limit_hours,
    payment_type: member.payment_type,
    salary: member.salary,
    salary_payment_type: member.salary_payment_type,
  }))
})

watch(searchQuery, newQuery => {
  if (newQuery.length >= 3) {
    fetchUsersOptions()
  }
})

const addUser = (userId, isOwner = false) => {
  const user = usersOptions.value.find(option => option.id === userId.id)

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
    billable_rate: 0,
    weekly_limit_enabled: false,
    weekly_limit_hours: null,
    is_owner: isOwner,
    is_admin: false,
    payment_type: PaymentType.NO_PAYMENT,
    salary: null,
    salary_payment_type: SalaryPaymentType.MONTHLY,
  })

  excludedUsers.value.push({
    id: user.id,
  })

  selectedUser.value = null
  usersOptions.value = []
  isFormDirty.value = true
}

const openRemoveDialog = member => {
  isRemoveModalVisible.value = true
  memberToRemove.value = member
}

const removeMember = member => {
  const index = members.value.findIndex(m => m.id === member.id)
  if (index !== -1) {
    members.value.splice(index, 1)[0]
    isFormDirty.value = true
    memberToRemove.value = null
  }
}

const onSubmit = async () => {
  try {
    const { valid } = await refBoardForm.value?.validate()
    
    if (!valid) return
    
    isSubmitting.value = true
    
    const formData = {
      name: name.value,
      is_active: isActive.value,
      project_id: props.projectId,
      members: boardDataLocal.value?.id ? members.value : [],
      owner_id: boardDataLocal.value?.id ? boardDataLocal.value.owner_id : userData.value.id,
    }

    const res = await $api(
      boardDataLocal.value?.id
        ? `/container/${boardDataLocal.value.id}`
        : '/container',
      {
        method: boardDataLocal.value?.id ? 'PUT' : 'POST',
        body: formData,
      },
    )

    if (res) {
      toast.success(
        `Board ${boardDataLocal.value?.id ? 'updated' : 'created'} successfully!`,
      )
      temporaryUsersAdded.value = []
      onReset()

      await nextTick(() => {
        emit('formSubmitted')
      })
    }
  } catch (err) {
    console.error(err)
    toast.error('Something went wrong. Please try again.')
  } finally {
    isSubmitting.value = false
  }
}

const onReset = () => {
  emit('update:isDialogVisible', false)
  refBoardForm.value?.resetValidation()
  refAddUserForm.value?.resetValidation()
  isFormDirty.value = false
  members.value = []
  excludedUsers.value = []
  usersOptions.value = []
  searchQuery.value = ''
  emailError.value = ''
  boardDataLocal.value = null

  if (temporaryUsersAdded.value.length) {
    temporaryUsersAdded.value.forEach(async userId => {
      await $api(`/user/${userId}`, { method: 'DELETE' })
    })
  }

  temporaryUsersAdded.value = []
}
</script>

<template>
  <div>
    <VDialog
      :model-value="isDialogVisible"
      :max-width="$vuetify.display.smAndDown ? '100%' : '1300'"
      :fullscreen="$vuetify.display.smAndDown"
      persistent
      class="board-dialog"
      @update:model-value="emit('update:isDialogVisible', $event)"
    >
      <VCard class="board-dialog-card">
        <VCardTitle class="dialog-header">
          <div class="d-flex align-center">
            <div class="icon-container">
              <span
                class="emoji"
                :class="{ 'is-hovered': isHeaderHovered }"
                @mouseenter="isHeaderHovered = true"
                @mouseleave="isHeaderHovered = false"
              >
                {{ isHeaderHovered ? '🎯' : '📋' }}
              </span>
            </div>
            <span class="title-text">{{ showBasicInfo ? 'Create Board' : 'Manage Members' }}</span>
          </div>
          <VBtn
            icon
            variant="text"
            size="small"
            @click="onReset"
          >
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <VForm
            ref="refBoardForm"
            validate-on="submit"
            @submit.prevent="onSubmit"
          >
            <div
              v-if="showBasicInfo"
              class="form-section"
            >
              <div class="section-header">
                <VIcon
                  icon="tabler-info-circle"
                  size="18"
                  class="me-2"
                />
                <span class="text-h6">Basic Information</span>
              </div>

              <VTextField
                v-model="name"
                label="Board Name"
                placeholder="Enter board name"
                :rules="[
                  v => !!v || 'Board name is required',
                  v => v.length >= 3 || 'Name must be at least 3 characters'
                ]"
                variant="outlined"
                density="comfortable"
                class="mb-4"
              >
                <template #prepend-inner>
                  <VIcon
                    icon="tabler-layout-board"
                    size="18"
                  />
                </template>
              </VTextField>

              <VSwitch
                v-model="isActive"
                label="Board Status"
                color="success"
                :true-value="true"  
                :false-value="false"
                hide-details
              >
                <template #label>
                  <span class="d-flex align-center">
                    Active
                    <VChip
                      :color="isActive ? 'success' : 'warning'"
                      size="x-small"
                      class="ms-2"
                    >
                      {{ isActive ? 'Active' : 'Inactive' }}
                    </VChip>
                  </span>
                </template>
              </VSwitch>
            </div>

            <!-- Team Members Section -->
            <div
              class="form-section"
              :class="{ 'mt-6': showBasicInfo }"
            >
              <div class="section-header d-flex justify-space-between align-center">
                <div class="d-flex align-center">
                  <VIcon
                    icon="tabler-users"
                    size="18"
                    class="me-2"
                  />
                  <span class="text-h6">Team Members</span>
                </div>
                
                <VChip
                  v-if="members.length"
                  color="primary"
                  size="small"
                >
                  {{ members.length }} {{ members.length === 1 ? 'Member' : 'Members' }}
                </VChip>
              </div>

              <template v-if="boardDataLocal?.id">
                <AppCombobox
                  ref="myCombobox"
                  v-model="selectedUser"
                  v-model:search="searchQuery"
                  :items="usersOptions"
                  :loading="isLoading"
                  label="Add Members"
                  placeholder="Search by name or email"
                  variant="outlined"
                  density="comfortable"
                  :error-messages="emailError"
                  item-title="name"
                  item-value="id"
                  return-object
                  clearable
                  @update:search="searchQuery = $event"
                  @keydown.enter="addTemporaryUser"
                  @update:model-value="addUser($event)"
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
                  <template #no-data>
                    <VListItem>
                      <VListItemTitle>
                        No users found. Press <strong>Enter</strong> to add "{{ searchQuery }}"
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
                </AppCombobox>

                <div
                  v-if="members.length"
                  class="members-list mt-6"
                >
                  <TransitionGroup name="member-list">
                    <div
                      v-for="member in members"
                      :key="member.user_id"
                      class="member-card"
                    >
                      <div class="member-info">
                        <VAvatar size="40">
                          <VImg
                            v-if="member.avatar"
                            :src="member.avatar"
                          />
                          <span
                            v-else
                            class="text-h6"
                          >{{ member.avatarOrInitials }}</span>
                        </VAvatar>
                        
                        <div class="member-details">
                          <div class="member-name">
                            {{ member.name }}
                            <VChip
                              v-if="member.role === 'Super-Admin'"
                              size="x-small"
                              color="info"
                              class="ms-2"
                            >
                              SA
                            </VChip>
                          </div>
                          <div class="member-email">
                            {{ member.email }}
                          </div>
                          <VChip
                            v-if="member.is_temporary"
                            size="x-small"
                            color="warning"
                            class="mt-1"
                          >
                            Pending Registration
                          </VChip>
                        </div>
                      </div>

                      <div class="member-controls">
                        <div class="controls-grid">
                          <div class="control-column switches">
                            <VSwitch
                              v-if="member.role !== 'Super-Admin' && !member.is_owner && (boardDataLocal && boardDataLocal.owner_id !== member.user_id)"
                              v-model="member.is_admin"
                              label="Admin"
                              density="compact"
                              hide-details
                              @change="isFormDirty = true"
                            >
                              <template #label>
                                <span class="d-flex align-center">
                                  Admin
                                  
                                </span>
                              </template>
                            </VSwitch>
                            <div 
                              v-if="member.is_owner || boardDataLocal?.owner_id === member.user_id"
                              class="d-flex align-center"
                            >
                              <VChip
                                
                                size="x-small"
                                color="warning"
                              >
                                Owner
                              </VChip>
                            </div>

                            <VSwitch
                              v-model="member.can_timing"
                              label="Can Time"
                              density="compact"
                              hide-details
                              @change="isFormDirty = true"
                            />
                          </div>

                          <div class="control-column inputs">
                            <VSelect
                              v-model="member.payment_type"
                              :items="[
                                { title: PaymentType.getName(PaymentType.HOURLY), value: PaymentType.HOURLY },
                                { title: PaymentType.getName(PaymentType.SALARY), value: PaymentType.SALARY },
                                { title: PaymentType.getName(PaymentType.NO_PAYMENT), value: PaymentType.NO_PAYMENT }
                              ]"
                              label="Payment Type"
                              density="compact"
                              hide-details
                              class="mb-3"
                              @change="isFormDirty = true"
                            />

                            <VSelect
                              v-if="member.payment_type !== PaymentType.NO_PAYMENT"
                              v-model="member.salary_payment_type"
                              :items="[
                                { title: SalaryPaymentType.getName(SalaryPaymentType.MONTHLY), value: SalaryPaymentType.MONTHLY },
                                { title: SalaryPaymentType.getName(SalaryPaymentType.WEEKLY), value: SalaryPaymentType.WEEKLY },
                                { title: SalaryPaymentType.getName(SalaryPaymentType.BI_WEEKLY), value: SalaryPaymentType.BI_WEEKLY }
                              ]"
                              label="Salary Payment Type"
                              density="compact"
                              hide-details
                              class="mb-3"
                              @change="isFormDirty = true"
                            />

                            <template v-if="member.payment_type === PaymentType.SALARY">
                              <VTextField
                                v-model="member.salary"
                                label="Salary Amount"
                                type="number"
                                min="0"
                                step="0.01"
                                density="compact"
                                hide-details
                                class="mb-3"
                                @change="isFormDirty = true"
                              >
                                <template #append>
                                  <span class="text-caption text-medium-emphasis">$</span>
                                </template>
                              </VTextField>
                            </template>

                            <template v-if="member.payment_type === PaymentType.HOURLY">
                              <VTextField
                                v-model="member.billable_rate"
                                label="Rate"
                                type="number"
                                min="0"
                                density="compact"
                                hide-details
                                class="mb-3"
                                @change="isFormDirty = true"
                              >
                                <template #append>
                                  <span class="text-caption text-medium-emphasis">$/h</span>
                                </template>
                              </VTextField>

                              <div class="weekly-limit-group">
                                <VSwitch
                                  v-model="member.weekly_limit_enabled"
                                  label="Weekly Limit"
                                  density="compact"
                                  hide-details
                                  @change="isFormDirty = true"
                                />
                                
                                <VSlideYTransition>
                                  <VTextField
                                    v-if="member.weekly_limit_enabled"
                                    v-model="member.weekly_limit_hours"
                                    label="Hours per week"
                                    type="number"
                                    :rules="[v => !member.weekly_limit_enabled || !!v || 'Required']"
                                    min="0"
                                    step="0.5"
                                    density="compact"
                                    hide-details
                                    @change="isFormDirty = true"
                                  >
                                    <template #append>
                                      <span class="text-caption text-medium-emphasis">h/w</span>
                                    </template>
                                  </VTextField>
                                </VSlideYTransition>
                              </div>
                            </template>
                          </div>
                        </div>

                        <VBtn
                          v-if="!member.is_owner && (!boardDataLocal || boardDataLocal.owner_id !== member.user_id)"
                          icon
                          color="error"
                          size="small"
                          variant="text"
                          class="delete-btn"
                          @click="openRemoveDialog(member)"
                        >
                          <VIcon
                            size="18"
                            icon="tabler-trash"
                          />
                        </VBtn>
                      </div>
                    </div>
                  </TransitionGroup>
                </div>
              </template>
              
              <template v-else>
                <div class="create-board-message">
                  <div class="message-icon">
                    <span class="emoji">👥</span>
                    <span class="plus-emoji">✨</span>
                  </div>
                  <div class="message-content">
                    <p class="message-title">
                      Team collaboration comes next!
                    </p>
                    <p class="message-text">
                      After creating the board, you'll be able to invite team members and set their permissions.
                    </p>
                  </div>
                </div>
              </template>
            </div>

            <div class="dialog-actions">
              <VBtn
                variant="tonal"
                color="secondary"
                :disabled="isSubmitting"
                @click="onReset"
              >
                Cancel
              </VBtn>

              <VBtn
                color="primary"
                :loading="isSubmitting"
                @click="onSubmit"
              >
                {{ showBasicInfo ? 'Create' : 'Update' }}
              </VBtn>
            </div>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Remove Member Dialog -->
    <ConfirmDialog
      v-model:isDialogVisible="isRemoveModalVisible"
      cancel-title="Cancel"
      confirm-title="Remove"
      confirm-msg="Please remember to save your changes to finalize the update."
      confirmation-question="Are you sure you want to remove this member? This action cannot be undone."
      @confirm="confirmed => confirmed && removeMember(memberToRemove)"
    />
  </div>
</template>

<style lang="scss">
.board-dialog {
  .board-dialog-card {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(var(--v-theme-on-surface), 0.12);

    .dialog-header {
      padding: 16px 24px;
      background: rgb(var(--v-theme-background));
      border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;

      .icon-container {
        width: 36px;
        height: 36px;
        background: rgba(var(--v-theme-primary), 0.08);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;

        .emoji {
          font-size: 18px;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          
          &.is-hovered {
            animation: bounce 0.5s cubic-bezier(0.4, 0, 0.2, 1);
          }
        }
      }

      .title-text {
        font-size: 1.125rem;
        font-weight: 600;
        color: rgb(var(--v-theme-on-surface));
      }
    }

    .form-section {
      background: rgb(var(--v-theme-surface));
      border-radius: 6px;
      padding: 24px;
      margin-bottom: 24px;
      border: 1px solid rgba(var(--v-theme-on-surface), 0.08);

      &:hover {
        border-color: rgba(var(--v-theme-on-surface), 0.12);
      }

      .section-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        color: rgb(var(--v-theme-on-surface));

        .v-icon {
          color: rgba(var(--v-theme-primary), 0.9);
          margin-right: 8px;
        }

        .text-h6 {
          font-size: 0.9375rem;
          font-weight: 600;
          letter-spacing: -0.01em;
        }
      }
    }

    .members-list {
      .member-card {
        display: flex;
        flex-direction: column;
        padding: 20px;
        gap: 20px;
        background: rgb(var(--v-theme-surface));
        border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
        border-radius: 6px;
        margin-bottom: 12px;
        transition: all 0.2s ease;

        &:hover {
          border-color: rgba(var(--v-theme-primary), 0.3);
          background: rgba(var(--v-theme-primary), 0.02);
        }

        @media (min-width: 768px) {
          flex-direction: row;
          align-items: flex-start;
        }

        .member-info {
          display: flex;
          align-items: center;
          gap: 12px;
          width: 100%;

          @media (min-width: 768px) {
            width: auto;
            flex: 0 0 250px;
          }

          .member-details {
            flex: 1;
            min-width: 0;

            .member-name {
              font-size: 0.9375rem;
              font-weight: 500;
              color: rgb(var(--v-theme-on-surface));
              display: flex;
              align-items: center;
              margin-bottom: 2px;
            }

            .member-email {
              font-size: 0.8125rem;
              color: rgba(var(--v-theme-on-surface), 0.7);
            }
          }
        }

        .member-controls {
          width: 100%;
          display: flex;
          flex-direction: column;
          gap: 20px;

          @media (min-width: 768px) {
            flex-direction: row;
            align-items: flex-start;
          }

          .controls-grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;

            @media (min-width: 768px) {
              grid-template-columns: 220px 1fr;
            }

            .control-column {
              &.switches {
                display: flex;
                flex-direction: column;
                gap: 8px;

                .v-switch {
                  margin: 0;
                  height: 32px;
                }
              }

              &.inputs {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
                align-items: start;

                @media (min-width: 1024px) {
                  grid-template-columns: repeat(3, minmax(180px, 1fr));
                }

                .v-text-field,
                .v-select {
                  width: 100%;
                }

                .weekly-limit-group {
                  display: flex;
                  flex-direction: column;
                  gap: 8px;
                }
              }
            }
          }

          .delete-btn {
            align-self: flex-start;
            margin-left: auto;

            @media (min-width: 768px) {
              margin-top: 4px;
            }
          }
        }
      }
    }

    .dialog-actions {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
      margin-top: 24px;
      padding-top: 16px;
      border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    }
  }
}

// Animații
.member-list-enter-active,
.member-list-leave-active {
  transition: all 0.3s ease;
}

.member-list-enter-from,
.member-list-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}

@keyframes bounce {
  0%, 100% {
    transform: scale(1) rotate(0);
  }
  50% {
    transform: scale(1.2) rotate(10deg);
  }
}

@keyframes sparkle {
  0%, 100% {
    transform: scale(1) rotate(0deg);
    opacity: 1;
  }
  50% {
    transform: scale(1.2) rotate(15deg);
    opacity: 0.8;
  }
}

@media (max-width: 767px) {
  .member-card {
    padding: 12px;
    gap: 12px;
    
    .v-btn.delete-btn {
      padding: 0;
      min-width: 32px;
      width: 32px;
      height: 32px;
    }
  }

  .form-section {
    .section-header {
      .v-icon {
        font-size: 16px;
      }
    }
  }
}
</style>
