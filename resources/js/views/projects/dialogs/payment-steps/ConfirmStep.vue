<template>
  <div class="confirm-step">
    <h3>Confirm Payment Details</h3>
    
    <div class="confirmation-details">
      <div class="detail-section">
        <h4>Recipient Information</h4>
        
        <div class="detail-row">
          <span class="label">Account Holder:</span>
          <span class="value">{{ selectedRecipient.name.fullName }}</span>
        </div>
        
        <div class="detail-row">
          <span class="label">Account Type:</span>
          <span class="value">{{ selectedRecipient.details.accountType }}</span>
        </div>
        
        <div class="detail-row">
          <span class="label">Account Number:</span>
          <span class="value">•••• {{ selectedRecipient.details.accountNumber.slice(-4) }}</span>
        </div>
      </div>

      <VDivider class="my-4" />

      <div class="detail-section">
        <h4>Payment Information</h4>
        
        <div class="detail-row">
          <span class="label">Amount:</span>
          <span class="value">${{ paymentDetails.amount.toFixed(2) }}</span>
        </div>
        
        <div class="detail-row">
          <span class="label">Currency:</span>
          <span class="value">{{ selectedRecipient.currency }}</span>
        </div>
      </div>

      <VAlert
        type="info"
        variant="tonal"
        class="mt-4"
      >
        Please verify all details before confirming the payment. This action cannot be undone.
      </VAlert>

      <div class="action-buttons mt-4">
        <VBtn
          color="secondary"
          variant="outlined"
          @click="$emit('back')"
        >
          Back
        </VBtn>
        
        <VBtn
          color="primary"
          :loading="isProcessing"
          @click="$emit('process-payment')"
        >
          Confirm and Process Payment
        </VBtn>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  paymentDetails: {
    type: Object,
    required: true
  },
  selectedRecipient: {
    type: Object,
    required: true
  },
  isProcessing: {
    type: Boolean,
    default: false
  }
})

defineEmits(['back', 'process-payment'])
</script>

<style lang="scss" scoped>
.confirm-step {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);

  h3, h4 {
    color: #24292f;
    font-weight: 600;
  }

  h3 {
    margin-bottom: 1.5rem;
  }

  h4 {
    margin-bottom: 1rem;
    font-size: 0.875rem;
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #d0d7de;

    &:last-child {
      border-bottom: none;
    }

    .label {
      color: #57606a;
    }

    .value {
      font-weight: 500;
      color: #24292f;
    }
  }

  .action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
  }
}
</style> 