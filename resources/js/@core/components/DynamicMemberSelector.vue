<script setup>
import { useToast } from "vue-toastification"

const props = defineProps({
  isSuperAdmin: { type: Boolean, required: false, default: false },
})

defineOptions({
  name: 'AppSelect',
  inheritAttrs: false,
})

const toast = useToast()

const elementId = computed(() => {
  const attrs = useAttrs()
  const _elementIdToken = attrs.id || attrs.label

  return _elementIdToken ? `member-selector-${_elementIdToken}-${Math.random().toString(36).slice(2, 7)}` : undefined
})

const label = computed(() => useAttrs().label)

const isUserDisabled = item => {
  const attrs = useAttrs()
  const memberIsSelected = attrs.modelValue.some(value => value.user_id === item.raw.user.id)

  if(props.isSuperAdmin){
    return false
  }else{
    return !props.isSuperAdmin && memberIsSelected
  }
}
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
      class="assignee-select avatar-group"
    >
      <template #item="{ props, item }">
        <VListItem
          v-bind="props"
          :prepend-avatar="item?.raw?.avatar"
          :title="item?.raw?.user.full_name"
          :subtitle="item?.raw?.user.email"
          :disabled="isUserDisabled(item)"
        />
      </template>
      <template #selection="{ item }">
        <VAvatar
          size="26"
          :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
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
  .v-field__append-inner {
    .v-select__menu-icon {
      display: none;
    }
  }
}
</style>
