import { createFetch } from '@vueuse/core'
import { destr } from 'destr'
import { useRouter } from 'vue-router'

export const useApi = createFetch({
  baseUrl: import.meta.env.VITE_API_BASE_URL || '/api',
  fetchOptions: {
    headers: {
      Accept: 'application/json',
    },
  },
  options: {
    refetch: true,
    async beforeFetch({ options }) {
      const accessToken = useCookie('accessToken').value
      if (accessToken) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${accessToken}`,
        }
      }

      return { options }
    },
    afterFetch(ctx) {
      const { data, response } = ctx

      // Parse data if it's JSON
      let parsedData = null
      try {
        parsedData = destr(data)
      }

      catch (error) {
        console.error(error)
      }

      return { data: parsedData?.data, response }
    },
    onFetchError({ data, response }) {
      console.error('API Error:', response)

      if (response.status === 401) {
        useCookie('accessToken', { default: null }).value = null
        useCookie('userData', { default: null }).value = null
        location.href = '/login'
      }

      return { error: true, data }
    },
  },
})
