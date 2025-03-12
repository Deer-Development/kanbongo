<template>
  <div class="complete-step">
    <div class="success-animation">
      <div class="checkmark-circle">
        <VIcon
          size="48"
          color="success"
          class="checkmark-icon"
        >
          tabler-check
        </VIcon>
      </div>
    </div>

    <VCard class="status-card" variant="flat" elevation="0">
      <VCardText class="text-center">
        <h3 class="success-title d-flex align-center justify-center gap-2">
          <VIcon color="success" size="20">tabler-circle-check</VIcon>
          Payment Successful
        </h3>
        
        <p class="status-message text-medium-emphasis">
          Your payment has been processed successfully
        </p>

        <div class="payment-summary mt-6">
          <div class="summary-item">
            <span class="label">Status</span>
            <VChip
              color="success"
              size="small"
              variant="tonal"
              class="status-chip"
            >
              <template #prepend>
                <VIcon size="16">tabler-check-circle</VIcon>
              </template>
              Completed
            </VChip>
          </div>

          <VDivider class="my-4" />

          <div class="summary-item">
            <span class="label">Transaction ID</span>
            <span class="value font-weight-medium">
              <VIcon size="16" color="primary" class="mr-1">tabler-receipt</VIcon>
              P-{{ Math.random().toString(36).substr(2, 9).toUpperCase() }}
            </span>
          </div>

          <div class="summary-item">
            <span class="label">Date & Time</span>
            <span class="value font-weight-medium">
              <VIcon size="16" color="primary" class="mr-1">tabler-calendar</VIcon>
              {{ new Date().toLocaleString() }}
            </span>
          </div>
        </div>
      </VCardText>
    </VCard>

    <div class="action-buttons d-flex justify-center mt-6">
      <VBtn
        color="primary"
        variant="tonal"
        size="large"
        @click="$emit('close')"
        prepend-icon="tabler-check"
      >
        Done
      </VBtn>
    </div>
  </div>
</template>

<script setup>
defineEmits(['close'])
</script>

<style lang="scss" scoped>
.complete-step {
  max-width: 500px;
  margin: 0 auto;
  padding: 1rem;

  .success-animation {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;

    .checkmark-circle {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: #f6fef9;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid #2da44e;
      animation: scale-up 0.3s ease-in-out;

      .checkmark-icon {
        animation: slide-in 0.3s ease-out 0.15s both;
      }
    }
  }

  .status-card {
    border: 1px solid #d0d7de;
    border-radius: 6px;
    overflow: hidden;

    .success-title {
      color: #24292f;
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .status-message {
      font-size: 0.875rem;
      color: #57606a;
    }

    .payment-summary {
      .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;

        .label {
          color: #57606a;
          font-size: 0.875rem;
        }

        .value {
          color: #24292f;
          font-size: 0.875rem;
          display: flex;
          align-items: center;
        }

        .status-chip {
          font-weight: 500;
        }
      }
    }
  }
}

@keyframes scale-up {
  0% {
    transform: scale(0.8);
    opacity: 0;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes slide-in {
  0% {
    transform: translateY(5px);
    opacity: 0;
  }
  100% {
    transform: translateY(0);
    opacity: 1;
  }
}

// Responsive adjustments
@media (max-width: 600px) {
  .complete-step {
    padding: 1rem 0.5rem;

    .success-animation {
      margin-bottom: 1.5rem;

      .checkmark-circle {
        width: 70px;
        height: 70px;
      }
    }

    .status-card {
      .success-title {
        font-size: 1.125rem;
      }
    }
  }
}
</style> 