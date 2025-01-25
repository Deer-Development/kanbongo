<template>
  <VDialog
    persistent
    max-width="80%"
    :model-value="props.isDialogVisible"
    class="github-dialog"
  >
    <DialogCloseBtn class="close-btn" @click="closeDialog" />
    <VCard class="p-4 github-card">
      <VCardTitle class="d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold text-dark">Manage Time Entries</span>
      </VCardTitle>

      <VCardText>
        <VBtn
          class="mb-3"
          color="primary"
          variant="outlined"
          prepend-icon="tabler-plus"
          @click="addNewEntry"
        >
          Add New Entry
        </VBtn>

        <div
          v-for="(entry, index) in localMemberDetails.timeEntries"
          :key="index"
          class="entry-card-github"
        >
          <VRow class="gx-3 align-items-center">
            <VCol cols="12" md="5">
              <label class="form-label">Start Time</label>
              <AppDateTimePicker
                v-model="entry.startFormatted"
                placeholder="Select start time"
                :config="datetimeConfig"
                class="input-github"
              />
            </VCol>
            <VCol cols="12" md="5">
              <label class="form-label">End Time</label>
              <AppDateTimePicker
                v-model="entry.endFormatted"
                placeholder="Select end time"
                :config="datetimeConfig"
                class="input-github"
              />
            </VCol>
            <VCol cols="12" md="2" class="text-end">
              <VBtn
                prepend-icon="tabler-trash"
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
                  {{ entry.trackedTimeDisplay }}
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
            <VCol cols="4" class="text-end">
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
import { parse, format, differenceInSeconds } from 'date-fns'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  taskId: {
    type: Number,
    required: false,
  },
  memberDetails: {
    type: Object,
    required: false,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'formSubmitted'])

const toast = useToast()
const isFormValid = ref(false)
const refForm = ref()
const localMemberDetails = ref(props.memberDetails)
const errors = ref({})
const logsToDisplay = ref([])
const displayLogs = ref(false)

const datetimeConfig = {
  enableTime: true,
  enableSeconds: true,
  dateFormat: 'Y-m-d\\TH:i:s',
  altInput: true,
  altFormat: 'Y-m-d h:i:s K',
}

const calculateTrackedTime = (start, end) => {
  if (!start || !end) return 'N/A'

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
    (timeEntries) => {
      timeEntries.forEach((entry) => {
        if (!entry._isWatching) {
          watch(
            () => [entry.startFormatted, entry.endFormatted],
            ([newStart, newEnd]) => {
              entry.trackedTimeDisplay = calculateTrackedTime(newStart, newEnd);
            },
            { immediate: true, deep: true }
          );
          entry._isWatching = true; // Flag to prevent duplicate watchers
        }
      });
    },
    { immediate: true, deep: true }
  );
};

const convertToDatetimeLocal = dateString => {
  if (!dateString) return ''

  return format(parse(dateString, 'MM/dd/yyyy hh:mm:ss a', new Date()), "yyyy-MM-dd'T'HH:mm:ss")
}

const convertToOriginalFormat = datetimeLocal => {
  if (!datetimeLocal) return ''

  return format(parse(datetimeLocal, "yyyy-MM-dd'T'HH:mm:ss", new Date()), 'MM/dd/yyyy hh:mm:ss a')
}

const submitForm = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) sendData()
  })
}

const sendData = async () => {
  try {
    const timeEntries = localMemberDetails.value.timeEntries.map(entry => ({
      ...entry,
      start: convertToOriginalFormat(entry.startFormatted),
      end: convertToOriginalFormat(entry.endFormatted),
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

const displayLogsForEntry = entry => {
  logsToDisplay.value = entry.logs

  displayLogs.value = true
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
      watchTimeEntries()
    }
  },
  { immediate: true },
)

const addNewEntry = () => {
  localMemberDetails.value.timeEntries.push({
    id: null,
    startFormatted: '',
    endFormatted: '',
    trackedTimeDisplay: 'N/A',
    _isWatching: false,
  })
}

const deleteEntry = index => {
  localMemberDetails.value.timeEntries.splice(index, 1)
}
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

.btn-delete-github {
  background-color: #cf222e;
  color: #ffffff;
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  transition: background-color 0.2s;

  &:hover {
    background-color: #a40f1b;
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
</style>


