<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'
import {
  LogOut,
  History,
  User,
  Check,
  CheckCircle2,
  Building2,
  Wrench,
  ClipboardCheck,
  Search,
  X,
  ChevronDown,
  MapPin,
  Lock,
  ChevronRight,
  ShieldCheck,
  Image as ImageIcon
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const logoFailed = ref(false)

/* Inisial nama staff untuk avatar */
const initials = computed(() => {
  const name = authStore.user?.name || 'Staff Fasilitas'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const firstName = computed(() => (authStore.user?.name || 'Staff Sarpras').trim().split(/\s+/)[0])

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 11) return 'Selamat pagi'
  if (h < 15) return 'Selamat siang'
  if (h < 18) return 'Selamat sore'
  return 'Selamat malam'
})

/* Logout */
const handleLogout = async () => {
  if (authStore.logout) {
    await authStore.logout()
  }
  toast.success('Berhasil keluar dari sistem.')
  router.push({ name: 'login' })
}

/* ---------------------------------- */
/* State filter & pencarian           */
/* ---------------------------------- */
const searchQuery = ref('')
const selectedStatus = ref('ALL')
const selectedCategory = ref('ALL')
const selectedDamageLevel = ref('ALL')

/* Data laporan fasilitas */
const reports = ref([
  {
    id: 101,
    ticket_code: 'FAS-2026-001',
    title: 'AC Ruang Kelas 12 IPA 2 Mati Total',
    category: 'Elektronik & Kelistrikan',
    location: 'Gedung A - Lantai 2 (Kelas 12 IPA 2)',
    damage_level: 'Berat',
    reporter_name: 'Ahmad Rizky (Guru)',
    is_anonymous: false,
    created_at: '2026-09-14 08:30',
    status: 'Menunggu',
    photos_count: 2
  },
  {
    id: 102,
    ticket_code: 'FAS-2026-003',
    title: 'Kran Air Wastafel Toilet Siswa Bocor',
    category: 'Sanitasi & Plambing',
    location: 'Toilet Pria Lantai 1',
    damage_level: 'Sedang',
    reporter_name: 'Anonim',
    is_anonymous: true,
    created_at: '2026-09-13 11:15',
    status: 'Ditinjau',
    photos_count: 1
  },
  {
    id: 103,
    ticket_code: 'FAS-2026-005',
    title: 'Pintu Lab Komputer 1 Rusak / Tidak Bisa Dikunci',
    category: 'Bangunan & Mebel',
    location: 'Gedung B - Lantai 1 (Lab Komputer 1)',
    damage_level: 'Sedang',
    reporter_name: 'Siti Nurhaliza (Siswa)',
    is_anonymous: false,
    created_at: '2026-09-12 14:20',
    status: 'Diproses',
    photos_count: 3
  },
  {
    id: 104,
    ticket_code: 'FAS-2026-008',
    title: 'Proyektor Ruang Audio Visual Redup & Bergaris',
    category: 'Elektronik & Kelistrikan',
    location: 'Gedung Utama - Ruang AV',
    damage_level: 'Ringan',
    reporter_name: 'Budi Santoso (Guru)',
    is_anonymous: false,
    created_at: '2026-09-10 09:45',
    status: 'Selesai',
    photos_count: 1
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
    created_at: '2026-09-09 16:10',
    status: 'Ditolak',
    photos_count: 1
  }
])

/* Opsi filter */
const categories = ['ALL', 'Elektronik & Kelistrikan', 'Sanitasi & Plambing', 'Bangunan & Mebel', 'Fasilitas Olahraga', 'Lainnya']
const damageLevels = ['ALL', 'Ringan', 'Sedang', 'Berat']

/* ---------------------------------- */
/* Filter + urutan                    */
/* ---------------------------------- */
const filteredReports = computed(() => {
  return reports.value.filter(item => {
    const q = searchQuery.value.toLowerCase().trim()
    const matchesSearch = !q ||
                          item.ticket_code.toLowerCase().includes(q) ||
                          item.title.toLowerCase().includes(q) ||
                          item.location.toLowerCase().includes(q) ||
                          String(item.id).includes(q)

    const matchesStatus = selectedStatus.value === 'ALL' || item.status === selectedStatus.value
    const matchesCategory = selectedCategory.value === 'ALL' || item.category === selectedCategory.value
    const matchesLevel = selectedDamageLevel.value === 'ALL' || item.damage_level === selectedDamageLevel.value

    return matchesSearch && matchesStatus && matchesCategory && matchesLevel
  }).sort((a, b) => {
    const levelWeight = { 'Berat': 3, 'Sedang': 2, 'Ringan': 1 }
    return (levelWeight[b.damage_level] || 0) - (levelWeight[a.damage_level] || 0)
  })
})

/* ---------------------------------- */
/* Statistik                          */
/* ---------------------------------- */
const totalPending = computed(() => reports.value.filter(r => r.status === 'Menunggu').length)
const totalInReview = computed(() => reports.value.filter(r => r.status === 'Ditinjau' || r.status === 'Diproses').length)
const totalResolved = computed(() => reports.value.filter(r => r.status === 'Selesai').length)
const totalTickets = computed(() => reports.value.length)
const activeTickets = computed(() => totalPending.value + totalInReview.value)

const activeSummary = computed(() => {
  if (totalPending.value === 0 && totalInReview.value === 0) {
    return 'Tidak ada tiket aktif — seluruh laporan telah ditangani'
  }
  const parts = []
  if (totalPending.value > 0) parts.push(`${totalPending.value} tiket menunggu inspeksi`)
  if (totalInReview.value > 0) parts.push(`${totalInReview.value} perbaikan sedang berjalan`)
  return parts.join(' · ')
})

/* Kartu statistik dengan ikon Lucide Vue Next */
const statCards = computed(() => {
  const total = reports.value.length
  const pct = (n) => (total > 0 ? Math.round((n / total) * 100) : 0)
  return [
    {
      label: 'Total Tiket',
      value: total,
      caption: 'Seluruh laporan kerusakan fasilitas',
      pct: 100,
      icon: Building2,
      num: 'text-white',
      tile: 'border-cyan-500/25 bg-cyan-500/10 text-cyan-400',
      bar: 'bg-cyan-500',
    },
    {
      label: 'Butuh Inspeksi',
      value: totalPending.value,
      caption: 'Laporan baru berstatus Menunggu',
      pct: pct(totalPending.value),
      icon: Search,
      num: 'text-amber-400',
      tile: 'border-amber-500/25 bg-amber-500/10 text-amber-400',
      bar: 'bg-amber-500',
      pulse: true,
    },
    {
      label: 'Proses Perbaikan',
      value: totalInReview.value,
      caption: 'Sedang ditinjau / pengerjaan teknisi',
      pct: pct(totalInReview.value),
      icon: Wrench,
      num: 'text-emerald-400',
      tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
      bar: 'bg-emerald-500',
    },
    {
      label: 'Perbaikan Selesai',
      value: totalResolved.value,
      caption: 'Fasilitas tuntas diperbaiki',
      pct: pct(totalResolved.value),
      icon: CheckCircle2,
      num: 'text-slate-300',
      tile: 'border-slate-600/50 bg-slate-700/30 text-slate-300',
      bar: 'bg-slate-500',
    },
  ]
})

/* Helper badge */
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

const getDamageLevelBadge = (level) => {
  switch (level) {
    case 'Berat': return 'bg-rose-500/10 text-rose-400 border-rose-500/20'
    case 'Sedang': return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    case 'Ringan': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  }
}

const getDamageBorderClass = (level) => {
  switch (level) {
    case 'Berat': return 'border-l-rose-500'
    case 'Sedang': return 'border-l-amber-500'
    case 'Ringan': return 'border-l-emerald-500'
    default: return 'border-l-slate-700'
  }
}

const getDamageMeta = (level) => {
  const map = {
    'Berat': { label: 'Berat', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    'Sedang': { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    'Ringan': { label: 'Ringan', level: 1, text: 'text-emerald-400', bar: 'bg-emerald-500' },
  }
  return map[level] || map['Ringan']
}

/* Pil status */
const statusOptions = [
  { value: 'ALL',      label: 'Semua',     active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Menunggu', label: 'Menunggu',  active: 'border-amber-500/50 bg-amber-500/15 text-amber-400' },
  { value: 'Ditinjau', label: 'Ditinjau',  active: 'border-blue-500/50 bg-blue-500/15 text-blue-400' },
  { value: 'Diproses', label: 'Diproses',  active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Selesai',  label: 'Selesai',   active: 'border-slate-600 bg-slate-700/40 text-slate-200' },
  { value: 'Ditolak',  label: 'Ditolak',   active: 'border-red-500/50 bg-red-500/15 text-red-400' },
]

const pillIdle = 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'

const statusCounts = computed(() => {
  const counts = { ALL: reports.value.length }
  for (const r of reports.value) counts[r.status] = (counts[r.status] || 0) + 1
  return counts
})

const hasActiveFilters = computed(() =>
  searchQuery.value.trim() !== '' || selectedStatus.value !== 'ALL' ||
  selectedCategory.value !== 'ALL' || selectedDamageLevel.value !== 'ALL'
)

const clearFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = 'ALL'
  selectedCategory.value = 'ALL'
  selectedDamageLevel.value = 'ALL'
}

const reportRows = computed(() =>
  filteredReports.value.map(item => ({
    ...item,
    statusBadge: getStatusBadge(item.status),
    damage: getDamageMeta(item.damage_level),
  }))
)

const parseDateTime = (d) => {
  if (!d) return null
  const date = new Date(String(d).replace(' ', 'T'))
  return isNaN(date.getTime()) ? null : date
}

const formatDate = (d) => {
  const date = parseDateTime(d)
  return date ? date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : (d || '—')
}

const formatTime = (d) => {
  const date = parseDateTime(d)
  return date ? date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : ''
}

const goToDetail = (id) => router.push(`/staff/facility-reports/${item => item}`.replace('/item => item/', `${id}`))

const heroPhoto = 'https://picsum.photos/seed/sapasarpras/1600/900.jpg'
const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas ============ -->
    <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">

        <!-- Merek + identitas panel -->
        <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20">
            <img v-if="!logoFailed" src="@/assets/logo/logo sapa.jpeg" alt="Logo SAPA" class="h-full w-full object-cover" @error="logoFailed = true" />
            <span v-else class="text-sm font-extrabold text-emerald-400">S</span>
          </div>
          <div class="hidden min-w-0 sm:block">
            <div class="flex items-center gap-2">
              <p class="text-[15px] font-extrabold leading-none tracking-tight text-white">SAPA</p>
              <span class="rounded border border-cyan-500/30 bg-cyan-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-cyan-400">Staff Sarpras</span>
            </div>
            <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Antrian Laporan Kerusakan Fasilitas</p>
          </div>
        </div>

        <!-- Aksi akun & navigasi -->
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
          <router-link
            to="/staff/facility-history"
            title="Lihat Riwayat Selesai"
            class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <History class="h-4 w-4 text-slate-500 transition-colors duration-200 group-hover:text-slate-300" />
            <span class="hidden sm:inline">Riwayat Fasilitas</span>
            <span class="sm:hidden">Riwayat</span>
          </router-link>

          <div class="hidden h-6 w-px bg-slate-800 md:block"></div>

          <router-link
            to="/staff/profile"
            title="Lihat profil saya"
            class="group hidden items-center gap-2.5 rounded-lg p-1 transition-all hover:bg-slate-900/80 md:flex focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
          >
            <div class="flex h-9 w-9 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[11px] font-bold text-emerald-400 transition-all duration-200 group-hover:border-emerald-500/60 group-hover:bg-emerald-500/20">
              {{ initials }}
            </div>
            <div class="hidden max-w-[150px] text-left leading-tight xl:block">
              <p class="truncate text-xs font-semibold text-slate-200 transition-colors duration-200 group-hover:text-emerald-400">{{ authStore.user?.name || 'Staff Sarpras' }}</p>
              <p class="mt-0.5 truncate text-[10px] text-slate-500">Sarana Prasarana</p>
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

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-10 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- ===== Hero panel sarpras ===== -->
      <section class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <img :src="heroPhoto" alt="" aria-hidden="true" draggable="false"
             class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover opacity-20 grayscale contrast-125 brightness-[.65]" />
        <div class="pointer-events-none absolute inset-0 bg-slate-950/60" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/30 to-transparent" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0" aria-hidden="true"
             style="background: radial-gradient(900px 420px at 10% 0%, rgba(34, 211, 238, 0.12), transparent 65%)"></div>
        <span class="absolute inset-x-0 top-0 z-10 h-[2px] bg-gradient-to-r from-cyan-500/70 via-cyan-500/20 to-transparent" aria-hidden="true"></span>

        <div class="relative z-10 space-y-8 p-6 sm:p-8 lg:p-10">
          <div class="flex flex-col gap-8 lg:grid lg:grid-cols-[minmax(0,1fr)_330px] lg:gap-12">

            <!-- Kolom kiri -->
            <div class="flex flex-col justify-center">
              <div class="flex items-center gap-2.5">
                <span class="relative flex h-2 w-2">
                  <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-60"></span>
                  <span class="relative inline-flex h-2 w-2 rounded-full bg-cyan-500"></span>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-300/90">{{ greeting }}, {{ firstName }}</p>
              </div>

              <h1 class="mt-4 text-3xl font-extrabold leading-[1.08] tracking-tight text-white sm:text-4xl lg:text-[2.5rem]">
                Antrian Perbaikan<br class="hidden sm:block" />
                <span class="text-cyan-400">Fasilitas Sekolah.</span>
              </h1>

              <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-400 sm:text-[15px]">
                Pantau, atur prioritas, dan perbarui status perbaikan sarana prasarana sekolah.
                Laporan otomatis diurutkan berdasarkan tingkat kerusakan.
              </p>

              <div class="mt-6">
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-700/80 bg-slate-950/70 py-1.5 pl-3 pr-4 text-xs text-slate-300 backdrop-blur-sm">
                  <span v-if="totalPending > 0" class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-60"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-400"></span>
                  </span>
                  <span v-else class="h-2 w-2 rounded-full bg-emerald-400" aria-hidden="true"></span>
                  <span class="font-medium">{{ activeSummary }}</span>
                </div>
              </div>
            </div>

            <!-- Kartu petugas -->
            <aside class="rounded-xl border border-slate-800 bg-slate-950/70 p-5 backdrop-blur-sm">
              <div class="flex items-center justify-between gap-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Petugas Sarpras</p>
                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-400">
                  <Check class="h-3 w-3" />
                  Terverifikasi
                </span>
              </div>

              <div class="mt-4 flex items-center gap-3.5">
                <div class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-sm font-bold text-emerald-400">
                  {{ initials }}
                  <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-slate-950 bg-emerald-500" aria-hidden="true"></span>
                </div>
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-white">{{ authStore.user?.name || 'Staff Sarpras' }}</p>
                  <p class="mt-0.5 truncate text-xs text-slate-400">Sarana &amp; Prasarana</p>
                </div>
              </div>

              <div class="mt-5 flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/70 px-3.5 py-3">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tiket Aktif</p>
                  <p class="mt-1 text-lg font-extrabold leading-none tabular-nums text-white">{{ activeTickets }}</p>
                </div>
                <Wrench class="h-5 w-5 text-slate-600" />
              </div>
            </aside>
          </div>

          <!-- Strip kepercayaan penanganan -->
          <div class="grid grid-cols-1 gap-4 border-t border-slate-800/70 pt-5 sm:grid-cols-3 sm:gap-6">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <ShieldCheck class="h-4 w-4 text-emerald-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Prioritas Otomatis</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Kerusakan berat selalu tampil paling atas</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <Wrench class="h-4 w-4 text-emerald-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Koordinasi Teknisi</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Penanganan diteruskan ke tim yang tepat</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <ClipboardCheck class="h-4 w-4 text-emerald-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Dokumentasi Transparan</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Setiap perubahan status tercatat pada sistem</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Statistik perbaikan ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Ringkasan Perbaikan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pantau beban kerja dan progres perbaikan fasilitas sekolah.</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
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

      <!-- ===== Antrian perbaikan ===== -->
      <section class="fade-up" style="animation-delay: 180ms">
        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">

          <!-- Kepala seksi -->
          <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5">
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">Daftar Laporan Kerusakan</h2>
              <p class="mt-0.5 text-xs text-slate-500">Telusuri antrian dan tindak lanjuti setiap laporan fasilitas.</p>
            </div>
            <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400">{{ totalTickets }} Tiket</span>
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
                  aria-label="Cari laporan kerusakan"
                  placeholder="Cari Tiket, ID, Judul Kerusakan, atau Lokasi..."
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

              <!-- Filter kategori -->
              <div class="relative w-full sm:w-56">
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

              <!-- Filter tingkat kerusakan -->
              <div class="relative w-full sm:w-52">
                <Wrench class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <select
                  v-model="selectedDamageLevel"
                  aria-label="Filter tingkat kerusakan"
                  class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-cyan-500/60 focus:outline-none focus:ring-2 focus:ring-cyan-500/15"
                >
                  <option value="ALL">Semua Kerusakan</option>
                  <option v-for="lvl in damageLevels.filter(l => l !== 'ALL')" :key="lvl" :value="lvl" class="bg-slate-900 text-slate-100">Kerusakan {{ lvl }}</option>
                </select>
                <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
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
                  Menampilkan <span class="font-semibold tabular-nums text-slate-300">{{ reportRows.length }}</span> dari {{ totalTickets }} tiket
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

          <!-- Label kolom -->
          <div class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.5fr)_180px_125px_100px_90px_150px] xl:gap-x-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Kerusakan</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Lokasi</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Pelapor</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tingkat</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</p>
            <p class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</p>
          </div>

          <!-- Baris tiket -->
          <div v-if="reportRows.length > 0" class="divide-y divide-slate-800/70">
            <article
              v-for="(item, i) in reportRows"
              :key="item.id"
              class="card-enter group relative flex cursor-pointer flex-col gap-3 border-l-[3px] px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.5fr)_180px_125px_100px_90px_150px] xl:items-center xl:gap-x-4"
              :class="[getDamageBorderClass(item.damage_level)]"
              :style="{ animationDelay: (i * 60) + 'ms' }"
              @click="goToDetail(item.id)"
            >
              <!-- Kolom kerusakan -->
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded border border-cyan-500/20 bg-cyan-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-cyan-400">{{ item.ticket_code }}</span>
                  <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold xl:hidden" :class="getDamageLevelBadge(item.damage_level)">Tingkat {{ item.damage_level }}</span>
                  <span class="hidden items-center gap-1 rounded-full border border-slate-800 bg-slate-950/50 px-2 py-0.5 text-[10px] font-medium text-slate-400 sm:inline-flex">
                    {{ item.category }}
                  </span>
                  <span v-if="item.photos_count" class="inline-flex items-center gap-1 rounded-full border border-slate-800 bg-slate-950/50 px-2 py-0.5 text-[10px] font-medium text-slate-400">
                    <ImageIcon class="h-3 w-3" />
                    {{ item.photos_count }} Foto
                  </span>
                </div>
                <h3 class="mt-2 truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white">{{ item.title }}</h3>
                <p class="mt-0.5 text-[10px] text-slate-600">Dilaporkan {{ formatDate(item.created_at) }} · {{ formatTime(item.created_at) }}</p>
              </div>

              <!-- Meta -->
              <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents">
                <!-- Lokasi -->
                <div class="min-w-0">
                  <p class="flex items-center gap-1.5 text-xs font-medium text-slate-300">
                    <MapPin class="h-3.5 w-3.5 shrink-0 text-slate-500" />
                    <span class="truncate">{{ item.location }}</span>
                  </p>
                </div>

                <!-- Pelapor -->
                <div class="min-w-0">
                  <p v-if="item.is_anonymous" class="flex items-center gap-1.5 text-xs font-semibold text-slate-300">
                    <Lock class="h-3.5 w-3.5 shrink-0 text-slate-500" />
                    Anonim
                  </p>
                  <template v-else>
                    <p class="truncate text-xs font-semibold text-slate-200">{{ item.reporter_name }}</p>
                  </template>
                  <p v-if="item.is_anonymous" class="mt-0.5 truncate text-[10px] text-slate-600">Identitas dilindungi</p>
                </div>

                <!-- Tingkat kerusakan -->
                <div class="flex items-center gap-2">
                  <div class="flex items-end gap-[3px]" aria-hidden="true">
                    <span class="h-1.5 w-[3px] rounded-[1px]" :class="item.damage.level >= 1 ? item.damage.bar : 'bg-slate-700'"></span>
                    <span class="h-2 w-[3px] rounded-[1px]" :class="item.damage.level >= 2 ? item.damage.bar : 'bg-slate-700'"></span>
                    <span class="h-2.5 w-[3px] rounded-[1px]" :class="item.damage.level >= 3 ? item.damage.bar : 'bg-slate-700'"></span>
                  </div>
                  <span class="text-[11px] font-medium" :class="item.damage.text">{{ item.damage.label }}</span>
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
              <div class="flex justify-end">
                <button
                  type="button"
                  @click.stop="goToDetail(item.id)"
                  class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-3.5 py-2 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:bg-emerald-500/10 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 sm:w-auto"
                >
                  Tinjau &amp; Tangani
                  <ChevronRight class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" />
                </button>
              </div>
            </article>
          </div>

          <!-- Keadaan kosong -->
          <div v-else class="flex flex-col items-center px-6 py-16 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
              <Search v-if="hasActiveFilters" class="h-6 w-6" />
              <ShieldCheck v-else class="h-6 w-6" />
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-200">
              {{ hasActiveFilters ? 'Laporan tidak ditemukan' : 'Tidak ada laporan kerusakan' }}
            </p>
            <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
              {{ hasActiveFilters
                ? 'Coba gunakan kata kunci lain atau atur ulang filter status, kategori, dan tingkat kerusakan.'
                : 'Saat ini tidak ada laporan kerusakan fasilitas — seluruh sarana prasarana dalam kondisi baik.' }}
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
              Laporan diurutkan otomatis — kerusakan paling berat selalu ditampilkan lebih dulu
            </p>
            <p class="whitespace-nowrap text-[11px] text-slate-600">{{ totalTickets }} tiket terdaftar</p>
          </div>
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

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .card-enter,
  .stat-bar { animation: none; opacity: 1; }
}
</style>