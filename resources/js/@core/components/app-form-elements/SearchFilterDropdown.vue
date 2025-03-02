<template>
  <div class="relative inline-block text-left">
    <button
      ref="btn"
      class="github-style-badge"
      :class="{ 'has-selections': modelValue }"
    >
      <VIcon
        size="16"
        class="icon"
        :color="modelValue ? 'primary' : ''"
      >
        tabler-search
      </VIcon>

      <span 
        v-if="modelValue" 
        class="selection-indicator"
      >
        <VIcon
          size="12"
          icon="tabler-circle-check"
        />
      </span>
    </button>

    <div
      ref="dropdown"
      class="dropdown-menu github-style-dropdown hidden"
    >
      <div class="dropdown-header">
        <VIcon
          size="14"
          icon="tabler-search"
        />
        Search by ID or title
      </div>
      <div class="dropdown-search">
        <AppTextField
          v-model="search"
          placeholder="Search..."
          class="search-field"
          prepend-inner-icon="tabler-search"
          dense
          outlined
          clearable
          @click:clear="onClear"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import tippy from "tippy.js"
import { watchDebounced } from '@vueuse/core'

const props = defineProps({
  modelValue: { type: String, default: () => "" },
})

const emit = defineEmits(["update:modelValue"])

const btn = ref(null)
const dropdown = ref(null)
const search = ref("")

watchDebounced(
  search,
  () => { emit("update:modelValue", search.value) },
  { debounce: 500, maxWait: 1000 },
)

const onClear = () => {
  search.value = ""
}

onMounted(() => {
  tippy(btn.value, {
    content: dropdown.value,
    interactive: true,
    placement: "bottom",
    trigger: "click",
    onShow() {
      search.value = props.modelValue
    },
  })
})
</script>

<style lang="scss" scoped>
@import './shared-github-styles.scss';

.github-style-badge {
  &.has-selections {
    position: relative;
    
    .selection-indicator {
      position: absolute;
      top: -4px;
      right: -4px;
      background: #6366f1;
      color: white;
      border-radius: 20px;
      padding: 0 !important;
      font-size: 8px !important;
      font-weight: 600;
      min-width: 10px !important;
      height: 12px !important;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid white;
    }
  }
}
.dropdown-search {
  padding: 8px 12px;
  border-bottom: 1px solid map-get($github-colors, border-subtle);
  
  .search-field {
    :deep(.v-field) {
      border-radius: 6px;
      
      &.v-field--focused {
        .v-field__outline {
          --v-field-border-width: 1px;
          border-color: map-get($github-colors, accent);
        }
      }
      
      .v-field__input {
        font-size: 13px;
        min-height: 32px;
        padding-top: 0;
        padding-bottom: 0;
      }
      
      .v-field__append-inner {
        padding-inline-start: 8px;
        color: rgba(0, 0, 0, 0.38);
      }
    }
  }
}
</style>
