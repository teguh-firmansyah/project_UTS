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
}