<script setup>
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  showBackButton: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['cancel', 'back'])
</script>

<template>
  <div class="drawer-header">
    <div class="d-flex justify-space-between align-center px-4 py-3">
      <div class="d-flex align-center gap-2">
        <VBtn
          v-if="showBackButton"
          icon
          variant="text"
          size="small"
          @click="$emit('back')"
        >
          <VIcon size="20">tabler-arrow-left</VIcon>
        </VBtn>
        
        <h2 class="text-h6 font-weight-medium mb-0">{{ title }}</h2>
      </div>
      
      <div class="d-flex gap-2">
        <slot name="beforeClose" />
        
        <VBtn
          icon
          variant="text"
          size="small"
          @click="$emit('cancel')"
        >
          <VIcon size="20">tabler-x</VIcon>
        </VBtn>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.drawer-header {
  background-color: #fff;
  border-bottom: 1px solid #e5e7eb;
  
  .text-h6 {
    font-size: 1.125rem;
    line-height: 1.75rem;
    font-weight: 500;
    color: #111827;
  }
  
  :deep(.v-btn--icon) {
    color: #4b5563;
    
    &:hover {
      background-color: #f3f4f6;
      color: #111827;
    }
  }
}
</style>
