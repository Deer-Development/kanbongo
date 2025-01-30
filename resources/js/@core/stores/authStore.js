import { defineStore } from 'pinia'

export const useAuthStore = defineStore('authStore', {
  state: () => {
    return {
      currentUser: null,
      accessToken: null,
    }
  },
  actions: {
    async requestLoginToken(data) {
      try {
        await $api('/auth/send-token', {
          method: 'POST',
          body: data,
          onResponseError({ response }) {
            throw response._data.errors
          },
        })
      } catch (error) {
        console.error('Token Request Error:', error)
        throw error
      }
    },
    async requestRegisterToken(data) {
      try {
        await $api('/auth/register', {
          method: 'POST',
          body: data,
          onResponseError({ response }) {
            throw response._data.errors
          },
        })
      } catch (error) {
        console.error('Token Request Error:', error)
        throw error
      }
    },
    async verifyLoginToken(data) {
      try {
        const res = await $api('/auth/verify-token', {
          method: 'POST',
          body: data,
          onResponseError({ response }) {
            throw response._data.errors
          },
        })

        useCookie('accessToken', { default: null }).value = res.token
        useCookie('userData', { default: null }).value = res.user

        this.currentUser = res.user
        this.accessToken = res.token
      } catch (error) {
        console.error('Login Verification Error:', error)
        throw error
      }
    },
  },
  getters: {
    isLoggedIn: state => !!state.currentUser && !!state.accessToken,
    getToken: state => state.accessToken,
    getCurrentUser: state => state.currentUser,
  },
})
