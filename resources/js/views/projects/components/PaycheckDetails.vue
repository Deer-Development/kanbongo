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

const paychecksDetails = ref(null)
const loading = ref(false)

const fetchMemberPaychecksDetails = async () => {
  if (!props.member) return

  loading.value = true
  try {
    const res = await $api(`/container/${props.boardId}/member-paychecks-details/${props.member.user.id}`, {
      method: "GET",
      params: { date_range: props.dateRange },
    })

    paychecksDetails.value = res.data
  } catch (error) {
    console.error("Error fetching member payment details:", error)
  } finally {
    loading.value = false
  }
}

const formatDate = dateString => {
  return format(new Date(dateString), "MMM d, yyyy h:mm:ss a")
}

watch(() => props.dateRange, (newValue, oldValue) => {
  if (newValue !== oldValue) fetchMemberPaychecksDetails()
}, { deep: true, immediate: true })

watch(() => props.member, (newValue, oldValue) => {
  console.log("Member changed:", newValue, oldValue)
  if (newValue && newValue !== oldValue) fetchMemberPaychecksDetails()
}, { deep: true, immediate: true })
</script>

<template>
  <div class="member-payment-details">
    <!-- Payment Summary Section -->
    <div v-if="paychecksDetails?.length" class="payment-summary">
      <h3>Payment History</h3>
      <div class="summary-stats">
        <div class="summary-stat">
          <span>Total Paychecks</span>
          <span>{{ paychecksDetails.length }}</span>
        </div>
        <div class="summary-stat">
          <span>Total Hours</span>
          <span>{{ paychecksDetails.reduce((sum, p) => sum + parseFloat(p.total_hours), 0).toFixed(2) }} hrs</span>
        </div>
        <div class="summary-stat">
          <span>Total Amount</span>
          <span>${{ paychecksDetails.reduce((sum, p) => sum + parseFloat(p.total_amount), 0).toFixed(2) }}</span>
        </div>
        <div class="summary-stat">
          <span>Latest Payment</span>
          <span>{{ paychecksDetails[0]?.created_at ? formatDate(paychecksDetails[0].created_at) : 'N/A' }}</span>
        </div>
      </div>
    </div>

    <div v-if="paychecksDetails" class="paychecks-grid">
      <div
        v-for="paycheck in paychecksDetails"
        :key="paycheck.id"
        class="paycheck-card"
      >
        <div class="status-badge" :class="paycheck.status">
          {{ paycheck.status === 'paid' ? 'Paid' : 'Pending' }}
        </div>

        <div class="payment-stats">
          <div class="stat-row">
            <span class="label">Total Paid Hours:</span>
            <span class="value text-success">{{ paycheck.total_hours }} hrs</span>
          </div>
          <div class="stat-row">
            <span class="label">Total Paid Amount:</span>
            <span class="value text-success">${{ paycheck.total_amount }}</span>
          </div>
          <div class="stat-row">
            <span class="label">Paid By:</span>
            <span class="value text-primary">{{ paycheck.created_by.full_name }}</span>
          </div>
          <div class="stat-row">
            <span class="label">Paid At:</span>
            <span class="value text-primary">{{ formatDate(paycheck.created_at) }}</span>
          </div>
        </div>
      </div>
    </div>

    <div
      v-else
      class="no-data"
    >
      No paychecks details available.
    </div>
  </div>
</template>

<style lang="scss" scoped>
.member-payment-details {
  padding: 1rem;
}

.payment-summary {
  background: #ffffff;
  border: 1px solid #d0d7de;
  border-radius: 6px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  width: 100%;

  h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #24292f;
    margin-bottom: 1rem;
  }

  .summary-stats {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    
    @media (max-width: 768px) {
      grid-template-columns: repeat(2, 1fr);
    }
    
    @media (max-width: 480px) {
      grid-template-columns: 1fr;
    }
  }

  .summary-stat {
    background: #f6f8fa;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #d0d7de;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;

    span:first-child {
      font-size: 0.875rem;
      color: #57606a;
      display: block;
      margin-bottom: 0.25rem;
    }

    span:last-child {
      font-size: 1.125rem;
      font-weight: 600;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }
}

.paychecks-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
}

.paycheck-card {
  position: relative;
  background-color: #ffffff;
  border: 1px solid #d0d7de;
  border-radius: 6px;
  padding: 1.25rem;
  transition: all 0.2s ease-in-out;

  &:hover {
    border-color: #0969da;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .status-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;

    &.paid {
      background: #dafbe1;
      color: #1a7f37;
    }

    &.pending {
      background: #fff8c5;
      color: #9a6700;
    }
  }

  .payment-stats {
    background: #f6f8fa;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    padding: 1rem;
    margin-top: 1rem;

    .stat-row {
      display: flex;
      justify-content: space-between;
      font-size: 0.875rem;
      padding: 0.5rem 0;
      border-bottom: 1px solid #d8dee4;

      &:last-child {
        border-bottom: none;
      }

      span:first-child {
        color: #57606a;
      }
    }
  }
}

.input-github {
  .v-input {
    border-radius: 6px;
    padding: 0.4rem;
    transition: border-color 0.2s;

    &:focus-within {
      border-color: #0969da;
      box-shadow: 0 0 0 2px rgba(9, 105, 218, 0.3);
    }
  }
}

.global-stats {
  padding: 1rem;
  border-top: 1px solid #d0d7de;
  margin-top: 1rem;

  h4 {
    font-size: 1.1rem;
    font-weight: bold;
    margin-bottom: 0.75rem;
  }

  div {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;

    span:last-child {
      font-weight: bold;
      color: #0969da;
    }
  }
}

.no-data {
  text-align: center;
  font-size: 0.875rem;
  color: #57606a;
  margin-top: 1.5rem;
  padding: 2rem;
  background: #f6f8fa;
  border: 1px solid #d0d7de;
  border-radius: 6px;
}

.text-success { color: #1a7f37; }
.text-danger { color: #cf222e; }
.text-primary { color: #0969da; }
</style>


