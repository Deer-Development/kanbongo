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
        tabler-users
      </VIcon>
    </button>

    <div
      ref="dropdown"
      class="dropdown-menu hidden"
    >
      <div class="dropdown-header">
        Assignees
      </div>

      <!-- Filtru pentru "Unassigned" -->
      <button
        class="dropdown-item"
        :class="{ 'is-selected': modelValue.includes('unassigned') }"
        @click="toggleUnassigned"
      >
        <VIcon
          size="18"
          :icon="modelValue.includes('unassigned') ? 'tabler-user-x' : 'tabler-user'"
          class="user-icon"
        />
        Unassigned
      </button>

      <template v-if="users.length">
        <button
          v-for="user in users"
          :key="user.id"
          class="dropdown-item"
          :class="{ 'is-selected': modelValue.includes(user.id) }"
          @click="selectUser(user)"
        >
          <VIcon
            size="18"
            :icon="modelValue.includes(user.id) ? 'tabler-user-check' : 'tabler-user'"
            class="user-icon"
          />
          {{ user.full_name }}
        </button>

        <VDivider />

        <div
          class="dropdown-item priority-clear"
          @click="clearSelection"
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
import { ref, defineProps, defineEmits, onMounted } from "vue"
import tippy from "tippy.js"
import "tippy.js/animations/shift-away.css"

const props = defineProps({
  modelValue: { type: Array, default: () => [] }, // Acum include și `unassigned`
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

// Toggle pentru `unassigned`
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

// Resetare filtre
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
  color: #0969da;
  font-weight: 600;
}

.no-result {
  padding: 12px;
  text-align: center;
  color: #57606a;
  font-size: 14px;
}
</style>
