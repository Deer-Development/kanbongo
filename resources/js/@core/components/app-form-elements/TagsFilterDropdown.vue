<template>
  <div class="relative inline-block text-left">
    <button
      ref="btn"
      class="github-style-badge"
      :class="{ 'has-selections': modelValue.length }"
    >
      <VIcon
        size="16"
        class="icon"
      >
        {{ modelValue.length ? 'tabler-tags' : 'tabler-tag' }}
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
          icon="tabler-tag" 
          class="header-icon"
        />
        Filter by tag
      </div>

      <div class="dropdown-search">
        <VTextField
          v-model="searchQuery"
          density="compact"
          placeholder="Filter tags..."
          variant="outlined"
          hide-details
          clearable
          class="search-field"
        >
        <template #prepend-inner>
            <VIcon size="16" icon="tabler-search" />
          </template>
        </VTextField>
      </div>

      <div class="dropdown-content">
        <button
          class="dropdown-item"
          :class="{ 'is-selected': modelValue.includes('untagged') }"
          :style="{ '--tag-color': '#6366f1' }"
          @click="toggleUntagged"
        >
          <div class="tag-icon untagged">
            <VIcon size="12">tabler-tag-off</VIcon>
          </div>
          <span class="tag-name">Untagged</span>
          <VIcon 
            v-if="modelValue.includes('untagged')"
            size="14" 
            icon="tabler-check" 
            class="check-icon"
          />
        </button>

        <template v-if="filteredTags.length">
          <button
            v-for="tag in filteredTags"
            :key="tag.id"
            :data-tag-id="tag.id"
            class="dropdown-item"
            :class="{ 'is-selected': modelValue.includes(tag.id) }"
            :style="{ '--tag-color': tag.color }"
            @click="selectTag(tag)"
          >
            <div 
              class="tag-icon" 
              :style="{ backgroundColor: `${tag.color}15` }"
            >
              <VIcon 
                size="12" 
                :color="tag.color"
              >
                tabler-tag
              </VIcon>
            </div>
            <div 
              class="tag-badge"
              :style="{
                backgroundColor: `${tag.color}15`,
                color: tag.color,
                borderColor: `${tag.color}30`
              }"
            >
              {{ tag.name }}
            </div>
            <VIcon 
              v-if="modelValue.includes(tag.id)"
              size="14" 
              icon="tabler-check"
              class="check-icon"
              :color="tag.color"
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
            <span>Clear tag filter</span>
          </button>
        </template>

        <div
          v-else
          class="dropdown-empty"
        >
          <VIcon 
            size="20" 
            icon="tabler-tags-off" 
            class="empty-icon"
          />
          <p>No matching tags found</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits, onMounted, computed, nextTick } from "vue"
import tippy from "tippy.js"
import "tippy.js/animations/shift-away.css"

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
})

const emit = defineEmits(["update:modelValue"])

const btn = ref(null)
const dropdown = ref(null)
const tags = ref([])
const route = useRoute()

// Add search functionality
const searchQuery = ref('')

// Add computed property for filtered tags
const filteredTags = computed(() => {
  if (!searchQuery.value) return tags.value
  
  const query = searchQuery.value.toLowerCase()
  return tags.value.filter(tag => 
    tag.name.toLowerCase().includes(query)
  )
})

const selectTag = tag => {
  const selected = [...props.modelValue]
  const index = selected.indexOf(tag.id)
  
  if (index === -1) {
    selected.push(tag.id)
  } else {
    selected.splice(index, 1)
  }
  
  // Set CSS variables for the selected tag's colors
  nextTick(() => {
    const items = document.querySelectorAll('.dropdown-item')
    items.forEach(item => {
      const tagId = item.getAttribute('data-tag-id')
      if (tagId) {
        const tag = tags.value.find(t => t.id === tagId)
        if (tag) {
          item.style.setProperty('--tag-color', tag.color)
          item.style.setProperty('--tag-bg-color', `${tag.color}15`)
        }
      }
    })
  })
  
  emit("update:modelValue", selected)
}

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

const clearSelection = () => {
  emit("update:modelValue", [])
}

const fetchContainerTags = async () => {
  try {
    const res = await $api(`/container/tags/${route.params.containerId}`)
    tags.value = res.data
  } catch (error) {
    console.error('Failed to fetch tags:', error)
    tags.value = []
  }
}

onMounted(() => {
  tippy(btn.value, {
    content: dropdown.value,
    interactive: true,
    placement: "bottom-start",
    animation: "shift-away",
    trigger: "click",
    arrow: false,
    onShow() {
      fetchContainerTags()
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

.dropdown-menu {
  // min-width: 280px;
  
  // .dropdown-header {
  //   display: flex;
  //   align-items: center;
  //   gap: 8px;
  //   font-weight: 600;
  //   padding: 8px 12px;
  //   border-bottom: 1px solid map-get($github-colors, border-subtle);
    
  //   .header-icon {
  //     color: inherit;
  //     opacity: 0.8;
  //   }
  // }

  .dropdown-content {
    padding: 0 !important;
  }
  
  .dropdown-item {
    display: flex;
    align-items: center;
    width: 100%;
    transition: all 0.2s ease;
    
    &:hover {
      background: map-get($github-colors, hover);
    }
    
    &.is-selected {
      background: transparent;
      
      .tag-badge {
        flex: 1;
        border-color: var(--tag-color) !important;
      }
      
      .check-icon {
        background-color: var(--tag-color) !important;
        color: white !important;
        border-color: var(--tag-color) !important;
   
        z-index: 1;
      }
      
      .tag-name {
        color: var(--tag-color) !important;
      }

      .tag-icon {
        border-color: var(--tag-color) !important;

        &.untagged {
          background-color: var(--tag-color) !important;
          font-weight: 600;



          :deep(.v-icon) {
            color: white !important;
          }
        }
      }
    }
    
    .tag-icon {
      width: 24px;
      height: 24px;
      border-radius: 3px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      
      &.untagged {
        background: map-get($github-colors, bg-secondary);
      }
    }
    
    .tag-badge {
      flex: 1;
      font-size: 12px;
      font-weight: 500;
      padding: 4px 8px;
      border-radius: 4px !important;
      border: 1px solid;
      display: inline-flex;
      align-items: center;
      max-width: fit-content;
      transition: all 0.2s ease;
      position: relative;
      
      &:hover {
        opacity: 0.95;
      }
    }
    
    .check-icon {
      margin-left: auto;
      flex-shrink: 0;
    }
    
    &.clear-action {
      color: map-get($github-colors, danger);
      font-size: 12px;
      margin: 0 !important;
      padding: 4px 16px;
      border-top: 1px solid map-get($github-colors, border-subtle);
      border-bottom: none;
      
      &:hover {
        background: map-get($github-colors, danger-bg);
      }
      
      .clear-icon {
        opacity: 0.8;
      }
    }
  }
  
  .dropdown-empty {
    padding: 24px 16px;
    text-align: center;
    color: map-get($github-colors, text-tertiary);
    font-size: 13px;
    
    .empty-icon {
      display: block;
      margin: 0 auto 8px;
      opacity: 0.6;
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
