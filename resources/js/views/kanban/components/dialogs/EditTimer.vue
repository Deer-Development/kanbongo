<template>
  <VDialog
    persistent
    max-width="80%"
    :model-value="props.isDialogVisible"
    class="github-dialog"
  >
    <DialogCloseBtn
      class="close-btn"
      @click="closeDialog"
    />
    <VCard
      v-if="props.isDialogVisible"
      class="p-4 github-card"
    >
      <VCardTitle class="d-flex justify-content-between align-items-center bg-warning rounded">
        <span class="fs-6 fw-bold text-white text-wrap text-h6">{{ props.taskName }}</span>
      </VCardTitle>

      <div class="d-flex align-items-center text-center gap-1 mt-2">
        <div class="mt-2">
          <VAvatar
            v-tooltip="localMemberDetails.user.full_name"
            size="28"
            class="cursor-pointer"
            :color="localMemberDetails.isTiming ? 'success' : (localMemberDetails.timeEntries.length ? '#FACA15' : '#EEEDF0')"
            :class="localMemberDetails.isTiming ? 'glow' : (localMemberDetails.timeEntries.length ? 'worked' : '')"
            @click="editTimer(member, item.id, item.name)"
          >
            <template v-if="localMemberDetails.user.avatar">
              <img
                :src="localMemberDetails.user.avatar"
                alt="Avatar"
              >
            </template>
            <template v-else>
              <span>{{ localMemberDetails.user.avatar_or_initials }}</span>
            </template>
          </VAvatar>
        </div>
        <div>
          <span class="text-muted">Tracked Time:</span>
          <VChip
            label
            class="chip-time-new"
            color="success"
            size="small"
            outlined
          >
            {{ localMemberDetails.totalTrackedTimeDisplay }}
          </VChip>
        </div>
        <!--        <div -->
        <!--          v-if="getActiveTimeEntry(localMemberDetails)" -->
        <!--          class="active-timer-badge" -->
        <!--        > -->
        <!--          <div class="badge-title mt-2"> -->
        <!--            Active Timer -->
        <!--          </div> -->
        <!--          <div class="badge-time"> -->
        <!--            {{ activeTimerDisplay }} -->
        <!--          </div> -->
        <!--        </div> -->
      </div>

      <VCardText>
        <div
          v-for="(entry, index) in localMemberDetails.timeEntries"
          :key="index"
          class="entry-card-github"
          :class="[{ 'entry-card-deleted': entry.deleted }]"
        >
          <div
            v-if="entry.added_manually"
            class="chip-manually-added"
          >
            <VChip
              label
              color="warning"
              size="small"
              outlined
            >
              Manually Added
            </VChip>
          </div>
          <VRow class="gx-3 align-items-center">
            <VCol
              cols="12"
              md="5"
            >
              <label class="form-label">Start Time</label>
              <AppDateTimePicker
                v-model="entry.startFormatted"
                placeholder="Select start time"
                :config="datetimeConfig"
                :readonly="entry.deleted"
                class="input-github"
              />
            </VCol>
            <VCol
              cols="12"
              md="5"
            >
              <label class="form-label">End Time</label>
              <AppDateTimePicker
                v-model="entry.endFormatted"
                placeholder="Select end time"
                :config="datetimeConfig"
                :readonly="entry.deleted"
                class="input-github"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
              class="text-end"
            >
              <VBtn
                prepend-icon="tabler-trash"
                color="error"
                variant="outlined"
                class="btn-delete-github"
                @click="deleteEntry(index)"
              >
                Delete
              </VBtn>
            </VCol>
          </VRow>

          <VRow class="mt-3">
            <VCol cols="8">
              <div class="d-flex flex-column flex-md-row align-items-center gap-2">
                <span class="text-muted">Tracked Time:</span>
                <VChip
                  label
                  class="chip-time-new"
                  color="success"
                  size="small"
                  outlined
                >
                  {{ entry.end ? entry.trackedTimeDisplay : activeTimerDisplay }}
                </VChip>
                <VChip
                  v-if="entry.oldTrackedTimeDisplay && entry.trackedTimeDisplay !== entry.oldTrackedTimeDisplay"
                  label
                  class="chip-time-old"
                  size="small"
                  outlined
                >
                  <s>{{ entry.oldTrackedTimeDisplay }}</s>
                </VChip>
              </div>
            </VCol>
            <VCol
              cols="4"
              class="text-end"
            >
              <VChip
                v-if="entry.oldTrackedTimeDisplay && entry.trackedTimeDisplay !== entry.oldTrackedTimeDisplay"
                label
                class="chip-github"
                size="small"
                outlined
              >
                Modified
              </VChip>
            </VCol>
          </VRow>
        </div>
        <VBtn
          class="mb-3"
          color="primary"
          variant="outlined"
          prepend-icon="tabler-plus"
          @click="addNewEntry"
        >
          Add New Entry
        </VBtn>
      </VCardText>

      <VCardActions class="d-flex justify-content-end">
        <VBtn
          color="primary"
          variant="tonal"
          @click="submitForm"
        >
          Save Changes
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { defineProps, nextTick, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { parse, format, differenceInSeconds, parseISO } from 'date-fns'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  taskId: {
    type: Number,
    required: false,
  },
  taskName: {
    type: String,
    required: false,
  },
  memberDetails: {
    type: Object,
    default: () => ({ user: {}, timeEntries: [] }),
  },
})

const emit = defineEmits(['update:isDialogVisible', 'formSubmitted'])

const toast = useToast()
const refForm = ref()
const localMemberDetails = ref(props.memberDetails)
const errors = ref({})
let activeTimerInterval

const datetimeConfig = {
  enableTime: true,
  enableSeconds: true,
  dateFormat: 'Y-m-d\\TH:i:s',
  altInput: true,
  altFormat: 'Y-m-d h:i:s K',
}

const calculateTrackedTime = (start, end) => {
  const parseFormat = date =>
    parse(date, "yyyy-MM-dd'T'HH:mm:ss", new Date())

  const startDate = parseFormat(start)
  const endDate = parseFormat(end)

  const seconds = differenceInSeconds(endDate, startDate)
  if (seconds < 0) return 'Invalid Time'

  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const remainingSeconds = seconds % 60

  return `${hours}h ${minutes}m ${remainingSeconds}s`
}

const watchTimeEntries = () => {
  watch(
    () => localMemberDetails.value.timeEntries,
    timeEntries => {
      timeEntries.forEach(entry => {
        if (!entry._isWatching) {
          watch(
            () => [entry.startFormatted, entry.endFormatted],
            ([newStart, newEnd]) => {
              entry.trackedTimeDisplay = calculateTrackedTime(newStart, newEnd)
            },
            { immediate: true, deep: true },
          )
          entry._isWatching = true // Flag to prevent duplicate watchers
        }
      })
    },
    { immediate: true, deep: true },
  )
}

const convertToDatetimeLocal = dateString => {
  if (!dateString) return ''

  return format(parse(dateString, 'MM/dd/yyyy hh:mm:ss a', new Date()), "yyyy-MM-dd'T'HH:mm:ss")
}

const convertToOriginalFormat = datetimeLocal => {
  if (!datetimeLocal) return ''

  return format(parse(datetimeLocal, "yyyy-MM-dd'T'HH:mm:ss", new Date()), 'MM/dd/yyyy hh:mm:ss a')
}

const submitForm = () => {
  sendData()
}

const sendData = async () => {
  try {
    const timeEntries = localMemberDetails.value.timeEntries.map(entry => ({
      ...entry,
      start: convertToOriginalFormat(entry.startFormatted),
      end: convertToOriginalFormat(entry.endFormatted),
      deleted: entry.deleted,
      user_id: localMemberDetails.value.user_id,
    }))

    const res = await $api(`/task/update-timer/${props.taskId}`, {
      method: 'POST',
      body: timeEntries,
    })

    if (res) {
      toast.success('Time entries updated successfully')
    }

    emit('update:isDialogVisible', false)
    emit('formSubmitted', true)

    await nextTick(() => {
      refForm.value?.resetValidation()
      errors.value = {}
    })
  } catch (err) {
    console.error(err)
  }
}

const closeDialog = () => {
  emit('update:isDialogVisible', false)
}

const updateActiveTimer = () => {
  const activeEntry = getActiveTimeEntry(localMemberDetails.value)
  if (!activeEntry) {
    activeTimerDisplay.value = "N/A"

    return
  }

  const rawStartTime = activeEntry.start

  console.log("Raw Start Time:", rawStartTime)

  const localStartTime = parse(rawStartTime, "MM/dd/yyyy hh:mm:ss a", new Date())

  const startTimeUTC = new Date(Date.UTC(
    localStartTime.getFullYear(),
    localStartTime.getMonth(),
    localStartTime.getDate(),
    localStartTime.getHours(),
    localStartTime.getMinutes(),
    localStartTime.getSeconds(),
  ))

  const nowUTC = new Date()

  const secondsElapsed = Math.floor((nowUTC - startTimeUTC) / 1000)

  if (secondsElapsed < 0) {
    activeTimerDisplay.value = "Invalid Time"

    return
  }

  const hours = Math.floor(secondsElapsed / 3600)
  const minutes = Math.floor((secondsElapsed % 3600) / 60)
  const seconds = secondsElapsed % 60

  activeTimerDisplay.value = `${hours}h ${minutes}m ${seconds}s`
}

watch(
  () => props.memberDetails,
  newDetails => {
    if (newDetails) {

      localMemberDetails.value = {
        ...newDetails,
        timeEntries: newDetails.timeEntries.map(entry => ({
          ...entry,

          startFormatted: convertToDatetimeLocal(entry.start),
          endFormatted: convertToDatetimeLocal(entry.end),
          added_manually: entry.added_manually || false,
          deleted: false,
          trackedTimeDisplay: calculateTrackedTime(
            convertToDatetimeLocal(entry.start),
            convertToDatetimeLocal(entry.end),
          ),
          oldTrackedTimeDisplay: calculateTrackedTime(
            convertToDatetimeLocal(entry.start),
            convertToDatetimeLocal(entry.end),
          ),
        })),
      }

      activeTimerInterval = setInterval(updateActiveTimer, 1000)

      watchTimeEntries()
    }
  },
  { immediate: true },
)

const addNewEntry = () => {
  const lastEntry = localMemberDetails.value.timeEntries.at(-1)
  if (lastEntry && (!lastEntry.startFormatted || !lastEntry.endFormatted)) {
    toast.warning('Please complete the last entry before adding a new one.')

    return
  }

  localMemberDetails.value.timeEntries.push({
    id: null,
    startFormatted: '',
    endFormatted: '',
    taskId: props.taskId,
    trackedTimeDisplay: 'N/A',
    added_manually: true,
    deleted: false,
    _isWatching: false,
  })
}

const deleteEntry = index => {
  const entry = localMemberDetails.value.timeEntries[index]
  if (entry) {
    entry.deleted = true
  }
}

const getActiveTimeEntry = member => {
  if (!member || !member.timeEntries) return null

  return member.timeEntries.find(entry => !entry.end)
}

const activeTimerDisplay = ref("")

onBeforeUnmount(() => {
  if (activeTimerInterval) clearInterval(activeTimerInterval)
})

watch(
  () => getActiveTimeEntry(localMemberDetails.value),
  () => {
    updateActiveTimer()
  },
)
</script>

<style lang="scss" scoped>
.github-dialog {
  .v-card {
    background-color: #f6f8fa;
    border: 1px solid #d0d7de;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }
}

.github-card {
  padding: 1.5rem;
}

.entry-card-github {
  position: relative;
  background-color: #ffffff;
  border: 1px solid #d0d7de;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1rem;
  transition: all 0.2s;

  &:hover {
    background-color: #f6f8fa;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  }

  .chip-manually-added {
    position: absolute;
    top: -0.5rem;
    left: -0.5rem;
    z-index: 1;

    .v-chip {
      background-color: #ffeeba;
      border-color: #ffc107;
      color: #856404;
      font-weight: 600;
      font-size: 0.75rem;
    }
  }
}

.entry-card-deleted {
  background-color: #ffe6e6;
  border-color: #e63946;
  opacity: 0.8;
  transition: all 0.3s;

  &:hover {
    opacity: 1;
  }
}

.input-github {
  .v-input {
    background-color: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    padding: 0.5rem;
    transition: border-color 0.2s;

    &:focus {
      border-color: #0969da;
    }
  }
}

.btn-github {
  background-color: #0969da;
  color: #ffffff;
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  transition: background-color 0.2s;

  &:hover {
    background-color: #055dba;
  }
}

.chip-github {
  background-color: #ddf4ff;
  color: #0969da;
  border: 1px solid #0969da;
  border-radius: 12px;
  padding: 0.2rem 0.5rem;
  font-size: 0.75rem;
}

.chip-time-new {
  background-color: #ddf4ff;
  color: #0969da;
  border: 1px solid #0969da;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
}

.chip-time-old {
  background-color: #f8f9fa;
  color: #6c757d;
  border: 1px solid #d0d7de;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 400;
  padding: 0.2rem 0.5rem;

  s {
    color: #6c757d;
  }
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
.active-timer-badge {
  display: inline-block;
  background-color: #28a745;
  color: #ffffff;
  border-radius: 5px;
  padding: 0.2rem 0.5rem;
  text-align: center;
  font-family: Arial, sans-serif;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);

  .badge-title {
    font-size: 0.5rem;
    font-weight: bold;
    margin-bottom: 0.1rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .badge-time {
    font-size: 0.7rem;
    font-weight: bold;
    font-family: "Courier New", monospace;
  }
}
</style>


