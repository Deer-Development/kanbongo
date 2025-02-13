<script setup>
import { defineProps, ref, watch, computed } from "vue"
import MemberPaymentDetails from "@/views/projects/components/MemberPaymentDetails.vue"
import PaycheckDetails from "@/views/projects/components/PaycheckDetails.vue"

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
const selectedMember = ref(null)
const showPaychecks = ref(false)
const totalSelectedPayment = ref(0)
const selectedEntries = ref([])

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
  if (boardIdLocal.value !== 0 && !selectedMember.value) fetchMemberDetails()
})

const onReset = () => {
  emit("update:isDialogVisible", false)
  members.value = []
  boardIdLocal.value = null
  selectedDateRange.value = ""
  selectedMember.value = null
  showPaychecks.value = false
  totalSelectedPayment.value = 0
  selectedEntries.value = []
}

const totalPaid = computed(() => members.value.reduce((sum, member) => sum + member.total_amount_paid, 0))
const totalPaidHours = computed(() => members.value.reduce((sum, member) => sum + member.total_paid_hours, 0))
const totalPending = computed(() => members.value.reduce((sum, member) => sum + member.pending_payment, 0))
const totalUnpaidHours = computed(() => members.value.reduce((sum, member) => sum + member.total_unpaid_hours, 0))

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
    body: {
      date_range: selectedDateRange.value,
      selected_entries: selectedEntries.value,
    },
  })

  if (res) {
    selectedMember.value = null

    await nextTick(() => {
      fetchMemberDetails()
    })
  }
}

const handleDetails = async member => {
  selectedMember.value = member
}

const handlePaychecks = async member => {
  showPaychecks.value = true
  selectedMember.value = member
}

const goBack = () => {
  selectedMember.value = null
  showPaychecks.value = false
  fetchMemberDetails()
}
</script>

<template>
  <VDialog
    persistent
    fullscreen
    :model-value="props.isDialogVisible"
  >
    <DialogCloseBtn
      class="mt-4 mr-4"
      @click="onReset"
    />
    <VCard class="github-card">
      <VCardTitle class="sticky-header">
        <span v-if="showPaychecks">
          Board Paychecks Details
        </span>
        <span v-else>
          Board Payment Details
        </span>
        <span
          v-if="selectedMember"
          class="text-primary font-weight-bold"
        >for {{ selectedMember.user.full_name }}</span>
        <VRow>
          <VCol :cols="selectedMember ? 11 : 12">
            <div class="mb-4">
              <label
                for="date-picker"
                class="form-label text-body-2"
              >Select Date Range:</label>
              <AppDateTimePicker
                v-model="selectedDateRange"
                :config="{ mode: 'range' }"
                placeholder="Select date range"
                clearable
                class="input-github"
              />
            </div>
          </VCol>
          <VCol
            v-if="selectedMember"
            cols="1"
          >
            <VBtn
              color="info"
              class="mt-7"
              @click="goBack"
            >
              Back
            </VBtn>
          </VCol>
        </VRow>
        <div
          v-if="selectedMember"
          class="d-flex gap-2 mt-0 pt-0"
        >
          <div
            v-if="totalSelectedPayment > 0 && !showPaychecks"
            class="mt-0 pt-0"
          >
            <div class="custom-badge">
              <VIcon class="me-1">
                tabler-credit-card-pay
              </VIcon>
              <span>Selected Payment: <span class="text-success font-weight-bold">${{ totalSelectedPayment.toFixed(2) }}</span></span>
            </div>
          </div>
          <div
            v-if="totalSelectedPayment > 0 && !showPaychecks"
            class="mt-0 pt-0"
          >
            <div class="custom-badge">
              <VIcon class="me-1">
                tabler-brand-cashapp
              </VIcon>
              <span>Rate: <span class="text-success font-weight-bold">${{ selectedMember.billable_rate }}</span></span>
            </div>
          </div>
          <div
            v-if="totalSelectedPayment > 0 && !showPaychecks"
            class="mt-0 pt-0"
            @click="confirmPayment(selectedMember.user.id)"
          >
            <div class="custom-badge bg-warning">
              <VIcon>
                tabler-cash-register
              </VIcon>
              <span class="font-weight-bold">Pay Now</span>
            </div>
          </div>
        </div>
      </VCardTitle>

      <VCardText>
        <div v-if="selectedMember && !showPaychecks">
          <MemberPaymentDetails
            :member="selectedMember"
            :date-range="selectedDateRange"
            :board-id="boardIdLocal"
            :is-owner="props.isOwner"
            :is-super-admin="props.isSuperAdmin"
            @update:selected-payment="totalSelectedPayment = $event"
            @update:selected-entries="selectedEntries = $event"
          />
        </div>
        <div v-if="selectedMember && showPaychecks">
          <PaycheckDetails
            :member="selectedMember"
            :date-range="selectedDateRange"
            :board-id="boardIdLocal"
            :is-owner="props.isOwner"
            :is-super-admin="props.isSuperAdmin"
            @update:selected-payment="totalSelectedPayment = $event"
            @update:selected-entries="selectedEntries = $event"
          />
        </div>
        <div
          v-else-if="members.length && !selectedMember && !showPaychecks"
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
              <div
                v-if="(props.isSuperAdmin || props.isOwner) && member.pending_payment > 0"
                class="custom-badge bg-warning"
                @click="confirmPayment(member.user.id)"
              >
                <VIcon>
                  tabler-cash-register
                </VIcon>
                <span class="font-weight-bold">Pay Now</span>
              </div>
              <div
                class="custom-badge"
                @click="handleDetails(member)"
              >
                <VIcon>
                  tabler-list-details
                </VIcon>
                <span class="font-weight-bold">Details</span>
              </div>
              <div
                v-if="member.has_paychecks"
                class="custom-badge bg-success"
                @click="handlePaychecks(member)"
              >
                <VIcon>
                  tabler-list-details
                </VIcon>
                <span class="font-weight-bold">Paychecks</span>
              </div>
            </div>
          </div>
        </div>
        <div
          v-if="!members.length && !selectedMember && !showPaychecks"
          class="no-data"
        >
          No payment details available.
        </div>

        <div class="summary-container" v-if="!selectedMember">
          <h4 class="summary-title">Payment Summary</h4>

          <div class="summary-content">
            <div class="summary-item">
              <span class="label">Total Paid Amount:</span>
              <span class="value text-success">${{ totalPaid.toFixed(2) }}</span>
            </div>

            <div class="summary-item">
              <span class="label">Total Pending Amount:</span>
              <span class="value text-danger">${{ totalPending.toFixed(2) }}</span>
            </div>

            <div class="progress-bar-container">
              <VProgressLinear
                :model-value="(totalPaid / (totalPaid + totalPending)) * 100"
                color="#0969da"
                height="12"
                rounded
              >
              </VProgressLinear>
              <div class="progress-text">
                {{ ((totalPaid / (totalPaid + totalPending)) * 100).toFixed(1) }}% Paid
              </div>
            </div>

            <div class="summary-row">
              <div class="summary-item flex-column">
                <span class="label">Total Paid Hours:</span>
                <span class="value text-success">{{ totalPaidHours.toFixed(2) }} hrs</span>
              </div>
              <div class="summary-item flex-column">
                <span class="label">Total Unpaid Hours:</span>
                <span class="value text-danger">{{ totalUnpaidHours.toFixed(2) }} hrs</span>
              </div>
            </div>

            <div class="summary-status">
              <VChip
                :color="totalPending > 0 ? 'warning' : 'success'"
                variant="elevated"
                class="status-chip"
              >
                {{
                  totalPending > 0
                    ? `⚠️ Pending Payments: $${totalPending.toFixed(2)}`
                    : "✅ All Payments Cleared"
                }}
              </VChip>
            </div>
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
.sticky-header {
  position: sticky;
  top: 0;
  background-color: white;
  z-index: 10;
  padding: 1rem;
  border-bottom: 1px solid #d0d7de;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.github-dialog {
  .v-card {
    background-color: #f6f8fa;
    border: 1px solid #d0d7de;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  }
}

.github-card {
  //padding: 1rem;
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

.summary-container {
  padding: 1.5rem;
  border-radius: 10px;
  margin-top: 1.5rem;
  background: #ffffff;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  border-left: 6px solid #1466d4;
  width: 50%;
}

.summary-title {
  font-size: 1rem;
  font-weight: 700;
  color: #24292e;
  margin-bottom: 1rem;
}

.summary-content {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  font-weight: 500;

  .label {
    color: #6c757d;
  }

  .value {
    font-weight: 700;
  }
}

.progress-bar-container {
  position: relative;
  margin: 1rem 0;

  .progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.6rem;
    font-weight: 600;
    color: #b1b0b0;
  }
}

.summary-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.status-chip {
  font-size: 1rem;
  font-weight: 600;
  padding: 0.6rem 1.2rem;
  text-align: center;
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

