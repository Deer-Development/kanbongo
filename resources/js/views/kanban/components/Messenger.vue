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
const messageToReply = ref(null)
const chatLogPS = ref()
const previewDialog = ref(false)
const isEditMode = ref(false)
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
      parent_id: messageToReply.value ? messageToReply.value.id : null,
    },
  })

  if (res) {
    messages.value = res.data
  }

  message.value = ''
  selectedMembers.value = []
  uploadedFiles.value = []
  messageToReply.value = null
  currentMessageId.value = null
  isEditMode.value = false

  await nextTick(() => {
    exitEditMode()
    scrollToBottom()
  })

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

const editMessage = async messageToEdit => {
  if (messageToEdit) {
    exitReplyMode()
    message.value = messageToEdit.content
    uploadedFiles.value = messageToEdit.attachments
    selectedMembers.value = messageToEdit.mentioned_users
    currentMessageId.value = messageToEdit.id
    isEditMode.value = true
  }
}

const exitEditMode = () => {
  currentMessageId.value = null
  isEditMode.value = false
  messageToReply.value = null
  messageToReply.value = null
  message.value = ''
  uploadedFiles.value = []
  selectedMembers.value = []
}

const exitReplyMode = () => {
  messageToReply.value = null
  message.value = ''
}

const submitEditMessage = async messageId => {
  const res = await $api(`/comment/${messageId}`, {
    method: 'PUT',
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

    await nextTick(() => {
      exitEditMode()
      scrollToBottom()
    })
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
  exitEditMode()

  emit('refreshKanbanData')
}

const replyMessage = msg => {
  exitEditMode()

  messageToReply.value = msg
  message.value = `<span class="mention" data-type="mention" data-id="${msg.createdBy.id}" data-label="${msg.createdBy.full_name}" contenteditable="false">@${msg.createdBy.full_name}</span> `
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
    padding: 2px 10px;
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
      :title="`#${localKanbanItem.id}) ${localKanbanItem.name}`"
      class="py-2 px-2"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <div
      ref="chatLogPS"
      class="flex-grow-1"
      style="height: calc(100% - 64px); overflow: auto;"
    >
      <div
        v-if="messages.length"
        class="chat-log pa-6"
      >
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
              class="chat-content py-2 px-2 w-100 elevation-1 chat-right"
              style="background-color: rgb(var(--v-theme-surface));"
              :class="[
                msg.createdBy.id !== authId ? 'chat-left' : 'chat-right',
              ]"
            >
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
                      size="16"
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
              <div class="d-flex justify-space-between gap-1">
                <div class="d-flex flex-column gap-2 w-100 ">
                  <div class="chat-parent-message-preview">
                    <div
                      v-if="msg.parent"
                      class="chat-message-box-reply w-100"
                    >
                      <div
                        class="chat-parent-message-text me reply tiptap"
                        v-html="msg.parent.content"
                      />
                    </div>
                  </div>

                  <p
                    class="mb-0 text-message-content tiptap"
                    v-html="msg.content"
                  />
                </div>
                <div
                  class="message-actions text-right"
                  style="max-height: 20px !important;"
                >
                  <VMenu>
                    <template #activator="{ props }">
                      <div
                        class="custom-badge"
                        v-bind="props"
                      >
                        <VIcon

                          size="14"
                          color="primary"
                        >
                          tabler-dots-vertical
                        </VIcon>
                      </div>
                    </template>
                    <div class="d-flex flex-column dropdown-menu p-2">
                      <div
                        v-if="msg.createdBy.id === authId"
                        class="d-flex gap-2 justify-end"
                      >
                        <div
                          class="custom-badge"
                          @click="editMessage(msg)"
                        >
                          <VIcon
                            size="16"
                            color="info"
                          >
                            tabler-edit
                          </VIcon>
                        </div>
                        <div
                          class="custom-badge"
                          @click="deleteMessage(msg.id)"
                        >
                          <VIcon
                            size="16"
                            color="error"
                          >
                            tabler-trash
                          </VIcon>
                        </div>
                      </div>
                      <div
                        class="custom-badge mt-2"
                        @click="replyMessage(msg)"
                      >
                        <VIcon
                          size="16"
                          color="primary"
                        >
                          tabler-message-reply
                        </VIcon>
                        <span class="text-body-2 text-link">Reply</span>
                      </div>
                    </div>
                  </VMenu>
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
      <div
        v-if="messages.length === 0"
        class="d-flex flex-column align-center justify-center text-center py-10"
        style="height: 100%;"
      >
        <VIcon
          size="48"
          color="grey"
        >
          tabler-mood-smile-beam
        </VIcon>
        <p class="text-body-1 text-disabled mt-3">
          No messages yet. <br>
          <strong>Be the first to share your thoughts!</strong> <br>
          Type "@" to mention team members, add emojis, or upload files
        </p>
      </div>
    </div>

    <div class="chat-log-message-form mb-5">
      <MessageEditor
        v-model="message"
        v-model:pre-uploaded-files="uploadedFiles"
        v-model:is-edit-mode="isEditMode"
        :available-members="localAvailableMembers"
        :pre-selected-members="selectedMembers"
        :message-to-reply="messageToReply"
        @update:selected-members="selectedMembers = $event"
        @update:uploaded-files="uploadedFiles = $event"
        @send="handleAddMessage"
        @edit-message="submitEditMessage(currentMessageId)"
        @exit-edit-mode="exitEditMode"
        @exit-reply-mode="exitReplyMode"
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

<style scoped>
.chat-parent-message-preview{
  border-style: none none none solid;
  border-width: 1px 1px 1px 3px;
  border-color: #000 #000 #000 #f6c44c;
  background-color: #0000;
  border-radius: 0;
  align-items: center;
  min-width: auto;
  min-height: auto;
  max-height: 40px;
  margin-left: 6px;
  padding-top: 0;
  padding-bottom: 0;
  padding-right: 15px;
  display: flex;
  overflow: hidden;
}
.chat-message-box-reply {
  background-color: #f2f3fa;
  border-radius: 0 14px 14px 0;
  max-width: none;
  min-height: auto;
  max-height: 48px;
  margin-top: 0;
  margin-bottom: 0;
  padding-top: 4px;
  padding-bottom: 4px;
  padding-left: 8px;
  font-size: 13.7px;
  line-height: 19px;
  overflow: auto;
}
.chat-parent-message-text.me.reply{
  opacity: .8;
  color: #2d2e32;
  text-align: left;
  min-height: auto;
  max-height: none;
  margin-bottom: 0;
  font-family: Montserrat, sans-serif;
  font-size: 13.5px;
  line-height: 19px;
  display: inline;
  overflow: auto;
}
.text-message-content{
  font-family: Montserrat, sans-serif;
  font-size: 14.5px;
  line-height: 19px;
}
</style>
