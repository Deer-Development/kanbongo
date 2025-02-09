<template>
  <div class="dropdown-menu">
    <template v-if="items.length">
      <button
        :class="{ 'is-selected': index === selectedIndex }"
        v-for="(item, index) in items"
        :key="item.id"
        @click="selectItem(index)"
      >
        {{ item.name }}
      </button>
    </template>
    <div class="item" v-else>
      No result
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
/* Dropdown menu */
.dropdown-menu {
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
