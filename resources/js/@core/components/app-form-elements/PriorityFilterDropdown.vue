<template>
  <div class="relative inline-block text-left">
    <button
      ref="btn"
      class="custom-badge github-style-badge"
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
      class="dropdown-menu github-style-dropdown hidden"
    >
      <div class="dropdown-header">
        Priority
      </div>

      <button
        class="dropdown-item"
        :class="{ 'is-selected': modelValue.includes('unflagged') }"
        @click="toggleUnflagged"
      >
        <div class="priority-icon unflagged">
          <VIcon size="14">tabler-flag-off</VIcon>
        </div>
        <span class="priority-name">Unflagged</span>
        <VIcon 
          v-if="modelValue.includes('unflagged')"
          size="16" 
          icon="tabler-check" 
          color="accent"
          class="ms-auto"
        />
      </button>

      <VDivider />

      <button
        v-for="(label, key) in Priority.data"
        :key="key"
        class="dropdown-item"
        :class="{ 'is-selected': modelValue.includes(key) }"
        @click="selectPriority(key)"
      >
        <div class="priority-icon" :style="{ backgroundColor: `${getPriorityColor(key)}15` }">
          <VIcon
            size="14"
            :color="getPriorityColor(key)"
          >
            tabler-flag-3-filled
          </VIcon>
        </div>
        <span class="priority-badge" :style="{ backgroundColor: `${getPriorityColor(key)}15`, color: getPriorityColor(key) }">
          {{ label }}
        </span>
        <VIcon 
          v-if="modelValue.includes(key)"
          size="16" 
          icon="tabler-check" 
          color="accent"
          class="ms-auto"
        />
      </button>

      <VDivider />

      <div
        class="dropdown-item clear-action"
        @click="clearSelection"
      >
        <VIcon
          size="16"
          class="me-2"
        >
          tabler-circle-off
        </VIcon>
        <span>Clear all filters</span>
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

<style lang="scss">
@import './shared-github-styles.scss';

.dropdown-menu.github-style-dropdown {
  .priority-icon {
    width: 20px;
    height: 20px;
    border-radius: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    
    &.unflagged {
      background-color: #f0f0f0;
      color: map-get($github-colors, text-tertiary);
    }
  }
  
  .priority-name {
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 400;
    letter-spacing: 0.01em;
  }
  
  .priority-badge {
    display: inline-flex;
    align-items: center;
    padding: 1px 6px;
    font-size: 12px;
    font-weight: 400;
    border-radius: 3px;
    white-space: nowrap;
    letter-spacing: 0.01em;
    border: 1px solid transparent;
  }
}
</style>
