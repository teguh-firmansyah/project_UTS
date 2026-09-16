<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'
import reportService from '@/services/reportService'

const router = useRouter()
const authStore = useAuthStore()

const logoFailed = ref(false)
const isLoading = ref(true)
const isLoadingStats = ref(true)

/* Inisial nama petugas BK untuk avatar */
const initials = computed(() => {
  const name = authStore.user?.name || 'Guru BK'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const firstName = computed(() => (authStore.user?.name || 'Guru BK').trim().split(/\s+/)[0])

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 11) return 'Selamat pagi'
  if (h < 15) return 'Selamat siang'
  if (h < 18) return 'Selamat sore'
  return 'Selamat malam'
})

const handleLogout = async () => {
  await authStore.logout()
  toast.success('Berhasil keluar dari sistem.')
  router.push({ name: 'login' })
}

/* ---------------------------------- */
/* State filter & pencarian            */
/* ---------------------------------- */
const searchQuery = ref('')
const selectedStatus = ref('ALL')

/* ---------------------------------- */
/* Mapping status backend <-> label UI */
/* Backend: pending, reviewing, in_progress, resolved, rejected        */
/* ---------------------------------- */
const STATUS_MAP = {
  pending: 'Menunggu',
  reviewing: 'Ditinjau',
  in_progress: 'Diproses',
  resolved: 'Selesai',
  rejected: 'Ditolak',
}
const STATUS_MAP_REVERSE = {
  Menunggu: 'pending',
  Ditinjau: 'reviewing',
  Diproses: 'in_progress',
  Selesai: 'resolved',
  Ditolak: 'rejected',
}

const RELATION_MAP = {
  victim: 'Korban',
  witness: 'Saksi',
}

/* ---------------------------------- */
/* Data laporan bullying — dari API, bukan dummy lagi                  */
/* ---------------------------------- */
const reports = ref([])
const stats = ref(null)

async function loadReports() {
  isLoading.value = true
  try {
    const params = {}
    if (selectedStatus.value !== 'ALL') {
      params.status = STATUS_MAP_REVERSE[selectedStatus.value]
    }

    const data = await reportService.getBullyingQueue(params)

    // Mapping response backend ke bentuk yang dipakai UI ini
    reports.value = data.data.map((r) => ({
      id: r.id,
      ticket_code: r.report_code,
      category: r.title, // backend generate otomatis "Laporan Bullying"
      reporter_relation: RELATION_MAP[r.detail?.reporter_relation] ?? '—',
      is_anonymous: r.is_anonymous,
      incident_date: r.detail?.incident_date ?? null,
      created_at: r.created_at,
      status: STATUS_MAP[r.status] ?? r.status,
      priority: r.priority === 'urgent' || r.priority === 'high' ? 'Tinggi'
        : r.priority === 'medium' ? 'Sedang' : 'Rendah',
    }))
  } catch {
    toast.error('Gagal memuat antrian laporan bullying.')
  } finally {
    isLoading.value = false
  }
}

async function loadStats() {
  isLoadingStats.value = true
  try {
    stats.value = await reportService.getBullyingStats()
  } catch {
    toast.error('Gagal memuat statistik.')
  } finally {
    isLoadingStats.value = false
  }
}

onMounted(() => {
  loadReports()
  loadStats()
})

/* Reload dari server saat filter status berubah — bukan filter client-side lagi,
   karena backend sudah sediakan filter via query param */
function selectStatus(value) {
  selectedStatus.value = value
  loadReports()
}

/* ---------------------------------- */
/* Filter search tetap di client (ringan, tidak perlu roundtrip API)   */
/* ---------------------------------- */
const filteredReports = computed(() => {
  if (!searchQuery.value.trim()) return reports.value
  const q = searchQuery.value.toLowerCase()
  return reports.value.filter(item =>
    item.ticket_code.toLowerCase().includes(q) ||
    item.category.toLowerCase().includes(q)
  )
})

/* Counter stats — dari endpoint /bullying-stats, bukan dihitung di client */
const totalPending = computed(() => stats.value?.pending ?? 0)
const totalInReview = computed(() => (stats.value?.reviewing ?? 0) + (stats.value?.in_progress ?? 0))
const totalResolved = computed(() => stats.value?.resolved ?? 0)
const totalCases = computed(() => stats.value?.total ?? 0)
const activeCases = computed(() => totalPending.value + totalInReview.value)

const activeSummary = computed(() => {
  if (totalPending.value === 0 && totalInReview.value === 0) {
    return 'Tidak ada kasus aktif — seluruh laporan telah ditangani'
  }
  const parts = []
  if (totalPending.value > 0) parts.push(`${totalPending.value} laporan menunggu respons`)
  if (totalInReview.value > 0) parts.push(`${totalInReview.value} kasus sedang ditangani`)
  return parts.join(' · ')
})

const statCards = computed(() => {
  const total = totalCases.value
  const pct = (n) => (total > 0 ? Math.round((n / total) * 100) : 0)
  return [
    {
      label: 'Total Kasus', value: total, caption: 'Seluruh kasus dalam antrian BK', pct: 100,
      icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
      num: 'text-white', tile: 'border-rose-500/25 bg-rose-500/10 text-rose-400', bar: 'bg-rose-500',
    },
    {
      label: 'Butuh Respon', value: totalPending.value, caption: 'Laporan baru berstatus Menunggu', pct: pct(totalPending.value),
      icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
      num: 'text-amber-400', tile: 'border-amber-500/25 bg-amber-500/10 text-amber-400', bar: 'bg-amber-500', pulse: true,
    },
    {
      label: 'Dalam Penanganan', value: totalInReview.value, caption: 'Sedang ditinjau / diproses BK', pct: pct(totalInReview.value),
      icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
      num: 'text-emerald-400', tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', bar: 'bg-emerald-500',
    },
    {
      label: 'Kasus Selesai', value: totalResolved.value, caption: 'Laporan tuntas ditangani', pct: pct(totalResolved.value),
      icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      num: 'text-slate-300', tile: 'border-slate-600/50 bg-slate-700/30 text-slate-300', bar: 'bg-slate-500',
    },
  ]
})

/* ---------------------------------- */
/* Helper badge status                 */
/* ---------------------------------- */
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

const getPriority = (priority) => {
  const map = {
    'Tinggi': { label: 'Tinggi', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    'Sedang': { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    'Rendah': { label: 'Rendah', level: 1, text: 'text-slate-400', bar: 'bg-slate-500' },
  }
  return map[priority] || map['Rendah']
}

const statusOptions = [
  { value: 'ALL',       label: 'Semua',     active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Menunggu',  label: 'Menunggu',  active: 'border-amber-500/50 bg-amber-500/15 text-amber-400' },
  { value: 'Ditinjau',  label: 'Ditinjau',  active: 'border-blue-500/50 bg-blue-500/15 text-blue-400' },
  { value: 'Diproses',  label: 'Diproses',  active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Selesai',   label: 'Selesai',   active: 'border-slate-600 bg-slate-700/40 text-slate-200' },
]

const pillIdle = 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'

/* Hitung jumlah per status dari data yang sedang termuat (approksimasi tampilan pill) */
const statusCounts = computed(() => {
  const counts = { ALL: totalCases.value }
  counts['Menunggu'] = totalPending.value
  counts['Ditinjau'] = stats.value?.reviewing ?? 0
  counts['Diproses'] = stats.value?.in_progress ?? 0
  counts['Selesai'] = totalResolved.value
  counts['Ditolak'] = stats.value?.rejected ?? 0
  return counts
})

const hasActiveFilters = computed(() => searchQuery.value.trim() !== '' || selectedStatus.value !== 'ALL')
const clearFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = 'ALL'
  loadReports()
}

const reportRows = computed(() =>
  filteredReports.value.map(item => ({
    ...item,
    statusBadge: getStatusBadge(item.status),
    priority: getPriority(item.priority),
  }))
)

const formatIncidentDate = (d) => {
  if (!d) return '—'
  const date = new Date(String(d).replace(' ', 'T'))
  if (isNaN(date.getTime())) return d
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const formatCreatedTime = (d) => {
  if (!d) return ''
  const date = new Date(String(d).replace(' ', 'T'))
  if (isNaN(date.getTime())) return ''
  return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

const goToDetail = (id) => router.push(`/counselor/bullying-reports/${id}`)

const heroPhoto = 'https://picsum.photos/seed/sapabkpanel/1600/900.jpg'
const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas ============ -->
    <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20">
            <img v-if="!logoFailed" src="@/assets/logo/logo sapa.jpeg" alt="Logo SAPA" class="h-full w-full object-cover" @error="logoFailed = true" />
            <span v-else class="text-sm font-extrabold text-emerald-400">S</span>
          </div>
          <div class="hidden min-w-0 sm:block">
            <div class="flex items-center gap-2">
              <p class="text-[15px] font-extrabold leading-none tracking-tight text-white">SAPA</p>
              <span class="rounded border border-rose-500/30 bg-rose-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-rose-400">Panel BK</span>
            </div>
            <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Antrian Penanganan Kasus Bullying</p>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
          <router-link
            to="/counselor/archived-reports"
            title="Lihat Arsip Pelaporan"
            class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <svg class="h-4 w-4 text-slate-500 transition-colors duration-200 group-hover:text-slate-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
            <span class="hidden sm:inline">Arsip Laporan</span>
          </router-link>

          <div class="hidden h-6 w-px bg-slate-800 md:block"></div>

          <router-link
            to="/profile"
            title="Lihat profil saya"
            class="group hidden items-center gap-2.5 rounded-lg p-1 transition-all hover:bg-slate-900/80 md:flex focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
          >
            <div class="flex h-9 w-9 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[11px] font-bold text-emerald-400 transition-all duration-200 group-hover:border-emerald-500/60 group-hover:bg-emerald-500/20">
              {{ initials }}
            </div>
            <div class="hidden max-w-[150px] text-left leading-tight xl:block">
              <p class="truncate text-xs font-semibold text-slate-200 transition-colors duration-200 group-hover:text-emerald-400">{{ authStore.user?.name || 'Guru Bimbingan Konseling' }}</p>
              <p class="mt-0.5 truncate text-[10px] text-slate-500">Guru BK</p>
            </div>
          </router-link>

          <button
            type="button"
            @click="handleLogout"
            title="Keluar dari akun"
            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/80 text-slate-500 transition-all duration-200 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-10 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- ===== Hero panel BK ===== -->
      <section class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <img :src="heroPhoto" alt="" aria-hidden="true" draggable="false"
             class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover opacity-20 grayscale contrast-125 brightness-[.65]" />
        <div class="pointer-events-none absolute inset-0 bg-slate-950/60" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/30 to-transparent" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0" aria-hidden="true"
             style="background: radial-gradient(900px 420px at 10% 0%, rgba(244, 63, 94, 0.12), transparent 65%)"></div>
        <span class="absolute inset-x-0 top-0 z-10 h-[2px] bg-gradient-to-r from-rose-500/70 via-rose-500/20 to-transparent" aria-hidden="true"></span>

        <div class="relative z-10 space-y-8 p-6 sm:p-8 lg:p-10">
          <div class="flex flex-col gap-8 lg:grid lg:grid-cols-[minmax(0,1fr)_330px] lg:gap-12">
            <div class="flex flex-col justify-center">
              <div class="flex items-center gap-2.5">
                <span class="relative flex h-2 w-2">
                  <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-60"></span>
                  <span class="relative inline-flex h-2 w-2 rounded-full bg-rose-500"></span>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-rose-300/90">{{ greeting }}, {{ firstName }}</p>
              </div>

              <h1 class="mt-4 text-3xl font-extrabold leading-[1.08] tracking-tight text-white sm:text-4xl lg:text-[2.5rem]">
                Antrian Penanganan<br class="hidden sm:block" />
                <span class="text-rose-400">Kasus Perundungan.</span>
              </h1>

              <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-400 sm:text-[15px]">
                Kelola dan tindak lanjuti laporan perundungan dari siswa dengan aman, cepat,
                dan berempati. Setiap laporan ditangani secara rahasia oleh petugas berwenang.
              </p>

              <div class="mt-6">
                <div v-if="!isLoadingStats" class="inline-flex items-center gap-2 rounded-full border border-slate-700/80 bg-slate-950/70 py-1.5 pl-3 pr-4 text-xs text-slate-300 backdrop-blur-sm">
                  <span v-if="totalPending > 0" class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-60"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-400"></span>
                  </span>
                  <span v-else class="h-2 w-2 rounded-full bg-emerald-400" aria-hidden="true"></span>
                  <span class="font-medium">{{ activeSummary }}</span>
                </div>
              </div>
            </div>

            <aside class="rounded-xl border border-slate-800 bg-slate-950/70 p-5 backdrop-blur-sm">
              <div class="flex items-center justify-between gap-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Petugas Penanganan</p>
                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-400">
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                  </svg>
                  Terverifikasi
                </span>
              </div>

              <div class="mt-4 flex items-center gap-3.5">
                <div class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-sm font-bold text-emerald-400">
                  {{ initials }}
                  <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-slate-950 bg-emerald-500" aria-hidden="true"></span>
                </div>
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-white">{{ authStore.user?.name || 'Guru Bimbingan Konseling' }}</p>
                  <p class="mt-0.5 truncate text-xs text-slate-400">Bimbingan Konseling</p>
                </div>
              </div>

              <div class="mt-5 flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/70 px-3.5 py-3">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Kasus Aktif</p>
                  <p class="mt-1 text-lg font-extrabold leading-none tabular-nums text-white">{{ activeCases }}</p>
                </div>
                <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-5.99-.626-8.542-1.744M21 12V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h10.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H21v-4z" />
                </svg>
              </div>
            </aside>
          </div>

          <div class="grid grid-cols-1 gap-4 border-t border-slate-800/70 pt-5 sm:grid-cols-3 sm:gap-6">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Kerahasiaan Terjamin</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Identitas pelapor anonim tetap dilindungi</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Penanganan Berempati</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Keamanan psikologis siswa menjadi prioritas</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Dokumentasi Transparan</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Setiap tindak lanjut tercatat pada sistem</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Statistik penanganan ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Ringkasan Penanganan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pantau beban kasus dan progres penanganan perundungan.</p>
          </div>
        </div>

        <!-- Skeleton saat loading stats -->
        <div v-if="isLoadingStats" class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
          <div v-for="i in 4" :key="i" class="h-32 rounded-xl border border-slate-800 bg-slate-900 animate-pulse" />
        </div>

        <div v-else class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
          <article
            v-for="s in statCards"
            :key="s.label"
            class="group rounded-xl border border-slate-800 bg-slate-900 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-700 sm:p-5"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ s.label }}</p>
                  <span v-if="s.pulse && s.value > 0" class="relative flex h-1.5 w-1.5" aria-hidden="true">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-60"></span>
                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                  </span>
                </div>
                <p class="mt-2 text-3xl font-extrabold tracking-tight tabular-nums" :class="s.num">{{ s.value }}</p>
              </div>
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border transition-transform duration-200 group-hover:scale-110" :class="s.tile">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" :d="s.icon" />
                </svg>
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

      <!-- ===== Antrian kasus ===== -->
      <section class="fade-up" style="animation-delay: 180ms">
        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">

          <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5">
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">Daftar Kasus Perundungan</h2>
              <p class="mt-0.5 text-xs text-slate-500">Telusuri antrian dan tindak lanjuti setiap laporan siswa.</p>
            </div>
          </div>

          <div class="space-y-4 border-b border-slate-800/80 p-4 sm:p-5">
            <div class="relative">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
              </svg>
              <input
                v-model="searchQuery"
                type="text"
                aria-label="Cari kasus"
                placeholder="Cari Tiket / Kategori..."
                class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-rose-500/60 focus:outline-none focus:ring-2 focus:ring-rose-500/15"
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

            <div class="flex flex-wrap items-center gap-2">
              <button
                v-for="opt in statusOptions"
                :key="opt.value"
                type="button"
                :aria-pressed="selectedStatus === opt.value"
                @click="selectStatus(opt.value)"
                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
                :class="selectedStatus === opt.value ? opt.active : pillIdle"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80" aria-hidden="true"></span>
                {{ opt.label }}
                <span class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500">{{ statusCounts[opt.value] || 0 }}</span>
              </button>

              <div class="ml-auto flex items-center gap-3 pl-2">
                <p class="whitespace-nowrap text-[11px] text-slate-500">
                  Menampilkan <span class="font-semibold tabular-nums text-slate-300">{{ reportRows.length }}</span> dari {{ totalCases }} kasus
                </p>
                <button
                  v-if="hasActiveFilters"
                  type="button"
                  @click="clearFilters"
                  class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-[11px] font-semibold text-slate-400 transition-all duration-150 hover:border-rose-500/40 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50"
                >
                  Atur ulang
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1fr)_140px_105px_100px_140px_165px] xl:gap-x-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Kasus</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Pelapor</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Prioritas</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tgl Kejadian</p>
            <p class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</p>
          </div>

          <!-- Skeleton loading list -->
          <div v-if="isLoading" class="divide-y divide-slate-800/70">
            <div v-for="i in 4" :key="i" class="px-5 py-4 sm:px-6">
              <div class="h-4 w-24 rounded bg-slate-800 animate-pulse mb-2"></div>
              <div class="h-4 w-64 rounded bg-slate-800 animate-pulse"></div>
            </div>
          </div>

          <div v-else-if="reportRows.length > 0" class="divide-y divide-slate-800/70">
            <article
              v-for="(item, i) in reportRows"
              :key="item.id"
              class="card-enter group relative flex cursor-pointer flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1fr)_140px_105px_100px_140px_165px] xl:items-center xl:gap-x-4"
              :style="{ animationDelay: (i * 60) + 'ms' }"
              @click="goToDetail(item.id)"
            >
              <span class="absolute bottom-3 left-0 top-3 w-[3px] rounded-r-full bg-rose-500/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100" aria-hidden="true"></span>

              <div class="min-w-0">
                <span class="rounded border border-rose-500/20 bg-rose-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-rose-400">{{ item.ticket_code }}</span>
                <h3 class="mt-2 truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white">{{ item.category }}</h3>
              </div>

              <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents">
                <div class="min-w-0">
                  <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-200">
                    <svg class="h-3.5 w-3.5 shrink-0 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ item.reporter_relation }}
                  </p>
                  <p class="mt-0.5 flex items-center gap-1 text-[10px] text-slate-500">
                    <svg v-if="item.is_anonymous" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <svg v-else class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ item.is_anonymous ? 'Identitas dilindungi' : 'Teridentifikasi' }}</span>
                  </p>
                </div>

                <div>
                  <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="item.statusBadge">
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                    {{ item.status }}
                  </span>
                </div>

                <div class="flex items-center gap-2">
                  <div class="flex items-end gap-[3px]" aria-hidden="true">
                    <span class="h-1.5 w-[3px] rounded-[1px]" :class="item.priority.level >= 1 ? item.priority.bar : 'bg-slate-700'"></span>
                    <span class="h-2 w-[3px] rounded-[1px]" :class="item.priority.level >= 2 ? item.priority.bar : 'bg-slate-700'"></span>
                    <span class="h-2.5 w-[3px] rounded-[1px]" :class="item.priority.level >= 3 ? item.priority.bar : 'bg-slate-700'"></span>
                  </div>
                  <span class="text-[11px] font-medium" :class="item.priority.text">{{ item.priority.label }}</span>
                </div>

                <div class="min-w-0">
                  <p class="flex items-center gap-1.5 text-xs text-slate-400">
                    <svg class="h-3.5 w-3.5 shrink-0 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ formatIncidentDate(item.incident_date) }}</span>
                  </p>
                  <p class="mt-0.5 pl-5 text-[10px] text-slate-600">Dilaporkan {{ formatCreatedTime(item.created_at) }}</p>
                </div>
              </div>

              <div class="flex justify-end">
                <button
                  type="button"
                  @click.stop="goToDetail(item.id)"
                  class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-3.5 py-2 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:bg-emerald-500/10 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 sm:w-auto"
                >
                  Tinjau &amp; Tangani
                  <svg class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            </article>
          </div>

          <div v-else class="flex flex-col items-center px-6 py-16 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
              <svg v-if="hasActiveFilters" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
              </svg>
              <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-200">
              {{ hasActiveFilters ? 'Kasus tidak ditemukan' : 'Tidak ada kasus perundungan' }}
            </p>
            <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
              {{ hasActiveFilters
                ? 'Coba gunakan kata kunci lain atau atur ulang filter status.'
                : 'Saat ini tidak ada laporan perundungan dalam antrian — lingkungan yang lebih aman bagi seluruh siswa.' }}
            </p>

            <button
              v-if="hasActiveFilters"
              type="button"
              @click="clearFilters"
              class="mt-6 inline-flex items-center gap-1.5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-semibold text-rose-400 transition-all duration-200 hover:bg-rose-500 hover:text-slate-950 active:scale-[.97]"
            >
              Atur Ulang Filter
            </button>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 sm:px-6">
            <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
              <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              Laporan anonim hanya menampilkan relasi pelapor — identitas asli diakses petugas berwenang saat penanganan
            </p>
            <p class="whitespace-nowrap text-[11px] text-slate-600">{{ totalCases }} kasus terdaftar</p>
          </div>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="text-[11px] text-slate-600">Laporan perundungan ditangani secara rahasia oleh petugas berwenang</p>
      </div>
    </footer>
  </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
.sapa-root {
  font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}
</style>

<style scoped>
.fade-up {
  opacity: 0;
  animation: fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}
.card-enter {
  opacity: 0;
  animation: card-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes card-in {
  from { opacity: 0; }
  to   { opacity: 1; }
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
  .fade-up,
  .card-enter,
  .stat-bar { animation: none; opacity: 1; }
}
</style>