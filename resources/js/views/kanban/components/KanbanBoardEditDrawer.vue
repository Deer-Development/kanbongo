<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import CKEditor from "@/@core/components/CKEditor.vue"
import { useToast } from "vue-toastification"
import { defineExpose, ref } from "vue"
import { watchDebounced } from "@vueuse/core"
import BadgeDateTimePicker from "@core/components/app-form-elements/BadgeDateTimePicker.vue"

const props = defineProps({
  kanbanItem: {
    type: null,
    required: false,
    default: () => ({
      item: {
        title: '',
        dueDate: '2022-01-01T00:00:00Z',
        labels: [],
        members: [],
        id: 0,
        attachments: 0,
        commentsCount: 0,
        image: '',
        comments: '',
      },
      boardId: 0,
      boardName: '',
    }),
  },
  availableMembers: {
    type: Array,
    required: false,
    default: () => [],
  },
  activeUsers: {
    type: Array,
    required: false,
    default: () => [],
  },
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  isSuperAdmin: { type: Boolean, required: false, default: false },
  hasActiveTimer: { type: Boolean, required: false, default: false },
  isOwner: { type: Boolean, required: false, default: false },
  isMember: { type: Boolean, required: false, default: false },
  authId: { type: Number, required: false },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'update:kanbanItem',
  'deleteKanbanItem',
  'refreshKanbanData',
  'editTimer',
])

const messages = ref([])
const message = ref('')
const currentMessageId = ref(null)
const isEditingName = ref(false)
const localActiveUsers = ref([...props.activeUsers])

const toast = useToast()

const Priority = {
  URGENT: 1,
  HIGH: 2,
  NORMAL: 3,
  LOW: 4,
  data: {
    1: "Urgent",
    2: "High",
    3: "Normal",
    4: "Low",
  },
  getName(value) {
    return this.data[value] || null
  },
}

const localKanbanItem = ref(JSON.parse(JSON.stringify(props.kanbanItem.item)))

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

const fetchKanbanItem = async () => {
  const res = await $api(`/task/${localKanbanItem.value.id}`)
  if (res) {
    localKanbanItem.value = res.data
  }
}

watch(() => props.kanbanItem, async () => {
  localKanbanItem.value = JSON.parse(JSON.stringify(props.kanbanItem.item))

  await fetchKanbanItem()

  if (localKanbanItem.value.comments) {
    messages.value = localKanbanItem.value.comments
    resizeImages()
    scrollToBottom()
  }
}, { deep: true })

const updateTask = async updates => {
  return await $api(`/task/${localKanbanItem.value.id}`, {
    method: 'PUT',
    body: {
      name: localKanbanItem.value.name,
      priority: localKanbanItem.value.priority,
      due_date: localKanbanItem.value.due_date,
      members: localKanbanItem.value.members.map(member => member.user.id),
      ...updates,
    },
  })
}

const addMember = async userId => {
  const userIds = localKanbanItem.value.members.map(member => member.user.id)

  userIds.push(userId)

  const res = await updateTask({ members: userIds })

  if (res) {
    localKanbanItem.value.members = res.data.members
  }
}

const setPriority = async priority => {
  const res = await updateTask({ priority })

  if (res) {
    localKanbanItem.value.priority = priority
  }
}

watchDebounced(
  () => isEditingName.value,
  async value => {
    if (!value) {
      await updateTask({ name: localKanbanItem.value.name })
    }
  },
  { debounce: 100 },
)

watchDebounced(
  () => localKanbanItem.value.due_date,
  async (newDate, oldDate) => {
    if (newDate !== oldDate) {
      await updateTask({ due_date: newDate })
    }
  },
  { debounce: 500 },
)

const editTimer = (member, id, name) => {
  emit('editTimer', member, id, name)
}

const deleteKanbanItem = () => {
  emit('deleteKanbanItem', {
    item: localKanbanItem.value,
  })
}

const getPriorityColor = priority => {
  if (priority == Priority.URGENT)
    return '#FF5733'
  if (priority == Priority.HIGH)
    return '#FFA533'
  if (priority == Priority.NORMAL)
    return '#338DFF'
  if (priority == Priority.LOW)
    return '#30c15a'

  return 'primary'
}

const priorityMenu = ref(false)
const membersMenu = ref(false)

const messageListRef = ref(null)

const scrollToBottom = async () => {
  await nextTick()
  if (messageListRef.value) {
    messageListRef.value.scrollTop = messageListRef.value.scrollHeight
  }
}

const handleAddMessage = async () => {
  const res = await $api('/comment', {
    method: 'POST',
    body: {
      content: message.value,
      commentable_id: localKanbanItem.value.id,
      commentable_type: 'App\\Models\\Task',
    },
  })

  if (res) {
    messages.value = res.data
  }

  message.value = ''
  resizeImages()

  scrollToBottom()
}

const closeDrawer = () => {
  emit('update:isDrawerOpen', false)
  messages.value = []
  message.value = ''

  emit('refreshKanbanData')
}

const isDeleteDisabled = computed(() => {
  return localKanbanItem.value.members.some(member => member.timeEntries && member.timeEntries.length > 0)
})

const resizeImages = () => {
  nextTick(() => {
    const messageContainers = document.querySelectorAll(".message-content")

    messageContainers.forEach(container => {
      const images = container.querySelectorAll("img")

      images.forEach(img => {
        img.onload = () => {
          img.style.maxWidth = "100%"
          img.style.maxHeight = "300px"
          img.style.objectFit = "contain"
          img.style.borderRadius = "8px"
          img.style.margin = "8px auto"
          img.style.display = "block"
        }
      })
    })
  })
}

const editMessage = async messageId => {
  const messageToEdit = messages.value.find(msg => msg.id === messageId)
  if (messageToEdit) {
    message.value = messageToEdit.content
    currentMessageId.value = messageId
  }
}

const submitEditMessage = async messageId => {
  const res = await $api(`/comment/${messageId}`, {
    method: 'PUT',
    body: {
      content: message.value,
      commentable_id: localKanbanItem.value.id,
      commentable_type: 'App\\Models\\Task',
    },
  })

  if (res) {
    messages.value = res.data

    currentMessageId.value = null
    message.value = ''

    resizeImages()

    scrollToBottom()
  }
}

const deleteMessage = async messageId => {
  const res = await $api(`/comment/${messageId}`, {
    method: 'DELETE',
  })

  if (res) {
    messages.value = messages.value.filter(msg => msg.id !== messageId)
    toast.success('Comment deleted successfully')
  }
}

defineExpose({
  fetchKanbanItem,
})
</script>

<template>
  <VDialog
    transition="dialog-bottom-transition"
    fullscreen
    max-height="100%"
    persistent
    :model-value="isDrawerOpen"
    class="github-dialog"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <VCard class="github-edit-card h-full flex flex-col">
      <VRow class="github-header my-0 py-0">
        <VCol
          cols="8"
          class="d-flex justify-start gap-2"
        >
          <VBtn
            color="secondary"
            variant="outlined"
            @click="closeDrawer"
          >
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCol>
        <VCol
          cols="4"
          class="d-flex justify-end gap-2"
        >
          <VBtn
            v-if="isSuperAdmin && !isDeleteDisabled"
            color="error"
            class="btn-github"
            variant="outlined"
            @click="deleteKanbanItem"
          >
            Delete
          </VBtn>
        </VCol>
      </VRow>

      <VCardText class="flex-grow flex flex-col p-4 gap-6">
        <div class="form-github">
          <VRow>
            <VCol cols="6">
              <VTextarea
                v-model="localKanbanItem.name"
                :rules="[requiredValidator, maxLengthValidator(localKanbanItem.name, 255)]"
                rows="3"
                :readonly="!isEditingName"
                label="Title"
                dense
                variant="solo"
                class="input-github"
              >
                <template #append-inner>
                  <VSlideXReverseTransition mode="out-in">
                    <VIcon
                      size="20"
                      :key="`icon-${isEditingName}`"
                      :color="isEditingName ? 'success' : 'info'"
                      :icon="isEditingName ? 'tabler-checks' : 'tabler-edit-circle'"
                      @click="isEditingName = !isEditingName"
                    />
                  </VSlideXReverseTransition>
                </template>
              </VTextarea>
            </VCol>
          </VRow>
          <div class="d-flex gap-2 flex-column mt-4">
            <div class="d-flex gap-2">
              <VMenu v-model="priorityMenu" offset-y>
                <template #activator="{ props }">
                  <div class="custom-badge" v-bind="props">
                    <VIcon left size="16" :color="getPriorityColor(localKanbanItem.priority)">tabler-flag-3-filled</VIcon>
                    <span>{{ localKanbanItem.priority ? Priority.data[localKanbanItem.priority] : 'Priority' }}</span>
                  </div>
                </template>
                <div class="priority-options">
                  <div
                    v-for="(label, key) in Priority.data"
                    :key="key"
                    class="priority-option"
                    @click="setPriority(key)"
                  >
                    <VIcon size="16" :color="getPriorityColor(key)">tabler-flag-3-filled</VIcon>
                    <span>{{ label }}</span>
                  </div>
                  <VDivider />
                  <div class="priority-clear" @click="setPriority(0)">
                    <VIcon left size="16" color="gray">tabler-circle-off</VIcon>
                    <span>Clear</span>
                  </div>
                </div>
              </VMenu>
              <BadgeDateTimePicker
                v-model="localKanbanItem.due_date"
                density="compact"
                variant="underlined"
                placeholder="Date"
                clearable
                :config="{ altFormat: 'j, M', altInput: true }"
              />
            </div>
            <DynamicMemberSelector
              v-model="localKanbanItem.members"
              :items="props.availableMembers"
              :is-super-admin="props.isSuperAdmin"
              item-title="user.full_name"
              item-value="user.id"
              :active-users="localActiveUsers"
              :tracked-users="localKanbanItem.tracked_time?.usersTracked"
              :task-id="localKanbanItem.id"
              dense
              outlined
            />
          </div>
        </div>

        <div class="comments-section">
          <VDivider />
          <VCardTitle class="text-center text-dark fs-6 fw-bold mb-2">
            Comments
          </VCardTitle>

          <VRow>
            <VCol
              cols="12"
              md="4"
              class="message-editor"
            >
              <CKEditor
                v-model="message"
                class="input-github"
              />
              <div class="d-flex gap-2 mt-2 justify-end">
                <VBtn
                  v-if="currentMessageId"
                  color="primary"
                  class="btn-github"
                  prepend-icon="tabler-send"
                  @click="submitEditMessage(currentMessageId)"
                >
                  Submit
                </VBtn>
                <VBtn
                  v-else
                  color="primary"
                  class="btn-github"
                  prepend-icon="tabler-send"
                  @click="handleAddMessage"
                >
                  Submit
                </VBtn>
              </div>
            </VCol>
            <VCol
              cols="12"
              md="8"
            >
              <div
                ref="messageListRef"
                class="messages-container"
              >
                <PerfectScrollbar>
                  <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="message-item-github"
                  >
                    <div class="message-header">
                      <div class="d-flex align-items-center gap-2">
                        <VAvatar size="40">
                          <template v-if="msg.createdBy.avatar">
                            <img
                              :src="msg.createdBy.avatar"
                              alt="Avatar"
                            >
                          </template>
                          <template v-else>
                            <span>{{ msg.createdBy.avatar_or_initials }}</span>
                          </template>
                        </VAvatar>
                        <div>
                          <strong>{{ msg.createdBy.full_name }}</strong>
                          <div class="text-muted text-xs">
                            {{ msg.created_at }}
                          </div>
                        </div>
                      </div>
                      <div
                        v-if="msg.createdBy.id === props.authId"
                        class="d-flex gap-2"
                      >
                        <button
                          class="edit-button"
                          @click="editMessage(msg.id)"
                        >
                          Edit
                        </button>
                        <button
                          class="delete-button"
                          @click="deleteMessage(msg.id)"
                        >
                          Delete
                        </button>
                      </div>
                    </div>
                    <div class="message-content">
                      <p v-html="msg.content" />
                    </div>
                  </div>
                </PerfectScrollbar>
              </div>
            </VCol>
          </VRow>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.comments-section {
  margin-top: 20px;
}

.github-header {
  border-bottom: 1px solid #d8dee4;
  padding: 0.2rem 2rem;
  background-color: #f6f8fa;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
}

.btn-github {
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  transition: background-color 0.2s;
}

.message-item-github {
  background-color: #ffffff;
  border: 1px solid #d0d7de;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  transition: all 0.2s;

  &:hover {
    background-color: #f6f8fa;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  }

  .message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .message-content {
    color: #333;
  }

  .edit-button,
  .delete-button {
    font-size: 0.8rem;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s;

    &.edit-button {
      background-color: #e7f3ff;
      color: #0969da;

      &:hover {
        background-color: #c7e0f9;
      }
    }

    &.delete-button {
      background-color: #f8d7da;
      color: #e63946;

      &:hover {
        background-color: #f5c2c7;
      }
    }
  }
}

.messages-container {
  min-height: 60vh;
  max-height: 60vh;
  overflow-y: auto;
  border-radius: 8px;
  border: 1px solid #ddd;
  padding: 16px;
}

.message-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 8px;
  border-bottom: 2px solid #f0f0f0;
  margin-bottom: 12px;
}

.message-header .font-weight-bold {
  color: #2c3e50;
}

.message-header .text-caption {
  color: #7f8c8d;
}

.message-content {
  color: #34495e;
  line-height: 1.6;
  word-wrap: break-word;
}

.edit-button {
  background: #f5f5f5;
  border: 1px solid #dcdcdc;
  border-radius: 8px;
  padding: 4px 8px;
  font-size: 0.85rem;
  color: #007bff;
  cursor: pointer;
  transition: background 0.3s ease;
}

.edit-button:hover {
  background: #007bff;
  color: #ffffff;
}

.delete-button {
  background: #ffdddd;
  border: 1px solid #ff4444;
  border-radius: 8px;
  padding: 4px 8px;
  font-size: 0.85rem;
  color: #ff4444;
  cursor: pointer;
  transition: background 0.3s ease;
}

.delete-button:hover {
  background: #ff4444;
  color: #ffffff;
}
</style>



