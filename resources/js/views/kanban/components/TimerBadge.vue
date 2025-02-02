<template>
  <VMenu v-model="timerMenu" offset-y :close-on-content-click="false" eager persistent
  >
    <template #activator="{ props }">
      <div class="custom-badge" v-bind="props">
        <VIcon left size="16" color="warning">tabler-hourglass</VIcon>
        <span>{{ isTiming ? activeTimer : task.tracked_time.trackedTimeDisplay }}</span>
        <button
          v-if="member"
          class="timer-btn"
          :class="{
            'timer-btn-active': isTiming,
            'timer-btn-disabled': hasActiveTimer && !isTiming
          }"
          :disabled="hasActiveTimer && !isTiming"
          @click.stop="toggleTimer"
        >
          <VIcon :icon="isTiming ? 'tabler-pause' : 'tabler-play'" size="14" />
        </button>
      </div>
    </template>
    <div class="timer-options ">
      <VProgressCircular v-if="loading" indeterminate color="primary" />

      <template v-else>
        <div class="d-flex justify-end">
          <div
            class="custom-badge mb-2"
            @click="timerMenu = false"
          >
            <VIcon left>tabler-circle-x</VIcon>
            <span>Close</span>
          </div>
        </div>

        <VExpansionPanels variant="accordion"
                          class="expansion-panels-width-border">
          <VExpansionPanel
            v-for="entry in allEntries"
            :key="entry.details.user.id"
            class="user-panel"
          >
            <VExpansionPanelTitle click.stop>
              <div class="custom-badge border-0 gap-1" >
                <VAvatar
                  size="26"
                  :color="entry.details.hasActiveTimer ? '#38a169' :
                        entry.time_entries?.length ? '#42bc7b' : '#EEEDF0'"
                  :class="entry.details.hasActiveTimer ? 'glow' :
                        entry.time_entries?.length ? 'worked' : ''"
                >
                  <VImg
                    v-if="entry.details.user.avatar"
                    :src="entry.details.user.avatar"
                  />
                  <template v-else>
                    <span class="text-xs font-weight-medium">{{ entry.details.user.avatar_or_initials }}</span>
                  </template>
                </VAvatar>
                <div>
                  <span class="font-weight-bold">{{ entry.details.user.full_name }}</span>
                </div>
              </div>
              <template #actions>
                <div class="custom-badge"
                     :class="entry.details.hasActiveTimer ? 'has-active-timer' : (
                    entry.time_entries?.length ? 'has-time-entries' : ''
                    )"
                >
                  <VIcon left size="16">tabler-hourglass</VIcon>
                  <span>{{ entry.details.totalWorkedTime }}</span>
                </div>
              </template>
            </VExpansionPanelTitle>

            <VExpansionPanelText>
              <VList slim variant="flat">
                <VListItemAction end>
                  <div class="custom-badge-edit"
                     @click="updateTimeEntries"
                  >
                    <VIcon>tabler-circle-check</VIcon>
                    <span>Save</span>
                  </div>
                  <div class="custom-badge-add"
                       v-if="!entry.details.hasActiveTimer"
                       @click="addTimeEntry(entry.details.user.id)"
                  >
                    <VIcon>tabler-plus</VIcon>
                    <span>Add Time</span>
                  </div>
                </VListItemAction>

                <div class="timers-list">
                  <VListItem
                    v-for="(timeEntry, index) in entry.time_entries"
                    :key="timeEntry.id"
                  >
                    <div class="d-flex gap-2" v-if="timeEntry.is_paid || timeEntry.added_manually">
                      <div class="custom-badge-paid" v-if="timeEntry.is_paid">
                        <VIcon left size="16">tabler-dollar</VIcon>
                        <span>Paid</span>
                      </div>
                      <div class="custom-badge-manually" v-if="timeEntry.added_manually">
                        <VIcon left size="16">tabler-edit</VIcon>
                        <span>Manually Added</span>
                      </div>
                    </div>
                    <div class="d-flex gap-2 align-content-center pa-1 rounded"
                      :class="timeEntry.deleted ? 'border-color-error' : ''"
                    >
                      <BadgeDateTimePicker
                        v-model="timeEntry.start"
                        density="compact"
                        variant="underlined"
                        placeholder="Start Time"
                        label="Start Time"
                        :extended-badge="true"
                        :disabled="timeEntry.deleted || timeEntry.is_paid"
                        :config="datetimeConfig"
                        @update:model-value="updateDuration(timeEntry)"
                      />
                      <BadgeDateTimePicker
                        v-model="timeEntry.end"
                        density="compact"
                        variant="underlined"
                        placeholder="End Time"
                        label="End Time"
                        :extended-badge="true"
                        :disabled="timeEntry.deleted || timeEntry.is_paid"
                        :config="datetimeConfig"
                        @update:model-value="updateDuration(timeEntry)"
                      />

                      <div class="d-flex flex-column gap-2" >
                        <div class="custom-badge has-time-entries" v-if="timeEntry.duration">
                          <VIcon left size="16">tabler-clock</VIcon>
                          <span>{{ timeEntry.duration }}</span>
                        </div>
                        <div class="custom-badge-delete"
                          @click="deleteEntry(entry, timeEntry)"
                        >
                          <VIcon left size="16">tabler-circle-x</VIcon>
                          <span>Delete</span>
                        </div>
                      </div>
                    </div>
                    <div v-if="index !== entry.time_entries.length - 1">
                      <VDivider class="mt-2"/>
                    </div>
                  </VListItem>
                </div>
              </VList>
            </VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>
      </template>
    </div>
  </VMenu>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import { differenceInSeconds, parseISO, format, parse } from "date-fns"

const props = defineProps({
  task: { type: Object, required: true },
  authId: { type: Number, required: true },
  member: { type: Object, required: false },
  hasActiveTimer: { type: Boolean, required: false, default: false },
  activeUsers: {
    type: Array,
    required: false,
    default: () => [],
  },
});

const emit = defineEmits(['toggleTimer', 'refreshKanbanData']);

const datetimeConfig = {
  enableTime: true,
  enableSeconds: true,
  dateFormat: 'Y-m-d\\TH:i:S',
  altInput: true,
  altFormat: 'M, j Y h:i K',
}

const timerMenu = ref(null)
const isTiming = ref(false)
const loading = ref(false)
const activeTimer = ref(null)
const allEntries = ref([])

const calculateTrackedTime = start => {
  try {
    const startDate = parseISO(start)
    const now = new Date()
    const seconds = differenceInSeconds(now, startDate)

    if (seconds < 0) return 'Invalid Time'

    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const remainingSeconds = seconds % 60

    return `${hours}h ${minutes}m ${remainingSeconds}s`
  } catch (error) {

    return 'Error calculating time'
  }
}

watch(
  () => props.activeUsers,
  (newValue, oldValue) => {
    if(newValue.length === 0) {
      isTiming.value = false;
      activeTimer.value = null;
      return;
    }

    isTiming.value = newValue.some(
      (user) => user.user.id === props.authId && user.time_entry?.task_id === props.task.id
    );

    if (isTiming.value && !activeTimer.value) {
      const trackedTime = newValue.find(
        (user) => user.user.id === props.authId && user.time_entry?.task_id === props.task.id
      );

      activeTimer.value = calculateTrackedTime(trackedTime.time_entry.start);

      const intervalId = setInterval(() => {
        activeTimer.value = calculateTrackedTime(trackedTime.time_entry.start);
      }, 1000);

      trackedTime.user.intervalId = intervalId;
    }
  },
  { deep: true, immediate: true }
);

const fetchTimeEntries = async () => {
  loading.value = true;

  const res = await $api(`/task/time-entries/${props.task.id}`)

  if (res) {
    allEntries.value = res.data;
  }

  loading.value = false;
};

watch(
  () => timerMenu.value,
  async (newValue) => {
    if (newValue === true) {
      await fetchTimeEntries();
    }else{
      allEntries.value = [];
      loading.value = false;
    }
  },
  { deep: true }
);

const toggleTimer = () => {
  emit('toggleTimer', props.member);
};

const updateTimeEntries = async () => {
  try {
    const entriesArray = Object.values(allEntries.value);
    const timeEntries = entriesArray.flatMap(entry =>
      entry.time_entries.map(timeEntry => ({
        id: timeEntry.id || null,
        user_id: entry.details.user.id,
        task_id: props.task.id,
        start: parseISO(timeEntry.start),
        end: timeEntry.end ? parseISO(timeEntry.end) : null,
        deleted: timeEntry.deleted || false,
        is_paid: timeEntry.is_paid || false,
        added_manually: timeEntry.added_manually || false
      }))
    );

    await $api(`/task/update-timer/${props.task.id}`, {
      method: 'POST',
      body: JSON.stringify(timeEntries),
    });

    emit('refreshKanbanData');

    timerMenu.value = false;
  } catch (error) {
    console.error("Error updating time entries:", error);
  }
};

const addTimeEntry = userId => {
  const entriesArray = Object.values(allEntries.value);
  const userEntry = entriesArray.find(entry => entry.details.user.id === userId);

  if (userEntry) {
    userEntry.time_entries.push({
      start: new Date().toISOString(),
      end: new Date().toISOString(),
      duration: null,
      is_paid: false,
      user_id: userId,
      added_manually: true,
    });
  } else {
    console.error("User not found in time entries");
  }
};

const updateDuration = timeEntry => {
  console.log(timeEntry);

  if (timeEntry.start && timeEntry.end) {
    const startDate = parseISO(timeEntry.start);
    const endDate = parseISO(timeEntry.end);


    const seconds = differenceInSeconds(endDate, startDate);

    if (seconds < 0) {
      timeEntry.duration = 'Invalid Time';
      return;
    }

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;

    timeEntry.duration = `${hours}h ${minutes}m ${remainingSeconds}s`;
  } else {
    timeEntry.duration = null;
  }
};

const deleteEntry = (entry, timeEntry) => {
  if (timeEntry.added_manually && !timeEntry.id) {
    const index = entry.time_entries.indexOf(timeEntry);
    if (index !== -1) {
      entry.time_entries.splice(index, 1);
    }
  } else {
    timeEntry.deleted = !timeEntry.deleted;
  }
};

onUnmounted(() => {
  if (props.member?.intervalId) {
    clearInterval(props.member.intervalId);
    props.member.intervalId = null;
  }

  isTiming.value = false;
  activeTimer.value = null;
});
</script>

<style lang="scss" scoped>
.timer-options {
  min-width: 320px;
  max-width: 500px;
  padding: 6px 4px;
  background-color: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;

  .custom-badge .v-field .v-field__input {
    max-width: 6rem !important;
  }
}

.timers-list {
  max-height: 15rem;
  overflow-y: auto;
}

.border-color-error {
  border: 1px solid #f56565;
}

.user-panel {
  background-color: #f8f9fa;
  border-radius: 8px;
}
</style>

