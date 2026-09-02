import { defineStore } from 'pinia'
import authService from '@/services/authService'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isInitialized: false, // penting: beda dari isLoading, untuk cek "app sudah selesai cek session?"
    isLoading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,

    roles: (state) => state.user?.roles ?? [],
    permissions: (state) => state.user?.permissions ?? [],

    isStudent: (state) => state.user?.roles?.includes('student') ?? false,
    isStaff: (state) => state.user?.roles?.includes('staff') ?? false,
    isCounselor: (state) => state.user?.roles?.includes('counselor') ?? false,
    isAdmin: (state) => state.user?.roles?.includes('admin') ?? false,
  },

  actions: {
    hasPermission(permission) {
      return this.permissions.includes(permission)
    },

    hasRole(role) {
      return this.roles.includes(role)
    },

    async register(payload) {
      this.isLoading = true
      this.error = null
      try {
        const data = await authService.register(payload)
        this.user = data.user
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Registrasi gagal.'
        throw err
      } finally {
        this.isLoading = false
      }
    },

    async login(credentials) {
      this.isLoading = true
      this.error = null
      try {
        const data = await authService.login(credentials)
        this.user = data.user
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Login gagal.'
        throw err
      } finally {
        this.isLoading = false
      }
    },

    async logout() {
      try {
        await authService.logout()
      } finally {
        this.clearAuth()
      }
    },

    clearAuth() {
      this.user = null
    },

    // Dipanggil sekali saat app pertama kali load — cek apakah session masih valid
    async fetchCurrentUser() {
      this.isLoading = true
      try {
        const data = await authService.getCurrentUser()
        this.user = data.user
      } catch {
        this.user = null
      } finally {
        this.isLoading = false
        this.isInitialized = true
      }
    },
  },
})