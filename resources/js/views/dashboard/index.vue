<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardHeader from '@/components/dashboard/DashboardHeader.vue'
import DashboardTabs from '@/components/dashboard/DashboardTabs.vue'
import FinancialSummary from '@/components/dashboard/FinancialSummary.vue'
import ActivityTimeline from '@/components/dashboard/ActivityTimeline.vue'
import IncomeTable from '@/components/dashboard/IncomeTable.vue'
import SpendingTable from '@/components/dashboard/SpendingTable.vue'
import ProjectsGrid from '@/components/dashboard/ProjectsGrid.vue'
import ActiveUsersList from '@/components/dashboard/ActiveUsersList.vue'

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
  recent_activity: [],
  active_users: []
})

const selectedPeriod = ref('current_month')
const customDateRange = ref(null)
const showDatePicker = ref(false)

// Tabs
const activeTab = ref('overview')
const hasOwnedContainers = computed(() => {
  return dashboardData.value?.spending?.containers?.length > 0
})

const tabs = computed(() => {
  const defaultTabs = [
    { value: 'overview', label: 'Overview', icon: 'tabler-dashboard' },
    { value: 'income', label: 'Income', icon: 'tabler-cash' },
    { value: 'projects', label: 'Projects', icon: 'tabler-briefcase' },
    { value: 'activity', label: 'Activity', icon: 'tabler-history' }
  ]
  
  // Add spending tab only if user has owned containers
  if (hasOwnedContainers.value) {
    defaultTabs.splice(2, 0, { value: 'spending', label: 'Spending', icon: 'tabler-cash-off' })
  }
  
  return defaultTabs
})

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

const activeUsers = computed(() => {
  return dashboardData.value.active_projects
    ?.flatMap(project => project.active_users || [])
    ?.sort((a, b) => new Date(b.started_at) - new Date(a.started_at))
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
          <VContainer fluid class="pa-0">
            <VRow>
              <VCol cols="12">
                <FinancialSummary
                  :income="dashboardData?.income"
                  :spending="dashboardData?.spending"
                  :is-loading="isLoading"
                  :has-owned-containers="hasOwnedContainers"
                />
              </VCol>
              <VCol cols="12" lg="6">
                <ActiveUsersList
                  :users="activeUsers"
                />
              </VCol>
              <VCol cols="12" lg="6">
                <ActivityTimeline
                  :activities="dashboardData?.recent_activity"
                  :limit="5"
                />
              </VCol>
            </VRow>
          </VContainer>
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
        <template v-if="activeTab === 'spending' && hasOwnedContainers">
          <SpendingTable
            :containers="dashboardData?.spending?.containers"
            :totals="dashboardData?.spending"
            :is-loading="isLoading"
            @navigate="navigateToContainer"
          />
        </template>
        
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

.empty-state {
  padding: 2rem;
  
  .empty-card {
    background: #f6f8fa;
    border: 1px solid #d0d7de;
    
    .empty-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 3rem 2rem;
      
      .empty-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #24292f;
        margin: 0 0 0.5rem;
      }
      
      .empty-description {
        font-size: 0.875rem;
        color: #57606a;
        margin: 0;
        max-width: 400px;
      }
    }
  }
}
</style>