<script setup>
import { defineProps, ref, watch, computed } from "vue"
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
const expandedTask = ref(null) // Track expanded task for logs

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

const formatDate = dateString => format(new Date(dateString), "MMM d, yyyy h:mm:ss a")

const parseDurationToSeconds = duration => {
  const [hours, minutes, seconds] = duration.split(":").map(Number)
  
  return hours * 3600 + minutes * 60 + seconds
}

const totalSelectedPayment = computed(() => {
  if (!paymentDetails.value) return 0

  return Object.values(paymentDetails.value).reduce((total, taskDetail) => {
    return (
      total +
      taskDetail.timeEntries
        .filter(entry => selectedTimeEntries.value.includes(entry.id))
        .reduce((sum, entry) => sum + (parseDurationToSeconds(entry.duration) / 3600) * props.member.billable_rate, 0)
    )
  }, 0)
})

const isAllSelected = (entries) => {
  if (!entries || entries.length === 0) return false
  return entries.every(entry => 
    entry.is_paid || selectedTimeEntries.value.includes(entry.id)
  )
}

const getLogColor = (action) => {
  switch (action) {
    case 'update':
      return 'primary'
    case 'delete':
      return 'error'
    default:
      return 'grey'
  }
}

const selectionState = computed(() => {
  if (!paymentDetails.value) return {}
  
  return Object.values(paymentDetails.value).reduce((acc, taskDetail) => {
    acc[taskDetail.task.id] = taskDetail.timeEntries.every(entry => 
      selectedTimeEntries.value.includes(entry.id)
    )
    return acc
  }, {})
})

const toggleTaskEntries = (taskDetail) => {
  const entryIds = taskDetail.timeEntries
    .filter(entry => !entry.is_paid)
    .map(entry => entry.id)
    
  const allSelected = entryIds.every(id => selectedTimeEntries.value.includes(id))

  if (allSelected) {
    selectedTimeEntries.value = selectedTimeEntries.value.filter(id => 
      !entryIds.includes(id)
    )
  } else {
    selectedTimeEntries.value = [
      ...new Set([...selectedTimeEntries.value, ...entryIds])
    ]
  }
}

const generateLogMessage = log => {
  const formatIfNeeded = date => {
    // Check if it's an ISO string (if it contains 'T' and 'Z', it's likely raw)
    return typeof date === "string" && date.includes("T") ? formatDate(date) : date
  }

  if (log.action === "update") {
    if (log.old_data?.is_paid === false && log.new_data?.is_paid === true) {
      return `
        <div class="log-message log-paid">
          <strong>💰 Payment Recorded!</strong><br>
          ${log.user.full_name} just marked this time entry as paid.<br>
          <strong>Paid Rate:</strong> ${log.new_data.paid_rate ? `$${log.new_data.paid_rate}` : "N/A"}<br>
          <strong>Amount Paid:</strong> ${log.new_data.amount_paid ? `$${log.new_data.amount_paid.toFixed(2)}` : "N/A"}<br>
          A job well done! 🎉
        </div>
      `
    }

    // Time Tracker Stopped
    if (log.old_data?.end === null && log.new_data?.end) {
      return `
        <div class="log-message log-stopped">
          <strong>⏱️ Time Tracker Stopped!</strong><br>
          ${log.user.full_name} stopped the time tracker at <strong>${formatDate(log.new_data.end)}</strong>.<br>
          <strong>Session Duration:</strong> From <strong>${formatIfNeeded(log.old_data.start)}</strong> to <strong>${formatDate(log.new_data.end)}</strong>.
        </div>
      `
    }

    // General update (excluding 'end' updates from stopped tracking)
    let changes = Object.entries(log.new_data)
      .filter(([key]) => key !== "updated_at" && key !== "end") // Ignore 'end' because we already handled it above
      .map(([key, value]) => {
        let oldValue = formatIfNeeded(log.old_data[key]) || "N/A"
        let newValue = formatIfNeeded(value)

        return `<strong>${key}</strong>: <span class="old-value">${oldValue}</span> → <span class="new-value">${newValue}</span>`
      })
      .join("<br>")

    return `
      <div class="log-message log-manual">
        <strong>🔄 Time Entries Modified Manually</strong><br>
        ${log.user.full_name} updated this time entry.<br>
        ${changes ? changes : "Some details have been updated."}
      </div>
    `
  }

  if (log.action === "delete") {
    return `
      <div class="log-message log-deleted">
        <strong>❌ Entry Deleted!</strong><br>
        ${log.user.full_name} decided to remove this time entry.<br>
        <strong>Start:</strong> ${formatIfNeeded(log.old_data.start)}<br>
        <strong>End:</strong> ${log.old_data.end ? formatDate(log.old_data.end) : "N/A"}<br>
        It's gone forever! 🚀
      </div>
    `
  }

  return `<div class="log-message log-default"><strong>📝 Action:</strong> ${log.action}</div>`
}


watch(() => selectedTimeEntries, () => {
  emit("update:selectedPayment", totalSelectedPayment.value)
  emit("update:selectedEntries", selectedTimeEntries.value)
}, { deep: true, immediate: true })

watch(() => props.dateRange, fetchMemberPaymentDetails, { deep: true, immediate: true })
watch(() => props.member, fetchMemberPaymentDetails, { deep: true, immediate: true })
</script>

<template>
  <div class="member-payment-details">
    <div v-if="paymentDetails" class="tasks-container">
      <div
        v-for="(taskDetail, key) in paymentDetails"
        :key="key"
        class="github-card"
      >
        <div class="card-header">
          <div class="header-content">
            <h4 class="task-title">
              {{ taskDetail.task?.name }}
            </h4>
            <div class="time-badge">
              <VIcon size="16" color="success">tabler-clock-play</VIcon>
              <span>{{ taskDetail.trackedTimeDisplay }}</span>
            </div>
          </div>

          <VBtn
            v-if="taskDetail.task && taskDetail.entries_logs.length"
            variant="text"
            size="small"
            class="logs-btn"
            prepend-icon="tabler-history"
            @click="expandedTask = expandedTask === taskDetail.task.id ? null : taskDetail.task.id"
          >
            Task Logs
          </VBtn>
        </div>

        <!-- Task Logs -->
        <VExpandTransition>
          <div v-if="expandedTask === taskDetail.task.id" class="logs-section">
            <VTimeline
              density="comfortable"
              line-thickness="2"
              class="github-timeline"
            >
              <VTimelineItem
                v-for="log in taskDetail.entries_logs"
                :key="log.id"
                :dot-color="getLogColor(log.action)"
                size="small"
              >
                <div class="log-entry">
                  <div class="log-header">
                    <span class="action-badge" :class="log.action">
                      {{ log.action.toUpperCase() }}
                    </span>
                    <span class="log-date">{{ formatDate(log.created_at) }}</span>
                  </div>
                  <div
                    class="log-content"
                    v-html="generateLogMessage(log)"
                  />
                </div>
              </VTimelineItem>
            </VTimeline>
          </div>
        </VExpandTransition>

        <!-- Time Entries Table -->
        <div class="table-container">
          <table class="github-table">
            <thead>
              <tr>
                <th v-if="props.isOwner || props.isSuperAdmin" class="checkbox-column">
                  <VCheckbox
                    :model-value="isAllSelected(taskDetail.timeEntries)"
                    :indeterminate="
                      taskDetail.timeEntries.some(entry => selectedTimeEntries.includes(entry.id)) &&
                      !isAllSelected(taskDetail.timeEntries)
                    "
                    @change="toggleTaskEntries(taskDetail)"
                  />
                </th>
                <th>Start</th>
                <th>End</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Rate</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="entry in taskDetail.timeEntries"
                :key="entry.id"
                :class="{ 'is-paid': entry.is_paid }"
              >
                <td v-if="props.isOwner || props.isSuperAdmin" class="checkbox-column">
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
                  <span class="status-badge" :class="entry.is_paid ? 'paid' : 'unpaid'">
                    {{ entry.is_paid ? 'Paid' : 'Unpaid' }}
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
  </div>
</template>

<style lang="scss" scoped>
.member-payment-details {
  .tasks-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .github-card {
    background: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    overflow: hidden;
    transition: all 0.2s ease;

    &:hover {
      border-color: #0969da;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .card-header {
      padding: 1rem;
      background: #f6f8fa;
      border-bottom: 1px solid #d0d7de;
      display: flex;
      justify-content: space-between;
      align-items: center;

      .header-content {
        display: flex;
        align-items: center;
        gap: 1rem;
      }

      .task-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #24292f;
        margin: 0;
      }

      .time-badge {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.75rem;
        background: #dafbe1;
        color: #1a7f37;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;
      }

      .logs-btn {
        color: #57606a;
        font-size: 0.75rem;
        
        &:hover {
          color: #0969da;
        }
      }
    }

    .logs-section {
      padding: 1rem;
      background: #f6f8fa;
      border-bottom: 1px solid #d0d7de;

      .github-timeline {
        .log-entry {
          background: #ffffff;
          border: 1px solid #d0d7de;
          border-radius: 6px;
          padding: 0.75rem;

          .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;

            .action-badge {
              font-size: 0.75rem;
              font-weight: 600;
              padding: 0.25rem 0.5rem;
              border-radius: 2rem;

              &.update { background: #ddf4ff; color: #0969da; }
              &.delete { background: #ffebe9; color: #cf222e; }
            }

            .log-date {
              font-size: 0.75rem;
              color: #57606a;
            }
          }

          .log-content {
            font-size: 0.8125rem;
            color: #24292f;
          }
        }
      }
    }

    .table-container {
      overflow-x: auto;
    }

    .github-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.8125rem;

      th, td {
        padding: 0.75rem;
        border-bottom: 1px solid #d0d7de;
        text-align: left;
      }

      th {
        background: #f6f8fa;
        color: #24292f;
        font-weight: 600;
      }

      .checkbox-column {
        width: 48px;
        text-align: center;
      }

      tr {
        &:hover {
          background: #f6f8fa;
        }

        &.is-paid {
          background: #f8f9fa;
          color: #57606a;
        }
      }

      .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;

        &.paid {
          background: #dafbe1;
          color: #1a7f37;
        }

        &.unpaid {
          background: #ffebe9;
          color: #cf222e;
        }
      }
    }
  }
}

.github-table {
  .checkbox-column {
    .v-checkbox {
      .v-selection-control {
        &--dirty {
          .v-selection-control__input::before {
            background-color: #0969da;
          }
        }
        
        &--indeterminate {
          .v-selection-control__input::before {
            background-color: #6e7781;
          }
        }
      }
    }
  }
}
</style>
