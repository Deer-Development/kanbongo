// import { useQuery, useMutation, useQueryClient, useInfiniteQuery } from '@tanstack/vue-query'
// import axios from 'axios'
// import { ref } from 'vue'
//
// const axiosInstance = axios.create({
//   baseURL: process.env.VUE_APP_API_BASE_URL || 'http://localhost:8000/api',
//   headers: {
//     'Content-Type': 'application/json',
//   },
// })
//
// axiosInstance.interceptors.response.use(
//   response => response,
//   error => {
//     const router = useRouter()
//     if (error.response?.status === 401) {
//       // Redirect to login page
//       router.push({ name: 'login' })
//     }
//
//     return Promise.reject(error)
//   },
// )
//
// export function useApi() {
//   const queryClient = useQueryClient()
//   const isLoading = ref(false)
//
//   const fetcher = async (url, params = {}) => {
//     isLoading.value = true
//     try {
//       const { data } = await axiosInstance.get(url, { params })
//
//       return data
//     } finally {
//       isLoading.value = false
//     }
//   }
//
//   const poster = async (url, body) => {
//     const { data } = await axiosInstance.post(url, body)
//
//     return data
//   }
//
//   const updater = async (url, body) => {
//     const { data } = await axiosInstance.put(url, body)
//
//     return data
//   }
//
//   const deleter = async url => {
//     const { data } = await axiosInstance.delete(url)
//
//     return data
//   }
//
//   const useInfiniteGet = (key, url, options = {}) => {
//     return useInfiniteQuery(
//       key,
//       ({ pageParam = 1 }) => fetcher(url, { page: pageParam }),
//       {
//         getNextPageParam: (lastPage, allPages) => {
//           return lastPage.nextPage ? allPages.length + 1 : undefined
//         },
//         ...options,
//       },
//     )
//   }
//
//   const useGet = (key, url, params = {}, options = {}) => {
//     return useQuery({
//       queryKey: key,
//       queryFn: () => fetcher(url, params),
//       ...options,
//     })
//   }
//
//   const usePost = (url, options = {}) => {
//     return useMutation({
//       mutationFn: body => poster(url, body),
//       onError: error => {
//         console.error("POST Request Failed: ", error)
//       },
//       ...options,
//     })
//   }
//
//   const usePut = (url, options = {}) => {
//     return useMutation({
//       mutationFn: body => updater(url, body),
//       onError: error => {
//         console.error("PUT Request Failed: ", error)
//       },
//       ...options,
//     })
//   }
//
//   const useDelete = (url, options = {}) => {
//     return useMutation({
//       mutationFn: () => deleter(url),
//       onError: error => {
//         console.error("DELETE Request Failed: ", error)
//       },
//       ...options,
//     })
//   }
//
//   const prefetchQuery = (key, url, params = {}) => {
//     return queryClient.prefetchQuery({
//       queryKey: key,
//       queryFn: () => fetcher(url, params),
//     })
//   }
//
//   const invalidateQuery = key => {
//     queryClient.invalidateQueries({ queryKey: key })
//   }
//
//   const clearCache = () => {
//     queryClient.clear()
//   }
//
//   return {
//     useGet,
//     usePost,
//     usePut,
//     useDelete,
//     useInfiniteGet,
//     prefetchQuery,
//     invalidateQuery,
//     clearCache,
//     isLoading,
//   }
// }
