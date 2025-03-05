<script setup>
import { defineProps, ref, watch, computed } from "vue"
import { format } from "date-fns"

const props = defineProps({
  member: { type: Object, required: true },
  dateRange: { type: String, required: false, default: null },
  paymentStatus: { type: String, required: false, default: 'all' },
  boardId: { type: Number, required: true, default: 0 },
  isOwner: { type: Boolean, required: false, default: false },
  isSuperAdmin: { type: Boolean, required: false, default: false },
})

const emit = defineEmits(["update:selectedPayment", "update:selectedEntries"])

const paymentDetails = ref(null)
const loading = ref(false)
const selectedTimeEntries = ref([])
const expandedTasks = ref([]) // Track expanded tasks for time entries
const expandedLogs = ref([]) // Track expanded tasks for logs

const fetchMemberPaymentDetails = async () => {
  if (!props.member) return

  loading.value = true
  try {
    const res = await $api(`/container/${props.boardId}/member-payment-details/${props.member.user.id}`, {
      method: "GET",
      params: { 
        date_range: props.dateRange,
        payment_status: props.paymentStatus
      },
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
  return entries
    .filter(entry => !entry.deleted_at && !entry.is_paid)
    .every(entry => selectedTimeEntries.value.includes(entry.id))
}

const toggleTaskEntries = (taskDetail) => {
  const entryIds = taskDetail.timeEntries
    .filter(entry => !entry.deleted_at && !entry.is_paid)
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

const toggleTaskExpansion = (taskId) => {
  const index = expandedTasks.value.indexOf(taskId)
  if (index === -1) {
    expandedTasks.value.push(taskId)
  } else {
    expandedTasks.value.splice(index, 1)
  }
}

const toggleLogsExpansion = (taskId) => {
  const index = expandedLogs.value.indexOf(taskId)
  if (index === -1) {
    expandedLogs.value.push(taskId)
  } else {
    expandedLogs.value.splice(index, 1)
  }
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

const getLogIcon = (action) => {
  switch (action) {
    case 'update':
      return 'tabler-edit'
    case 'delete':
      return 'tabler-trash'
    default:
      return 'tabler-info-circle'
  }
}

const hasTimeChange = (log) => {
  return log.new_data?.end || log.new_data?.start
}

const hasPaymentChange = (log) => {
  return log.new_data?.is_paid
}

const formatTimeRange = (start, end) => {
  if (!start || !end) return '—'
  return `${format(new Date(start), 'MMM d, yyyy h:mm:ss a')} - ${format(new Date(end), 'h:mm:ss a')}`
}

const isAllEntriesPaid = (entries) => {
  if (!entries || entries.length === 0) return false
  return entries.every(entry => entry.is_paid)
}

watch(() => selectedTimeEntries, () => {
  emit("update:selectedPayment", totalSelectedPayment.value)
  emit("update:selectedEntries", selectedTimeEntries.value)
}, { deep: true, immediate: true })

watch(() => props.dateRange, fetchMemberPaymentDetails, { deep: true, immediate: true })
watch(() => props.member, fetchMemberPaymentDetails, { deep: true, immediate: true })
watch(() => props.paymentStatus, fetchMemberPaymentDetails, { deep: true })
</script>

<template>
  <div class="member-payment-details">
    <div v-if="paymentDetails" class="tasks-container">
      <div
        v-for="(taskDetail, key) in paymentDetails"
        :key="key"
        class="github-card"
        :class="{ 'is-deleted': taskDetail.task?.deleted_at }"
      >
        <div class="card-header">
          <div class="header-left">
            <div v-if="props.isOwner || props.isSuperAdmin" class="select-all">
              <VCheckbox
                :model-value="isAllSelected(taskDetail.timeEntries)"
                :indeterminate="
                  taskDetail.timeEntries.some(entry => selectedTimeEntries.includes(entry.id)) &&
                  !isAllSelected(taskDetail.timeEntries)
                "
                :disabled="!!taskDetail.task?.deleted_at || isAllEntriesPaid(taskDetail.timeEntries)"
                :color="isAllEntriesPaid(taskDetail.timeEntries) ? 'success' : undefined"
                @change="toggleTaskEntries(taskDetail)"
              />
            </div>
            
            <div class="header-content">
              <div class="task-title-container">
                <h4 class="task-title">
                  <span class="task-id">{{ taskDetail.task?.sequence_id }})</span>
                  {{ taskDetail.task?.name }}
                  <span v-if="taskDetail.task?.deleted_at" class="deleted-badge">
                    Deleted
                  </span>
                </h4>
                <div class="task-metrics">
                  <div class="time-badge">
                    <VIcon size="16" color="success">tabler-clock-play</VIcon>
                    <span>{{ taskDetail.trackedTimeDisplay }}</span>
                  </div>
                  
                  <div class="payment-badges">
                    <div v-if="taskDetail.paidAmount > 0" class="payment-badge paid">
                      <VIcon size="16">tabler-cash</VIcon>
                      <span>${{ taskDetail.paidAmount }}</span>
                    </div>
                    <div v-if="taskDetail.pendingAmount > 0" class="payment-badge pending">
                      <VIcon size="16">tabler-clock-dollar</VIcon>
                      <span>${{ taskDetail.pendingAmount }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="header-actions">
            <VBtn
              variant="text"
              size="small"
              class="action-btn"
              :class="{ active: expandedTasks.includes(taskDetail.task?.id) }"
              prepend-icon="tabler-clock-hour-4"
              @click="toggleTaskExpansion(taskDetail.task?.id)"
            >
              Time Entries
            </VBtn>
            
            <VBtn
              v-if="taskDetail.task && taskDetail.entries_logs.length"
              variant="text"
              size="small"
              class="action-btn"
              :class="{ active: expandedLogs.includes(taskDetail.task?.id) }"
              prepend-icon="tabler-history"
              @click="toggleLogsExpansion(taskDetail.task?.id)"
            >
              Task Logs
            </VBtn>
          </div>
        </div>

        <!-- Time Entries Section -->
        <VExpandTransition>
          <div v-if="expandedTasks.includes(taskDetail.task?.id)" class="entries-section">
            <div class="table-container">
              <table class="github-table">
                <thead>
                  <tr>
                    <th v-if="props.isOwner || props.isSuperAdmin" class="checkbox-column">Select</th>
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
                    :class="{
                      'is-paid': entry.is_paid,
                      'is-deleted': entry.deleted_at
                    }"
                  >
                    <td v-if="props.isOwner || props.isSuperAdmin" class="checkbox-column">
                      <VCheckbox
                        v-model="selectedTimeEntries"
                        :value="entry.id"
                        :disabled="entry.is_paid || !!entry.deleted_at"
                      />
                    </td>
                    <td>
                      {{ formatDate(entry.start) }}
                      <span v-if="entry.deleted_at" class="deleted-badge small">Deleted</span>
                    </td>
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
        </VExpandTransition>

        <!-- Task Logs Section -->
        <VExpandTransition>
          <div v-if="expandedLogs.includes(taskDetail.task?.id)" class="logs-section">
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
                <template #icon>
                  <VIcon :color="getLogColor(log.action)" size="16">
                    {{ getLogIcon(log.action) }}
                  </VIcon>
                </template>

                <div class="log-entry">
                  <div class="log-header">
                    <div class="log-meta">
                      <span class="action-badge" :class="log.action">
                        {{ log.action.toUpperCase() }}
                      </span>
                      <span class="user-info">
                        <VAvatar size="20" class="user-avatar">
                          <span v-if="!log.user.avatar" class="text-caption">
                            {{ log.user.avatar_or_initials }}
                          </span>
                          <VImg
                            v-else
                            :src="log.user.avatar"
                            :alt="log.user.full_name"
                          />
                        </VAvatar>
                        {{ log.user.full_name }}
                      </span>
                    </div>
                    <span class="log-date">
                      <VIcon size="14" color="grey">tabler-clock</VIcon>
                      {{ formatDate(log.created_at) }}
                    </span>
                  </div>

                  <div class="log-content">
                    <template v-if="log.action === 'update'">
                      <div class="changes-container">
                        <div v-if="hasTimeChange(log)" class="change-item time-change">
                          <VIcon size="16" color="primary">tabler-clock-edit</VIcon>
                          <div class="change-details">
                            <span class="change-label">Time Adjustment</span>
                            <div class="time-comparison">
                              <div class="old-value">
                                <small>From</small>
                                <span>{{ formatTimeRange(log.old_data.start, log.old_data.end) }}</span>
                              </div>
                              <VIcon size="16" color="grey">tabler-arrow-right</VIcon>
                              <div class="new-value">
                                <small>To</small>
                                <span>{{ formatTimeRange(log.old_data.start, log.new_data.end) }}</span>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div v-if="hasPaymentChange(log)" class="change-item payment-change">
                          <VIcon size="16" color="success">tabler-currency-dollar</VIcon>
                          <div class="change-details">
                            <span class="change-label">Payment Update</span>
                            <div class="payment-info">
                              <template v-if="log.new_data.is_paid">
                                <div class="payment-status success">
                                  <VIcon size="14">tabler-check</VIcon>
                                  Marked as Paid
                                </div>
                                <div class="payment-details">
                                  <span>Amount: ${{ log.new_data.amount_paid.toFixed(2) }}</span>
                                  <span>Rate: ${{ log.new_data.paid_rate }}/hr</span>
                                </div>
                              </template>
                              <template v-else>
                                <div class="payment-status pending">
                                  <VIcon size="14">tabler-clock</VIcon>
                                  Pending Payment
                                </div>
                              </template>
                            </div>
                          </div>
                        </div>
                      </div>
                    </template>

                    <template v-else-if="log.action === 'delete'">
                      <div class="delete-info">
                        <VIcon size="16" color="error">tabler-trash</VIcon>
                        <div class="delete-details">
                          <span class="delete-message">Time entry was removed</span>
                          <div class="deleted-time">
                            {{ formatTimeRange(log.old_data.start, log.old_data.end) }}
                          </div>
                        </div>
                      </div>
                    </template>
                  </div>
                </div>
              </VTimelineItem>
            </VTimeline>
          </div>
        </VExpandTransition>
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

    &.is-deleted {
      opacity: 0.8;
      background: #f6f8fa;
      
      .card-header {
        background: #f0f2f4;
      }
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 1rem;

      .header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
      }

      .select-all {
        padding-right: 0.5rem;
        border-right: 1px solid #d0d7de;
      }

      .header-actions {
        display: flex;
        gap: 0.5rem;
      }

      .action-btn {
        color: #57606a;
        font-size: 0.75rem;
        border: 1px solid transparent;
        
        &:hover, &.active {
          color: #0969da;
          background: #f6f8fa;
          border-color: #d0d7de;
        }
      }

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
        display: flex;
        align-items: center;
        gap: 0.5rem;

        .task-id {
          color: #57606a;
          font-family: monospace;
          font-weight: 500;
        }
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
        width: fit-content;
      }

      .logs-btn {
        color: #57606a;
        font-size: 0.75rem;
        
        &:hover {
          color: #0969da;
        }
      }
    }

    .deleted-badge {
      display: inline-block;
      padding: 0.125rem 0.5rem;
      font-size: 0.75rem;
      font-weight: 500;
      color: #cf222e;
      background: #ffebe9;
      border-radius: 2rem;
      margin-left: 0.5rem;

      &.small {
        font-size: 0.675rem;
        padding: 0.125rem 0.375rem;
      }
    }

    .entries-section {
      border-top: 1px solid #d0d7de;
    }

    .logs-section {
      padding: 1.5rem;
      background: #f6f8fa;
      border-top: 1px solid #d0d7de;

      .github-timeline {
        .log-entry {
          background: #ffffff;
          border: 1px solid #d0d7de;
          border-radius: 8px;
          padding: 1rem;
          box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
          transition: all 0.2s ease;

          &:hover {
            border-color: #0969da;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
          }

          .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;

            .log-meta {
              display: flex;
              align-items: center;
              gap: 0.75rem;
            }

            .user-info {
              display: flex;
              align-items: center;
              gap: 0.5rem;
              font-size: 0.875rem;
              color: #24292f;

              .user-avatar {
                background: #f3f4f6;
                color: #57606a;
                font-size: 0.75rem;
                border: 1px solid #d0d7de;
              }
            }

            .action-badge {
              font-size: 0.75rem;
              font-weight: 600;
              padding: 0.25rem 0.5rem;
              border-radius: 2rem;

              &.update { 
                background: #ddf4ff; 
                color: #0969da;
              }
              &.delete { 
                background: #ffebe9; 
                color: #cf222e;
              }
            }

            .log-date {
              display: flex;
              align-items: center;
              gap: 0.375rem;
              font-size: 0.75rem;
              color: #57606a;
            }
          }

          .log-content {
            .changes-container {
              display: flex;
              flex-direction: column;
              gap: 1rem;
            }

            .change-item {
              display: flex;
              gap: 0.75rem;
              padding: 0.75rem;
              background: #f6f8fa;
              border-radius: 6px;
              border: 1px solid #d0d7de;

              .change-details {
                flex: 1;

                .change-label {
                  display: block;
                  font-size: 0.75rem;
                  font-weight: 600;
                  color: #57606a;
                  margin-bottom: 0.5rem;
                }
              }

              &.time-change {
                .time-comparison {
                  display: flex;
                  align-items: center;
                  gap: 1rem;
                  font-size: 0.875rem;

                  .old-value, .new-value {
                    display: flex;
                    flex-direction: column;
                    gap: 0.25rem;

                    small {
                      color: #57606a;
                      font-size: 0.75rem;
                    }

                    span {
                      color: #24292f;
                      font-family: monospace;
                    }
                  }
                }
              }

              &.payment-change {
                .payment-info {
                  .payment-status {
                    display: flex;
                    align-items: center;
                    gap: 0.375rem;
                    font-size: 0.875rem;
                    margin-bottom: 0.5rem;

                    &.success {
                      color: #1a7f37;
                    }

                    &.pending {
                      color: #9a6700;
                    }
                  }

                  .payment-details {
                    display: flex;
                    gap: 1rem;
                    font-size: 0.875rem;
                    color: #24292f;
                  }
                }
              }
            }

            .delete-info {
              display: flex;
              gap: 0.75rem;
              padding: 0.75rem;
              background: #ffebe9;
              border-radius: 6px;
              border: 1px solid #cf222e;

              .delete-details {
                .delete-message {
                  display: block;
                  font-size: 0.875rem;
                  font-weight: 600;
                  color: #cf222e;
                  margin-bottom: 0.25rem;
                }

                .deleted-time {
                  font-size: 0.875rem;
                  color: #57606a;
                  font-family: monospace;
                }
              }
            }
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

        &.is-deleted {
          opacity: 0.8;
          background: #f6f8fa;
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

  .task-metrics {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.5rem;
  }

  .payment-badges {
    display: flex;
    gap: 0.5rem;
  }

  .payment-badge {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
    transition: all 0.2s ease;

    &:hover {
      transform: translateY(-1px);
    }

    &.paid {
      background: #dafbe1;
      color: #1a7f37;
      
      .v-icon {
        color: #1a7f37;
        opacity: 0.8;
      }
    }

    &.pending {
      background: #fff8c5;
      color: #9a6700;
      
      .v-icon {
        color: #9a6700;
        opacity: 0.8;
      }
    }
  }

  .github-card {
    @media (max-width: 768px) {
      margin: 0 -1rem; // Negative margin to stretch on mobile
      border-radius: 0;
      border-left: none;
      border-right: none;
    }

    .card-header {
      @media (max-width: 768px) {
        flex-direction: column;
        gap: 1rem;

        .header-left {
          width: 100%;
        }

        .header-actions {
          width: 100%;
          justify-content: stretch;
          border-top: 1px solid #d0d7de;
          padding-top: 0.75rem;

          .action-btn {
            flex: 1;
            justify-content: center;
          }
        }
      }

      .task-metrics {
        @media (max-width: 768px) {
          flex-wrap: wrap;
          gap: 0.5rem;
        }
      }

      .payment-badges {
        @media (max-width: 768px) {
          flex-wrap: wrap;
        }
      }
    }

    // Improve table responsiveness on mobile
    .table-container {
      @media (max-width: 768px) {
        margin: 0;  // Remove negative margin
        position: relative;
        overflow-x: auto;
        width: 100%;
        
        // Enable smooth scrolling
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        
        &::-webkit-scrollbar {
          display: none;
        }
        
        .github-table {
          position: relative;
          border-spacing: 0;
          
          th, td {
            padding: 0.5rem;
            font-size: 0.75rem;
            min-width: 120px;
            white-space: nowrap;
            
            // Make first column sticky
            &:first-child {
              position: sticky;
              left: 0;
              z-index: 2;
              background: #ffffff;
              width: 60px;  // Fixed width for checkbox column
              min-width: 60px;  // Ensure minimum width
              max-width: 60px;  // Ensure maximum width
              padding: 0.5rem;
              border-right: 1px solid #d0d7de;
              box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.1);
            }
            
            &:last-child {
              padding-right: 1rem;
            }
          }

          // Special styling for header cells
          th:first-child {
            background: #f6f8fa;
            z-index: 3;
            width: 60px;
            min-width: 60px;
            max-width: 60px;
          }

          // Checkbox column specific styles
          .checkbox-column {
            width: 60px;
            min-width: 60px;
            max-width: 60px;
            padding: 0.5rem;
            text-align: center;

            .v-checkbox {
              margin: 0 auto;
              width: fit-content;
              
              .v-selection-control {
                width: auto;
                min-width: auto;
              }
            }
          }

          // Ensure proper background for sticky column on hover
          tr:hover td:first-child {
            background: #f6f8fa;
          }

          tr.is-paid td:first-child {
            background: #f8f9fa;
          }

          tr.is-deleted td:first-child {
            background: #f6f8fa;
            opacity: 0.8;
          }
        }

        // Add fade effect on the right
        &::after {
          content: '';
          position: absolute;
          right: 0;
          top: 0;
          bottom: 0;
          width: 24px;
          background: linear-gradient(to right, transparent, rgba(255,255,255,0.9));
          pointer-events: none;
          z-index: 1;
        }
      }
    }

    // Improve logs section on mobile
    .logs-section {
      @media (max-width: 768px) {
        padding: 1rem;

        .github-timeline {
          .log-entry {
            padding: 0.75rem;

            .log-header {
              flex-direction: column;
              align-items: flex-start;
              gap: 0.5rem;

              .log-date {
                width: 100%;
                justify-content: flex-start;
                padding-top: 0.25rem;
                border-top: 1px solid #d0d7de;
              }
            }

            .log-content {
              .change-item {
                padding: 0.5rem;

                &.time-change {
                  .time-comparison {
                    flex-direction: column;
                    gap: 0.5rem;

                    .v-icon {
                      display: none; // Hide arrow on mobile
                    }
                  }
                }

                &.payment-change {
                  .payment-info {
                    .payment-details {
                      flex-direction: column;
                      gap: 0.25rem;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  // Improve task metrics display on mobile
  .task-metrics {
    @media (max-width: 768px) {
      margin-top: 0.75rem;
      
      .time-badge, .payment-badge {
        padding: 0.25rem 0.5rem;
        font-size: 0.7rem;
      }
    }
  }

  // Improve status badges on mobile
  .status-badge {
    @media (max-width: 768px) {
      white-space: nowrap;
      
      &.paid, &.unpaid {
        padding: 0.125rem 0.375rem;
        font-size: 0.7rem;
      }
    }
  }
}

// Add smooth transitions for better mobile experience
.v-expand-transition {
  &-enter-active,
  &-leave-active {
    transition: all 0.3s ease-out;
  }
  
  &-enter-from,
  &-leave-to {
    opacity: 0;
    transform: translateY(-10px);
  }
}
</style>
