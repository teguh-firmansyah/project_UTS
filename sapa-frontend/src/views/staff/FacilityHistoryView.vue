<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

/* Import Ikon Lucide */
import {
  ArrowLeft,
  LogOut,
  Archive,
  CheckCircle2,
  XCircle,
  Search,
  X,
  Building2,
  ChevronDown,
  Wrench,
  UserCheck,
  MapPin,
  Check,
  Printer,
  ArchiveX,
  ShieldCheck,
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const logoFailed = ref(false)

/* Navigasi */
const goBack = () => {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/staff/facility-queue')
  }
}

const goToDetail = (id) => router.push(`/staff/facility-reports/${id}`)

/* Logout */
const handleLogout = async () => {
  if (authStore.logout) {
    await authStore.logout()
  }
  toast.success('Berhasil keluar dari sistem.')
  router.push({ name: 'login' })
}

/* State Filter */
const searchQuery = ref('')
const selectedCategory = ref('ALL')
const selectedStatus = ref('ALL')

/* Data Riwayat */
const archivedReports = ref([
  {
    id: 104,
    ticket_code: 'FAS-2026-008',
    title: 'Proyektor Ruang Audio Visual Redup & Bergaris',
    category: 'Elektronik & Kelistrikan',
    location: 'Gedung Utama - Ruang AV',
    damage_level: 'Ringan',
    reporter_name: 'Budi Santoso (Guru)',
    is_anonymous: false,
    resolved_at: '2026-09-10 14:00',
    status: 'Selesai',
    technician_note: 'Dilakukan penggantian lampu proyektor baru dan pembersihan lensa.',
  },
  {
    id: 105,
    ticket_code: 'FAS-2026-009',
    title: 'Lampu Lapangan Basket Padam',
    category: 'Elektronik & Kelistrikan',
    location: 'Lapangan Basket Outdoor',
    damage_level: 'Ringan',
    reporter_name: 'Anonim',
    is_anonymous: true,
    resolved_at: '2026-09-09 16:10',
    status: 'Ditolak',
    technician_note: 'Bukan kerusakan fasilitas. Saklar utama hanya dalam kondisi OFF.',
  },
  {
    id: 98,
    ticket_code: 'FAS-2026-002',
    title: 'Pintu Kamar Mandi Siswi Lepas Engsel',
    category: 'Bangunan & Mebel',
    location: 'Toilet Wanita Lantai 2',
    damage_level: 'Sedang',
    reporter_name: 'Siti Aminah (Siswa)',
    is_anonymous: false,
    resolved_at: '2026-08-28 11:30',
    status: 'Selesai',
    technician_note: 'Penggantian engsel stainless baru dan penyesuaian posisi kusen.',
  },
  {
    id: 95,
    ticket_code: 'FAS-2026-000',
    title: 'Pompa Air Utama Gedung B Berisik & Lemah',
    category: 'Sanitasi & Plambing',
    location: 'Ruang Pompa Belakang',
    damage_level: 'Berat',
    reporter_name: 'Rahmat (Penjaga Sekolah)',
    is_anonymous: false,
    resolved_at: '2026-08-15 09:45',
    status: 'Selesai',
    technician_note: 'Penggantian bearing dan impeller pompa air.',
  },
])

const categories = [
  'ALL',
  'Elektronik & Kelistrikan',
  'Sanitasi & Plambing',
  'Bangunan & Mebel',
  'Fasilitas Olahraga',
  'Lainnya',
]

/* Computed Filter */
const filteredHistory = computed(() => {
  const result = archivedReports.value.filter((item) => {
    const matchesSearch =
      item.ticket_code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.location.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.reporter_name.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchesCategory =
      selectedCategory.value === 'ALL' || item.category === selectedCategory.value
    const matchesStatus =
      selectedStatus.value === 'ALL' || item.status === selectedStatus.value

    return matchesSearch && matchesCategory && matchesStatus
  })

  return [...result].sort((a, b) => {
    const da = parseDateTime(a.resolved_at)?.getTime() || 0
    const db = parseDateTime(b.resolved_at)?.getTime() || 0
    return db - da
  })
})

/* Statistik */
const totalArchived = computed(() => archivedReports.value.length)
const totalSuccess = computed(
  () => archivedReports.value.filter((r) => r.status === 'Selesai').length
)
const totalRejected = computed(
  () => archivedReports.value.filter((r) => r.status === 'Ditolak').length
)

const statCards = computed(() => {
  const pct = (n) =>
    totalArchived.value > 0 ? Math.round((n / totalArchived.value) * 100) : 0
  return [
    {
      label: 'Total Arsip Laporan',
      value: totalArchived.value,
      caption: 'Terdata dalam basis data sarpras',
      pct: 100,
      icon: Archive,
      num: 'text-white',
      tile: 'border-cyan-500/25 bg-cyan-500/10 text-cyan-400',
      bar: 'bg-cyan-500',
    },
    {
      label: 'Telah Diperbaiki',
      value: totalSuccess.value,
      caption: `${pct(totalSuccess.value)}% status Selesai / Tuntas`,
      pct: pct(totalSuccess.value),
      icon: CheckCircle2,
      num: 'text-slate-300',
      tile: 'border-slate-600/50 bg-slate-700/30 text-slate-300',
      bar: 'bg-slate-500',
    },
    {
      label: 'Laporan Ditolak',
      value: totalRejected.value,
      caption: `${pct(totalRejected.value)}% tidak valid / di luar wewenang`,
      pct: pct(totalRejected.value),
      icon: XCircle,
      num: 'text-red-400',
      tile: 'border-red-500/25 bg-red-500/10 text-red-400',
      bar: 'bg-red-500',
    },
  ]
})

/* Helpers Badges & Meta */
const getStatusBadge = (status) => {
  switch (status) {
    case 'Selesai':
      return 'bg-slate-500/10 text-slate-300 border-slate-500/20'
    case 'Ditolak':
      return 'bg-red-500/10 text-red-400 border-red-500/20'
    default:
      return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  }
}

const getDamageLevelBadge = (level) => {
  switch (level) {
    case 'Berat':
      return 'bg-rose-500/10 text-rose-400 border-rose-500/20'
    case 'Sedang':
      return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    case 'Ringan':
      return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    default:
      return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  }
}

const getDamageBorderClass = (level) => {
  switch (level) {
    case 'Berat':
      return 'border-l-rose-500'
    case 'Sedang':
      return 'border-l-amber-500'
    case 'Ringan':
      return 'border-l-emerald-500'
    default:
      return 'border-l-slate-700'
  }
}

const getDamageMeta = (level) => {
  const map = {
    Berat: { label: 'Berat', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    Sedang: { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    Ringan: { label: 'Ringan', level: 1, text: 'text-emerald-400', bar: 'bg-emerald-500' },
  }
  return map[level] || map['Ringan']
}

/* Options & Counts */
const statusOptions = [
  { value: 'ALL', label: 'Semua', active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Selesai', label: 'Selesai', active: 'border-slate-600 bg-slate-700/40 text-slate-200' },
  { value: 'Ditolak', label: 'Ditolak', active: 'border-red-500/50 bg-red-500/15 text-red-400' },
]

const pillIdle = 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'

const statusCounts = computed(() => {
  const counts = { ALL: archivedReports.value.length }
  for (const r of archivedReports.value) counts[r.status] = (counts[r.status] || 0) + 1
  return counts
})

const hasActiveFilters = computed(
  () => searchQuery.value.trim() !== '' || selectedCategory.value !== 'ALL' || selectedStatus.value !== 'ALL'
)

const clearFilters = () => {
  searchQuery.value = ''
  selectedCategory.value = 'ALL'
  selectedStatus.value = 'ALL'
}

const reportRows = computed(() =>
  filteredHistory.value.map((item) => ({
    ...item,
    statusBadge: getStatusBadge(item.status),
    damage: getDamageMeta(item.damage_level),
  }))
)

/* Formatter */
const parseDateTime = (d) => {
  if (!d) return null
  const date = new Date(String(d).replace(' ', 'T'))
  return isNaN(date.getTime()) ? null : date
}

const formatDate = (d) => {
  const date = parseDateTime(d)
  return date
    ? date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    : d || '—'
}

const formatTime = (d) => {
  const date = parseDateTime(d)
  return date
    ? date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
    : ''
}

/* Cetak Berita Acara */
const printReport = (item) => {
  const printWindow = window.open('', '_blank', 'height=600,width=800')
  if (!printWindow) {
    toast.error('Jendela cetak diblokir browser. Izinkan pop-up untuk mencetak Berita Acara.')
    return
  }

  const htmlContent = `
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8">
      <title>Berita Acara - ${item.ticket_code}</title>
      <style>
        body { font-family: 'Inter', Arial, sans-serif; padding: 20px; color: #1e293b; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px 0 0; font-size: 12px; color: #64748b; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table th, .info-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        .info-table th { background-color: #f8fafc; width: 30%; font-weight: bold; }
        .note-box { border: 1px solid #cbd5e1; background-color: #f8fafc; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 30px; }
        .signatures { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { text-align: center; width: 40%; font-size: 12px; }
        .signature-space { height: 60px; }
      </style>
    </head>
    <body>
      <div class="header">
        <h2>Berita Acara Penanganan Fasilitas</h2>
        <p>SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
      </div>

      <table class="info-table">
        <tr><th>No. Tiket</th><td><strong>${item.ticket_code}</strong></td></tr>
        <tr><th>Judul Laporan</th><td>${item.title}</td></tr>
        <tr><th>Kategori</th><td>${item.category}</td></tr>
        <tr><th>Lokasi Kerusakan</th><td>${item.location}</td></tr>
        <tr><th>Tingkat Kerusakan</th><td>${item.damage_level}</td></tr>
        <tr><th>Pelapor</th><td>${item.reporter_name}</td></tr>
        <tr><th>Tanggal Selesai</th><td>${item.resolved_at}</td></tr>
        <tr><th>Status Akhir</th><td><strong>${item.status}</strong></td></tr>
      </table>

      <div class="note-box">
        <strong>Catatan Akhir Penanganan / Hasil Pemeriksaan:</strong><br/>
        <p style="margin: 5px 0 0 0;">${item.technician_note}</p>
      </div>

      <div class="signatures">
        <div class="signature-box">
          <p>Petugas / Teknisi</p>
          <div class="signature-space"></div>
          <p><strong>( ${authStore.user?.name || 'Staff Sarpras'} )</strong></p>
        </div>
        <div class="signature-box">
          <p>Mengetahui, Kepala Sarpras</p>
          <div class="signature-space"></div>
          <p><strong>( ____________________ )</strong></p>
        </div>
      </div>
    </body>
    </html>
  `

  printWindow.document.write(htmlContent)
  printWindow.document.close()
  printWindow.focus()

  setTimeout(() => {
    printWindow.print()
    printWindow.close()
  }, 250)
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
            title="Kembali"
            class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <ArrowLeft class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5" />
            <span class="hidden sm:inline">Kembali</span>
          </button>

          <span class="hidden h-6 w-px bg-slate-800 sm:block" aria-hidden="true"></span>

          <div class="hidden min-w-0 items-center gap-2.5 sm:flex">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20">
              <img v-if="!logoFailed" src="@/assets/logo/logo sapa.jpeg" alt="Logo SAPA" class="h-full w-full object-cover" @error="logoFailed = true" />
              <span v-else class="text-sm font-extrabold text-emerald-400">S</span>
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-[15px] font-extrabold leading-none tracking-tight text-white">SAPA</p>
                <span class="rounded border border-cyan-500/30 bg-cyan-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-cyan-400">Staff Sarpras</span>
              </div>
              <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Arsip &amp; Riwayat Penanganan Fasilitas</p>
            </div>
          </div>
        </div>

        <!-- Aksi akun -->
        <div class="flex shrink-0 items-center gap-2">
          <div class="hidden h-6 w-px bg-slate-800 sm:block"></div>

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

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- ===== Kepala halaman ===== -->
      <section class="fade-up">
        <div class="flex items-center gap-2.5">
          <span class="h-2 w-2 rounded-full bg-cyan-500" aria-hidden="true"></span>
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-300/90">Panel Sarpras · Dokumentasi Perbaikan</p>
        </div>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Riwayat &amp; Arsip Perbaikan</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-400">Seluruh laporan kerusakan fasilitas yang telah tuntas dikerjakan atau ditolak — setiap penyelesaian dapat dicetak sebagai Berita Acara.</p>
      </section>

      <!-- ===== Statistik arsip ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Ringkasan Arsip</h2>
            <p class="mt-0.5 text-xs text-slate-500">Hasil akhir seluruh penanganan yang telah ditutup.</p>
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

      <!-- ===== Tabel arsip ===== -->
      <section class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 180ms">

        <!-- Kepala seksi -->
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5">
          <div class="flex items-center gap-3">
            <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">Daftar Arsip Perbaikan</h2>
              <p class="mt-0.5 text-xs text-slate-500">Telusuri dokumentasi perbaikan yang telah ditutup.</p>
            </div>
          </div>
          <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400">{{ totalArchived }} Tiket</span>
        </div>

        <!-- Toolbar filter -->
        <div class="space-y-4 border-b border-slate-800/80 p-4 sm:p-5">
          <div class="relative">
            <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
            <input
              v-model="searchQuery"
              type="text"
              aria-label="Cari arsip perbaikan"
              placeholder="Cari Tiket, Judul, Pelapor, atau Lokasi..."
              class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-cyan-500/60 focus:outline-none focus:ring-2 focus:ring-cyan-500/15"
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

          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <!-- Filter kategori -->
            <div class="relative w-full sm:w-60">
              <Building2 class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
              <select
                v-model="selectedCategory"
                aria-label="Filter kategori"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-cyan-500/60 focus:outline-none focus:ring-2 focus:ring-cyan-500/15"
              >
                <option value="ALL">Semua Kategori</option>
                <option v-for="cat in categories.filter(c => c !== 'ALL')" :key="cat" :value="cat" class="bg-slate-900 text-slate-100">{{ cat }}</option>
              </select>
              <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
            </div>

            <!-- Pil status -->
            <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
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

              <p class="ml-auto whitespace-nowrap pl-2 text-[11px] text-slate-500 sm:ml-0">
                <span class="font-semibold tabular-nums text-slate-300">{{ reportRows.length }}</span> dari {{ totalArchived }} arsip
              </p>

              <button
                v-if="hasActiveFilters"
                type="button"
                @click="clearFilters"
                class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-[11px] font-semibold text-slate-400 transition-all duration-150 hover:border-cyan-500/40 hover:text-cyan-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/50"
              >
                Atur ulang
                <X class="h-3 w-3" />
              </button>
            </div>
          </div>
        </div>

        <!-- Label kolom (desktop lebar) -->
        <div class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.5fr)_150px_100px_170px_150px_180px] xl:gap-x-4">
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Kerusakan &amp; Penanganan</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Pelapor</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tingkat</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Lokasi</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Penyelesaian</p>
          <p class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</p>
        </div>

        <!-- Baris arsip -->
        <div v-if="reportRows.length > 0" class="divide-y divide-slate-800/70">
          <article
            v-for="(item, i) in reportRows"
            :key="item.id"
            class="card-enter group relative flex cursor-pointer flex-col gap-3 border-l-[3px] px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.5fr)_150px_100px_170px_150px_180px] xl:items-start xl:gap-x-4"
            :class="[getDamageBorderClass(item.damage_level)]"
            :style="{ animationDelay: (i * 60) + 'ms' }"
            @click="goToDetail(item.id)"
          >
            <!-- Kolom kasus -->
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded border border-cyan-500/20 bg-cyan-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-cyan-400">{{ item.ticket_code }}</span>
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="item.statusBadge">
                  <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ item.status }}
                </span>
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold xl:hidden" :class="getDamageLevelBadge(item.damage_level)">Tingkat {{ item.damage_level }}</span>
                <span class="hidden items-center gap-1.5 rounded-full border border-slate-800 bg-slate-950/50 px-2.5 py-0.5 text-[10px] font-medium text-slate-400 sm:inline-flex">
                  {{ item.category }}
                </span>
              </div>

              <h3 class="mt-2 truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white">{{ item.title }}</h3>

              <p class="clamp-2 mt-1.5 flex items-start gap-1.5 text-[11px] leading-relaxed text-slate-500">
                <Wrench class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-600" />
                <span><span class="font-semibold text-slate-400">Catatan akhir:</span> {{ item.technician_note }}</span>
              </p>
            </div>

            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents">
              <!-- Pelapor -->
              <div class="min-w-0">
                <p v-if="item.is_anonymous" class="flex items-center gap-1.5 text-xs font-semibold text-slate-300">
                  <UserCheck class="h-3.5 w-3.5 shrink-0 text-slate-500" />
                  Anonim
                </p>
                <template v-else>
                  <p class="truncate text-xs font-semibold text-slate-200">{{ item.reporter_name }}</p>
                </template>
                <p v-if="item.is_anonymous" class="mt-0.5 truncate text-[10px] text-slate-600">Identitas dilindungi</p>
              </div>

              <!-- Tingkat -->
              <div class="flex items-center gap-2">
                <div class="flex items-end gap-[3px]" aria-hidden="true">
                  <span class="h-1.5 w-[3px] rounded-[1px]" :class="item.damage.level >= 1 ? item.damage.bar : 'bg-slate-700'"></span>
                  <span class="h-2 w-[3px] rounded-[1px]" :class="item.damage.level >= 2 ? item.damage.bar : 'bg-slate-700'"></span>
                  <span class="h-2.5 w-[3px] rounded-[1px]" :class="item.damage.level >= 3 ? item.damage.bar : 'bg-slate-700'"></span>
                </div>
                <span class="text-[11px] font-medium" :class="item.damage.text">{{ item.damage.label }}</span>
              </div>

              <!-- Lokasi -->
              <div class="min-w-0">
                <p class="flex items-center gap-1.5 text-xs text-slate-300">
                  <MapPin class="h-3.5 w-3.5 shrink-0 text-slate-500" />
                  <span class="truncate">{{ item.location }}</span>
                </p>
              </div>

              <!-- Penyelesaian -->
              <div class="min-w-0">
                <p class="flex items-center gap-1.5 font-mono text-xs text-slate-400">
                  <Check class="h-3.5 w-3.5 shrink-0" :class="item.status === 'Selesai' ? 'text-emerald-500/80' : 'text-slate-600'" />
                  {{ formatDate(item.resolved_at) }}
                </p>
                <p class="mt-0.5 pl-5 text-[10px] text-slate-600">{{ item.status === 'Selesai' ? 'Tuntas' : 'Ditutup' }} · {{ formatTime(item.resolved_at) }}</p>
              </div>
            </div>

            <!-- Aksi -->
            <div class="flex flex-wrap justify-end gap-2">
              <button
                type="button"
                @click.stop="goToDetail(item.id)"
                class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-2 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-slate-600 hover:text-white active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 sm:w-auto"
              >
                Detail
              </button>

              <button
                type="button"
                @click.stop="printReport(item)"
                class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-400 transition-all duration-150 hover:bg-emerald-500 hover:text-slate-950 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 sm:w-auto"
              >
                <Printer class="h-3.5 w-3.5" />
                Cetak BA
              </button>
            </div>
          </article>
        </div>

        <!-- Keadaan kosong -->
        <div v-else class="flex flex-col items-center px-6 py-16 text-center">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
            <Search v-if="hasActiveFilters" class="h-6 w-6" />
            <ArchiveX v-else class="h-6 w-6" />
          </div>
          <p class="mt-4 text-sm font-semibold text-slate-200">
            {{ hasActiveFilters ? 'Arsip tidak ditemukan' : 'Belum ada riwayat perbaikan' }}
          </p>
          <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
            {{ hasActiveFilters
              ? 'Coba gunakan kata kunci lain atau atur ulang filter kategori dan status.'
              : 'Tiket yang telah selesai atau ditolak akan terdokumentasi di sini secara otomatis.' }}
          </p>

          <button
            v-if="hasActiveFilters"
            type="button"
            @click="clearFilters"
            class="mt-6 inline-flex items-center gap-1.5 rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-xs font-semibold text-cyan-400 transition-all duration-200 hover:bg-cyan-500 hover:text-slate-950 active:scale-[.97]"
          >
            Atur Ulang Filter
          </button>
        </div>

        <!-- Kaki daftar -->
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 sm:px-6">
          <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
            <ShieldCheck class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />
            Setiap penyelesaian dapat diterbitkan sebagai Berita Acara resmi untuk arsip sekolah
          </p>
          <p class="whitespace-nowrap text-[11px] text-slate-600">{{ totalArchived }} tiket terdokumentasi</p>
        </div>
      </section>
    </main>

    <!-- ============ Footer ============ -->
    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="text-[11px] text-slate-600">Perbaikan fasilitas didokumentasikan secara transparan</p>
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

.clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .card-enter,
  .stat-bar { animation: none; opacity: 1; }
}
</style>