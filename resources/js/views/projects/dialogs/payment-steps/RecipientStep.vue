<template>
  <div class="recipient-step">
    <div class="step-header">
      <h3 class="d-flex align-center gap-2">
        <VIcon color="primary" size="20">tabler-user-circle</VIcon>
        Select Payment Recipient
      </h3>
      <div class="d-flex justify-space-between align-center mb-4">
        <p class="text-subtitle-2 text-medium-emphasis">
          Choose the recipient for your Wise transfer
        </p>
        <VBtn
          color="primary"
          variant="outlined"
          prepend-icon="tabler-plus"
          @click="$emit('add-recipient')"
        >
          Add Recipient
        </VBtn>
      </div>
    </div>
    
    <VProgressLinear
      v-if="isProcessing"
      indeterminate
      color="primary"
      class="mb-4"
    />
    
    <template v-else>
      <div class="recipient-list">
        <VCard
          v-for="recipient in recipients" 
          :key="recipient.id"
          :class="['recipient-card', { 'selected': selectedRecipient?.id === recipient.id }]"
          @click="$emit('select-recipient', recipient)"
          variant="flat"
          elevation="0"
        >
          <VCardItem>
            <template #prepend>
              <VAvatar
                color="primary"
                variant="tonal"
                size="40"
              >
                {{ getInitials(recipient.name?.fullName) }}
              </VAvatar>
            </template>

            <VCardTitle>
              {{ recipient.name?.fullName || 'Unnamed Recipient' }}
            </VCardTitle>

            <template #append>
              <VIcon 
                v-if="selectedRecipient?.id === recipient.id"
                color="success"
                size="20"
              >
                tabler-check-circle
              </VIcon>
            </template>
          </VCardItem>

          <VDivider />

          <VCardText>
            <div class="recipient-details">
              <div class="details-grid">
                <template v-if="recipient.displayFields?.length">
                  <template v-for="field in recipient.displayFields" :key="field.key">
                    <div class="detail-label">
                      <VIcon size="16" class="mr-2">{{ getFieldIcon(field.key) }}</VIcon>
                      {{ field.label }}
                    </div>
                    <div class="detail-value">{{ field.value }}</div>
                  </template>
                </template>
                <div class="detail-label">
                  <VIcon size="16" class="mr-2">tabler-info-circle</VIcon>
                  Summary
                </div>
                <div class="detail-value">{{ recipient.longAccountSummary || 'No account details' }}</div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </div>

      <VAlert
        v-if="!recipients.length"
        type="info"
        variant="tonal"
        class="my-4"
      >
        <template #prepend>
          <VIcon>tabler-info-circle</VIcon>
        </template>
        No recipients found. Please add a recipient first.
      </VAlert>

      <VDivider class="my-4" />

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
          :disabled="!selectedRecipient"
          @click="$emit('next')"
          append-icon="tabler-arrow-right"
        >
          Continue
        </VBtn>
      </div>
    </template>
  </div>
</template>

<script setup>
const props = defineProps({
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

defineEmits(['back', 'next', 'select-recipient', 'add-recipient'])

const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getFieldIcon = (key) => {
  const icons = {
    'accountNumber': 'tabler-credit-card',
    'iban': 'tabler-building-bank',
    'swift': 'tabler-world',
    'sortCode': 'tabler-hash',
    default: 'tabler-info-circle'
  }
  return icons[key] || icons.default
}
</script>

<style lang="scss" scoped>
.recipient-step {
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

  .recipient-list {
    display: grid;
    gap: 1rem;
    margin-bottom: 2rem;

    .recipient-card {
      border: 1px solid #d0d7de;
      transition: all 0.2s ease;
      cursor: pointer;

      &:hover {
        border-color: #0969da;
        background: #f6f8fa;
      }

      &.selected {
        border-color: #2da44e;
        background: #f6fef9;
        box-shadow: 0 0 0 3px rgba(45, 164, 78, 0.1);
      }

      .recipient-details {
        .details-grid {
          display: grid;
          grid-template-columns: minmax(120px, auto) 1fr;
          gap: 0.75rem 2rem;
          align-items: center;

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
  }
}

// Responsive adjustments
@media (max-width: 600px) {
  .recipient-step {
    padding: 1rem 0.5rem;

    .step-header {
      margin-bottom: 1.5rem;

      h3 {
        font-size: 1.125rem;
      }
    }

    .recipient-card {
      :deep(.v-card-item) {
        padding: 0.75rem;
      }

      :deep(.v-card-text) {
        padding: 0.75rem;
        padding-top: 0;
      }
    }

    .bank-details {
      .v-chip {
        font-size: 0.75rem;
      }
    }
  }
}
</style> 