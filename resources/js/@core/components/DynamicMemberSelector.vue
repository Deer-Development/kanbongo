<script setup>
const props = defineProps({
  isSuperAdmin: { type: Boolean, required: false, default: false },
  taskId: {
    type: Number,
    required: false,
    default: null,
  },
  activeUsers: {
    type: Array,
    required: false,
    default: () => [],
  },
  trackedUsers: {
    type: Array,
    required: false,
    default: () => [],
  },
})

const localActiveUsers = ref(props.activeUsers)
const localTrackedUsers = ref(props.trackedUsers)

defineOptions({
  name: 'AppSelect',
  inheritAttrs: false,
})

const elementId = computed(() => {
  const attrs = useAttrs()
  const _elementIdToken = attrs.id || attrs.label

  return _elementIdToken ? `member-selector-${_elementIdToken}-${Math.random().toString(36).slice(2, 7)}` : undefined
})

const label = computed(() => useAttrs().label)

watch(() => props.activeUsers, () => {
  localActiveUsers.value = [...props.activeUsers]
}, { deep: true })

watch(() => props.trackedUsers, () => {
  localTrackedUsers.value = [...props.trackedUsers]
}, { deep: true })
</script>

<template>
  <div
    class="app-select flex-grow-1"
    :class="$attrs.class"
  >
    <VLabel
      v-if="label"
      :for="elementId"
      class="mb-1 text-body-2"
      style="line-height: 15px;"
      :text="label"
    />
    <VSelect
      v-bind="{
        ...$attrs,
        class: null,
        label: undefined,
        variant: 'outlined',
        id: elementId,
        menuProps: { contentClass: ['app-inner-list', 'app-select__content', 'v-select__content', $attrs.multiple !== undefined ? 'v-list-select-multiple' : ''] },
      }"
      multiple
      return-object
      variant="plain"
      :menu-props="{
        offset: 10,
      }"
      class="assignee-select"
    >
      <template #item="{ props, item }">
        <VListItem
          v-bind="props"
          :prepend-avatar="item?.raw?.avatar"
          :title="item?.raw?.user.full_name"
          :subtitle="item?.raw?.user.email"
        />
      </template>
      <template #selection="{ item }" style="margin-inline-start: -0.8rem;">
        <VAvatar
          size="26"
          :color="localActiveUsers.some(user => user.user.id === item.raw.user.id) ? '#38a169' :
            localTrackedUsers.some(id => id === item.raw.user.id) ? '#42bc7b' : '#EEEDF0'"
          :class="localActiveUsers.some(user => user.user.id === item.raw.user.id) ? 'glow' :
            localTrackedUsers.some(id => id === item.raw.user.id) ? 'worked' : ''"
        >
          <VImg
            v-if="item.raw.user.avatar"
            :src="item.raw.user.avatar"
          />
          <template v-else>
            <span class="text-xs font-weight-medium">{{ item.raw.user.avatar_or_initials }}</span>
          </template>
          <VTooltip activator="parent">
            {{ item.title }}
          </VTooltip>
        </VAvatar>
      </template>

      <template #prepend-inner>
        <IconBtn
          size="26"
          variant="tonal"
          color="secondary"
        >
          <VIcon
            size="18"
            icon="tabler-users-plus"
          />
        </IconBtn>
      </template>
      <template
        v-for="(_, name) in $slots"
        #[name]="slotProps"
      >
        <slot
          :name="name"
          v-bind="slotProps || {}"
        />
      </template>
    </VSelect>
  </div>
</template>

<style lang="scss">
.assignee-select {
  display: inline-flex;
  align-items: center;
  padding: 2px 4px;
  background-color: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: background-color 0.2s ease, border-color 0.2s ease;

  .v-field.v-field--variant-plain .v-field__prepend-inner{
    padding-top: 2px;
  }

  .v-input__control {
    width: 100%;
  }

  .v-field__input{
    padding-top: 0;
    padding-left: 2px;
    width: 100%;
    margin-left: 0;
  }
  .v-field__append-inner {
    padding-top: 0 !important;
    .v-select__menu-icon {
      display: none;
    }
  }
  .v-field--dirty .v-select__selection {
    margin-inline-start: -0.8rem;

    > .v-avatar {
      //border: 2px solid rgb(var(--v-theme-surface));
      transition: transform 0.15s ease;

      &:hover {
        transform: scale(1.15);
        transition: .2s cubic-bezier(.4,0,.2,1);
        transition-property: width, height;
        z-index: 100;
      }
    }
  }
}
//v-input.v-input--density-comfortable .v-field .v-field__input

</style>
