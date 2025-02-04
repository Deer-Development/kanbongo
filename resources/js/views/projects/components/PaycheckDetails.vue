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
    <div v-if="paychecksDetails" class="members-container">
      <div
        v-for="paycheck in paychecksDetails"
        :key="paycheck.id"
        class="entry-card-github"
      >
        <VChip
          :color="(paycheck.status === 'paid') ? 'success' : 'warning'"
          class="payment-status-badge"
          label
          outlined
        >
          {{
            (paycheck.status === 'paid') ? 'Paid' : 'Pending'
          }}
        </VChip>

        <div class="payment-details mt-7">
          <div class="detail">
            <span class="label">Total Paid Hours:</span>
            <span class="value text-success">{{ paycheck.total_hours }} hrs</span>
          </div>
          <div class="detail">
            <span class="label">Total Paid Amount:</span>
            <span class="value text-success">${{ paycheck.total_amount }}</span>
          </div>
          <div class="detail">
            <span class="label">Paid By:</span>
            <span class="value text-primary">{{ paycheck.created_by.full_name }}</span>
          </div>
          <div class="detail">
            <span class="label">Paid At:</span>
            <span class="value text-primary">{{ formatDate(paycheck.created_at) }}</span>
          </div>
        </div>

        <div class="card-actions">

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
.members-container {
  display: grid;
  gap: 0.8rem;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.entry-card-github {
  position: relative;
  background-color: #ffffff;
  border: 1px solid #d0d7de;
  border-radius: 8px;
  padding: 1rem;
  transition: all 0.2s ease-in-out;

  &:hover {
    background-color: #f3f4f6;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
  }

  .payment-status-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  }

  .member-info {
    h3 {
      font-size: 1rem;
      font-weight: 600;
      color: #24292e;
      margin-bottom: 2px;
    }
    p {
      font-size: 0.875rem;
      color: #57606a;
    }
  }

  .payment-details {
    margin-bottom: 0.75rem;
    .detail {
      display: flex;
      justify-content: space-between;
      font-size: 0.875rem;
      margin-bottom: 0.4rem;

      .label {
        color: #6c757d;
      }
      .value {
        font-weight: bold;
      }
    }
  }

  .card-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.4rem;

    .btn-github {
      font-weight: 600;
      padding: 0.4rem 0.8rem;
      border-radius: 6px;
      transition: all 0.2s ease-in-out;

      &:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
  font-size: 0.95rem;
  color: #6c757d;
  margin-top: 1.5rem;
}

.text-danger {
  color: #cf222e;
}

.text-primary {
  color: #0969da;
}
</style>


