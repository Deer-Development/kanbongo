<template>
  <VMenu
    v-model="timerMenu"
    offset-y
    :close-on-content-click="false"
    close-on-back
    eager
    persistent
  >
    <template #activator="{ props }">
      <div
        class="custom-badge"
        v-bind="props"
      >
        <VIcon
          class="timer-btn"
          :class="{
            'timer-btn-active': isTiming,
          }"
          @click.stop="toggleTimer"
          :icon="getTimerIcon"
          :color="getTimerColor"
          size="14"
        />
        <span>{{ isTiming ? currentTimer : (task.tracked_time?.trackedTimeDisplay || '0h 0m 0s') }}</span>
      </div>
    </template>
    <template #default>
      <div class="timer-options">
        <div class="header-sticky">
          <div class="d-flex justify-end">
            <div
              class="custom-badge"
              style="color: #dc2626"
              @click="timerMenu = false"
            >
              <VIcon size="14">tabler-circle-x</VIcon>
              <span>Close</span>
            </div>
          </div>
        </div>

        <div class="content-wrapper">
          <VProgressCircular
            v-if="loading"
            indeterminate
            color="secondary"
          />

          <template v-else>
            <VExpansionPanels
              variant="accordion"
            >
              <VExpansionPanel
                v-for="entry in allEntries"
                :key="entry.details.user.id"
                class="user-panel"
                elevation="0"
              >
                <VExpansionPanelTitle class="d-flex gap-1">
                  <div class="custom-badge border-0 gap-1">
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
                    <div class="d-flex flex-column">
                      <span class="font-weight-bold">{{ entry.details.user.full_name }}</span>
                      <div
                        class="custom-badge mt-1"
                        style="width: 80px"
                        :class="entry.details.hasActiveTimer ? 'has-active-timer' : (
                          entry.time_entries?.length ? 'has-time-entries' : 'no-time-entries'
                        )"
                      >
                        <VIcon
                          left
                          size="16"
                        >
                          tabler-hourglass
                        </VIcon>
                        <span>{{ entry.details.totalWorkedTime }}</span>
                      </div>
                    </div>
                  </div>
                </VExpansionPanelTitle>

                <VExpansionPanelText>
                  <VList
                    slim
                    variant="flat"
                  >
                    <VListItemAction class="d-flex flex-column">
                      <div class="d-flex justify-end gap-2 mb-2">
                        <div
                          class="custom-badge-edit"
                          @click="updateTimeEntries"
                        >
                          <VIcon size="18">tabler-circle-check</VIcon>
                          <span>Save Changes</span>
                        </div>
                        <div
                          v-if="!entry.details.hasActiveTimer"
                          class="custom-badge-add"
                          @click="addTimeEntry(entry.details.user.id)"
                        >
                          <VIcon size="18">tabler-plus</VIcon>
                          <span>Add Time Entry</span>
                        </div>
                      </div>

                      <div class="timers-list">
                        <VListItem
                          v-for="(timeEntry, index) in entry.time_entries"
                          :key="timeEntry.id"
                        >
                          <div class="d-flex flex-column w-100">
                            <div
                              v-if="timeEntry.is_paid || timeEntry.added_manually"
                              class="d-flex gap-2 flex-wrap"
                            >
                              <div
                                v-if="timeEntry.is_paid"
                                class="custom-badge-paid"
                              >
                                <VIcon size="16">tabler-dollar</VIcon>
                                <span>Paid</span>
                              </div>
                              <div
                                v-if="timeEntry.added_manually"
                                class="custom-badge-manually"
                              >
                                <VIcon size="16">tabler-edit</VIcon>
                                <span>Manual Entry</span>
                              </div>
                            </div>

                            <div 
                              class="date-time-pickers"
                              :class="timeEntry.deleted ? 'border-color-error' : ''"
                            >
                              <BadgeDateTimePicker
                                v-model="timeEntry.start"
                                density="comfortable"
                                variant="outlined"
                                placeholder="Start Time"
                                label="Start Time"
                                extended-badge
                                :disabled="timeEntry.deleted || timeEntry.is_paid"
                                :config="datetimeConfig"
                                @update:model-value="updateDuration(timeEntry)"
                              />
                              <BadgeDateTimePicker
                                v-model="timeEntry.end"
                                density="comfortable"
                                variant="outlined"
                                placeholder="End Time"
                                label="End Time"
                                extended-badge
                                :disabled="timeEntry.deleted || timeEntry.is_paid"
                                :config="datetimeConfig"
                                @update:model-value="updateDuration(timeEntry)"
                              />
                            </div>

                            <div class="time-entry-controls">
                              <div
                                v-if="timeEntry.duration"
                                class="custom-badge has-time-entries"
                              >
                                <VIcon size="16">tabler-clock</VIcon>
                                <span>{{ timeEntry.duration }}</span>
                              </div>
                              <div
                                class="custom-badge-delete"
                                @click="deleteEntry(entry, timeEntry)"
                              >
                                <VIcon size="16">tabler-circle-x</VIcon>
                                <span>Delete</span>
                              </div>
                            </div>
                          </div>
                        </VListItem>
                      </div>
                    </VListItemAction>
                  </VList>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>
          </template>
        </div>
      </div>
    </template>
  </VMenu>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits, computed, onUnmounted } from 'vue'
import { differenceInSeconds, parseISO, format, parse } from "date-fns"
import { useTimerStore } from '@/stores/useTimerStore'

const props = defineProps({
  task: { type: Object, required: true },
  auth: { type: Object, required: true },
  member: { type: Object, required: false },
  hasActiveTimer: { type: Boolean, required: false, default: false },
  activeUsers: {
    type: Array,
    required: false,
    default: () => [],
  },
})

const emit = defineEmits(['toggleTimer', 'refreshKanbanData'])

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
const authDetails = ref(props.auth)
const timerStore = useTimerStore()
const localActiveUsers = ref([...props.activeUsers])
const localMember = ref(props.member)

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

watch(() => props.activeUsers, () => {
  localActiveUsers.value = [...props.activeUsers]
}, { deep: true, immediate: true })

watch(() => props.member, () => {
  localMember.value = props.member
}, { deep: true, immediate: true })

watch(
  () => localActiveUsers.value,
  (newValue, oldValue) => {
    if(newValue.length === 0) {
      isTiming.value = false
      return
    }

    const isCurrentUserTimingThisTask = newValue.some(
      user => user.user.id === props.auth.id && user.time_entry?.task_id === props.task.id,
    )

    isTiming.value = isCurrentUserTimingThisTask

    if (isTiming.value) {
      const trackedTime = newValue.find(
        user => user.user.id === props.auth.id && user.time_entry?.task_id === props.task.id,
      )
      
      if (trackedTime) {
        timerStore.startTimer(props.auth.id, trackedTime.time_entry)
      }
    }
  },
  { deep: true, immediate: true },
)

const currentTimer = computed(() => {
  if (isTiming.value) {
    return timerStore.getTimer(props.auth.id)
  }
  return props.task.tracked_time?.trackedTimeDisplay || '0h 0m 0s'
})

watch(
  () => props.auth,
  (newValue, oldValue) => {
    if(newValue) {
      authDetails.value = newValue
    }
  },
  { deep: true, immediate: true },
)

const fetchTimeEntries = async () => {
  loading.value = true

  const res = await $api(`/task/time-entries/${props.task.id}`)

  if (res) {
    allEntries.value = res.data
  }

  loading.value = false
}

watch(
  () => timerMenu.value,
  async newValue => {
    if (newValue === true) {
      await fetchTimeEntries()
    }else{
      allEntries.value = []
      loading.value = false
    }
  },
  { deep: true },
)

const toggleTimer = () => {
  if (isTimerDisabled.value) return
  timerStore.clearTimer(props.auth.id)
  
  isTiming.value = false
  activeTimer.value = null
  emit('toggleTimer', localMember.value)
}

const updateTimeEntries = async () => {
  try {
    const entriesArray = Object.values(allEntries.value)

    const timeEntries = entriesArray.flatMap(entry =>
      entry.time_entries.map(timeEntry => ({
        id: timeEntry.id || null,
        user_id: entry.details.user.id,
        task_id: props.task.id,
        start: parseISO(timeEntry.start),
        end: timeEntry.end ? parseISO(timeEntry.end) : null,
        deleted: timeEntry.deleted || false,
        is_paid: timeEntry.is_paid || false,
        added_manually: timeEntry.added_manually || false,
      })),
    )

    await $api(`/task/update-timer/${props.task.id}`, {
      method: 'POST',
      body: JSON.stringify(timeEntries),
    })

    emit('refreshKanbanData')

    timerMenu.value = false
  } catch (error) {
    console.error("Error updating time entries:", error)
  }
}

const addTimeEntry = userId => {
  const entriesArray = Object.values(allEntries.value)
  const userEntry = entriesArray.find(entry => entry.details.user.id === userId)

  if (userEntry) {
    userEntry.time_entries.push({
      start: new Date().toISOString(),
      end: new Date().toISOString(),
      duration: null,
      is_paid: false,
      user_id: userId,
      added_manually: true,
    })
  } else {
    console.error("User not found in time entries")
  }
}

const updateDuration = timeEntry => {
  if (timeEntry.start && timeEntry.end) {
    const startDate = parseISO(timeEntry.start)
    const endDate = parseISO(timeEntry.end)

    const seconds = differenceInSeconds(endDate, startDate)

    if (seconds < 0) {
      timeEntry.duration = 'Invalid Time'
      return
    }

    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const remainingSeconds = seconds % 60

    timeEntry.duration = `${hours}h ${minutes}m ${remainingSeconds}s`
  } else {
    timeEntry.duration = null
  }
}

const deleteEntry = (entry, timeEntry) => {
  if (timeEntry.added_manually && !timeEntry.id) {
    const index = entry.time_entries.indexOf(timeEntry)
    if (index !== -1) {
      entry.time_entries.splice(index, 1)
    }
  } else {
    timeEntry.deleted = !timeEntry.deleted
  }
}

const isTimerDisabled = computed(() => {
  return (props.hasActiveTimer && !isTiming.value) || 
         !localMember.value || 
         (props.auth.has_weekly_limit && 
          props.auth.weekly_limit_seconds <= props.auth.weekly_tracked.total_seconds)
})

const getTimerIcon = computed(() => { 
  if (isTiming.value) return 'tabler-player-pause-filled'
  if (!localMember.value) return 'tabler-hourglass-empty'
  return 'tabler-player-play'
})

const getTimerColor = computed(() => {
  if (isTimerDisabled.value) return '#9CA3AF'
  if (!localMember.value) return '#9CA3AF'
  if (isTiming.value) return '#059669'
  return '#38a169'
})

onUnmounted(() => {
  timerStore.clearTimer(props.auth.id)
  isTiming.value = false
})
</script>

<style lang="scss" scoped>
.timer-options {
  min-width: 320px;
  max-width: 580px;
  padding: 0;
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 200px);

  @media (max-width: 600px) {
    min-width: calc(100vw - 2rem);
    margin: 0 1rem;
    max-height: 80vh;
  }

  .header-sticky {
    position: sticky;
    top: 0;
    background-color: rgba(255, 255, 255, 0.95);
    padding: 0.75rem;
    z-index: 10;
    border-bottom: 1px solid #e2e8f0;
    backdrop-filter: blur(8px);
  }

  .content-wrapper {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem;
    
    &::-webkit-scrollbar {
      width: 6px;
    }
    
    &::-webkit-scrollbar-track {
      background: #f8fafc;
    }
    
    &::-webkit-scrollbar-thumb {
      background: #e2e8f0;
      border-radius: 3px;
      
      &:hover {
        background: #cbd5e1;
      }
    }
  }

  .v-expansion-panels {
    background: transparent;
    border-radius: 8px;
    overflow: visible;
    
    .v-expansion-panel {
      overflow: visible;
      
      &::before {
        box-shadow: none;
      }
    }
  }

  .user-panel {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    margin-bottom: 0.75rem;
    border-radius: 8px !important;
    transition: all 0.2s ease;
    overflow: visible;
    
    &:hover {
      border-color: #94a3b8;
      box-shadow: 0 2px 4px -2px rgb(0 0 0 / 0.1);
    }

    &:last-child {
      margin-bottom: 0;
    }

    .v-expansion-panel-title {
      min-height: 52px;
      padding: 0.75rem;
      color: #1e293b;
      font-weight: 500;

      &:hover {
        background-color: #f8fafc;
      }

      .custom-badge {
        min-width: 110px;
        
        &.mt-1 {
          width: auto !important;
          min-width: 120px;
        }
      }
    }

    .v-expansion-panel-text.v-expansion-panel-text__wrapper {
      padding: 1rem !important;
      background-color: #ffffff;

      .v-list {
        width: 100%;
        padding: 0;
        min-width: 0;

        .v-list-item {
          width: 100%;
          padding: 1rem;
          margin-bottom: 0.75rem;
          
          &:last-child {
            margin-bottom: 0;
          }

          .date-time-pickers {
            display: flex;
            gap: 1rem;
            width: 100%;
            margin: 0.75rem 0;
            
            @media (max-width: 600px) {
              flex-direction: column;
              gap: 0.75rem;
            }

            .app-picker-field {
              flex: 1;
              width: 50%;
              min-width: 0;
              
              @media (max-width: 600px) {
                width: 100%;
              }
            }
          }

          .time-entry-controls {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
            width: 100%;
            justify-content: flex-end;

            @media (max-width: 600px) {
              justify-content: flex-start;
            }

            .custom-badge {
              flex: 0 0 auto;
            }
          }
        }
      }
    }
  }

  .timers-list {
    width: 100%;
    .v-list-item {
      margin-bottom: 0.75rem;
      background-color: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 1.25rem;
      transition: all 0.2s ease;

      &:hover {
        border-color: #94a3b8;
        box-shadow: 0 2px 4px -2px rgb(0 0 0 / 0.1);
      }

      &:last-child {
        margin-bottom: 0;
      }

      @media (max-width: 600px) {
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
      }

      .time-entry-controls {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
        
        @media (min-width: 601px) {
          justify-content: flex-end;
        }

        .custom-badge {
          min-width: auto;
          height: 1.75rem;
          padding: 0 0.5rem;
          font-size: 0.75rem;
          font-weight: 500;
          border-radius: 4px;
          
          .v-icon {
            font-size: 14px;
          }

          &.has-time-entries {
            background-color: #f0fdfa;
            border-color: #ccfbf1;
            color: #0d9488;
          }

          &.custom-badge-delete {
            background-color: #fef2f2;
            border-color: #fee2e2;
            color: #dc2626;
            
            &:hover {
              background-color: #fee2e2;
              border-color: #fecaca;
            }
          }
        }
      }

      .date-time-pickers {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        width: 100%;
        
        .app-picker-field {
          flex: 1;
          width: 50%;
          min-width: 0;
          
          @media (max-width: 600px) {
            width: 100%;
          }
        }
        
        @media (max-width: 600px) {
          flex-direction: column;
          gap: 1rem;
        }

        .v-field {
          flex: 1;
          min-width: 200px;
          
          @media (max-width: 600px) {
            min-width: 100%;
          }

          .v-field__input {
            min-height: 44px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            
            @media (max-width: 600px) {
              padding-right: 2.5rem;
            }
          }

          @media (max-width: 600px) {
            .v-field__prepend-inner {
              display: none;
            }
          }

          input {
            width: 100%;
            color: #1e293b;
          }

          .v-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
          }
        }
      }
    }
  }

  .date-time-pickers {
    display: flex;
    gap: 1rem;
    
    @media (max-width: 600px) {
      flex-direction: column;
    }

    .v-field {
      background-color: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      min-height: 42px;
      transition: all 0.2s ease;
      
      &:hover {
        border-color: #94a3b8;
      }
      
      &:focus-within {
        border-color: #3b82f6;
        box-shadow: 0 0 0 1px #3b82f6;
      }

      input {
        font-size: 0.875rem;
        color: #1e293b;
      }

      .v-field__input {
        padding: 0.5rem 0.75rem;
      }
    }
  }
}

.custom-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.1rem;
  padding: 0.35rem 0.5rem;
  border-radius: 0.375rem;
  background-color: #f3f4f6;
  border: 1px solid #e5e7eb;
  color: #374151;
  font-size: 0.64rem;
  line-height: 1;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
  font-weight: 500;
  
  &:hover {
    background-color: #e5e7eb;
    border-color: #d1d5db;
  }

  &.has-active-timer {
    background-color: #ecfdf5;
    border-color: #d1fae5;
    color: #065f46;
  }

  &.has-time-entries {
    background-color: #f0f9ff;
    border-color: #e0f2fe;
    color: #0369a1;
  }

  &.no-time-entries {
    background-color: #f9fafb;
    border-color: #f3f4f6;
    color: #6b7280;
  }

  .timer-btn {
    font-size: 0.875rem;
    height: 0.875rem;
    width: 0.875rem;
    transition: all 0.2s ease;
    
    &:not(:disabled) {
      &:hover {
        transform: scale(1.1);
        opacity: 0.9;
      }
    }
    
    &-active {
      animation: pulse 2s infinite;
    }
  }

  span {
    font-size: 0.68rem;
  }

  &-edit, &-add, &-delete {
    font-size: 0.8125rem;
    padding: 0.35rem 0.5rem;
    
    .v-icon {
      font-size: 0.875rem !important;
      height: 0.875rem !important;
      width: 0.875rem !important;
    }
  }

  &-edit {
    background-color: #f0f9ff;
    border-color: #e0f2fe;
    color: #0369a1;

    &:hover {
      background-color: #e0f7fe;
      border-color: #bae6fd;
    }
  }

  &-add {
    background-color: #ecfdf5;
    border-color: #d1fae5;
    color: #065f46;

    &:hover {
      background-color: #d1fae5;
      border-color: #a7f3d0;
    }
  }

  &-delete {
    background-color: #fef2f2;
    border-color: #fee2e2;
    color: #991b1b;

    &:hover {
      background-color: #fee2e2;
      border-color: #fecaca;
    }
  }

  @media (max-width: 600px) {
    padding: 0.3rem 0.45rem;
    font-size: 0.75rem;
    
    .timer-btn {
      font-size: 0.8125rem;
      height: 0.8125rem;
      width: 0.8125rem;
    }
    
    span {
      font-size: 0.75rem;
    }
  }
}

@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.05);
    opacity: 0.8;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.border-color-error {
  border: 1px solid #dc2626 !important;
  background-color: #fef2f2;
}

.glow {
  animation: glow 2s infinite;
}

@keyframes glow {
  0% {
    box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.4);
  }
  50% {
    box-shadow: 0 0 0 4px rgba(5, 150, 105, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(5, 150, 105, 0);
  }
}

.worked {
  box-shadow: 0 0 0 2px rgba(8, 145, 178, 0.2);
}

@media (max-width: 600px) {
  .timer-options {
    .content-wrapper {
      padding: 0.5rem;
    }

    .v-expansion-panel-text__wrapper {
      padding: 0.75rem;
    }

    .time-entry-controls {
      justify-content: flex-start;
      
      .custom-badge {
        flex: 0 1 auto;
      }
    }

    .user-panel {
      .v-expansion-panel-text__wrapper {
        padding: 0.75rem;

        .v-list-item {
          padding: 1rem !important;
        }
      }
    }
  }
}

:deep(.badge-date-time-picker) {
  width: 50%;
  
  @media (max-width: 600px) {
    width: 100%;
  }

  .v-field {
    min-height: 44px;
    
    .v-field__input {
      padding: 0.5rem 0.75rem;
    }

    input {
      width: 100%;
      min-width: 0;
    }
  }
}
</style>


