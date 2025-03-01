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
        tabler-tag
      </VIcon>
    </button>

    <div
      ref="dropdown"
      class="dropdown-menu github-style-dropdown hidden"
    >
      <div class="dropdown-header">
        Tags
      </div>
      <button
        class="dropdown-item"
        :class="{ 'is-selected': modelValue.includes('untagged') }"
        @click="toggleUntagged"
      >
        <div class="tag-icon untagged">
          <VIcon size="14">tabler-tag-off</VIcon>
        </div>
        <span class="tag-name">Untagged</span>
        <VIcon 
          v-if="modelValue.includes('untagged')"
          size="16" 
          icon="tabler-check" 
          color="accent"
          class="ms-auto"
        />
      </button>

      <VDivider />

      <template v-if="tags.length">
        <button
          v-for="tag in tags"
          :key="tag.id"
          class="dropdown-item"
          :class="{ 'is-selected': modelValue.includes(tag.id) }"
          @click="selectTag(tag)"
        >
          <div class="tag-icon" :style="{ backgroundColor: `${tag.color}33` }">
            <VIcon size="14" :color="tag.color">tabler-tag</VIcon>
          </div>
          <span 
            class="tag"
            :style="{ backgroundColor: `${tag.color}15`, color: tag.color }"
          >
            {{ tag.name }}
          </span>
          <VIcon 
            v-if="modelValue.includes(tag.id)"
            size="16" 
            icon="tabler-check" 
            color="accent"
            class="ms-auto"
          />
        </button>
        <VDivider />
        <div
          class="dropdown-item clear-action"
          @click="emit('update:modelValue', [])"
        >
          <VIcon
            size="16"
            class="me-2"
          >
            tabler-circle-off
          </VIcon>
          <span>Clear all filters</span>
        </div>
      </template>
      <div
        v-else
        class="dropdown-empty"
      >
        <VIcon size="16" icon="tabler-tag-off" class="me-2" />
        No tags available
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
const tags = ref([])
const route = useRoute()

const toggleUntagged = () => {
  const selected = [...props.modelValue]
  const index = selected.indexOf("untagged")

  if (index === -1) {
    selected.push("untagged")
  } else {
    selected.splice(index, 1)
  }

  emit("update:modelValue", selected)
}

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

<style lang="scss">
@import './shared-github-styles.scss';

.dropdown-menu.github-style-dropdown {
  .tag-icon {
    width: 20px;
    height: 20px;
    border-radius: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    
    &.untagged {
      background-color: #f0f0f0;
      color: map-get($github-colors, text-tertiary);
    }
  }
  
  .tag-name {
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 400;
    letter-spacing: 0.01em;
  }
  
  .tag {
    display: inline-flex;
    align-items: center;
    padding: 1px 6px;
    font-size: 12px;
    font-weight: 400;
    border-radius: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
    letter-spacing: 0.01em;
    border: 1px solid transparent;
  }
}
</style>
