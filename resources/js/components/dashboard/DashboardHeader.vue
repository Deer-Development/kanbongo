<script setup>
const props = defineProps({
  selectedPeriod: String,
  periodOptions: Array,
  customDateRange: String,
  isLoading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:selectedPeriod', 'update:customDateRange', 'periodChange', 'refresh'])

const changePeriod = (period) => {
  emit('periodChange', period)
}

const updateCustomDateRange = (value) => {
  emit('update:customDateRange', value)
}
</script>

<template>
  <div class="dashboard-header">
    <div class="header-left">
      <div class="title-section">
        <VIcon size="24" color="primary" class="dashboard-icon">tabler-dashboard</VIcon>
        <h1 class="dashboard-title">Dashboard</h1>
      </div>
      
      <VDivider vertical class="divider" />
      
      <div class="period-selector">
        <VBtnGroup density="comfortable" class="period-buttons">
          <VBtn
            v-for="option in periodOptions"
            :key="option.value"
            :color="selectedPeriod === option.value ? 'primary' : undefined"
            :variant="selectedPeriod === option.value ? 'tonal' : 'text'"
            size="small"
            class="period-btn"
            :class="{ active: selectedPeriod === option.value }"
            @click="changePeriod(option.value)"
          >
            <VIcon 
              size="16" 
              class="period-icon"
              :class="{ active: selectedPeriod === option.value }"
            >
              {{ option.icon }}
            </VIcon>
            {{ option.label }}
          </VBtn>
        </VBtnGroup>
      </div>
    </div>
    
    <div class="header-right">
      <div 
        v-if="selectedPeriod === 'custom'" 
        class="custom-date-wrapper"
        :class="{ 'has-value': customDateRange }"
      >
        <AppDateTimePicker
          :model-value="customDateRange"
          :config="{ 
            mode: 'range',
            maxDate: 'today',
            altInput: true,
            altFormat: 'M j, Y',
            dateFormat: 'Y-m-d'
          }"
          placeholder="Select date range"
          clearable
          class="date-picker"
          @update:model-value="updateCustomDateRange"
        >
          <template #prepend>
            <VIcon size="16" class="calendar-icon">tabler-calendar</VIcon>
          </template>
        </AppDateTimePicker>
      </div>
      
      <VBtn
        icon
        variant="text"
        size="small"
        class="refresh-btn"
        :loading="isLoading"
        @click="$emit('refresh')"
      >
        <VIcon size="20">tabler-refresh</VIcon>
        
        <VTooltip activator="parent" location="bottom">
          Refresh Dashboard
        </VTooltip>
      </VBtn>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  gap: 1rem;
  
  .header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0;
    
    .title-section {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      flex: none;
      
      .dashboard-icon {
        opacity: 0.9;
      }
      
      .dashboard-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        color: var(--v-high-emphasis);
      }
    }
    
    .divider {
      height: 24px;
      opacity: 0.1;
    }
    
    .period-selector {
      flex: none;
      
      .period-buttons {
        border: 1px solid var(--v-border-color);
        border-radius: 6px;
        overflow: hidden;
        
        .period-btn {
          height: 32px;
          font-size: 0.8125rem;
          font-weight: 500;
          letter-spacing: 0.25px;
          text-transform: none;
          padding: 0 0.75rem;
          border-radius: 0;
          color: var(--v-medium-emphasis);
          
          &:not(:last-child) {
            border-right: 1px solid var(--v-border-color);
          }
          
          &.active {
            font-weight: 600;
            
            .period-icon {
              opacity: 1;
            }
          }
          
          .period-icon {
            opacity: 0.7;
            transition: opacity 0.2s ease;
          }
          
          &:hover {
            .period-icon {
              opacity: 0.9;
            }
          }
        }
      }
    }
  }
  
  .header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: none;
    
    .custom-date-wrapper {
      width: 240px;
      
      &.has-value {
        :deep(.v-field) {
          --v-field-border-width: 1px;
          border-color: var(--v-primary);
        }
      }
      
      .date-picker {
        :deep(.v-field) {
          border-radius: 6px;
          font-size: 0.875rem;
          
          .v-field__input {
            padding-top: 0;
            padding-bottom: 0;
            min-height: 32px;
          }
        }
        
        .calendar-icon {
          color: var(--v-medium-emphasis);
          opacity: 0.8;
        }
      }
    }
    
    .refresh-btn {
      color: var(--v-medium-emphasis);
      
      &:hover {
        color: var(--v-high-emphasis);
      }
      
      &.v-btn--loading {
        color: var(--v-primary);
      }
    }
  }
}

// Dark mode adjustments
:deep(.v-theme--dark) {
  .dashboard-header {
    .period-buttons {
      border-color: rgba(255, 255, 255, 0.12);
      
      .period-btn {
        &:not(:last-child) {
          border-right-color: rgba(255, 255, 255, 0.12);
        }
      }
    }
  }
}

// Responsive adjustments
@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
    
    .header-left {
      flex-wrap: wrap;
      gap: 0.75rem;
      
      .divider {
        display: none;
      }
      
      .period-selector {
        width: 100%;
        margin-top: 0.5rem;
        
        .period-buttons {
          width: 100%;
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
        }
      }
    }
    
    .header-right {
      flex-wrap: wrap;
      gap: 0.75rem;
      
      .custom-date-wrapper {
        flex: 1;
        min-width: 200px;
      }
    }
  }
}

// Print styles
@media print {
  .dashboard-header {
    .period-selector,
    .refresh-btn {
      display: none !important;
    }
  }
}
</style> 