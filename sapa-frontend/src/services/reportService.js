import api from "./api";

export default {
  // ===== Riwayat & Statistik Laporan Saya (Dashboard Siswa) =====
  async getMyReports(params = {}) {
    const { data } = await api.get("/api/my-reports", { params });
    return data;
  },

  async getMyStats() {
    const { data } = await api.get("/api/my-reports/stats");
    return data;
  },

  // ===== Submit per jenis laporan =====
  async submitAspiration(formData) {
    const { data } = await api.post("/api/aspirations", formData);
    return data;
  },

  async submitFacilityReport(formData) {
    const { data } = await api.post("/api/facility-reports", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    return data;
  },

  async submitBullyingReport(formData) {
    const { data } = await api.post("/api/bullying-reports", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    return data;
  },

  // ===== Area Counselor / BK =====
  async getBullyingQueue(params = {}) {
    const { data } = await api.get("/api/counselor/bullying-queue", { params });
    return data;
  },

  async getBullyingStats() {
    const { data } = await api.get("/api/counselor/bullying-stats");
    return data;
  },

  async getBullyingReportDetail(id) {
    const { data } = await api.get(`/api/counselor/bullying-reports/${id}`);
    return data;
  },

  async handleBullyingReport(id, payload) {
    const { data } = await api.patch(
      `/api/counselor/bullying-reports/${id}/handle`,
      payload,
    );
    return data;
  },

  async revealIdentity(id) {
    const { data } = await api.post(
      `/api/counselor/bullying-reports/${id}/reveal-identity`,
    );
    return data;
  },
  async getBullyingArchive() {
    const { data } = await api.get("/api/counselor/bullying-queue", {
      params: { "status[]": ["resolved", "rejected"] },
    });
    return data;
  },

  // Comment
  async getComments(reportId) {
    const { data } = await api.get(`/api/reports/${reportId}/comments`);
    return data;
  },

  async addComment(reportId, comment) {
    const { data } = await api.post(`/api/reports/${reportId}/comments`, {
      comment,
    });
    return data;
  },

  async getReportDetail(id) {
    const { data } = await api.get(`/api/reports/${id}`);
    return data;
  },

  // Aspiration
  async getAspirations(params = {}) {
    const { data } = await api.get('/api/aspirations', { params })
    return data
  },

  async toggleUpvote(reportId) {
    const { data } = await api.post(`/api/aspirations/${reportId}/upvote`)
    return data
  },
};
