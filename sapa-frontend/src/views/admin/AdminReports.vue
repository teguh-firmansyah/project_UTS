<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'
import reportService from '@/services/reportService'
import {
  BarChart3, FileText, Users, LogOut, Download, Search, X, Filter,
  ChevronDown, Lock, Eye, FileSpreadsheet, Check, Loader2, Zap, Settings,
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const logoFailed = ref(false)
const isLoading = ref(true)

const navItems = [
  { label: 'Analitik', to: '/admin/dashboard', icon: BarChart3 },
  { label: 'Semua Laporan', to: '/admin/reports', icon: FileText },
  { label: 'Manajemen User', to: '/admin/users', icon: Users },
  { label: 'Pengaturan', to: '/admin/settings', icon: Settings },
]

const isActive = (item) => route.path === item.to || route.path.startsWith(item.to + '/')

/* ---------------------------------- */
/* State filter                        */
/* ---------------------------------- */
const searchQuery = ref('')
const selectedType = ref('all')
const selectedStatus = ref('all')
const selectedPriority = ref('all')

/* Mapping UI (Indonesia) <-> backend (English) */
const TYPE_MAP_REVERSE = { Fasilitas: 'facility', Aspirasi: 'aspiration', Bullying: 'bullying' }
const STATUS_MAP = { pending: 'Menunggu', reviewing: 'Ditinjau', in_progress: 'Diproses', resolved: 'Selesai', rejected: 'Ditolak' }
const STATUS_MAP_REVERSE = { Menunggu: 'pending', Ditinjau: 'reviewing', Diproses: 'in_progress', Selesai: 'resolved', Ditolak: 'rejected' }
const PRIORITY_MAP = { urgent: 'Mendesak', high: 'Tinggi', medium: 'Sedang', low: 'Biasa' }

/* ---------------------------------- */
/* Data laporan — sekarang dari API    */
/* ---------------------------------- */
const reports = ref([])
const totalReportsCount = ref(0)

async function loadReports() {
  isLoading.value = true
  try {
    const params = {}
    if (selectedType.value !== 'all') params.type = TYPE_MAP_REVERSE[selectedType.value]
    if (selectedStatus.value !== 'all') params.status = STATUS_MAP_REVERSE[selectedStatus.value]
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim()

    const data = await reportService.getAdminReports(params)

    reports.value = data.data.map((r) => ({
      id: r.id,
      report_code: r.report_code,
      title: r.type === 'bullying' ? '[TERKUNCI] Laporan Kejadian Perundungan' : r.title,
      description: r.type === 'bullying' ? 'Konten terproteksi privasi BK.' : null,
      type: r.type === 'facility' ? 'Fasilitas' : r.type === 'aspiration' ? 'Aspirasi' : 'Bullying',
      reporter: r.is_anonymous ? 'Anonim' : (r.reporter?.name ? `${r.reporter.name}` : '—'),
      status: STATUS_MAP[r.status] ?? r.status,
      priority: PRIORITY_MAP[r.priority] ?? r.priority,
      date: r.created_at,
    }))
    totalReportsCount.value = data.total ?? reports.value.length
  } catch {
    toast.error('Gagal memuat daftar laporan.')
  } finally {
    isLoading.value = false
  }
}

onMounted(loadReports)

let searchDebounce = null
watch(searchQuery, () => {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(loadReports, 400)
})
watch([selectedType, selectedStatus], loadReports)

/* Prioritas tetap difilter di client karena backend belum sediakan filter ini */
const filteredReports = computed(() => {
  if (selectedPriority.value === 'all') return reports.value
  return reports.value.filter((item) => item.priority === selectedPriority.value)
})

const hasActiveFilters = computed(() =>
  searchQuery.value.trim() !== '' || selectedType.value !== 'all' ||
  selectedStatus.value !== 'all' || selectedPriority.value !== 'all'
)

const clearFilters = () => {
  searchQuery.value = ''
  selectedType.value = 'all'
  selectedStatus.value = 'all'
  selectedPriority.value = 'all'
}

const statusPills = [
  { value: 'all', label: 'Semua', active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Menunggu', label: 'Menunggu', active: 'border-amber-500/50 bg-amber-500/15 text-amber-400' },
  { value: 'Diproses', label: 'Diproses', active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Ditinjau', label: 'Ditinjau', active: 'border-blue-500/50 bg-blue-500/15 text-blue-400' },
  { value: 'Selesai', label: 'Selesai', active: 'border-slate-600 bg-slate-700/40 text-slate-200' },
]

const pillIdle = 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'

const statusCounts = computed(() => {
  const counts = { all: reports.value.length }
  for (const r of reports.value) counts[r.status] = (counts[r.status] || 0) + 1
  return counts
})

const getTypeBadgeClass = (type) => {
  switch (type) {
    case 'Fasilitas': return 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20'
    case 'Aspirasi': return 'bg-purple-500/10 text-purple-400 border-purple-500/20'
    case 'Bullying': return 'bg-amber-500/10 text-amber-400 border-amber-500/25'
    default: return 'bg-slate-800 text-slate-300 border-slate-700'
  }
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Selesai': return 'bg-slate-500/10 text-slate-300 border-slate-500/20'
    case 'Diproses': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    case 'Ditinjau': return 'bg-blue-500/10 text-blue-400 border-blue-500/20'
    case 'Menunggu': return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    case 'Ditolak': return 'bg-red-500/10 text-red-400 border-red-500/20'
    default: return 'bg-slate-800 text-slate-300 border-slate-700'
  }
}

const getPriorityMeta = (priority) => {
  const map = {
    'Mendesak': { label: 'Mendesak', level: 4, text: 'text-rose-400', bar: 'bg-rose-500', pulse: true },
    'Tinggi': { label: 'Tinggi', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    'Sedang': { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    'Biasa': { label: 'Biasa', level: 1, text: 'text-slate-400', bar: 'bg-slate-500' },
  }
  return map[priority] || map['Biasa']
}

const typeAccent = { 'Fasilitas': 'bg-cyan-500', 'Aspirasi': 'bg-purple-500', 'Bullying': 'bg-amber-500' }

const reportRows = computed(() =>
  filteredReports.value.map(item => ({
    ...item,
    typeBadge: getTypeBadgeClass(item.type),
    statusBadge: getStatusBadgeClass(item.status),
    priority: getPriorityMeta(item.priority),
    isBullying: item.type === 'Bullying',
    accent: typeAccent[item.type] || 'bg-emerald-500',
  }))
)

const formatDate = (d) => {
  if (!d) return '—'
  const date = new Date(d)
  return isNaN(date.getTime()) ? d : date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

/* ---------------------------------- */
/* Ekspor — sekarang panggil backend, bukan generate CSV di client */
/* ---------------------------------- */
const showExportModal = ref(false)
const exportFormat = ref('excel') // catatan: backend hanya sediakan CSV, opsi 'pdf' di UI tetap kirim CSV untuk saat ini
const exportTypeTarget = ref('all')
const getTodayDate = () => new Date().toISOString().split('T')[0]
const exportDate = ref(getTodayDate())
const isExporting = ref(false)

const openExportModal = () => {
  exportTypeTarget.value = selectedType.value
  exportDate.value = getTodayDate()
  showExportModal.value = true
}

const handleExecuteExport = async () => {
  isExporting.value = true
  try {
    const params = {}
    if (exportTypeTarget.value !== 'all') params.type = TYPE_MAP_REVERSE[exportTypeTarget.value]
    if (exportDate.value) params.date = exportDate.value

    const blob = await reportService.exportAdminReports(params)

    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `Rekap_Laporan_${exportTypeTarget.value}_${exportDate.value}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    showExportModal.value = false
    toast.success('Rekap laporan berhasil diunduh.')
  } catch {
    toast.error('Gagal mengekspor laporan.')
  } finally {
    isExporting.value = false
  }
}

/* ---------------------------------- */
/* Aksi baris — non-bullying ke halaman detail, bullying tetap modal metadata */
/* ---------------------------------- */
const showMetadataModal = ref(false)
const selectedMetadata = ref(null)

const handleViewDetail = (report) => {
  if (report.isBullying) {
    selectedMetadata.value = report
    showMetadataModal.value = true
  } else {
    router.push({ name: 'admin-report-detail', params: { id: report.id } })
  }
}

const anyModalOpen = computed(() => showMetadataModal.value || showExportModal.value)

const handleEscKey = (e) => {
  if (e.key !== 'Escape') return
  if (showExportModal.value) showExportModal.value = false
  else if (showMetadataModal.value) showMetadataModal.value = false
}

watch(anyModalOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => window.addEventListener('keydown', handleEscKey))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscKey)
  document.body.style.overflow = ''
})

const handleLogout = async () => {
  await authStore.logout()
  toast.success('Berhasil keluar dari sistem.')
  router.push({ name: 'login' })
}

const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas ============ -->
    <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3 md:h-16 md:py-0">

          <!-- Merek -->
          <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20">
              <img v-if="!logoFailed" src="@/assets/logo/logo sapa.jpeg" alt="Logo SAPA" class="h-full w-full object-cover" @error="logoFailed = true" />
              <span v-else class="text-sm font-extrabold text-emerald-400">S</span>
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-[15px] font-extrabold leading-none tracking-tight text-white">SAPA</p>
                <span class="rounded border border-blue-500/30 bg-blue-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-blue-400">Admin</span>
              </div>
              <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Manajemen Laporan Lintas Sistem</p>
            </div>
          </div>

          <!-- Navigasi -->
          <nav class="no-scrollbar order-3 -mx-1 flex w-full items-center gap-1 overflow-x-auto pb-1 md:order-2 md:mx-0 md:w-auto md:border-l md:border-slate-800/80 md:pb-0 md:pl-6" aria-label="Navigasi utama">
            <router-link
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              :aria-current="isActive(item) ? 'page' : undefined"
              class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all duration-200"
              :class="isActive(item)
                ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                : 'border-transparent text-slate-400 hover:bg-slate-900 hover:text-slate-200'"
            >
              <component :is="item.icon" class="h-3.5 w-3.5" />
              <span>{{ item.label }}</span>
            </router-link>
          </nav>

          <!-- Keluar -->
          <button
            type="button"
            @click="handleLogout"
            title="Keluar dari akun"
            class="order-2 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/80 text-slate-500 transition-all duration-200 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50 md:order-3"
          >
            <LogOut class="h-4 w-4" />
          </button>
        </div>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- ===== Kepala halaman ===== -->
      <section class="fade-up">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div class="min-w-0">
            <div class="flex items-center gap-2.5">
              <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300/90">Panel Admin · Manajemen Laporan</p>
            </div>
            <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Manajemen Semua Laporan</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">Kelola, pantau, dan ekspor seluruh aduan dari semua kategori secara terpusat.</p>
          </div>

          <button
            type="button"
            @click="openExportModal"
            class="inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
          >
            <Download class="h-4 w-4" />
            <span>Ekspor Data</span>
          </button>
        </div>
      </section>

      <!-- ===== Tabel laporan ===== -->
      <section class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 90ms">

        <!-- Kepala seksi -->
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5">
          <div class="flex items-center gap-3">
            <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">Daftar Laporan Terdaftar</h2>
              <p class="mt-0.5 text-xs text-slate-500">Akses baca — perubahan status ditangani oleh petugas kanal terkait.</p>
            </div>
          </div>
          <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400">{{ reports.length }} Laporan</span>
        </div>

        <!-- Toolbar filter -->
        <div class="space-y-4 border-b border-slate-800/80 p-4 sm:p-5">
          <div class="flex flex-col gap-3 sm:flex-row">
            <!-- Pencarian -->
            <div class="relative flex-1">
              <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
              <input
                v-model="searchQuery"
                type="text"
                aria-label="Cari laporan"
                placeholder="Contoh: LAP-2026 atau Proyektor"
                class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              />
              <button
                v-if="searchQuery"
                type="button"
                @click="searchQuery = ''"
                aria-label="Bersihkan pencarian"
                class="absolute right-2.5 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-500 transition-colors duration-150 hover:bg-slate-800 hover:text-slate-200"
              >
                <X class="h-3.5 w-3.5" />
              </button>
            </div>

            <!-- Filter tipe -->
            <div class="relative w-full sm:w-56">
              <Filter class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
              <select
                v-model="selectedType"
                aria-label="Filter tipe laporan"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              >
                <option value="all">Semua Tipe</option>
                <option value="Fasilitas">Fasilitas (Sarpras)</option>
                <option value="Aspirasi">Aspirasi</option>
                <option value="Bullying">Bullying (Aduan BK)</option>
              </select>
              <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
            </div>

            <!-- Filter prioritas -->
            <div class="relative w-full sm:w-52">
              <Zap class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
              <select
                v-model="selectedPriority"
                aria-label="Filter prioritas"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              >
                <option value="all">Semua Prioritas</option>
                <option value="Biasa">Biasa</option>
                <option value="Sedang">Sedang</option>
                <option value="Tinggi">Tinggi</option>
                <option value="Mendesak">Mendesak</option>
              </select>
              <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
            </div>
          </div>

          <!-- Pil status -->
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-for="opt in statusPills"
              :key="opt.value"
              type="button"
              :aria-pressed="selectedStatus === opt.value"
              @click="selectedStatus = opt.value"
              class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
              :class="selectedStatus === opt.value ? opt.active : pillIdle"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80" aria-hidden="true"></span>
              {{ opt.label }}
              <span class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500">{{ statusCounts[opt.value] || 0 }}</span>
            </button>

            <div class="ml-auto flex items-center gap-3 pl-2">
              <p class="whitespace-nowrap text-[11px] text-slate-500">
                Menampilkan <span class="font-semibold tabular-nums text-slate-300">{{ reportRows.length }}</span> dari {{ reports.length }} laporan
              </p>
              <button
                v-if="hasActiveFilters"
                type="button"
                @click="clearFilters"
                class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-[11px] font-semibold text-slate-400 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
              >
                Atur ulang
                <X class="h-3 w-3" />
              </button>
            </div>
          </div>
        </div>

        <!-- Label kolom -->
        <div class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.4fr)_110px_150px_125px_115px_150px] xl:gap-x-4">
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Laporan</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tipe</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Pelapor</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Prioritas</p>
          <p class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</p>
        </div>

        <div v-if="isLoading" class="divide-y divide-slate-800/70">
  <div v-for="i in 4" :key="i" class="px-5 py-4 sm:px-6">
    <div class="h-4 w-28 rounded bg-slate-800 animate-pulse mb-2"></div>
    <div class="h-4 w-64 rounded bg-slate-800 animate-pulse"></div>
  </div>
</div>

        <!-- Baris laporan -->
        <div v-else-if="reportRows.length > 0" class="divide-y divide-slate-800/70">
          <article
            v-for="(item, i) in reportRows"
            :key="item.id"
            class="card-enter group relative flex cursor-pointer flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.4fr)_110px_150px_125px_115px_150px] xl:items-center xl:gap-x-4"
            :style="{ animationDelay: (i * 60) + 'ms' }"
            @click="handleViewDetail(item)"
          >
            <span
              class="absolute bottom-3 left-0 top-3 w-[3px] rounded-r-full transition-opacity duration-200"
              :class="item.isBullying ? 'bg-amber-500/80 opacity-100' : item.accent + ' opacity-0 group-hover:opacity-100'"
              aria-hidden="true"
            ></span>

            <!-- Kolom laporan -->
            <div class="min-w-0">
              <span
                class="rounded border px-1.5 py-0.5 font-mono text-[11px] font-medium"
                :class="item.isBullying ? 'border-amber-500/25 bg-amber-500/10 text-amber-400' : 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'"
              >{{ item.report_code }}</span>
              <h3 class="mt-2 truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white">{{ item.title }}</h3>
              <p v-if="item.isBullying" class="mt-1 flex items-center gap-1.5 font-mono text-[10px] text-amber-400/80">
                <Lock class="h-3 w-3 shrink-0" />
                Terproteksi Kebijakan Privasi
              </p>
              <p class="mt-0.5 text-[10px] text-slate-600">{{ formatDate(item.date) }}</p>
            </div>

            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents">
              <!-- Tipe -->
              <div>
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="item.typeBadge">
                  <Lock v-if="item.isBullying" class="h-3 w-3" />
                  <span class="uppercase">{{ item.type }}</span>
                </span>
              </div>

              <!-- Pelapor -->
              <div class="min-w-0">
                <p v-if="item.isBullying" class="flex items-center gap-1.5 text-xs font-semibold text-slate-300">
                  <Lock class="h-3.5 w-3.5 shrink-0 text-slate-500" />
                  Anonim
                </p>
                <p v-else class="truncate text-xs font-semibold text-slate-200">{{ item.reporter }}</p>
                <p v-if="item.isBullying" class="mt-0.5 truncate text-[10px] text-slate-600">Identitas dilindungi</p>
              </div>

              <!-- Status -->
              <div>
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="item.statusBadge">
                  <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ item.status }}
                </span>
              </div>

              <!-- Prioritas -->
              <div class="flex items-center gap-2">
                <div class="flex items-end gap-[3px]" aria-hidden="true">
                  <span class="h-1.5 w-[3px] rounded-[1px]" :class="item.priority.level >= 1 ? item.priority.bar : 'bg-slate-700'"></span>
                  <span class="h-2 w-[3px] rounded-[1px]" :class="item.priority.level >= 2 ? item.priority.bar : 'bg-slate-700'"></span>
                  <span class="h-2.5 w-[3px] rounded-[1px]" :class="item.priority.level >= 3 ? item.priority.bar : 'bg-slate-700'"></span>
                  <span class="h-3 w-[3px] rounded-[1px]" :class="item.priority.level >= 4 ? item.priority.bar : 'bg-slate-700'"></span>
                </div>
                <span class="whitespace-nowrap text-[11px] font-medium" :class="item.priority.text">{{ item.priority.label }}</span>
                <span v-if="item.priority.pulse" class="relative flex h-1.5 w-1.5" aria-hidden="true">
                  <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-60"></span>
                  <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                </span>
              </div>
            </div>

            <!-- Aksi -->
            <div class="flex justify-end">
              <button
                type="button"
                @click.stop="handleViewDetail(item)"
                class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border px-3 py-2 text-xs font-semibold transition-all duration-150 active:scale-[.97] focus:outline-none focus-visible:ring-2 sm:w-auto"
                :class="item.isBullying
                  ? 'border-amber-500/30 bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 focus-visible:ring-amber-400/60'
                  : 'border-slate-700 bg-slate-800/50 text-slate-300 hover:border-emerald-500/40 hover:bg-emerald-500/10 hover:text-emerald-400 focus-visible:ring-emerald-400/60'"
              >
                <Lock v-if="item.isBullying" class="h-3.5 w-3.5" />
                <Eye v-else class="h-3.5 w-3.5" />
                <span>{{ item.isBullying ? 'Metadata Only' : 'Lihat Detail' }}</span>
              </button>
            </div>
          </article>
        </div>

        <!-- Keadaan kosong -->
        <div v-else class="flex flex-col items-center px-6 py-16 text-center">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
            <Search v-if="hasActiveFilters" class="h-6 w-6" />
            <FileText v-else class="h-6 w-6" />
          </div>
          <p class="mt-4 text-sm font-semibold text-slate-200">
            {{ hasActiveFilters ? 'Laporan tidak ditemukan' : 'Belum ada laporan terdaftar' }}
          </p>
          <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
            {{ hasActiveFilters
              ? 'Coba gunakan kata kunci lain atau atur ulang filter tipe, status, dan prioritas.'
              : 'Laporan dari seluruh kanal akan terdaftar di sini secara otomatis.' }}
          </p>

          <button
            v-if="hasActiveFilters"
            type="button"
            @click="clearFilters"
            class="mt-6 inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-xs font-semibold text-emerald-400 transition-all duration-200 hover:bg-emerald-500 hover:text-slate-950 active:scale-[.97]"
          >
            Atur Ulang Filter
          </button>
        </div>

        <!-- Kaki daftar -->
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 sm:px-6">
          <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
            <Lock class="h-3.5 w-3.5 shrink-0 text-amber-400/70" />
            Laporan perundungan hanya menampilkan metadata — narasi &amp; identitas eksklusif Guru BK
          </p>
          <p class="whitespace-nowrap text-[11px] text-slate-600">Akses admin: mode baca</p>
        </div>
      </section>
    </main>

    <!-- ============ Footer ============ -->
    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
          <Lock class="h-3.5 w-3.5 text-emerald-500/70" />
          Data perundungan hanya dapat diakses oleh Guru BK berwenang
        </p>
      </div>
    </footer>

    <!-- ============ Modal metadata bullying ============ -->
    <div
      v-if="showMetadataModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="admin-metadata-title"
    >
      <div class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm" aria-hidden="true" @click="showMetadataModal = false"></div>

      <div class="modal-panel relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-amber-500/30 bg-slate-900 shadow-2xl shadow-black/50">
        <span class="absolute inset-x-0 top-0 z-20 h-[2px] bg-gradient-to-r from-amber-500/70 via-amber-500/20 to-transparent" aria-hidden="true"></span>

        <!-- Kepala modal -->
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-amber-500/20 px-5 py-4">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded border border-amber-500/25 bg-amber-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-amber-400">{{ selectedMetadata?.report_code }}</span>
              <span class="inline-flex shrink-0 items-center gap-1 rounded border border-amber-500/25 bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-400">
                <Lock class="h-3 w-3" />
                BK Exclusive
              </span>
            </div>
            <h3 id="admin-metadata-title" class="mt-2.5 text-base font-bold leading-snug tracking-tight text-white">Metadata Laporan Bullying</h3>
          </div>

          <button
            type="button"
            @click="showMetadataModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
              <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Status Penanganan</p>
              <p class="mt-1 text-xs font-semibold text-blue-400">{{ selectedMetadata?.status }}</p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
              <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Tanggal Masuk</p>
              <p class="mt-1 text-xs font-medium text-slate-200">{{ formatDate(selectedMetadata?.date) }}</p>
            </div>
          </div>

          <div class="flex items-start gap-2.5 rounded-lg border border-amber-500/25 bg-amber-500/[0.06] px-3.5 py-3">
            <Lock class="mt-0.5 h-4 w-4 shrink-0 text-amber-400/90" />
            <p class="text-[11px] leading-relaxed text-slate-400">
              <span class="font-semibold text-amber-300">Aturan Privasi:</span>
              Isi laporan, narasi kronologi, serta identitas pelapor bersifat rahasia dan hanya dapat diakses oleh Guru BK yang bertugas.
            </p>
          </div>
        </div>

        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-amber-500/20 bg-slate-950/30 p-5">
          <button
            type="button"
            @click="showMetadataModal = false"
            class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- ============ Modal ekspor rekap ============ -->
    <div
      v-if="showExportModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="admin-export-title"
    >
      <div class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm" aria-hidden="true" @click="showExportModal = false"></div>

      <div class="modal-panel relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/50">
        <span class="absolute inset-x-0 top-0 z-20 h-[2px] bg-gradient-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

        <!-- Kepala modal -->
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-800/70 px-5 py-4">
          <div>
            <h3 id="admin-export-title" class="text-sm font-bold tracking-tight text-slate-100">Ekspor Rekap Laporan</h3>
            <p class="mt-0.5 text-[11px] text-slate-500">Unduh rekap laporan sesuai filter tipe &amp; tanggal</p>
          </div>
          <button
            type="button"
            @click="showExportModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-5 overflow-y-auto p-5">
          <!-- Tipe laporan -->
          <div class="space-y-2">
            <label for="export-type" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Tipe Laporan yang Diekspor</label>
            <div class="relative">
              <Filter class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
              <select
                id="export-type"
                v-model="exportTypeTarget"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              >
                <option value="all">Semua Tipe Laporan</option>
                <option value="Fasilitas">Fasilitas (Sarpras)</option>
                <option value="Aspirasi">Aspirasi</option>
                <option value="Bullying">Bullying (Aduan BK)</option>
              </select>
              <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
            </div>
          </div>

          <!-- Format berkas -->
          <div class="space-y-2">
            <span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Format Berkas</span>
            <div class="space-y-1.5" role="radiogroup" aria-label="Format berkas ekspor">
              <button
                type="button"
                :aria-pressed="exportFormat === 'excel'"
                @click="exportFormat = 'excel'"
                class="flex w-full items-center gap-2.5 rounded-lg border px-3 py-2.5 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="exportFormat === 'excel' ? 'border-emerald-500/50 bg-emerald-500/[0.06] text-emerald-400' : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'"
              >
                <FileSpreadsheet class="h-4 w-4 shrink-0" />
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-slate-100">Microsoft Excel (.csv)</span>
                  <span class="block text-[10px] leading-snug text-slate-500">Data terstruktur untuk pengolahan lanjutan</span>
                </span>
                <Check v-if="exportFormat === 'excel'" class="h-3.5 w-3.5 shrink-0" />
              </button>

              <button
                type="button"
                :aria-pressed="exportFormat === 'pdf'"
                @click="exportFormat = 'pdf'"
                class="flex w-full items-center gap-2.5 rounded-lg border px-3 py-2.5 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="exportFormat === 'pdf' ? 'border-emerald-500/50 bg-emerald-500/[0.06] text-emerald-400' : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'"
              >
                <FileText class="h-4 w-4 shrink-0" />
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-slate-100">Dokumen Ringkasan (.txt)</span>
                  <span class="block text-[10px] leading-snug text-slate-500">Rekap ringkas siap cetak / arsip</span>
                </span>
                <Check v-if="exportFormat === 'pdf'" class="h-3.5 w-3.5 shrink-0" />
              </button>
            </div>
          </div>

          <!-- Tanggal -->
          <div class="space-y-2">
            <label for="export-date" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Tanggal Laporan</label>
            <input
              id="export-date"
              v-model="exportDate"
              type="date"
              class="w-full rounded-lg border border-slate-800 bg-slate-950/60 px-3.5 py-2.5 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15 [color-scheme:dark]"
            />
          </div>

          <!-- Pratinjau jumlah -->
          <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
            <p class="text-[11px] text-slate-500">Laporan sesuai filter ekspor</p>
            <p class="text-sm font-extrabold tabular-nums text-white">{{ totalReportsCount }}</p>
          </div>

          <!-- Catatan privasi -->
          <p class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500">
            <Lock class="mt-px h-3 w-3 shrink-0 text-amber-400/70" />
            Laporan perundungan diekspor sebagai metadata saja — tanpa narasi &amp; identitas.
          </p>
        </div>

        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-slate-800/70 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showExportModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
            >
              Batal
            </button>

            <button
              type="button"
              @click="handleExecuteExport"
              :disabled="isExporting"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
            >
              <Download v-if="!isExporting" class="h-4 w-4" />
              <Loader2 v-else class="h-4 w-4 animate-spin" />
              {{ isExporting ? 'Memproses...' : 'Unduh Rekap' }}
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<style>
/* Inter sebagai identitas tipografi */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.sapa-root {
  font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}
</style>

<style scoped>
/* Entrance seksi */
.fade-up {
  opacity: 0;
  animation: fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Entrance baris */
.card-enter {
  opacity: 0;
  animation: card-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes card-in {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* Modal */
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

/* Scrollbar modal */
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

/* Scrollbar nav mobile */
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .card-enter,
  .backdrop-in,
  .modal-panel { animation: none; opacity: 1; }
}
</style>