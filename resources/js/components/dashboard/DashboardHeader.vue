<script setup>
import { ref, computed } from 'vue'
import AppDateTimePicker from '@core/components/app-form-elements/AppDateTimePicker.vue'

const props = defineProps({
  selectedPeriod: {
    type: String,
    required: true
  },
  customDateRange: {
    type: Array,
    default: () => null
  },
  isLoading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:selectedPeriod', 'update:customDateRange', 'period-change'])

const showMenu = ref(false)
const showDatePicker = ref(false)

const periodOptions = [
  {
    label: 'Current Period',
    items: [
      { 
        label: 'Current Week',
        value: 'current_week',
        icon: 'tabler-calendar-week',
        description: 'This week\'s data'
      },
      { 
        label: 'Current Month',
        value: 'current_month',
        icon: 'tabler-calendar-month',
        description: 'This month\'s data'
      },
      {
        label: 'Current Quarter',
        value: 'current_quarter',
        icon: 'tabler-calendar-stats',
        description: 'This quarter\'s data'
      },
      {
        label: 'Current Year',
        value: 'current_year',
        icon: 'tabler-calendar',
        description: 'This year\'s data'
      }
    ]
  },
  {
    label: 'Previous Period',
    items: [
      {
        label: 'Last Week',
        value: 'last_week',
        icon: 'tabler-calendar-week',
        description: 'Previous week\'s data'
      },
      {
        label: 'Last Month',
        value: 'last_month',
        icon: 'tabler-calendar-month',
        description: 'Previous month\'s data'
      },
      {
        label: 'Last Quarter',
        value: 'last_quarter',
        icon: 'tabler-calendar-stats',
        description: 'Previous quarter\'s data'
      },
      {
        label: 'Last Year',
        value: 'last_year',
        icon: 'tabler-calendar',
        description: 'Previous year\'s data'
      }
    ]
  },
  {
    label: 'Custom',
    items: [
      {
        label: 'Custom Range',
        value: 'custom',
        icon: 'tabler-calendar-due',
        description: 'Select a custom date range'
      },
      {
        label: 'All Time',
        value: 'all_time',
        icon: 'tabler-calendar-due',
        description: 'All time data'
      }
    ]
  }
]

const selectedPeriodLabel = computed(() => {
  const option = periodOptions
    .flatMap(group => group.items)
    .find(item => item.value === props.selectedPeriod)
  
  return option?.label || 'Select Period'
})

const handlePeriodSelect = (period) => {
  emit('update:selectedPeriod', period)
  showMenu.value = false
  
  if (period === 'custom') {
    showDatePicker.value = true
  } else {
    emit('period-change', period)
  }
}

const handleDateRangeChange = (range) => {
  emit('update:customDateRange', range)
  if (range && range.length === 2) {
    emit('period-change', 'custom')
  }
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
        <div class="period-controls">
          <VMenu
            v-model="showMenu"
            :close-on-content-click="false"
            location="bottom end"
            offset="4"
          >
            <template #activator="{ props: menuProps }">
              <VBtn
                variant="outlined"
                class="period-btn"
                v-bind="menuProps"
                :loading="isLoading"
                :disabled="isLoading"
              >
                <VIcon
                  :icon="periodOptions
                    .flatMap(group => group.items)
                    .find(item => item.value === selectedPeriod)?.icon || 'tabler-calendar'"
                  size="20"
                  class="mr-2"
                />
                {{ selectedPeriodLabel }}
                <VIcon
                  size="20"
                  icon="tabler-chevron-down"
                  class="ml-1"
                />
              </VBtn>
            </template>

            <VCard class="period-menu" elevation="3" min-width="280">
              <VList lines="two" density="compact">
                <template v-for="(group, index) in periodOptions" :key="index">
                  <VListSubheader class="period-group-header">
                    {{ group.label }}
                  </VListSubheader>

                  <VListItem
                    v-for="item in group.items"
                    :key="item.value"
                    :value="item.value"
                    class="period-option"
                    :active="selectedPeriod === item.value"
                    @click="handlePeriodSelect(item.value)"
                  >
                    <template #prepend>
                      <VIcon :icon="item.icon" size="20" />
                    </template>

                    <VListItemTitle>{{ item.label }}</VListItemTitle>
                    <VListItemSubtitle>{{ item.description }}</VListItemSubtitle>
                  </VListItem>

                  <VDivider v-if="index < periodOptions.length - 1" />
                </template>
              </VList>
            </VCard>
          </VMenu>

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
              @update:model-value="handleDateRangeChange"
            >
              <template #prepend>
                <VIcon size="16" class="calendar-icon">tabler-calendar</VIcon>
              </template>
            </AppDateTimePicker>
          </div>
        </div>
      </div>
    </div>
    
    <div class="header-right">
      
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
      
      .period-controls {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        
        .custom-date-wrapper {
          position: relative;
          flex: 1;
          min-width: 270px;
        }
      }
      
      .period-btn {
        height: 36px;
        font-size: 0.875rem;
        font-weight: 500;
        color: #24292f;
        background: #f6f8fa;
        border: 1px solid #d0d7de;
        letter-spacing: normal;
        text-transform: none;
        
        &:hover {
          background: #f3f4f6;
          border-color: #1a7f37;
          color: #1a7f37;
        }
      }
    }
  }
  
  .header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: none;
    
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

.period-menu {
  .period-group-header {
    font-size: 0.75rem;
    font-weight: 600;
    color: #57606a;
    background: #f6f8fa;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }
  
  .period-option {
    min-height: 56px;
    padding: 8px 16px;
    
    &:hover {
      background: #f6f8fa;
    }
    
    &.v-list-item--active {
      background: #f0f9ff;
      color: #0969da;
      
      .v-list-item-subtitle {
        color: #57606a;
      }
    }
    
    .v-list-item-title {
      font-size: 0.875rem;
      font-weight: 500;
    }
    
    .v-list-item-subtitle {
      font-size: 0.75rem;
      color: #57606a;
      margin-top: 2px;
    }
  }
}

.date-picker {
  min-width: 240px;
  width: 100%;
  
  :deep(.flatpickr-custom-style) {
    position: relative !important;
    width: 100% !important;
    
    input {
      width: 100% !important;
    }
  }
  
  :deep(.v-field__input) {
    width: 100%;
    min-height: 36px;
    padding: 0 12px;
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
        max-width: 300px;
        
        .date-picker {
          :deep(.v-field) {
            border-radius: 6px;
            border: 1px solid #d0d7de;
            background: #ffffff;
            height: 24px;
            min-height: 24px;
            font-size: 0.75rem;
            
            &:hover {
              border-color: #0969da;
            }
            
            &.v-field--focused {
              border-color: #0969da;
              box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.1);
            }

            .v-field__input {
              min-height: 24px;
              padding-top: 0;
              padding-bottom: 0;
            }
          }
        }
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