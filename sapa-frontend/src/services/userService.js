// src/services/userService.js
import api from "./api";

export default {
  async getUsers(params = {}) {
    const { data } = await api.get("/api/admin/users", { params });
    return data;
  },

  async createUser(payload) {
    const { data } = await api.post("/api/admin/users", payload);
    return data;
  },

  async updateUser(id, payload) {
    const { data } = await api.patch(`/api/admin/users/${id}`, payload);
    return data;
  },

  async toggleActive(id) {
    const { data } = await api.patch(`/api/admin/users/${id}/toggle-active`);
    return data;
  },

  async assignRole(id, role) {
    const { data } = await api.post(`/api/admin/users/${id}/assign-role`, {
      role,
    });
    return data;
  },

  async resetPassword(id, newPassword) {
    const { data } = await api.post(`/api/admin/users/${id}/reset-password`, {
      new_password: newPassword,
    });
    return data;
  },

  async deleteUser(id) {
    const { data } = await api.delete(`/api/admin/users/${id}`);
    return data;
  },

  // Export & Impor
  async exportStudents(params = {}) {
    const response = await api.get("/api/admin/students/export", {
      params,
      responseType: "blob",
    });
    return response.data;
  },

  async downloadImportTemplate() {
    const response = await api.get("/api/admin/students/import-template", {
      responseType: "blob",
    });
    return response.data;
  },

  async importStudents(file) {
    const formData = new FormData();
    formData.append("file", file);
    const { data } = await api.post("/api/admin/students/import", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    return data;
  },

  async getUserStats() {
    const { data } = await api.get("/api/admin/users/stats");
    return data;
  },

  async exportStudents(params = {}) {
    const response = await api.get("/api/admin/students/export", {
      params,
      responseType: "blob",
    });
    return response.data;
  },

  async exportStaff(role) {
    const response = await api.get("/api/admin/staff/export", {
      params: { role },
      responseType: "blob",
    });
    return response.data;
  },
};
