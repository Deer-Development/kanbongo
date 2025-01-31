<script setup>
import { ref, onUnmounted, onMounted, defineEmits, watch } from 'vue'

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

const getPriorityColor = priority => {
  if (priority == Priority.URGENT)
    return '#FF5733'
  if (priority == Priority.HIGH)
    return '#FFA533'
  if (priority == Priority.NORMAL)
    return '#338DFF'
  if (priority == Priority.LOW)
    return '#30c15a'

  return '#c2c2c3'
}

const priorityMenu = ref(null)
const membersMenu = ref(null)
const dueDate = ref(null)
const hasLocalActiveTimer = ref(props.hasActiveTimer)
const localAvailableMembers = ref([...props.availableMembers])
const membersExpanded = ref(null)

const formatTime = (seconds = 0) => {
  const roundedSeconds = Math.floor(seconds)
  const hours = Math.floor(roundedSeconds / 3600)
  const minutes = Math.floor((roundedSeconds % 3600) / 60)
  const secs = roundedSeconds % 60

  return `${hours.toString().padStart(2, '0')}:${minutes
    .toString()
    .padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

const toggleTimer = member => {
  const latestEntry = member.timeEntries[member.timeEntries.length - 1]

  if (member.isTiming) {
    clearInterval(member.timerInterval)
    latestEntry.end = new Date().toISOString()
    member.isTiming = false
  } else {
    const newTimeEntry = reactive({
      start: new Date().toISOString(),
      end: null,
      trackedTime: 0,
      trackedTimeDisplay: formatTime(0),
    })

    member.timeEntries.push(newTimeEntry)

    member.isTiming = true
    member.timerInterval = setInterval(() => {
      newTimeEntry.trackedTime += 1
      newTimeEntry.trackedTimeDisplay = formatTime(newTimeEntry.trackedTime)
    }, 1000)
  }

  emit('toggleTimer', member, props.item.id)

  // nextTick(() => {
  //   emit('refreshKanbanData')
  // })
}

watch(() => props.hasActiveTimer, () => {
  hasLocalActiveTimer.value = props.hasActiveTimer
}, { deep: true })

watch(() => props.availableMembers, () => {
  localAvailableMembers.value = [...props.availableMembers]
}, { deep: true })

const editTimer = (member, id, name) => {
  emit('editTimer', member, id, name)
}

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

const addMember = async userId => {
  const userIds = props.item.members.map(member => member.user.id)

  userIds.push(userId)
  await updateTask({ members: userIds })
}

const setPriority = async priority => {
  await updateTask({ priority })
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
      <VRow>
        <VCol cols="3">
          <VMenu
            v-model="priorityMenu"
            offset-y
          >
            <template #activator="{ props }">
              <VChip
                v-bind="props"
                :color="getPriorityColor(item.priority)"
                size="small"
                variant="elevated"
                class="comments-chip"
              >
                <VIcon
                  left
                  size="16"
                >
                  tabler-flag
                </VIcon>
              </VChip>
            </template>
            <div class="priority-options">
              <VChip
                v-for="(label, key) in Priority.data"
                :key="key"
                :color="getPriorityColor(key)"
                @click="setPriority(key)"
              >
                {{ label }}
              </VChip>
            </div>
          </VMenu>
        </VCol>
        <div class="align-content-center">
          <VIcon
            right
            icon="tabler-calendar"
          />
        </div>
        <VCol
          cols="4"
          class="px-0"
        >
          <AppDateTimePicker
            v-model="item.due_date"
            density="compact"
            variant="underlined"
            placeholder="Due Date"
            @change="updateDueDate(item.due_date)"
          />
        </VCol>
        <VCol
          cols="4"
          class="d-flex justify-end"
        >
          <VChip
            size="small"
            variant="elevated"
            :color="item.comments_count ? '#faca15' : '#c2c2c3'"
            class="comments-chip"
          >
            <VIcon
              left
              size="16"
            >
              tabler-message-2
            </VIcon> {{ item.comments_count }}
          </VChip>
        </VCol>
      </VRow>
      <VDivider class="my-2" />
      <VRow class="d-flex gap-1 justify-space-between">
        <VCol
          cols="8"
          class="d-flex gap-1"
        >
          <VMenu
            v-model="membersMenu"
            offset-y
          >
            <template #activator="{ props }">
              <VAvatar
                v-bind="props"
                size="28"
                class="cursor-pointer"
                :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
              >
                <VIcon
                  size="18"
                  icon="tabler-users-plus"
                />
              </VAvatar>
            </template>
            <VList
              class="github-style-list"
              style="min-width: 100%;"
            >
              <template v-if="localAvailableMembers.filter(member => !item.members.some(m => m.user.id === member.user_id)).length">
                <VListItem
                  v-for="(member, index) in props.availableMembers.filter(member => !item.members.some(m => m.user.id === member.user_id))"
                  :key="member.id"
                  class="github-list-item"
                  @click="addMember(member.user_id)"
                >
                  <VListItemTitle class="font-medium text-sm text-gray-800">
                    {{ member.user.full_name }}
                  </VListItemTitle>
                  <VListItemSubtitle class="text-xs text-gray-500">
                    {{ member.user.email }}
                  </VListItemSubtitle>
                </VListItem>
              </template>
              <template v-else>
                <VListItem class="empty-state text-center">
                  <VListItemTitle class="text-gray-500 text-sm">
                    No available members to add
                  </VListItemTitle>
                </VListItem>
              </template>
            </VList>
          </VMenu>
          <div class="v-avatar-group">
            <template
              v-for="(member, index) in item.members"
              :key="member.id"
            >
              <VAvatar
                v-tooltip="member.user.full_name"
                size="28"
                class="cursor-pointer"
                :color="member.isTiming ? '#38a169' : (member.timeEntries.length ? '#44b87a' : '#EEEDF0')"
                :class="member.isTiming ? 'glow' : (member.timeEntries.length ? 'worked' : '')"
                @click="editTimer(member, item.id, item.name)"
              >
                <template v-if="member.user.avatar">
                  <img
                    :src="member.user.avatar"
                    alt="Avatar"
                  >
                </template>
                <template v-else>
                  <span>{{ member.user.avatar_or_initials }}</span>
                </template>
              </VAvatar>
            </template>
          </div>
        </VCol>
        <VCol
          cols="3"
          class="d-flex justify-end"
        >
          <VChip
            v-if="item.members.some(member => authId === member.user.id)"
            size="small"
            variant="elevated"
            :color="item.members.some(member => authId === member.user.id && member.isTiming) ? '#e53e3e' : '#38a169'"
            :disabled="hasLocalActiveTimer && !item.members.some(member => authId === member.user.id && member.isTiming)"
            :class="item.members.some(member => authId === member.user.id && member.isTiming) ? 'stop-btn' : 'play-btn'"
            @click.stop="toggleTimer(item.members.find(member => authId === member.user.id))"
          >
            <VIcon
              :icon="item.members.some(member => authId === member.user.id && member.isTiming) ? 'tabler-pause' : 'tabler-play'"
              size="16"
            />
          </VChip>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
