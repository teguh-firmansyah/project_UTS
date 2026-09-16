<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'
import reportService from '@/services/reportService'
import {
  ArrowLeft, Settings, Check, CheckCircle2, GraduationCap, Lightbulb,
  FileText, TrendingUp, Lock, Clock, Camera, User, Mail, CreditCard,
  Phone, X, Eye, EyeOff, ShieldCheck, AlertTriangle, Loader2,
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const currentUser = computed(() => authStore.user || {})

const initialsOf = (name) => {
  const n = (name || '').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/)
  return (parts.length > 1 ? parts[0][0] + parts[parts.length - 1][0] : n.slice(0, 2)).toUpperCase()
}

const displayInitials = computed(() => initialsOf(currentUser.value.name || 'Siswa SAPA'))
const displayAvatar = computed(() => currentUser.value.avatar || null)

const activeTab = ref('aspirations')
const isLoadingActivity = ref(true)

/* ---------------------------------- */
/* Data aktivitas — sekarang dari API  */
/* ---------------------------------- */
const myAspirations = ref([])
const myReports = ref([])

async function loadActivity() {
  isLoadingActivity.value = true
  try {
    const data = await reportService.getMyReports({ per_page: 50 })
    const all = data.data

    myAspirations.value = all
      .filter((r) => r.type === 'aspiration')
      .map((r) => ({
        id: r.id,
        title: r.title,
        content: r.description_excerpt,
        category: 'Aspirasi',
        createdAt: formatRelative(r.created_at),
        likesCount: r.type_meta?.upvotes_count ?? 0,
        status: STATUS_MAP[r.status] ?? r.status,
      }))

    myReports.value = all
      .filter((r) => r.type !== 'aspiration')
      .map((r) => ({
        id: r.id,
        title: r.title,
        content: r.description_excerpt,
        category: r.type === 'facility' ? 'Fasilitas' : 'Perundungan',
        createdAt: formatRelative(r.created_at),
        status: STATUS_MAP[r.status] ?? r.status,
      }))
  } catch {
    toast.error('Gagal memuat riwayat aktivitas.')
  } finally {
    isLoadingActivity.value = false
  }
}

onMounted(loadActivity)

const STATUS_MAP = {
  pending: 'Menunggu', reviewing: 'Ditinjau', in_progress: 'Diproses',
  resolved: 'Selesai', rejected: 'Ditolak',
}

function formatRelative(dateString) {
  const date = new Date(dateString)
  const diffMs = Date.now() - date.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  if (diffDays === 0) return 'hari ini'
  if (diffDays === 1) return '1 hari yang lalu'
  if (diffDays < 30) return `${diffDays} hari yang lalu`
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const totalVotesReceived = computed(() =>
  myAspirations.value.reduce((acc, curr) => acc + curr.likesCount, 0)
)

const getStatusBadge = (status) => {
  switch (status) {
    case 'Menunggu': return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    case 'Ditinjau': return 'bg-blue-500/10 text-blue-400 border-blue-500/20'
    case 'Diproses': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    case 'Selesai': return 'bg-slate-500/10 text-slate-300 border-slate-500/20'
    case 'Ditolak': return 'bg-red-500/10 text-red-400 border-red-500/20'
    default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  }
}

const getTypeBadge = (category) => {
  switch (category) {
    case 'Aspirasi': return 'bg-purple-500/10 text-purple-400 border-purple-500/20'
    case 'Fasilitas': return 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20'
    case 'Perundungan': return 'bg-rose-500/10 text-rose-400 border-rose-500/20'
    default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  }
}

/* ---------------------------------- */
/* Modal pengaturan akun (tetap sama, diperbaiki bug syntax)          */
/* ---------------------------------- */
const isEditModalOpen = ref(false)
const isSaving = ref(false)
const activeModalTab = ref('profile')

const profileForm = ref({
  name: '', email: '', identity_number: '', phone: '', class_name: '',
})
const profileErrors = ref({})

const avatarFile = ref(null)
const avatarPreview = ref(null)
const avatarInputRef = ref(null)

const passwordForm = ref({ old_password: '', new_password: '', new_password_confirmation: '' })
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

const openEditModal = () => {
  profileForm.value = {
    name: currentUser.value.name || '',
    email: currentUser.value.email || '',
    identity_number: currentUser.value.identity_number || '',
    phone: currentUser.value.phone || '',
    class_name: currentUser.value.class_name || '',
  }
  avatarPreview.value = currentUser.value.avatar || null
  avatarFile.value = null
  passwordForm.value = { old_password: '', new_password: '', new_password_confirmation: '' }
  profileErrors.value = {}
  passwordErrors.value = {}
  showOldPassword.value = showNewPassword.value = showConfirmPassword.value = false
  activeModalTab.value = 'profile'
  isEditModalOpen.value = true
}

const closeModal = () => { isEditModalOpen.value = false }

const handleEscKey = (e) => {
  if (e.key === 'Escape' && isEditModalOpen.value) closeModal()
}

watch(isEditModalOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => window.addEventListener('keydown', handleEscKey))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscKey)
  document.body.style.overflow = ''
  if (avatarPreview.value?.startsWith('blob:')) URL.revokeObjectURL(avatarPreview.value)
})

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

const removeAvatar = () => {
  if (avatarPreview.value?.startsWith('blob:')) URL.revokeObjectURL(avatarPreview.value)
  avatarFile.value = null
  avatarPreview.value = null
}

const triggerAvatarPick = () => avatarInputRef.value?.click()

const validateProfileForm = () => {
  const e = {}
  const f = profileForm.value
  if (!f.name.trim()) e.name = 'Nama wajib diisi'
  else if (f.name.trim().length < 3) e.name = 'Nama minimal 3 karakter'
  if (!f.email.trim()) e.email = 'Email wajib diisi'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email.trim())) e.email = 'Format email tidak valid'
  if (!f.identity_number.trim()) e.identity_number = 'NIS wajib diisi'
  if (f.phone.trim() && !/^[0-9+\-\s()]{8,16}$/.test(f.phone.trim())) {
    e.phone = 'Format nomor telepon tidak valid'
  }
  return e
}

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

const handleSaveProfile = async () => {
  profileErrors.value = validateProfileForm()
  if (Object.keys(profileErrors.value).length > 0) return

  isSaving.value = true
  try {
    const payload = { ...profileForm.value }
    if (avatarFile.value) payload.avatar = avatarFile.value

    // PERBAIKAN: authStore.updateProfile kita rancang untuk field "nip" (BK),
    // tapi ProfileController generic pakai identity_number langsung —
    // di sini kirim langsung sesuai field asli tanpa mapping nip
    const formData = new FormData()
    formData.append('name', payload.name)
    formData.append('identity_number', payload.identity_number)
    formData.append('email', payload.email)
    formData.append('phone', payload.phone || '')
    formData.append('class_name', payload.class_name || '')
    if (payload.avatar) formData.append('avatar', payload.avatar)

    const api = (await import('@/services/api')).default
    const { data } = await api.post('/api/profile?_method=PATCH', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    authStore.user = data.user

    toast.success('Profil berhasil diperbarui.')
    closeModal()
  } catch (error) {
    const validationErrors = error?.response?.data?.errors
    if (validationErrors) {
      profileErrors.value = Object.fromEntries(
        Object.entries(validationErrors).map(([k, v]) => [k, v[0]])
      )
    }
    toast.error(error?.response?.data?.message || 'Gagal memperbarui profil. Silakan coba lagi.')
  } finally {
    isSaving.value = false
  }
}

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

const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">
        <button
          type="button"
          @click="router.back()"
          class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
        >
          <ArrowLeft class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5" />
          <span class="hidden sm:inline">Kembali</span>
          <span class="sm:hidden">Kembali</span>
        </button>

        <h1 class="absolute left-1/2 hidden -translate-x-1/2 text-sm font-semibold text-slate-200 sm:block">Profil Saya</h1>

        <button
          type="button"
          @click="openEditModal"
          class="group inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
        >
          <Settings class="h-3.5 w-3.5" />
          <span class="hidden sm:inline">Pengaturan Akun</span>
          <span class="sm:hidden">Edit</span>
        </button>
      </div>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
      <div class="grid items-start gap-6 lg:grid-cols-[340px_minmax(0,1fr)]">
        <aside class="fade-up space-y-6 lg:sticky lg:top-20">
          <div class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/25">
            <span class="absolute inset-x-0 top-0 z-10 h-0.75 bg-linear-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

            <div class="p-6">
              <div class="flex flex-col items-center text-center">
                <div class="relative">
                  <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-2 border-emerald-500/40 bg-slate-800 text-xl font-bold text-emerald-400 shadow-xl shadow-emerald-500/10">
                    <img v-if="displayAvatar" :src="displayAvatar" alt="Foto profil" class="h-full w-full object-cover" />
                    <template v-else>{{ displayInitials }}</template>
                  </div>
                  <span class="absolute -bottom-0.5 -right-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-slate-950 ring-4 ring-slate-900" title="Akun terverifikasi">
                    <Check class="h-3.5 w-3.5 stroke-3" />
                  </span>
                </div>

                <h2 class="mt-4 text-lg font-extrabold tracking-tight text-white">{{ currentUser.name || 'Siswa SAPA' }}</h2>
                <p class="mt-1 truncate font-mono text-[11px] text-slate-500">{{ currentUser.email || 'email@sekolah.id' }}</p>

                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                  <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold capitalize text-emerald-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                    {{ currentUser.role || 'Siswa' }}
                  </span>
                  <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-slate-950/60 px-2.5 py-0.5 text-[11px] font-medium text-slate-300">
                    <GraduationCap class="h-3.5 w-3.5 text-slate-500" />
                    {{ currentUser.class_name || currentUser.class || 'Kelas belum diatur' }}
                  </span>
                </div>
              </div>

              <dl class="mt-6 divide-y divide-slate-800/60 rounded-lg border border-slate-800 bg-slate-950/40">
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                  <dt class="shrink-0 text-[11px] font-medium text-slate-500">NISN</dt>
                  <dd class="min-w-0 truncate font-mono text-xs font-semibold text-slate-200">{{ currentUser.identity_number || '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                  <dt class="shrink-0 text-[11px] font-medium text-slate-500">Email</dt>
                  <dd class="min-w-0 truncate text-xs text-slate-300">{{ currentUser.email || '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                  <dt class="shrink-0 text-[11px] font-medium text-slate-500">Telepon</dt>
                  <dd class="min-w-0 truncate text-xs text-slate-300">{{ currentUser.phone || '—' }}</dd>
                </div>
              </dl>
            </div>

            <div class="grid grid-cols-3 divide-x divide-slate-800/60 border-t border-slate-800/70 bg-slate-950/40">
              <div class="flex flex-col items-center px-2 py-4">
                <Lightbulb class="h-4 w-4 text-purple-400" />
                <p class="mt-1.5 text-lg font-extrabold leading-none tracking-tight tabular-nums text-white">{{ myAspirations.length }}</p>
                <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-500">Aspirasi</p>
              </div>
              <div class="flex flex-col items-center px-2 py-4">
                <FileText class="h-4 w-4 text-emerald-400" />
                <p class="mt-1.5 text-lg font-extrabold leading-none tracking-tight tabular-nums text-white">{{ myReports.length }}</p>
                <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-500">Laporan</p>
              </div>
              <div class="flex flex-col items-center px-2 py-4">
                <TrendingUp class="h-4 w-4 text-purple-400" />
                <p class="mt-1.5 text-lg font-extrabold leading-none tracking-tight tabular-nums text-purple-400">{{ totalVotesReceived }}</p>
                <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-500">Dukungan</p>
              </div>
            </div>

            <p class="flex items-center justify-center gap-1.5 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 text-[11px] text-slate-600">
              <Lock class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />
              Data akun hanya dapat diakses oleh Anda dan petugas berwenang
            </p>
          </div>
        </aside>

        <section class="fade-up min-w-0 space-y-5" style="animation-delay: 90ms">
          <div class="flex items-center gap-2.5">
            <span class="h-4 w-0.75 rounded-full bg-emerald-500" aria-hidden="true"></span>
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">Riwayat Aktivitas</h2>
              <p class="mt-0.5 text-xs text-slate-500">Semua aspirasi dan laporan yang telah Anda kirimkan.</p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              :aria-pressed="activeTab === 'aspirations'"
              @click="activeTab = 'aspirations'"
              class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400/50"
              :class="activeTab === 'aspirations' ? 'border-purple-500/50 bg-purple-500/15 text-purple-400' : 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80" aria-hidden="true"></span>
              Aspirasi Saya
              <span class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500">{{ myAspirations.length }}</span>
            </button>

            <button
              type="button"
              :aria-pressed="activeTab === 'reports'"
              @click="activeTab = 'reports'"
              class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
              :class="activeTab === 'reports' ? 'border-cyan-500/50 bg-cyan-500/15 text-cyan-400' : 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80" aria-hidden="true"></span>
              Laporan Saya
              <span class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500">{{ myReports.length }}</span>
            </button>
          </div>

          <div v-if="isLoadingActivity" class="space-y-4">
              <div v-for="i in 2" :key="i" class="h-40 rounded-xl border border-slate-800 bg-slate-900 animate-pulse" />
          </div>

          <template v-else>
            <div v-if="activeTab === 'aspirations'" class="space-y-4">
            <article
              v-for="item in myAspirations"
              :key="item.id"
              class="rounded-xl border border-slate-800 bg-slate-900 p-5 transition-colors duration-200 hover:border-slate-700"
            >
              <div class="flex items-start justify-between gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="getTypeBadge(item.category)">
                  <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ item.category }}
                </span>
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="getStatusBadge(item.status)">
                  <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ item.status }}
                </span>
              </div>

              <h3 class="mt-4 text-sm font-bold leading-snug tracking-tight text-white">{{ item.title }}</h3>
              <p class="clamp-3 mt-2 text-xs leading-relaxed text-slate-400">{{ item.content }}</p>

              <div class="mt-4 flex items-center justify-between border-t border-slate-800/70 pt-3">
                <span class="flex items-center gap-1.5 text-[11px] text-slate-500">
                  <Clock class="h-3.5 w-3.5 text-slate-600" />
                  Diposting {{ item.createdAt }}
                </span>
                <span class="flex items-center gap-1.5 text-[11px] font-semibold text-purple-400">
                  <TrendingUp class="h-3.5 w-3.5" />
                  <span class="tabular-nums">{{ item.likesCount }}</span> Dukungan
                </span>
              </div>
            </article>

            <div v-if="myAspirations.length === 0" class="flex flex-col items-center rounded-xl border border-dashed border-slate-800 bg-slate-900/40 px-6 py-12 text-center">
              <p class="text-sm font-semibold text-slate-200">Belum ada aspirasi</p>
              <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">Sampaikan usulan pertama Anda untuk kemajuan sekolah.</p>
              <router-link
                :to="{ path: '/reports/new', query: { type: 'aspirasi' } }"
                class="mt-5 inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97]"
              >
                Kirim Aspirasi Pertama
              </router-link>
            </div>
          </div>

          <div v-if="activeTab === 'reports'" class="space-y-4">
            <article
              v-for="item in myReports"
              :key="item.id"
              class="rounded-xl border border-slate-800 bg-slate-900 p-5 transition-colors duration-200 hover:border-slate-700"
            >
              <div class="flex items-start justify-between gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="getTypeBadge(item.category)">
                  <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ item.category }}
                </span>
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="getStatusBadge(item.status)">
                  <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ item.status }}
                </span>
              </div>

              <h3 class="mt-4 text-sm font-bold leading-snug tracking-tight text-white">{{ item.title }}</h3>
              <p class="clamp-3 mt-2 text-xs leading-relaxed text-slate-400">{{ item.content }}</p>

              <div class="mt-4 flex items-center justify-between border-t border-slate-800/70 pt-3">
                <span class="flex items-center gap-1.5 text-[11px] text-slate-500">
                  <Clock class="h-3.5 w-3.5 text-slate-600" />
                  Dilaporkan {{ item.createdAt }}
                </span>
              </div>
            </article>

            <div v-if="myReports.length === 0" class="flex flex-col items-center rounded-xl border border-dashed border-slate-800 bg-slate-900/40 px-6 py-12 text-center">
              <p class="text-sm font-semibold text-slate-200">Belum ada laporan</p>
              <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">Riwayat laporan Anda akan muncul di sini setelah laporan pertama dikirim.</p>
              <router-link
                to="/reports/new"
                class="mt-5 inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97]"
              >
                Buat Laporan Pertama
              </router-link>
            </div>
          </div>
          </template>
        </section>
      </div>
    </main>

    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="text-[11px] text-slate-600">Setiap laporan dijaga kerahasiaannya</p>
      </div>
    </footer>

    <div
      v-if="isEditModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="account-settings-title"
    >
      <div class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm" aria-hidden="true"></div>

      <div class="modal-panel relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/50">
        <span class="absolute inset-x-0 top-0 z-20 h-0.75 bg-linear-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

        <div class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-800/70 px-5 py-4">
          <div>
            <h3 id="account-settings-title" class="text-sm font-bold tracking-tight text-white">Pengaturan Akun</h3>
            <p class="mt-0.5 text-[11px] text-slate-500">Perbarui data diri dan keamanan akun Anda</p>
          </div>
          <button
            type="button"
            @click="closeModal"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="flex shrink-0 gap-2 border-b border-slate-800/70 bg-slate-950/30 px-5 py-2.5">
          <button
            type="button"
            @click="activeModalTab = 'profile'"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
            :class="activeModalTab === 'profile' ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-300'"
          >
            <User class="h-3.5 w-3.5" />
            Data Profil
          </button>

          <button
            type="button"
            @click="activeModalTab = 'security'"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
            :class="activeModalTab === 'security' ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-300'"
          >
            <Lock class="h-3.5 w-3.5" />
            Ganti Password
          </button>
        </div>

        <div class="modal-scroll min-h-0 flex-1 overflow-y-auto p-5 sm:p-6">
          <form v-if="activeModalTab === 'profile'" @submit.prevent="handleSaveProfile" class="space-y-5">
            <div class="flex items-center gap-4 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
              <label
                for="avatar-input"
                class="group relative flex h-16 w-16 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-full border-2 border-slate-700 bg-slate-800 text-sm font-bold text-emerald-400 transition-colors duration-200 hover:border-emerald-500/50"
              >
                <img v-if="avatarPreview" :src="avatarPreview" alt="Pratinjau foto profil" class="h-full w-full object-cover" />
                <template v-else>{{ initialsOf(profileForm.name) || '?' }}</template>
                <span class="absolute inset-0 flex items-center justify-center bg-slate-950/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100" aria-hidden="true">
                  <Camera class="h-5 w-5 text-white" />
                </span>
              </label>
              <input id="avatar-input" ref="avatarInputRef" type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />

              <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-slate-200">Foto Profil</p>
                <p class="mt-0.5 text-[11px] text-slate-500">PNG, JPG, JPEG · maks 2MB</p>
                <div class="mt-2 flex flex-wrap gap-2">
                  <button
                    type="button"
                    @click="triggerAvatarPick"
                    class="inline-flex items-center gap-1 rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97]"
                  >
                    Ganti Foto
                  </button>
                  <button
                    v-if="avatarPreview"
                    type="button"
                    @click="removeAvatar"
                    class="inline-flex items-center gap-1 rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-rose-500/40 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97]"
                  >
                    Hapus
                  </button>
                </div>
              </div>
            </div>

            <div>
              <label for="pf-name" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Nama Lengkap</label>
              <div class="relative">
                <User class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <input
                  id="pf-name"
                  v-model="profileForm.name"
                  type="text"
                  autocomplete="name"
                  placeholder="Masukkan nama lengkap"
                  :aria-invalid="!!profileErrors.name || undefined"
                  class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  :class="profileErrors.name ? 'border-red-400/60' : 'border-slate-800'"
                />
              </div>
              <p v-if="profileErrors.name" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                {{ profileErrors.name }}
              </p>
            </div>

            <div>
              <label for="pf-email" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Email Sekolah</label>
              <div class="relative">
                <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <input
                  id="pf-email"
                  v-model="profileForm.email"
                  type="email"
                  autocomplete="email"
                  placeholder="nama@sapa.sch.id"
                  :aria-invalid="!!profileErrors.email || undefined"
                  class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  :class="profileErrors.email ? 'border-red-400/60' : 'border-slate-800'"
                />
              </div>
              <p v-if="profileErrors.email" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                {{ profileErrors.email }}
              </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label for="pf-nis" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">NIS</label>
                <div class="relative">
                  <CreditCard class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                  <input
                    id="pf-nis"
                    v-model="profileForm.identity_number"
                    type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="12345678"
                    :aria-invalid="!!profileErrors.identity_number || undefined"
                    class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-9 pr-3 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                    :class="profileErrors.identity_number ? 'border-red-400/60' : 'border-slate-800'"
                  />
                </div>
                <p v-if="profileErrors.identity_number" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                  <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                  {{ profileErrors.identity_number }}
                </p>
              </div>

              <div>
                <label for="pf-class" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Kelas</label>
                <div class="relative">
                  <GraduationCap class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                  <input
                    id="pf-class"
                    v-model="profileForm.class_name"
                    type="text"
                    autocomplete="off"
                    placeholder="XI RPL 2"
                    class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-9 pr-3 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                    :class="profileErrors.class_name ? 'border-red-400/60' : 'border-slate-800'"
                  />
                </div>
              </div>
            </div>

            <div>
              <label for="pf-phone" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Nomor Telepon <span class="font-normal normal-case tracking-normal text-slate-600">(opsional)</span></label>
              <div class="relative">
                <Phone class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <input
                  id="pf-phone"
                  v-model="profileForm.phone"
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
                <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                {{ profileErrors.phone }}
              </p>
            </div>

            <div class="flex flex-col-reverse gap-2.5 border-t border-slate-800/70 pt-5 sm:flex-row sm:justify-end">
              <button
                type="button"
                @click="closeModal"
                class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
              >
                <Check v-if="!isSaving" class="h-4 w-4" />
                <Loader2 v-else class="h-4 w-4 animate-spin" />
                {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </div>
          </form>

          <form v-else @submit.prevent="handleChangePassword" class="space-y-5">
            <div class="flex items-start gap-2.5 rounded-lg border border-emerald-500/25 bg-emerald-500/5 px-3.5 py-3">
              <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-emerald-400/90" />
              <p class="text-[11px] leading-relaxed text-slate-400">
                Password melindungi akun dan kerahasiaan laporan Anda. Masukkan <span class="font-medium text-slate-300">password saat ini</span> untuk mengatur password baru.
              </p>
            </div>

            <div>
              <label for="pw-old" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Password Saat Ini</label>
              <div class="relative">
                <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <input
                  id="pw-old"
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
                  <Eye v-if="!showOldPassword" class="h-4 w-4" />
                  <EyeOff v-else class="h-4 w-4" />
                </button>
              </div>
              <p v-if="passwordErrors.old_password" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                {{ passwordErrors.old_password }}
              </p>
            </div>

            <div>
              <div class="mb-2 flex items-center justify-between">
                <label for="pw-new" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Password Baru</label>
                <span class="font-mono text-[10px] text-slate-600">min. 8 karakter</span>
              </div>
              <div class="relative">
                <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <input
                  id="pw-new"
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
                  <Eye v-if="!showNewPassword" class="h-4 w-4" />
                  <EyeOff v-else class="h-4 w-4" />
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
                <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                {{ passwordErrors.new_password }}
              </p>
            </div>

            <div>
              <div class="mb-2 flex items-center justify-between">
                <label for="pw-confirm" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Konfirmasi Password Baru</label>
                <span
                  v-if="confirmMatch !== 'empty'"
                  class="flex items-center gap-1 text-[10px] font-semibold"
                  :class="confirmMatch === 'match' ? 'text-emerald-400' : 'text-red-400'"
                >
                  <CheckCircle2 v-if="confirmMatch === 'match'" class="h-3 w-3" />
                  <X v-else class="h-3 w-3" />
                  {{ confirmMatch === 'match' ? 'Cocok' : 'Belum cocok' }}
                </span>
              </div>
              <div class="relative">
                <ShieldCheck class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <input
                  id="pw-confirm"
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
                  <Eye v-if="!showConfirmPassword" class="h-4 w-4" />
                  <EyeOff v-else class="h-4 w-4" />
                </button>
              </div>
              <p v-if="passwordErrors.new_password_confirmation" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                {{ passwordErrors.new_password_confirmation }}
              </p>
            </div>

            <div class="flex flex-col-reverse gap-2.5 border-t border-slate-800/70 pt-5 sm:flex-row sm:justify-end">
              <button
                type="button"
                @click="closeModal"
                class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto"
              >
                Tutup
              </button>
              <button
                type="submit"
                :disabled="isChangingPassword"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
              >
                <Lock v-if="!isChangingPassword" class="h-4 w-4" />
                <Loader2 v-else class="h-4 w-4 animate-spin" />
                {{ isChangingPassword ? 'Memproses...' : 'Ubah Password' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-up {
  opacity: 0;
  animation: fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

.backdrop-in {
  animation: backdrop-in 0.25s ease both;
}

@keyframes backdrop-in {
  from { opacity: 0; }
  to   { opacity: 1; }
}

.modal-panel {
  animation: modal-in 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes modal-in {
  from { opacity: 0; transform: translateY(16px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-scroll::-webkit-scrollbar {
  width: 6px;
}

.modal-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.modal-scroll::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 3px;
}

.clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .backdrop-in,
  .modal-panel { animation: none; opacity: 1; }
}
</style>