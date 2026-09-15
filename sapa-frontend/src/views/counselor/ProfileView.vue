<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const logoFailed = ref(false)
const avatarFailed = ref(false)

/* ---------------------------------- */
/* Turunan tampilan                   */
/* ---------------------------------- */
const initialsOf = (name) => {
  const n = (name || '').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/)
  return (parts.length > 1 ? parts[0][0] + parts[parts.length - 1][0] : n.slice(0, 2)).toUpperCase()
}

/* ---------------------------------- */
/* Data profil BK (existing)          */
/* ---------------------------------- */
/* ---------------------------------- */
/* Data profil BK — sekarang dari authStore, field disesuaikan skema */
/* ---------------------------------- */
const profile = ref({
  name: authStore.user?.name || '',
  nip: authStore.user?.identity_number || '', // mapping identity_number -> nip di UI
  role: 'Guru Bimbingan Konseling / Konselor Utama', // statis, tidak dari backend
  email: authStore.user?.email || '',
  phone: authStore.user?.phone || '',
  room: authStore.user?.room || '',
  bio: authStore.user?.bio || '',
  avatar_url: authStore.user?.avatar || null,
})

/* Snapshot untuk tombol "Atur Ulang" */
const snapshotOf = (p) => JSON.parse(JSON.stringify({
  name: p.name, nip: p.nip, email: p.email, phone: p.phone, room: p.room, bio: p.bio,
}))

const initialSnapshot = ref(snapshotOf(profile.value))

/* ---------------------------------- */
/* Avatar: unggah / hapus / pratinjau */
/* ---------------------------------- */
const avatarFile = ref(null)
const avatarPreview = ref(null)
const avatarInputRef = ref(null)

const displayAvatar = computed(() => avatarPreview.value || profile.value.avatar_url || null)

const handleAvatarChange = (e) => {
  const file = e.target.files[0]
  e.target.value = ''
  if (!file) return

  if (!file.type.startsWith('image/')) {
    toast.error('Format berkas tidak didukung. Unggah PNG, JPG, atau JPEG.')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    toast.error('Ukuran gambar maksimal 2MB!')
    return
  }

  if (avatarPreview.value?.startsWith('blob:')) URL.revokeObjectURL(avatarPreview.value)
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

/* Kembalikan ke foto tersimpan (batalkan unggahan) */
const removeAvatar = () => {
  if (avatarPreview.value?.startsWith('blob:')) URL.revokeObjectURL(avatarPreview.value)
  avatarFile.value = null
  avatarPreview.value = null
}

const triggerAvatarPick = () => avatarInputRef.value?.click()

/* ---------------------------------- */
/* Validasi formulir profil           */
/* ---------------------------------- */
const profileErrors = ref({})

const validateProfileForm = () => {
  const e = {}
  const f = profile.value

  if (!f.name.trim()) e.name = 'Nama wajib diisi'
  else if (f.name.trim().length < 3) e.name = 'Nama minimal 3 karakter'

  if (!f.nip.trim()) e.nip = 'NIP wajib diisi'

  if (!f.email.trim()) e.email = 'Email wajib diisi'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email.trim())) e.email = 'Format email tidak valid'

  if (!f.phone.trim()) e.phone = 'Nomor telepon wajib diisi'
  else if (!/^[0-9+\-\s()]{8,16}$/.test(f.phone.trim())) e.phone = 'Format nomor telepon tidak valid'

  if (!f.room.trim()) e.room = 'Lokasi ruangan wajib diisi'

  return e
}

/* Bersihkan error saat field diedit */
watch(
  () => [profile.value.name, profile.value.nip, profile.value.email, profile.value.phone, profile.value.room],
  () => {
    for (const key of ['name', 'nip', 'email', 'phone', 'room']) {
      if (profileErrors.value[key]) profileErrors.value[key] = ''
    }
  }
)

/* ---------------------------------- */
/* Simpan profil (behavior existing + */
/* validasi & sinkronisasi store)     */
/* ---------------------------------- */
const isSaving = ref(false)
const showSuccessAlert = ref(false)

const handleSaveProfile = async () => {
  profileErrors.value = validateProfileForm()
  if (Object.keys(profileErrors.value).length > 0) {
    toast.error('Mohon lengkapi data profil yang belum valid.')
    return
  }

  isSaving.value = true
  showSuccessAlert.value = false

  try {
    const payload = { ...profile.value }
    if (avatarFile.value) payload.avatar = avatarFile.value
    await authStore.updateProfile(payload)

    showSuccessAlert.value = true
    setTimeout(() => { showSuccessAlert.value = false }, 3000)

    initialSnapshot.value = snapshotOf(profile.value)
    avatarFile.value = null
    avatarPreview.value = null
  } catch (error) {
    const validationErrors = error?.response?.data?.errors
    if (validationErrors) {
      profileErrors.value = Object.fromEntries(
        Object.entries(validationErrors).map(([k, v]) => [k, v[0]])
      )
    }
    toast.error(authStore.error || 'Gagal menyimpan profil. Silakan coba lagi.')
  } finally {
    isSaving.value = false
  }
}

/* Atur ulang formulir ke kondisi tersimpan */
const resetProfileForm = () => {
  Object.assign(profile.value, snapshotOf(initialSnapshot.value))
  profileErrors.value = {}
  removeAvatar()
}

/* ---------------------------------- */
/* Ganti password — wajib verifikasi  */
/* password lama (selaras profil      */
/* siswa)                             */
/* ---------------------------------- */
const passwordForm = ref({
  old_password: '',
  new_password: '',
  new_password_confirmation: '',
})
const passwordErrors = ref({})
const showOldPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const isChangingPassword = ref(false)

const passwordStrength = computed(() => {
  const pw = passwordForm.value.new_password || ''
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

const confirmMatch = computed(() => {
  const c = passwordForm.value.new_password_confirmation || ''
  if (!c) return 'empty'
  return c === (passwordForm.value.new_password || '') ? 'match' : 'mismatch'
})

const validatePasswordForm = () => {
  const e = {}
  const f = passwordForm.value

  if (!f.old_password) e.old_password = 'Password saat ini wajib diisi'

  if (!f.new_password) e.new_password = 'Password baru wajib diisi'
  else if (f.new_password.length < 8) e.new_password = 'Password baru minimal 8 karakter'
  else if (f.old_password && f.new_password === f.old_password) {
    e.new_password = 'Password baru harus berbeda dari password saat ini'
  }

  if (!f.new_password_confirmation) e.new_password_confirmation = 'Konfirmasi password wajib diisi'
  else if (f.new_password_confirmation !== f.new_password) {
    e.new_password_confirmation = 'Konfirmasi password tidak cocok'
  }

  return e
}

watch(
  () => [passwordForm.value.old_password, passwordForm.value.new_password, passwordForm.value.new_password_confirmation],
  () => {
    for (const key of ['old_password', 'new_password', 'new_password_confirmation']) {
      if (passwordErrors.value[key]) passwordErrors.value[key] = ''
    }
  }
)

const handleChangePassword = async () => {
  passwordErrors.value = validatePasswordForm()
  if (Object.keys(passwordErrors.value).length > 0) return

  isChangingPassword.value = true
  try {
    await authStore.changePassword(passwordForm.value)
    toast.success('Password berhasil diubah.')
    passwordForm.value = { old_password: '', new_password: '', new_password_confirmation: '' }
    passwordErrors.value = {}
    showOldPassword.value = showNewPassword.value = showConfirmPassword.value = false
  } catch (error) {
    const validationErrors = error?.response?.data?.errors
    if (validationErrors?.old_password) {
      passwordErrors.value.old_password = validationErrors.old_password[0]
    }
    toast.error(authStore.error || 'Gagal mengubah password. Periksa kembali password saat ini.')
  } finally {
    isChangingPassword.value = false
  }
}

/* ---------------------------------- */
/* Navigasi                           */
/* ---------------------------------- */
const goToQueue = () => router.push('/counselor/bullying-queue')
const goToArchive = () => router.push('/counselor/archived-reports')

const goBack = () => {
  if (typeof window !== 'undefined' && window.history.state?.back) {
    router.back()
  } else {
    router.push('/counselor/bullying-queue')
  }
}

const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas ============ -->
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">

        <!-- Kembali + merek panel -->
        <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
          <button
            type="button"
            @click="goBack"
            class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <svg class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span class="hidden sm:inline">Kembali</span>
          </button>

          <span class="hidden h-6 w-px bg-slate-800 sm:block" aria-hidden="true"></span>

          <div class="hidden min-w-0 items-center gap-2.5 sm:flex">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20">
              <img v-if="!logoFailed" src="@/assets/logo sapa.jpeg" alt="Logo SAPA" class="h-full w-full object-cover" @error="logoFailed = true" />
              <span v-else class="text-sm font-extrabold text-emerald-400">S</span>
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-[15px] font-extrabold leading-none tracking-tight text-white">SAPA</p>
                <span class="rounded border border-rose-500/30 bg-rose-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-rose-400">Panel BK</span>
              </div>
              <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Profil Petugas Bimbingan Konseling</p>
            </div>
          </div>
        </div>

        <h1 class="absolute left-1/2 hidden -translate-x-1/2 text-sm font-semibold text-slate-200 sm:block">Profil Petugas BK</h1>

        <button
          type="button"
          @click="goToQueue"
          class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h10" />
          </svg>
          <span class="hidden sm:inline">Antrian Aktif</span>
          <span class="sm:hidden">Antrian</span>
        </button>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- Notifikasi berhasil (behavior existing, restyled) -->
      <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showSuccessAlert"
          role="status"
          class="mb-6 flex items-center gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-semibold text-emerald-400"
        >
          <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span>Perubahan profil berhasil disimpan!</span>
        </div>
      </transition>

      <div class="grid items-start gap-6 lg:grid-cols-[340px_minmax(0,1fr)]">

        <!-- ===== Kartu identitas (sticky) ===== -->
        <aside class="fade-up space-y-6 lg:sticky lg:top-20">
          <div class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/25">
            <span class="absolute inset-x-0 top-0 z-10 h-[2px] bg-gradient-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

            <!-- Identitas -->
            <div class="p-6">
              <div class="flex flex-col items-center text-center">
                <div class="relative">
                  <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-2 border-emerald-500/40 bg-slate-800 text-xl font-bold text-emerald-400 shadow-xl shadow-emerald-500/10">
                    <img v-if="displayAvatar && !avatarFailed" :src="displayAvatar" :alt="'Foto ' + profile.name" class="h-full w-full object-cover" @error="avatarFailed = true" />
                    <template v-else>{{ initialsOf(profile.name) }}</template>
                  </div>
                  <span class="absolute -bottom-0.5 -right-0.5 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500 text-slate-950 ring-4 ring-slate-900" title="Akun terverifikasi">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </span>
                </div>

                <h2 class="mt-4 text-base font-extrabold leading-snug tracking-tight text-white">{{ profile.name }}</h2>
                <p class="mt-1 text-xs font-semibold text-emerald-400">{{ profile.role }}</p>

                <div class="mt-3 inline-flex items-center rounded-full border border-slate-800 bg-slate-950/60 px-3 py-1 font-mono text-[11px] text-slate-400">
                  NIP. {{ profile.nip }}
                </div>
              </div>

              <!-- Status bertugas -->
              <div class="mt-6 flex items-center justify-between rounded-lg border border-emerald-500/25 bg-emerald-500/[0.05] px-3.5 py-3">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status Petugas</p>
                  <p class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-emerald-400">
                    <span class="relative flex h-2 w-2">
                      <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                      <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                    </span>
                    Aktif / Bertugas
                  </p>
                </div>
                <svg class="h-5 w-5 shrink-0 text-emerald-400/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
            </div>

            <!-- Detail akun -->
            <dl class="divide-y divide-slate-800/60 border-t border-slate-800/70 bg-slate-950/40">
              <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="shrink-0 text-[11px] font-medium text-slate-500">Email</dt>
                <dd class="min-w-0 truncate text-xs text-slate-300">{{ profile.email }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="shrink-0 text-[11px] font-medium text-slate-500">Telepon</dt>
                <dd class="min-w-0 truncate text-xs text-slate-300">{{ profile.phone }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="shrink-0 text-[11px] font-medium text-slate-500">Ruang BK</dt>
                <dd class="min-w-0 truncate text-xs text-slate-300">{{ profile.room }}</dd>
              </div>
            </dl>

            <!-- Catatan kepercayaan -->
            <p class="flex items-center justify-center gap-1.5 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 text-center text-[11px] text-slate-600">
              <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              Data akun petugas hanya diakses oleh Anda &amp; administrator sistem
            </p>
          </div>

          <!-- Navigasi panel -->
          <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
            <div class="border-b border-slate-800/70 px-5 py-4">
              <h3 class="text-sm font-bold tracking-tight text-slate-100">Navigasi Panel BK</h3>
              <p class="mt-0.5 text-[11px] text-slate-500">Akses cepat tugas penanganan kasus.</p>
            </div>
            <div class="divide-y divide-slate-800/60">
              <button
                type="button"
                @click="goToQueue"
                class="group flex w-full items-center gap-3 px-5 py-3.5 text-left transition-colors duration-150 hover:bg-slate-800/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-400/50"
              >
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-rose-500/25 bg-rose-500/10 text-rose-400">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h10" />
                  </svg>
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-slate-200">Antrian Kasus Aktif</span>
                  <span class="block text-[10px] text-slate-500">Laporan yang sedang ditangani</span>
                </span>
                <svg class="h-4 w-4 shrink-0 text-slate-600 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </button>

              <button
                type="button"
                @click="goToArchive"
                class="group flex w-full items-center gap-3 px-5 py-3.5 text-left transition-colors duration-150 hover:bg-slate-800/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-400/50"
              >
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 text-slate-300">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                  </svg>
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-slate-200">Arsip &amp; Riwayat Kasus</span>
                  <span class="block text-[10px] text-slate-500">Dokumentasi kasus yang ditutup</span>
                </span>
                <svg class="h-4 w-4 shrink-0 text-slate-600 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          </div>
        </aside>

        <!-- ===== Pengaturan ===== -->
        <section class="fade-up min-w-0 space-y-6" style="animation-delay: 90ms">

          <div class="flex items-center gap-3">
            <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">Pengaturan Profil</h2>
              <p class="mt-0.5 text-xs text-slate-500">Perbarui data petugas dan keamanan akun panel BK.</p>
            </div>
          </div>

          <!-- ===== Kartu 1: Data profil ===== -->
          <form @submit.prevent="handleSaveProfile" class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/25">
            <span class="absolute inset-x-0 top-0 z-10 h-[2px] bg-gradient-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

            <div class="flex items-center justify-between gap-4 border-b border-slate-800/70 px-5 py-4 sm:px-6">
              <div class="flex items-center gap-2.5">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <div>
                  <h3 class="text-sm font-bold tracking-tight text-slate-100">Informasi Pribadi &amp; Kontak</h3>
                  <p class="mt-0.5 text-[11px] text-slate-500">Data identitas petugas yang tertera pada sistem</p>
                </div>
              </div>
              <span class="hidden font-mono text-[9px] font-semibold uppercase tracking-[0.18em] text-slate-600 sm:block">Data Profil</span>
            </div>

            <div class="space-y-5 p-5 sm:p-6">

              <!-- Foto profil -->
              <div class="flex items-center gap-4 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                <label
                  for="bk-avatar-input"
                  class="group relative flex h-16 w-16 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-full border-2 border-slate-700 bg-slate-800 text-sm font-bold text-emerald-400 transition-colors duration-200 hover:border-emerald-500/50"
                >
                  <img v-if="displayAvatar" :src="displayAvatar" alt="Pratinjau foto profil" class="h-full w-full object-cover" />
                  <template v-else>{{ initialsOf(profile.name) }}</template>
                  <span class="absolute inset-0 flex items-center justify-center bg-slate-950/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100" aria-hidden="true">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </span>
                </label>
                <input id="bk-avatar-input" ref="avatarInputRef" type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />

                <div class="min-w-0 flex-1">
                  <p class="text-xs font-semibold text-slate-200">Foto Profil</p>
                  <p class="mt-0.5 text-[11px] text-slate-500">PNG, JPG, JPEG · maks 2MB</p>
                  <div class="mt-2 flex flex-wrap gap-2">
                    <button
                      type="button"
                      @click="triggerAvatarPick"
                      class="inline-flex items-center gap-1 rounded-md border border-slate-700 bg-slate-800/60 px-2.5 py-1 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
                    >
                      Ganti Foto
                    </button>
                    <button
                      v-if="avatarPreview"
                      type="button"
                      @click="removeAvatar"
                      class="inline-flex items-center gap-1 rounded-md border border-slate-700 bg-slate-800/60 px-2.5 py-1 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-rose-500/40 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50"
                    >
                      Batalkan
                    </button>
                  </div>
                </div>
              </div>

              <!-- Nama + NIP -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label for="bk-name" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Nama Lengkap &amp; Gelar</label>
                  <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <input
                      id="bk-name"
                      v-model="profile.name"
                      type="text"
                      autocomplete="name"
                      placeholder="Nama lengkap & gelar"
                      :aria-invalid="!!profileErrors.name || undefined"
                      class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                      :class="profileErrors.name ? 'border-red-400/60' : 'border-slate-800'"
                    />
                  </div>
                  <p v-if="profileErrors.name" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                    {{ profileErrors.name }}
                  </p>
                </div>

                <div>
                  <label for="bk-nip" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">NIP</label>
                  <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0h4m-6 6h2m-2 4h2m4-4h2m-2 4h2" />
                    </svg>
                    <input
                      id="bk-nip"
                      v-model="profile.nip"
                      type="text"
                      autocomplete="off"
                      placeholder="Nomor Induk Pegawai"
                      :aria-invalid="!!profileErrors.nip || undefined"
                      class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-9 pr-3 font-mono text-sm text-slate-100 placeholder-slate-600 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                      :class="profileErrors.nip ? 'border-red-400/60' : 'border-slate-800'"
                    />
                  </div>
                  <p v-if="profileErrors.nip" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                    {{ profileErrors.nip }}
                  </p>
                </div>
              </div>

              <!-- Email + Telepon -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label for="bk-email" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Alamat Email (Akun)</label>
                  <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <input
                      id="bk-email"
                      v-model="profile.email"
                      type="email"
                      autocomplete="email"
                      placeholder="nama@sekolah.sch.id"
                      :aria-invalid="!!profileErrors.email || undefined"
                      class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                      :class="profileErrors.email ? 'border-red-400/60' : 'border-slate-800'"
                    />
                  </div>
                  <p v-if="profileErrors.email" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                    {{ profileErrors.email }}
                  </p>
                </div>

                <div>
                  <label for="bk-phone" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">No. WhatsApp / Telepon</label>
                  <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <input
                      id="bk-phone"
                      v-model="profile.phone"
                      type="tel"
                      inputmode="tel"
                      autocomplete="tel"
                      placeholder="08xx xxxx xxxx"
                      :aria-invalid="!!profileErrors.phone || undefined"
                      class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                      :class="profileErrors.phone ? 'border-red-400/60' : 'border-slate-800'"
                    />
                  </div>
                  <p v-if="profileErrors.phone" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                    {{ profileErrors.phone }}
                  </p>
                </div>
              </div>

              <!-- Ruangan -->
              <div>
                <label for="bk-room" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Lokasi Ruangan BK</label>
                <div class="relative">
                  <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  <input
                    id="bk-room"
                    v-model="profile.room"
                    type="text"
                    autocomplete="organization"
                    placeholder="Contoh: Ruang BK Gedung Utama Fl. 2"
                    :aria-invalid="!!profileErrors.room || undefined"
                    class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                    :class="profileErrors.room ? 'border-red-400/60' : 'border-slate-800'"
                  />
                </div>
                <p v-if="profileErrors.room" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                  <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                  {{ profileErrors.room }}
                </p>
              </div>

              <!-- Bio -->
              <div>
                <div class="mb-2 flex items-center justify-between">
                  <label for="bk-bio" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Fokus Pendampingan / Deskripsi Peran</label>
                  <span class="font-mono text-[10px] tabular-nums" :class="(profile.bio || '').length > 0 ? 'text-emerald-500/80' : 'text-slate-600'">{{ (profile.bio || '').length }} karakter</span>
                </div>
                <div class="relative">
                  <svg class="pointer-events-none absolute left-3.5 top-3 h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                  <textarea
                    id="bk-bio"
                    v-model="profile.bio"
                    rows="3"
                    placeholder="Tuliskan fokus pendampingan Anda..."
                    class="w-full resize-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm leading-relaxed text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  ></textarea>
                </div>
                <p class="mt-1.5 text-[10px] text-slate-500">Deskripsi ini membantu siswa mengenali pendekatan konseling Anda.</p>
              </div>
            </div>

            <!-- Kaki formulir -->
            <div class="flex flex-col-reverse gap-3 border-t border-slate-800/70 bg-slate-950/30 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
              <p class="flex items-center gap-1.5 text-[10px] text-slate-600">
                <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Perubahan data profil tercatat pada jejak audit sistem.
              </p>

              <div class="flex flex-col-reverse gap-2.5 sm:flex-row">
                <button
                  type="button"
                  @click="resetProfileForm"
                  class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Atur Ulang
                </button>

                <button
                  type="submit"
                  :disabled="isSaving"
                  class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                >
                  <svg v-if="!isSaving" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                  <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                  {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
              </div>
            </div>
          </form>

          <!-- ===== Kartu 2: Keamanan akun ===== -->
          <form @submit.prevent="handleChangePassword" class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/25">
            <span class="absolute inset-x-0 top-0 z-10 h-[2px] bg-gradient-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

            <div class="flex items-center justify-between gap-4 border-b border-slate-800/70 px-5 py-4 sm:px-6">
              <div class="flex items-center gap-2.5">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <div>
                  <h3 class="text-sm font-bold tracking-tight text-slate-100">Keamanan Akun</h3>
                  <p class="mt-0.5 text-[11px] text-slate-500">Verifikasi password saat ini untuk mengatur password baru</p>
                </div>
              </div>
              <span class="hidden font-mono text-[9px] font-semibold uppercase tracking-[0.18em] text-slate-600 sm:block">Ganti Password</span>
            </div>

            <div class="space-y-5 p-5 sm:p-6">

              <!-- Catatan keamanan -->
              <div class="flex items-start gap-2.5 rounded-lg border border-emerald-500/25 bg-emerald-500/[0.05] px-3.5 py-3">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-400/90" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <p class="text-[11px] leading-relaxed text-slate-400">
                  Password Anda melindungi kerahasiaan kasus perundungan yang sedang ditangani. Masukkan
                  <span class="font-medium text-slate-300">password saat ini</span> untuk mengatur password baru.
                </p>
              </div>

              <!-- Password saat ini -->
              <div>
                <label for="bk-pw-old" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Password Saat Ini</label>
                <div class="relative">
                  <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  <input
                    id="bk-pw-old"
                    v-model="passwordForm.old_password"
                    :type="showOldPassword ? 'text' : 'password'"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    :aria-invalid="!!passwordErrors.old_password || undefined"
                    class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-11 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                    :class="passwordErrors.old_password ? 'border-red-400/60' : 'border-slate-800'"
                  />
                  <button
                    type="button"
                    @click="showOldPassword = !showOldPassword"
                    :aria-label="showOldPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                    class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-500 transition-colors duration-200 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
                  >
                    <svg v-if="!showOldPassword" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.03 10.03 0 013.682-.782c4.478 0 8.268 2.943 9.542 7a10.017 10.017 0 01-2.06 3.65m-2.222 2.221L2 2l20 20" />
                    </svg>
                  </button>
                </div>
                <p v-if="passwordErrors.old_password" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                  <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                  {{ passwordErrors.old_password }}
                </p>
              </div>

              <!-- Password baru -->
              <div>
                <div class="mb-2 flex items-center justify-between">
                  <label for="bk-pw-new" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Password Baru</label>
                  <span class="font-mono text-[10px] text-slate-600">min. 8 karakter</span>
                </div>
                <div class="relative">
                  <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  <input
                    id="bk-pw-new"
                    v-model="passwordForm.new_password"
                    :type="showNewPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    placeholder="••••••••"
                    :aria-invalid="!!passwordErrors.new_password || undefined"
                    class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-11 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                    :class="passwordErrors.new_password ? 'border-red-400/60' : 'border-slate-800'"
                  />
                  <button
                    type="button"
                    @click="showNewPassword = !showNewPassword"
                    :aria-label="showNewPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                    class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-500 transition-colors duration-200 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
                  >
                    <svg v-if="!showNewPassword" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.03 10.03 0 013.682-.782c4.478 0 8.268 2.943 9.542 7a10.017 10.017 0 01-2.06 3.65m-2.222 2.221L2 2l20 20" />
                    </svg>
                  </button>
                </div>

                <div v-if="passwordForm.new_password && !passwordErrors.new_password && passwordStrength" class="mt-2 flex items-center gap-2.5">
                  <div class="flex gap-1" aria-hidden="true">
                    <span class="h-1 w-6 rounded-full transition-colors duration-200" :class="passwordStrength.score >= 1 ? passwordStrength.bar : 'bg-slate-800'"></span>
                    <span class="h-1 w-6 rounded-full transition-colors duration-200" :class="passwordStrength.score >= 2 ? passwordStrength.bar : 'bg-slate-800'"></span>
                    <span class="h-1 w-6 rounded-full transition-colors duration-200" :class="passwordStrength.score >= 3 ? passwordStrength.bar : 'bg-slate-800'"></span>
                  </div>
                  <span class="text-[10px] font-medium" :class="passwordStrength.text">Kekuatan: {{ passwordStrength.label }}</span>
                </div>

                <p v-if="passwordErrors.new_password" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                  <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                  {{ passwordErrors.new_password }}
                </p>
              </div>

              <!-- Konfirmasi -->
              <div>
                <div class="mb-2 flex items-center justify-between">
                  <label for="bk-pw-confirm" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Konfirmasi Password Baru</label>
                  <span
                    v-if="confirmMatch !== 'empty'"
                    class="flex items-center gap-1 text-[10px] font-semibold"
                    :class="confirmMatch === 'match' ? 'text-emerald-400' : 'text-red-400'"
                  >
                    <svg v-if="confirmMatch === 'match'" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <svg v-else class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    {{ confirmMatch === 'match' ? 'Cocok' : 'Belum cocok' }}
                  </span>
                </div>
                <div class="relative">
                  <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                  <input
                    id="bk-pw-confirm"
                    v-model="passwordForm.new_password_confirmation"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    placeholder="••••••••"
                    :aria-invalid="!!passwordErrors.new_password_confirmation || undefined"
                    class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-11 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                    :class="passwordErrors.new_password_confirmation ? 'border-red-400/60' : 'border-slate-800'"
                  />
                  <button
                    type="button"
                    @click="showConfirmPassword = !showConfirmPassword"
                    :aria-label="showConfirmPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                    class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-500 transition-colors duration-200 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
                  >
                    <svg v-if="!showConfirmPassword" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.03 10.03 0 013.682-.782c4.478 0 8.268 2.943 9.542 7a10.017 10.017 0 01-2.06 3.65m-2.222 2.221L2 2l20 20" />
                    </svg>
                  </button>
                </div>
                <p v-if="passwordErrors.new_password_confirmation" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                  <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                  {{ passwordErrors.new_password_confirmation }}
                </p>
              </div>
            </div>

            <!-- Kaki formulir -->
            <div class="flex flex-col-reverse gap-3 border-t border-slate-800/70 bg-slate-950/30 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
              <p class="text-[10px] text-slate-600">Setelah diubah, sesi Anda tetap aktif pada perangkat ini.</p>
              <button
                type="submit"
                :disabled="isChangingPassword"
                class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
              >
                <svg v-if="!isChangingPassword" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ isChangingPassword ? 'Memproses...' : 'Ubah Password' }}
              </button>
            </div>
          </form>
        </section>
      </div>
    </main>

    <!-- ============ Footer ============ -->
    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="text-[11px] text-slate-600">Laporan perundungan ditangani secara rahasia oleh petugas berwenang</p>
      </div>
    </footer>
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
/* Entrance seksi: fade-up halus dengan stagger */
.fade-up {
  opacity: 0;
  animation: fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  .fade-up { animation: none; opacity: 1; }
}
</style>