<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import DashboardHeader from '@/components/dashboard/DashboardHeader.vue'
import DashboardTabs from '@/components/dashboard/DashboardTabs.vue'
import FinancialSummary from '@/components/dashboard/FinancialSummary.vue'
import ActivityTimeline from '@/components/dashboard/ActivityTimeline.vue'
import IncomeTable from '@/components/dashboard/IncomeTable.vue'
import SpendingTable from '@/components/dashboard/SpendingTable.vue'
import ProjectsGrid from '@/components/dashboard/ProjectsGrid.vue'

// State
const router = useRouter()
const isLoading = ref(true)
const dashboardData = ref({
  income: {
    containers: [],
    total_income: 0,
    total_hours: 0,
    pending_income: 0,
    pending_hours: 0,
    grand_total: 0
  },
  spending: {
    containers: [],
    total_spending: 0,
    pending_spending: 0,
    grand_total: 0
  },
  active_projects: [],
  recent_activity: []
})

const selectedPeriod = ref('month')
const customDateRange = ref(null)
const showDatePicker = ref(false)

// Tabs
const activeTab = ref('overview')
const tabs = [
  { value: 'overview', label: 'Overview', icon: 'tabler-dashboard' },
  { value: 'income', label: 'Income', icon: 'tabler-cash' },
  { value: 'spending', label: 'Spending', icon: 'tabler-cash-off' },
  { value: 'projects', label: 'Projects', icon: 'tabler-briefcase' },
  { value: 'activity', label: 'Activity', icon: 'tabler-history' }
]

// Fetch dashboard data
const fetchDashboardData = async () => {
  isLoading.value = true
  try {
    const params = { period: selectedPeriod.value }
    if (selectedPeriod.value === 'custom' && customDateRange.value) {
      params.date_range = customDateRange.value
    }
    
    const response = await $api('/dashboard', {
      method: 'GET',
      params
    })
    
    dashboardData.value = response.data
  } catch (error) {
    console.error('Error fetching dashboard data:', error)
  } finally {
    isLoading.value = false
  }
}

// Period options
const periodOptions = [
  { label: 'Week', value: 'week', icon: 'tabler-calendar-week' },
  { label: 'Month', value: 'month', icon: 'tabler-calendar-month' },
  { label: 'Quarter', value: 'quarter', icon: 'tabler-calendar-stats' },
  { label: 'Year', value: 'year', icon: 'tabler-calendar' },
  { label: 'Custom', value: 'custom', icon: 'tabler-calendar-due' },
]

// Handle period change
const changePeriod = async (period) => {
  selectedPeriod.value = period
  if (period === 'custom') {
    showDatePicker.value = true
  } else {
    await fetchDashboardData()
  }
}

// Navigate to container
const navigateToContainer = (containerId) => {
  router.push({ name: 'kanban', params: { id: containerId } })
}

// Watch for date range changes
watch(customDateRange, async () => {
  if (selectedPeriod.value === 'custom' && customDateRange.value) {
    await fetchDashboardData()
  }
})

// Lifecycle hooks
onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <div class="dashboard-container">
    <DashboardHeader
      v-model:selectedPeriod="selectedPeriod"
      v-model:customDateRange="customDateRange"
      :period-options="periodOptions"
      :is-loading="isLoading"
      @period-change="changePeriod"
    />
    
    <VProgressLinear
      v-if="isLoading"
      indeterminate
      color="primary"
    />
    
    <div v-else class="dashboard-content">
      <DashboardTabs
        v-model:activeTab="activeTab"
        :tabs="tabs"
      />
      
      <div class="tab-content">
        <!-- Overview Tab -->
        <template v-if="activeTab === 'overview'">
          <FinancialSummary
            :income="dashboardData?.income"
            :spending="dashboardData?.spending"
            :is-loading="isLoading"
          />
          
          <ActivityTimeline
            :activities="dashboardData?.recent_activity"
            :limit="5"
          />
        </template>
        
        <!-- Income Tab -->
        <IncomeTable
          v-if="activeTab === 'income'"
          :containers="dashboardData?.income?.containers"
          :totals="dashboardData?.income"
          :is-loading="isLoading"
          @navigate="navigateToContainer"
        />
        
        <!-- Spending Tab -->
        <SpendingTable
          v-if="activeTab === 'spending'"
          :containers="dashboardData?.spending?.containers"
          :totals="dashboardData?.spending"
          :is-loading="isLoading"
          @navigate="navigateToContainer"
        />
        
        <!-- Projects Tab -->
        <ProjectsGrid
          v-if="activeTab === 'projects'"
          :projects="dashboardData?.active_projects"
          :is-loading="isLoading"
          @navigate="navigateToContainer"
        />
        
        <!-- Activity Tab -->
        <ActivityTimeline
          v-if="activeTab === 'activity'"
          :activities="dashboardData?.recent_activity"
          :is-loading="isLoading"
        />
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import '@styles/dashboard-style.scss';
</style>