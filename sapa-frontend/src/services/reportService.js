import api from './api'

export default {
  // ===== Submit per jenis laporan =====
  async submitAspiration(formData) {
    const { data } = await api.post('/api/aspirations', formData)
    return data
  },

  async submitFacilityReport(formData) {
    const { data } = await api.post('/api/facility-reports', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
  },

  async submitBullyingReport(formData) {
    const { data } = await api.post('/api/bullying-reports', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
  },

  // ===== Area Counselor / BK =====
  async getBullyingQueue(params = {}) {
    const { data } = await api.get('/api/counselor/bullying-queue', { params })
    return data
  },

  async getBullyingStats() {
    const { data } = await api.get('/api/counselor/bullying-stats')
    return data
  },

  // PERBAIKAN: Gunakan endpoint khusus detail laporan perundungan milik Counselor
  async getReportDetail(id) {
    const { data } = await api.get(`/api/counselor/bullying-reports/${id}`)
    return data
  },

  async handleBullyingReport(id, payload) {
    const { data } = await api.patch(`/api/counselor/bullying-reports/${id}/handle`, payload)
    return data
  },

  async revealIdentity(id) {
    const { data } = await api.post(`/api/counselor/bullying-reports/${id}/reveal-identity`)
    return data
  },
  async getBullyingArchive() {
    const { data } = await api.get('/api/counselor/bullying-queue', {
      params: { 'status[]': ['resolved', 'rejected'] },
    })
    return data
  },
}