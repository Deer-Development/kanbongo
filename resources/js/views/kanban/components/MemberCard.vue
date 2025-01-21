<script setup>
const props = defineProps({
  member: { type: Object, required: true },
  isSuperAdmin: { type: Boolean, required: false, default: false },
  hasActiveTimer: { type: Boolean, required: false, default: false },
  isOwner: { type: Boolean, required: false, default: false },
  isMember: { type: Boolean, required: false, default: false },
  authId: { type: Number, required: false },
})

const emit = defineEmits(['toggleTimer', 'editTimer'])

const toggleTimer = member => {
  emit('toggleTimer', member)
}

const editTimer = member => {
  emit('editTimer', member)
}

const getActiveTimeEntry = member => {
  return member.timeEntries?.find(entry => !entry.end)
}

const getInactiveTimeEntries = member => {
  return member.timeEntries?.filter(entry => entry.end) || []
}

const hasInactiveTimeEntries = member => {
  return getInactiveTimeEntries(member).length > 0
}
</script>

<template>
  <div class="member-card compact">
    <div class="member-header px-2 py-1">
      <div class="d-flex align-center">
        <VAvatar
          size="36"
          class="member-avatar"
        >
          <template v-if="member.user.avatar">
            <VImg
              :src="member.user.avatar"
              alt="Avatar"
            />
          </template>
          <template v-else>
            <span class="avatar-initials">{{ member.user.avatar_or_initials }}</span>
          </template>
        </VAvatar>
        <div class="member-details">
          <div class="member-name">
            {{ member.user.full_name }}
          </div>
        </div>
      </div>

      <div class="d-flex flex-column gap-2">
        <VBtn
          v-if="(!isSuperAdmin && (isMember && authId === member.user.id)) || isSuperAdmin && authId === member.user.id"
          size="x-small"
          color="success"
          :readonly="!member.isTiming && hasActiveTimer"
          @click.stop="toggleTimer(member)"
        >
          <VIcon
            :icon="member.isTiming ? 'tabler-pause' : 'tabler-play'"
            size="14"
          />
        </VBtn>
        <VBtn
          v-if="hasInactiveTimeEntries(member) && (isSuperAdmin || (isMember && authId === member.user.id))"
          size="x-small"
          color="error"
          @click.stop="editTimer(member)"
        >
          <VIcon
            icon="tabler-edit"
            size="14"
          />
        </VBtn>
      </div>
    </div>

    <div
      v-if="getActiveTimeEntry(member)"
      class="active-entry"
    >
      <span>Active Timer:</span>
      <span class="tracked-time elegant">{{ getActiveTimeEntry(member).trackedTimeDisplay }}</span>
    </div>

    <VExpansionPanels
      v-if="hasInactiveTimeEntries(member)"
      variant="accordion"
      class="expansion-panels-width-border mt-2 border-0 rounded-0"
    >
      <VExpansionPanel>
        <VExpansionPanelTitle>
          <span class="text-success">{{ member.totalTrackedTimeDisplay }}</span>
        </VExpansionPanelTitle>
        <VExpansionPanelText>
          <div
            v-for="entry in getInactiveTimeEntries(member)"
            :key="entry.start"
            class="time-entry px-2 py-2"
          >
            <VChip
              v-tooltip="entry.start + ' - ' + (entry.end ? entry.end : 'Ongoing')"
              color="primary"
              class="details-chip"
            >
              <span class="tracked-time-text">{{ entry.trackedTimeDisplay }}</span>
            </VChip>
          </div>
        </VExpansionPanelText>
      </VExpansionPanel>
    </VExpansionPanels>
  </div>
</template>

<style scoped>
.member-card.compact {
  background: linear-gradient(135deg, #ffffff, #f3f4f6);
  border: 1px solid #e5e7eb;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.06);
}

.member-card.compact:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
}

.member-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.member-avatar {
  border: 1px solid #e5e7eb;
  border-radius: 50%;
  overflow: hidden;
}

.avatar-initials {
  font-size: 12px;
  font-weight: bold;
  color: #4b5563;
}

.member-details {
  margin-left: 10px;
}

.member-name {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.active-entry {
  background-color: #ecfdf5;
  color: #065f46;
  font-weight: 500;
  padding: 6px 8px;
  border-radius: 6px;
  font-size: 13px;
}

.details-chip {
  display: flex;
  justify-content: center;
  width: 100%;
  text-align: center;
  font-size: 14px;
  font-weight: 600;
  background-color: #e0e7ff;
  color: #1e3a8a;
  box-shadow: inset 0 0 3px rgba(0, 0, 0, 0.1);
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.details-chip:hover {
  background-color: #c7d2fe;
  transform: scale(1.02);
}

.tracked-time.elegant {
  text-align: center;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  color: #4f46e5;
}

.tracked-time-text {
  font-weight: bold;
  color: #4f46e5;
  margin-bottom: 4px;
}
</style>
