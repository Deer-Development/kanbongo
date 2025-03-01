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
        tabler-users
      </VIcon>
    </button>

    <div
      ref="dropdown"
      class="dropdown-menu github-style-dropdown hidden"
    >
      <div class="dropdown-header">
        Assignees
      </div>

      <button
        class="dropdown-item"
        :class="{ 'is-selected': modelValue.includes('unassigned') }"
        @click="toggleUnassigned"
      >
        <div class="user-avatar unassigned">
          <VIcon size="14">tabler-user-off</VIcon>
        </div>
        <span class="user-name">Unassigned</span>
      </button>

      <VDivider />

      <template v-if="users.length">
        <button
          v-for="user in users"
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
      </template>

      <div
        v-else
        class="dropdown-empty"
      >
        <VIcon size="16" icon="tabler-users-off" class="me-2" />
        No users available
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
const users = ref([])
const route = useRoute()

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

<style lang="scss">
@import './shared-github-styles.scss';

.dropdown-menu.github-style-dropdown {
  .user-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: map-get($github-colors, avatar-bg);
    color: map-get($github-colors, text-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 500;
    flex-shrink: 0;
    border: 1px solid rgba(31, 35, 40, 0.06);
    
    &.unassigned {
      background-color: #f0f0f0;
      color: map-get($github-colors, text-tertiary);
    }
  }
  
  .user-name {
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 400;
    letter-spacing: 0.01em;
  }
  
  .dropdown-item.is-selected {
    .user-avatar {
      background-color: rgba(map-get($github-colors, accent), 0.12);
      color: map-get($github-colors, accent);
      border-color: rgba(map-get($github-colors, accent), 0.2);
    }
  }
}
</style>
