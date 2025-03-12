<template>
  <div class="confirm-step">
    <div class="step-header">
      <h3 class="d-flex align-center gap-2">
        <VIcon color="primary" size="20">tabler-file-check</VIcon>
        Confirm Payment Details
      </h3>
      <p class="text-subtitle-2 text-medium-emphasis">
        Please review the payment information before proceeding
      </p>
    </div>

    <VCard class="confirmation-card" variant="flat" elevation="0">
      <VCardItem>
        <template #prepend>
          <VAvatar color="primary" variant="tonal" size="40">
            {{ getInitials(selectedRecipient.name.fullName) }}
          </VAvatar>
        </template>
        <VCardTitle>{{ selectedRecipient.name.fullName }}</VCardTitle>
      </VCardItem>

      <VDivider />

      <VCardText>
        <div class="details-grid">
          <div class="section-title" colspan="2">Recipient Details</div>
          
          <div class="detail-label">
            <VIcon size="16" class="mr-2">tabler-building-bank</VIcon>
            Account Type
          </div>
          <div class="detail-value">{{ selectedRecipient.details.accountType }}</div>
          
          <div class="detail-label">
            <VIcon size="16" class="mr-2">tabler-credit-card</VIcon>
            Account Number
          </div>
          <div class="detail-value">•••• {{ selectedRecipient.details.accountNumber.slice(-4) }}</div>

          <div class="section-title" colspan="2">Payment Details</div>
          
          <div class="detail-label">
            <VIcon size="16" class="mr-2">tabler-cash</VIcon>
            Amount
          </div>
          <div class="detail-value">${{ paymentDetails.amount.toFixed(2) }}</div>
          
          <div class="detail-label">
            <VIcon size="16" class="mr-2">tabler-currency-dollar</VIcon>
            Currency
          </div>
          <div class="detail-value">{{ selectedRecipient.currency }}</div>
        </div>
      </VCardText>
    </VCard>

    <VAlert
      type="warning"
      variant="tonal"
      class="mt-6"
      border="start"
    >
      <template #prepend>
        <VIcon>tabler-alert-circle</VIcon>
      </template>
      Please verify all details before confirming. This action cannot be undone.
    </VAlert>

    <VDivider class="my-6" />

    <div class="d-flex justify-end gap-3">
      <VBtn
        variant="tonal"
        @click="$emit('back')"
        prepend-icon="tabler-arrow-left"
      >
        Back
      </VBtn>
      
      <VBtn
        color="primary"
        :loading="isProcessing"
        @click="$emit('process-payment')"
        prepend-icon="tabler-check"
      >
        Confirm Payment
      </VBtn>
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

const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}
</script>

<style lang="scss" scoped>
.confirm-step {
  max-width: 700px;
  margin: 0 auto;
  padding: 1rem;

  .step-header {
    margin-bottom: 2rem;

    h3 {
      color: #24292f;
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
  }

  .confirmation-card {
    border: 1px solid #d0d7de;
    
    .details-grid {
      display: grid;
      grid-template-columns: minmax(140px, auto) 1fr;
      gap: 1rem;
      padding: 0.5rem 0;

      .section-title {
        grid-column: 1 / -1;
        color: #24292f;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 1rem 0 0.5rem;
        
        &:first-child {
          padding-top: 0;
        }
      }

      .detail-label {
        color: #57606a;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
      }

      .detail-value {
        color: #24292f;
        font-size: 0.875rem;
        font-weight: 500;
      }
    }
  }
}

// ... existing responsive styles ...
</style> 