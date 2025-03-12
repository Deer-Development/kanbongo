<template>
  <VDialog
    v-model="isVisible"
    :fullscreen="$vuetify.display.mobile"
    class="wise-payment-dialog"
    max-width="800"
  >
    <VCard class="wise-payment-card">
      <!-- Header -->
      <VCardItem class="header-section">
        <template #prepend>
          <VIcon color="primary" size="24">tabler-cash</VIcon>
        </template>
        <VCardTitle>Process Payment</VCardTitle>
        <template #append>
          <VBtn
            icon
            variant="text"
            class="close-btn"
            @click="close"
          >
            <VIcon>tabler-x</VIcon>
          </VBtn>
        </template>
      </VCardItem>
      
      <!-- Stepper -->
      <div class="stepper-container px-4">
        <div class="stepper-wrapper">
          <div
            v-for="(step, index) in stepperItems"
            :key="step.value"
            class="stepper-item"
            :class="{
              'active': currentStep === step.value,
              'completed': currentStep > step.value,
              'disabled': !canAccessStep(step.value)
            }"
          >
            <div class="step-counter">
              <VIcon v-if="currentStep > step.value" size="16">tabler-check</VIcon>
              <span v-else>{{ index + 1 }}</span>
            </div>
            <div class="step-name" :title="step.title">
              {{ step.title }}
              <span class="step-emoji">{{ step.emoji }}</span>
            </div>
          </div>
        </div>
      </div>

      <VDivider />

      <VCardText class="payment-content">
        <!-- Payment Method Selection (Step 1) -->
        <div v-if="currentStep === STEPS.REVIEW" class="payment-methods">
          <div class="text-subtitle-1 mb-4">Choose Payment Method</div>
          
          <div class="payment-options">
            <VCard
              :class="['payment-option', { selected: selectedMethod === 'wise' }]"
              @click="selectPaymentMethod('wise')"
              elevation="0"
              variant="flat"
            >
              <VCardItem>
                <VCardTitle>
                  <div class="d-flex align-center gap-2">
                    <VIcon color="primary" size="24">tabler-brand-wise</VIcon>
                    Wise Transfer
                  </div>
                </VCardTitle>
                <VCardText>
                  Process payment through Wise platform 🏦
                </VCardText>
              </VCardItem>
              <VCardActions v-if="selectedMethod === 'wise'" class="pa-4 pt-0">
                <VBtn
                  color="primary"
                  block
                  @click="handleNext"
                  prepend-icon="tabler-arrow-right"
                >
                  Continue to Wise Transfer
                </VBtn>
              </VCardActions>
            </VCard>

            <VCard
              :class="['payment-option', { selected: selectedMethod === 'manual' }]"
              @click="selectPaymentMethod('manual')"
              elevation="0"
              variant="flat"
            >
              <VCardItem>
                <VCardTitle>
                  <div class="d-flex align-center gap-2">
                    <VIcon color="success" size="24">tabler-check-circle</VIcon>
                    Mark as Paid
                  </div>
                </VCardTitle>
                <VCardText>
                  Record manual payment ✅
                </VCardText>
              </VCardItem>
              <VCardActions v-if="selectedMethod === 'manual'" class="pa-4 pt-0">
                <VBtn
                  color="success"
                  block
                  :loading="isProcessing"
                  @click="processManualPayment"
                  prepend-icon="tabler-check"
                >
                  Confirm Manual Payment
                </VBtn>
              </VCardActions>
            </VCard>
          </div>
        </div>

        <!-- Other Steps (Only for Wise) -->
        <component 
          v-else
          :is="currentStepComponent"
          :payment-details="paymentDetails"
          :recipients="recipients"
          :selected-recipient="selectedRecipient"
          :is-processing="isProcessing"
          @back="handleBack"
          @next="handleNext"
          @select-recipient="selectRecipient"
          @process-payment="processWisePayment"
          @close="close"
        />
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useToast } from 'vue-toastification'
import ReviewStep from './payment-steps/ReviewStep.vue'
import RecipientStep from './payment-steps/RecipientStep.vue'
import ConfirmStep from './payment-steps/ConfirmStep.vue'
import ProcessingStep from './payment-steps/ProcessingStep.vue'
import CompleteStep from './payment-steps/CompleteStep.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  paymentDetails: {
    type: Object,
    required: true,
    validator: (value) => {
      return value.boardId && 
             value.amount && 
             value.dateRange && 
             Array.isArray(value.selectedEntries)
    }
  }
})

const emit = defineEmits(['update:modelValue', 'payment-complete', 'close'])

const STEPS = {
  REVIEW: 1,
  RECIPIENT: 2,
  CONFIRM: 3,
  PROCESSING: 4,
  COMPLETE: 5
}

const isVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const currentStep = ref(STEPS.REVIEW)
const recipients = ref([])
const selectedRecipient = ref(null)
const isProcessing = ref(false)
const toast = useToast()
const selectedMethod = ref(null)

const stepperItems = computed(() => {
  const baseSteps = [
    { 
      title: 'Method',
      value: STEPS.REVIEW, 
      icon: 'tabler-credit-card',
      emoji: '💳'
    }
  ]
  
  return selectedMethod.value === 'wise'
    ? [
        ...baseSteps,
        { 
          title: 'Recipient',
          value: STEPS.RECIPIENT,
          icon: 'tabler-user',
          emoji: '👤'
        },
        { 
          title: 'Confirm',
          value: STEPS.CONFIRM,
          icon: 'tabler-check',
          emoji: '✅'
        }
      ]
    : [
        ...baseSteps,
        { 
          title: 'Confirm',
          value: STEPS.CONFIRM,
          icon: 'tabler-check',
          emoji: '✅'
        }
      ]
})

const currentStepComponent = computed(() => {
  switch (currentStep.value) {
    case STEPS.REVIEW: return ReviewStep
    case STEPS.RECIPIENT: return RecipientStep
    case STEPS.CONFIRM: return ConfirmStep
    case STEPS.PROCESSING: return ProcessingStep
    case STEPS.COMPLETE: return CompleteStep
    default: return ReviewStep
  }
})

// Remove fetchRecipients from watch
watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    currentStep.value = STEPS.REVIEW
    selectedRecipient.value = null
    recipients.value = []
    selectedMethod.value = null
  }
})

// Add watch for currentStep
watch(() => currentStep.value, async (newStep) => {
  if (newStep === STEPS.RECIPIENT && recipients.value.length === 0) {
    await fetchRecipients()
  }
})

const fetchRecipients = async () => {
  try {
    isProcessing.value = true
    const response = await $api('/wise/recipients')
    recipients.value = response.recipients?.content || []
  } catch (error) {
    toast.error('Failed to fetch recipients')
    console.error('Error fetching recipients:', error)
    // If fetching fails, go back to previous step
    currentStep.value = STEPS.REVIEW
  } finally {
    isProcessing.value = false
  }
}

const selectRecipient = (recipient) => {
  selectedRecipient.value = recipient
}

const handleBack = () => {
  if (selectedMethod.value === 'wise') {
    if (currentStep.value > STEPS.REVIEW) {
      currentStep.value--
    }
  }
}

const visitedSteps = ref(new Set([STEPS.REVIEW]))
const canAccessStep = (step) => {
  // First step is always accessible
  if (step === STEPS.REVIEW) return true
  
  // Can only access steps that have been visited or are next in sequence
  return visitedSteps.value.has(step) || 
         (Math.max(...Array.from(visitedSteps.value)) === step - 1 && canProceedToNextStep.value)
}

const canProceedToNextStep = computed(() => {
  switch (currentStep.value) {
    case STEPS.REVIEW:
      return selectedMethod.value === 'wise'
    case STEPS.RECIPIENT:
      return selectedRecipient.value !== null
    case STEPS.CONFIRM:
      return true // Add any validation needed for confirmation
    default:
      return false
  }
})

const handleNext = () => {
  if (selectedMethod.value === 'wise' && canProceedToNextStep.value) {
    if (currentStep.value < STEPS.COMPLETE) {
      currentStep.value++
      visitedSteps.value.add(currentStep.value)
    }
  }
}

const selectPaymentMethod = (method) => {
  selectedMethod.value = method
}

const processWisePayment = async () => {
  try {
    currentStep.value = STEPS.PROCESSING
    isProcessing.value = true

    const res = await $api(`/container/${props.paymentDetails.boardId}/process-payment/${props.paymentDetails.userId}`, {
      method: "POST",
      body: {
        amount: props.paymentDetails.amount,
        currency: selectedRecipient.value.currency,
        date_range: props.paymentDetails.dateRange,
        selected_entries: props.paymentDetails.selectedEntries,
        recipient_id: selectedRecipient.value.id
      },
    })

    if (res.data) {
      currentStep.value = STEPS.COMPLETE
      toast.success('Payment processed successfully')
    }
  } catch (error) {
    toast.error(error.response?.data?.message || 'Payment processing failed')
    currentStep.value = STEPS.CONFIRM
  } finally {
    isProcessing.value = false
  }
}

const processManualPayment = async () => {
  try {
    isProcessing.value = true

    const res = await $api(`/container/${props.paymentDetails.boardId}/mark-as-paid/${props.paymentDetails.userId}`, {
      method: "POST",
      body: {
        amount: props.paymentDetails.amount,
        date_range: props.paymentDetails.dateRange,
        selected_entries: props.paymentDetails.selectedEntries,
      },
    })

    if (res.data) {
      toast.success('Entries marked as paid successfully')
      emit('payment-complete')
      close()
    }
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to mark entries as paid')
  } finally {
    isProcessing.value = false
  }
}

const close = () => {
  emit('close')
  isVisible.value = false
  currentStep.value = STEPS.REVIEW
  selectedRecipient.value = null
  selectedMethod.value = null
}
</script>

<style lang="scss" scoped>
.wise-payment-dialog {
  .wise-payment-card {
    border-radius: 12px;
    overflow: hidden;
  }

  .header-section {
    background: #ffffff;
    border-bottom: 1px solid #d0d7de;
  }

  .stepper-container {
    padding: 1.5rem 0;
    background: #f6f8fa;
  }

  .stepper-wrapper {
    display: flex;
    justify-content: center;
    margin: 0 auto;
    max-width: 600px;
    gap: 3.5rem;
  }

  .stepper-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 0;
    min-width: 100px;
    max-width: 100px;

    &.disabled {
      opacity: 0.5;
      cursor: not-allowed;

      .step-counter {
        background: #d0d7de;
      }
    }

    &.completed {
      .step-counter {
        background: #2da44e;
        color: white;
        border-color: #2da44e;
      }
    }

    &.active {
      .step-counter {
        background: #0969da;
        color: white;
        border-color: #0969da;
        transform: scale(1.1);
      }

      .step-name {
        color: #0969da;
        font-weight: 600;
      }
    }
  }

  .step-counter {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid #d0d7de;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
  }

  .step-name {
    font-size: 0.875rem;
    color: #57606a;
    text-align: center;
    white-space: nowrap;
    overflow: visible;
    width: 100%;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    line-height: 1.2;
  }

  .step-emoji {
    font-size: 1rem;
    line-height: 1;
    opacity: 0.8;
    margin-left: 2px;
  }

  .stepper-wrapper::before,
  .stepper-wrapper::after,
  .step-line {
    display: none;
  }

  .payment-content {
    background: #ffffff;
    min-height: 400px;
    padding: 1.5rem;
  }

  .payment-methods {
    max-width: 600px;
    margin: 0 auto;
  }

  .payment-options {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }

  .payment-option {
    cursor: pointer;
    border: 1px solid #d0d7de;
    transition: all 0.2s ease;
    border-radius: 6px;
    
    &:hover {
      border-color: #0969da;
      background: #f6f8fa;
    }
    
    &.selected {
      border-color: #0969da;
      background: #f6f8fa;
      box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.1);
    }

    .v-card-title {
      font-size: 1rem;
      font-weight: 600;
    }

    .v-card-text {
      color: #57606a;
      font-size: 0.875rem;
      padding-bottom: 0;
    }
  }
}

// Responsive adjustments
@media (max-width: 600px) {
  .wise-payment-dialog {
    .stepper-wrapper {
      gap: 1.5rem;
    }

    .stepper-item {
      min-width: 80px;
      max-width: 80px;
    }

    .step-name {
      font-size: 0.75rem;
      letter-spacing: -0.2px;
    }

    .step-emoji {
      font-size: 0.875rem;
    }
  }
}
</style> 