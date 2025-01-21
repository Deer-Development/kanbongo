<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="800"
    persistent
  >
    <DialogCloseBtn @click="closeDialog" />

    <VCard title="Edit Time Entries">
      <VCardText>
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="submitForm"
        >
          <VRow>
            <VCol cols="12">
              <VAlert
                v-if="errors.value"
                type="error"
                outlined
                dense
                transition="slide-x-transition"
                class="mb-3"
              >
                {{ errors.value }}
              </VAlert>
            </VCol>

            <VCol
              v-if="displayLogs"
              cols="12"
            >
              <div class="d-flex align-items-center mb-4">
                <VIcon
                  icon="tabler-arrow-back"
                  size="24"
                  class="me-2 cursor-pointer text-primary"
                  @click="displayLogs = false; logsToDisplay = []"
                />
                <h3 class="h5 mb-0">
                  Log Details
                </h3>
              </div>

              <div class="card shadow-sm">
                <div class="card-body p-0">
                  <div
                    v-for="(log, index) in logsToDisplay"
                    :key="log.id || index"
                    class="d-flex flex-wrap border-bottom gap-4 py-3 px-4"
                  >
                    <div class="col-12 col-md-3 mb-2 mb-md-0">
                      <strong class="d-block text-muted">Field</strong>
                      <span class="text-dark">{{ log.field }}</span>
                    </div>
                    <div class="col-12 col-md-3 mb-2 mb-md-0">
                      <strong class="d-block text-muted">Old Value</strong>
                      <span class="text-dark">{{ log.old_entry || 'N/A' }}</span>
                    </div>
                    <div class="col-12 col-md-3 mb-2 mb-md-0">
                      <strong class="d-block text-muted">New Value</strong>
                      <span class="text-dark">{{ log.entry }}</span>
                    </div>
                    <div class="col-12 col-md-3">
                      <strong class="d-block text-muted">Updated By</strong>
                      <span class="text-dark">{{ log.created_by }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </VCol>

            <VCol
              v-if="localMemberDetails && !displayLogs"
              cols="12"
            >
              <div class="grid grid-cols-3 items-center bg-gray-200 py-2 px-4 rounded-t-md font-semibold">
                <span>Start Time</span>
                <span>End Time</span>
                <span>Tracked Time</span>
              </div>

              <div
                v-for="(entry, index) in localMemberDetails.timeEntries"
                :key="index"
                class="grid grid-cols-3 gap-4 items-center border-b py-2 px-4"
              >
                <div>
                  <AppDateTimePicker
                    v-model="entry.startFormatted"
                    label="Start Time"
                    placeholder="Select start time"
                    :config="{ enableTime: true, enableSeconds: true, dateFormat: 'Y-m-d\\TH:i:s', altInput: true, altFormat: 'Y-m-d h:i:s K' }"
                  />
                </div>

                <div>
                  <AppDateTimePicker
                    v-model="entry.endFormatted"
                    label="End Time"
                    placeholder="Select end time"
                    :config="{ enableTime: true, enableSeconds: true, dateFormat: 'Y-m-d\\TH:i:s', altInput: true, altFormat: 'Y-m-d h:i:s K' }"
                  />
                </div>

                <div class="d-flex gap-4 justify-space-between">
                  <span>{{ entry.trackedTimeDisplay }}</span>
                  <div v-if="entry.logs.length">
                    <VIcon
                      v-tooltip="'View Logs'"
                      color="primary"
                      size="18"
                      icon="tabler-logs"
                      class="me-2 cursor-pointer"
                      @click="displayLogsForEntry(entry)"
                    />
                  </div>
                </div>
              </div>
            </VCol>

            <VCol
              cols="12"
              class="d-flex justify-end flex-wrap gap-3 mt-5"
            >
              <VBtn
                type="submit"
                color="primary"
                class="me-3"
              >
                Save Changes
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
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

const headers = ref([
  { title: 'Start Time', key: 'start', sortable: false },
  { title: 'End Time', key: 'end', sortable: false },
  { title: 'Tracked Time', key: 'trackedTimeDisplay', sortable: false },
])

const calculateTrackedTime = (start, end) => {
  console.log(start, end)

  if (!start || !end) return 'N/A'

  const parseFormat = date =>
    parse(date, "yyyy-MM-dd'T'HH:mm:ss", new Date())

  const startDate = parseFormat(start)
  const endDate = parseFormat(end)

  console.log(startDate, endDate)

  // const startDate = parse(start, "yyyy-MM-dd'T'HH:mm:ss", new Date())
  // const endDate = parse(end, "yyyy-MM-dd'T'HH:mm:ss", new Date())
  const seconds = differenceInSeconds(endDate, startDate)
  if (seconds < 0) return 'Invalid Time'

  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const remainingSeconds = seconds % 60

  return `${hours}h ${minutes}m ${remainingSeconds}s`
}

const watchTimeEntries = () => {
  if (!localMemberDetails.value?.timeEntries) return

  localMemberDetails.value.timeEntries.forEach(entry => {
    watch(
      () => [entry.startFormatted, entry.endFormatted],
      ([newStart, newEnd]) => {
        entry.trackedTimeDisplay = calculateTrackedTime(newStart, newEnd)
      },
      { immediate: true, deep: true },
    )
  })
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
    console.log(newDetails)
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
        })),
      }
      watchTimeEntries()
    }
  },
  { immediate: true },
)
</script>

<style scoped>
.grid {
  display: grid;
}
.grid-cols-3 {
  grid-template-columns: repeat(3, 1fr);
}
.gap-4 {
  gap: 1rem;
}
</style>


