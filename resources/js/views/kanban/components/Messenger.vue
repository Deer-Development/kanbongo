<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useToast } from "vue-toastification"
import { defineExpose, ref, watch } from "vue"
import { remapNodes } from "@formkit/drag-and-drop"

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
const selectedMembers = ref([])
const uploadedFiles = ref([])
const localAvailableMembers = ref([])
const message = ref('')
const currentMessageId = ref(null)
const chatLogPS = ref()
const previewDialog = ref(false)
const selectedAttachment = ref(null)

const toast = useToast()

const localKanbanItem = ref(JSON.parse(JSON.stringify(props.kanbanItem.item)))

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

watch(() => props, () => {
  localAvailableMembers.value = props.availableMembers
}, { deep: true, immediate: true })

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
    checkCommentsInView()
  }
}, { deep: true })

const scrollToBottom = async () => {
  await nextTick()

  const scrollEl = chatLogPS.value.$el || chatLogPS.value

  scrollEl.scrollTop = scrollEl.scrollHeight
}

const handleAddMessage = async () => {
  const res = await $api('/comment', {
    method: 'POST',
    body: {
      content: message.value,
      commentable_id: localKanbanItem.value.id,
      commentable_type: 'App\\Models\\Task',
      temporary_uploads: uploadedFiles.value,
      mentioned_users: selectedMembers.value,
    },
  })

  if (res) {
    messages.value = res.data
  }

  message.value = ''
  selectedMembers.value = []
  uploadedFiles.value = []

  scrollToBottom()
}

const markCommentAsRead = async commentId => {
  const comment = messages.value.find(msg => msg.id === commentId)
  if (!comment || comment.is_read) return

  comment.markingAsRead = true

  try {
    await $api('/comment/mark-as-read', {
      method: 'POST',
      body: { type: 'comment', id: commentId },
    })

    comment.is_read = true
  } catch (error) {
    toast.error('Failed to mark comment as read.')
  } finally {
    comment.markingAsRead = false
  }
}

const checkCommentsInView = () => {
  messages.value.forEach(msg => {
    if (!msg.is_read) {
      markCommentAsRead(msg.id)
    }
  })
}

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

const editMessage = async messageToEdit => {
  if (messageToEdit) {
    message.value = messageToEdit.content
    uploadedFiles.value = messageToEdit.attachments
    selectedMembers.value = messageToEdit.mentioned_users
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

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  messages.value = []
  message.value = ''

  emit('refreshKanbanData')
}

const isImage = attachment => /\.(jpg|jpeg|png|gif)$/i.test(attachment.name)
const isPDF = attachment => attachment.name.endsWith('.pdf')
const isWord = attachment => attachment.name.endsWith('.docx')
const isExcel = attachment => attachment.name.endsWith('.xlsx')

const openPreview = attachment => {
  selectedAttachment.value = attachment
  previewDialog.value = true
}

const closePreview = () => {
  selectedAttachment.value = null
  previewDialog.value = false
}

const getAttachmentStyle = attachment => {
  let backgroundColor = "#F4F4F4"
  let hoverColor = "#E0E0E0"

  if (isImage(attachment)) {
    backgroundColor = "#DFF4FF"
    hoverColor = "#C2E5FA"
  } else if (isPDF(attachment)) {
    backgroundColor = "#FDECEC"
    hoverColor = "#F8D7DA"
  } else if (isWord(attachment)) {
    backgroundColor = "#E8EDFB"
    hoverColor = "#D0D9F5"
  } else if (isExcel(attachment)) {
    backgroundColor = "#E6F4E9"
    hoverColor = "#C8E6C9"
  }

  return `
    background-color: ${backgroundColor};
    border-radius: 8px;
    padding: 6px 12px;
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
  `
}

defineExpose({
  fetchKanbanItem,
})
</script>

<template>
  <VNavigationDrawer
    temporary
    persistent
    :width="700"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      title="Messages"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar
      ref="chatLogPS"
      tag="ul"
      :options="{ wheelPropagation: false }"
      class="flex-grow-1"
    >
      <div class="chat-log pa-6">
        <div
          v-for="(msg, index) in messages"
          :id="`comment-${msg.id}`"
          :key="msg.createdBy.id + String(index)"
          class="chat-group d-flex align-start mb-6"
          :class="[{
            'flex-row-reverse': msg.createdBy.id === authId,
          }]"
        >
          <div
            class="chat-avatar"
            :class="msg.createdBy.id === authId ? 'ms-4' : 'me-4'"
          >
            <VAvatar
              size="32"
              variant="flat"
            >
              <template v-if="msg.createdBy.avatar">
                <VImg
                  :src="msg.createdBy.avatar"
                  alt="Avatar"
                />
              </template>
              <template v-else>
                <span>{{ msg.createdBy.avatar_or_initials }}</span>
              </template>
            </VAvatar>
          </div>
          <div class="chat-body d-inline-flex flex-column align-end w-100">
            <div
              class="chat-content py-2 px-4 w-100 elevation-10 chat-right"
              style="background-color: rgb(var(--v-theme-surface));"
              :class="[
                msg.createdBy.id !== authId ? 'chat-left' : 'chat-right',
              ]"
            >
              <div
                v-if="msg.createdBy.id === authId"
                class="message-actions text-right"
              >
                <VIcon
                  size="16"
                  color="primary"
                  @click="editMessage(msg)"
                >
                  tabler-edit
                </VIcon>
                <VIcon
                  size="16"
                  color="error"
                  @click="deleteMessage(msg.id)"
                >
                  tabler-trash
                </VIcon>
              </div>
              <p
                class="mb-0 text-base tiptap"
                v-html="msg.content"
              />
              <div
                v-if="msg.attachments?.length"
                class="attachment-section"
              >
                <div
                  v-for="attachment in msg.attachments"
                  :key="attachment.id"
                  class="attachment-item"
                >
                  <a
                    class="attachment-link cursor-pointer d-flex flex-column align-items-center justify-center"
                    :style="getAttachmentStyle(attachment)"
                    @click.prevent="openPreview(attachment)"
                  >
                    <VIcon
                      v-if="isImage(attachment)"
                      size="24"
                      style="color: #00A5E0;"
                    >
                      tabler-photo
                    </VIcon>

                    <VIcon
                      v-else-if="isPDF(attachment)"
                      size="24"
                      style="color: #E74C3C;"
                    >
                      tabler-file-type-pdf
                    </VIcon>

                    <VIcon
                      v-else-if="isWord(attachment)"
                      size="24"
                      style="color: #2B579A;"
                    >
                      tabler-file-word
                    </VIcon>

                    <VIcon
                      v-else-if="isExcel(attachment)"
                      size="24"
                      style="color: #217346;"
                    >
                      tabler-file-excel
                    </VIcon>

                    <VIcon
                      v-else
                      size="24"
                      style="color: #636E72;"
                    >
                      tabler-file-check
                    </VIcon>

                    <span class="text-body-2 text-link">{{ attachment.size }}</span>
                  </a>
                </div>
              </div>
            </div>

            <div :class="[ msg.createdBy.id === authId ? 'text-right' : 'text-left align-self-start' ]">
              <VIcon
                v-if="msg.createdBy.id !== authId"
                id="comment-check check-animation"
                size="16"
                :class="{'check-animation': msg.markingAsRead, 'opacity-0': !msg.is_read}"
                color="success"
              >
                tabler-check
              </VIcon>
              <span class="text-sm ms-2 text-disabled">{{ msg.created_at }}</span>
            </div>
          </div>
        </div>
      </div>
    </PerfectScrollbar>
    <VDivider />
    <div class="chat-log-message-form mb-5 mx-5">
      <MessageEditor
        v-model="message"
        :available-members="localAvailableMembers"
        @update:selected-members="selectedMembers = $event"
        @update:uploaded-files="uploadedFiles = $event"
        @send="handleAddMessage"
      />
    </div>

    <VDialog
      v-if="previewDialog"
      v-model="previewDialog"
      max-width="800"
    >
      <VCard>
        <VCardTitle
          v-if="selectedAttachment"
          class="d-flex justify-space-between align-items-center"
        >
          <span class="font-weight-bold">Preview Attachment</span>
          <a
            :href="selectedAttachment.url"
            target="_blank"
            class="custom-badge text-primary"
          >Download File</a>
        </VCardTitle>
        <VCardText class="text-center">
          <template v-if="selectedAttachment">
            <VImg
              v-if="isImage(selectedAttachment)"
              :src="selectedAttachment.url"
              max-height="500"
              contain
              class="rounded-lg mx-auto"
            />

            <iframe
              v-else-if="isPDF(selectedAttachment)"
              :src="selectedAttachment.url"
              width="100%"
              height="500px"
            />

            <iframe
              v-else-if="isWord(selectedAttachment) || isExcel(selectedAttachment)"
              :src="`https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(selectedAttachment.url)}`"
              width="100%"
              height="500px"
            />

            <!-- Dacă fișierul nu este suportat -->
            <p
              v-else
              class="text-center"
            >
              <VIcon
                v-if="isWord(selectedAttachment)"
                size="48"
                style="color: #2B579A;"
              >
                tabler-file-word
              </VIcon>
              <VIcon
                v-else-if="isExcel(selectedAttachment)"
                size="48"
                style="color: #217346;"
              >
                tabler-file-excel
              </VIcon>
              <br>
              <a
                :href="selectedAttachment.url"
                target="_blank"
                class="custom-badge text-primary"
              >Download File</a>
            </p>
          </template>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn
            color="primary"
            @click="closePreview"
          >
            Close
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VNavigationDrawer>
</template>
