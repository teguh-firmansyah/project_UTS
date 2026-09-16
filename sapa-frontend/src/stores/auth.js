import { defineStore } from 'pinia'
import authService from '@/services/authService'
import api from '@/services/api'
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isInitialized: false,
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

    defaultRoute: (state) => {
      const roles = state.user?.roles ?? []
      if (roles.includes('admin')) return { name: 'admin-dashboard' }
      if (roles.includes('counselor')) return { name: 'counselor-bullying-queue' }
      if (roles.includes('staff')) return { name: 'staff-facility-queue' }
      return { name: 'dashboard' }
    },
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

    async updateProfile(payload) {
      this.isLoading = true
      this.error = null
      try {
        const formData = new FormData()
        formData.append('name', payload.name)
        formData.append('identity_number', payload.nip || payload.identity_number)
        formData.append('email', payload.email)
        formData.append('phone', payload.phone || '')
        formData.append('room', payload.room || '')
        formData.append('bio', payload.bio || '')
        if (payload.avatar) formData.append('avatar', payload.avatar)

        const { data } = await api.post('/api/profile?_method=PATCH', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        this.user = data.user
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menyimpan profil.'
        throw err
      } finally {
        this.isLoading = false
      }
    },

    async changePassword(payload) {
      this.isLoading = true
      this.error = null
      try {
        const { data } = await api.patch('/api/profile/password', {
          old_password: payload.old_password,
          new_password: payload.new_password,
          new_password_confirmation: payload.new_password_confirmation,
        })
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal mengubah password.'
        throw err
      } finally {
        this.isLoading = false
      }
    },
  },
})