<script setup>
import { formatCurrency, formatHours, formatPercent } from '@/utils/formatters'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_hours: 0,
      previous_hours: 0,
      hours_trend: 0,
      total_income: 0,
      previous_income: 0,
      income_trend: 0,
      active_projects: 0,
      total_projects: 0,
      projects_trend: 0,
      average_rate: 0,
      previous_rate: 0,
      rate_trend: 0
    })
  },
  isLoading: {
    type: Boolean,
    default: false
  }
})

const trendColor = (value) => {
  if (!value) return 'secondary'
  if (value > 0) return 'success'
  if (value < 0) return 'error'
  return 'secondary'
}

const trendIcon = (value) => {
  if (!value) return 'tabler-minus'
  if (value > 0) return 'tabler-trending-up'
  if (value < 0) return 'tabler-trending-down'
  return 'tabler-minus'
}
</script>

<template>
  <div class="stats-section">
    <VRow>
      <!-- Total Hours -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="stat-card" :loading="isLoading" elevation="0" border>
          <VCardItem>
            <template #prepend>
              <div class="stat-icon-wrapper hours">
                <VIcon size="20" icon="tabler-clock" />
              </div>
            </template>
            
            <VCardTitle class="stat-title">
              Total Hours
              <VTooltip location="top" class="stat-tooltip">
                <template #activator="{ props }">
                  <VIcon
                    v-bind="props"
                    size="14"
                    icon="tabler-info-circle"
                    class="info-icon"
                  />
                </template>
                Total hours worked this period
              </VTooltip>
            </VCardTitle>
            
            <template #append>
              <div 
                class="trend-chip"
                :class="trendColor(stats?.hours_trend)"
              >
                <VIcon
                  size="14"
                  :icon="trendIcon(stats?.hours_trend)"
                  class="trend-icon"
                />
                {{ formatPercent(stats?.hours_trend || 0) }}
              </div>
            </template>
          </VCardItem>
          
          <VCardText>
            <div class="stat-value">{{ formatHours(stats?.total_hours || 0) }}</div>
            <div class="stat-subtitle">vs. {{ formatHours(stats?.previous_hours || 0) }} last period</div>
          </VCardText>
        </VCard>
      </VCol>
      
      <!-- Total Income -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="stat-card" :loading="isLoading" elevation="0" border>
          <VCardItem>
            <template #prepend>
              <div class="stat-icon-wrapper income">
                <VIcon size="20" icon="tabler-cash" />
              </div>
            </template>
            
            <VCardTitle class="stat-title">
              Total Income
            </VCardTitle>
            
            <template #append>
              <div 
                class="trend-chip"
                :class="trendColor(stats?.income_trend)"
              >
                <VIcon
                  size="14"
                  :icon="trendIcon(stats?.income_trend)"
                  class="trend-icon"
                />
                {{ formatPercent(stats?.income_trend || 0) }}
              </div>
            </template>
          </VCardItem>
          
          <VCardText>
            <div class="stat-value">{{ formatCurrency(stats?.total_income || 0) }}</div>
            <div class="stat-subtitle">vs. {{ formatCurrency(stats?.previous_income || 0) }} last period</div>
          </VCardText>
        </VCard>
      </VCol>
      
      <!-- Active Projects -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="stat-card" :loading="isLoading" elevation="0" border>
          <VCardItem>
            <template #prepend>
              <div class="stat-icon-wrapper projects">
                <VIcon size="20" icon="tabler-layout-grid" />
              </div>
            </template>
            
            <VCardTitle class="stat-title">
              Active Projects
            </VCardTitle>
            
            <template #append>
              <div 
                class="trend-chip"
                :class="trendColor(stats?.projects_trend)"
              >
                <VIcon
                  size="14"
                  :icon="trendIcon(stats?.projects_trend)"
                  class="trend-icon"
                />
                {{ formatPercent(stats?.projects_trend || 0) }}
              </div>
            </template>
          </VCardItem>
          
          <VCardText>
            <div class="stat-value">{{ stats?.active_projects || 0 }}</div>
            <div class="stat-subtitle">out of {{ stats?.total_projects || 0 }} total projects</div>
          </VCardText>
        </VCard>
      </VCol>
      
      <!-- Average Rate -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="stat-card" :loading="isLoading" elevation="0" border>
          <VCardItem>
            <template #prepend>
              <div class="stat-icon-wrapper rate">
                <VIcon size="20" icon="tabler-chart-line" />
              </div>
            </template>
            
            <VCardTitle class="stat-title">
              Average Rate
            </VCardTitle>
            
            <template #append>
              <div 
                class="trend-chip"
                :class="trendColor(stats?.rate_trend)"
              >
                <VIcon
                  size="14"
                  :icon="trendIcon(stats?.rate_trend)"
                  class="trend-icon"
                />
                {{ formatPercent(stats?.rate_trend || 0) }}
              </div>
            </template>
          </VCardItem>
          
          <VCardText>
            <div class="stat-value">{{ formatCurrency(stats?.average_rate || 0) }}/hr</div>
            <div class="stat-subtitle">across all active projects</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style lang="scss" scoped>
.stats-section {
  .v-row {
    margin: -0.75rem;
    
    .v-col {
      padding: 0.75rem;
    }
  }

  .stat-card {
    transition: all 0.2s ease;
    border: 1px solid #d0d7de !important;
    background: #ffffff;

    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(27, 31, 35, 0.15) !important;
      border-color: #0969da !important;

      .stat-icon-wrapper {
        transform: scale(1.05);
      }
    }

    .stat-icon-wrapper {
      width: 42px;
      height: 42px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: all 0.2s ease;

      &.hours {
        background: #ddf4ff;
        color: #0969da;
      }

      &.income {
        background: #dafbe1;
        color: #1a7f37;
      }

      &.projects {
        background: #fff8c5;
        color: #9a6700;
      }

      &.rate {
        background: #ffebe9;
        color: #cf222e;
      }
    }

    .stat-title {
      font-size: 0.875rem !important;
      font-weight: 500 !important;
      color: #57606a;
      display: flex;
      align-items: center;
      gap: 0.5rem;

      .info-icon {
        color: #6e7781;
        opacity: 0.8;
        cursor: help;

        &:hover {
          opacity: 1;
        }
      }
    }

    .stat-value {
      font-size: 1.5rem;
      font-weight: 600;
      color: #24292f;
      margin: 0.5rem 0 0.25rem;
      letter-spacing: -0.5px;
    }

    .stat-subtitle {
      font-size: 0.75rem;
      color: #57606a;
    }

    .trend-chip {
      display: flex;
      align-items: center;
      gap: 4px;
      padding: 2px 8px;
      border-radius: 1rem;
      font-size: 0.75rem;
      font-weight: 600;
      height: 24px;
      transition: all 0.2s ease;

      &.success {
        background: #dafbe1;
        color: #1a7f37;

        .trend-icon {
          color: #1a7f37;
        }
      }

      &.error {
        background: #ffebe9;
        color: #cf222e;

        .trend-icon {
          color: #cf222e;
        }
      }

      &.secondary {
        background: #f6f8fa;
        color: #57606a;

        .trend-icon {
          color: #57606a;
        }
      }

      .trend-icon {
        margin-right: 2px;
      }
    }
  }

  // Loading state styles
  .v-card.v-card--loading {
    .stat-icon-wrapper,
    .stat-value,
    .stat-subtitle,
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

    .stat-icon-wrapper {
      background: #eaeef2 !important;
    }

    .stat-value {
      width: 80px;
      height: 24px;
      border-radius: 4px;
    }

    .stat-subtitle {
      width: 120px;
      height: 16px;
      border-radius: 4px;
    }

    .trend-chip {
      width: 60px;
      height: 24px;
      border-radius: 12px;
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
  .stats-section {
    .stat-card {
      .stat-value {
        font-size: 1.25rem;
      }

      .stat-icon-wrapper {
        width: 36px;
        height: 36px;

        .v-icon {
          font-size: 18px;
        }
      }
    }
  }
}
</style> 