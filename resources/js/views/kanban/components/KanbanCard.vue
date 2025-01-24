<script setup>
import { ref, onUnmounted, onMounted, defineEmits } from 'vue'
import MemberCard from "@/views/kanban/components/MemberCard.vue"

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
    return 'success'
  if (priority == Priority.HIGH)
    return 'error'
  if (priority == Priority.NORMAL)
    return 'info'
  if (priority == Priority.LOW)
    return 'warning'
}

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
    if (!member.isTiming) {
      emit('refreshKanbanData')
    }
  })
}

const editTimer = (member, id) => {
  emit('editTimer', member, id)
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
      @click="$emit('editKanbanItem', item.id)"
    >
      <h3
        v-tooltip="item.name"
        class="card-title truncate"
      >
        {{ item.name }}
      </h3>
    </div>

    <VCardText class="card-body">
      <div
        v-if="item.priority"
        class="kanban-row"
      >
        <VChip
          :color="getPriorityColor(item.priority) || 'info'"
          size="small"
          variant="flat"
          prepend-icon="tabler-flag"
        >
          {{ Priority.getName(item.priority) || 'Set Priority' }}
        </VChip>
        <VChip
          v-if="item.comments.length"
          size="x-small"
          variant="text"
          color="warning"
        >
          <VIcon>tabler-message-2</VIcon>
        </VChip>
      </div>
      <VExpansionPanels
        v-if="item.members.length"
        v-model="membersExpanded"
        variant="accordion"
        class="expansion-panels-width-border"
      >
        <VExpansionPanel>
          <VExpansionPanelTitle>
            <div class="v-avatar-group">
              <template
                v-for="(member, index) in item.members"
                :key="member.id"
              >
                <VBadge
                  v-if="member.timeEntries.length"
                  location="bottom start"
                  color="success"
                  dot
                >
                  <template #badge>
                    <VIcon icon="tabler-hourglass-low" />
                  </template>

                  <VAvatar
                    v-if="item.members.length > 0 && item.members.length !== 4 && index < 3"
                    v-tooltip="member.user.full_name"
                    size="32"
                    :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
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

                  <VAvatar
                    v-if="item.members.length === 4"
                    v-tooltip="member.user.full_name"
                    size="32"
                    :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
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
                </VBadge>

                <div v-else>
                  <VAvatar
                    v-if="item.members.length > 0 && item.members.length !== 4 && index < 3"
                    v-tooltip="member.user.full_name"
                    size="32"
                    :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
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

                  <VAvatar
                    v-if="item.members.length === 4"
                    v-tooltip="member.user.full_name"
                    size="32"
                    :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
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
                </div>
              </template>

              <VAvatar
                v-if="item.members.length > 4"
                :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
              >
                <span>
                  +{{ item.members.length - 3 }}
                </span>
              </VAvatar>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div
              v-for="(member, index) in item.members"
              :key="member.id"
              class="member-section compact"
            >
              <MemberCard
                :member="member"
                :is-super-admin="props.isSuperAdmin"
                :has-active-timer="props.hasActiveTimer"
                :is-owner="props.isOwner"
                :is-member="props.isMember"
                :auth-id="props.authId"
                @toggle-timer="toggleTimer(member)"
                @edit-timer="editTimer(member, item.id)"
              />
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>
    </VCardText>
  </VCard>
</template>

<style scoped>
.kanban-card {
  border-radius: 12px;
  background: linear-gradient(135deg, #f9fafb, #ffffff);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.kanban-card.hover-scale:hover {
  transform: scale(1.02);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.kanban-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
  border-bottom: 1px solid #e5e7eb;
}

.card-header {
  position: relative;
  display: flex;
  align-items: center;
  cursor: pointer;
  justify-content: space-between;
  padding: 12px 16px;
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
  padding: 10px;
}

.member-section.compact {
  margin-top: 8px;
}
</style>
