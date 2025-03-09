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
  <div class="drawer-header github-style-header">
    <div class="d-flex justify-space-between align-center px-4 py-3">
      <div class="d-flex align-center gap-2 header-left">
        <VBtn
          v-if="showBackButton"
          icon
          variant="text"
          size="small"
          class="back-button flex-shrink-0"
          @click="$emit('back')"
        >
          <VIcon size="18">tabler-arrow-left</VIcon>
        </VBtn>
        
        <h2 class="text-h6 font-weight-medium mb-0 header-title text-truncate">{{ title }}</h2>
      </div>
      
      <div class="d-flex gap-2 header-actions flex-shrink-0">
        <slot name="beforeClose" />
        
        <VBtn
          icon
          variant="text"
          size="small"
          class="close-button"
          @click="$emit('cancel')"
        >
          <VIcon size="18">tabler-x</VIcon>
        </VBtn>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
$github-colors: (
  text-primary: #24292f,
  text-secondary: #57606a,
  border: #d0d7de,
  bg-primary: #ffffff,
  bg-secondary: #f6f8fa,
  accent: #0969da,
  hover: #f3f4f6,
  shadow: rgba(31, 35, 40, 0.07)
);

.drawer-header.github-style-header {
  background-color: map-get($github-colors, bg-primary);
  border-bottom: 1px solid map-get($github-colors, border);
  position: sticky;
  top: 0;
  z-index: 10;
  
  .header-left {
    flex: 1;
    min-width: 0;
  }
  
  .header-title {
    font-size: 16px;
    line-height: 24px;
    font-weight: 600;
    color: map-get($github-colors, text-primary);
    margin: 0;
    min-width: 0;
    max-width: 100%;
  }
  
  .header-actions {
    margin-left: 8px;
  }
  
  .back-button, .close-button {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    color: map-get($github-colors, text-secondary);
    
    &:hover {
      background-color: map-get($github-colors, hover);
      color: map-get($github-colors, text-primary);
    }
    
    .v-icon {
      font-size: 18px;
    }
  }
  
  :deep(.v-btn--icon) {
    color: map-get($github-colors, text-secondary);
    border-radius: 6px;
    
    &:hover {
      background-color: map-get($github-colors, hover);
      color: map-get($github-colors, text-primary);
    }
  }
}

// Keep original styling for non-GitHub style
.drawer-header:not(.github-style-header) {
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
