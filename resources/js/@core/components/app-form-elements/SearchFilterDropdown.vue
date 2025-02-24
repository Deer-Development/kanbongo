<template>
  <div class="relative inline-block text-left">
    <button
      ref="btn"
      class="custom-badge"
    >
      <VIcon
        size="16"
        class="icon"
        :color="modelValue ? 'primary' : ''"
      >
        tabler-search
      </VIcon>
    </button>

    <div
      ref="dropdown"
      class="dropdown-menu hidden"
    >
      <div class="dropdown-header">
        Search
      </div>
      <AppTextField
        v-model="search"
        placeholder="Search..."
        class="p-2 search-input"
        hint="Will search for tasks ID and title"
        dense
        outlined
        clearable
        hide-details
        @click:clear="onClear"
      />
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

<style scoped>
.dropdown-menu {
  background: white;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(27, 31, 36, 0.15);
  border: 1px solid #d0d7de;
  overflow: hidden;
  z-index: 10;
  min-width: 220px;
}

.dropdown-header {
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #57606a;
  border-bottom: 1px solid #d0d7de;
  background: #f6f8fa;
}

.tag {
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 8px;
  font-size: 12px;
  font-weight: 500;
  border-radius: 4px;
  background: rgba(241, 243, 245, 0.7);
  color: #333;
  border: 1px solid #d0d7de;
  gap: 2px;
  cursor: pointer;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  font-size: 14px;
  transition: background 0.2s;
  color: #24292e;
  cursor: pointer;
}

.dropdown-item:hover {
  background: #f6f8fa;
}

.is-selected {
  background: rgba(9, 105, 218, 0.1);
  font-weight: 600;
}

.no-result {
  padding: 12px;
  text-align: center;
  color: #57606a;
  font-size: 14px;
}
</style>
