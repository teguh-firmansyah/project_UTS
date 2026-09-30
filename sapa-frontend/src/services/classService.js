import api from "./api";

export default {
  async getClasses(params = {}) {
    const { data } = await api.get("/api/admin/classes", { params });
    return data;
  },
  async createClass(payload) {
    const { data } = await api.post("/api/admin/classes", payload);
    return data;
  },
  async updateClass(id, payload) {
    const { data } = await api.patch(`/api/admin/classes/${id}`, payload);
    return data;
  },
  async deleteClass(id) {
    const { data } = await api.delete(`/api/admin/classes/${id}`);
    return data;
  },
  async getClassOptions(academicYear) {
    const { data } = await api.get("/api/classes/options", {
      params: { academic_year: academicYear },
    });
    return data;
  },
  async getPublicClassOptions() {
    const { data } = await api.get("/api/classes/options");
    return data;
  },
  async getAcademicYears() {
    const { data } = await api.get('/api/admin/classes/academic-years')
    return data
  },
};
