<script setup>
import { Placeholder } from '@tiptap/extension-placeholder'
import { TextAlign } from '@tiptap/extension-text-align'
import { Underline } from '@tiptap/extension-underline'
import { StarterKit } from '@tiptap/starter-kit'
import { ImageResize } from 'tiptap-extension-resize-image'
import { Document } from '@tiptap/extension-document'
import { Mention } from '@tiptap/extension-mention'
import { Text } from '@tiptap/extension-text'
import { TaskItem } from '@tiptap/extension-task-item'
import { TaskList } from '@tiptap/extension-task-list'
import { Emoji, gitHubEmojis } from '@tiptap-pro/extension-emoji'
import { Link } from '@tiptap/extension-link'
import {
  EditorContent,
  useEditor,
} from '@tiptap/vue-3'
import { FileHandler } from '@tiptap-pro/extension-file-handler'
import { useToast } from 'vue-toastification'
import suggestion from "@/components/messages/suggestion"
import suggestionEmojis from "@/components/messages/suggestionEmojis"
import { watch } from "vue"

const props = defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  placeholder: {
    type: String,
    required: false,
  },
  isEditMode: {
    type: Boolean,
    required: false,
    default: false,
  },
  availableMembers: {
    type: Array,
    required: false,
    default: () => [],
  },
  preSelectedMembers: {
    type: Array,
    required: false,
    default: () => [],
  },
  preUploadedFiles: {
    type: Array,
    required: false,
    default: () => [],
  },
  messageToReply: {
    type: Object,
    required: false,
    default: () => null,
  },
})

const emit = defineEmits(['update:modelValue', 'send', 'update:selectedMembers', 'update:uploadedFiles', 'exitEditMode', 'editMessage', 'exitReplyMode'])
const toast = useToast()
const editorRef = ref()
const uploadedFiles = ref([])
const uploadingFiles = ref([])
const selectedMembers = ref([])
const isEditing = ref(props.isEditMode)
const localAvailableMembers = ref(props.availableMembers)
const localMessageToReply = ref(props.messageToReply)

watch(() => props, () => {
  localAvailableMembers.value = props.availableMembers

  if (props.preSelectedMembers.length) {
    selectedMembers.value = props.preSelectedMembers
  }

  if (props.preUploadedFiles.length) {
    uploadedFiles.value = props.preUploadedFiles
  }

  if (props.isEditMode) {
    isEditing.value = true
  }

  if (props.messageToReply) {
    localMessageToReply.value = props.messageToReply
  }
}, { deep: true, immediate: true })

const remapMentions = content => {
  if (!content) return content

  const parser = new DOMParser()
  const doc = parser.parseFromString(content, "text/html")

  doc.querySelectorAll('.mention').forEach(mention => {
    const userId = mention.getAttribute('data-id')
    const foundUser = localAvailableMembers.value.find(member => member.user.id == userId)

    if (foundUser) {
      mention.innerText = `@${foundUser.user.full_name}`
      mention.setAttribute('data-label', foundUser.user.full_name)
    } else {
      mention.innerText = `@Unknown`
      mention.setAttribute('data-label', 'Unknown')
    }
  })

  return doc.body.innerHTML
}

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    Document,
    StarterKit,
    Placeholder.configure({
      includeChildren: true,
      placeholder: ({ node }) => {
        if (node.type.name === 'detailsSummary') {
          return 'Summary'
        }

        return null
      },
    }),
    TextAlign.configure({
      types: [
        'heading',
        'paragraph',
      ],
    }),
    Placeholder.configure({ placeholder: props.placeholder ?? 'Write something here...' }),
    Underline,
    Text,
    TaskList,
    TaskItem.configure({
      nested: true,
    }),
    ImageResize.configure({
      allowBase64: true,
    }),
    FileHandler.configure({
      allowedMimeTypes: ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
      onDrop: (currentEditor, files, pos) => {
        files.forEach(file => {
          const fileReader = new FileReader()

          fileReader.readAsDataURL(file)
          fileReader.onload = () => {
            currentEditor.chain().insertContentAt(pos, {
              type: 'image',
              attrs: {
                src: fileReader.result,
              },
            }).focus().run()
          }
        })
      },
      onPaste: (currentEditor, files) => {
        files.forEach(file => {
          const fileReader = new FileReader()

          fileReader.readAsDataURL(file)
          fileReader.onload = () => {
            currentEditor.chain().insertContentAt(currentEditor.state.selection.anchor, {
              type: 'image',
              attrs: {
                src: fileReader.result,
              },
            }).focus().run()
          }
        })
      },
    }),
    Mention.configure({
      HTMLAttributes: {
        class: 'mention',
      },
      suggestion: {
        ...suggestion,
        items: ({ query }) => {
          return localAvailableMembers.value
            .map(member => ({
              id: member.user.id,
              name: member.user.full_name,
            }))
            .filter(user => user.name.toLowerCase().includes(query.toLowerCase()))
            .slice(0, 5)
        },
      },
      renderHTML: ({ node }) => {
        return [
          'span',
          {
            class: 'mention',
            'data-type': 'mention',
            'data-id': node.attrs.id,
            'data-label': node.attrs.label || 'Unknown',
          },
          `@${node.attrs.label || 'Unknown'}`,
        ]
      },
      parseHTML: [
        {
          tag: 'span[data-type="mention"]',
          getAttrs: dom => ({
            id: dom.getAttribute('data-id'),
            label: dom.innerText.replace('@', ''),
          }),
        },
      ],
    }),
    Emoji.configure({
      emojis: gitHubEmojis,
      enableEmoticons: true,
      suggestion: {
        ...suggestionEmojis,
      },
    }),
    Link.configure({
      openOnClick: true,
      autolink: true,
      linkOnPaste: true,
      HTMLAttributes: {
        class: 'tiptap-link',
        target: '_blank',
        rel: 'noopener noreferrer',
      },
    }),
  ],
  onUpdate() {
    if (!editor.value)
      return
    emit('update:modelValue', editor.value.getHTML())
  },
})

const simulateProgress = fileData => {
  fileData.progress = 0

  const interval = setInterval(() => {
    if (fileData.progress >= 90) {
      clearInterval(interval)
    } else {
      fileData.progress += Math.random() * 10
    }
  }, 300)

  
  return interval
}

const uploadFile = async event => {
  const file = event.target.files[0]
  if (!file) return

  const fileData = { name: file.name, progress: 0, error: false }
  uploadingFiles.value.push(fileData)
  const formData = new FormData()
  formData.append('file', file)
  const progressInterval = simulateProgress(fileData)

  try {
    const response = await $api('/upload-temp-spatie', {
      method: 'POST',
      body: formData,
      onResponseError({ response }) {
        throw response._data.errors
      },
    })

    clearInterval(progressInterval)
    fileData.progress = 100

    setTimeout(() => {
      uploadingFiles.value = []
      uploadedFiles.value.push(response)
      toast.success('File uploaded successfully!')
      emit('update:uploadedFiles', uploadedFiles.value)
      
      // Add a simple space to the editor if it's empty
      if (editor.value && editor.value.isEmpty) {
        editor.value.commands.setContent('&nbsp;')
      }
    }, 500)
  } catch (error) {
    clearInterval(progressInterval)
    fileData.progress = 0
    fileData.error = true
    toast.error('Error uploading file')
  }
}

const deleteFile = async (fileId, index) => {
  try {
    await $api(`/delete-temp-spatie/${fileId}`, {
      method: 'DELETE',
      onResponseError({ response }) {
        throw response._data.errors
      },
    })
    uploadedFiles.value.splice(index, 1)
    toast.success('File deleted!')
    emit('update:uploadedFiles', uploadedFiles.value)
  } catch (error) {
    toast.error('Error deleting file')
  }
}

watch(() => props.modelValue, () => {
  const isSame = editor.value?.getHTML() === props.modelValue

  const mentions = []
  const parser = new DOMParser()
  const doc = parser.parseFromString(editor.value.getHTML(), "text/html")

  doc.querySelectorAll('.mention').forEach(mention => {
    mentions.push({
      id: mention.getAttribute('data-id'),
      name: mention.innerText.replace('@', ''),
    })
  })

  selectedMembers.value = mentions
  emit('update:selectedMembers', mentions)
  if (isSame)
    return

  const fixedContent = remapMentions(props.modelValue)

  if (fixedContent) {
    setTimeout(() => {
      editor.value.commands.setContent(fixedContent, true)
      editor.value.commands.focus('end')
    }, 0)
  }
})

const sendMessage = () => {
  if(isEditing.value) {
    emit('editMessage')
  }else{
    emit('send')
  }

  nextTick(() => {
    exitEditMode()
    exitReplyMode()
  })
}

const exitEditMode = () => {
  isEditing.value = false
  emit('exitEditMode')
  editor.value.commands.setContent('', true)
  uploadingFiles.value = []
  uploadedFiles.value = []
  selectedMembers.value = []
}

const exitReplyMode = () => {
  localMessageToReply.value = null
  emit('exitReplyMode')
}

onUnmounted(() => {
  uploadingFiles.value.forEach(file => {
    clearInterval(file.progress)
  })

  uploadingFiles.value = []
  uploadedFiles.value = []

  if (editor.value) {
    editor.value.destroy()
  }
})

// Add this before the template
const editorActions = [
  {
    icon: 'tabler-paperclip',
    action: () => document.querySelector('input[type="file"]').click(),
    tooltip: 'Attach file'
  },
  {
    icon: 'tabler-bold',
    command: 'bold',
    action: editor => editor.chain().focus().toggleBold().run(),
    tooltip: 'Bold'
  },
  {
    icon: 'tabler-italic',
    command: 'italic',
    action: editor => editor.chain().focus().toggleItalic().run(),
    tooltip: 'Italic'
  },
  {
    icon: 'tabler-strikethrough',
    command: 'strike',
    action: editor => editor.chain().focus().toggleStrike().run(),
    tooltip: 'Strike'
  },
  {
    icon: 'tabler-underline',
    command: 'underline',
    action: editor => editor.commands.toggleUnderline(),
    tooltip: 'Underline'
  },
  {
    icon: 'tabler-align-left',
    command: { textAlign: 'left' },
    action: editor => editor.chain().focus().setTextAlign('left').run(),
    tooltip: 'Align left'
  },
  {
    icon: 'tabler-align-center',
    command: { textAlign: 'center' },
    action: editor => editor.chain().focus().setTextAlign('center').run(),
    tooltip: 'Align center'
  },
  {
    icon: 'tabler-align-right',
    command: { textAlign: 'right' },
    action: editor => editor.chain().focus().setTextAlign('right').run(),
    tooltip: 'Align right'
  },
  {
    icon: 'tabler-link',
    command: 'link',
    action: editor => {
      const previousUrl = editor.getAttributes('link').href
      const url = window.prompt('URL', previousUrl)
      
      // cancelled
      if (url === null) {
        return
      }
      
      // empty
      if (url === '') {
        editor.chain().focus().extendMarkRange('link').unsetLink().run()
        return
      }
      
      // update link
      editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
    },
    tooltip: 'Insert link'
  },
]
</script>

<template>
  <div class="message-editor">
    <div
      v-if="isEditing"
      class="edit-mode-banner"
    >
      <span class="edit-mode-text">
        <VIcon
          size="16"
          icon="tabler-pencil"
          class="me-2"
        />
        Editing message
      </span>
      <VBtn
        size="x-small"
        variant="text"
        color="default"
        @click="exitEditMode"
      >
        <VIcon size="16">tabler-x</VIcon>
      </VBtn>
    </div>

    <div
      v-if="localMessageToReply"
      class="reply-banner"
    >
      <div class="reply-preview">
        <VIcon
          size="16"
          icon="tabler-message-reply"
          class="me-2"
          color="warning"
        />
        <div
          class="reply-content tiptap"
          v-html="localMessageToReply.content"
        />
      </div>
      <VBtn
        size="x-small"
        variant="text"
        color="default"
        @click="exitReplyMode"
      >
        <VIcon size="16">tabler-x</VIcon>
      </VBtn>
    </div>

    <div class="editor-main">
      <div
        v-if="uploadedFiles.length || uploadingFiles.length"
        class="attachments-section"
      >
        <div class="attachments-wrapper">
          <TransitionGroup name="attachment">
            <div
              v-for="file in uploadingFiles"
              :key="`uploading-${file.name}`"
              class="attachment-item uploading"
            >
              <div class="attachment-info">
                <VIcon
                  size="16"
                  icon="tabler-file-upload"
                />
                <span class="attachment-name">{{ file.name }}</span>
              </div>
              <VProgressLinear
                :model-value="file.progress"
                color="primary"
                height="2"
                class="upload-progress"
              />
            </div>

            <div
              v-for="(file, index) in uploadedFiles"
              :key="`uploaded-${file.id}`"
              class="attachment-item"
            >
              <div class="attachment-info">
                <VIcon
                  size="16"
                  icon="tabler-file-check"
                  color="success"
                />
                <span class="attachment-name">{{ file.name }}</span>
              </div>
              <VBtn
                size="x-small"
                variant="text"
                color="default"
                class="delete-btn"
                @click="deleteFile(file.id, index)"
              >
                <VIcon size="14">tabler-trash</VIcon>
              </VBtn>
            </div>
          </TransitionGroup>
        </div>
      </div>

      <EditorContent
        ref="editorRef"
        class="editor-content"
        :editor="editor"
      />

      <div class="editor-toolbar">
        <div class="toolbar-actions">
          <input
            ref="fileInput"
            type="file"
            class="hidden"
            @change="uploadFile"
          >
          <VTooltip
            v-for="(action, index) in editorActions"
            :key="index"
            :text="action.tooltip"
            location="top"
          >
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                size="x-small"
                variant="text"
                :color="editor?.isActive(action.command) ? 'primary' : 'default'"
                @click="action.action(editor)"
              >
                <VIcon :icon="action.icon" />
              </VBtn>
            </template>
          </VTooltip>
        </div>

        <VBtn
          color="primary"
          size="small"
          :disabled="!editor || editor.isEmpty"
          class="send-button"
          @click="sendMessage"
        >
          <VIcon
            size="18"
            icon="tabler-send"
          />
        </VBtn>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
$github-colors: (
  text-primary: #24292f,
  text-secondary: #57606a,
  border: #d0d7de,
  bg-primary: #ffffff,
  bg-secondary: #f6f8fa,
  accent: #0969da,
  hover: #f3f4f6,
  warning-bg: #fff8c5,
  warning-border: #eed888,
  warning-text: #9a6700
);

$spacing: (
  xs: 4px,
  sm: 8px,
  md: 12px,
  lg: 16px
);

.message-editor {
  background: map-get($github-colors, bg-primary);
  border: 1px solid map-get($github-colors, border);
  border-radius: 6px;
  margin: map-get($spacing, md);
}

.edit-mode-banner,
.reply-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: map-get($spacing, sm) map-get($spacing, md);
  background: map-get($github-colors, warning-bg);
  border-bottom: 1px solid map-get($github-colors, warning-border);
  color: map-get($github-colors, warning-text);
  font-size: 13px;
}

.reply-preview {
  display: flex;
  align-items: center;
  gap: map-get($spacing, xs);
  max-width: 90%;
  
  .reply-content {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 13px;
  }
}

.attachments-section {
  border-bottom: 1px solid map-get($github-colors, border);
  padding: map-get($spacing, xs) map-get($spacing, sm);
  max-height: 120px;
  overflow: hidden;
}

.attachments-wrapper {
  display: flex;
  overflow-x: auto;
  gap: map-get($spacing, xs);
  padding-bottom: map-get($spacing, sm); // For scrollbar space
  
  &::-webkit-scrollbar {
    height: 4px;
  }
  
  &::-webkit-scrollbar-track {
    background: map-get($github-colors, bg-secondary);
    border-radius: 4px;
  }
  
  &::-webkit-scrollbar-thumb {
    background: darken(map-get($github-colors, border), 10%);
    border-radius: 4px;
    
    &:hover {
      background: darken(map-get($github-colors, border), 15%);
    }
  }
}

.attachment-item {
  display: flex;
  align-items: center;
  background: map-get($github-colors, bg-secondary);
  border: 1px solid map-get($github-colors, border);
  border-radius: 4px;
  padding: map-get($spacing, xs) map-get($spacing, sm);
  min-width: 200px;
  max-width: 200px;
  position: relative;
  
  &.uploading {
    padding-bottom: map-get($spacing, md);
    
    .upload-progress {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      border-radius: 0 0 4px 4px;
    }
  }

  .attachment-info {
    display: flex;
    align-items: center;
    gap: map-get($spacing, sm);
    flex: 1;
    min-width: 0; // For text truncation
    
    .attachment-name {
      font-size: 12px;
      color: map-get($github-colors, text-primary);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }

  .delete-btn {
    opacity: 0.7;
    transition: opacity 0.2s ease;
    
    &:hover {
      opacity: 1;
      background: rgba(map-get($github-colors, text-primary), 0.1);
    }
  }
}

.editor-content {
  min-height: 100px;
  padding: map-get($spacing, md);

  :deep(.ProseMirror) {
    min-height: 100px;
    outline: none;
    font-size: 14px;
    line-height: 1.6;
    color: map-get($github-colors, text-primary);

    p {
      margin: 0;
    }

    p.is-empty::before {
      content: attr(data-placeholder);
      float: left;
      color: map-get($github-colors, text-secondary);
      pointer-events: none;
      height: 0;
    }

    .mention {
      color: map-get($github-colors, accent);
      background: rgba(map-get($github-colors, accent), 0.1);
      padding: 2px 4px;
      border-radius: 4px;
      font-weight: 500;
      white-space: nowrap;
    }

    .tiptap-link {
      color: map-get($github-colors, accent);
      text-decoration: underline;
      cursor: pointer;
      
      &:hover {
        text-decoration: none;
      }
    }

    .attachment-placeholder {
      opacity: 0;
      height: 1px;
      margin: 0;
      padding: 0;
      pointer-events: none;
      user-select: none;
    }
  }
}

.editor-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: map-get($spacing, xs) map-get($spacing, sm);
  background: map-get($github-colors, bg-secondary);
  border-top: 1px solid map-get($github-colors, border);
  border-radius: 0 0 6px 6px;

  .toolbar-actions {
    display: flex;
    gap: map-get($spacing, xs);
    flex-wrap: wrap;
  }
}

.send-button {
  border-radius: 999px;
  min-width: 36px;
  padding: 0 10px;
}

// Improve attachment transitions
.attachment-enter-active,
.attachment-leave-active {
  transition: all 0.3s ease;
  max-width: 200px;
}

.attachment-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.attachment-leave-to {
  opacity: 0;
  max-width: 0;
  padding: 0;
  margin: 0;
  border-width: 0;
}

.hidden {
  display: none;
}
</style>
