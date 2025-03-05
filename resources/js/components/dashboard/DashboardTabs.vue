<script setup>
const props = defineProps({
  activeTab: String,
  tabs: Array
})

const emit = defineEmits(['update:activeTab'])
</script>

<template>
  <div class="dashboard-tabs">
    <VBtn
      v-for="tab in tabs"
      :key="tab.value"
      :color="activeTab === tab.value ? 'primary' : undefined"
      :variant="activeTab === tab.value ? 'tonal' : 'text'"
      size="small"
      class="tab-btn"
      @click="$emit('update:activeTab', tab.value)"
    >
      <VIcon 
        size="16" 
        class="mr-1"
        :color="activeTab === tab.value ? undefined : '#57606a'"
      >
        {{ tab.icon }}
      </VIcon>
      {{ tab.label }}
    </VBtn>
  </div>
</template> 