<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const logoFailed = ref(false)

/* ---------------------------------- */
/* State filter (existing)            */
/* ---------------------------------- */
const searchQuery = ref('')
const selectedStatus = ref('Semua')
const selectedPriority = ref('Semua')

/* ---------------------------------- */
/* Data arsip (existing, verbatim)    */
/* ---------------------------------- */
const archivedReports = ref([
  {
    id: 101,
    ticket_code: 'RPT-2026-001',
    category: 'Cyberbullying & Ancaman',
    reporter_name: 'Ahmad Faisal',
    is_anonymous: false,
    reporter_class: 'XI RPL 2',
    created_at: '02 Sep 2026',
    closed_at: '08 Sep 2026',
    status: 'Selesai',
    priority: 'Tinggi',
    handled_by: 'Dra. Hj. Nurhaliza, M.Pd',
    action_taken: 'Mediasi Damai & Sesi Konseling Individu',
    summary: 'Terlapor telah meminta maaf dan menandatangani surat perjanjian. Kasus dinyatakan selesai secara kekeluargaan.'
  },
  {
    id: 102,
    ticket_code: 'RPT-2026-002',
    category: 'Perundungan Fisik',
    reporter_name: 'Anonim',
    is_anonymous: true,
    reporter_class: 'X TKJ 1',
    created_at: '25 Agt 2026',
    closed_at: '30 Agt 2026',
    status: 'Selesai',
    priority: 'Tinggi',
    handled_by: 'Bpk. Bambang, S.Pd',
    action_taken: 'Pemanggilan Orang Tua & Skorsing Terlapor',
    summary: 'Tindak lanjut bersama tim Kesiswaan dan orang tua murid. Pelaku mendapatkan sanksi pembinaan.'
  },
  {
    id: 103,
    ticket_code: 'RPT-2026-003',
    category: 'Pelecehan Verbal',
    reporter_name: 'Siti Rahma',
    is_anonymous: false,
    reporter_class: 'XII AKL 3',
    created_at: '18 Agt 2026',
    closed_at: '20 Agt 2026',
    status: 'Ditolak',
    priority: 'Rendah',
    handled_by: 'Dra. Hj. Nurhaliza, M.Pd',
    action_taken: 'Klarifikasi & Penghentian Pengaduan',
    summary: 'Laporan ditolak setelah klarifikasi awal menunjukkan kesalahpahaman informasi antar siswa.'
  },
  {
    id: 104,
    ticket_code: 'RPT-2026-004',
    category: 'Pengucilan / Social Bullying',
    reporter_name: 'Budi Santoso',
    is_anonymous: false,
    reporter_class: 'X MM 2',
    created_at: '10 Jul 2026',
    closed_at: '22 Jul 2026',
    status: 'Selesai',
    priority: 'Sedang',
    handled_by: 'Dra. Hj. Nurhaliza, M.Pd',
    action_taken: 'Konseling Kelompok / Restorative Justice',
    summary: 'Pendampingan kelompok kelas oleh wali kelas dan konselor BK.'
  }
])

/* ---------------------------------- */
/* Filter (logika existing)           */
/* ---------------------------------- */
const filteredReports = computed(() => {
  return archivedReports.value.filter((item) => {
    const matchesSearch =
      item.ticket_code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.category.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.reporter_name.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchesStatus = selectedStatus.value === 'Semua' || item.status === selectedStatus.value
    const matchesPriority = selectedPriority.value === 'Semua' || item.priority === selectedPriority.value

    return matchesSearch && matchesStatus && matchesPriority
  })
})

/* ---------------------------------- */
/* Statistik (computed existing,      */
/* kini dengan resep kartu sistem)    */
/* ---------------------------------- */
const stats = computed(() => ({
  total: archivedReports.value.length,
  completed: archivedReports.value.filter((r) => r.status === 'Selesai').length,
  rejected: archivedReports.value.filter((r) => r.status === 'Ditolak').length
}))

const statCards = computed(() => {
  const { total, completed, rejected } = stats.value
  const pct = (n) => (total > 0 ? Math.round((n / total) * 100) : 0)
  return [
    {
      label: 'Total Kasus Diarsipkan',
      value: total,
      caption: 'Dokumentasi kasus yang telah ditutup',
      pct: 100,
      icon: 'M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
      num: 'text-white',
      tile: 'border-rose-500/25 bg-rose-500/10 text-rose-400',
      bar: 'bg-rose-500',
    },
    {
      label: 'Kasus Tuntas (Selesai)',
      value: completed,
      caption: `${pct(completed)}% dari total arsip`,
      pct: pct(completed),
      icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      num: 'text-slate-300',
      tile: 'border-slate-600/50 bg-slate-700/30 text-slate-300',
      bar: 'bg-slate-500',
    },
    {
      label: 'Laporan Ditolak / Tidak Valid',
      value: rejected,
      caption: `${pct(rejected)}% dari total arsip`,
      pct: pct(rejected),
      icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
      num: 'text-red-400',
      tile: 'border-red-500/25 bg-red-500/10 text-red-400',
      bar: 'bg-red-500',
    },
  ]
})

/* ---------------------------------- */
/* Pil status + hitungan              */
/* ---------------------------------- */
const statusOptions = [
  { value: 'Semua',   label: 'Semua',   active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Selesai', label: 'Selesai', active: 'border-slate-600 bg-slate-700/40 text-slate-200' },
  { value: 'Ditolak', label: 'Ditolak', active: 'border-red-500/50 bg-red-500/15 text-red-400' },
]

const pillIdle = 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'

const statusCounts = computed(() => {
  const counts = { Semua: archivedReports.value.length }
  for (const r of archivedReports.value) counts[r.status] = (counts[r.status] || 0) + 1
  return counts
})

const hasActiveFilters = computed(() =>
  searchQuery.value.trim() !== '' || selectedStatus.value !== 'Semua' || selectedPriority.value !== 'Semua'
)

const clearFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = 'Semua'
  selectedPriority.value = 'Semua'
}

/* ---------------------------------- */
/* Helper badge — warna diselaraskan  */
/* sistem (Selesai=slate, Ditolak=red)*/
/* ---------------------------------- */
const getStatusStyle = (status) => {
  switch (status) {
    case 'Selesai': return 'bg-slate-500/10 text-slate-300 border-slate-500/20'
    case 'Ditolak': return 'bg-red-500/10 text-red-400 border-red-500/20'
    default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  }
}

/* Prioritas — bar sinyal ala antrian BK */
const getPriority = (priority) => {
  const map = {
    'Tinggi': { label: 'Tinggi', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    'Sedang': { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    'Rendah': { label: 'Rendah', level: 1, text: 'text-slate-400', bar: 'bg-slate-500' },
  }
  return map[priority] || map['Rendah']
}

/* Baris siap-render dengan badge ter-prakomputasi */
const reportRows = computed(() =>
  filteredReports.value.map(item => ({
    ...item,
    statusBadge: getStatusStyle(item.status),
    priority: getPriority(item.priority),
  }))
)

/* ---------------------------------- */
/* Modal ringkasan (existing          */
/* selectedReport + openDetailModal)  */
/* ---------------------------------- */
const selectedReport = ref(null)

const openDetailModal = (item) => {
  selectedReport.value = item
}

const closeDetailModal = () => {
  selectedReport.value = null
}

/* Navigasi */
const goToDetail = (id) => router.push(`/counselor/bullying-reports/${id}`)

const goToDetailFromModal = () => {
  const id = selectedReport.value?.id
  closeDetailModal()
  router.push(`/counselor/bullying-reports/${id}`)
}

const goToQueue = () => router.push('/counselor/bullying-queue')

/* Kembali: fallback ke antrian bila dibuka langsung */
const goBack = () => {
  if (typeof window !== 'undefined' && window.history.state?.back) {
    router.back()
  } else {
    router.push('/counselor/bullying-queue')
  }
}

/* Escape menutup modal + kunci scroll body */
const handleEscKey = (e) => {
  if (e.key === 'Escape' && selectedReport.value) closeDetailModal()
}

watch(selectedReport, (v) => {
  document.body.style.overflow = v ? 'hidden' : ''
})

onMounted(() => window.addEventListener('keydown', handleEscKey))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscKey)
  document.body.style.overflow = ''
})

/* Turunan tampilan */
const initialsOf = (name) => {
  const n = (name || '').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/)
  return (parts.length > 1 ? parts[0][0] + parts[parts.length - 1][0] : n.slice(0, 2)).toUpperCase()
}

const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas ============ -->
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">

        <!-- Kembali + merek -->
        <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
          <button
            type="button"
            @click="goBack"
            title="Kembali"
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
              <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Dokumentasi Kasus Perundungan</p>
            </div>
          </div>
        </div>

        <!-- Tautan antrian aktif -->
        <button
          type="button"
          @click="goToQueue"
          class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h10M4 18h10" />
          </svg>
          <span class="hidden sm:inline">Lihat Antrian Aktif</span>
          <span class="sm:hidden">Antrian</span>
        </button>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- ===== Kepala halaman ===== -->
      <section class="fade-up">
        <div class="flex items-center gap-2.5">
          <span class="h-2 w-2 rounded-full bg-rose-500" aria-hidden="true"></span>
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-rose-300/90">Panel BK · Dokumentasi Kasus</p>
        </div>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Arsip &amp; Riwayat Penanganan</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-400">Dokumentasi kasus penanganan perundungan yang telah ditutup — setiap tindak lanjut tercatat sebagai jejak audit.</p>
      </section>

      <!-- ===== Statistik arsip ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Ringkasan Arsip</h2>
            <p class="mt-0.5 text-xs text-slate-500">Hasil akhir seluruh kasus yang telah ditutup.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
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

      <!-- ===== Tabel arsip ===== -->
      <section class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 180ms">

        <!-- Kepala seksi -->
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5">
          <div class="flex items-center gap-3">
            <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">Daftar Arsip Kasus</h2>
              <p class="mt-0.5 text-xs text-slate-500">Telusuri dokumentasi kasus yang telah ditutup.</p>
            </div>
          </div>
          <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400">{{ archivedReports.length }} Kasus</span>
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
                aria-label="Cari arsip kasus"
                placeholder="Cari Tiket, Kategori, Pelapor..."
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

            <!-- Filter prioritas -->
            <div class="relative w-full sm:w-52">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
              </svg>
              <select
                v-model="selectedPriority"
                aria-label="Filter prioritas"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-rose-500/60 focus:outline-none focus:ring-2 focus:ring-rose-500/15"
              >
                <option value="Semua">Semua Prioritas</option>
                <option value="Tinggi">Prioritas Tinggi</option>
                <option value="Sedang">Prioritas Sedang</option>
                <option value="Rendah">Prioritas Rendah</option>
              </select>
              <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Pil status + ringkasan hasil -->
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-for="opt in statusOptions"
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
                Menampilkan <span class="font-semibold tabular-nums text-slate-300">{{ reportRows.length }}</span> dari {{ stats.total }} arsip
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

        <!-- Label kolom (desktop lebar) -->
        <div class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.35fr)_150px_95px_170px_95px_190px] xl:gap-x-4">
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Kasus</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Pelapor</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Prioritas</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Penutupan</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</p>
          <p class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</p>
        </div>

        <!-- Baris arsip -->
        <div v-if="reportRows.length > 0" class="divide-y divide-slate-800/70">
          <article
            v-for="(item, i) in reportRows"
            :key="item.id"
            class="card-enter group relative flex cursor-pointer flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.35fr)_150px_95px_170px_95px_190px] xl:items-center xl:gap-x-4"
            :style="{ animationDelay: (i * 60) + 'ms' }"
            @click="openDetailModal(item)"
          >
            <!-- Aksen channel, muncul saat hover -->
            <span class="absolute bottom-3 left-0 top-3 w-[3px] rounded-r-full bg-rose-500/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100" aria-hidden="true"></span>

            <!-- Kolom kasus -->
            <div class="min-w-0">
              <span class="rounded border border-rose-500/20 bg-rose-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-rose-400">{{ item.ticket_code }}</span>
              <h3 class="mt-2 truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white">{{ item.category }}</h3>
              <p class="mt-0.5 truncate text-[10px] text-slate-600">Ditangani oleh {{ item.handled_by }}</p>
            </div>

            <!-- Meta: pelapor, prioritas, penutupan, status -->
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents">
              <!-- Pelapor -->
              <div class="min-w-0">
                <p v-if="item.is_anonymous" class="flex items-center gap-1.5 text-xs font-semibold text-slate-300">
                  <svg class="h-3.5 w-3.5 shrink-0 text-rose-400/80" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Anonim
                </p>
                <template v-else>
                  <p class="truncate text-xs font-semibold text-slate-200">{{ item.reporter_name }}</p>
                  <p class="mt-0.5 truncate text-[10px] text-slate-500">{{ item.reporter_class }}</p>
                </template>
                <p v-if="item.is_anonymous" class="mt-0.5 truncate text-[10px] text-slate-600">Identitas dilindungi</p>
              </div>

              <!-- Prioritas -->
              <div class="flex items-center gap-2">
                <div class="flex items-end gap-[3px]" aria-hidden="true">
                  <span class="h-1.5 w-[3px] rounded-[1px]" :class="item.priority.level >= 1 ? item.priority.bar : 'bg-slate-700'"></span>
                  <span class="h-2 w-[3px] rounded-[1px]" :class="item.priority.level >= 2 ? item.priority.bar : 'bg-slate-700'"></span>
                  <span class="h-2.5 w-[3px] rounded-[1px]" :class="item.priority.level >= 3 ? item.priority.bar : 'bg-slate-700'"></span>
                </div>
                <span class="text-[11px] font-medium" :class="item.priority.text">{{ item.priority.label }}</span>
              </div>

              <!-- Penutupan -->
              <div class="min-w-0">
                <p class="font-mono text-xs text-slate-400">{{ item.closed_at }}</p>
                <p class="mt-0.5 text-[10px] text-slate-600">Dibuka {{ item.created_at }}</p>
              </div>

              <!-- Status -->
              <div>
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="item.statusBadge">
                  <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ item.status }}
                </span>
              </div>
            </div>

            <!-- Aksi -->
            <div class="mt-1 flex flex-wrap justify-end gap-2 xl:mt-0">
              <button
                type="button"
                @click.stop="openDetailModal(item)"
                class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-2 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-slate-600 hover:text-white active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 sm:w-auto"
              >
                Ringkasan
              </button>
              <button
                type="button"
                @click.stop="goToDetail(item.id)"
                class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-400 transition-all duration-150 hover:bg-emerald-500 hover:text-slate-950 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 sm:w-auto"
              >
                Detail
                <svg class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
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
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
          </div>
          <p class="mt-4 text-sm font-semibold text-slate-200">
            {{ hasActiveFilters ? 'Arsip tidak ditemukan' : 'Belum ada kasus terarsip' }}
          </p>
          <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
            {{ hasActiveFilters
              ? 'Coba gunakan kata kunci lain atau atur ulang filter status dan prioritas.'
              : 'Kasus yang telah ditutup akan terdokumentasi di sini secara otomatis.' }}
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

        <!-- Kaki daftar -->
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 sm:px-6">
          <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
            <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Arsip kasus bersifat rahasia — hanya dapat diakses oleh petugas berwenang
          </p>
          <p class="whitespace-nowrap text-[11px] text-slate-600">{{ stats.total }} kasus terdokumentasi</p>
        </div>
      </section>
    </main>

    <!-- ============ Footer ============ -->
    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="text-[11px] text-slate-600">Laporan perundungan ditangani secara rahasia oleh petugas berwenang</p>
      </div>
    </footer>

    <!-- ============ Modal ringkasan kasus ============ -->
    <div
      v-if="selectedReport"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="archive-modal-title"
    >
      <!-- Latar belakang -->
      <div class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm" aria-hidden="true" @click="closeDetailModal"></div>

      <!-- Panel -->
      <div class="modal-panel relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/50">
        <span class="absolute inset-x-0 top-0 z-20 h-[2px] bg-gradient-to-r from-rose-500/70 via-rose-500/20 to-transparent" aria-hidden="true"></span>

        <!-- Kepala modal -->
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-800/70 px-5 py-4">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded border border-rose-500/20 bg-rose-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-rose-400">{{ selectedReport.ticket_code }}</span>
              <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="selectedReport.statusBadge">
                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                {{ selectedReport.status }}
              </span>
              <span class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-950/50 px-2.5 py-0.5">
                <span class="flex items-end gap-[3px]" aria-hidden="true">
                  <span class="h-1.5 w-[3px] rounded-[1px]" :class="selectedReport.priority.level >= 1 ? selectedReport.priority.bar : 'bg-slate-700'"></span>
                  <span class="h-2 w-[3px] rounded-[1px]" :class="selectedReport.priority.level >= 2 ? selectedReport.priority.bar : 'bg-slate-700'"></span>
                  <span class="h-2.5 w-[3px] rounded-[1px]" :class="selectedReport.priority.level >= 3 ? selectedReport.priority.bar : 'bg-slate-700'"></span>
                </span>
                <span class="text-[11px] font-semibold" :class="selectedReport.priority.text">Prioritas {{ selectedReport.priority.label }}</span>
              </span>
            </div>
            <h3 id="archive-modal-title" class="mt-2.5 text-base font-bold leading-snug tracking-tight text-white">{{ selectedReport.category }}</h3>
          </div>

          <button
            type="button"
            @click="closeDetailModal"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Badan modal (dapat digulir) -->
        <div class="modal-scroll min-h-0 flex-1 space-y-5 overflow-y-auto p-5">

          <!-- Metadata kasus -->
          <dl class="grid grid-cols-2 gap-3">
            <div class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
              <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Pelapor</dt>
              <dd class="mt-1 min-w-0">
                <template v-if="selectedReport.is_anonymous">
                  <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-300">
                    <svg class="h-3.5 w-3.5 shrink-0 text-rose-400/80" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Anonim
                  </p>
                  <p class="mt-0.5 text-[10px] text-slate-500">{{ selectedReport.reporter_class }} · Identitas dilindungi</p>
                </template>
                <template v-else>
                  <p class="truncate text-xs font-semibold text-slate-200">{{ selectedReport.reporter_name }}</p>
                  <p class="mt-0.5 truncate text-[10px] text-slate-500">{{ selectedReport.reporter_class }}</p>
                </template>
              </dd>
            </div>

            <div class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
              <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Petugas Penanganan</dt>
              <dd class="mt-1.5 flex items-center gap-2">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[9px] font-bold text-emerald-400">
                  {{ initialsOf(selectedReport.handled_by) }}
                </span>
                <span class="truncate text-xs font-medium text-slate-200">{{ selectedReport.handled_by }}</span>
              </dd>
            </div>

            <div class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
              <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Dibuka</dt>
              <dd class="mt-1 font-mono text-xs text-slate-400">{{ selectedReport.created_at }}</dd>
            </div>

            <div class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
              <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Ditutup</dt>
              <dd class="mt-1 font-mono text-xs text-slate-400">{{ selectedReport.closed_at }}</dd>
            </div>
          </dl>

          <!-- Tindakan akhir -->
          <div class="flex items-start gap-2.5 rounded-lg border border-emerald-500/25 bg-emerald-500/[0.05] px-3.5 py-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-400/90" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <div class="min-w-0">
              <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Tindakan Akhir Guru BK</p>
              <p class="mt-1 text-xs font-semibold leading-snug text-emerald-400">{{ selectedReport.action_taken }}</p>
            </div>
          </div>

          <!-- Ringkasan penyelesaian -->
          <div class="space-y-2">
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Ringkasan Penyelesaian</p>
            <p class="rounded-lg border border-slate-800 bg-slate-950/60 p-3.5 text-xs leading-relaxed text-slate-300">
              {{ selectedReport.summary }}
            </p>
          </div>
        </div>

        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-slate-800/70 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[10px] leading-relaxed text-slate-600">Dokumentasi rahasia internal BK — tidak untuk disebarluaskan.</p>
            <div class="flex flex-col-reverse gap-2.5 sm:flex-row">
              <button
                type="button"
                @click="closeDetailModal"
                class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
              >
                Tutup
              </button>
              <button
                type="button"
                @click="goToDetailFromModal"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
              >
                Buka Detail Lengkap
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
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

/* Bar statistik tumbuh dari kiri saat mount */
.stat-bar {
  transform-origin: left center;
  animation: grow-x 0.8s cubic-bezier(0.22, 1, 0.36, 1) 0.3s both;
}

@keyframes grow-x {
  from { transform: scaleX(0); }
  to   { transform: scaleX(1); }
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

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .card-enter,
  .stat-bar,
  .backdrop-in,
  .modal-panel { animation: none; opacity: 1; }
}
</style>