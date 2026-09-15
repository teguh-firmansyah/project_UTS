<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const logoFailed = ref(false)

/* ---------------------------------- */
/* Navigasi panel (selaras dashboard  */
/* admin: aktif berbasis route)       */
/* ---------------------------------- */
const navItems = [
  {
    label: 'Analitik',
    to: '/admin/dashboard',
    icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
  },
  {
    label: 'Semua Laporan',
    to: '/admin/reports',
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  },
  {
    label: 'Manajemen User',
    to: '/admin/users',
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  },
]

const isActive = (item) => route.path === item.to || route.path.startsWith(item.to + '/')

/* ---------------------------------- */
/* State filter & pencarian (existing)*/
/* ---------------------------------- */
const searchQuery = ref('')
const selectedType = ref('all')
const selectedStatus = ref('all')
const selectedPriority = ref('all')

/* ---------------------------------- */
/* State modal (existing)             */
/* ---------------------------------- */
const showExportModal = ref(false)
const showMetadataModal = ref(false)
const selectedMetadata = ref(null)
const showDetailModal = ref(false)
const selectedDetail = ref(null)

/* ---------------------------------- */
/* Tanggal hari ini (existing)        */
/* ---------------------------------- */
const getTodayDate = () => {
  const today = new Date()
  const year = today.getFullYear()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  const day = String(today.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

/* ---------------------------------- */
/* Formulir ekspor (existing)         */
/* ---------------------------------- */
const exportFormat = ref('excel')
const exportTypeTarget = ref('all')
const exportDate = ref(getTodayDate())
const isExporting = ref(false)

/* ---------------------------------- */
/* Data laporan (existing, verbatim)  */
/* ---------------------------------- */
const reports = ref([
  {
    id: 1,
    report_code: 'LAP-2026-089',
    title: 'Kerusakan Proyektor di Ruang Lab Komputer 2',
    description: 'Proyektor tiba-tiba mati total dan tercium bau sangit saat jam pelajaran Informatika ke-3.',
    type: 'Fasilitas',
    reporter: 'Ahmad Rizky (Siswa)',
    status: 'Diproses',
    priority: 'Tinggi',
    date: '2026-09-15',
    location: 'Lab Komputer 2'
  },
  {
    id: 2,
    report_code: 'LAP-2026-088',
    title: 'Usulan Penambahan Fasilitas Tempat Sampah Pilah',
    description: 'Mohon disediakan tempat sampah terpisah untuk organik dan anorganik di sekitar area kantin belakang.',
    type: 'Aspirasi',
    reporter: 'Siti Aminah (Siswa)',
    status: 'Selesai',
    priority: 'Sedang',
    date: '2026-09-15',
    location: 'Kantin Belakang'
  },
  {
    id: 3,
    report_code: 'BUL-2026-012',
    title: '[TERKUNCI] Laporan Kejadian Perundungan',
    description: 'Konten terproteksi privasi BK.',
    type: 'Bullying',
    reporter: 'Anonim',
    status: 'Diproses BK',
    priority: 'Mendesak',
    date: '2026-09-14',
    location: 'Lingkungan Sekolah'
  },
  {
    id: 4,
    report_code: 'LAP-2026-085',
    title: 'Pintu Toilet Lantai 2 Rusak/Tidak Bisa Dikunci',
    description: 'Grendel pintu toilet siswa laki-laki nomor 2 lepas sehingga tidak bisa dikunci dari dalam.',
    type: 'Fasilitas',
    reporter: 'Budi Santoso (Siswa)',
    status: 'Menunggu',
    priority: 'Tinggi',
    date: '2026-09-10',
    location: 'Toilet Samping Lab Fisika'
  }
])

/* ---------------------------------- */
/* Filter (logika existing)           */
/* ---------------------------------- */
const filteredReports = computed(() => {
  return reports.value.filter(item => {
    const matchSearch = item.report_code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        item.title.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchType = selectedType.value === 'all' || item.type === selectedType.value
    const matchStatus = selectedStatus.value === 'all' || item.status === selectedStatus.value
    const matchPriority = selectedPriority.value === 'all' || item.priority === selectedPriority.value

    return matchSearch && matchType && matchStatus && matchPriority
  })
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

/* ---------------------------------- */
/* Pil status + hitungan              */
/* ---------------------------------- */
const statusPills = [
  { value: 'all',        label: 'Semua',       active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Menunggu',   label: 'Menunggu',    active: 'border-amber-500/50 bg-amber-500/15 text-amber-400' },
  { value: 'Diproses',   label: 'Diproses',    active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Diproses BK', label: 'Diproses BK', active: 'border-blue-500/50 bg-blue-500/15 text-blue-400' },
  { value: 'Selesai',    label: 'Selesai',     active: 'border-slate-600 bg-slate-700/40 text-slate-200' },
]

const pillIdle = 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'

const statusCounts = computed(() => {
  const counts = { all: reports.value.length }
  for (const r of reports.value) counts[r.status] = (counts[r.status] || 0) + 1
  return counts
})

/* ---------------------------------- */
/* Helper badge — warna diselaraskan  */
/* sistem (Fasilitas=cyan, Aspirasi=  */
/* purple, Bullying=amber-terkunci;   */
/* Selesai=slate, Diproses=emerald)   */
/* ---------------------------------- */
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
    case 'Diproses BK': return 'bg-blue-500/10 text-blue-400 border-blue-500/20'
    case 'Menunggu': return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    case 'Ditolak': return 'bg-red-500/10 text-red-400 border-red-500/20'
    default: return 'bg-slate-800 text-slate-300 border-slate-700'
  }
}

const getPriorityBadgeClass = (priority) => {
  switch (priority) {
    case 'Mendesak': return 'bg-rose-500/10 text-rose-400 border-rose-500/20'
    case 'Tinggi': return 'bg-rose-500/10 text-rose-400 border-rose-500/20'
    case 'Sedang': return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  }
}

/* Bar sinyal prioritas — bahasa prioritas sistem (4 tingkat) */
const getPriorityMeta = (priority) => {
  const map = {
    'Mendesak': { label: 'Mendesak', level: 4, text: 'text-rose-400', bar: 'bg-rose-500', pulse: true },
    'Tinggi': { label: 'Tinggi', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    'Sedang': { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    'Biasa': { label: 'Biasa', level: 1, text: 'text-slate-400', bar: 'bg-slate-500' },
  }
  return map[priority] || map['Biasa']
}

/* Aksen channel pada hover baris */
const typeAccent = { 'Fasilitas': 'bg-cyan-500', 'Aspirasi': 'bg-purple-500', 'Bullying': 'bg-amber-500' }

/* Baris siap-render */
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

/* Format 'YYYY-MM-DD' */
const formatDate = (d) => {
  if (!d) return '—'
  const date = new Date(d)
  return isNaN(date.getTime()) ? d : date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

/* ---------------------------------- */
/* Ekspor (behavior existing +        */
/* pratinjau, pengaman kosong, toast) */
/* ---------------------------------- */
const openExportModal = () => {
  exportTypeTarget.value = selectedType.value
  exportDate.value = getTodayDate()
  showExportModal.value = true
}

const exportPreviewCount = computed(() => {
  return reports.value.filter(item => {
    const matchType = exportTypeTarget.value === 'all' || item.type === exportTypeTarget.value
    const matchDate = !exportDate.value || item.date === exportDate.value
    return matchType && matchDate
  }).length
})

const handleExecuteExport = () => {
  let dataToExport = reports.value.filter(item => {
    const matchType = exportTypeTarget.value === 'all' || item.type === exportTypeTarget.value
    const matchDate = !exportDate.value || item.date === exportDate.value
    return matchType && matchDate
  })

  if (dataToExport.length === 0) {
    toast.error('Tidak ada laporan yang sesuai filter ekspor pada tanggal tersebut.')
    return
  }

  isExporting.value = true

  setTimeout(() => {
    let fileContent = ""
    let fileName = `Rekap_Laporan_${exportTypeTarget.value}_${exportDate.value}`
    let mimeType = ""

    if (exportFormat.value === 'excel') {
      const headers = ["Kode Laporan", "Judul", "Tipe", "Pelapor", "Status", "Prioritas", "Tanggal"]
      const rows = dataToExport.map(r => [
        `"${r.report_code}"`,
        `"${r.title.replace(/"/g, '""')}"`,
        `"${r.type}"`,
        `"${r.reporter}"`,
        `"${r.status}"`,
        `"${r.priority}"`,
        `"${r.date}"`
      ].join(","))

      fileContent = [headers.join(","), ...rows].join("\n")
      fileName += ".csv"
      mimeType = "text/csv;charset=utf-8;"
    } else {
      fileContent = `==================================================\n`
      fileContent += `       REKAP LAPORAN SAPA ADMIN (${exportDate.value})      \n`
      fileContent += `==================================================\n`
      fileContent += `Tipe Filter : ${exportTypeTarget.value}\n`
      fileContent += `Tanggal     : ${exportDate.value}\n`
      fileContent += `Total Data  : ${dataToExport.length} Laporan\n`
      fileContent += `--------------------------------------------------\n\n`

      dataToExport.forEach((r, idx) => {
        fileContent += `${idx + 1}. [${r.report_code}] ${r.title}\n`
        fileContent += `   Tipe: ${r.type} | Status: ${r.status} | Tgl: ${r.date}\n`
        fileContent += `   Pelapor: ${r.reporter} | Prioritas: ${r.priority}\n`
        fileContent += `--------------------------------------------------\n`
      })

      fileName += ".txt"
      mimeType = "text/plain;charset=utf-8;"
    }

    const blob = new Blob([fileContent], { type: mimeType })
    const link = document.createElement("a")
    link.href = URL.createObjectURL(blob)
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    isExporting.value = false
    showExportModal.value = false
    toast.success('Rekap laporan berhasil diunduh.', {
      description: `${dataToExport.length} laporan · ${exportFormat.value === 'excel' ? 'format CSV' : 'format ringkasan'}.`
    })
  }, 800)
}

/* ---------------------------------- */
/* Detail mode baca (behavior         */
/* existing: bullying → metadata)     */
/* ---------------------------------- */
const handleViewDetail = (report) => {
  if (report.type === 'Bullying') {
    selectedMetadata.value = report
    showMetadataModal.value = true
  } else {
    selectedDetail.value = report
    showDetailModal.value = true
  }
}

/* ---------------------------------- */
/* Modal: escape + kunci scroll body  */
/* ---------------------------------- */
const anyModalOpen = computed(() => showDetailModal.value || showMetadataModal.value || showExportModal.value)

const handleEscKey = (e) => {
  if (e.key !== 'Escape') return
  if (showExportModal.value) showExportModal.value = false
  else if (showMetadataModal.value) showMetadataModal.value = false
  else if (showDetailModal.value) showDetailModal.value = false
}

watch(anyModalOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => window.addEventListener('keydown', handleEscKey))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscKey)
  document.body.style.overflow = ''
})

/* ---------------------------------- */
/* Logout (behavior existing)         */
/* ---------------------------------- */
const handleLogout = async () => {
  if (authStore?.logout) {
    await authStore.logout()
  }
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
              <img v-if="!logoFailed" src="@/assets/logo sapa.jpeg" alt="Logo SAPA" class="h-full w-full object-cover" @error="logoFailed = true" />
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

          <!-- Navigasi: sejajar di desktop, baris dapat digulir di mobile -->
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
              <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
              </svg>
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
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
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
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
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
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
              </svg>
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
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Filter tipe -->
            <div class="relative w-full sm:w-56">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
              </svg>
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
              <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>

            <!-- Filter prioritas -->
            <div class="relative w-full sm:w-52">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
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
              <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Pil status + ringkasan hasil -->
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
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Label kolom (desktop lebar) -->
        <div class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.4fr)_110px_150px_125px_115px_150px] xl:gap-x-4">
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Laporan</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tipe</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Pelapor</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Prioritas</p>
          <p class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</p>
        </div>

        <!-- Baris laporan -->
        <div v-if="reportRows.length > 0" class="divide-y divide-slate-800/70">
          <article
            v-for="(item, i) in reportRows"
            :key="item.id"
            class="card-enter group relative flex cursor-pointer flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.4fr)_110px_150px_125px_115px_150px] xl:items-center xl:gap-x-4"
            :style="{ animationDelay: (i * 60) + 'ms' }"
            @click="handleViewDetail(item)"
          >
            <!-- Aksen: permanen amber untuk bullying, warna channel saat hover untuk lainnya -->
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
                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Terproteksi Kebijakan Privasi
              </p>
              <p class="mt-0.5 text-[10px] text-slate-600">{{ formatDate(item.date) }}</p>
            </div>

            <!-- Meta: tipe, pelapor, status, prioritas -->
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents">
              <!-- Tipe -->
              <div>
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="item.typeBadge">
                  <svg v-if="item.isBullying" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  <span class="uppercase">{{ item.type }}</span>
                </span>
              </div>

              <!-- Pelapor -->
              <div class="min-w-0">
                <p v-if="item.isBullying" class="flex items-center gap-1.5 text-xs font-semibold text-slate-300">
                  <svg class="h-3.5 w-3.5 shrink-0 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
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
                <svg v-if="item.isBullying" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ item.isBullying ? 'Metadata Only' : 'Lihat Detail' }}</span>
              </button>
            </div>
          </article>
        </div>

        <!-- Keadaan kosong -->
        <div v-else class="flex flex-col items-center px-6 py-16 text-center">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
            <svg v-if="hasActiveFilters" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
            </svg>
            <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
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
            <svg class="h-3.5 w-3.5 shrink-0 text-amber-400/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
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
          <svg class="h-3.5 w-3.5 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Data perundungan hanya dapat diakses oleh Guru BK berwenang
        </p>
      </div>
    </footer>

    <!-- ============ Modal detail (mode baca) ============ -->
    <div
      v-if="showDetailModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="admin-detail-title"
    >
      <div class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm" aria-hidden="true" @click="showDetailModal = false"></div>

      <div class="modal-panel relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/50">
        <span class="absolute inset-x-0 top-0 z-20 h-[2px] bg-gradient-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

        <!-- Kepala modal -->
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-800/70 px-5 py-4">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded border border-emerald-500/20 bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-emerald-400">{{ selectedDetail?.report_code }}</span>
              <span class="inline-flex items-center gap-1 rounded-full border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[10px] font-semibold text-slate-400">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.03 10.03 0 013.682-.782c4.478 0 8.268 2.943 9.542 7a10.017 10.017 0 01-2.06 3.65m-2.222 2.221L2 2l20 20" />
                </svg>
                Akses Baca Saja
              </span>
            </div>
            <h3 id="admin-detail-title" class="mt-2.5 text-base font-bold leading-snug tracking-tight text-white">{{ selectedDetail?.title }}</h3>
          </div>

          <button
            type="button"
            @click="showDetailModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-5 overflow-y-auto p-5">

          <!-- Badge tipe & status -->
          <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium uppercase" :class="getTypeBadgeClass(selectedDetail?.type)">
              <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
              {{ selectedDetail?.type }}
            </span>
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="getStatusBadgeClass(selectedDetail?.status)">
              <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
              {{ selectedDetail?.status }}
            </span>
          </div>

          <!-- Metadata -->
          <dl class="grid grid-cols-2 gap-3">
            <div class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
              <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Pelapor</dt>
              <dd class="mt-1 truncate text-xs font-medium text-slate-200">{{ selectedDetail?.reporter }}</dd>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
              <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Lokasi</dt>
              <dd class="mt-1 truncate text-xs font-medium text-slate-200">{{ selectedDetail?.location || '—' }}</dd>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
              <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Tanggal Laporan</dt>
              <dd class="mt-1 text-xs font-medium text-slate-200">{{ formatDate(selectedDetail?.date) }}</dd>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
              <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Prioritas</dt>
              <dd class="mt-1">
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-semibold" :class="getPriorityBadgeClass(selectedDetail?.priority)">
                  {{ selectedDetail?.priority }}
                </span>
              </dd>
            </div>
          </dl>

          <!-- Deskripsi -->
          <div class="space-y-2">
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Deskripsi / Kronologi</p>
            <p class="rounded-lg border border-slate-800 bg-slate-950/60 p-3.5 text-xs leading-relaxed text-slate-300">
              {{ selectedDetail?.description || 'Tidak ada deskripsi tambahan.' }}
            </p>
          </div>
        </div>

        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-slate-800/70 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[10px] leading-relaxed text-slate-600">Perubahan status ditangani oleh petugas kanal terkait (Sarpras / BK).</p>
            <button
              type="button"
              @click="showDetailModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>

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
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
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
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
          <!-- Metadata agregat -->
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

          <!-- Peringatan privasi -->
          <div class="flex items-start gap-2.5 rounded-lg border border-amber-500/25 bg-amber-500/[0.06] px-3.5 py-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-400/90" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <p class="text-[11px] leading-relaxed text-slate-400">
              <span class="font-semibold text-amber-300">Aturan Privasi:</span>
              Isi laporan, narasi kronologi, serta identitas pelapor bersifat rahasia dan hanya dapat diakses
              oleh Guru BK yang bertugas.
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
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-5 overflow-y-auto p-5">

          <!-- Tipe laporan -->
          <div class="space-y-2">
            <label for="export-type" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Tipe Laporan yang Diekspor</label>
            <div class="relative">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
              </svg>
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
              <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Format berkas: kartu radio (nilai identik dengan select asli) -->
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
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-slate-100">Microsoft Excel (.csv)</span>
                  <span class="block text-[10px] leading-snug text-slate-500">Data terstruktur untuk pengolahan lanjutan</span>
                </span>
                <svg v-if="exportFormat === 'excel'" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </button>

              <button
                type="button"
                :aria-pressed="exportFormat === 'pdf'"
                @click="exportFormat = 'pdf'"
                class="flex w-full items-center gap-2.5 rounded-lg border px-3 py-2.5 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="exportFormat === 'pdf' ? 'border-emerald-500/50 bg-emerald-500/[0.06] text-emerald-400' : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'"
              >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-slate-100">Dokumen Ringkasan (.txt)</span>
                  <span class="block text-[10px] leading-snug text-slate-500">Rekap ringkas siap cetak / arsip</span>
                </span>
                <svg v-if="exportFormat === 'pdf'" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
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
            <p class="text-sm font-extrabold tabular-nums text-white">{{ exportPreviewCount }}</p>
          </div>

          <!-- Catatan privasi -->
          <p class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500">
            <svg class="mt-px h-3 w-3 shrink-0 text-amber-400/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
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
              <svg v-if="!isExporting" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
              </svg>
              <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              {{ isExporting ? 'Memproses...' : 'Unduh Rekap' }}
            </button>
          </div>
        </div>
      </div>
    </div>
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

/* Entrance baris: hanya opacity — interaksi hover tetap bekerja */
.card-enter {
  opacity: 0;
  animation: card-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes card-in {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* Modal: latar memudar, panel naik dengan skala halus */
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

/* Gulir tipis pada badan modal */
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

/* Gulir horizontal nav mobile tanpa scrollbar */
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