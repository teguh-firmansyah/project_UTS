<script setup>
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()
  toast.success('Berhasil logout.')
  router.push('/login')
}
</script>

<template>
  <h1>Dashboard Murid</h1>
  <button @click="handleLogout" class="text-sm text-gray-500 hover:text-red-500">
    Keluar
  </button>
</template>