<script setup>
import { formatCurrency, formatHours } from '@/utils/formatters'

const props = defineProps({
  income: {
    type: Object,
    default: () => ({
      total_income: 0,
      total_hours: 0,
      pending_income: 0,
      pending_hours: 0,
      grand_total: 0
    })
  },
  spending: {
    type: Object,
    default: () => ({
      total_spending: 0,
      pending_spending: 0,
      grand_total: 0
    })
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  hasOwnedContainers: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <VRow class="financial-summary">
    <!-- Income Card -->
    <VCol cols="12" md="6">
      <VCard class="summary-card income-card" :loading="isLoading" elevation="0" border>
        <VCardItem>
          <template #prepend>
            <div class="icon-wrapper income">
              <VIcon size="24" icon="tabler-cash" />
            </div>
          </template>
          
          <VCardTitle class="card-title">
            Income Summary
            <VChip
              size="small"
              :color="income?.total_income > 0 ? 'success' : 'secondary'"
              variant="flat"
              class="trend-chip"
            >
              {{ formatCurrency(income?.total_income || 0) }}
            </VChip>
          </VCardTitle>
        </VCardItem>
        
        <VCardText>
          <div class="summary-grid">
            <div class="stat-item">
              <span class="stat-label">Paid Income</span>
              <span class="stat-value">{{ formatCurrency(income?.total_income || 0) }}</span>
              <span v-if="income?.pending_income" class="pending-value">
                +{{ formatCurrency(income?.pending_income) }} pending
              </span>
            </div>
            
            <div class="stat-item">
              <span class="stat-label">Paid Hours</span>
              <span class="stat-value">{{ formatHours(income?.total_hours || 0) }}</span>
              <span v-if="income?.pending_hours" class="pending-value">
                +{{ formatHours(income?.pending_hours) }} pending
              </span>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
    
    <!-- Spending Card -->
    <VCol v-if="hasOwnedContainers" cols="12" md="6">
      <VCard class="summary-card spending-card" :loading="isLoading" elevation="0" border>
        <VCardItem>
          <template #prepend>
            <div class="icon-wrapper spending">
              <VIcon size="24" icon="tabler-cash-off" />
            </div>
          </template>
          
          <VCardTitle class="card-title">
            Spending Summary
            <VChip
              size="small"
              :color="spending?.total_spending > 0 ? 'error' : 'secondary'"
              variant="flat"
              class="ml-2"
            >
              {{ formatCurrency(spending?.grand_total || 0) }}
            </VChip>
          </VCardTitle>
        </VCardItem>
        
        <template v-if="spending?.containers?.length === 0">
          <VCardText class="empty-state">
            <VIcon
              size="40"
              icon="tabler-mood-empty"
              color="secondary"
              class="mb-2"
            />
            <div class="empty-text">
              <p class="text-medium-emphasis">
                You are not an owner of any boards yet.
              </p>
              <p class="text-caption">
                Spending data will be available when you own boards with active time entries.
              </p>
            </div>
          </VCardText>
        </template>
        
        <template v-else>
          <VCardText>
            <div class="summary-grid">
              <div class="stat-item">
                <span class="stat-label">Paid Spending</span>
                <span class="stat-value">{{ formatCurrency(spending?.total_spending || 0) }}</span>
                <span v-if="spending?.pending_spending" class="pending-value">
                  +{{ formatCurrency(spending?.pending_spending) }} pending
                </span>
              </div>
              
              <div class="stat-item">
                <span class="stat-label">Total Budget Used</span>
                <span class="stat-value">
                  {{ formatCurrency((spending?.total_spending || 0) + (spending?.pending_spending || 0)) }}
                </span>
              </div>
            </div>
          </VCardText>
        </template>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss" scoped>
.financial-summary {
  margin-top: 1.5rem;
  .summary-card {
    transition: all 0.2s ease;
    border: 1px solid #d0d7de !important;
    background: #ffffff;
    height: 100%;

    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(27, 31, 35, 0.15) !important;
      
      .icon-wrapper {
        transform: scale(1.05);
      }
    }

    .icon-wrapper {
      width: 48px;
      height: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: all 0.2s ease;

      &.income {
        background: #dafbe1;
        color: #1a7f37;
      }

      &.spending {
        background: #ffebe9;
        color: #cf222e;
      }
    }

    .card-title {
      font-size: 1rem !important;
      font-weight: 600 !important;
      color: #24292f;
      display: flex;
      align-items: center;
      gap: 0.75rem;

      .trend-chip {
        font-size: 0.75rem;
        font-weight: 600;
        height: 24px;
      }
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 1.5rem;
      margin-top: 1rem;

      .stat-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;

        .stat-label {
          font-size: 0.75rem;
          color: #57606a;
          font-weight: 500;
        }

        .stat-value {
          font-size: 1.25rem;
          font-weight: 600;
          color: #24292f;
          letter-spacing: -0.5px;
        }

        .pending-value {
          font-size: 0.75rem;
          color: #0969da;
          font-weight: 500;
        }
      }
    }
  }

  // Loading state styles
  .v-card.v-card--loading {
    .icon-wrapper,
    .stat-value,
    .stat-label,
    .pending-value,
    .trend-chip {
      position: relative;
      overflow: hidden;
      background: #f6f8fa;
      color: transparent;
      
      &::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(
          90deg,
          rgba(255, 255, 255, 0) 0,
          rgba(255, 255, 255, 0.6) 50%,
          rgba(255, 255, 255, 0) 100%
        );
        animation: shimmer 2s infinite;
      }
    }

    .icon-wrapper {
      background: #eaeef2 !important;
    }

    .stat-value {
      width: 100px;
      height: 24px;
      border-radius: 4px;
    }

    .stat-label {
      width: 80px;
      height: 16px;
      border-radius: 4px;
    }

    .pending-value {
      width: 120px;
      height: 16px;
      border-radius: 4px;
    }
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
    
    .empty-text {
      margin-top: 0.5rem;
      
      p {
        margin: 0;
        
        &:not(:last-child) {
          margin-bottom: 0.25rem;
        }
      }
    }
  }
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

// Responsive adjustments
@media (max-width: 600px) {
  .financial-summary {
    .summary-card {
      .summary-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }

      .stat-value {
        font-size: 1.125rem;
      }

      .icon-wrapper {
        width: 40px;
        height: 40px;

        .v-icon {
          font-size: 20px;
        }
      }
    }
  }
}
</style> 