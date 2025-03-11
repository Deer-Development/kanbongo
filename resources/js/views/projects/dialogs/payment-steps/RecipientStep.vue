<template>
  <div class="recipient-step">
    <h3>Select Payment Recipient</h3>
    
    <div v-if="isProcessing" class="d-flex justify-center py-4">
      <VProgressCircular indeterminate />
    </div>
    
    <template v-else>
      <div class="recipient-list">
        <div 
          v-for="recipient in recipients" 
          :key="recipient.id"
          class="recipient-card"
          :class="{ 'selected': selectedRecipient?.id === recipient.id }"
          @click="$emit('select-recipient', recipient)"
        >
          <div class="recipient-info">
            <div class="recipient-name">
              {{ recipient.name?.fullName || 'Unnamed Recipient' }}
            </div>
            <div class="recipient-details">
              <div class="account-summary">
                {{ recipient.longAccountSummary || 'No account details' }}
              </div>
              <div class="bank-details" v-if="recipient.displayFields?.length">
                <template v-for="field in recipient.displayFields" :key="field.key">
                  <span class="detail-label">{{ field.label }}:</span>
                  <span class="detail-value">{{ field.value }}</span>
                </template>
              </div>
            </div>
          </div>
          
          <div class="recipient-status">
            <VIcon 
              v-if="selectedRecipient?.id === recipient.id"
              color="success"
              size="20"
            >
              tabler-check-circle
            </VIcon>
          </div>
        </div>
      </div>

      <div v-if="!recipients.length" class="no-recipients">
        <p>No recipients found. Please add a recipient first.</p>
      </div>

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
          :disabled="!selectedRecipient"
          @click="$emit('next')"
        >
          Continue to Confirmation
        </VBtn>
      </div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  recipients: {
    type: Array,
    required: true
  },
  selectedRecipient: {
    type: Object,
    default: null
  },
  isProcessing: {
    type: Boolean,
    default: false
  }
})

defineEmits(['back', 'next', 'select-recipient'])
</script>

<style lang="scss" scoped>
.recipient-step {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem;

  h3 {
    margin-bottom: 1.5rem;
    color: #24292f;
    font-weight: 600;
  }

  .recipient-list {
    display: grid;
    gap: 1rem;
    margin-bottom: 2rem;

    .recipient-card {
      background: #fff;
      border: 1px solid #d0d7de;
      border-radius: 6px;
      padding: 1rem;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;

      &:hover {
        border-color: #0969da;
        background: #f6f8fa;
      }

      &.selected {
        border-color: #2da44e;
        background: #f6fef9;
      }

      .recipient-info {
        flex: 1;

        .recipient-name {
          font-weight: 600;
          color: #24292f;
          margin-bottom: 0.5rem;
        }

        .recipient-details {
          font-size: 0.875rem;
          color: #57606a;

          .bank-details {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.25rem 1rem;
            margin-top: 0.5rem;
          }
        }
      }
    }
  }

  .action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
  }
}
</style> 