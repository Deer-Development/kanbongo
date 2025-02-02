<script setup>
import { ref, defineEmits, watch } from 'vue'
import BadgeDateTimePicker from "@core/components/app-form-elements/BadgeDateTimePicker.vue"
import PriorityBadge from "@/views/kanban/components/PriorityBadge.vue"
import TimerBadge from "@/views/kanban/components/TimerBadge.vue"

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
  authId: { type: Number, required: false },
})

const emit = defineEmits([
  "editKanbanItem",
  "toggleTimer",
  "editTimer",
  'refreshKanbanData',
  'addMember',
])

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

const dueDate = ref(null)
const hasLocalActiveTimer = ref(props.hasActiveTimer)
const localAvailableMembers = ref([...props.availableMembers])
const localActiveUsers = ref([...props.activeUsers])

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
</script>

<template>
  <VCard
    v-if="item"
    :ripple="false"
    :link="false"
    class="kanban-card position-relative"
  >
    <div class="card-header">
      <div class="d-flex align-center">
        <VIcon
          class="card-handler"
          size="20"
          color="primary"
          icon="tabler-grip-vertical"
        />
        <h3
          v-tooltip="item.name"
          class="card-title cursor-pointer"
          @click="$emit('editKanbanItem', item.id)"
        >
          {{ item.name }}
        </h3>
      </div>
    </div>

    <VCardText class="card-body">
      <div class="d-flex gap-2">
        <PriorityBadge
          :priority="item.priority"
          :item-id="item.id"
          @update-priority="setPriority"
        />

        <BadgeDateTimePicker
          v-model="item.due_date"
          density="compact"
          variant="underlined"
          placeholder="Date"
          clearable
          :config="{ altFormat: 'M, j Y', altInput: true }"
          @change="updateDueDate(item.due_date)"
        />
        <div class="d-flex justify-end">
          <div
            class="custom-badge"
            :class="item.comments_count ? 'has-comments' : ''"
          >
            <VIcon
              left
              size="16"
            >
              tabler-message-2
            </VIcon>
            <span>{{ item.comments_count }}</span>
          </div>
        </div>
      </div>
      <VDivider class="my-2" />
      <TimerBadge
        :task="item"
        :auth-id="authId"
        :has-active-timer="hasLocalActiveTimer"
        :member="item.members.find(member => authId === member.user_id)"
        :active-users="localActiveUsers"
        @toggle-timer="toggleTimer"
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
