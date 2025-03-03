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
        {{ modelValue.length ? 'tabler-users-group' : 'tabler-users' }}
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
        <VIcon size="14" icon="tabler-users" />
        Filter by assignee
      </div>

      <div class="dropdown-search">
        <VTextField
          v-model="searchQuery"
          density="compact"
          placeholder="Filter users..."
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
          :class="{ 'is-selected': modelValue.includes('unassigned') }"
          @click="toggleUnassigned"
        >
          <div class="user-avatar unassigned">
            <VIcon size="12">tabler-user-off</VIcon>
          </div>
          <span class="user-name">Unassigned</span>
          <VIcon 
            v-if="modelValue.includes('unassigned')"
            size="14" 
            icon="tabler-check" 
            color="primary"
          />
        </button>

        <template v-if="filteredUsers.length">
          <button
            v-for="user in filteredUsers"
            :key="user.id"
            class="dropdown-item"
            :class="{ 'is-selected': modelValue.includes(user.id) }"
            @click="selectUser(user)"
          >
            <div class="user-avatar">
              {{ user.full_name.charAt(0) }}
            </div>
            <span class="user-name">{{ user.full_name }}</span>
            <VIcon 
              v-if="modelValue.includes(user.id)"
              size="14" 
              icon="tabler-check" 
              color="primary"
            />
          </button>

          <button
            v-if="modelValue.length"
            class="dropdown-item clear-action"
            @click="clearSelection"
          >
            <VIcon
              size="14"
              class="me-2"
            >
              tabler-x
            </VIcon>
            <span>Clear assignee filter</span>
          </button>
        </template>

        <div
          v-else
          class="dropdown-empty"
        >
          <VIcon size="20" icon="tabler-users-off" />
          <p>No matching users found</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits, onMounted, computed } from "vue"
import tippy from "tippy.js"
import "tippy.js/animations/shift-away.css"

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
})

const emit = defineEmits(["update:modelValue"])

const btn = ref(null)
const dropdown = ref(null)
const users = ref([])
const route = useRoute()

// Add new reactive ref for search
const searchQuery = ref('')

// Add computed property for filtered users
const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value
  
  return users.value.filter(user => 
    user.full_name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const selectUser = user => {
  const selected = [...props.modelValue]
  const index = selected.indexOf(user.id)

  if (index === -1) {
    selected.push(user.id)
  } else {
    selected.splice(index, 1)
  }

  emit("update:modelValue", selected)
}

const toggleUnassigned = () => {
  const selected = [...props.modelValue]
  const index = selected.indexOf("unassigned")

  if (index === -1) {
    selected.push("unassigned")
  } else {
    selected.splice(index, 1)
  }

  emit("update:modelValue", selected)
}

const clearSelection = () => {
  emit("update:modelValue", [])
}

const fetchContainerUsers = async () => {
  const res = await $api(`/container/members/${route.params.containerId}`, {
    method: "GET",
  })

  users.value = res.data
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
      fetchContainerUsers()
    },
  })
})
</script>

<style lang="scss" scoped>
@import '@styles/shared-github-styles';

.has-selections {
  position: relative;
  
  .selection-indicator {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #6366f1;
    color: white;
    border-radius: 10px;
    padding: 0 3px !important;
    font-size: 8px !important;
    font-weight: 600;
    min-width: 12px !important;
    height: 12px !important;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
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

.dropdown-content {
  max-height: 300px;
  overflow-y: auto;
  padding: 4px;
}
</style>
