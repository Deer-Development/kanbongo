<template>
  <VDialog
    v-model="isVisible"
    persistent
    fullscreen
    class="wise-payment-dialog"
  >
    <!-- Header -->
    <div class="header-section">
      <div class="top-bar">
        <h2 class="text-h6">Process Payment</h2>
        <VBtn
          icon
          variant="text"
          class="close-btn"
          @click="close"
        >
          <VIcon>tabler-x</VIcon>
        </VBtn>
      </div>
      
      <VStepper
        v-model="currentStep"
        class="payment-stepper"
        :items="[
          { title: 'Review', value: STEPS.REVIEW },
          { title: 'Select Recipient', value: STEPS.RECIPIENT },
          { title: 'Confirm', value: STEPS.CONFIRM }
        ]"
      />
    </div>

    <div class="payment-content pa-6">
      <!-- Steps content here -->
      <component 
        :is="currentStepComponent"
        :payment-details="paymentDetails"
        :recipients="recipients"
        :selected-recipient="selectedRecipient"
        :is-processing="isProcessing"
        @back="handleBack"
        @next="handleNext"
        @select-recipient="selectRecipient"
        @process-payment="processPayment"
      />
    </div>
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

const emit = defineEmits(['update:modelValue', 'payment-complete'])

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

// Reset state when dialog closes
watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    currentStep.value = STEPS.REVIEW
    selectedRecipient.value = null
    recipients.value = []
  } else {
    fetchRecipients()
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
  } finally {
    isProcessing.value = false
  }
}

const selectRecipient = (recipient) => {
  selectedRecipient.value = recipient
}

const handleBack = () => {
  if (currentStep.value > STEPS.REVIEW) {
    currentStep.value--
  }
}

const handleNext = () => {
  if (currentStep.value < STEPS.COMPLETE) {
    currentStep.value++
  }
}

const processPayment = async () => {
  try {
    currentStep.value = STEPS.PROCESSING
    isProcessing.value = true

    console.log(props.paymentDetails)

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
      startPaymentStatusPolling(res.data.payment.wise_transfer_id)
    }
  } catch (error) {
    toast.error(error.response?.data?.message || 'Payment processing failed')
    currentStep.value = STEPS.CONFIRM
  } finally {
    isProcessing.value = false
  }
}

const startPaymentStatusPolling = async (transferId) => {
  const pollInterval = 5000
  const maxAttempts = 12
  let attempts = 0
  
  const pollStatus = async () => {
    try {
      const response = await $api(`/payments/${transferId}/status`)
      const status = response.data.status
      
      if (status === 'completed') {
        toast.success('Payment completed successfully')
        emit('payment-complete')
        return
      }
      
      if (status === 'failed') {
        toast.error('Payment failed')
        return
      }
      
      if (attempts < maxAttempts) {
        attempts++
        setTimeout(pollStatus, pollInterval)
      }
    } catch (error) {
      console.error('Failed to fetch payment status:', error)
    }
  }
  
  pollStatus()
}

const close = () => {
  isVisible.value = false
  currentStep.value = STEPS.REVIEW
  selectedRecipient.value = null
}
</script>

<style lang="scss" scoped>
.wise-payment-dialog {
  .header-section {
    background: #ffffff;
    padding: 1.5rem;
    border-bottom: 1px solid #d0d7de;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  .top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .payment-stepper {
    margin-top: 1rem;
    
    :deep(.v-stepper-header) {
      box-shadow: none;
      border: 1px solid #d0d7de;
      border-radius: 6px;
    }
  }

  .payment-content {
    background: #f6f8fa;
    min-height: calc(100vh - 140px);
  }
}
</style> 