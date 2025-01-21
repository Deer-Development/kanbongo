<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { VForm } from 'vuetify/components/VForm'
import CKEditor from "@/@core/components/CKEditor.vue"
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

const refEditTaskForm = ref()

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
    boardId: props.kanbanItem.boardId,
    boardName: props.kanbanItem.boardName,
  })
  emit('update:isDrawerOpen', false)
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

const scrollToBottom = async () => {
  await nextTick()
  if (messageListRef.value) {
    messageListRef.value.scrollTop = messageListRef.value.scrollHeight
  }
}

const addMessage = async () => {
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

  scrollToBottom()
}

const closeDrawer = () => {
  emit('update:isDrawerOpen', false)
  refEditTaskForm.value?.reset()
  messages.value = []
  message.value = ''
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
        <VToolbarTitle>{{ localKanbanItem.name }}</VToolbarTitle>
      </VToolbar>

      <VCardText class="flex-grow flex flex-col">
        <VRow>
          <VCol cols="6">
            <h4 class="text-h5 font-semibold mb-4 text-center">
              Task Details
            </h4>
            <VForm
              ref="refEditTaskForm"
              @submit.prevent="updateKanbanItem"
            >
              <VRow>
                <VCol
                  cols="12"
                  class="d-flex justify-end"
                >
                  <VBtn
                    color="primary"
                    class="me-3"
                    type="submit"
                  >
                    Update
                  </VBtn>
                  <VBtn
                    v-if="isSuperAdmin"
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
                  <AppTextField
                    v-model="localKanbanItem.name"
                    :rules="[requiredValidator, maxLengthValidator(localKanbanItem.name, 255)]"
                    label="Title"
                    dense
                    outlined
                  />
                </VCol>
                <VCol cols="2">
                  <VLabel
                    for="priority"
                    class="mb-1 text-body-2"
                    style="line-height: 15px;"
                    text="Priority"
                  />
                  <div class="kanban-row">
                    <VMenu
                      id="priority"
                      v-model="priorityMenu"
                      close-on-content-click
                      offset-y
                      class="box"
                    >
                      <template #activator="{ props }">
                        <VBtn
                          v-bind="props"
                          :color="getPriorityColor(localKanbanItem.priority) || 'info'"
                          size="small"
                          variant="flat"
                          prepend-icon="tabler-flag"
                        >
                          {{ Priority.getName(localKanbanItem.priority) || 'Set Priority' }}
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
                  </div>
                </VCol>
                <VCol cols="6">
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
                <VCol cols="12" class="mt-5">
                  <CKEditor v-model="description" />
                </VCol>
              </VRow>
            </VForm>
          </VCol>

          <VDivider vertical />

          <VCol
            cols="6"
            class="flex flex-col h-full w-full"
          >
            <h4 class="text-h5 font-semibold mb-4 text-center">
              Comments
            </h4>
            <div class="messages-container flex-grow overflow-y-auto" ref="messageListRef">
              <div
                v-if="messages.length === 0"
                class="empty-state"
              >
                <p class="text-center text-gray-500 font-weight-bold">
                  Be the first to share your thoughts
                </p>
              </div>
              <PerfectScrollbar>
                <div
                  v-for="msg in messages"
                  :key="msg.id"
                  class="message-item"
                >
                  <div class="message-header">
                    <span class="font-weight-bold text-sm text-gray-900">{{ msg.createdBy.full_name }}</span>
                    <span class="text-caption text-xs text-gray-500">{{ msg.created_at }}</span>
                  </div>
                  <div class="message-content">
                    <p
                      class="text-body-2 text-gray-700"
                      v-html="msg.content"
                    />
                  </div>
                </div>
              </PerfectScrollbar>
            </div>
            <div class="message-editor">
              <CKEditor
                v-model="message"
                :rules="[requiredValidator]"
                class="editor-input"
              />
              <VBtn
                color="primary"
                prepend-icon="tabler-send"
                class="mt-2"
                @click="addMessage"
              >
                Add Comment
              </VBtn>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.messages-container {
  flex-grow: 1;
  min-height: 45vh;
  max-height: 45vh;
  overflow-y: auto;
  padding: 16px;
  margin-bottom: 16px;
}

.message-editor {
  background-color: #fff;
  box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
}

.message-item {
  margin-bottom: 16px;
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
}

.message-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.message-content {
  white-space: pre-wrap;
  word-wrap: break-word;
}
</style>

