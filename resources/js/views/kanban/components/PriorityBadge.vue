<template>
  <VMenu v-model="priorityMenu" offset-y>
    <template #activator="{ props }">
      <div class="custom-badge" v-bind="props">
        <VIcon left size="16" :color="getPriorityColor(priority)">tabler-flag-3-filled</VIcon>
        <span>{{ priority ? Priority.data[priority] : 'Priority' }}</span>
      </div>
    </template>
    <div class="priority-options">
      <div
        v-for="(label, key) in Priority.data"
        :key="key"
        class="priority-option"
        @click="setPriority(key)"
      >
        <VIcon size="16" :color="getPriorityColor(key)">tabler-flag-3-filled</VIcon>
        <span>{{ label }}</span>
      </div>
      <VDivider />
      <div class="priority-clear" @click="setPriority(0)">
        <VIcon left size="16" color="gray">tabler-circle-off</VIcon>
        <span>Clear</span>
      </div>
    </div>
  </VMenu>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue';

const props = defineProps({
  priority: { type: Number, required: true },
  itemId: { type: Number, required: true },
});

const emit = defineEmits(['update-priority']);

const priorityMenu = ref(null);

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
};

const getPriorityColor = (priority) => {
  if (priority == Priority.URGENT)
    return '#FF5733'
  if (priority == Priority.HIGH)
    return '#FFA533'
  if (priority == Priority.NORMAL)
    return '#338DFF'
  if (priority == Priority.LOW)
    return '#30c15a'

  return '#d2d2d5'
};

const setPriority = (priority) => {
  emit('update-priority', { itemId: props.itemId, priority });
};
</script>
