
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000',
  withCredentials: true,
  withXSRFToken: true,
   headers: {
    'Accept': 'application/json',
  },
})


// Interceptor global — kalau session expired (401), redirect ke login
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const authStore = window.__sapaAuthStore
      authStore?.clearAuth()
    }
    return Promise.reject(error)
  }
)

export default api