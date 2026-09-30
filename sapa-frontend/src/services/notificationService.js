import api from './api'

export default {
  async getNotifications(params = {}) {
    const { data } = await api.get('/api/notifications', { params })
    return data
  },

  async getUnreadCount() {
    const { data } = await api.get('/api/notifications/unread-count')
    return data
  },

  async markAsRead(id) {
    const { data } = await api.patch(`/api/notifications/${id}/read`)
    return data
  },

  async markAllAsRead() {
    const { data } = await api.patch('/api/notifications/read-all')
    return data
  },

  async deleteNotification(id) {
    const { data } = await api.delete(`/api/notifications/${id}`)
    return data
  },

  async deleteAllNotifications() {
    const { data } = await api.delete('/api/notifications')
    return data
  },
}