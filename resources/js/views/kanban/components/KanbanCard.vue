<script setup>
import { ref, onUnmounted, onMounted, defineEmits, watch } from 'vue'
import MemberCard from "@/views/kanban/components/MemberCard.vue"
import { remapNodes } from "@formkit/drag-and-drop"

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
    return '#33FF6D'
}

const priorityMenu = ref(null)
const membersMenu = ref(null)
const dueDate = ref(null)
const hasLocalActiveTimer = ref(props.hasActiveTimer)

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

const initializeMemberTimers = () => {
  props.item?.members?.forEach(member => {
    const latestEntry = member.timeEntries[member.timeEntries.length - 1]
    if (latestEntry && !latestEntry.end) {
      member.isTiming = true
      member.timerInterval = setInterval(() => {
        latestEntry.trackedTime += 1
        latestEntry.trackedTimeDisplay = formatTime(latestEntry.trackedTime)
      }, 1000)

      membersExpanded.value = 0
    } else {
      member.isTiming = false
    }
  })

  dueDate.value = props.item.due_date
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

  nextTick(() => {
    emit('refreshKanbanData')
  })
}

watch(() => props.hasActiveTimer, () => {
  hasLocalActiveTimer.value = props.hasActiveTimer
}, { deep: true })

const editTimer = (member, id) => {
  emit('editTimer', member, id)
}

const updateTask = async updates => {
  const res = await $api(`/task/${props.item.id}`, {
    method: 'PUT',
    body: {
      name: props.item.name,
      description: props.item.description,
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

const updateDueDate = async () => {
  await updateTask({ due_date: dueDate.value })
}

onMounted(() => {
  initializeMemberTimers()
})

onUnmounted(() => {
  props.item?.members?.forEach(member => {
    if (member.timerInterval) clearInterval(member.timerInterval)
  })
})
</script>

<template>
  <VCard
    v-if="item"
    :ripple="false"
    :link="false"
    class="kanban-card position-relative"
  >
    <div
      class="card-header"
    >
      <div class="d-flex align-center">
        <VIcon
          class="card-handler"
          size="20"
          color="primary"
          icon="tabler-grip-vertical"
        />
        <h3
          v-tooltip="item.name"
          class="card-title truncate"
          @click="$emit('editKanbanItem', item.id)"
        >
          {{ item.name }}
        </h3>
      </div>
    </div>

    <VCardText class="card-body">
      <div v-if="dueDate">
        <AppDateTimePicker
          v-model="dueDate"
          density="compact"
          outlined
          prepend-icon="tabler-calendar"
          @change="updateDueDate"
        />
        <VDivider class="my-2" />
      </div>
      <div class="kanban-row">
        <VMenu
          v-model="priorityMenu"
          offset-y
        >
          <template #activator="{ props }">
            <VBtn
              v-bind="props"
              :color="getPriorityColor(item.priority)"
              size="x-small"
              class="btn-github"
              prepend-icon="tabler-flag"
            >
              {{ Priority.getName(item.priority) || "Set Priority" }}
            </VBtn>
          </template>
          <div class="priority-options">
            <VChip
              v-for="(label, key) in Priority.data"
              :key="key"
              :color="getPriorityColor(key)"
              variant="flat"
              class="chip-priority-github"
              @click="setPriority(key)"
            >
              {{ label }}
            </VChip>
          </div>
        </VMenu>
        <div class="d-flex flex gap-2">
          <VChip
            v-if="item.comments.length"
            size="x-small"
            variant="text"
            color="warning"
            class="comments-chip"
          >
            <VIcon
              left
              size="16"
            >
              tabler-message-2
            </VIcon>
            {{ item.comments.length }}
          </VChip>
          <VChip
            v-if="isSuperAdmin || item.members.some(member => authId === member.user.id)"
            size="x-small"
            variant="text"
            class="play-stop-btn"
            :disabled="hasLocalActiveTimer && !item.members.some(member => authId === member.user.id && member.isTiming)"
            :class="item.members.some(member => authId === member.user.id && member.isTiming) ? 'stop-btn' : 'play-btn'"
            @click.stop="toggleTimer(item.members.find(member => authId === member.user.id))"
          >
            <VIcon
              :icon="item.members.some(member => authId === member.user.id && member.isTiming) ? 'tabler-pause' : 'tabler-play'"
              size="14"
              class="play-stop-icon"
            />
          </VChip>
        </div>
      </div>
      <VDivider class="my-2" />
      <div class="d-flex gap-1 w-100">
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
            <template v-if="props.availableMembers.filter(member => !item.members.some(m => m.user.id === member.user_id)).length">
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
              :color="member.isTiming ? 'success' : (member.timeEntries.length ? '#FACA15' : '#EEEDF0')"
              :class="member.isTiming ? 'glow' : (member.timeEntries.length ? 'worked' : '')"
              @click="editTimer(member, item.id)"
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
      </div>
    </VCardText>
  </VCard>
</template>

<style scoped>
.kanban-card {
  border-radius: 6px;
  border: 1px solid #d1d0d0;
  height: auto;
  max-height: none;
  background: linear-gradient(135deg, #f9fafb, #ffffff);
  transition: height 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.kanban-card.hover-scale:hover {
  transform: scale(1.02);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.card-handler {
  cursor: grab;

  &:active {
    cursor: grabbing;
  }
}

.kanban-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
}

.card-header {
  position: relative;
  display: flex;
  align-items: center;
  cursor: pointer;
  justify-content: space-between;
  padding: 12px 16px 12px 1px;
  background-color: #f3f4f6;
  border-bottom: 1px solid #e5e7eb;
}

.card-title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.card-body {
  padding: 8px;
}

.glow {
  box-shadow: 0 0 8px 2px rgba(72, 187, 120, 0.8);
  animation: pulse 1.5s infinite ease-in-out;
  border: 1px solid rgba(53, 186, 109, 0.8);
}

.worked {
  border: 1px solid rgba(53, 186, 109, 0.8);
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 8px 2px rgba(72, 187, 120, 0.8);
  }
  50% {
    box-shadow: 0 0 16px 4px rgba(72, 187, 120, 0.6);
  }
  100% {
    box-shadow: 0 0 8px 2px rgba(72, 187, 120, 0.8);
  }
}

.github-style-list {
  background-color: #f6f8fa;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.github-list-item {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  border-bottom: 1px solid #eaecef;
  transition: background-color 0.2s ease;
}

.github-list-item:last-child {
  border-bottom: none;
}

.github-list-item:hover {
  background-color: #f0f0f0;
}

.github-list-item .v-avatar {
  margin-right: 12px;
}

.empty-state {
  padding: 16px;
}

.empty-state .text-center {
  margin: 0 auto;
}

.comments-chip {
  background-color: #faca15;
  color: #1f2937;
  font-size: 12px;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border-radius: 16px;
  transition: background-color 0.2s ease, box-shadow 0.2s ease;
}

.comments-chip:hover {
  background-color: #f5b509;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.play-stop-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  border-radius: 16px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.1s ease;
}

.play-btn {
  background-color: #38a169;
  color: #fff;
}

.play-btn:hover {
  background-color: #2f855a;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  transform: scale(1.05);
}

.stop-btn {
  background-color: #e53e3e;
  color: #fff;
}

.stop-btn:hover {
  background-color: #c53030;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  transform: scale(1.05);
}

.play-stop-icon {
  font-size: 14px;
  line-height: 1;
}
</style>
