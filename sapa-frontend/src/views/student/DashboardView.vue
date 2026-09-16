<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'
import reportService from '@/services/reportService'
import {
  ArrowRight,
  Plus,
  LogOut,
  CheckCircle2,
  Lock,
  GraduationCap,
  FileSearch,
  Lightbulb,
  Building2,
  ShieldAlert,
  ChevronRight,
  FileText,
  Clock,
  RefreshCw,
  Calendar,
  Shield
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const isLoadingStats = ref(true)
const isLoadingReports = ref(true)

const reports = ref([])
const rawStats = ref(null)

async function loadStats() {
  isLoadingStats.value = true
  try {
    rawStats.value = await reportService.getMyStats()
  } catch {
    toast.error('Gagal memuat statistik laporan.')
  } finally {
    isLoadingStats.value = false
  }
}

async function loadReports() {
  isLoadingReports.value = true
  try {
    const data = await reportService.getMyReports({ per_page: 10 })
    reports.value = data.data.map((r) => ({
      id: r.id,
      report_code: r.report_code,
      title: r.title,
      type: r.type,
      status: r.status,
      priority: r.priority,
      created_at: r.created_at,
    }))
  } catch {
    toast.error('Gagal memuat riwayat laporan.')
  } finally {
    isLoadingReports.value = false
  }
}

const avatarUrl = computed(() => {
  const avatar = authStore.user?.avatar
  if (!avatar) return null
  if (avatar.startsWith('http') || avatar.startsWith('blob:')) return avatar
  return `http://localhost:8000/storage/${avatar.replace(/^\/?storage\//, '')}`
})

onMounted(async () => {
  if (!authStore.user) {
    await authStore.fetchCurrentUser()
  }
  loadStats()
  loadReports()
})

const stats = computed(() => {
  if (!rawStats.value) {
    return { total: 0, pending: 0, in_progress: 0, resolved: 0 }
  }
  return {
    total:
      rawStats.value.pending +
      rawStats.value.reviewing +
      rawStats.value.in_progress +
      rawStats.value.resolved +
      rawStats.value.rejected,
    pending: rawStats.value.pending,
    in_progress: rawStats.value.reviewing + rawStats.value.in_progress,
    resolved: rawStats.value.resolved,
  }
})

const getStatusBadge = (status) => {
  const map = {
    pending: { label: 'Menunggu', class: 'bg-amber-500/10 text-amber-400 border-amber-500/20' },
    reviewing: { label: 'Ditinjau', class: 'bg-blue-500/10 text-blue-400 border-blue-500/20' },
    in_progress: { label: 'Diproses', class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' },
    resolved: { label: 'Selesai', class: 'bg-slate-500/10 text-slate-300 border-slate-500/20' },
    rejected: { label: 'Ditolak', class: 'bg-red-500/10 text-red-400 border-red-500/20' },
  }
  return map[status] || { label: status, class: 'bg-slate-700 text-slate-300' }
}

const getTypeBadge = (type) => {
  const map = {
    aspiration: { label: 'Aspirasi', class: 'bg-purple-500/10 text-purple-400 border-purple-500/20' },
    facility: { label: 'Fasilitas', class: 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20' },
    bullying: { label: 'Perundungan', class: 'bg-rose-500/10 text-rose-400 border-rose-500/20' },
  }
  return map[type] || { label: type, class: 'bg-slate-700 text-slate-300' }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

const getPriority = (priority) => {
  const map = {
    urgent: { label: 'Mendesak', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    high: { label: 'Tinggi', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    medium: { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    low: { label: 'Rendah', level: 1, text: 'text-slate-400', bar: 'bg-slate-500' },
  }
  return map[priority] || map.low
}

const reportRows = computed(() =>
  reports.value.map((r) => ({
    ...r,
    typeBadge: getTypeBadge(r.type),
    statusBadge: getStatusBadge(r.status),
    priority: getPriority(r.priority),
  }))
)

const statCards = computed(() => {
  const { total, pending, in_progress, resolved } = stats.value
  const pct = (n) => (total > 0 ? Math.round((n / total) * 100) : 0)
  return [
    {
      label: 'Total Laporan', value: total, caption: 'Seluruh riwayat pelaporan Anda', pct: 100,
      icon: FileText,
      num: 'text-white', tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', bar: 'bg-emerald-500',
    },
    {
      label: 'Menunggu', value: pending, caption: `${pct(pending)}% dari total laporan`, pct: pct(pending),
      icon: Clock,
      num: 'text-amber-400', tile: 'border-amber-500/25 bg-amber-500/10 text-amber-400', bar: 'bg-amber-500',
    },
    {
      label: 'Diproses', value: in_progress, caption: `${pct(in_progress)}% dari total laporan`, pct: pct(in_progress),
      icon: RefreshCw,
      num: 'text-emerald-400', tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', bar: 'bg-emerald-500',
    },
    {
      label: 'Selesai', value: resolved, caption: `${pct(resolved)}% dari total laporan`, pct: pct(resolved),
      icon: CheckCircle2,
      num: 'text-slate-300', tile: 'border-slate-600/50 bg-slate-700/30 text-slate-300', bar: 'bg-slate-500',
    },
  ]
})

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 11) return 'Selamat pagi'
  if (h < 15) return 'Selamat siang'
  if (h < 18) return 'Selamat sore'
  return 'Selamat malam'
})

const firstName = computed(() => (authStore.user?.name || 'Siswa').trim().split(/\s+/)[0])

const initials = computed(() => {
  const name = (authStore.user?.name || 'Siswa SAPA').trim()
  const parts = name.split(/\s+/)
  return (parts.length > 1 ? parts[0][0] + parts[parts.length - 1][0] : name.slice(0, 2)).toUpperCase()
})

const activeSummary = computed(() => {
  if (isLoadingStats.value) return 'Memuat ringkasan laporan...'
  const { pending, in_progress } = stats.value
  if (pending === 0 && in_progress === 0) return 'Semua laporan Anda telah ditangani'
  const parts = []
  if (pending > 0) parts.push(`${pending} laporan menunggu tindak lanjut`)
  if (in_progress > 0) parts.push(`${in_progress} laporan sedang diproses`)
  return parts.join(' · ')
})

const heroPhoto = 'https://picsum.photos/seed/sapacampus/1600/900.jpg'
const currentYear = new Date().getFullYear()
const logoFailed = ref(false)

const navigateToCreateWithType = (type) => {
  router.push({ path: '/reports/new', query: { type } })
}

async function handleLogout() {
  await authStore.logout()
  toast.success('Berhasil keluar dari sistem.')
  router.push('/login')
}
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">

        <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20">
            <img v-if="!logoFailed" src="../../assets/logo/logo sapa.jpeg" alt="Logo SAPA" class="h-full w-full object-cover" @error="logoFailed = true" />
            <span v-else class="text-sm font-extrabold text-emerald-400">S</span>
          </div>
          <div class="hidden min-w-0 sm:block">
            <p class="text-[15px] font-extrabold leading-none tracking-tight text-white">SAPA</p>
            <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
          <router-link
            to="/aspirations"
            class="group inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-slate-950 shadow-md shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 hover:shadow-emerald-500/30 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
          >
            <ArrowRight class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5" />
            <span>Lihat Aspirasi</span>
          </router-link>

          <router-link
            to="/reports/new"
            class="group inline-flex items-center gap-1 whitespace-nowrap rounded-md bg-emerald-500 px-2.5 py-1.5 text-xs font-semibold text-slate-950 shadow-md shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 hover:shadow-emerald-500/30 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
          >
            <Plus class="h-3.5 w-3.5 transition-transform duration-300 group-hover:rotate-90" />
            <span>Buat Laporan</span>
          </router-link>

          <div class="hidden h-6 w-px bg-slate-800 md:block"></div>

          <router-link
            to="/profile"
            title="Lihat profil saya"
            class="group hidden items-center gap-2.5 rounded-lg p-1 transition-all hover:bg-slate-900/80 md:flex focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
          >
            <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[11px] font-bold text-emerald-400 transition-all duration-200 group-hover:border-emerald-500/60 group-hover:bg-emerald-500/20">
  <img 
    v-if="avatarUrl" 
    :src="avatarUrl" 
    alt="Foto Profil" 
    class="h-full w-full object-cover" 
  />
</div>
            <div class="hidden max-w-37.5 leading-tight xl:block text-left">
              <p class="truncate text-xs font-semibold text-slate-200 transition-colors duration-200 group-hover:text-emerald-400">
                {{ authStore.user?.name || 'Siswa SAPA' }}
              </p>
              <p class="mt-0.5 truncate text-[10px] text-slate-500">
                {{ authStore.user?.class_name || 'Kelas —' }}
              </p>
            </div>
          </router-link>

          <button
            type="button"
            @click="handleLogout"
            title="Keluar dari akun"
            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/80 text-slate-500 transition-all duration-200 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50"
          >
            <LogOut class="h-5 w-5" />
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 space-y-10 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <section class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <img :src="heroPhoto" alt="" aria-hidden="true" draggable="false"
             class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover opacity-20 grayscale contrast-125 brightness-[.65]" />
        <div class="pointer-events-none absolute inset-0 bg-slate-950/60"></div>
        <div class="pointer-events-none absolute inset-0 bg-linear-to-r from-slate-950/80 via-slate-950/30 to-transparent"></div>
        <div class="pointer-events-none absolute inset-0" style="background: radial-gradient(900px 420px at 10% 0%, rgba(16, 185, 129, 0.12), transparent 65%)"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-linear-to-r from-emerald-500/60 via-emerald-500/10 to-transparent"></div>

        <div class="relative z-10 space-y-8 p-6 sm:p-8 lg:p-10">
          <div class="flex flex-col gap-8 lg:grid lg:grid-cols-[minmax(0,1fr)_330px] lg:gap-12">

            <div class="flex flex-col justify-center">
              <div class="flex items-center gap-2.5">
                <span class="relative flex h-2 w-2">
                  <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                  <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300/90">{{ greeting }}, {{ firstName }}</p>
              </div>

              <h1 class="mt-4 text-3xl font-extrabold leading-[1.08] tracking-tight text-white sm:text-4xl lg:text-[2.5rem]">
                Suara Anda,<br class="hidden sm:block" />
                <span class="text-emerald-400">Membangun Sekolah.</span>
              </h1>

              <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-400 sm:text-[15px]">
                Pantau laporan, sampaikan aspirasi, laporkan fasilitas bermasalah, dan bantu
                menciptakan lingkungan sekolah yang lebih aman.
              </p>

              <div class="mt-6">
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-700/80 bg-slate-950/70 py-1.5 pl-3 pr-4 text-xs text-slate-300 backdrop-blur-sm">
                  <span class="h-1.5 w-1.5 rounded-full" :class="stats.pending > 0 ? 'bg-amber-400' : 'bg-emerald-400'"></span>
                  <span class="font-medium">{{ activeSummary }}</span>
                </div>
              </div>
            </div>

            <aside class="rounded-xl border border-slate-800 bg-slate-950/70 p-5 backdrop-blur-sm">
              <div class="flex items-center justify-between gap-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Profil Pelapor</p>
                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-400">
                  <CheckCircle2 class="h-3 w-3" />
                  Terverifikasi
                </span>
              </div>

              <router-link
  to="/profile"
  title="Lihat profil saya"
  class="group mt-4 flex items-center gap-3.5 rounded-xl p-2 transition-all duration-200 hover:bg-slate-900/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
>
  <!-- Avatar Container -->
  <div class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-sm font-bold text-emerald-400 transition-all duration-200 group-hover:border-emerald-500/60 group-hover:bg-emerald-500/20">
    <!-- Gambar Profil (dengan wrapper overflow-hidden internal) -->
    <div class="h-full w-full overflow-hidden rounded-full flex items-center justify-center">
      <img 
        v-if="avatarUrl" 
        :src="avatarUrl" 
        alt="Foto Profil" 
        class="h-full w-full object-cover" 
      />
      <template v-else>
        {{ initials }}
      </template>
    </div>

    <!-- Indicator Badge Online/Terverifikasi (Tetap di Luar Masking Image) -->
    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-slate-950 bg-emerald-500" aria-hidden="true"></span>
  </div>
  
  <div class="min-w-0 text-left">
    <p class="truncate text-sm font-semibold text-white transition-colors duration-200 group-hover:text-emerald-400">
      {{ authStore.user?.name || 'Siswa SAPA' }}
    </p>
    <p class="mt-0.5 truncate text-xs text-slate-400">
      {{ authStore.user?.class_name || 'Kelas belum diatur' }}
    </p>
  </div>
</router-link>

              <div class="mt-5 flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/70 px-3.5 py-3">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">NISN</p>
                  <p class="mt-1 font-mono text-xs font-semibold tracking-wide text-slate-200">{{ authStore.user?.identity_number || '—' }}</p>
                </div>
                <GraduationCap class="h-5 w-5 text-slate-600" />
              </div>
            </aside>
          </div>

          <div class="grid grid-cols-1 gap-4 border-t border-slate-800/70 pt-5 sm:grid-cols-3 sm:gap-6">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <Lock class="h-4 w-4 text-emerald-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Identitas Dilindungi</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Kerahasiaan pelapor dijamin sepenuhnya</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <GraduationCap class="h-4 w-4 text-emerald-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Ditangani Tim Sekolah</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Ditinjau oleh guru &amp; tim SAPA</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <FileSearch class="h-4 w-4 text-emerald-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Status Terlacak</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Pantau perkembangan setiap laporan</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-0.75 rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Kanal Pelaporan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pilih kanal yang sesuai untuk menyampaikan suara Anda.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <button
            type="button"
            @click="navigateToCreateWithType('aspirasi')"
            class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-500/40 hover:bg-slate-800/60 hover:shadow-lg hover:shadow-black/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/60 active:scale-[.99]"
          >
            <span class="absolute left-0 top-5 bottom-5 w-0.75 rounded-r-full bg-purple-500/70" aria-hidden="true"></span>
            <div class="flex items-start justify-between gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-purple-500/25 bg-purple-500/10 text-purple-400 transition-all duration-200 group-hover:scale-110 group-hover:border-purple-500/40">
                <Lightbulb class="h-5 w-5" />
              </div>
              <ChevronRight class="mt-1 h-4 w-4 text-slate-600 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-purple-400" />
            </div>
            <h3 class="mt-4 text-sm font-semibold text-white">Kirim Aspirasi</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-slate-400">Sampaikan ide dan usulan untuk kemajuan sekolah.</p>
          </button>

          <button
            type="button"
            @click="navigateToCreateWithType('fasilitas')"
            class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-cyan-500/40 hover:bg-slate-800/60 hover:shadow-lg hover:shadow-black/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/60 active:scale-[.99]"
          >
            <span class="absolute left-0 top-5 bottom-5 w-0.75 rounded-r-full bg-cyan-500/70" aria-hidden="true"></span>
            <div class="flex items-start justify-between gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-cyan-500/25 bg-cyan-500/10 text-cyan-400 transition-all duration-200 group-hover:scale-110 group-hover:border-cyan-500/40">
                <Building2 class="h-5 w-5" />
              </div>
              <ChevronRight class="mt-1 h-4 w-4 text-slate-600 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-cyan-400" />
            </div>
            <h3 class="mt-4 text-sm font-semibold text-white">Lapor Fasilitas</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-slate-400">Laporkan kerusakan atau masalah fasilitas sekolah.</p>
          </button>

          <button
            type="button"
            @click="navigateToCreateWithType('bullying')"
            class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-rose-500/40 hover:bg-slate-800/60 hover:shadow-lg hover:shadow-black/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/60 active:scale-[.99] sm:col-span-2 lg:col-span-1"
          >
            <span class="absolute left-0 top-5 bottom-5 w-0.75 rounded-r-full bg-rose-500/70" aria-hidden="true"></span>
            <div class="flex items-start justify-between gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-rose-500/25 bg-rose-500/10 text-rose-400 transition-all duration-200 group-hover:scale-110 group-hover:border-rose-500/40">
                <ShieldAlert class="h-5 w-5" />
              </div>
              <ChevronRight class="mt-1 h-4 w-4 text-slate-600 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-rose-400" />
            </div>
            <h3 class="mt-4 text-sm font-semibold text-white">Pengaduan Bullying</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-slate-400">Laporkan perundungan secara aman dan bertanggung jawab.</p>

            <div class="mt-4 flex items-center gap-2 border-t border-slate-800/70 pt-3">
              <ShieldAlert class="h-3.5 w-3.5 shrink-0 text-rose-400/80" />
              <p class="text-[11px] leading-snug text-slate-500">Laporan ditangani secara aman dan bertanggung jawab.</p>
            </div>
          </button>
        </div>
      </section>

      <section class="fade-up space-y-4" style="animation-delay: 180ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-0.75 rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Ringkasan Laporan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pantau perkembangan seluruh laporan yang telah Anda kirimkan.</p>
          </div>
        </div>

        <div v-if="isLoadingStats" class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
          <div v-for="i in 4" :key="i" class="h-28 rounded-xl border border-slate-800 bg-slate-900 animate-pulse" />
        </div>

        <div v-else class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
          <article
            v-for="s in statCards"
            :key="s.label"
            class="group rounded-xl border border-slate-800 bg-slate-900 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-700 sm:p-5"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ s.label }}</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight tabular-nums" :class="s.num">{{ s.value }}</p>
              </div>
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border transition-transform duration-200 group-hover:scale-110" :class="s.tile">
                <component :is="s.icon" class="h-5 w-5" />
              </div>
            </div>
            <div class="mt-4">
              <div class="h-1 w-full overflow-hidden rounded-full bg-slate-800">
                <div class="stat-bar h-full rounded-full" :class="s.bar" :style="{ width: s.pct + '%' }"></div>
              </div>
              <p class="mt-2 truncate text-[10px] text-slate-500">{{ s.caption }}</p>
            </div>
          </article>
        </div>
      </section>

      <section class="fade-up" style="animation-delay: 270ms">
        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">

          <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center gap-3">
              <span class="h-4 w-0.75 rounded-full bg-emerald-500" aria-hidden="true"></span>
              <div>
                <h2 class="text-base font-bold tracking-tight text-slate-100">Riwayat Laporan Saya</h2>
                <p class="mt-0.5 text-xs text-slate-500">Daftar laporan dan aspirasi yang pernah Anda kirimkan.</p>
              </div>
            </div>

            <button
              type="button"
              @click="router.push('/reports')"
              class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-2 text-xs font-semibold text-emerald-400 transition-all duration-200 hover:bg-emerald-500 hover:text-slate-950 hover:shadow-lg hover:shadow-emerald-500/20 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
            >
              Lihat Semua Detail
              <ArrowRight class="h-3.5 w-3.5" />
            </button>
          </div>

          <div class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 lg:grid lg:grid-cols-[minmax(0,1fr)_120px_112px_104px_96px] lg:gap-x-4 sm:px-6">
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Laporan</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Prioritas</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tanggal</p>
            <p class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</p>
          </div>

          <div v-if="isLoadingReports" class="divide-y divide-slate-800/70">
            <div v-for="i in 3" :key="i" class="px-5 py-4 sm:px-6">
              <div class="mb-2 h-4 w-32 animate-pulse rounded bg-slate-800"></div>
              <div class="h-4 w-64 animate-pulse rounded bg-slate-800"></div>
            </div>
          </div>

          <div v-else-if="reportRows.length > 0" class="divide-y divide-slate-800/70">
            <article
              v-for="row in reportRows"
              :key="row.id"
              class="group flex flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 lg:grid lg:grid-cols-[minmax(0,1fr)_120px_112px_104px_96px] lg:items-center lg:gap-x-4 lg:gap-y-0 lg:py-4"
            >
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded border border-emerald-500/20 bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-emerald-400">{{ row.report_code }}</span>
                  <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="row.typeBadge.class">{{ row.typeBadge.label }}</span>
                </div>
                <h3 class="mt-2 truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white">{{ row.title }}</h3>
              </div>

              <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 lg:contents">
                <div>
                  <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="row.statusBadge.class">
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                    {{ row.statusBadge.label }}
                  </span>
                </div>

                <div class="flex items-center gap-2">
                  <div class="flex items-end gap-0.75" aria-hidden="true">
                    <span class="h-1.5 w-0.75 rounded-[1px]" :class="row.priority.level >= 1 ? row.priority.bar : 'bg-slate-700'"></span>
                    <span class="h-2 w-0.75 rounded-[1px]" :class="row.priority.level >= 2 ? row.priority.bar : 'bg-slate-700'"></span>
                    <span class="h-2.5 w-0.75 rounded-[1px]" :class="row.priority.level >= 3 ? row.priority.bar : 'bg-slate-700'"></span>
                  </div>
                  <span class="text-[11px] font-medium" :class="row.priority.text">{{ row.priority.label }}</span>
                </div>

                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                  <Calendar class="h-3.5 w-3.5 text-slate-600" />
                  <span>{{ formatDate(row.created_at) }}</span>
                </div>
              </div>

              <div class="mt-1 flex justify-end lg:mt-0">
                <button
                  type="button"
                  @click="router.push(`/reports/${row.id}`)"
                  class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-3.5 py-2 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:bg-emerald-500/10 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 sm:w-auto"
                >
                  Detail
                  <ChevronRight class="h-3.5 w-3.5" />
                </button>
              </div>
            </article>
          </div>

          <div v-else class="flex flex-col items-center px-6 py-16 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
              <FileText class="h-6 w-6" />
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-200">Belum ada laporan</p>
            <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">Riwayat laporan Anda akan muncul di sini setelah laporan pertama dikirim.</p>
            <router-link to="/reports/new" class="mt-6 inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97]">
              Buat Laporan Pertama
            </router-link>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 sm:px-6">
            <p class="text-[11px] text-slate-500">
              Menampilkan <span class="font-semibold text-slate-400">{{ reports.length }}</span> laporan Anda
            </p>
            <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
              <Lock class="h-3 w-3" />
              Laporan perundungan dirahasiakan
            </p>
          </div>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
          <Shield class="h-3.5 w-3.5 text-emerald-500/70" />
          Setiap laporan dijaga kerahasiaannya
        </p>
      </div>
    </footer>
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
.stat-bar {
  transform-origin: left center;
  animation: grow-x 0.8s cubic-bezier(0.22, 1, 0.36, 1) 0.3s both;
}
@keyframes grow-x {
  from { transform: scaleX(0); }
  to   { transform: scaleX(1); }
}
@media (prefers-reduced-motion: reduce) {
  .fade-up { animation: none; opacity: 1; }
  .stat-bar { animation: none; }
}
</style>