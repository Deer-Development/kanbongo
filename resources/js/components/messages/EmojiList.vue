<template>
  <div class="dropdown-menu github-style-dropdown">
    <button
      class="dropdown-item emoji-item"
      :class="{ 'is-selected': index === selectedIndex }"
      v-for="(item, index) in items"
      :key="index"
      @click="selectItem(index)"
    >
      <div class="emoji-container">
        <img v-if="item.fallbackImage" :src="item.fallbackImage" alt="emoji">
        <template v-else>
          {{ item.emoji }}
        </template>
      </div>
      <span class="emoji-name">:{{ item.name }}:</span>
    </button>
    <div class="dropdown-empty" v-if="items.length === 0">
      <VIcon size="16" icon="tabler-mood-sad" class="me-2" />
      No matching emojis found
    </div>
  </div>
</template>

<script>
export default {
  props: {
    items: {
      type: Array,
      required: true,
    },

    command: {
      type: Function,
      required: true,
    },

    editor: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      selectedIndex: 0,
    }
  },

  watch: {
    items() {
      this.selectedIndex = 0
    },
  },

  methods: {
    onKeyDown({ event }) {
      if (event.key === 'ArrowUp') {
        this.upHandler()
        return true
      }

      if (event.key === 'ArrowDown') {
        this.downHandler()
        return true
      }

      if (event.key === 'Enter') {
        this.enterHandler()
        return true
      }

      return false
    },

    upHandler() {
      this.selectedIndex = ((this.selectedIndex + this.items.length) - 1) % this.items.length
    },

    downHandler() {
      this.selectedIndex = (this.selectedIndex + 1) % this.items.length
    },

    enterHandler() {
      this.selectItem(this.selectedIndex)
    },

    selectItem(index) {
      const item = this.items[index]

      if (item) {
        this.command({ name: item.name })
      }
    },
  },
}
</script>

<style lang="scss">
// The github-style-dropdown class is already defined in MentionList.vue
// Here we just add emoji-specific styling

.dropdown-menu.github-style-dropdown {
  .emoji-item {
    padding: 8px 12px;
    
    .emoji-container {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 24px;
      height: 24px;
      margin-right: 8px;
      font-size: 18px;
      
      img {
        width: 20px;
        height: 20px;
        object-fit: contain;
      }
    }
    
    .emoji-name {
      font-size: 14px;
      color: #24292f;
      font-family: monospace;
    }
  }
}
</style>
