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

/* ---------------------------------- */
/* Validasi (logika existing)         */
/* ---------------------------------- */
const schema = yup.object({
  email: yup.string().required('Email wajib diisi').email('Format email tidak valid'),
  password: yup.string().required('Password wajib diisi'),
})

const { handleSubmit, defineField, errors } = useForm({
  validationSchema: schema,
})

const [email, emailAttrs] = defineField('email')
const [password, passwordAttrs] = defineField('password')

/* ---------------------------------- */
/* Login (behavior existing)          */
/* ---------------------------------- */
const onSubmit = handleSubmit(async (values) => {
  try {
    await authStore.login(values)
    toast.success('Login berhasil!')
    const target = route.query.redirect || authStore.defaultRoute
    router.push(target)
  } catch {
    toast.error(authStore.error || 'Login gagal, periksa email dan password.')
  }
})

/* ---------------------------------- */
/* Tambahan kecil (non-invasif)       */
/* ---------------------------------- */
const logoFailed = ref(false)
</script>

<template>
  <div class="sapa-root relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-950 px-4 py-10 font-sans text-slate-100 antialiased selection:bg-emerald-500/25 sm:px-6">

    <!-- Cahaya ambien emerald yang sangat halus -->
    <div class="pointer-events-none absolute inset-0" aria-hidden="true"
         style="background: radial-gradient(760px 420px at 50% -5%, rgba(16, 185, 129, 0.07), transparent 65%)"></div>

    <!-- ============ Kartu autentikasi ============ -->
    <main class="relative z-10 w-full max-w-[980px]">
      <div class="auth-card relative flex flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/40 md:min-h-[560px] md:flex-row">

        <!-- Garis aksen emerald di puncak kartu -->
        <span class="absolute inset-x-0 top-0 z-20 h-[2px] bg-gradient-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

        <!-- ===== Panel visual kiri ===== -->
        <section class="relative flex min-h-[210px] flex-col overflow-hidden md:w-1/2 md:min-h-0">

          <!-- Foto latar: lingkungan sekolah, digelapkan & sedikit desaturasi -->
          <img
            src="../../assets/login.jpeg"
            alt=""
            aria-hidden="true"
            draggable="false"
            class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover brightness-[.62] contrast-110 saturate-[.65]"
          />

          <!-- Lapisan overlay agar teks tetap terbaca -->
          <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-slate-950 via-slate-900/80 to-emerald-950/70" aria-hidden="true"></div>
          <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/10 to-slate-950/40" aria-hidden="true"></div>

          <!-- Konten panel -->
          <div class="relative z-10 flex flex-1 flex-col justify-between p-6 sm:p-7 md:p-8 lg:p-10">

            <!-- Kepala: logo & merek -->
            <div class="soft-in flex items-center gap-2.5" style="animation-delay: .2s">
              <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg border border-slate-700/60 bg-slate-800 ring-1 ring-emerald-500/20">
                <img
                  v-if="!logoFailed"
                  src="../../assets/logo sapa.jpeg"
                  alt="Logo SAPA"
                  class="h-full w-full object-cover"
                  @error="logoFailed = true"
                />
                <span v-else class="text-sm font-extrabold text-emerald-400">S</span>
              </div>
              <span class="text-lg font-bold tracking-wide text-white">SAPA</span>
            </div>

            <!-- Teks hero -->
            <div class="soft-in my-auto py-6 md:py-0" style="animation-delay: .3s">
              <h2 class="text-[22px] font-extrabold leading-[1.15] tracking-tight text-white drop-shadow-lg sm:text-2xl md:text-3xl">
                Suara Anda,<br />
                <span class="text-emerald-400">Membangun Sekolah.</span>
              </h2>
              <p class="mt-3 max-w-xs text-xs leading-relaxed text-slate-300 drop-shadow sm:text-sm">
                Sistem Layanan Aspirasi dan Pengaduan Online Sekolah.
              </p>

              <!-- Kanal pelaporan -->
              <div class="mt-6 hidden flex-wrap gap-x-4 gap-y-2 sm:flex">
                <span class="flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                  <span class="h-1.5 w-1.5 rounded-full bg-purple-400" aria-hidden="true"></span> Aspirasi
                </span>
                <span class="flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                  <span class="h-1.5 w-1.5 rounded-full bg-cyan-400" aria-hidden="true"></span> Fasilitas
                </span>
                <span class="flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                  <span class="h-1.5 w-1.5 rounded-full bg-rose-400" aria-hidden="true"></span> Perundungan
                </span>
              </div>
            </div>

            <!-- Indikator dekoratif + pesan kepercayaan -->
            <div class="soft-in hidden space-y-4 md:block" style="animation-delay: .4s">
              <div class="flex items-center gap-1.5" aria-hidden="true">
                <span class="h-1 w-7 rounded-full bg-emerald-400"></span>
                <span class="h-1 w-2.5 rounded-full bg-slate-600/70"></span>
              </div>

              <div class="flex items-start gap-2.5">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-400/80" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <p class="max-w-xs text-[11px] leading-relaxed text-slate-400">
                  Ruang aman untuk menyampaikan suara dan membangun lingkungan sekolah yang lebih baik.
                </p>
              </div>
            </div>
          </div>
        </section>

        <!-- ===== Panel formulir kanan ===== -->
        <section class="relative flex flex-1 flex-col p-6 sm:p-8 md:w-1/2 md:p-10 lg:p-12">
          <div class="mx-auto flex w-full max-w-[400px] flex-1 flex-col">

            <div class="my-auto">
              <!-- Kepala formulir -->
              <div class="soft-in text-center" style="animation-delay: .35s">
                <h1 class="text-xl font-bold tracking-tight text-white sm:text-2xl">Masuk ke Akun</h1>
                <p class="mt-1.5 text-xs text-slate-400">Silakan masukkan akun SAPA Anda</p>
              </div>

              <!-- Formulir -->
              <form @submit="onSubmit" class="soft-in mx-auto mt-8 w-full space-y-5" style="animation-delay: .45s">

                <!-- Email / NISN -->
                <div>
                  <label for="login-email" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                    Email Sekolah / NISN
                  </label>
                  <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <input
                      id="login-email"
                      v-model="email"
                      v-bind="emailAttrs"
                      type="email"
                      autocomplete="email"
                      placeholder="nama@sapa.sch.id"
                      :aria-invalid="!!errors.email || undefined"
                      class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                      :class="errors.email ? 'border-red-400/60' : 'border-slate-800'"
                    />
                  </div>
                  <p v-if="errors.email" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    {{ errors.email }}
                  </p>
                </div>

                <!-- Password -->
                <div>
                  <div class="mb-2 flex items-center justify-between">
                    <label for="login-password" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                      Password
                    </label>
                  </div>
                  <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <input
                      id="login-password"
                      v-model="password"
                      v-bind="passwordAttrs"
                      :type="showPassword ? 'text' : 'password'"
                      autocomplete="current-password"
                      placeholder="••••••••"
                      :aria-invalid="!!errors.password || undefined"
                      class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-11 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                      :class="errors.password ? 'border-red-400/60' : 'border-slate-800'"
                    />
                    <button
                      type="button"
                      @click="showPassword = !showPassword"
                      :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                      :title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                      class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-500 transition-colors duration-200 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
                    >
                      <svg v-if="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.03 10.03 0 013.682-.782c4.478 0 8.268 2.943 9.542 7a10.017 10.017 0 01-2.06 3.65m-2.222 2.221L2 2l20 20" />
                      </svg>
                    </button>
                  </div>
                  <p v-if="errors.password" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    {{ errors.password }}
                  </p>
                </div>

                <!-- Tombol masuk -->
                <button
                  type="submit"
                  :disabled="authStore.isLoading"
                  class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                >
                  <svg v-if="!authStore.isLoading" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                  </svg>
                  <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                  {{ authStore.isLoading ? 'Memproses...' : 'Masuk' }}
                </button>
              </form>
            </div>

            <!-- Tautan pendaftaran -->
            <div class="soft-in mt-8 border-t border-slate-800/70 pt-5 text-center" style="animation-delay: .55s">
              <p class="text-xs text-slate-400">
                Belum punya akun?
                <router-link
                  to="/register"
                  class="ml-1 font-semibold text-emerald-400 transition-colors duration-200 hover:text-emerald-300 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
                >
                  Daftar sekarang
                </router-link>
              </p>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<style>
/* Inter sebagai identitas tipografi (aman dihapus jika sudah dikonfigurasi di Tailwind) */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.sapa-root {
  font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}
</style>

<style scoped>
/* Entrance kartu: fade-up dengan skala halus */
.auth-card {
  animation: card-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes card-in {
  from { opacity: 0; transform: translateY(20px) scale(0.98); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Konten panel masuk perlahan menyusul kartu */
.soft-in {
  opacity: 0;
  animation: soft-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes soft-in {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  .auth-card,
  .soft-in { animation: none; opacity: 1; }
}
</style>