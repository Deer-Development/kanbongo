<script setup>
import { VForm } from 'vuetify/components/VForm'
import { defineProps, nextTick, ref, watch, onMounted, computed } from 'vue'
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
const isTransferOwnershipDialogVisible = ref(false)
const selectedNewOwner = ref(null)
const isTransferringOwnership = ref(false)
const confirmationEmail = ref('')
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
      members: boardDataLocal.value?.id ? members.value : [], // Trimitem members doar pentru edit
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
  selectedNewOwner.value = null
  confirmationEmail.value = ''
  isTransferOwnershipDialogVisible.value = false
}

const confirmationError = computed(() => {
  if (!confirmationEmail.value) return ''
  if (confirmationEmail.value !== members.value.find(m => m.user_id === selectedNewOwner.value)?.email) {
    return 'Email does not match'
  }
  return ''
})

const canTransfer = computed(() => {
  return selectedNewOwner.value && 
         confirmationEmail.value === members.value.find(m => m.user_id === selectedNewOwner.value)?.email
})

const openTransferOwnershipDialog = () => {
  selectedNewOwner.value = null
  confirmationEmail.value = ''
  isTransferOwnershipDialogVisible.value = true
}

const transferOwnership = async () => {
  if (!canTransfer.value) return
  
  isTransferringOwnership.value = true
  
  try {
    await $api(`/container/${boardDataLocal.value.id}/transfer-ownership`, {
      method: 'POST',
      body: {
        new_owner_id: selectedNewOwner.value
      }
    })
    
    toast.success('Board ownership transferred successfully!')
    boardDataLocal.value.owner_id = selectedNewOwner.value
    isTransferOwnershipDialogVisible.value = false
    
    await nextTick(() => {
      emit('formSubmitted')
    })
  } catch (error) {
    toast.error('Failed to transfer ownership. Please try again.')
  } finally {
    isTransferringOwnership.value = false
    selectedNewOwner.value = null
    confirmationEmail.value = ''
  }
}
</script>

<template>
  <div>
    <VDialog
      :model-value="isDialogVisible"
      @update:model-value="emit('update:isDialogVisible', $event)"
      :max-width="$vuetify.display.smAndDown ? '100%' : '950'"
      :fullscreen="$vuetify.display.smAndDown"
      persistent
      class="board-dialog"
    >
      <VCard class="board-dialog-card">
        <VCardTitle class="dialog-header">
          <div class="d-flex align-center">
            <div class="icon-container">
              <span class="emoji" :class="{ 'is-hovered': isHeaderHovered }" @mouseenter="isHeaderHovered = true" @mouseleave="isHeaderHovered = false">
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
            <!-- Basic Information - Only show when creating new board -->
            <div v-if="showBasicInfo" class="form-section">
              <div class="section-header">
                <VIcon icon="tabler-info-circle" size="18" class="me-2" />
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
                  <VIcon icon="tabler-layout-board" size="18" />
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
            <div class="form-section" :class="{ 'mt-6': showBasicInfo }">
              <div class="section-header d-flex justify-space-between align-center">
                <div class="d-flex align-center">
                  <VIcon icon="tabler-users" size="18" class="me-2" />
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

                <div v-if="members.length" class="members-list mt-6">
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
                          <span v-else class="text-h6">{{ member.avatarOrInitials }}</span>
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
                          <div class="member-email">{{ member.email }}</div>
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
                              v-model="member.is_admin"
                              :disabled="member.is_owner || member.role === 'Super-Admin' || (boardDataLocal && boardDataLocal.owner_id === member.user_id)"
                              label="Admin"
                              density="compact"
                              hide-details
                              @change="isFormDirty = true"
                            >
                              <template #label>
                                <span class="d-flex align-center">
                                  Admin
                                  <VChip
                                    v-if="member.is_owner || boardDataLocal?.owner_id === member.user_id"
                                    size="x-small"
                                    color="warning"
                                    class="ms-2"
                                  >
                                    Owner
                                  </VChip>
                                  <VChip
                                    v-else-if="member.role === 'Super-Admin'"
                                    size="x-small"
                                    color="info"
                                    class="ms-2"
                                  >
                                    SA
                                  </VChip>
                                </span>
                              </template>
                            </VSwitch>

                            <VSwitch
                              v-model="member.can_timing"
                              label="Can Time"
                              density="compact"
                              hide-details
                              @change="isFormDirty = true"
                            />
                          </div>

                          <div class="control-column inputs">
                            <VTextField
                              v-model="member.billable_rate"
                              label="Rate"
                              type="number"
                              min="0"
                              density="compact"
                              hide-details
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
                          <VIcon size="18" icon="tabler-trash" />
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
                    <p class="message-title">Team collaboration comes next!</p>
                    <p class="message-text">After creating the board, you'll be able to invite team members and set their permissions.</p>
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
    border-radius: 12px;
    overflow: hidden;

    .dialog-header {
      padding: 16px 20px;
      background: rgb(var(--v-theme-background));
      display: flex;
      justify-content: space-between;
      align-items: center;

      .icon-container {
        width: 40px;
        height: 40px;
        background: rgba(var(--v-theme-primary), 0.1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;

        .emoji {
          font-size: 20px;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          
          &.is-hovered {
            animation: bounce 0.5s cubic-bezier(0.4, 0, 0.2, 1);
          }
        }
      }

      .title-text {
        font-size: 1.25rem;
        font-weight: 600;
        color: rgb(var(--v-theme-on-surface));
      }
    }

    .form-section {
      background: rgb(var(--v-theme-surface));
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 24px;
      border: 1px solid rgba(var(--v-theme-on-surface), 0.08);

      &:hover {
        border-color: rgba(var(--v-theme-on-surface), 0.12);
      }

      .section-header {
        display: flex;
        align-items: center;
        margin-bottom: 16px;
        color: rgb(var(--v-theme-on-surface));

        .v-icon {
          color: rgba(var(--v-theme-primary), 0.9);
        }

        .text-h6 {
          font-size: 1rem;
          font-weight: 600;
        }
      }
    }

    .members-list {
      .member-card {
        display: flex;
        flex-direction: column;
        padding: 16px;
        gap: 16px;

        @media (min-width: 768px) {
          flex-direction: row;
          align-items: center;
        }

        .member-info {
          display: flex;
          align-items: center;
          gap: 12px;
          width: 100%;

          @media (max-width: 767px) {
            flex: 0 0 auto;
            width: auto;
            min-width: 0;
            max-width: 100%;
          }

          @media (min-width: 768px) {
            width: auto;
            flex: 0 0 250px;
          }

          .v-avatar {
            flex-shrink: 0;
            
            @media (max-width: 767px) {
              width: 32px;
              height: 32px;
            }
          }

          .member-details {
            flex: 1;
            min-width: 0;
            max-width: calc(100% - 44px);

            @media (max-width: 767px) {
              .member-name {
                font-size: 0.875rem;
                
                .v-chip {
                  font-size: 0.75rem;
                  height: 16px;
                  padding: 0 4px;
                }
              }

              .member-email {
                font-size: 0.75rem;
              }

              .v-chip.mt-1 {
                margin-top: 2px;
                font-size: 0.75rem;
                height: 16px;
                padding: 0 4px;
              }
            }
          }
        }

        .member-controls {
          width: 100%;
          display: flex;
          flex-direction: column;
          gap: 16px;

          @media (min-width: 768px) {
            flex-direction: row;
            align-items: flex-start;
          }

          .controls-grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;

            @media (min-width: 480px) {
              grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            @media (min-width: 768px) {
              grid-template-columns: minmax(200px, auto) minmax(200px, 1fr);
            }

            .control-column {
              &.switches {
                display: flex;
                flex-direction: column;
                gap: 8px;

                .v-switch {
                  margin: 0;
                  min-height: 32px;
                }
              }

              &.inputs {
                display: flex;
                flex-direction: column;
                gap: 12px;

                .v-text-field {
                  width: 100%;
                  max-width: 150px;
                }

                .weekly-limit-group {
                  display: flex;
                  flex-direction: column;
                  gap: 8px;

                  @media (max-width: 479px) {
                    flex-direction: row;
                    align-items: center;
                    gap: 12px;
                  }

                  .v-text-field {
                    width: 100%;
                    max-width: 150px;

                    @media (max-width: 479px) {
                      max-width: 120px;
                    }
                  }
                }
              }
            }
          }

          .delete-btn {
            align-self: flex-end;

            @media (min-width: 768px) {
              align-self: center;
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
    }

    @media (max-width: 767px) {
      .dialog-header {
        padding: 12px 16px;

        .title-text {
          font-size: 1.1rem;
        }
      }

      .form-section {
        padding: 16px;
        margin-bottom: 16px;

        .section-header {
          margin-bottom: 12px;

          .text-h6 {
            font-size: 0.9rem;
          }
        }
      }

      .dialog-actions {
        flex-direction: column-reverse;
        gap: 8px;
        
        .v-btn {
          width: 100%;
        }
      }
    }

    .create-board-message {
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 24px;
      background: rgba(var(--v-theme-primary), 0.04);
      border-radius: 8px;
      transition: all 0.2s ease;

      .message-icon {
        position: relative;
        flex-shrink: 0;

        .emoji {
          font-size: 32px;
          display: block;
        }

        .plus-emoji {
          position: absolute;
          bottom: -4px;
          right: -8px;
          font-size: 16px;
          filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
          animation: sparkle 1.5s ease-in-out infinite;
        }
      }

      .message-content {
        flex: 1;

        .message-title {
          font-size: 1rem;
          font-weight: 600;
          color: rgb(var(--v-theme-on-surface));
          margin-bottom: 4px;
        }

        .message-text {
          font-size: 0.875rem;
          color: rgba(var(--v-theme-on-surface), 0.7);
          line-height: 1.4;
        }
      }

      @media (max-width: 767px) {
        padding: 16px;
        gap: 16px;

        .message-icon {
          .emoji {
            font-size: 28px;
          }

          .plus-emoji {
            font-size: 14px;
          }
        }

        .message-content {
          .message-title {
            font-size: 0.9rem;
          }

          .message-text {
            font-size: 0.8rem;
          }
        }
      }
    }
  }

  .member-card {
    display: flex;
    padding: 16px;
    background: rgba(var(--v-theme-surface), 0.5);
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 8px;
    margin-bottom: 12px;
    transition: all 0.2s ease;
    gap: 24px;

    .member-info {
      flex: 0 0 250px;

      .member-details {
        .member-name {
          font-weight: 500;
          display: flex;
          align-items: center;
        }

        .member-email {
          font-size: 0.875rem;
          color: rgba(var(--v-theme-on-surface), 0.7);
        }
      }
    }

    .member-controls {
      flex: 1;
      display: flex;
      align-items: flex-start;
      gap: 16px;

      .controls-grid {
        flex: 1;
        display: grid;
        grid-template-columns: minmax(200px, auto) minmax(300px, 1fr);
        gap: 24px;
        align-items: flex-start;

        .control-column {
          &.switches {
            display: flex;
            flex-direction: column;
            gap: 8px;

            .v-switch {
              margin-bottom: 4px;
            }
          }

          &.inputs {
            display: flex;
            flex-direction: column;
            gap: 8px;

            .weekly-limit-group {
              display: flex;
              flex-direction: column;
              gap: 8px;

              @media (max-width: 479px) {
                flex-direction: row;
                align-items: center;
                gap: 12px;
              }

              .v-text-field {
                width: 100%;
                max-width: 150px;

                @media (max-width: 479px) {
                  max-width: 120px;
                }
              }
            }
          }
        }
      }

      .delete-btn {
        flex-shrink: 0;
        align-self: center;
      }
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
