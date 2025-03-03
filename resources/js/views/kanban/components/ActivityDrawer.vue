<script setup>
import BoardActivities from './BoardActivities.vue'
import { ref } from 'vue'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  boardId: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits([
  "update:isDrawerOpen",
  "refreshKanbanData",
])

const boardActivitiesRef = ref(null)

const handleDrawerModelValueUpdate = val => {
  emit("update:isDrawerOpen", val)
}

const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false)
  emit("refreshKanbanData")
}

defineExpose({ 
  fetchActivities: () => {
    if (boardActivitiesRef.value) {
      boardActivitiesRef.value.fetchActivities()
    }
  }
})
</script>

<template>
  <VNavigationDrawer
    :model-value="isDrawerOpen"
    temporary
    location="right"
    width="750"
    class="activities-drawer"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <div class="drawer-header">
      <div class="d-flex justify-space-between align-center px-4 py-3">
        <h2 class="text-h6 font-weight-medium mb-0">Board Activities</h2>
        <div class="d-flex gap-2">
          <VBtn
            icon
            variant="text"
            size="small"
            @click="boardActivitiesRef?.fetchActivities()"
          >
            <VIcon>tabler-refresh</VIcon>
          </VBtn>
          <VBtn
            icon
            variant="text"
            size="small"
            @click="closeNavigationDrawer"
          >
            <VIcon>tabler-x</VIcon>
          </VBtn>
        </div>
      </div>
      <VDivider />
    </div>

    <div class="drawer-content">
      <BoardActivities
        ref="boardActivitiesRef"
        :board-id="boardId"
        :is-active="isDrawerOpen"
      />
    </div>
  </VNavigationDrawer>
</template>

<style lang="scss" scoped>
.activities-drawer {
  display: flex;
  flex-direction: column;
  height: 100%;

  .drawer-header {
    background-color: #fff;
    border-bottom: 0;
    flex-shrink: 0;
  }

  .drawer-content {
    flex-grow: 1;
    overflow: hidden;
    height: calc(100% - 64px);
  }
}
</style> 