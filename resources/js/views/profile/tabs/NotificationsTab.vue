<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'

const toast = useToast()
const loading = ref(false)
const preferences = ref({
  activities_enabled: false,
  activities_frequency: 'daily',
  member_report_enabled: false,
  owner_report_enabled: false,
  daily_report_time: '00:00'
})

const frequencyOptions = [
  { title: 'Every 4 hours', value: '4_hours' },
  { title: 'Every 8 hours', value: '8_hours' },
  { title: 'Daily', value: 'daily' }
]

onMounted(async () => {
  try {
    const response = await $api('/user/notification-preferences')
    preferences.value = response.data
  } catch (error) {
    console.error('Failed to load preferences:', error)
  }
})

const savePreferences = async () => {
  loading.value = true
  try {
    await $api('/user/notification-preferences', {
      method: 'POST',
      body: preferences.value
    })
    toast.success('Notification preferences updated')
  } catch (error) {
    toast.error('Failed to update preferences')
    console.error(error)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <VCard>
    <VCardText>
      <h3 class="text-h6 mb-4">
        Notification Preferences
      </h3>

      <VDivider class="mb-4" />

      <!-- Activities Notifications -->
      <div class="mb-6">
        <h4 class="text-h6 mb-2">
          Activity Notifications
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Get notified about task assignments, mentions, and comments
        </p>

        <VSwitch
          v-model="preferences.activities_enabled"
          label="Enable activity notifications"
          color="primary"
          hide-details
          class="mb-4"
        />

        <!-- <VSelect
          v-if="preferences.activities_enabled"
          v-model="preferences.activities_frequency"
          :items="frequencyOptions"
          item-title="title"
          item-value="value"
          label="Notification Frequency"
          variant="outlined"
          density="comfortable"
          class="max-w-xs"
        /> -->
      </div>

      <VDivider class="mb-4" />

      <!-- Daily Reports -->
      <div class="mb-6">
        <h4 class="text-h6 mb-2">
          Daily Reports
        </h4>
        
        <VSwitch
          v-model="preferences.member_report_enabled"
          label="Receive daily activity report as a member"
          color="primary"
          hide-details
          class="mb-4"
        />

        <VSwitch
          v-model="preferences.owner_report_enabled"
          label="Receive daily team activity report as an owner"
          color="primary"
          hide-details
          class="mb-4"
        />

        <!-- <div class="max-w-xs">
          <AppDateTimePicker
            v-if="preferences.member_report_enabled || preferences.owner_report_enabled"
            v-model="preferences.daily_report_time"
            label="Report Time"
            placeholder="Select time"
            variant="outlined"
          density="comfortable"
          class="max-w-xs"
          :config="{ 
            enableTime: true, 
            noCalendar: true, 
            dateFormat: 'H:i',
            altFormat: 'h:i K',
            altInput: true,
            time_24hr: false
          }"
          />
        </div> -->
      </div>

      <div class="d-flex justify-end">
        <VBtn
          color="primary"
          :loading="loading"
          @click="savePreferences"
        >
          Save Preferences
        </VBtn>
      </div>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.max-w-xs {
  max-width: 300px;
}
</style> 