<template>
  <div class="review-step">
    <h3>Review Payment Details</h3>
    
    <div class="payment-details">
      <div class="detail-row">
        <span class="label">Amount:</span>
        <span class="value">${{ paymentDetails.amount.toFixed(2) }}</span>
      </div>
      
      <div class="detail-row">
        <span class="label">Selected Entries:</span>
        <span class="value">{{ paymentDetails.selectedEntries.length }} items</span>
      </div>
      
      <div class="detail-row">
        <span class="label">Date Range:</span>
        <span class="value">{{ paymentDetails.dateRange }}</span>
      </div>
    </div>

    <div class="action-buttons mt-4">
      <VBtn
        color="secondary"
        variant="outlined"
        @click="$emit('back')"
      >
        Cancel
      </VBtn>
      
      <VBtn
        color="primary"
        @click="$emit('next')"
      >
        Continue to Recipient Selection
      </VBtn>
    </div>
  </div>
</template>

<script setup>
defineProps({
  paymentDetails: {
    type: Object,
    required: true
  }
})

defineEmits(['back', 'next'])
</script>

<style lang="scss" scoped>
.review-step {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);

  h3 {
    margin-bottom: 1.5rem;
    color: #24292f;
    font-weight: 600;
  }

  .payment-details {
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
  }

  .action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
  }
}
</style> 