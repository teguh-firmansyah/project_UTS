import api from './api'

export default {
  async getCsrfCookie() {
    await api.get('/sanctum/csrf-cookie')
  },

  async register(payload) {
    await this.getCsrfCookie()
    const { data } = await api.post('/api/register', payload)
    return data
  },

  async login(credentials) {
    await this.getCsrfCookie()
    const { data } = await api.post('/api/login', credentials)
    return data
  },

  async logout() {
    await api.post('/api/logout')
  },

  async getCurrentUser() {
    const { data } = await api.get('/api/me')
    return data
  },
}