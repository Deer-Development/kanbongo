<script setup>
import { defineProps, ref, watch, computed, onMounted } from "vue"
import MemberPaymentDetails from "@/views/projects/components/MemberPaymentDetails.vue"
import PaycheckDetails from "@/views/projects/components/PaycheckDetails.vue"
import { useToast } from 'vue-toastification'


const props = defineProps({
  boardId: { type: Number, required: true, default: 0 },
  isOwner: { type: Boolean, required: false, default: false },
  isAdmin: { type: Boolean, required: false, default: false },
  isSuperAdmin: { type: Boolean, required: false, default: false },
  isDialogVisible: { type: Boolean, required: true, default: false },
})

const emit = defineEmits(["update:isDialogVisible", "update:boardDetails", "formSubmitted"])

const boardIdLocal = ref(props.boardId)
const isConfirmDialogVisible = ref(false)
const members = ref([])
const memberToPay = ref(null)
const selectedMember = ref(null)
const showPaychecks = ref(false)
const totalSelectedPayment = ref(0)
const selectedEntries = ref([])
const toast = useToast()

const selectedDateRange = ref("")
const paymentStatusFilter = ref('all')
const datePreset = ref('current_month')

const showMenu = ref(false)

const periodOptions = [
  {
    label: 'Current Period',
    items: [
      { 
        label: 'Current Week',
        value: 'current_week',
        icon: 'tabler-calendar-week',
        description: 'This week\'s data'
      },
      { 
        label: 'Current Month',
        value: 'current_month',
        icon: 'tabler-calendar-month',
        description: 'Current month\'s data'
      },
      {
        label: 'Current Quarter',
        value: 'current_quarter',
        icon: 'tabler-calendar-stats',
        description: 'This quarter\'s data'
      },
      {
        label: 'Current Year',
        value: 'current_year',
        icon: 'tabler-calendar',
        description: 'This year\'s data'
      }
    ]
  },
  {
    label: 'Previous Period',
    items: [
      {
        label: 'Last Week',
        value: 'last_week',
        icon: 'tabler-calendar-week',
        description: 'Previous week\'s data'
      },
      {
        label: 'Last Month',
        value: 'last_month',
        icon: 'tabler-calendar-month',
        description: 'Previous month\'s data'
      },
      {
        label: 'Last Quarter',
        value: 'last_quarter',
        icon: 'tabler-calendar-stats',
        description: 'Previous quarter\'s data'
      },
      {
        label: 'Last Year',
        value: 'last_year',
        icon: 'tabler-calendar',
        description: 'Previous year\'s data'
      }
    ]
  },
  {
    label: 'Custom',
    items: [
      {
        label: 'Custom Range',
        value: 'custom',
        icon: 'tabler-calendar-due',
        description: 'Select a custom date range'
      }
    ]
  }
]

const selectedPeriodLabel = computed(() => {
  const option = periodOptions
    .flatMap(group => group.items)
    .find(item => item.value === datePreset.value)
  
  return option?.label || 'Select Period'
})

const filteredMembers = computed(() => {
  return members.value.filter(member => {
    switch (paymentStatusFilter.value) {
      case 'paid':
        return member.total_paid_hours > 0;
      case 'pending':
        return member.pending_payment > 0;
      default:
        return true;
    }
  });
});

const fetchMemberDetails = async () => {
  const res = await $api(`/board/payment-details/${boardIdLocal.value}`, {
    method: "GET",
    params: { 
      date_range: selectedDateRange.value, 
      payment_status: paymentStatusFilter.value,
      is_super_admin: props.isSuperAdmin, 
      is_owner: props.isOwner, 
      is_admin: props.isAdmin 
    },
  })

  members.value = res.data
}

watch(() => props.boardId, value => {
  boardIdLocal.value = value
  if (boardIdLocal.value !== 0) fetchMemberDetails()
})

watch(() => props.isDialogVisible, value => {
  boardIdLocal.value = props.boardId
  if (value) {
    fetchMemberDetails()
  }
})

watch(selectedDateRange, () => {
  if (boardIdLocal.value !== 0 && !selectedMember.value) fetchMemberDetails()
})

watch(paymentStatusFilter, () => {
  if (boardIdLocal.value !== 0 && !selectedMember.value) fetchMemberDetails()
})

watch(selectedDateRange, () => {
  if (selectedDateRange.value === '') {
    datePreset.value = 'current_month'
  }
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

const totalPaid = computed(() => filteredMembers.value.reduce((sum, member) => sum + member.total_amount_paid, 0))
const totalPaidHours = computed(() => filteredMembers.value.reduce((sum, member) => sum + member.total_paid_hours, 0))
const totalPending = computed(() => filteredMembers.value.reduce((sum, member) => sum + member.pending_payment, 0))
const totalUnpaidHours = computed(() => filteredMembers.value.reduce((sum, member) => sum + member.total_unpaid_hours, 0))

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
  return 'No Payment'
}

// Payment flow states
const paymentStep = ref(1)
const paymentFlowError = ref(null)
const isProcessing = ref(false)
const selectedRecipient = ref(null)
const recipients = ref([])
const showNewRecipientForm = ref(false)

// Payment steps
const STEPS = {
  REVIEW: 1,
  RECIPIENT: 2,
  CONFIRM: 3,
  PROCESSING: 4,
  COMPLETE: 5
}

// Computed properties for payment flow
const canProceedToRecipient = computed(() => {
  return totalSelectedPayment.value > 0 && selectedEntries.value.length > 0
})

const canProceedToConfirm = computed(() => {
  return selectedRecipient.value || 
    (showNewRecipientForm.value && memberToPay.value?.user?.paymentDetails)
})

const recipientDetails = computed(() => {
  if (selectedRecipient.value) {
    return selectedRecipient.value
  }
  
  if (memberToPay.value?.user?.paymentDetails) {
    const details = memberToPay.value.user.paymentDetails
    return {
      accountHolderName: details.account_holder_name,
      email: memberToPay.value.user.email,
      currency: details.currency,
      type: details.account_type
    }
  }
  
  return null
})

// Methods for payment flow
const fetchRecipients = async () => {
  try {
    isProcessing.value = true
    const response = await $api('/wise/recipients')
    recipients.value = response.data.recipients
  } catch (error) {
    toast.error('Failed to fetch recipients')
    paymentFlowError.value = error.response?.data?.message || 'Failed to fetch recipients'
  } finally {
    isProcessing.value = false
  }
}

const confirmPayment = async (member) => {
  try {
    memberToPay.value = member
    paymentStep.value = STEPS.REVIEW
    isConfirmDialogVisible.value = true
    await fetchRecipients()
  } catch (error) {
    toast.error('Failed to initialize payment')
    paymentFlowError.value = error.message
  }
}

const proceedToRecipientSelection = () => {
  if (!canProceedToRecipient.value) {
    toast.error('Please select entries to pay')
    return
  }
  paymentStep.value = STEPS.RECIPIENT
}

const proceedToConfirmation = () => {
  if (!canProceedToConfirm.value) {
    toast.error('Please select or create a recipient')
    return
  }
  paymentStep.value = STEPS.CONFIRM
}

const handlePayment = async () => {
  try {
    paymentStep.value = STEPS.PROCESSING
    isProcessing.value = true
    paymentFlowError.value = null

    const res = await $api(`/container/${boardIdLocal.value}/process-payment/${memberToPay.value}`, {
      method: "POST",
      body: {
        amount: totalSelectedPayment.value,
        currency: recipientDetails.value?.currency || 'USD',
        date_range: selectedDateRange.value,
        selected_entries: selectedEntries.value,
        recipient_id: selectedRecipient.value?.id
      },
    })

    if (res.data) {
      paymentStep.value = STEPS.COMPLETE
      toast.success('Payment processed successfully')
      
      // Update UI after short delay
      setTimeout(() => {
        selectedMember.value = null
        isConfirmDialogVisible.value = false
        fetchMemberDetails()
      }, 2000)
    }
  } catch (error) {
    paymentFlowError.value = error.response?.data?.message || 'Payment processing failed'
    toast.error(paymentFlowError.value)
    paymentStep.value = STEPS.CONFIRM
  } finally {
    isProcessing.value = false
  }
}

const resetPaymentFlow = () => {
  paymentStep.value = STEPS.REVIEW
  paymentFlowError.value = null
  selectedRecipient.value = null
  showNewRecipientForm.value = false
  isProcessing.value = false
}

const closeConfirmDialog = () => {
  isConfirmDialogVisible.value = false
  resetPaymentFlow()
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

const setDatePreset = (preset) => {
  datePreset.value = preset
  showMenu.value = false
  
  if (preset === 'custom') {
    selectedDateRange.value = ''
    return
  }
  
  const now = new Date()
  
  switch (preset) {
    case 'current_week': {
      const start = new Date(now)
      start.setDate(start.getDate() - start.getDay() + 1)
      const end = new Date(start)
      end.setDate(end.getDate() + 6)
      selectedDateRange.value = `${start.toISOString().split('T')[0]} to ${end.toISOString().split('T')[0]}`
      break
    }
    case 'current_month': {
      const start = new Date(now.getFullYear(), now.getMonth(), 1)
      const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
      selectedDateRange.value = `${start.toISOString().split('T')[0]} to ${end.toISOString().split('T')[0]}`
      break
    }
    case 'current_quarter': {
      const quarter = Math.floor(now.getMonth() / 3)
      const start = new Date(now.getFullYear(), quarter * 3, 1)
      const end = new Date(now.getFullYear(), (quarter + 1) * 3, 0)
      selectedDateRange.value = `${start.toISOString().split('T')[0]} to ${end.toISOString().split('T')[0]}`
      break
    }
    case 'current_year': {
      const start = new Date(now.getFullYear(), 0, 1)
      const end = new Date(now.getFullYear(), 11, 31)
      selectedDateRange.value = `${start.toISOString().split('T')[0]} to ${end.toISOString().split('T')[0]}`
      break
    }
    case 'last_week': {
      const start = new Date(now)
      start.setDate(start.getDate() - start.getDay() - 6)
      const end = new Date(start)
      end.setDate(end.getDate() + 6)
      selectedDateRange.value = `${start.toISOString().split('T')[0]} to ${end.toISOString().split('T')[0]}`
      break
    }
    case 'last_month': {
      const start = new Date(now.getFullYear(), now.getMonth() - 1, 1)
      const end = new Date(now.getFullYear(), now.getMonth(), 0)
      selectedDateRange.value = `${start.toISOString().split('T')[0]} to ${end.toISOString().split('T')[0]}`
      break
    }
    case 'last_quarter': {
      const quarter = Math.floor(now.getMonth() / 3)
      const start = new Date(now.getFullYear(), (quarter - 1) * 3, 1)
      const end = new Date(now.getFullYear(), quarter * 3, 0)
      selectedDateRange.value = `${start.toISOString().split('T')[0]} to ${end.toISOString().split('T')[0]}`
      break
    }
    case 'last_year': {
      const start = new Date(now.getFullYear() - 1, 0, 1)
      const end = new Date(now.getFullYear() - 1, 11, 31)
      selectedDateRange.value = `${start.toISOString().split('T')[0]} to ${end.toISOString().split('T')[0]}`
      break
    }
  }
}

const showDateMenu = ref(false)

const datePresets = [
  { label: 'This Week', value: 'this-week', icon: 'tabler-calendar-event' },
  { label: 'Last Week', value: 'last-week', icon: 'tabler-calendar-minus' },
  { label: 'This Month', value: 'this-month', icon: 'tabler-calendar-month' },
  { label: 'This Year', value: 'this-year', icon: 'tabler-calendar-stats' },
]

const getDateRangeLabel = computed(() => {
  if (!selectedDateRange.value) return 'Select date range'
  
  const preset = datePresets.find(p => p.value === datePreset.value)
  if (preset) return preset.label
  
  const [start, end] = selectedDateRange.value.split(' to ')
  if (!end) return 'Custom range'
  
  return `${new Date(start).toLocaleDateString()} - ${new Date(end).toLocaleDateString()}`
})

const showCustomDateMenu = ref(false)

const getCustomDateLabel = computed(() => {
  if (!selectedDateRange.value) return 'Custom'
  const [start, end] = selectedDateRange.value.split(' to ')
  if (!end) return 'Custom'
  return `${new Date(start).toLocaleDateString()} - ${new Date(end).toLocaleDateString()}`
})

onMounted(() => {
  setDatePreset('current_month')
})
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

        <div class="filters-section" v-if="!showPaychecks">
          <div class="date-filters">
            <div class="filters-group">
              <div class="period-selector">
                <div class="period-controls">
                  <VMenu
                    v-model="showMenu"
                    :close-on-content-click="false"
                    location="bottom start"
                    offset="4"
                  >
                    <template #activator="{ props: menuProps }">
                      <VBtn
                        variant="outlined"
                        v-bind="menuProps"
                      >
                        <VIcon
                          :icon="periodOptions
                            .flatMap(group => group.items)
                            .find(item => item.value === datePreset)?.icon || 'tabler-calendar'"
                          size="20"
                          class="mr-2"
                        />
                        {{ selectedPeriodLabel }}
                        <VIcon
                          size="20"
                          icon="tabler-chevron-down"
                          class="ml-1"
                        />
                      </VBtn>
                    </template>

                    <VCard class="period-menu" elevation="3" min-width="280">
                      <VList lines="two" density="compact">
                        <template v-for="(group, index) in periodOptions" :key="index">
                          <VListSubheader class="period-group-header">
                            {{ group.label }}
                          </VListSubheader>

                          <VListItem
                            v-for="item in group.items"
                            :key="item.value"
                            :value="item.value"
                            class="period-option"
                            :active="datePreset === item.value"
                            @click="setDatePreset(item.value)"
                          >
                            <template #prepend>
                              <VIcon :icon="item.icon" size="20" />
                            </template>

                            <VListItemTitle>{{ item.label }}</VListItemTitle>
                            <VListItemSubtitle>{{ item.description }}</VListItemSubtitle>
                          </VListItem>

                          <VDivider v-if="index < periodOptions.length - 1" />
                        </template>
                      </VList>
                    </VCard>
                  </VMenu>

                  <div 
                    v-if="datePreset === 'custom'" 
                    class="custom-date-wrapper"
                  >
                    <AppDateTimePicker
                      v-model="selectedDateRange"
                      :config="{ mode: 'range' }"
                      placeholder="Select custom date range"
                      clearable
                      class="date-picker"
                    />
                  </div>
                </div>
              </div>

              <VDivider vertical class="mx-2" />

              <VBtnGroup 
                class="btn-group-status-filter"
                density="compact"
              >
                <VBtn
                  :color="paymentStatusFilter === 'all' ? 'primary' : undefined"
                  :variant="paymentStatusFilter === 'all' ? 'tonal' : 'outlined'"
                  size="x-small"
                  class="filter-btn"
                  @click="paymentStatusFilter = 'all'"
                >
                  <VIcon 
                    size="14" 
                    class="mr-1"
                    :color="paymentStatusFilter === 'all' ? undefined : '#57606a'"
                  >
                    tabler-filter
                  </VIcon>
                  All
                </VBtn>
                <VBtn
                  :color="paymentStatusFilter === 'paid' ? 'success' : undefined"
                  :variant="paymentStatusFilter === 'paid' ? 'tonal' : 'outlined'"
                  size="x-small"
                  class="filter-btn"
                  @click="paymentStatusFilter = 'paid'"
                >
                  <VIcon 
                    size="14" 
                    class="mr-1"
                    :color="paymentStatusFilter === 'paid' ? undefined : '#57606a'"
                  >
                    tabler-cash
                  </VIcon>
                  Paid
                </VBtn>
                <VBtn
                  :color="paymentStatusFilter === 'pending' ? 'warning' : undefined"
                  :variant="paymentStatusFilter === 'pending' ? 'tonal' : 'outlined'"
                  size="x-small"
                  class="filter-btn"
                  @click="paymentStatusFilter = 'pending'"
                >
                  <VIcon 
                    size="14" 
                    class="mr-1"
                    :color="paymentStatusFilter === 'pending' ? undefined : '#57606a'"
                  >
                    tabler-clock-dollar
                  </VIcon>
                  Pending
                </VBtn>
              </VBtnGroup>
            </div>
          </div>
        </div>

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
          <div v-if="filteredMembers.length === 0" class="no-results">
            <div class="empty-state">
              <VIcon
                size="40"
                color="#57606a"
                class="mb-4"
              >
                tabler-mood-empty
              </VIcon>
              <h3>No matching results</h3>
              <p class="text-medium-emphasis">
                {{ 
                  paymentStatusFilter === 'paid' 
                    ? 'No paid time entries found in the selected period.' 
                    : paymentStatusFilter === 'pending' 
                      ? 'No pending payments found in the selected period.'
                      : 'No time entries found in the selected period.'
                }}
              </p>
            </div>
          </div>
          
          <div
            v-for="member in filteredMembers"
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
                  v-if="(isSuperAdmin || isOwner || isAdmin) && member.pending_payment > 0"
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
            :payment-status="paymentStatusFilter"
            :board-id="boardIdLocal"
            :is-owner="isOwner"
            :is-admin="isAdmin"
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
            :is-admin="isAdmin"
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
      :cancel-title="paymentStep === STEPS.COMPLETE ? 'Close' : 'Cancel'"
      :confirm-title="
        paymentStep === STEPS.REVIEW ? 'Continue' :
        paymentStep === STEPS.RECIPIENT ? 'Continue' :
        paymentStep === STEPS.CONFIRM ? 'Process Payment' :
        paymentStep === STEPS.PROCESSING ? 'Processing...' :
        'Done'
      "
      :confirmation-question="
        paymentStep === STEPS.REVIEW ? 'Review Payment Details' :
        paymentStep === STEPS.RECIPIENT ? 'Select Payment Recipient' :
        paymentStep === STEPS.CONFIRM ? 'Confirm Payment Details' :
        paymentStep === STEPS.PROCESSING ? 'Processing Payment...' :
        'Payment Complete'
      "
      @confirm="
        paymentStep === STEPS.REVIEW ? proceedToRecipientSelection() :
        paymentStep === STEPS.RECIPIENT ? proceedToConfirmation() :
        paymentStep === STEPS.CONFIRM ? handlePayment() :
        paymentStep === STEPS.COMPLETE ? closeConfirmDialog() : null
      "
      @close="closeConfirmDialog"
    >
      <template #default>
        <!-- Error Alert -->
        <VAlert
          v-if="paymentFlowError"
          type="error"
          variant="tonal"
          class="mb-4"
          closable
          @click:close="paymentFlowError = null"
        >
          {{ paymentFlowError }}
        </VAlert>

        <!-- Step 1: Review -->
        <div v-if="paymentStep === STEPS.REVIEW">
          <div class="payment-review mb-4">
            <h3 class="text-h6 mb-2">Payment Review</h3>
            
            <div class="payment-details">
              <div class="detail-row">
                <span class="label">Recipient Name:</span>
                <span class="value">{{ memberToPay?.user?.full_name }}</span>
              </div>
              
              <div class="detail-row">
                <span class="label">Amount:</span>
                <span class="value">${{ totalSelectedPayment.toFixed(2) }}</span>
              </div>
              
              <div class="detail-row">
                <span class="label">Selected Entries:</span>
                <span class="value">{{ selectedEntries.length }} entries</span>
              </div>
              
              <div class="detail-row">
                <span class="label">Date Range:</span>
                <span class="value">{{ selectedDateRange }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Recipient Selection -->
        <div v-else-if="paymentStep === STEPS.RECIPIENT">
          <div class="recipient-selection mb-4">
            <h3 class="text-h6 mb-2">Select Payment Recipient</h3>
            
            <div v-if="isProcessing" class="d-flex justify-center py-4">
              <VProgressCircular indeterminate />
            </div>
            
            <template v-else>
              <VSelect
                v-model="selectedRecipient"
                :items="recipients"
                item-title="accountHolderName"
                item-value="id"
                label="Select existing recipient"
                class="mb-2"
                :loading="!recipients.length"
                :disabled="showNewRecipientForm"
              >
                <template #item="{ props, item }">
                  <VListItem v-bind="props">
                    <template #prepend>
                      <VIcon 
                        size="20"
                        color="primary"
                        class="me-2"
                      >
                        tabler-user-circle
                      </VIcon>
                    </template>
                    
                    <VListItemTitle>
                      {{ item.raw.accountHolderName }}
                    </VListItemTitle>
                    
                    <VListItemSubtitle>
                      {{ item.raw.currency }} • {{ item.raw.type }}
                    </VListItemSubtitle>
                  </VListItem>
                </template>
              </VSelect>

              <div class="d-flex align-center gap-2 mt-4">
                <VDivider />
                <span class="text-medium-emphasis text-sm">or</span>
                <VDivider />
              </div>

              <VBtn
                variant="tonal"
                color="primary"
                size="small"
                prepend-icon="tabler-plus"
                class="mt-4"
                :disabled="!memberToPay?.user?.paymentDetails"
                @click="showNewRecipientForm = !showNewRecipientForm"
              >
                {{ showNewRecipientForm ? 'Cancel' : 'Create New Recipient' }}
              </VBtn>

              <div 
                v-if="showNewRecipientForm" 
                class="new-recipient-form mt-4"
              >
                <div class="payment-details-preview">
                  <h4 class="text-subtitle-1 font-weight-medium mb-2">
                    Payment Details Preview
                  </h4>
                  
                  <template v-if="memberToPay?.user?.paymentDetails">
                    <div class="detail-row">
                      <span class="label">Account Holder:</span>
                      <span class="value">{{ memberToPay.user.paymentDetails.account_holder_name }}</span>
                    </div>
                    
                    <div class="detail-row">
                      <span class="label">Account Type:</span>
                      <span class="value">{{ memberToPay.user.paymentDetails.account_type }}</span>
                    </div>
                    
                    <div class="detail-row">
                      <span class="label">Currency:</span>
                      <span class="value">{{ memberToPay.user.paymentDetails.currency }}</span>
                    </div>
                  </template>
                  
                  <VAlert
                    v-else
                    type="warning"
                    variant="tonal"
                    class="mt-2"
                  >
                    User has not provided payment details yet.
                  </VAlert>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- Step 3: Confirmation -->
        <div v-else-if="paymentStep === STEPS.CONFIRM">
          <div class="payment-confirmation">
            <h3 class="text-h6 mb-4">Confirm Payment Details</h3>
            
            <div class="confirmation-details">
              <div class="detail-section">
                <h4 class="text-subtitle-1 font-weight-medium mb-2">
                  Recipient Information
                </h4>
                
                <div class="detail-row">
                  <span class="label">Name:</span>
                  <span class="value">{{ recipientDetails?.accountHolderName }}</span>
                </div>
                
                <div class="detail-row">
                  <span class="label">Email:</span>
                  <span class="value">{{ recipientDetails?.email }}</span>
                </div>
                
                <div class="detail-row">
                  <span class="label">Currency:</span>
                  <span class="value">{{ recipientDetails?.currency }}</span>
                </div>
                
                <div class="detail-row">
                  <span class="label">Account Type:</span>
                  <span class="value">{{ recipientDetails?.type }}</span>
                </div>
              </div>

              <VDivider class="my-4" />

              <div class="detail-section">
                <h4 class="text-subtitle-1 font-weight-medium mb-2">
                  Payment Information
                </h4>
                
                <div class="detail-row">
                  <span class="label">Amount:</span>
                  <span class="value">${{ totalSelectedPayment.toFixed(2) }}</span>
                </div>
                
                <div class="detail-row">
                  <span class="label">Entries:</span>
                  <span class="value">{{ selectedEntries.length }} selected</span>
                </div>
                
                <div class="detail-row">
                  <span class="label">Period:</span>
                  <span class="value">{{ selectedDateRange }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 4: Processing -->
        <div v-else-if="paymentStep === STEPS.PROCESSING">
          <div class="processing-state text-center py-4">
            <VProgressCircular
              indeterminate
              size="64"
              color="primary"
              class="mb-4"
            />
            <h3 class="text-h6">Processing Payment</h3>
            <p class="text-medium-emphasis">
              Please wait while we process your payment...
            </p>
          </div>
        </div>

        <!-- Step 5: Complete -->
        <div v-else-if="paymentStep === STEPS.COMPLETE">
          <div class="completion-state text-center py-4">
            <VIcon
              size="64"
              color="success"
              class="mb-4"
            >
              tabler-circle-check
            </VIcon>
            <h3 class="text-h6 text-success">Payment Successful</h3>
            <p class="text-medium-emphasis">
              The payment has been processed successfully.
            </p>
          </div>
        </div>
      </template>
    </ConfirmDialog>
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
    padding: 0.5rem !important;
  }

  .members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1rem;
    min-height: 200px;

    .no-results {
      grid-column: 1 / -1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 3rem 1rem;

      .empty-state {
        text-align: center;
        background: #ffffff;
        border: 1px solid #d0d7de;
        border-radius: 6px;
        padding: 2rem;
        max-width: 400px;
        width: 100%;

        h3 {
          font-size: 1rem;
          font-weight: 600;
          color: #24292f;
          margin-bottom: 0.5rem;
        }

        p {
          font-size: 0.875rem;
          color: #57606a;
          margin: 0;
        }
      }
    }
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
      display: grid;
      gap: 0.75rem;
      margin-bottom: 1.5rem;
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

  .selected-payment-info {
    margin-top: 0.5rem;
    
    @media (max-width: 768px) {
      margin: 0.5rem -1rem 0;
      padding: 0.5rem;
      background: #f6f8fa;
      border-top: 1px solid #d0d7de;
      border-bottom: 1px solid #d0d7de;
    }
    
    .payment-stats {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      flex-wrap: wrap;
      background: #f6f8fa;
      border: 1px solid #d0d7de;
      border-radius: 6px;
      padding: 0.5rem;
      
      .stat-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.75rem;
        background: #ffffff;
        border: 1px solid #d0d7de;
        border-radius: 6px;
        flex: 1;
        min-width: 200px;
        max-width: 250px;
        height: 40px;
        transition: all 0.2s ease;

        &:hover {
          border-color: #0969da;
          background: #f8fafc;
        }

        .stat-icon {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 28px;
          height: 28px;
          background: #f6f8fa;
          border-radius: 6px;
          
          .v-icon {
            font-size: 16px;
          }
        }

        .stat-content {
          display: flex;
          flex-direction: row;
          align-items: center;
          justify-content: space-between;
          flex: 1;

          .stat-label {
            font-size: 0.75rem;
            color: #57606a;
            margin: 0;
          }

          .stat-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: #0969da;
          }
        }
      }

      .pay-btn {
        margin-left: auto;
        padding: 0 1rem;
        height: 40px;
        font-weight: 600;
        font-size: 0.875rem;
        letter-spacing: 0.3px;
        min-width: 120px;
        transition: all 0.2s ease;
        
        .v-btn__content {
          gap: 0.375rem;
        }
        
        .v-icon {
          font-size: 18px;
        }
        
        &:hover {
          transform: translateY(-1px);
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
      }
      
      @media (max-width: 768px) {
        flex-direction: row;
        justify-content: space-between;
        width: 100%;
        
        .stat-item {
          flex: 1;
          min-width: calc(50% - 0.25rem);
          padding: 0.375rem 0.5rem;
          background: #ffffff;
          
          .stat-icon {
            width: 24px;
            height: 24px;
            
            .v-icon {
              font-size: 14px;
            }
          }

          .stat-content {
            flex: 1;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.125rem;

            .stat-label {
              margin: 0;
              font-size: 0.675rem;
              line-height: 1;
            }

            .stat-value {
              font-size: 0.75rem;
              line-height: 1;
            }
          }
        }

        .pay-btn {
          width: 100%;
          margin: 0;
          height: 32px;
          padding: 0 1rem;
          font-size: 0.75rem;
          
          .v-icon {
            font-size: 16px;
          }
          
          .v-btn__content {
            font-size: 0.75rem;
            gap: 0.25rem;
          }
        }
      }
    }
  }

  .filters-section {
    .date-filters {
      display: flex;
      align-items: center;
      gap: 1rem;
      
      .filters-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
        min-width: 0;
        
        .period-selector {
          flex: 1;
          min-width: 0;
          
          .period-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            
            .period-btn {
              height: 24px;
              font-size: 0.75rem;
              text-transform: none;
              letter-spacing: normal;
              white-space: nowrap;
              
              &:hover {
                background: #f3f4f6;
              }
            }
          }
        }
        
        .custom-date-wrapper {
          flex: 1;
          min-width: 200px;
          max-width: 300px;
          
          .date-picker {
            :deep(.v-field) {
              border-radius: 6px;
              border: 1px solid #d0d7de;
              background: #ffffff;
              height: 24px;
              min-height: 24px;
              font-size: 0.75rem;
              
              &:hover {
                border-color: #0969da;
              }
              
              &.v-field--focused {
                border-color: #0969da;
                box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.1);
              }

              .v-field__input {
                min-height: 24px;
                padding-top: 0;
                padding-bottom: 0;
              }
            }
          }
        }
      }
    }
  }
}

.text-success { color: #1a7f37; }
.text-danger { color: #cf222e; }
.text-primary { color: #0969da; }

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.date-menu-card {
  border: 1px solid #d0d7de;
  box-shadow: 0 8px 24px rgba(140,149,159,0.2);
  
  .dropdown-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    padding: 8px 12px;
    border-bottom: 1px solid #d0d7de;
    background: #f6f8fa;
    
    .header-icon {
      color: inherit;
      opacity: 0.8;
    }
  }

  .date-presets-list {
    padding: 4px 0;

    :deep(.v-list-item) {
      min-height: 32px;
      padding: 4px 12px;
      cursor: pointer;
      
      &:hover {
        background: #f6f8fa;
      }
      
      &.v-list-item--active {
        background: #f0f3f9;
        color: #0969da;
      }

      .v-list-item__prepend {
        margin-right: 8px;
      }
    }
  }

  .date-picker {
    :deep(.v-field) {
      border-radius: 6px;
      border: 1px solid #d0d7de;
      background: #ffffff;
      height: 32px;
      
      &:hover {
        border-color: #0969da;
      }
      
      &.v-field--focused {
        border-color: #0969da;
        box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.1);
      }
    }
  }
}

.btn-group-status-filter {
  max-width: 240px;
}

.date-filter-btn {
  height: 24px;
  min-width: 160px;
  font-size: 0.75rem;
  border-color: #d0d7de;
  text-transform: none;
  letter-spacing: normal;
  
  &:hover {
    border-color: #0969da;
    background: #f3f4f6;
  }
  
  .v-icon {
    color: #57606a;
  }
  
  &.v-btn--active {
    .v-icon {
      color: inherit;
    }
  }
}

.custom-date-card {
  padding: 0.75rem;
  border: 1px solid #d0d7de;
  box-shadow: 0 8px 24px rgba(140,149,159,0.2);
  
  .date-picker {
    :deep(.v-field) {
      border-radius: 6px;
      border: 1px solid #d0d7de;
      background: #ffffff;
      height: 32px;
      
      &:hover {
        border-color: #0969da;
      }
      
      &.v-field--focused {
        border-color: #0969da;
        box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.1);
      }
    }
  }
}

@media (max-width: 768px) {
  .filters-section {
    .date-filters {
      flex-direction: column;
      align-items: stretch;
      gap: 0.75rem;
      
      .filters-group {
        flex-direction: column;
        
        .v-divider {
          display: none;
        }
        
        .period-selector {
          justify-content: flex-start;
          width: 100%;
          
          .period-controls {
            flex-direction: column;
            
            .period-btn {
              flex: 1;
            }
          }
        }
        
        .custom-date-wrapper {
          width: 100%;
          max-width: none;
        }
      }
      
      .btn-group-status-filter {
        width: 100%;
        
        .v-btn {
          flex: 1;
        }
      }
    }
  }
}

.period-menu {
  .period-group-header {
    font-size: 0.75rem;
    font-weight: 600;
    color: #57606a;
    background: #f6f8fa;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }
  
  .period-option {
    min-height: 56px;
    padding: 8px 16px;
    
    &:hover {
      background: #f6f8fa;
    }
    
    &.v-list-item--active {
      background: #f0f9ff;
      color: #0969da;
      
      .v-list-item-subtitle {
        color: #57606a;
      }
    }
    
    .v-list-item-title {
      font-size: 0.875rem;
      font-weight: 500;
    }
    
    .v-list-item-subtitle {
      font-size: 0.75rem;
      color: #57606a;
      margin-top: 2px;
    }
  }
}

.period-selector {
  .period-controls {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    
    .period-btn {
      height: 24px;
      font-size: 0.75rem;
      font-weight: 500;
      color: #24292f;
      background: #f6f8fa;
      border: 1px solid #d0d7de;
      letter-spacing: normal;
      text-transform: none;
      
      &:hover {
        background: #f3f4f6;
        border-color: #0969da;
      }
    }
  }
}

.payment-review,
.recipient-selection,
.payment-confirmation {
  background: #f6f8fa;
  border: 1px solid #d0d7de;
  border-radius: 6px;
  padding: 1rem;

  h3 {
    color: #24292f;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
  }
}

.payment-details,
.confirmation-details {
  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #d0d7de;

    &:last-child {
      border-bottom: none;
    }

    .label {
      color: #57606a;
      font-size: 0.875rem;
    }

    .value {
      color: #24292f;
      font-weight: 500;
      font-size: 0.875rem;
    }
  }
}

.new-recipient-form {
  background: #ffffff;
  border: 1px solid #d0d7de;
  border-radius: 6px;
  padding: 1rem;

  .payment-details-preview {
    h4 {
      color: #24292f;
      font-size: 0.875rem;
      margin-bottom: 0.75rem;
    }
  }
}

.detail-section {
  h4 {
    color: #24292f;
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
  }
}

.processing-state,
.completion-state {
  h3 {
    margin-bottom: 0.5rem;
  }

  p {
    font-size: 0.875rem;
    color: #57606a;
  }
}
</style>

