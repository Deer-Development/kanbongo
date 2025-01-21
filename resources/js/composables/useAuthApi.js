import { useAuthStore } from "@core/stores/authStore"

export function useAuthApi() {
  const authStore = useAuthStore()

  // Login function using usePost
  const login = async credentials => {
    const { usePost } = useApi()
    const { mutateAsync: loginRequest } = usePost('/auth/login')
    try {
      const res = await loginRequest(credentials)

      // Store tokens and user data in cookies
      useCookie('accessToken', { default: null }).value = res.token
      useCookie('userData', { default: null }).value = res.user

      // Update Pinia state
      authStore.setUser(res.user)
      authStore.setAccessToken(res.token)
    } catch (error) {
      console.error('Login Error:', error)
      throw error
    }
  }

  const fetchUser = async () => {
    const { useGet } = useApi()
    const token = useCookie('accessToken', { default: null }).value
    if (!token) return null

    try {
      const { data } = await useGet(['auth-user'], '/auth/user')

      authStore.setUser(data.user)
    } catch (error) {
      console.error('Fetch User Error:', error)
      throw error
    }
  }

  const logout = async () => {
    const { useGet } = useApi()
    const { mutateAsync: logoutRequest } = useGet('/auth/logout')
    try {
      await logoutRequest()

      // Clear authentication data
      authStore.clearAuthData()
    } catch (error) {
      console.error('Logout Error:', error)
      throw error
    }
  }

  return {
    login,
    fetchUser,
    logout,
  }
}
