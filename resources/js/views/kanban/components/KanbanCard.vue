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
    class="kanban-card position-relative"
  >
    <div class="card-header">
      <VMenu offset-y>
        <template #activator="{ props }">
          <div
            v-bind="props"
            class="custom-badge pl-0 pt-0 align-self-end"
          >
            <span class="pr-1">{{ item.id }}</span>
            <VIcon
              size="14"
              color="#374151"
              icon="tabler-dots-circle-horizontal"
            />
          </div>
        </template>

        <div class="d-flex flex-column dropdown-menu p-2 mt-0 pt-0">
          <div
            class="custom-badge mt-2"
            @click="isEditingName = true"
          >
            <VIcon
              size="16"
              color="primary"
            >
              tabler-edit
            </VIcon>
            <span class="text-body-5 text-link">Edit Title</span>
          </div>
          <div
            v-if="(isSuperAdmin || isOwner) && !item.tracked_time"
            class="custom-badge mt-2"
            @click="deleteKanbanItem"
          >
            <VIcon
              size="16"
              color="error"
            >
              tabler-trash
            </VIcon>
            <span class="text-body-2 text-link">Delete Task</span>
          </div>
        </div>
      </VMenu>
      <div class="d-flex align-center pl-0 w-100">
        <div class="pr-0">
          <VIcon
            class="card-handler"
            size="20"
            color="primary"
            icon="tabler-grip-vertical"
          />
        </div>
        <div class="pl-0 pr-1 mx-0 w-100">
          <VTextarea
            v-if="isEditingName"
            v-model="item.name"
            :rules="[requiredValidator, maxLengthValidator(item.name, 255)]"
            rows="3"
            dense
            variant="underlined"
            class="custom-textarea"
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
          :task="item"
          :auth="authDetails"
          :has-active-timer="hasLocalActiveTimer"
          :member="item.members.find(member => auth.id === member.user_id)"
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
