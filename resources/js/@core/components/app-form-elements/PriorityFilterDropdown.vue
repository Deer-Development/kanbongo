<template>
  <div class="relative inline-block text-left">
    <button
      ref="btn"
      class="custom-badge"
    >
      <VIcon
        size="16"
        class="icon"
        :color="modelValue.length ? 'primary' : ''"
      >
        tabler-flag
      </VIcon>
    </button>

    <div
      ref="dropdown"
      class="dropdown-menu hidden"
    >
      <div class="dropdown-header">
        Priority
      </div>

      <button
        class="dropdown-item"
        :class="{ 'is-selected': modelValue.includes('unflagged') }"
        @click="toggleUnflagged"
      >
        <VIcon
          size="18"
          :icon="modelValue.includes('unflagged') ? 'tabler-user-x' : 'tabler-user'"
          class="user-icon"
        />
        Unflagged
      </button>

      <VDivider />

      <button
        v-for="(label, key) in Priority.data"
        :key="key"
        class="dropdown-item"
        :class="{ 'is-selected': modelValue.includes(key) }"
        @click="selectPriority(key)"
      >
        <VIcon
          size="16"
          :color="getPriorityColor(key)"
          class="user-icon"
        >
          tabler-flag-3-filled
        </VIcon>
        <span>{{ label }}</span>
      </button>

      <VDivider />

      <div
        class="dropdown-item priority-clear"
        @click="clearSelection"
      >
        <VIcon
          left
          size="16"
          color="gray"
        >
          tabler-circle-off
        </VIcon>
        <span>Clear</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits, onMounted } from "vue"
import tippy from "tippy.js"
import "tippy.js/animations/shift-away.css"

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
})

const emit = defineEmits(["update:modelValue"])

const btn = ref(null)
const dropdown = ref(null)

const Priority = {
  URGENT: 1,
  HIGH: 2,
  NORMAL: 3,
  LOW: 4,
  data: {
    1: 'Urgent',
    2: 'High',
    3: 'Normal',
    4: 'Low',
  },
}

const getPriorityColor = priority => {
  if (priority == Priority.URGENT)
    return '#FF5733'
  if (priority == Priority.HIGH)
    return '#FFA533'
  if (priority == Priority.NORMAL)
    return '#338DFF'
  if (priority == Priority.LOW)
    return '#30c15a'

  return '#d2d2d5'
}

const selectPriority = priority => {
  const selected = [...props.modelValue]
  const index = selected.indexOf(priority)

  if (index === -1) {
    selected.push(priority)
  } else {
    selected.splice(index, 1)
  }

  emit("update:modelValue", selected)
}

const toggleUnflagged = () => {
  const selected = [...props.modelValue]
  const index = selected.indexOf("unflagged")

  if (index === -1) {
    selected.push("unflagged")
  } else {
    selected.splice(index, 1)
  }

  emit("update:modelValue", selected)
}

const clearSelection = () => {
  emit("update:modelValue", [])
}

onMounted(() => {
  tippy(btn.value, {
    content: dropdown.value,
    interactive: true,
    placement: "bottom-start",
    animation: "shift-away",
    trigger: "click",
    arrow: false,
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
  color: #0969da;
  font-weight: 600;
}

.no-result {
  padding: 12px;
  text-align: center;
  color: #57606a;
  font-size: 14px;
}
</style>
