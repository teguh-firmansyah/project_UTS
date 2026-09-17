// src/services/userService.js
import api from './api'

export default {
  async getUsers(params = {}) {
    const { data } = await api.get('/api/admin/users', { params })
    return data
  },

  async createUser(payload) {
    const { data } = await api.post('/api/admin/users', payload)
    return data
  },

  async updateUser(id, payload) {
    const { data } = await api.patch(`/api/admin/users/${id}`, payload)
    return data
  },

  async toggleActive(id) {
    const { data } = await api.patch(`/api/admin/users/${id}/toggle-active`)
    return data
  },

  async assignRole(id, role) {
    const { data } = await api.post(`/api/admin/users/${id}/assign-role`, { role })
    return data
  },

  async resetPassword(id, newPassword) {
    const { data } = await api.post(`/api/admin/users/${id}/reset-password`, { new_password: newPassword })
    return data
  },

  async deleteUser(id) {
    const { data } = await api.delete(`/api/admin/users/${id}`)
    return data
  },
}