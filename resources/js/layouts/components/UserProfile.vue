<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const router = useRouter()

const userData = useCookie('userData')
const errors = ref(null)

const logout = async () => {
  try {
    const res = await $api('/auth/logout', {
      method: 'GET',
      onResponseError({ response }) {
        errors.value = response._data.errors
      },
    })

    useCookie('accessToken').value = null

    userData.value = null

    await router.push('/login')
    useCookie('userAbilityRules').value = null
  } catch (err) {
    console.error(err)
  }
}
</script>

<template>
  <VBadge
    v-if="userData"
    dot
    bordered
    location="bottom right"
    offset-x="1"
    offset-y="2"
    color="success"
  >
    <VAvatar
      size="38"
      class="cursor-pointer"
      :color="!(userData && userData.avatar) ? 'primary' : undefined"
      :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
    >
      <template v-if="userData.avatar">
        <VImg
          :src="userData.avatar"
          alt="Avatar"
        />
      </template>
      <template v-else>
        <span>{{ userData.avatar_or_initials }}</span>
      </template>

      <!-- SECTION Menu -->
      <VMenu
        activator="parent"
        width="240"
        location="bottom end"
        offset="12px"
      >
        <VList>
          <VListItem>
            <div class="d-flex gap-2 align-center">
              <VListItemAction>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                  bordered
                >
                  <VAvatar
                    :color="!(userData && userData.avatar) ? 'primary' : undefined"
                    :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
                  >
                    <VImg
                      v-if="userData && userData.avatar"
                      :src="userData.avatar"
                    />
                    <VIcon
                      v-else
                      icon="tabler-user"
                    />
                  </VAvatar>
                </VBadge>
              </VListItemAction>

              <div>
                <h6 class="text-h6 font-weight-medium">
                  {{ userData.full_name }}
                </h6>
                <VListItemSubtitle class="text-disabled">
                  {{ userData.email }}
                </VListItemSubtitle>
              </div>
            </div>
          </VListItem>

          <VDivider class="my-2" />

          <VListItem
            :to="{ name: 'user-profile' }"
            link
          >
            <template #prepend>
              <VIcon icon="tabler-user-circle" size="22" class="me-2" />
            </template>
            <VListItemTitle>Profile Settings</VListItemTitle>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <div class="px-4 py-2">
              <VBtn
                block
                size="small"
                color="error"
                append-icon="tabler-logout"
                @click="logout"
              >
                Logout
              </VBtn>
            </div>
          </PerfectScrollbar>
        </VList>
      </VMenu>
    </VAvatar>
  </VBadge>
</template>
