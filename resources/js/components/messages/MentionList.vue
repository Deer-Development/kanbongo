<template>
  <div class="dropdown-menu github-style-dropdown">
    <template v-if="items.length">
      <button
        class="dropdown-item"
        :class="{ 'is-selected': index === selectedIndex }"
        v-for="(item, index) in items"
        :key="item.id"
        @click="selectItem(index)"
      >
        <div class="mention-avatar">
          {{ item.name.charAt(0) }}
        </div>
        <span class="mention-name">{{ item.name }}</span>
      </button>
    </template>
    <div class="dropdown-empty" v-else>
      <VIcon size="16" icon="tabler-search-off" class="me-2" />
      No matching members found
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
        this.command({ id: item.id, label: item.name })
      }
    },
  },
}
</script>

<style lang="scss">
$github-colors: (
  text-primary: #24292f,
  text-secondary: #57606a,
  border: #d0d7de,
  bg-primary: #ffffff,
  bg-secondary: #f6f8fa,
  accent: #0969da,
  hover: #f3f4f6,
  shadow: rgba(31, 35, 40, 0.07),
  avatar-bg: #eef1f5
);

$spacing: (
  xs: 4px,
  sm: 8px,
  md: 12px,
  lg: 16px
);

.dropdown-menu.github-style-dropdown {
  background: map-get($github-colors, bg-primary);
  border: 1px solid map-get($github-colors, border);
  border-radius: 6px;
  box-shadow: 0 8px 24px map-get($github-colors, shadow);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  width: 250px;
  max-height: 250px;
  overflow-y: auto;
  padding: 0;
  
  &::-webkit-scrollbar {
    width: 6px;
  }
  
  &::-webkit-scrollbar-track {
    background: map-get($github-colors, bg-secondary);
  }
  
  &::-webkit-scrollbar-thumb {
    background: darken(map-get($github-colors, border), 10%);
    border-radius: 3px;
    
    &:hover {
      background: darken(map-get($github-colors, border), 15%);
    }
  }

  .dropdown-item {
    display: flex;
    align-items: center;
    padding: map-get($spacing, sm) map-get($spacing, md);
    background: transparent;
    border: none;
    border-bottom: 1px solid map-get($github-colors, border);
    text-align: left;
    cursor: pointer;
    transition: background-color 0.2s ease;
    width: 100%;
    
    &:last-child {
      border-bottom: none;
    }
    
    &:hover, &.is-selected {
      background-color: map-get($github-colors, hover);
    }
    
    &.is-selected {
      background-color: lighten(map-get($github-colors, accent), 45%);
    }
    
    .mention-avatar {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background-color: map-get($github-colors, avatar-bg);
      color: map-get($github-colors, accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 600;
      margin-right: map-get($spacing, sm);
      flex-shrink: 0;
    }
    
    .mention-name {
      font-size: 14px;
      color: map-get($github-colors, text-primary);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }
  
  .dropdown-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: map-get($spacing, md);
    color: map-get($github-colors, text-secondary);
    font-size: 13px;
    text-align: center;
  }
}

.dropdown-menu:not(.github-style-dropdown) {
  background: #FFF;
  border: 1px solid rgba(61, 37, 20, .05);
  border-radius: 0.7rem;
  box-shadow: 0 12px 33px 0 rgba(0, 0, 0, .06), 0 3.618px 9.949px 0 rgba(0, 0, 0, .04);
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  overflow: auto;
  padding: 0.6rem;
  position: relative;

  button {
    align-items: center;
    background-color: transparent;
    display: flex;
    gap: 0.25rem;
    text-align: left;
    width: 100%;

    &:hover,
    &:hover.is-selected {
      background-color: rgba(61, 37, 20, .12);
    }

    &.is-selected {
      background-color: rgba(61, 37, 20, .08);
    }

    img {
      height: 1em;
      width: 1em;
    }
  }
}
</style>
