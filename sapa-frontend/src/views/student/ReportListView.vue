<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

/* ---------------------------------- */
/* State filter (behavior existing)   */
/* ---------------------------------- */
const search = ref('')
const selectedStatus = ref('all')
const selectedType = ref('all') // tambahan: filter berdasarkan tipe laporan

/* ---------------------------------- */
/* Data laporan (existing)            */
/* ---------------------------------- */
const reports = ref([
  {
    id: 1,
    report_code: 'REP-2026-001',
    title: 'AC Ruang Kelas 12 IPA 1 Rusak dan Bising',
    type: 'facility',
    status: 'in_progress',
    created_at: '2026-03-01T08:30:00Z',
  },
  {
    id: 2,
    report_code: 'REP-2026-002',
    title: 'Usulan Penambahan Ekskul Coding & Robotik',
    type: 'aspiration',
    status: 'pending',
    created_at: '2026-03-02T10:15:00Z',
  },
  {
    id: 3,
    report_code: 'REP-2026-003',
    title: 'Laporan Kerusakan Lampu Lapangan Basket',
    type: 'facility',
    status: 'resolved',
    created_at: '2026-02-20T14:00:00Z',
  },
])

/* ---------------------------------- */
/* Filter (logika existing + tipe +   */
/* urutan terbaru lebih dulu)         */
/* ---------------------------------- */
const filteredReports = computed(() => {
  const result = reports.value.filter(item => {
    const matchesSearch = item.title.toLowerCase().includes(search.value.toLowerCase()) ||
                          item.report_code.toLowerCase().includes(search.value.toLowerCase())
    const matchesStatus = selectedStatus.value === 'all' || item.status === selectedStatus.value
    const matchesType = selectedType.value === 'all' || item.type === selectedType.value
    return matchesSearch && matchesStatus && matchesType
  })
  return [...result].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

/* ---------------------------------- */
/* Opsi filter                        */
/* ---------------------------------- */
const statusOptions = [
  { value: 'all',         label: 'Semua',     active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'pending',     label: 'Menunggu',  active: 'border-amber-500/50 bg-amber-500/15 text-amber-400' },
  { value: 'reviewing',   label: 'Ditinjau',  active: 'border-blue-500/50 bg-blue-500/15 text-blue-400' },
  { value: 'in_progress', label: 'Diproses',  active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'resolved',    label: 'Selesai',   active: 'border-slate-600 bg-slate-700/40 text-slate-200' },
  { value: 'rejected',    label: 'Ditolak',   active: 'border-red-500/50 bg-red-500/15 text-red-400' },
]

const typeOptions = [
  { value: 'all',       label: 'Semua Tipe' },
  { value: 'aspiration', label: 'Aspirasi' },
  { value: 'facility',   label: 'Fasilitas' },
  { value: 'bullying',   label: 'Perundungan' },
]

const pillIdle = 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'

const statusCounts = computed(() => {
  const counts = { all: reports.value.length }
  for (const r of reports.value) {
    counts[r.status] = (counts[r.status] || 0) + 1
  }
  return counts
})

const hasActiveFilters = computed(() =>
  search.value.trim() !== '' || selectedStatus.value !== 'all' || selectedType.value !== 'all'
)

const clearFilters = () => {
  search.value = ''
  selectedStatus.value = 'all'
  selectedType.value = 'all'
}

/* ---------------------------------- */
/* Helpers existing                   */
/* ---------------------------------- */
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
    month: 'long',
    year: 'numeric'
  })
}

/* ---------------------------------- */
/* Baris siap-render                  */
/* ---------------------------------- */
const typeAccent = {
  aspiration: 'bg-purple-500',
  facility: 'bg-cyan-500',
  bullying: 'bg-rose-500',
}

const reportRows = computed(() =>
  filteredReports.value.map(item => ({
    ...item,
    typeBadge: getTypeBadge(item.type),
    statusBadge: getStatusBadge(item.status),
    accent: typeAccent[item.type] || 'bg-emerald-500',
  }))
)

const goToDetail = (id) => router.push(`/reports/${id}`)
const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas ============ -->
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">

        <button
          type="button"
          @click="router.push('/')"
          class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
        >
          <svg class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span class="hidden sm:inline">Kembali ke Dashboard</span>
          <span class="sm:hidden">Kembali</span>
        </button>

        <router-link
          to="/reports/new"
          class="group inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg bg-emerald-500 px-3.5 py-2 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
        >
          <svg class="h-4 w-4 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          <span>Buat Laporan Baru</span>
        </router-link>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-6xl flex-1 space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- Kepala halaman -->
      <section class="fade-up">
        <div class="flex items-center gap-2.5">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-400/90">Riwayat Pelaporan</p>
        </div>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Semua Laporan Saya</h1>
        <p class="mt-2 text-sm text-slate-400">Daftar lengkap semua laporan yang pernah diajukan.</p>
      </section>

      <!-- Kartu daftar laporan -->
      <section class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 90ms">

        <!-- Toolbar filter -->
        <div class="space-y-4 border-b border-slate-800/80 p-4 sm:p-5">

          <div class="flex flex-col gap-3 sm:flex-row">
            <!-- Pencarian -->
            <div class="relative flex-1">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
              </svg>
              <input
                v-model="search"
                type="text"
                placeholder="Cari kode atau judul laporan..."
                class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              />
              <button
                v-if="search"
                type="button"
                @click="search = ''"
                aria-label="Bersihkan pencarian"
                class="absolute right-2.5 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-500 transition-colors duration-150 hover:bg-slate-800 hover:text-slate-200"
              >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Filter tipe -->
            <div class="relative sm:w-56">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
              </svg>
              <select
                v-model="selectedType"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              >
                <option v-for="t in typeOptions" :key="t.value" :value="t.value" class="bg-slate-900 text-slate-100">{{ t.label }}</option>
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
                Menampilkan <span class="font-semibold tabular-nums text-slate-300">{{ filteredReports.length }}</span> dari {{ reports.length }} laporan
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

        <!-- Label kolom (desktop) -->
        <div class="hidden border-b border-slate-800/70 bg-slate-950/50 px-4 py-2.5 sm:px-6 lg:grid lg:grid-cols-[minmax(0,1fr)_124px_112px_140px_100px] lg:gap-x-4">
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Laporan</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tipe</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tanggal</p>
          <p class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</p>
        </div>

        <!-- Daftar laporan -->
        <div v-if="reportRows.length > 0" class="divide-y divide-slate-800/70">
          <article
            v-for="row in reportRows"
            :key="row.id"
            @click="goToDetail(row.id)"
            class="group relative flex cursor-pointer flex-col gap-3 px-4 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 lg:grid lg:grid-cols-[minmax(0,1fr)_124px_112px_140px_100px] lg:items-center lg:gap-x-4"
          >
            <!-- Aksen tipe, muncul saat hover -->
            <span class="absolute bottom-3 left-0 top-3 w-[3px] rounded-r-full opacity-0 transition-opacity duration-200 group-hover:opacity-100" :class="row.accent" aria-hidden="true"></span>

            <!-- Kolom utama -->
            <div class="min-w-0">
              <span class="rounded border border-emerald-500/20 bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-emerald-400">{{ row.report_code }}</span>
              <h3 class="mt-2 truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white">{{ row.title }}</h3>
            </div>

            <!-- Meta: tipe, status, tanggal -->
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 lg:contents">
              <div>
                <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="row.typeBadge.class">{{ row.typeBadge.label }}</span>
              </div>

              <div>
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="row.statusBadge.class">
                  <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ row.statusBadge.label }}
                </span>
              </div>

              <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <svg class="h-3.5 w-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ formatDate(row.created_at) }}</span>
              </div>
            </div>

            <!-- Aksi -->
            <div class="flex justify-end">
              <button
                type="button"
                @click.stop="goToDetail(row.id)"
                class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-3.5 py-2 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:bg-emerald-500/10 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 sm:w-auto"
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
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <p class="mt-4 text-sm font-semibold text-slate-200">
            {{ hasActiveFilters ? 'Laporan tidak ditemukan' : 'Belum ada laporan' }}
          </p>
          <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
            {{ hasActiveFilters
              ? 'Coba gunakan kata kunci lain atau atur ulang filter status dan tipe laporan.'
              : 'Riwayat laporan Anda akan muncul di sini setelah laporan pertama dikirim.' }}
          </p>

          <button
            v-if="hasActiveFilters"
            type="button"
            @click="clearFilters"
            class="mt-6 inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-xs font-semibold text-emerald-400 transition-all duration-200 hover:bg-emerald-500 hover:text-slate-950 active:scale-[.97]"
          >
            Atur Ulang Filter
          </button>
          <router-link
            v-else
            to="/reports/new"
            class="mt-6 inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97]"
          >
            Buat Laporan Pertama
          </router-link>
        </div>

        <!-- Kaki daftar -->
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800/70 bg-slate-950/40 px-4 py-3 sm:px-6">
          <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
            <svg class="h-3.5 w-3.5 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Laporan perundungan dirahasiakan &amp; hanya ditinjau oleh petugas berwenang
          </p>
          <p class="text-[11px] text-slate-600">{{ reports.length }} laporan terdaftar</p>
        </div>
      </section>
    </main>

    <!-- ============ Footer ============ -->
    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="text-[11px] text-slate-600">Setiap laporan dijaga kerahasiaannya</p>
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
/* Entrance: fade-up halus dengan stagger */
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