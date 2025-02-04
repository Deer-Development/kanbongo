<script setup>
import { defineProps, ref, watch } from "vue"
import { format } from "date-fns"

const props = defineProps({
  member: { type: Object, required: true },
  dateRange: { type: String, required: false, default: null },
  boardId: { type: Number, required: true, default: 0 },
  isOwner: { type: Boolean, required: false, default: false },
  isSuperAdmin: { type: Boolean, required: false, default: false },
})

const emit = defineEmits(["update:selectedPayment", "update:selectedEntries"])

const paymentDetails = ref(null)
const loading = ref(false)
const selectedTimeEntries = ref([])

const fetchMemberPaymentDetails = async () => {
  if (!props.member) return

  loading.value = true
  try {
    const res = await $api(`/container/${props.boardId}/member-payment-details/${props.member.user.id}`, {
      method: "GET",
      params: { date_range: props.dateRange },
    })

    selectedTimeEntries.value = []
    paymentDetails.value = res.data
  } catch (error) {
    console.error("Error fetching member payment details:", error)
  } finally {
    loading.value = false
  }
}

const formatDate = dateString => {
  return format(new Date(dateString), "MMM d, yyyy h:mm:ss a")
}

const parseDurationToSeconds = duration => {
  const [hours, minutes, seconds] = duration.split(":").map(Number)
  
  return hours * 3600 + minutes * 60 + seconds
}

const totalSelectedPayment = () => {
  if (!paymentDetails.value) return 0

  const paymentArray = Object.values(paymentDetails.value)

  return paymentArray.reduce((total, taskDetail) => {
    return (
      total +
      taskDetail.timeEntries
        .filter(entry => selectedTimeEntries.value.includes(entry.id))
        .reduce((sum, entry) => sum + (parseDurationToSeconds(entry.duration) / 3600) * props.member.billable_rate, 0)
    )
  }, 0)
}

const toggleTaskEntries = (taskDetail) => {
  const entryIds = taskDetail.timeEntries
    .filter(entry => !entry.is_paid)
    .map(entry => entry.id)

  const allSelected = entryIds.every(id => selectedTimeEntries.value.includes(id))

  if (allSelected) {
    selectedTimeEntries.value = selectedTimeEntries.value.filter(id => !entryIds.includes(id))
  } else {
    selectedTimeEntries.value = [...new Set([...selectedTimeEntries.value, ...entryIds])]
  }
}

watch(() => selectedTimeEntries, (newValue) => {
  if (newValue) {
    emit("update:selectedPayment", totalSelectedPayment());
    emit("update:selectedEntries", selectedTimeEntries.value);
  }
}, { deep: true, immediate: true });

watch(() => props.dateRange, (newValue, oldValue) => {
  if (newValue !== oldValue) fetchMemberPaymentDetails()
}, { deep: true, immediate: true })

watch(() => props.member, (newValue, oldValue) => {
  console.log("Member changed:", newValue, oldValue)
  if (newValue && newValue !== oldValue) fetchMemberPaymentDetails()
}, { deep: true, immediate: true })
</script>

<template>
  <div class="member-payment-details">
    <div v-if="paymentDetails">
      <div
        v-for="(taskDetail, key) in paymentDetails"
        :key="key"
        class="task-card"
      >
        <VRow>
          <VCol cols="9">
            <h4 class="task-title">
              {{ taskDetail.task.name }}
            </h4>
          </VCol>
          <VCol
            cols="3"
            class="text-right"
          >
            <span class="custom-badge custom-badge-paid">{{ taskDetail.trackedTimeDisplay }}</span>
          </VCol>
        </VRow>

        <div class="time-entries">
          <table class="time-entries-table">
            <thead>
              <tr>
                <th
                  v-if="props.isOwner || props.isSuperAdmin"
                  class="center"
                >
                  <VIcon color="success" class="ml-2" @click="toggleTaskEntries(taskDetail)">tabler-checkbox</VIcon>
                </th>
                <th>Start</th>
                <th>End</th>
                <th>Duration</th>
                <th>Paid</th>
                <th>Amount</th>
                <th>Rate</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="entry in taskDetail.timeEntries"
                :key="entry.id"
              >
                <td
                  v-if="props.isOwner || props.isSuperAdmin"
                  class="center"
                >
                  <VCheckbox
                    v-model="selectedTimeEntries"
                    :value="entry.id"
                    :disabled="entry.is_paid"
                  />
                </td>
                <td>{{ formatDate(entry.start) }}</td>
                <td>{{ formatDate(entry.end) }}</td>
                <td>{{ entry.duration }}</td>
                <td>
                  <span :class="entry.is_paid ? 'custom-badge custom-badge-paid' : 'custom-badge custom-badge-manually'">
                    {{ entry.is_paid ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td>{{ entry.amount_paid ? `$${entry.amount_paid}` : '--' }}</td>
                <td>{{ entry.paid_rate ? `$${entry.paid_rate}` : '--' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div
      v-else
      class="no-data"
    >
      No payment details available.
    </div>
  </div>
</template>

<style lang="scss" scoped>
.member-payment-details {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.title {
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.task-card {
  background: #f8f9fa;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.task-title {
  font-size: 0.9rem;
  font-weight: 600;
}

.time-entries-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 0.5rem;
  background: #fff;
}

.time-entries-table th, .time-entries-table td {
  padding: 0.6rem;
  border-bottom: 1px solid #d0d7de;
  text-align: left;
}

.time-entries-table th {
  background: #f6f8fa;
  font-weight: bold;
}

.center {
  text-align: center;
}

.loading, .no-data {
  text-align: center;
  color: #6c757d;
  font-size: 0.95rem;
  margin-top: 1.5rem;
}
</style>


