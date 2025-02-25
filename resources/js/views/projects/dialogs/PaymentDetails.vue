<script setup>
import { defineProps, ref, watch, computed } from "vue"
import MemberPaymentDetails from "@/views/projects/components/MemberPaymentDetails.vue"
import PaycheckDetails from "@/views/projects/components/PaycheckDetails.vue"

const props = defineProps({
  boardId: { type: Number, required: true, default: 0 },
  isOwner: { type: Boolean, required: false, default: false },
  isAdmin: { type: Boolean, required: false, default: false },
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
    params: { date_range: selectedDateRange.value, is_super_admin: props.isSuperAdmin, is_owner: props.isOwner, is_admin: props.isAdmin },
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

const paymentProgress = computed(() => {
  const total = totalPaid.value + totalPending.value
  return total > 0 ? (totalPaid.value / total) * 100 : 0
})

const getStatusClass = (member) => {
  if (member.total_unpaid_hours === 0 && member.total_paid_hours > 0) return 'paid'
  if (member.pending_payment > 0) return 'pending'
  return 'not-worked'
}

const getStatusText = (member) => {
  if (member.total_unpaid_hours === 0 && member.total_paid_hours > 0) return 'Paid'
  if (member.pending_payment > 0) return 'Pending'
  return 'Not Worked'
}

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
    class="payment-details-dialog"
  >
    <VCard class="github-card">
      <!-- Header Section -->
      <VCardTitle class="header-section">
        <!-- Top Bar with Close -->
        <div class="top-bar">
          <div class="d-flex align-center">
            <VIcon 
              size="20" 
              color="primary"
              class="mr-2"
            >
              tabler-wallet
            </VIcon>
            <span class="text-body-2 text-medium-emphasis">Payment Management</span>
          </div>
          <VBtn
            icon
            variant="text"
            size="small"
            class="close-btn"
            @click="onReset"
          >
            <VIcon>tabler-x</VIcon>
          </VBtn>
        </div>

        <!-- Main Header -->
        <div class="d-flex justify-space-between align-center mb-4 mt-4">
          <div class="title-section">
            <h2 class="header-title">
              {{ showPaychecks ? 'Paychecks Details' : 'Payment Details' }}
            </h2>
            <div class="subtitle" v-if="selectedMember">
              <VIcon size="16" color="primary" class="mr-1">tabler-user</VIcon>
              {{ selectedMember.user.full_name }}
            </div>
          </div>
          <VBtn
            v-if="selectedMember"
            variant="outlined"
            size="small"
            class="github-btn"
            prepend-icon="tabler-arrow-left"
            @click="goBack"
          >
            Back
          </VBtn>
        </div>

        <!-- Date Range Picker -->
        <div class="date-range-section">
          <AppDateTimePicker
            v-model="selectedDateRange"
            :config="{ mode: 'range' }"
            placeholder="Select date range"
            clearable
            class="github-input"
          />
        </div>

        <!-- Selected Payment Info -->
        <div 
          v-if="selectedMember && totalSelectedPayment > 0 && !showPaychecks" 
          class="selected-payment-info"
        >
          <div class="payment-stats">
            <div class="stat-item">
              <div class="stat-icon">
                <VIcon size="20" color="primary">tabler-credit-card-pay</VIcon>
              </div>
              <div class="stat-content">
                <span class="stat-label">Selected Amount</span>
                <span class="stat-value">${{ totalSelectedPayment.toFixed(2) }}</span>
              </div>
            </div>

            <div class="stat-item">
              <div class="stat-icon">
                <VIcon size="20" color="primary">tabler-currency-dollar</VIcon>
              </div>
              <div class="stat-content">
                <span class="stat-label">Hourly Rate</span>
                <span class="stat-value">${{ selectedMember.billable_rate }}/hr</span>
              </div>
            </div>

            <VBtn
              color="success"
              size="small"
              class="pay-btn"
              prepend-icon="tabler-cash-register"
              @click="confirmPayment(selectedMember.user.id)"
            >
              Pay
            </VBtn>
          </div>
        </div>
      </VCardTitle>

      <VDivider />

      <VCardText class="content-section">
        <!-- Members Grid -->
        <div v-if="!selectedMember" class="members-grid">
          <div
            v-for="member in members"
            :key="member.member_id"
            class="member-card"
          >
            <!-- Status Badge -->
            <div class="status-badge" :class="getStatusClass(member)">
              {{ getStatusText(member) }}
            </div>

            <!-- Member Info -->
            <div class="member-header">
              <VAvatar
                :size="48"
                :color="member.user.avatar ? 'transparent' : '#f3f4f6'"
              >
                <VImg
                  v-if="member.user.avatar"
                  :src="member.user.avatar"
                  alt="Avatar"
                />
                <span v-else class="text-subtitle-2">{{ member.user.avatar_or_initials }}</span>
              </VAvatar>
              <div class="member-details">
                <h3>{{ member.member_name }}</h3>
                <span>{{ member.user.email }}</span>
              </div>
            </div>

            <!-- Payment Stats -->
            <div class="payment-stats">
              <div class="stat-row">
                <span>Paid Hours</span>
                <span class="text-success">{{ member.total_paid_hours.toFixed(2) }} hrs</span>
              </div>
              <div class="stat-row">
                <span>Unpaid Hours</span>
                <span class="text-danger">{{ member.total_unpaid_hours.toFixed(2) }} hrs</span>
              </div>
              <div class="stat-row">
                <span>Paid Amount</span>
                <span class="text-success">${{ member.total_amount_paid.toFixed(2) }}</span>
              </div>
              <div class="stat-row">
                <span>Pending</span>
                <span class="text-danger">${{ member.pending_payment.toFixed(2) }}</span>
              </div>
              <div class="stat-row">
                <span>Rate</span>
                <span class="text-primary">${{ member.billable_rate || 0 }}/hr</span>
              </div>
            </div>

            <!-- Member Actions -->
            <div class="member-actions">
              <div class="actions-wrapper">
                <VBtn
                  v-if="(isSuperAdmin || isOwner) && member.pending_payment > 0"
                  color="warning"
                  variant="tonal"
                  size="small"
                  prepend-icon="tabler-cash-register"
                  @click="confirmPayment(member.user.id)"
                >
                  Pay Now
                </VBtn>
                <VBtn
                  variant="outlined"
                  size="small"
                  prepend-icon="tabler-list-details"
                  @click="handleDetails(member)"
                >
                  Details
                </VBtn>
                <VBtn
                  v-if="member.has_paychecks"
                  color="success"
                  variant="tonal"
                  size="small"
                  prepend-icon="tabler-receipt"
                  @click="handlePaychecks(member)"
                >
                  Paychecks
                </VBtn>
              </div>
            </div>
          </div>
        </div>

        <!-- Member Details View -->
        <div v-else-if="selectedMember && !showPaychecks">
          <MemberPaymentDetails
            :member="selectedMember"
            :date-range="selectedDateRange"
            :board-id="boardIdLocal"
            :is-owner="isOwner"
            :is-super-admin="isSuperAdmin"
            @update:selected-payment="totalSelectedPayment = $event"
            @update:selected-entries="selectedEntries = $event"
          />
        </div>

        <!-- Paychecks View -->
        <div v-else-if="showPaychecks">
          <PaycheckDetails
            :member="selectedMember"
            :date-range="selectedDateRange"
            :board-id="boardIdLocal"
            :is-owner="isOwner"
            :is-super-admin="isSuperAdmin"
          />
        </div>

        <!-- Payment Summary -->
        <div v-if="!selectedMember" class="payment-summary">
          <h3>Payment Summary</h3>
          <div class="summary-stats">
            <div class="summary-stat">
              <span>Total Paid</span>
              <span class="text-success">${{ totalPaid.toFixed(2) }}</span>
            </div>
            <div class="summary-stat">
              <span>Total Pending</span>
              <span class="text-danger">${{ totalPending.toFixed(2) }}</span>
            </div>
            <div class="summary-stat">
              <span>Paid Hours</span>
              <span class="text-success">{{ totalPaidHours.toFixed(2) }} hrs</span>
            </div>
            <div class="summary-stat">
              <span>Unpaid Hours</span>
              <span class="text-danger">{{ totalUnpaidHours.toFixed(2) }} hrs</span>
            </div>
          </div>

          <div class="progress-section mt-4">
            <VProgressLinear
              :model-value="paymentProgress"
              color="success"
              height="8"
              rounded
            />
            <span class="progress-label">{{ paymentProgress.toFixed(1) }}% Paid</span>
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
.payment-details-dialog {
  .github-card {
    background: #f6f8fa;
    border: none;
    height: 100vh;
    display: flex;
    flex-direction: column;
  }

  .header-section {
    background: #ffffff;
    padding: 1.5rem;
    border-bottom: 1px solid #d0d7de;
    position: sticky;
    top: 0;
    z-index: 10;

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 1rem;
      border-bottom: 1px solid #d0d7de;
      margin-bottom: 1rem;

      .close-btn {
        color: #57606a;
        transition: all 0.2s ease;

        &:hover {
          color: #24292f;
          background: #f3f4f6;
        }
      }
    }

    .title-section {
      .header-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #24292f;
        margin: 0;
        line-height: 1.4;
      }

      .subtitle {
        display: flex;
        align-items: center;
        font-size: 0.875rem;
        color: #0969da;
        margin-top: 0.25rem;
        
        .v-icon {
          opacity: 0.8;
        }
      }
    }

    .github-btn {
      border-color: #d0d7de;
      color: #24292f;
      font-size: 0.875rem;
      height: 32px;
      
      &:hover {
        border-color: #0969da;
        background: #f3f4f6;
      }
    }
  }

  .content-section {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
  }

  .members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1rem;
  }

  .member-card {
    background: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    padding: 1.25rem;
    position: relative;
    transition: all 0.2s ease;

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

      &.not-worked {
        background: #f6f8fa;
        color: #57606a;
      }
    }

    .member-actions {
      display: flex;
      gap: 0.5rem;
      justify-content: flex-end;
      flex-wrap: wrap;

      .actions-wrapper {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        
        .v-btn {
          flex: 0 1 auto;
          min-width: auto;
        }
      }
    }
  }

  .member-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.25rem;

    .member-details {
      h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #24292f;
        margin-bottom: 0.25rem;
      }

      span {
        font-size: 0.875rem;
        color: #57606a;
      }
    }
  }

  .payment-stats {
    background: #f6f8fa;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1.25rem;

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

  .payment-summary {
    background: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    padding: 1.5rem;
    margin-top: 2rem;
    width: 100%;

    h3 {
      font-size: 1.125rem;
      font-weight: 600;
      color: #24292f;
      margin-bottom: 1rem;
    }

    .summary-stats {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
      flex-wrap: nowrap;
    }

    .summary-stat {
      flex: 1;
      background: #f6f8fa;
      padding: 1rem;
      border-radius: 6px;
      border: 1px solid #d0d7de;
      min-width: 150px;

      span:first-child {
        font-size: 0.875rem;
        color: #57606a;
        display: block;
        margin-bottom: 0.25rem;
        white-space: nowrap;
      }

      span:last-child {
        font-size: 1.125rem;
        font-weight: 600;
        white-space: nowrap;
      }
    }
  }

  .selected-payment-info {
    margin-top: 1rem;
    
    .payment-stats {
      display: flex;
      align-items: center;
      gap: 1rem;
      
      .stat-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: #f6f8fa;
        border: 1px solid #d0d7de;
        border-radius: 6px;
        transition: all 0.2s ease;

        &:hover {
          border-color: #0969da;
          background: #f3f4f6;
        }

        .stat-icon {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 36px;
          height: 36px;
          background: #ffffff;
          border-radius: 8px;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-content {
          display: flex;
          flex-direction: column;

          .stat-label {
            font-size: 0.75rem;
            color: #57606a;
            margin-bottom: 0.25rem;
          }

          .stat-value {
            font-size: 1rem;
            font-weight: 600;
            color: #0969da;
          }
        }
      }

      .pay-btn {
        margin-left: auto;
        padding: 0 1.5rem;
        height: 36px;
        font-weight: 600;
        letter-spacing: 0.3px;
        transition: all 0.2s ease;
        
        &:hover {
          transform: translateY(-1px);
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
      }
    }
  }
}

.text-success { color: #1a7f37; }
.text-danger { color: #cf222e; }
.text-primary { color: #0969da; }
</style>

