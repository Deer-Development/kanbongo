<template>
  <div class="relative inline-block text-left">
    <button
      ref="btn"
      class="github-style-badge"
      :class="{ 'has-selections': modelValue.length }"
    >
      <VIcon
        :size="modelValue.length ? 12 : 16"
        class="icon"
        :color="modelValue.length ? 'primary' : ''"
      >
        tabler-flag
      </VIcon>

      <span 
        v-if="modelValue.length" 
        class="selection-indicator"
      >
        {{ modelValue.length }}
      </span>
    </button>

    <div
      ref="dropdown"
      class="dropdown-menu github-style-dropdown hidden"
    >
      <div class="dropdown-header">
        <VIcon 
          size="14" 
          icon="tabler-flag" 
          class="header-icon"
        />
        Filter by priority
      </div>

      <div class="dropdown-content">
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

        <template v-if="Priority.data">
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

          <button
            v-if="modelValue.length"
            class="dropdown-item clear-action"
            @click="clearSelection"
          >
            <VIcon
              size="14"
              class="clear-icon"
            >
              tabler-x
            </VIcon>
            <span>Clear priority filter</span>
          </button>
        </template>

        <div
          v-else
          class="dropdown-empty"
        >
          <VIcon 
            size="20" 
            icon="tabler-flag-off" 
            class="empty-icon"
          />
          <p>No matching priority found</p>
        </div>
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

.github-style-badge {
  &.has-selections {
    position: relative;
    
    .selection-indicator {
      position: absolute;
      top: -4px;
      right: -4px;
      background: #637ff1;
      color: white;
      border-radius: 10px;
      padding: 0 3px;
      font-size: 8px;
      font-weight: 600;
      min-width: 12px;
      height: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid white;
    }
  }
}

.dropdown-menu.github-style-dropdown {
  .dropdown-content {
    padding: 0 !important;

    .dropdown-item {
      display: flex;
      align-items: center;
      width: 100%;
      transition: all 0.2s ease;
      border-bottom: 0 !important;

      &.clear-action {
        margin-top: 0 !important;
      }
    }
  }
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
