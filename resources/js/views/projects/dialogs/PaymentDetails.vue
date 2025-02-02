<script setup>
import { defineProps, ref, watch, computed } from "vue"

const props = defineProps({
  boardId: { type: Number, required: true, default: 0 },
  isOwner: { type: Boolean, required: false, default: false },
  isSuperAdmin: { type: Boolean, required: false, default: false },
  isDialogVisible: { type: Boolean, required: true, default: false },
})

const emit = defineEmits(["update:isDialogVisible", "update:boardDetails", "formSubmitted"])

const boardIdLocal = ref(null)
const isConfirmDialogVisible = ref(false)
const members = ref([])
const memberToPay = ref(null)

const selectedDateRange = ref("")

const fetchMemberDetails = async () => {
  const res = await $api(`/board/payment-details/${boardIdLocal.value}`, {
    method: "GET",
    params: { date_range: selectedDateRange.value, is_super_admin: props.isSuperAdmin, is_owner: props.isOwner },
  })

  members.value = res.data
}

watch(() => props.boardId, value => {
  boardIdLocal.value = value
  if (boardIdLocal.value !== 0) fetchMemberDetails()
})

watch(() => props.isDialogVisible, value => {
  boardIdLocal.value = props.boardId
  if (value) fetchMemberDetails()
})

watch(selectedDateRange, () => {
  if (boardIdLocal.value !== 0) fetchMemberDetails()
})

const onReset = () => {
  emit("update:isDialogVisible", false)
  members.value = []
  boardIdLocal.value = null
  selectedDateRange.value = ""
}

const totalPaid = computed(() => members.value.reduce((sum, member) => sum + member.total_amount_paid, 0))
const totalPending = computed(() => members.value.reduce((sum, member) => sum + member.pending_payment, 0))

const confirmPayment = member => {
  memberToPay.value = member
  isConfirmDialogVisible.value = true
}

const closeConfirmDialog = () => {
  isConfirmDialogVisible.value = false
  memberToPay.value = null
}

const handlePayment = async () => {
  const res = await $api(`/container/${boardIdLocal.value}/process-payment/${memberToPay.value}`, {
    method: "POST",
    body: { date_range: selectedDateRange.value },
  })

  if (res) fetchMemberDetails()
}

const handleDetails = member => {
  console.log("Viewing details for", member)
}
</script>

<template>
  <VDialog
    persistent
    max-width="80%"
    :model-value="props.isDialogVisible"
    class="github-dialog"
  >
    <DialogCloseBtn
      class="close-btn"
      @click="onReset"
    />
    <VCard class="p-4 github-card">
      <VCardTitle class="text-center text-dark fs-6 fw-bold">
        Board Payment Details
      </VCardTitle>

      <VCardText>
        <div class="mb-4">
          <label
            for="date-picker"
            class="form-label"
          >Select Date Range:</label>
          <AppDateTimePicker
            v-model="selectedDateRange"
            :config="{ mode: 'range' }"
            placeholder="Select date range"
            class="input-github"
          />
        </div>

        <div
          v-if="members.length"
          class="members-container"
        >
          <div
            v-for="member in members"
            :key="member.member_id"
            class="entry-card-github"
          >
            <VChip
              :color="(member.total_unpaid_hours === 0 && member.total_paid_hours !== 0) ? 'success' : 'warning'"
              class="payment-status-badge"
              label
              outlined
            >
              {{
                (member.total_unpaid_hours === 0 && member.total_paid_hours !== 0) ? 'Paid' : (member.pending_payment > 0 ? 'Pending' : 'Not Worked')
              }}
            </VChip>

            <div class="d-flex align-items-center gap-3 mb-3 mt-7">
              <VAvatar
                size="50"
                class="avatar"
                :color="$vuetify.theme.current.dark ? '#373B50' : '#F5F7FA'"
              >
                <template v-if="member.user.avatar">
                  <img
                    :src="member.user.avatar"
                    alt="Avatar"
                  >
                </template>
                <template v-else>
                  <span>{{ member.user.avatar_or_initials }}</span>
                </template>
              </VAvatar>
              <div class="member-info">
                <h3 class="fs-6 text-dark fw-bold">
                  {{ member.member_name }}
                </h3>
                <p class="text-muted">
                  {{ member.user.email }}
                </p>
              </div>
            </div>

            <div class="payment-details">
              <div class="detail">
                <span class="label">Total Paid Hours:</span>
                <span class="value text-success">{{ member.total_paid_hours.toFixed(2) }} hrs</span>
              </div>
              <div class="detail">
                <span class="label">Total Paid Amount:</span>
                <span class="value text-success">${{ member.total_amount_paid.toFixed(2) }}</span>
              </div>
              <div class="detail">
                <span class="label">Total Unpaid Hours:</span>
                <span class="value text-danger">{{ member.total_unpaid_hours.toFixed(2) }} hrs</span>
              </div>
              <div class="detail">
                <span class="label">Pending Payment:</span>
                <span class="value text-danger">${{ member.pending_payment.toFixed(2) }}</span>
              </div>
              <div class="detail">
                <span class="label">Billable Rate:</span>
                <span class="value text-primary">${{ member.billable_rate }}/hr</span>
              </div>
            </div>

            <div class="card-actions">
              <VBtn
                v-if="(props.isSuperAdmin || props.isOwner) && member.pending_payment > 0"
                color="primary"
                variant="tonal"
                class="btn-github"
                @click="confirmPayment(member.user.id)"
              >
                Pay Now
              </VBtn>
              <VBtn
                color="secondary"
                variant="tonal"
                class="btn-github"
                @click="handleDetails(member)"
              >
                View Details
              </VBtn>
            </div>
          </div>
        </div>

        <div
          v-else
          class="no-data"
        >
          No payment details available.
        </div>

        <div class="global-stats mt-4">
          <h4 class="fs-6 fw-bold">
            Summary
          </h4>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Total Paid Amount:</span>
            <span class="text-success fw-bold">${{ totalPaid.toFixed(2) }}</span>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Total Pending Amount:</span>
            <span class="text-danger fw-bold">${{ totalPending.toFixed(2) }}</span>
          </div>
        </div>
      </VCardText>
    </VCard>
    <ConfirmDialog
      v-model:isDialogVisible="isConfirmDialogVisible"
      cancel-title="Cancel"
      confirm-title="Pay Now"
      confirm-msg="Payment will be processed."
      confirmation-question="Are you sure you want to proceed with this payment?"
      cancel-msg="Payment will not be processed."
      @confirm="confirmed => confirmed && handlePayment()"
      @close="closeConfirmDialog"
    />
  </VDialog>
</template>

<style lang="scss" scoped>
.github-dialog {
  .v-card {
    background-color: #f6f8fa;
    border: 1px solid #d0d7de;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  }
}

.github-card {
  padding: 1rem;
}

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

  .d-flex {
    align-items: center;
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
      &:hover {
        transform: translateY(-1px);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
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

