<template>
  <VMenu
    v-model="priorityMenu"
    offset-y
  >
    <template #activator="{ props }">
      <div
        v-bind="props"
        class="priority-badge"
      >
        <VIcon
          left
          size="16"
          :color="getPriorityColor(priority)"
        >
          tabler-flag-3-filled
        </VIcon>
      </div>
    </template>
    <div class="priority-options">
      <div
        v-for="(label, key) in Priority.data"
        :key="key"
        class="priority-option"
        @click="setPriority(key)"
      >
        <VIcon
          size="16"
          :color="getPriorityColor(key)"
        >
          tabler-flag-3-filled
        </VIcon>
        <span>{{ label }}</span>
      </div>
      <VDivider />
      <div
        class="priority-clear"
        @click="setPriority(0)"
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
  </VMenu>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue'

const props = defineProps({
  priority: { type: [Number, null], required: false },
  itemId: { type: Number, required: false },
})

const emit = defineEmits(['updatePriority'])

const priorityMenu = ref(null)

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

const setPriority = priority => {
  emit('updatePriority', { itemId: props.itemId, priority })
}
</script>

<style lang="scss" scoped>
.priority-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: background-color 0.2s ease;

  &:hover {
    background-color: rgba(0, 0, 0, 0.04);
  }
}

.priority-options {
  min-width: 150px;
  padding: 8px 0;
  background: white;
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.priority-option, .priority-clear {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  cursor: pointer;
  transition: background-color 0.2s ease;

  &:hover {
    background-color: rgba(0, 0, 0, 0.04);
  }

  span {
    font-size: 0.875rem;
  }
}

.priority-clear {
  color: #666;
}
</style>
