<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import * as yup from 'yup'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'
import {
  Check,
  Lock,
  User,
  Mail,
  CreditCard,
  GraduationCap,
  Eye,
  EyeOff,
  AlertTriangle,
  UserPlus,
  Loader2,
  ShieldCheck,
  CheckCircle2,
  XCircle
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const schema = yup.object({
  name: yup.string().required('Nama wajib diisi').min(3, 'Nama minimal 3 karakter'),
  email: yup.string().required('Email wajib diisi').email('Format email tidak valid'),
  identity_number: yup.string().required('NIS wajib diisi'),
  class_name: yup.string().required('Kelas wajib diisi'),
  password: yup.string().required('Password wajib diisi').min(8, 'Password minimal 8 karakter'),
  password_confirmation: yup
    .string()
    .required('Konfirmasi password wajib diisi')
    .oneOf([yup.ref('password')], 'Konfirmasi password tidak cocok'),
})

const { handleSubmit, defineField, errors } = useForm({ validationSchema: schema })

const [name, nameAttrs] = defineField('name')
const [email, emailAttrs] = defineField('email')
const [identity_number, identityAttrs] = defineField('identity_number')
const [class_name, classAttrs] = defineField('class_name')
const [password, passwordAttrs] = defineField('password')
const [password_confirmation, passwordConfirmAttrs] = defineField('password_confirmation')

const onSubmit = handleSubmit(async (values) => {
  try {
    await authStore.register(values)
    toast.success('Registrasi berhasil! Selamat datang di SAPA.')
    router.push('/')
  } catch {
    toast.error(authStore.error || 'Registrasi gagal, periksa kembali data kamu.')
  }
})

const strengthMeta = computed(() => {
  const pw = password.value || ''
  if (!pw) return null

  let score = 0
  if (pw.length >= 8) score++
  if (/[a-zA-Z]/.test(pw) && /[0-9]/.test(pw)) score++
  if (/[^a-zA-Z0-9]/.test(pw) || (/[a-z]/.test(pw) && /[A-Z]/.test(pw))) score++
  score = Math.min(score, 3)

  if (score <= 1) return { label: 'Lemah', text: 'text-red-400', bar: 'bg-red-400', score }
  if (score === 2) return { label: 'Sedang', text: 'text-amber-400', bar: 'bg-amber-400', score }
  return { label: 'Kuat', text: 'text-emerald-400', bar: 'bg-emerald-400', score }
})

const confirmState = computed(() => {
  const c = password_confirmation.value || ''
  if (!c) return 'empty'
  return c === (password.value || '') ? 'match' : 'mismatch'
})

const benefits = [
  { title: 'Laporkan dengan Aman', desc: 'Pengaduan fasilitas & perundungan, dengan opsi anonim' },
  { title: 'Pantau Status Laporan', desc: 'Ikuti perkembangan setiap laporan secara transparan' },
  { title: 'Aspirasi Didengar', desc: 'Usulan Anda diteruskan langsung ke pihak sekolah' },
]

const logoFailed = ref(false)
const bgFailed = ref(false)
</script>

<template>
  <div class="sapa-root relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-950 px-4 py-10 font-sans text-slate-100 antialiased selection:bg-emerald-500/25 sm:px-6">

    <div class="pointer-events-none absolute inset-0" aria-hidden="true"
         style="background: radial-gradient(760px 420px at 50% -5%, rgba(16, 185, 129, 0.07), transparent 65%)"></div>

    <main class="relative z-10 w-full max-w-245">
      <div class="auth-card relative flex flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/40 md:flex-row">

        <span class="absolute inset-x-0 top-0 z-20 h-0.5 bg-linear-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

        <section class="relative flex min-h-50 flex-col overflow-hidden bg-slate-900 md:w-1/2 md:min-h-0">

          <img
            v-if="!bgFailed"
            src="../../assets/images/register.png"
            alt=""
            aria-hidden="true"
            draggable="false"
            class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover brightness-[.62] contrast-110 saturate-[.65]"
            @error="bgFailed = true"
          />

          <div class="pointer-events-none absolute inset-0 bg-linear-to-tr from-slate-950 via-slate-900/80 to-emerald-950/70" aria-hidden="true"></div>
          <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-slate-950/90 via-slate-950/10 to-slate-950/40" aria-hidden="true"></div>

          <div class="relative z-10 flex flex-1 flex-col justify-between p-6 sm:p-7 md:p-8 lg:p-10">

            <div class="soft-in flex items-center justify-between gap-3" style="animation-delay: .2s">
              <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg border border-slate-700/60 bg-slate-800 ring-1 ring-emerald-500/20">
                  <img
                    v-if="!logoFailed"
                    src="../../assets/logo/logo sapa.jpeg"
                    alt="Logo SAPA"
                    class="h-full w-full object-cover"
                    @error="logoFailed = true"
                  />
                  <span v-else class="text-sm font-extrabold text-emerald-400">S</span>
                </div>
                <span class="text-lg font-bold tracking-wide text-white">SAPA</span>
              </div>
            </div>

            <div class="soft-in my-auto py-5 md:py-0" style="animation-delay: .3s">
              <h2 class="text-[22px] font-extrabold leading-[1.15] tracking-tight text-white drop-shadow-lg sm:text-2xl md:text-3xl">
                Buat Akun Siswa,<br />
                <span class="text-emerald-400">Sampaikan Aspirasimu.</span>
              </h2>
              <p class="mt-3 max-w-xs text-xs leading-relaxed text-slate-300 drop-shadow sm:text-sm">
                Bergabung bersama komunitas sekolah untuk mewujudkan lingkungan belajar yang lebih aman dan nyaman.
              </p>
            </div>

            <div class="soft-in hidden space-y-4 md:block" style="animation-delay: .4s">
              <ul class="space-y-3">
                <li v-for="b in benefits" :key="b.title" class="flex items-start gap-2.5">
                  <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                    <Check class="h-2.5 w-2.5" />
                  </span>
                  <div class="min-w-0">
                    <p class="text-[11px] font-semibold text-slate-200">{{ b.title }}</p>
                    <p class="mt-0.5 text-[11px] leading-snug text-slate-500">{{ b.desc }}</p>
                  </div>
                </li>
              </ul>

              <div class="flex items-center gap-1.5 border-t border-slate-800/60 pt-4" aria-hidden="true">
                <span class="h-1 w-2.5 rounded-full bg-slate-600/70"></span>
                <span class="h-1 w-7 rounded-full bg-emerald-400"></span>
              </div>

              <div class="flex items-start gap-2.5">
                <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-emerald-400/80" />
                <p class="max-w-xs text-[11px] leading-relaxed text-slate-400">
                  Data Anda terlindungi dan hanya digunakan untuk verifikasi identitas siswa.
                </p>
              </div>
            </div>
          </div>
        </section>

        <section class="relative flex flex-1 flex-col p-6 sm:p-8 md:w-1/2 md:p-9 lg:p-11">
          <div class="mx-auto flex w-full max-w-105 flex-1 flex-col">

            <div class="my-auto">
              <div class="soft-in text-center" style="animation-delay: .35s">
                <h1 class="text-xl font-bold tracking-tight text-white sm:text-2xl">Buat Akun Baru</h1>
                <p class="mt-1.5 text-xs text-slate-400">Lengkapi data diri siswa untuk mendaftar</p>
              </div>

              <form @submit="onSubmit" class="soft-in mx-auto mt-7 w-full space-y-6" style="animation-delay: .45s">

                <section class="space-y-4">
                  <div class="flex items-center gap-2.5">
                    <span class="font-mono text-[11px] font-semibold text-emerald-500/80">01</span>
                    <h2 class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Data Diri</h2>
                    <span class="h-px flex-1 bg-slate-800/70" aria-hidden="true"></span>
                  </div>

                  <div>
                    <label for="reg-name" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Nama Lengkap</label>
                    <div class="relative">
                      <User class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                      <input
                        id="reg-name"
                        v-model="name"
                        v-bind="nameAttrs"
                        type="text"
                        autocomplete="name"
                        placeholder="Masukkan nama lengkap"
                        :aria-invalid="!!errors.name || undefined"
                        class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                        :class="errors.name ? 'border-red-400/60' : 'border-slate-800'"
                      />
                    </div>
                    <p v-if="errors.name" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                      <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                      {{ errors.name }}
                    </p>
                  </div>

                  <div>
                    <label for="reg-email" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Email</label>
                    <div class="relative">
                      <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                      <input
                        id="reg-email"
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
                      <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                      {{ errors.email }}
                    </p>
                  </div>

                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label for="reg-nis" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">NIS</label>
                      <div class="relative">
                        <CreditCard class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                        <input
                          id="reg-nis"
                          v-model="identity_number"
                          v-bind="identityAttrs"
                          type="text"
                          inputmode="numeric"
                          autocomplete="off"
                          placeholder="12345678"
                          :aria-invalid="!!errors.identity_number || undefined"
                          class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-9 pr-3 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                          :class="errors.identity_number ? 'border-red-400/60' : 'border-slate-800'"
                        />
                      </div>
                      <p v-if="errors.identity_number" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                        <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                        {{ errors.identity_number }}
                      </p>
                    </div>

                    <div>
                      <label for="reg-class" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Kelas</label>
                      <div class="relative">
                        <GraduationCap class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                        <input
                          id="reg-class"
                          v-model="class_name"
                          v-bind="classAttrs"
                          type="text"
                          autocomplete="off"
                          placeholder="XII RPL 1"
                          :aria-invalid="!!errors.class_name || undefined"
                          class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-9 pr-3 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                          :class="errors.class_name ? 'border-red-400/60' : 'border-slate-800'"
                        />
                      </div>
                      <p v-if="errors.class_name" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                        <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                        {{ errors.class_name }}
                      </p>
                    </div>
                  </div>
                </section>

                <section class="space-y-4 border-t border-slate-800/70 pt-6">
                  <div class="flex items-center gap-2.5">
                    <span class="font-mono text-[11px] font-semibold text-emerald-500/80">02</span>
                    <h2 class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Keamanan Akun</h2>
                    <span class="h-px flex-1 bg-slate-800/70" aria-hidden="true"></span>
                  </div>

                  <div>
                    <div class="mb-2 flex items-center justify-between">
                      <label for="reg-password" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Password</label>
                      <span class="font-mono text-[10px] text-slate-600" title="Minimal 8 karakter">min. 8 karakter</span>
                    </div>
                    <div class="relative">
                      <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                      <input
                        id="reg-password"
                        v-model="password"
                        v-bind="passwordAttrs"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
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
                        <Eye v-if="!showPassword" class="h-4 w-4" />
                        <EyeOff v-else class="h-4 w-4" />
                      </button>
                    </div>

                    <div v-if="password && !errors.password && strengthMeta" class="mt-2 flex items-center gap-2.5">
                      <div class="flex gap-1" aria-hidden="true">
                        <span class="h-1 w-6 rounded-full transition-colors duration-200" :class="strengthMeta.score >= 1 ? strengthMeta.bar : 'bg-slate-800'"></span>
                        <span class="h-1 w-6 rounded-full transition-colors duration-200" :class="strengthMeta.score >= 2 ? strengthMeta.bar : 'bg-slate-800'"></span>
                        <span class="h-1 w-6 rounded-full transition-colors duration-200" :class="strengthMeta.score >= 3 ? strengthMeta.bar : 'bg-slate-800'"></span>
                      </div>
                      <span class="text-[10px] font-medium" :class="strengthMeta.text">Kekuatan: {{ strengthMeta.label }}</span>
                    </div>

                    <p v-if="errors.password" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                      <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                      {{ errors.password }}
                    </p>
                  </div>

                  <div>
                    <div class="mb-2 flex items-center justify-between">
                      <label for="reg-password-confirm" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Konfirmasi Password</label>
                      <span
                        v-if="confirmState !== 'empty'"
                        class="flex items-center gap-1 text-[10px] font-semibold"
                        :class="confirmState === 'match' ? 'text-emerald-400' : 'text-red-400'"
                      >
                        <CheckCircle2 v-if="confirmState === 'match'" class="h-3 w-3" />
                        <XCircle v-else class="h-3 w-3" />
                        {{ confirmState === 'match' ? 'Cocok' : 'Belum cocok' }}
                      </span>
                    </div>
                    <div class="relative">
                      <ShieldCheck class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                      <input
                        id="reg-password-confirm"
                        v-model="password_confirmation"
                        v-bind="passwordConfirmAttrs"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        :aria-invalid="!!errors.password_confirmation || undefined"
                        class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-11 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                        :class="errors.password_confirmation ? 'border-red-400/60' : 'border-slate-800'"
                      />
                      <button
                        type="button"
                        @click="showConfirmPassword = !showConfirmPassword"
                        :aria-label="showConfirmPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                        :title="showConfirmPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                        class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-500 transition-colors duration-200 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
                      >
                        <Eye v-if="!showConfirmPassword" class="h-4 w-4" />
                        <EyeOff v-else class="h-4 w-4" />
                      </button>
                    </div>
                    <p v-if="errors.password_confirmation" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                      <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                      {{ errors.password_confirmation }}
                    </p>
                  </div>
                </section>

                <div class="border-t border-slate-800/70 pt-5">
                  <button
                    type="submit"
                    :disabled="authStore.isLoading"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                  >
                    <UserPlus v-if="!authStore.isLoading" class="h-4 w-4" />
                    <Loader2 v-else class="h-4 w-4 animate-spin" />
                    {{ authStore.isLoading ? 'Memproses...' : 'Buat Akun' }}
                  </button>
                </div>
              </form>
            </div>

            <div class="soft-in mt-6 border-t border-slate-800/70 pt-5 text-center" style="animation-delay: .55s">
              <p class="text-xs text-slate-400">
                Sudah punya akun?
                <router-link
                  to="/login"
                  class="ml-1 font-semibold text-emerald-400 transition-colors duration-200 hover:text-emerald-300 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
                >
                  Masuk sekarang
                </router-link>
              </p>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.auth-card {
  animation: card-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes card-in {
  from { opacity: 0; transform: translateY(20px) scale(0.98); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

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