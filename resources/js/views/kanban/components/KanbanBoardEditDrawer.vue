<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { VForm } from 'vuetify/components/VForm'
import CKEditor from "@/@core/components/CKEditor.vue"
import { useToast } from "vue-toastification"
import { ref } from "vue"

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
])

const messages = ref([])
const message = ref('')
const description = ref('')
const currentMessageId = ref(null)

const refEditTaskForm = ref()
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
const localAvailableMembers = ref(props.availableMembers)

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
  if (!val)
    refEditTaskForm.value?.reset()
}

watch(() => props.kanbanItem, () => {
  localKanbanItem.value = JSON.parse(JSON.stringify(props.kanbanItem.item))

  if(localKanbanItem.value.comments) {
    messages.value = localKanbanItem.value.comments
    resizeImages()
    scrollToBottom()
  }
}, { deep: true })

const updateKanbanItem = () => {
  refEditTaskForm.value?.validate().then(async valid => {
    if (valid.valid) {
      const res = await $api(`/task/${localKanbanItem.value.id}`, {
        method: 'PUT',
        body: {
          name: localKanbanItem.value.name,
          description: description.value,
          priority: localKanbanItem.value.priority,
          members: localKanbanItem.value.members.map(member => member.user.id),
        },
      })

      if (res) {
        emit('refreshKanbanData')
      }

      emit('update:isDrawerOpen', false)
      await nextTick()
      refEditTaskForm.value?.reset()
    }
  })
}

const deleteKanbanItem = () => {
  emit('deleteKanbanItem', {
    item: localKanbanItem.value,
  })
}

const getPriorityColor = priority => {
  if (priority == Priority.URGENT)
    return 'success'
  if (priority == Priority.HIGH)
    return 'error'
  if (priority == Priority.NORMAL)
    return 'info'
  if (priority == Priority.LOW)
    return 'warning'

  return 'primary'
}

const priorityMenu = ref(false)

const messageListRef = ref(null)

const showEditor = ref(false)

const toggleEditor = () => {
  showEditor.value = true
}

const cancelEditor = () => {
  showEditor.value = false
  message.value = ''
  currentMessageId.value = null

  resizeImages()
  scrollToBottom()
}

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

  showEditor.value = false
  message.value = ''
  resizeImages()

  scrollToBottom()
}

const closeDrawer = () => {
  emit('update:isDrawerOpen', false)
  refEditTaskForm.value?.reset()
  messages.value = []
  message.value = ''
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
    showEditor.value = true
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

    showEditor.value = false
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
</script>

<template>
  <VDialog
    transition="dialog-bottom-transition"
    fullscreen
    max-height="100%"
    persistent
    :model-value="isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <VCard class="rounded-lg shadow-lg h-full flex flex-col">
      <VToolbar class="pa-2">
        <VChip
          color="primary"
          variant="tonal"
          @click="closeDrawer"
        >
          <VIcon
            size="20"
            icon="tabler-x"
          />
        </VChip>
        <VToolbarTitle>
          <span class="text-wrap">{{ localKanbanItem.name }}</span>
        </VToolbarTitle>
      </VToolbar>

      <VCardText class="flex-grow flex flex-col p-4 gap-6">
        <VForm
          ref="refEditTaskForm"
          @submit.prevent="updateKanbanItem"
        >
          <VRow
            align="center"
            justify="space-between"
          >
            <VCol cols="3">
              <VMenu
                v-model="priorityMenu"
                offset-y
              >
                <template #activator="{ props }">
                  <VBtn
                    v-bind="props"
                    :color="getPriorityColor(localKanbanItem.priority) || 'info'"
                    size="small"
                    variant="flat"
                    prepend-icon="tabler-flag"
                  >
                    {{ Priority.getName(localKanbanItem.priority) || "Set Priority" }}
                  </VBtn>
                </template>
                <div class="priority-options">
                  <VChip
                    v-for="(label, key) in Priority.data"
                    :key="key"
                    :color="getPriorityColor(key)"
                    variant="flat"
                    class="priority-badge"
                    @click.stop="localKanbanItem.priority = key"
                  >
                    {{ label }}
                  </VChip>
                </div>
              </VMenu>
            </VCol>

            <VCol cols="5">
              <DynamicMemberSelector
                v-model="localKanbanItem.members"
                :items="localAvailableMembers"
                :is-super-admin="props.isSuperAdmin"
                label="Assign Members"
                item-title="user.full_name"
                item-value="user.id"
                dense
                outlined
              />
            </VCol>

            <VCol
              cols="4"
              class="d-flex justify-end gap-2"
            >
              <VBtn
                color="primary"
                class="me-3"
                type="submit"
              >
                Update
              </VBtn>
              <VBtn
                v-if="isSuperAdmin && !isDeleteDisabled"
                color="error"
                variant="tonal"
                @click="deleteKanbanItem"
              >
                Delete
              </VBtn>
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12">
              <VTextarea
                v-model="localKanbanItem.name"
                :rules="[requiredValidator, maxLengthValidator(localKanbanItem.name, 255)]"
                rows="3"
                label="Title"
                dense
                outlined
              />
            </VCol>
          </VRow>
        </VForm>

        <div class="comments-section">
          <VRow>
            <VCol
              cols="12"
              md="4"
            >
              <div class="message-editor">
                <CKEditor
                  v-model="message"
                  class="editor-input"
                />
                <div class="d-flex gap-2 mt-2 justify-end">
                  <VBtn
                    v-if="currentMessageId"
                    color="primary"
                    prepend-icon="tabler-send"
                    @click="submitEditMessage(currentMessageId)"
                  >
                    Submit
                  </VBtn>
                  <VBtn
                    v-else
                    color="primary"
                    prepend-icon="tabler-send"
                    @click="handleAddMessage"
                  >
                    Submit
                  </VBtn>
                </div>
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
                    class="message-item"
                    :class="[{ editing: msg.id === currentMessageId }]"
                  >
                    <div class="message-header">
                      <span class="font-weight-bold text-sm d-flex gap-2">
                        <VAvatar
                          size="40"
                          :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
                        >
                          <template v-if=" msg.createdBy.avatar">
                            <img
                              :src=" msg.createdBy.avatar"
                              alt="Avatar"
                            >
                          </template>
                          <template v-else>
                            <span>{{ msg.createdBy.avatar_or_initials }}</span>
                          </template>
                        </VAvatar>
                        <span class="d-flex flex-column gap-1">
                          {{ msg.createdBy.full_name }}
                          <span class="text-caption text-xs">
                            {{ msg.created_at }}
                          </span>
                        </span>
                      </span>
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

.comments-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.messages-container {
  min-height: 60vh;
  max-height: 60vh;
  overflow-y: auto;
  border-radius: 8px;
  border: 1px solid #ddd;
  padding: 16px;
}

.message-item {
  margin-bottom: 12px;
  padding: 16px;
  border-radius: 12px;
  background: #f9f9f9;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  border: 1px solid #e6e6e6;
  transition: box-shadow 0.3s ease, transform 0.2s ease;
}

.message-item:hover {
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.message-content img {
  max-width: 100%;
  max-height: 300px; /* Limita înălțimii */
  object-fit: contain; /* Pentru a păstra proporțiile */
  border: 2px solid #007bff; /* Highlight pentru imagine */
  border-radius: 8px; /* Colțuri rotunjite */
  margin: 8px 0; /* Spațiu între text și imagine */
  transition: transform 0.2s ease; /* Animație la hover */
}

.message-content img:hover {
  transform: scale(1.05); /* Efect vizual pe hover */
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
.message-item.editing {
  background-color: #e3f2fd;
  border-color: #90caf9;
  transition: background-color 0.3s ease, border-color 0.3s ease;
}
</style>



