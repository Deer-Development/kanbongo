<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  tabId: {
    type: Number,
    required: true
  },
  editor: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])

const userData = useCookie('userData')
const comments = ref([])
const newComment = ref('')
const replyContent = ref({})
const editingComment = ref(null)
const editContent = ref('')
const isLoading = ref(false)
const selectedText = ref(null)
const showCommentForm = ref(false)
const selectionInfo = ref(null)
const showNewCommentForm = ref(false)

const unresolvedCount = computed(() => {
  let count = 0
  comments.value.forEach(comment => {
    if (!comment.resolved_at) count++
    comment.replies?.forEach(reply => {
      if (!reply.resolved_at) count++
    })
  })
  return count
})

const loadComments = async () => {
  try {
    isLoading.value = true
    const response = await $api(`/container/documentation-tab/${props.tabId}/comments`, {
      method: 'GET'
    })
    comments.value = response.data
  } catch (error) {
    console.error('Failed to load comments:', error)
  } finally {
    isLoading.value = false
  }
}

const addComment = async () => {
  if (!newComment.value.trim()) return
  
  try {
    isLoading.value = true
    
    const payload = {
      content: newComment.value,
    }
    
    // Add selection data if available
    if (selectionInfo.value) {
      payload.selection_path = selectionInfo.value.path
      payload.selection_offset = selectionInfo.value.offset
      payload.selection_text = selectionInfo.value.text
    }
    
    const response = await $api(`/container/documentation-tab/${props.tabId}/comment`, {
      method: 'POST',
      body: payload
    })
    
    comments.value.unshift(response.data)
    newComment.value = ''
    showCommentForm.value = false
    selectionInfo.value = null
  } catch (error) {
    console.error('Failed to add comment:', error)
  } finally {
    isLoading.value = false
  }
}

const addReply = async (commentId) => {
  if (!replyContent.value[commentId]?.trim()) return
  
  try {
    isLoading.value = true
    const response = await $api(`/container/documentation-tab/${props.tabId}/comment`, {
      method: 'POST',
      body: {
        content: replyContent.value[commentId],
        parent_id: commentId
      }
    })
    
    // Find the parent comment and add the reply
    const parentIndex = comments.value.findIndex(c => c.id === commentId)
    if (parentIndex !== -1) {
      if (!comments.value[parentIndex].replies) {
        comments.value[parentIndex].replies = []
      }
      comments.value[parentIndex].replies.push(response.data)
      replyContent.value[commentId] = ''
    }
  } catch (error) {
    console.error('Failed to add reply:', error)
  } finally {
    isLoading.value = false
  }
}

const startEdit = (comment) => {
  editingComment.value = comment.id
  editContent.value = comment.content
}

const cancelEdit = () => {
  editingComment.value = null
  editContent.value = ''
}

const updateComment = async (comment) => {
  try {
    isLoading.value = true
    await $api(`/container/documentation-comment/${comment.id}`, {
      method: 'PUT',
      body: {
        content: editContent.value
      }
    })
    
    // Update the comment in the local state
    const isReply = !!comment.parent_id
    
    if (isReply) {
      // Find parent and update the reply
      const parentIndex = comments.value.findIndex(c => c.id === comment.parent_id)
      if (parentIndex !== -1) {
        const replyIndex = comments.value[parentIndex].replies.findIndex(r => r.id === comment.id)
        if (replyIndex !== -1) {
          comments.value[parentIndex].replies[replyIndex].content = editContent.value
        }
      }
    } else {
      // Update the main comment
      const commentIndex = comments.value.findIndex(c => c.id === comment.id)
      if (commentIndex !== -1) {
        comments.value[commentIndex].content = editContent.value
      }
    }
    
    editingComment.value = null
    editContent.value = ''
  } catch (error) {
    console.error('Failed to update comment:', error)
  } finally {
    isLoading.value = false
  }
}

const deleteComment = async (comment) => {
  if (!confirm('Are you sure you want to delete this comment?')) return
  
  try {
    isLoading.value = true
    await $api(`/container/documentation-comment/${comment.id}`, {
      method: 'DELETE'
    })
    
    const isReply = !!comment.parent_id
    
    if (isReply) {
      // Find parent and remove the reply
      const parentIndex = comments.value.findIndex(c => c.id === comment.parent_id)
      if (parentIndex !== -1) {
        comments.value[parentIndex].replies = comments.value[parentIndex].replies.filter(r => r.id !== comment.id)
      }
    } else {
      // Remove the main comment
      comments.value = comments.value.filter(c => c.id !== comment.id)
    }
  } catch (error) {
    console.error('Failed to delete comment:', error)
  } finally {
    isLoading.value = false
  }
}

const resolveComment = async (comment) => {
  try {
    isLoading.value = true
    const response = await $api(`/container/documentation-comment/${comment.id}/resolve`, {
      method: 'POST'
    })
    
    const isReply = !!comment.parent_id
    
    if (isReply) {
      // Find parent and update the reply
      const parentIndex = comments.value.findIndex(c => c.id === comment.parent_id)
      if (parentIndex !== -1) {
        const replyIndex = comments.value[parentIndex].replies.findIndex(r => r.id === comment.id)
        if (replyIndex !== -1) {
          comments.value[parentIndex].replies[replyIndex].resolved_at = response.data.resolved_at
          comments.value[parentIndex].replies[replyIndex].resolver = response.data.resolver
        }
      }
    } else {
      // Update the main comment
      const commentIndex = comments.value.findIndex(c => c.id === comment.id)
      if (commentIndex !== -1) {
        comments.value[commentIndex].resolved_at = response.data.resolved_at
        comments.value[commentIndex].resolver = response.data.resolver
      }
    }
  } catch (error) {
    console.error('Failed to resolve comment:', error)
  } finally {
    isLoading.value = false
  }
}

const unresolveComment = async (comment) => {
  try {
    isLoading.value = true
    await $api(`/container/documentation-comment/${comment.id}/unresolve`, {
      method: 'POST'
    })
    
    const isReply = !!comment.parent_id
    
    if (isReply) {
      // Find parent and update the reply
      const parentIndex = comments.value.findIndex(c => c.id === comment.parent_id)
      if (parentIndex !== -1) {
        const replyIndex = comments.value[parentIndex].replies.findIndex(r => r.id === comment.id)
        if (replyIndex !== -1) {
          comments.value[parentIndex].replies[replyIndex].resolved_at = null
          comments.value[parentIndex].replies[replyIndex].resolver = null
        }
      }
    } else {
      // Update the main comment
      const commentIndex = comments.value.findIndex(c => c.id === comment.id)
      if (commentIndex !== -1) {
        comments.value[commentIndex].resolved_at = null
        comments.value[commentIndex].resolver = null
      }
    }
  } catch (error) {
    console.error('Failed to unresolve comment:', error)
  } finally {
    isLoading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  
  // Format date in GitHub style: "Mar 4, 2025, 5:38 PM"
  const options = { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric',
    hour: 'numeric',
    minute: 'numeric',
    hour12: true
  }
  return date.toLocaleString('en-US', options)
}

const cancelNewComment = () => {
  newComment.value = ''
  selectionInfo.value = null
  showNewCommentForm.value = false
}

onMounted(() => {
  loadComments()
  
  // Adaugă event listener pentru selecția de text
  if (props.editor) {
    props.editor.on('selectionUpdate', ({ editor }) => {
      const { from, to } = editor.state.selection
      if (from !== to) {
        const text = editor.state.doc.textBetween(from, to)
        if (text.trim()) {
          selectedText.value = text
          selectionInfo.value = {
            path: [from, to],
            offset: from,
            text: text
          }
          showNewCommentForm.value = true
        }
      }
    })
  }
})
</script>

<template>
  <div class="comments-panel github-style">
    <div class="d-flex align-center justify-space-between mb-3">
      <h3 class="panel-title d-flex align-center">
        Comments
        <VChip
          v-if="unresolvedCount > 0"
          size="x-small"
          color="warning"
          class="ms-2 count-chip"
        >
          {{ unresolvedCount }}
        </VChip>
      </h3>
      <div class="d-flex align-center">
        <VBtn
          color="orange"
          variant="flat"
          size="small"
          prepend-icon="tabler-plus"
          @click="showNewCommentForm = !showNewCommentForm"
          class="me-2"
        >
          New comment
        </VBtn>
        <VBtn
          icon
          variant="text"
          size="small"
          @click="$emit('close')"
        >
          <VIcon>tabler-x</VIcon>
        </VBtn>
      </div>
    </div>
    
    <VDivider class="mb-3" />
    
    <!-- Form pentru adăugarea unui comentariu nou -->
    <VExpandTransition>
      <div v-if="showNewCommentForm || selectionInfo?.text" class="comment-form mb-3">
        <div class="d-flex align-center justify-space-between mb-2">
          <div class="form-title">Add comment</div>
          <VBtn
            v-if="!selectionInfo?.text"
            icon
            variant="text"
            size="x-small"
            @click="showNewCommentForm = false"
          >
            <VIcon size="16">tabler-x</VIcon>
          </VBtn>
        </div>
        
        <div v-if="selectionInfo?.text" class="selected-text mb-2 pa-2 bg-grey-lighten-4 rounded text-caption">
          <div class="text-caption text-medium-emphasis mb-1">Selected text:</div>
          <div class="text-body-2">"{{ selectionInfo.text }}"</div>
        </div>
        
        <div class="d-flex gap-2">
          <VAvatar
            size="32"
            color="orange-lighten-4"
            class="mt-1 flex-shrink-0 avatar-style"
            v-tooltip="userData.first_name + ' ' + userData.last_name"
          >
            <span class="text-orange-darken-2">{{ userData.first_name.charAt(0) }}{{ userData.last_name.charAt(0) }}</span>
          </VAvatar>
          
          <div class="flex-grow-1">
            <VTextarea
              v-model="newComment"
              placeholder="Add a comment..."
              rows="3"
              auto-grow
              hide-details
              variant="outlined"
              density="comfortable"
              class="mb-2 github-textarea"
            />
            
            <div class="d-flex justify-end gap-2">
              <VBtn
                variant="text"
                size="small"
                @click="cancelNewComment"
              >
                Cancel
              </VBtn>
              <VBtn
                color="orange"
                size="small"
                :disabled="!newComment.trim()"
                @click="addComment"
                :loading="isLoading"
              >
                Comment
              </VBtn>
            </div>
          </div>
        </div>
      </div>
    </VExpandTransition>
    
    <VDivider v-if="showNewCommentForm" class="mb-3" />
    
    <!-- Lista de comentarii -->
    <div v-if="isLoading && comments.length === 0" class="d-flex justify-center my-4">
      <VProgressCircular indeterminate color="orange" />
    </div>
    
    <div v-else-if="comments.length === 0 && !showNewCommentForm" class="empty-state text-center my-8">
      <VIcon
        icon="tabler-message-circle"
        size="48"
        color="grey-lighten-2"
        class="mb-2"
      />
      <div class="text-body-1 text-medium-emphasis">No comments yet</div>
      <div class="text-caption text-medium-emphasis">
        Add a comment to start a discussion
      </div>
      <VBtn
        color="orange"
        variant="outlined"
        size="small"
        class="mt-4"
        prepend-icon="tabler-plus"
        @click="showNewCommentForm = true"
      >
        Add comment
      </VBtn>
    </div>
    
    <div v-else class="comments-list">
      <div
        v-for="comment in comments"
        :key="comment.id"
        class="comment-card mb-3"
        :class="{ 'resolved': comment.resolved_at }"
      >
        <div class="comment-header d-flex align-center px-3 py-2">
          <VAvatar
            size="28"
            color="orange-lighten-4"
            class="me-2 avatar-style"
            v-tooltip="comment.user.first_name + ' ' + comment.user.last_name"
          >
            <span class="text-orange-darken-2">{{ comment.user.first_name.charAt(0) }}{{ comment.user.last_name.charAt(0) }}</span>
          </VAvatar>
          
          <div class="d-flex align-center justify-space-between flex-grow-1">
            <span class="comment-date text-caption text-medium-emphasis">
              {{ formatDate(comment.created_at) }}
            </span>
            
            <div class="d-flex align-center">
              <VChip
                v-if="comment.resolved_at"
                size="x-small"
                color="success"
                variant="flat"
                class="me-2 resolved-chip"
              >
                <template #prepend>
                  <VIcon size="12">tabler-check</VIcon>
                </template>
                Resolved
              </VChip>
              
              <VMenu location="bottom end">
                <template #activator="{ props }">
                  <VBtn
                    icon
                    variant="text"
                    size="x-small"
                    v-bind="props"
                  >
                    <VIcon size="16">tabler-dots-vertical</VIcon>
                  </VBtn>
                </template>
                
                <VList density="compact">
                  <VListItem
                    v-if="comment.user_id === userData.id"
                    @click="startEdit(comment)"
                    prepend-icon="tabler-edit"
                    title="Edit"
                  />
                  <VListItem
                    v-if="comment.user_id === userData.id"
                    @click="deleteComment(comment)"
                    prepend-icon="tabler-trash"
                    title="Delete"
                    color="error"
                  />
                  <VDivider />
                  <VListItem
                    v-if="!comment.resolved_at"
                    @click="resolveComment(comment)"
                    prepend-icon="tabler-check"
                    title="Mark as resolved"
                    color="success"
                  />
                  <VListItem
                    v-else
                    @click="unresolveComment(comment)"
                    prepend-icon="tabler-x"
                    title="Mark as unresolved"
                  />
                </VList>
              </VMenu>
            </div>
          </div>
        </div>
        
        <div class="comment-body px-3 py-2">
          <!-- Selected text if any -->
          <div v-if="comment.selection_text" class="selected-text mb-3 pa-2 bg-grey-lighten-5 rounded text-body-2">
            "{{ comment.selection_text }}"
          </div>
          
          <!-- Comment content -->
          <div v-if="editingComment !== comment.id" class="text-body-1 comment-content">
            {{ comment.content }}
          </div>
          
          <div v-else class="edit-form">
            <VTextarea
              v-model="editContent"
              rows="3"
              auto-grow
              hide-details
              variant="outlined"
              density="comfortable"
              class="mb-2 github-textarea"
            />
            
            <div class="d-flex justify-end gap-2">
              <VBtn
                variant="text"
                size="small"
                @click="cancelEdit"
              >
                Cancel
              </VBtn>
              <VBtn
                color="orange"
                size="small"
                :disabled="!editContent.trim()"
                @click="updateComment(comment)"
              >
                Save
              </VBtn>
            </div>
          </div>
        </div>
        
        <!-- Replies -->
        <div v-if="comment.replies && comment.replies.length > 0" class="replies-divider">
          <span class="replies-count">{{ comment.replies.length }} {{ comment.replies.length === 1 ? 'reply' : 'replies' }}</span>
        </div>
        
        <div v-if="comment.replies && comment.replies.length > 0" class="replies px-3 pt-2 pb-0">
          <div
            v-for="reply in comment.replies"
            :key="reply.id"
            class="reply mb-2"
            :class="{ 'resolved': reply.resolved_at }"
          >
            <div class="d-flex">
              <VAvatar
                size="22"
                color="orange-lighten-4"
                class="me-2 mt-1 flex-shrink-0 avatar-style"
                v-tooltip="reply.user.first_name + ' ' + reply.user.last_name"
              >
                <span class="text-orange-darken-2">{{ reply.user.first_name.charAt(0) }}{{ reply.user.last_name.charAt(0) }}</span>
              </VAvatar>
              
              <div class="flex-grow-1">
                <div class="d-flex align-center justify-space-between">
                  <span class="text-caption text-medium-emphasis">
                    {{ formatDate(reply.created_at) }}
                  </span>
                  
                  <div class="d-flex align-center">
                    <VChip
                      v-if="reply.resolved_at"
                      size="x-small"
                      color="success"
                      variant="flat"
                      class="me-1 resolved-chip"
                    >
                      <template #prepend>
                        <VIcon size="10">tabler-check</VIcon>
                      </template>
                      Resolved
                    </VChip>
                    
                    <VMenu location="bottom end">
                      <template #activator="{ props }">
                        <VBtn
                          icon
                          variant="text"
                          size="x-small"
                          v-bind="props"
                        >
                          <VIcon size="14">tabler-dots-vertical</VIcon>
                        </VBtn>
                      </template>
                      
                      <VList density="compact">
                        <VListItem
                          v-if="reply.user_id === userData.id"
                          @click="startEdit(reply)"
                          prepend-icon="tabler-edit"
                          title="Edit"
                        />
                        <VListItem
                          v-if="reply.user_id === userData.id"
                          @click="deleteComment(reply)"
                          prepend-icon="tabler-trash"
                          title="Delete"
                          color="error"
                        />
                        <VDivider />
                        <VListItem
                          v-if="!reply.resolved_at"
                          @click="resolveComment(reply)"
                          prepend-icon="tabler-check"
                          title="Mark as resolved"
                          color="success"
                        />
                        <VListItem
                          v-else
                          @click="unresolveComment(reply)"
                          prepend-icon="tabler-x"
                          title="Mark as unresolved"
                        />
                      </VList>
                    </VMenu>
                  </div>
                </div>
                
                <div v-if="editingComment !== reply.id" class="text-body-2 reply-content">
                  {{ reply.content }}
                </div>
                
                <div v-else class="edit-form mt-1">
                  <VTextarea
                    v-model="editContent"
                    rows="2"
                    auto-grow
                    hide-details
                    variant="outlined"
                    density="compact"
                    class="mb-2 github-textarea"
                  />
                  
                  <div class="d-flex justify-end gap-2">
                    <VBtn
                      variant="text"
                      size="x-small"
                      @click="cancelEdit"
                    >
                      Cancel
                    </VBtn>
                    <VBtn
                      color="orange"
                      size="x-small"
                      :disabled="!editContent.trim()"
                      @click="updateComment(reply)"
                    >
                      Save
                    </VBtn>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Reply Form -->
        <div class="reply-form px-3 py-2 mt-1">
          <div class="d-flex gap-2">
            <VAvatar
              size="22"
              color="orange-lighten-4"
              class="mt-1 flex-shrink-0 avatar-style"
              v-tooltip="userData.first_name + ' ' + userData.last_name"
            >
              <span class="text-orange-darken-2">{{ userData.first_name.charAt(0) }}{{ userData.last_name.charAt(0) }}</span>
            </VAvatar>
            
            <VTextField
              v-model="replyContent[comment.id]"
              placeholder="Reply to this comment..."
              variant="outlined"
              density="compact"
              hide-details
              class="flex-grow-1 github-input"
              @keyup.enter="addReply(comment.id)"
            >
              <template #append>
                <VBtn
                  icon
                  variant="text"
                  color="orange"
                  size="small"
                  :disabled="!replyContent[comment.id]?.trim()"
                  @click="addReply(comment.id)"
                >
                  <VIcon size="16">tabler-send</VIcon>
                </VBtn>
              </template>
            </VTextField>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.github-style {
  height: 100%;
  overflow-y: auto;
  padding: 16px;
  background-color: #f8f9fa;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
  
  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
  }

  &::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
    
    &:hover {
      background: #a8a8a8;
    }
  }
  
  .avatar-style {
    border-radius: 50%;
    box-shadow: 0 0 0 2px white;
    background-color: #fff3cd;
    color: #664d03;
    display: flex;
    align-items: center;
    justify-content: center;
    
    &:hover {
      transform: scale(1.05);
      transition: transform 0.2s ease;
    }
    
    &.avatar-sm {
      width: 24px;
      height: 24px;
      font-size: 12px;
    }
  }
  
  .panel-title {
    font-size: 16px;
    font-weight: 600;
    color: #24292f;
    margin: 0;
  }
  
  .count-chip {
    font-size: 11px;
    font-weight: 500;
  }
  
  .empty-state {
    padding: 32px 16px;
    border-radius: 6px;
    background-color: white;
    border: 1px dashed #d0d7de;
  }
  
  .comment-form {
    background: white;
    border: 1px solid #e1e4e8;
    border-radius: 6px;
    padding: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    
    .form-title {
      font-size: 14px;
      font-weight: 600;
      color: #24292f;
    }
    
    .selected-text {
      border-left: 3px solid #0366d6;
      padding-left: 8px;
      background-color: #f6f8fa;
    }
  }
  
  .comment-card {
    background: white;
    border: 1px solid #e1e4e8;
    border-radius: 6px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    
    &.resolved {
      opacity: 0.85;
      border-left: 3px solid #2da44e;
      
      .comment-header {
        background-color: #f6f8fa;
      }
    }
    
    &:hover {
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    
    .comment-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid #e1e4e8;
    }
    
    .comment-date {
      color: #57606a;
      font-size: 12px;
    }
    
    .comment-body {
      background-color: white;
    }
    
    .selected-text {
      border-left: 3px solid #0366d6;
      padding-left: 8px;
    }
    
    .comment-content, .reply-content {
      line-height: 1.5;
      word-break: break-word;
    }
    
    .replies-divider {
      position: relative;
      text-align: center;
      height: 24px;
      margin: 0 16px;
      
      &::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background-color: #e1e4e8;
        z-index: 1;
      }
      
      .replies-count {
        position: relative;
        background-color: #f8f9fa;
        padding: 0 8px;
        font-size: 12px;
        color: #57606a;
        z-index: 2;
      }
    }
    
    .replies {
      background-color: #f8f9fa;
      border-radius: 0 0 6px 6px;
      
      .reply {
        padding: 8px;
        border-radius: 4px;
        background-color: white;
        border: 1px solid #e1e4e8;
        margin-bottom: 8px;
        
        &.resolved {
          opacity: 0.85;
          background-color: #f8f9fa;
          border-left: 2px solid #2da44e;
        }
        
        &:last-child {
          margin-bottom: 0;
        }
      }
    }
    
    .reply-form {
      background-color: white;
      border-radius: 0 0 6px 6px;
      border-top: 1px solid #e1e4e8;
    }
    
    .resolved-chip {
      font-size: 11px;
      height: 18px;
    }
  }
  
  :deep(.github-textarea), :deep(.github-input) {
    .v-field__outline__start,
    .v-field__outline__end,
    .v-field__outline__notch {
      border-color: #d0d7de !important;
    }
    
    &:hover, &:focus-within {
      .v-field__outline__start,
      .v-field__outline__end,
      .v-field__outline__notch {
        border-color: #0366d6 !important;
      }
    }
    
    .v-field__input {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
      font-size: 14px;
    }
  }
}
</style>
