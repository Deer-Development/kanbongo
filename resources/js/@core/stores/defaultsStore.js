import { defineStore } from 'pinia'

export const useDefaultsStore = defineStore('defaultsStore', {
  state: () => ({
    defaultGroups: {},
  }),
  actions: {
    async fetchDefaultGroups(search = '') {
      try {
        const res = await $api(`/default-group/options`, {
          method: 'GET',
          onResponseError({ response }) {
            throw response._data.errors
          },
        })
          
        this.defaultGroups = res.data
      } catch (error) {
        console.error('Fetch Default Groups Error:', error)
        throw error
      }
    },
  },
  getters: {
    getGroups: state => state.defaultGroups,
  },
})

