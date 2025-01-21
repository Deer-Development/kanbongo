import { canNavigate } from '@layouts/plugins/casl'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from "@core/stores/authStore"

export const setupGuards = router => {
  setActivePinia(createPinia())

  const authStore = useAuthStore()

  // 👉 router.beforeEach
  // Docs: https://router.vuejs.org/guide/advanced/navigation-guards.html#global-before-guards
  router.beforeEach(to => {
    if (to.meta.public)
      return

    if (to.meta.unauthenticatedOnly) {
      if (useCookie('accessToken', { default: null }).value)
        return '/'
      else
        return undefined
    }

    if (!useCookie('accessToken', { default: null }).value) {
      console.log(authStore.getToken)
      console.log(authStore.getCurrentUser)

      return '/login'
    }
  })
}
