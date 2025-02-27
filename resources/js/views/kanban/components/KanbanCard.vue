<script setup>
import { ref, defineEmits, watch } from 'vue'
import PriorityBadge from "@/views/kanban/components/PriorityBadge.vue"
import TimerBadge from "@/views/kanban/components/TimerBadge.vue"
import { watchDebounced } from "@vueuse/core"

const props = defineProps({
  item: {
    type: null,
    required: true,
  },
  boardId: {
    type: Number,
    required: true,
  },
  boardName: {
    type: String,
    required: true,
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
  isSuperAdmin: { type: Boolean, required: false, default: false },
  hasActiveTimer: { type: Boolean, required: false, default: false },
  isOwner: { type: Boolean, required: false, default: false },
  isMember: { type: Boolean, required: false, default: false },
  auth: { type: Object, required: false },
})

const emit = defineEmits([
  "editKanbanItem",
  "toggleTimer",
  "editTimer",
  'refreshKanbanData',
  'addMember',
])

const dueDate = ref(null)
const hasLocalActiveTimer = ref(props.hasActiveTimer)
const localItem = ref(props.item)
const authDetails = ref(props.auth)
const localAvailableMembers = ref([...props.availableMembers])
const localActiveUsers = ref([...props.activeUsers])
const isEditingName = ref(false)
const isHovered = ref(false)
const selectedTags = ref([])

const toggleTimer = member => {
  emit('toggleTimer', member, props.item.id)
}

watch(() => props.hasActiveTimer, () => {
  hasLocalActiveTimer.value = props.hasActiveTimer
}, { deep: true })

watch(() => props.availableMembers, () => {
  localAvailableMembers.value = [...props.availableMembers]
}, { deep: true })

watch(() => props.activeUsers, () => {
  localActiveUsers.value = [...props.activeUsers]
}, { deep: true })

watch(
  () => props.auth,
  () => {
    authDetails.value = props.auth
  },
  { deep: true, immediate: true },
)

watch(
  () => props.item,
  () => {
    selectedTags.value = props.item.tags
    localItem.value = props.item
  },
  { deep: true, immediate: true },
)

const updateTask = async updates => {
  const res = await $api(`/task/${props.item.id}`, {
    method: 'PUT',
    body: {
      name: props.item.name,
      priority: props.item.priority,
      due_date: dueDate.value,
      members: props.item.members.map(member => member.user.id),
      ...updates,
    },
  })

  if (res) {
    emit('refreshKanbanData')
  }
}

const updateTags = async () => {
  await $api(`/task/attach-tags/${props.item.id}`, {
    method: 'POST',
    body: {
      tags: selectedTags.value.map(tag => tag.id),
    },
  })
}

watch(() => props.item.members, (value, oldValue) => {
  if (value.length !== oldValue.length) {
    updateTask({ members: props.item.members.map(member => member.user.id) })
  }
}, { deep: true })

const setPriority = async data => {
  await updateTask({ priority: data.priority })
}

const updateDueDate = async date => {
  await updateTask({ due_date: date })
}

const deleteKanbanItem = () => {
  emit('deleteKanbanItem', {
    item: props.item,
  })
}

watchDebounced(
  () => isEditingName.value,
  async value => {
    if (!value) {
      await updateTask({ name: props.item.name })
    }
  },
  { debounce: 100 },
)
</script>

<template>
  <VCard
    v-if="item"
    :ripple="false"
    :link="false"
    class="kanban-card"
  >
    <div class="card-header">
      <div class="d-flex align-center w-100">
        <div class="pr-0">
          <VIcon
            class="card-handler"
            size="20"
            color="primary"
            icon="tabler-grip-vertical"
          />
        </div>
        <div class="pl-0 pr-1 mx-0 flex-grow-1">
          <VTextarea
            v-if="isEditingName"
            v-model="item.name"
            :rules="[requiredValidator, maxLengthValidator(item.name, 255)]"
            rows="3"
            dense
            variant="underlined"
            class="test-design-textarea"
          >
            <template #append-inner>
              <VIcon
                :key="`icon-${isEditingName}`"
                size="20"
                :color="isEditingName ? 'success' : 'info'"
                :icon="isEditingName ? 'tabler-circle-check' : 'tabler-edit-circle'"
                @click="isEditingName = !isEditingName"
              />
            </template>
          </VTextarea>
          <div
            v-else
            class="d-flex align-center justify-space-between w-100"
            @mouseenter="isHovered = true"
            @mouseleave="isHovered = false"
          >
            <div class="d-flex align-center justify-space-between w-100">
              <h3 class="card-title">
                {{ item.name }}
              </h3>
            </div>
          </div>
        </div>
      </div>

      <div class="action-trigger-wrapper">
        <VMenu location="bottom end" offset="4">
          <template #activator="{ props }">
            <div v-bind="props" class="action-trigger">
              <span class="sequence-id">{{ item.sequence_id }}</span>
              <VIcon size="12" icon="tabler-chevron-down" class="action-icon" />
            </div>
          </template>

          <VList class="dropdown-menu pa-2" density="compact">
            <VListItem
              class="menu-item"
              @click="isEditingName = true"
            >
              <template #prepend>
                <VIcon
                  size="16"
                  color="primary"
                  icon="tabler-edit"
                />
              </template>
              <VListItemTitle>Edit Title</VListItemTitle>
            </VListItem>

            <VListItem
              v-if="(isSuperAdmin || isOwner) && !item.tracked_time"
              class="menu-item"
              color="error"
              @click="deleteKanbanItem"
            >
              <template #prepend>
                <VIcon
                  size="16"
                  icon="tabler-trash"
                />
              </template>
              <VListItemTitle>Delete Task</VListItemTitle>
            </VListItem>
          </VList>
        </VMenu>
      </div>
    </div>

    <VCardText class="card-body">
      <div class="d-flex gap-2 align-center">
        <PriorityBadge
          :priority="item.priority"
          :item-id="item.id"
          @update-priority="setPriority"
        />
        <div
          class="cursor-pointer"
          @click="$emit('editKanbanItem', item.id)"
        >
          <VIcon
            left
            size="16"
            :color="item.has_unread_comments ? '#FFA533' : (item.comments_count ? '#6C757D' : '#d2d2d5')"
          >
            tabler-message-filled
          </VIcon>
        </div>

        <!--        <BadgeDateTimePicker -->
        <!--          v-model="item.due_date" -->
        <!--          density="compact" -->
        <!--          variant="underlined" -->
        <!--          placeholder="Date" -->
        <!--          :config="{ altFormat: 'M j', altInput: true }" -->
        <!--          @change="updateDueDate(item.due_date)" -->
        <!--        /> -->

        <TimerBadge
          :task="localItem"
          :auth="authDetails"
          :has-active-timer="hasLocalActiveTimer"
          :member="localItem.members.find(member => auth.id === member.user_id)"
          :active-users="localActiveUsers"
          @toggle-timer="toggleTimer"
          @refresh-kanban-data="emit('refreshKanbanData')"
        />
      </div>
      <VDivider class="my-2" />
      <TagsComponent
        v-model="selectedTags"
        placeholder="Tags"
        @update:model-value="updateTags"
        @refresh-kanban-data="emit('refreshKanbanData')"
      />
      <VDivider class="my-2" />
      <DynamicMemberSelector
        v-model="item.members"
        :items="localAvailableMembers"
        :is-super-admin="props.isSuperAdmin"
        item-title="user.full_name"
        item-value="user.id"
        :active-users="localActiveUsers"
        :tracked-users="item.tracked_time?.usersTracked"
        :task-id="item.id"
        dense
        outlined
      />
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-header {
  padding: 8px;
  position: relative;
}

.action-trigger-wrapper {
  position: absolute;
  bottom: 0;
  right: 0;
}

.action-trigger {
  cursor: pointer;
  padding: 2px 8px;
  border-radius: 4px 0 0 0;
  transition: all 0.15s ease;
  display: flex;
  align-items: center;
  gap: 2px;
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  min-height: 20px;
  
  &:hover {
    background: rgba(var(--v-theme-surface-variant), 0.08);
    border-color: rgba(var(--v-theme-on-surface), 0.2);
  }

  .sequence-id {
    font-size: 0.7rem;
    color: rgba(var(--v-theme-on-surface), 0.8);
    font-weight: 500;
    letter-spacing: -0.25px;
    line-height: 1;
  }

  .action-icon {
    font-size: 12px;
    color: rgba(var(--v-theme-on-surface), 0.6);
  }
}

.card-header > .d-flex.align-center {
  padding-bottom: 16px;
  margin-bottom: 0;
}

.card-title {
  // padding-right: 40px; // removed
}

.kanban-card {
  &:hover {
    .action-trigger {
      opacity: 1;
    }
  }
}

.dropdown-menu {
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(var(--v-theme-on-surface), 0.08);
  min-width: 160px;

  .menu-item {
    border-radius: 4px;
    margin-bottom: 2px;
    min-height: 36px;
    padding: 0 8px;

    &:hover {
      background: rgba(var(--v-theme-surface-variant), 0.06);
    }

    .v-list-item-title {
      font-size: 0.875rem;
      font-weight: 400;
    }

    &.v-list-item--density-compact {
      min-height: 32px;
    }
  }
}

.test-design-textarea {
  :deep(.v-field__input) {
    min-height: unset !important;
    font-size: 11px !important;
    line-height: 13px !important;
    font-weight: 600 !important;
  }

  :deep(.v-field__append-inner) {
    padding-top: 2px;
  }
}
</style>
