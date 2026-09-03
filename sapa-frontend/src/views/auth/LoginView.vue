<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useForm } from 'vee-validate'
import * as yup from 'yup'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const showPassword = ref(false)

const schema = yup.object({
  email: yup.string().required('Email wajib diisi').email('Format email tidak valid'),
  password: yup.string().required('Password wajib diisi'),
})

const { handleSubmit, defineField, errors } = useForm({
  validationSchema: schema,
})

const [email, emailAttrs] = defineField('email')
const [password, passwordAttrs] = defineField('password')

const onSubmit = handleSubmit(async (values) => {
  try {
    await authStore.login(values)
    toast.success('Login berhasil!')
    router.push(route.query.redirect || '/')
  } catch {
    toast.error(authStore.error || 'Login gagal, periksa email dan password.')
  }
})
</script>

<template>
  <div class="min-h-screen bg-slate-900 flex items-center justify-center p-4 sm:p-6 overflow-hidden">
    <!-- Card Utama dengan Animasi Smooth Entry -->
    <div class="animate-fade-up w-full max-w-4xl bg-slate-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-slate-700/80 transition-all duration-500">
      
      <!-- Panel Kiri (Visual Banner) -->
      <!-- Panel Kiri (Visual Banner) -->
<div class="relative md:w-1/2 p-8 flex flex-col justify-between overflow-hidden min-h-[300px] md:min-h-[500px]">
  
  <!-- Gambar Latar Belakang -->
  <img 
    src="../../assets/login.jpeg" 
    alt="Background Sekolah" 
    class="absolute inset-0 w-full h-full object-cover"
  />

  <!-- Overlay Gelap / Gradient agar Teks Tetap Jelas Terbaca -->
  <div class="absolute inset-0 bg-gradient-to-tr from-slate-950 via-slate-900/80 to-emerald-950/70"></div>
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_var(--tw-gradient-stops))] from-emerald-500/20 via-transparent to-transparent"></div>
  
  <!-- Header Panel Kiri -->
  <div class="relative z-10 flex justify-between items-center animate-pulse-slow">
    <div class="flex items-center space-x-2">
      <!-- Container Foto Logo -->
      <div class="w-8 h-8 rounded-lg overflow-hidden flex items-center justify-center bg-slate-800 border border-slate-700/80 shadow-lg shadow-emerald-500/20">
        <img 
          src="../../assets/logo sapa.jpeg" 
          alt="Logo SAPA" 
          class="w-full h-full object-cover"
        />
      </div>
      <span class="text-white font-bold text-xl tracking-wide">SAPA</span>
    </div>
  </div>

  <!-- Hero Text -->
  <div class="relative z-10 my-auto py-8">
    <h2 class="text-2xl md:text-3xl font-bold text-white leading-tight mb-2 drop-shadow-md">
      Suara Anda,<br />Membangun Sekolah.
    </h2>
    <p class="text-slate-300 text-sm drop-shadow">
      Sistem Layanan Aspirasi dan Pengaduan Online Sekolah.
    </p>
  </div>

  <!-- Indicator Carousel -->
  <div class="relative z-10 flex space-x-2">
    <div class="h-1.5 w-4 rounded-full bg-slate-700/80"></div>
    <div class="h-1.5 w-8 rounded-full bg-emerald-400"></div>
  </div>
</div>
      <!-- Panel Kanan (Form Login) -->
      <div class="md:w-1/2 p-8 lg:p-10 flex flex-col justify-between bg-slate-800">
        <div>
          <!-- Judul di Ketengahkan -->
          <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white tracking-wide">Masuk ke Akun</h1>
            <p class="text-xs text-slate-400 mt-1">Silakan masukkan akun SAPA Anda</p>
          </div>

          <form @submit="onSubmit" class="space-y-4">
            <!-- Input Email -->
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Email Sekolah / NISN</label>
              <input
                v-model="email"
                v-bind="emailAttrs"
                type="email"
                placeholder="nama@sapa.sch.id"
                class="w-full bg-slate-700/40 border border-slate-600/80 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300"
              />
              <p v-if="errors.email" class="text-xs text-red-400 mt-1 animate-fade-in">{{ errors.email }}</p>
            </div>

            <!-- Input Password -->
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Password</label>
              <div class="relative">
                <input
                  v-model="password"
                  v-bind="passwordAttrs"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="••••••••"
                  class="w-full bg-slate-700/40 border border-slate-600/80 rounded-lg pl-3.5 pr-10 py-2.5 text-sm text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition-colors"
                >
                  <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.03 10.03 0 013.682-.782c4.478 0 8.268 2.943 9.542 7a10.017 10.017 0 01-2.06 3.65m-2.222 2.221L2 2l20 20" />
                  </svg>
                </button>
              </div>
              <p v-if="errors.password" class="text-xs text-red-400 mt-1 animate-fade-in">{{ errors.password }}</p>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="authStore.isLoading"
              class="w-full bg-emerald-500 hover:bg-emerald-600 active:scale-[0.98] text-slate-950 font-semibold rounded-lg py-2.5 text-sm transition-all duration-200 shadow-md shadow-emerald-500/10 disabled:opacity-50 mt-4"
            >
              {{ authStore.isLoading ? 'Memproses...' : 'Masuk' }}
            </button>
          </form>
        </div>

        <!-- Link Register di Paling Bawah Form -->
        <div class="mt-8 text-center pt-4 border-t border-slate-700/50">
          <p class="text-xs text-slate-400">
            Belum punya akun?
            <router-link to="/register" class="text-emerald-400 hover:text-emerald-300 hover:underline font-medium transition-colors ml-1">
              Daftar sekarang
            </router-link>
          </p>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
@keyframes fadeUp {
  0% {
    opacity: 0;
    transform: translateY(24px) scale(0.98);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes fadeIn {
  0% {
    opacity: 0;
  }
  100% {
    opacity: 1;
  }
}

.animate-fade-up {
  animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}
</style>