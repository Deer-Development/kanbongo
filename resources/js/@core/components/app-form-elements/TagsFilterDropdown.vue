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
        tabler-tag
      </VIcon>
    </button>

    <div
      ref="dropdown"
      class="dropdown-menu hidden"
    >
      <div class="dropdown-header">
        Tags
      </div>
      <template v-if="tags.length">
        <button
          v-for="tag in tags"
          :key="tag.id"
          class="dropdown-item"
          :class="{ 'is-selected': modelValue.includes(tag.id) }"
          :style="modelValue.includes(tag.id) ? { color: `${tag.color}73` } : ''"
          @click="selectTag(tag)"
        >
          <VIcon
            left
            size="18"
            class="user-icon"
            :icon="modelValue.includes(tag.id) ? 'tabler-tag-filled' : 'tabler-tag'"
          />
          <span
            class="tag"
            :style="{ backgroundColor: `${tag.color}73` }"
          >
            {{ tag.name }}
          </span>
        </button>
        <VDivider />
        <div
          class="dropdown-item priority-clear"
          @click="emit('update:modelValue', [])"
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
      </template>
      <div
        v-else
        class="no-result"
      >
        No result
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import tippy from "tippy.js"

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
})

const emit = defineEmits(["update:modelValue"])

const btn = ref(null)
const dropdown = ref(null)
const selectedTags = ref([])
const tags = ref([])
const route = useRoute()

const selectTag = tag => {
  const selected = [...props.modelValue]
  const index = selected.indexOf(tag.id)
  if (index === -1) {
    selected.push(tag.id)
  } else {
    selected.splice(index, 1)
  }
  emit("update:modelValue", selected)
}

const fetchContainerTags = async () => {
  const res = await $api(`/container/tags/${route.params.containerId}`, {
    method: "GET",
  })

  tags.value = res.data
}

onMounted(() => {
  tippy(btn.value, {
    content: dropdown.value,
    interactive: true,
    placement: "bottom",
    trigger: "click",
    onShow() {
      fetchContainerTags()
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
