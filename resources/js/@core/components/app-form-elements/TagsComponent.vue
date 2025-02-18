<script setup>
import { ref, computed } from 'vue'
import tippy from "tippy.js"
import "tippy.js/animations/shift-away.css"

const props = defineProps({
  modelValue: Array,
})

const emit = defineEmits(['update:modelValue', 'refreshKanbanData'])

const isOpen = ref(false)
const btn = ref(null)
const dropdown = ref(null)
const search = ref("")
const tags = ref([])
const editingTag = ref(null)
const activeMenuTag = ref({})
const activeOption = ref(null)
const route = useRoute()

const colors = [
  { name: 'Red', value: '#ef5350' },
  { name: 'Pink', value: '#ec407a' },
  { name: 'Purple', value: '#ab47bc' },
  { name: 'Deep Purple', value: '#7e57c2' },
  { name: 'Blue', value: '#42a5f5' },
  { name: 'Cyan', value: '#26c6da' },
  { name: 'Teal', value: '#26a69a' },
  { name: 'Green', value: '#66bb6a' },
  { name: 'Yellow', value: '#ffee58' },
  { name: 'Amber', value: '#ffca28' },
  { name: 'Orange', value: '#ffa726' },
  { name: 'Deep Orange', value: '#ff7043' },
  { name: 'Brown', value: '#8d6e63' },
  { name: 'Blue Grey', value: '#78909c' },
  { name: 'Light Grey', value: '#e0e0e0' },
  { name: 'Dark Grey', value: '#757575' },
  { name: 'Lime', value: '#cddc39' },
  { name: 'Indigo', value: '#5c6bc0' },
  { name: 'Light Blue', value: '#29b6f6' },
  { name: 'Light Green', value: '#9ccc65' },
  { name: 'Deep Green', value: '#388e3c' },
  { name: 'Hot Pink', value: '#ff4081' },
  { name: 'Bright Orange', value: '#ff5722' },
  { name: 'Dark Blue', value: '#303f9f' },
]

const fetchContainerTags = async () => {
  const res = await $api(`/container/tags/${route.params.containerId}`, {
    method: "GET",
  })

  tags.value = res.data
}

const filteredTags = computed(() => {
  return tags.value.filter(tag =>
    tag.name.toLowerCase().includes(search.value.toLowerCase()) &&
    !props.modelValue.some(selectedTag => selectedTag.id === tag.id),
  )
})

const selectTag = tag => {
  if(editingTag.value) {
    return
  }

  const exists = props.modelValue.some(t => t.id === tag.id)

  if (!exists) {
    emit('update:modelValue', [...props.modelValue, tag])
  } else {
    emit('update:modelValue', props.modelValue.filter(t => t.id !== tag.id))
  }
  search.value = ''
}

const createTag = async () => {
  if (search.value.trim().length > 0) {
    const newTag = {
      name: search.value.trim(),
      color: colors[Math.floor(Math.random() * colors.length)].value,
      container_id: route.params.containerId,
    }

    try {
      const res = await $api(`/tag`, {
        method: "POST",
        body: JSON.stringify(newTag),
      })

      tags.value.push(res.data)
      selectTag(res.data)
    } catch (error) {
      console.error("Error creating tag", error)
    }
  }
  search.value = ''
  isOpen.value = true
}

const renameTag = (tag, option) => {
  activeOption.value = option

  editingTag.value = tag
}

const updateTag = async (event, tag) => {
  const newName = event.target.value.trim()
  if (newName) {
    try {
      await $api(`/tag/${tag.id}`, {
        method: "PUT",
        body: JSON.stringify({ name: newName, color: tag.color, container_id: tag.container_id }),
      })
      tag.name = newName
    } catch (error) {
      console.error("Error updating tag", error)
    }
  }
  editingTag.value = null
  isOpen.value = true

  await nextTick(() => {
    emit('refreshKanbanData')
  })
}

const deleteTag = async (tag, option) => {
  activeOption.value = option
  try {
    await $api(`/tag/${tag.id}`, {
      method: "DELETE",
    })

    emit('update:modelValue', props.modelValue.filter(t => t.id !== tag.id))
    tags.value = tags.value.filter(t => t.id !== tag.id)
  } catch (error) {
    console.error("Error deleting tag", error)
  }
  isOpen.value = true

  await nextTick(() => {
    emit('refreshKanbanData')
  })
}

const changeTagColor = async (tag, option) => {
  activeOption.value = option

  const newColor = colors[Math.floor(Math.random() * colors.length)].value
  try {
    await $api(`/tag/${tag.id}`, {
      method: "PUT",
      body: JSON.stringify({ color: newColor, name: tag.name, container_id: tag.container_id }),
    })
    tag.color = newColor
  } catch (error) {
    console.error("Error changing tag color", error)
  }
  isOpen.value = true

  await nextTick(() => {
    emit('refreshKanbanData')
  })
}

const toggleTagMenu = tagId => {
  activeMenuTag.value = { [tagId]: !activeMenuTag.value[tagId] }
}

onMounted(async () => {
  tippy(btn.value, {
    content: dropdown.value,
    interactive: true,
    placement: "bottom-start",
    animation: "shift-away",
    trigger: "click",
    appendTo: document.body,
    popperOptions: {
      modifiers: [
        {
          name: "preventOverflow",
          options: {
            boundary: "window",
          },
        },
      ],
    },
    onShow() {
      fetchContainerTags()
    },
  })
})

onUnmounted(() => {
  activeMenuTag.value = {}
})
</script>

<template>
  <div class="relative inline-block text-left">
    <button
      ref="btn"
      class="tags-container w-100"
    >
      <span
        v-if="modelValue.length === 0"
        class="cursor-pointer"
      >
        <VIcon
          size="14"
          color="#374151"
          icon="tabler-tag"
        />
      </span>
      <span
        v-for="(tag, index) in modelValue.slice(0, 2)"
        :key="tag.id"
        class="tag py-0 my-0 px-1"
        :style="{ backgroundColor: `${tag.color}73` }"
      >
        <span>{{ tag.name }}</span>
        <VIcon
          size="14"
          color="#374151"
          icon="tabler-circle-minus"
          @click.stop="selectTag(tag)"
        />
      </span>
      <span
        v-if="modelValue.length > 2"
        class="tag-extra"
      >+{{ modelValue.length - 2 }}</span>
    </button>

    <!-- Dropdown-ul de tag-uri -->
    <div
      ref="dropdown"
      class="dropdown-menu hidden"
    >
      <div class="selected-tags">
        <span
          v-for="tag in modelValue"
          :key="tag.id"
          class="tag tag-selected"
          :style="{ backgroundColor: `${tag.color}73` }"
        >
          {{ tag.name }}
          <VIcon
            size="14"
            color="#374151"
            icon="tabler-circle-minus"
            @click.stop="selectTag(tag)"
          />
        </span>
      </div>

      <input
        v-model="search"
        type="text"
        placeholder="Search or Create New"
        class="search-input"
        @keyup.enter="createTag"
      >

      <div
        v-if="search.length > 0 && !tags.some(t => t.name === search)"
        class="tag-create"
        @click="createTag"
      >
        Create "{{ search }}"
      </div>

      <div class="dropdown-content">
        <div
          v-for="tag in filteredTags"
          :key="tag.id"
          class="selectable"
          @click.stop="selectTag(tag)"
        >
          <span
            class="tag"
            :style="{ backgroundColor: `${tag.color}73` }"
          >
            <input
              v-if="editingTag?.id === tag.id"
              v-model="editingTag.name"
              class="tag-input"
              autofocus
              @blur="(e) => updateTag(e, tag)"
              @keyup.enter="(e) => updateTag(e, tag)"
            >
            <span
              v-else
              class="d-flex justify-space-between gap-2"
            >
              {{ tag.name }}
              <button
                class="tag-menu-btn"
                @click.stop="toggleTagMenu(tag.id)"
              >
                <VIcon
                  size="14"
                  color="primary"
                > tabler-dots-circle-horizontal </VIcon>
              </button>
            </span>
          </span>

          <div
            v-if="activeMenuTag[tag.id]"
            class="tag-options"
          >
            <div
              class="tag-option"
              @click.stop="renameTag(tag, 'rename')"
            >
              <VIcon
                size="14"
                color="primary"
              >
                tabler-edit
              </VIcon>
            </div>
            <div
              class="tag-option"
              @click.stop="deleteTag(tag, 'delete')"
            >
              <VIcon
                size="14"
                color="error"
              >
                tabler-trash
              </VIcon>
            </div>
            <div
              class="tag-option"
              @click.stop="changeTagColor(tag, 'changeColor')"
            >
              <VIcon
                size="14"
                color="primary"
              >
                tabler-palette
              </VIcon>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.tags-container {
  display: flex;
  gap: 2px;
  cursor: pointer;
}

.tag {
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  padding: 2px 8px;
  font-size: 9px;
  font-weight: 500;
  border-radius: 4px;
  background: rgba(241, 243, 245, 0.7);
  color: #333;
  border: 1px solid #d0d7de;
  gap: 2px;
  cursor: pointer;
}

.dropdown-menu {
  background: white;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(27, 31, 36, 0.15);
  border: 1px solid #d0d7de;
  overflow: hidden;
  z-index: 999;
  min-width: 220px;
}

.tag-menu-item {
  padding: 6px;
  cursor: pointer;
  font-size: 12px;
}

.selectable {
  display: flex;
  flex-direction: column;
  align-items: start;
  justify-content: space-between;
  padding: 6px 2px;
  cursor: pointer;
  border-radius: 4px;
  transition: background 0.2s ease-in-out;

  &:hover {
    background: rgba(240, 240, 240, 0.8); // Gri deschis elegant
  }
}

.tag-input {
  border: none;
  outline: none;
  font-size: 11px;
  background: transparent;
  width: 100%;
}

.selected-tags {
  display: flex;
  gap: 2px;
  flex-wrap: wrap;
}

.tag-remove {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 11px;
  color: #d73a49;
  margin-left: 4px;
  padding: 0;
}

.tag-create {
  background: #f0f0f0;
  color: #57606a;
  font-size: 11px;
  padding: 4px;
  border-radius: 4px;
  cursor: pointer;
}

.tag-menu {
  cursor: pointer;
  font-size: 12px;
  margin-left: 6px;
  color: #57606a;
}

.tag-selected {
  background: #0969da;
  color: white;
  border: 1px solid #005cc5;
}

.tag-extra {
  background: #d0d7de;
  color: #57606a;
  font-weight: bold;
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 4px;
}

.search-input {
  width: 100%;
  padding: 4px;
  border: 1px solid #d0d7de;
  border-radius: 4px;
  outline: none;
  font-size: 11px;
  background: #ffffff;
  margin-top: 4px;
}

.dropdown-menu-custom {
  width: 100%;
  max-width: 16.5rem;
  max-height: 250px;
  overflow-y: auto;
  background: #ffffff;
  box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  padding: 8px;
  border: 1px solid #d0d7de;
}

.dropdown-content::-webkit-scrollbar,
.dropdown-menu-custom::-webkit-scrollbar {
  width: 6px;
}

.dropdown-content::-webkit-scrollbar-track,
.dropdown-menu-custom::-webkit-scrollbar-track {
  background: rgba(240, 240, 240, 0.8);
  border-radius: 4px;
}

.dropdown-content::-webkit-scrollbar-thumb,
.dropdown-menu-custom::-webkit-scrollbar-thumb {
  background: rgba(136, 136, 136, 0.5);
  border-radius: 4px;
  transition: background 0.3s ease;
}

.dropdown-content::-webkit-scrollbar-thumb:hover,
.dropdown-menu-custom::-webkit-scrollbar-thumb:hover {
  background: rgba(100, 100, 100, 0.7);
}

.dropdown-content {
  margin-top: 4px;
  max-height: 150px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.tag-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 6px 8px;
}

.tag-options {
  display: flex;
  gap: 8px;
  border-radius: 4px;
}

.tag-options {
  display: flex;
  gap: 6px;
  padding: 4px;
  border-radius: 6px;
  background: rgba(240, 240, 240, 0.9);
  transition: all 0.2s ease-in-out;
  justify-content: center;
}

.tag-option {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 6px;
  border-radius: 6px;
  transition: background 0.2s ease-in-out;

  &:hover {
    background: rgba(220, 220, 220, 0.8);
  }
}

.tag-option-delete:hover {
  background: rgba(255, 0, 0, 0.1);
}

.active-option {
  background: #007aff !important;
  color: white !important;
  font-weight: bold;
}

.tag-menu-btn {
  width: fit-content;
}
</style>
