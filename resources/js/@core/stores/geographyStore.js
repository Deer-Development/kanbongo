import { defineStore } from 'pinia'

export const useGeographyStore = defineStore('geographyStore', {
  state: () => ({
    countries: [],
    states: {},
    cities: {},
  }),
  actions: {
    async fetchCountries(search = '') {
      try {
        this.countries = await $api(`/geography/countries?search=${search}`, {
          method: 'GET',
          onResponseError({ response }) {
            throw response._data.errors
          },
        })
      } catch (error) {
        console.error('Fetch Countries Error:', error)
        throw error
      }
    },
    async fetchStatesByCountry(countryId) {
      if (!this.states[countryId]) {
        try {
          this.states[countryId] = await $api(`/geography/countries/${countryId}/states`, {
            method: 'GET',
            onResponseError({ response }) {
              throw response._data.errors
            },
          })

        } catch (error) {
          console.error('Fetch States Error:', error)
          throw error
        }
      }
    },
    async fetchCitiesByState(stateId) {
      if (!this.cities[stateId]) {
        try {
          this.cities[stateId] = await $api(`/geography/states/${stateId}/cities`, {
            method: 'GET',
            onResponseError({ response }) {
              throw response._data.errors
            },
          })
        } catch (error) {
          console.error('Fetch Cities Error:', error)
          throw error
        }
      }
    },
  },
  getters: {
    getCountries: state => state.countries,
    getStates: state => countryId => state.states[countryId] || [],
    getCities: state => stateId => state.cities[stateId] || [],
  },
})

