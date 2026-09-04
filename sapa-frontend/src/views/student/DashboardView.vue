<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

/* ---------------------------------- */
/* Data laporan (behavior existing)   */
/* ---------------------------------- */
const reports = ref([
  {
    id: 1,
    report_code: 'REP-2026-001',
    title: 'AC Ruang Kelas 12 IPA 1 Rusak dan Bising',
    type: 'facility',
    status: 'in_progress',
    priority: 'high',
    created_at: '2026-03-01T08:30:00Z',
  },
  {
    id: 2,
    report_code: 'REP-2026-002',
    title: 'Usulan Penambahan Ekskul Coding & Robotik',
    type: 'aspiration',
    status: 'resolved',
    priority: 'medium',
    created_at: '2026-03-02T10:15:00Z',
  },
  {
    id: 3,
    report_code: 'REP-2026-003',
    title: 'Laporan Kerusakan Lampu Lapangan Basket',
    type: 'facility',
    status: 'resolved',
    priority: 'low',
    created_at: '2026-02-20T14:00:00Z',
  },
  {
    id: 4,
    report_code: 'REP-2026-004',
    title: 'Permintaan Penambahan Meja dan Kursi di Perpustakaan',
    type: 'facility',
    status: 'pending',
    priority: 'low',
    created_at: '2026-02-20T14:00:00Z',
  },
])

const stats = computed(() => {
  return {
    total: reports.value.length,
    pending: reports.value.filter(r => r.status === 'pending').length,
    in_progress: reports.value.filter(r => r.status === 'in_progress' || r.status === 'reviewing').length,
    resolved: reports.value.filter(r => r.status === 'resolved').length,
  }
})

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
    month: 'short',
    year: 'numeric'
  })
}

/* ---------------------------------- */
/* Tambahan: indikator prioritas       */
/* ---------------------------------- */
const getPriority = (priority) => {
  const map = {
    high: { label: 'Tinggi', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    medium: { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    low: { label: 'Rendah', level: 1, text: 'text-slate-400', bar: 'bg-slate-500' },
  }
  return map[priority] || map.low
}

/* Baris laporan dengan badge & prioritas ter-prakomputasi */
const reportRows = computed(() =>
  reports.value.map((r) => ({
    ...r,
    typeBadge: getTypeBadge(r.type),
    statusBadge: getStatusBadge(r.status),
    priority: getPriority(r.priority),
  }))
)

/* ---------------------------------- */
/* Kartu statistik                     */
/* ---------------------------------- */
const statCards = computed(() => {
  const { total, pending, in_progress, resolved } = stats.value
  const pct = (n) => (total > 0 ? Math.round((n / total) * 100) : 0)
  return [
    {
      label: 'Total Laporan',
      value: total,
      caption: 'Seluruh riwayat pelaporan Anda',
      pct: 100,
      icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
      num: 'text-white',
      tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
      bar: 'bg-emerald-500',
    },
    {
      label: 'Menunggu',
      value: pending,
      caption: `${pct(pending)}% dari total laporan`,
      pct: pct(pending),
      icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
      num: 'text-amber-400',
      tile: 'border-amber-500/25 bg-amber-500/10 text-amber-400',
      bar: 'bg-amber-500',
    },
    {
      label: 'Diproses',
      value: in_progress,
      caption: `${pct(in_progress)}% dari total laporan`,
      pct: pct(in_progress),
      icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
      num: 'text-emerald-400',
      tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
      bar: 'bg-emerald-500',
    },
    {
      label: 'Selesai',
      value: resolved,
      caption: `${pct(resolved)}% dari total laporan`,
      pct: pct(resolved),
      icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      num: 'text-slate-300',
      tile: 'border-slate-600/50 bg-slate-700/30 text-slate-300',
      bar: 'bg-slate-500',
    },
  ]
})

/* ---------------------------------- */
/* Konteks personal & kepercayaan      */
/* ---------------------------------- */
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

/* ---------------------------------- */
/* Aksi (behavior existing)            */
/* ---------------------------------- */
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

    <!-- ============ Header ============ -->
    <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">

        <!-- Brand -->
        <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20">
            <img v-if="!logoFailed" src="../../assets/logo sapa.jpeg" alt="Logo SAPA" class="h-full w-full object-cover" @error="logoFailed = true" />
            <span v-else class="text-sm font-extrabold text-emerald-400">S</span>
          </div>
          <div class="hidden min-w-0 sm:block">
            <p class="text-[15px] font-extrabold leading-none tracking-tight text-white">SAPA</p>
            <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
          </div>
        </div>

        <!-- Aksi akun -->
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
          <router-link
    to="/aspirations"
    class="group inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-slate-950 shadow-md shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 hover:shadow-emerald-500/30 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
  >
    <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
    </svg>
    <span>Lihat Aspirasi</span>
  </router-link>

  <router-link
    to="/reports/new"
    class="group inline-flex items-center gap-1 whitespace-nowrap rounded-md bg-emerald-500 px-2.5 py-1.5 text-xs font-semibold text-slate-950 shadow-md shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 hover:shadow-emerald-500/30 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
  >
    <svg class="h-3.5 w-3.5 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
    </svg>
    <span>Buat Laporan</span>
  </router-link>

          <div class="hidden h-6 w-px bg-slate-800 md:block"></div>

          <div class="hidden items-center gap-2.5 md:flex">
            <div class="flex h-9 w-9 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[11px] font-bold text-emerald-400">{{ initials }}</div>
            <div class="hidden max-w-[150px] leading-tight xl:block">
              <p class="truncate text-xs font-semibold text-slate-200">{{ authStore.user?.name || 'Siswa SAPA' }}</p>
              <p class="mt-0.5 truncate text-[10px] text-slate-500">{{ authStore.user?.class_name || 'Kelas —' }}</p>
            </div>
          </div>

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

      <!-- ===== Hero / Sambutan ===== -->
      <section class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <!-- Foto latar: sekolah, digelapkan, desaturasi -->
        <img :src="heroPhoto" alt="" aria-hidden="true" draggable="false"
             class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover opacity-20 grayscale contrast-125 brightness-[.65]" />
        <div class="pointer-events-none absolute inset-0 bg-slate-950/60"></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/30 to-transparent"></div>
        <div class="pointer-events-none absolute inset-0" style="background: radial-gradient(900px 420px at 10% 0%, rgba(16, 185, 129, 0.12), transparent 65%)"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-emerald-500/60 via-emerald-500/10 to-transparent"></div>

        <div class="relative z-10 space-y-8 p-6 sm:p-8 lg:p-10">
          <div class="flex flex-col gap-8 lg:grid lg:grid-cols-[minmax(0,1fr)_330px] lg:gap-12">

            <!-- Kolom kiri -->
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

            <!-- Kartu identitas pelapor -->
            <aside class="rounded-xl border border-slate-800 bg-slate-950/70 p-5 backdrop-blur-sm">
              <div class="flex items-center justify-between gap-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Profil Pelapor</p>
                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-400">
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                  <p class="truncate text-sm font-semibold text-white">{{ authStore.user?.name || 'Siswa SAPA' }}</p>
                  <p class="mt-0.5 truncate text-xs text-slate-400">{{ authStore.user?.class_name || 'Kelas belum diatur' }}</p>
                </div>
              </div>

              <div class="mt-5 flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/70 px-3.5 py-3">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">NISN</p>
                  <p class="mt-1 font-mono text-xs font-semibold tracking-wide text-slate-200">{{ authStore.user?.identity_number || '—' }}</p>
                </div>
                <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                </svg>
              </div>
            </aside>
          </div>

          <!-- Strip kepercayaan -->
          <div class="grid grid-cols-1 gap-4 border-t border-slate-800/70 pt-5 sm:grid-cols-3 sm:gap-6">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Identitas Dilindungi</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Kerahasiaan pelapor dijamin sepenuhnya</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Ditangani Tim Sekolah</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Ditinjau oleh guru &amp; tim SAPA</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Status Terlacak</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Pantau perkembangan setiap laporan</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Aksi cepat ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Kanal Pelaporan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pilih kanal yang sesuai untuk menyampaikan suara Anda.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

          <!-- Aspirasi -->
          <button
            type="button"
            @click="navigateToCreateWithType('aspirasi')"
            class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-500/40 hover:bg-slate-800/60 hover:shadow-lg hover:shadow-black/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/60 active:scale-[.99]"
          >
            <span class="absolute left-0 top-5 bottom-5 w-[3px] rounded-r-full bg-purple-500/70" aria-hidden="true"></span>
            <div class="flex items-start justify-between gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-purple-500/25 bg-purple-500/10 text-purple-400 transition-all duration-200 group-hover:scale-110 group-hover:border-purple-500/40">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
              </div>
              <svg class="mt-1 h-4 w-4 text-slate-600 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </div>
            <h3 class="mt-4 text-sm font-semibold text-white">Kirim Aspirasi</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-slate-400">Sampaikan ide dan usulan untuk kemajuan sekolah.</p>
          </button>

          <!-- Fasilitas -->
          <button
            type="button"
            @click="navigateToCreateWithType('fasilitas')"
            class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-cyan-500/40 hover:bg-slate-800/60 hover:shadow-lg hover:shadow-black/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/60 active:scale-[.99]"
          >
            <span class="absolute left-0 top-5 bottom-5 w-[3px] rounded-r-full bg-cyan-500/70" aria-hidden="true"></span>
            <div class="flex items-start justify-between gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-cyan-500/25 bg-cyan-500/10 text-cyan-400 transition-all duration-200 group-hover:scale-110 group-hover:border-cyan-500/40">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <svg class="mt-1 h-4 w-4 text-slate-600 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-cyan-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </div>
            <h3 class="mt-4 text-sm font-semibold text-white">Lapor Fasilitas</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-slate-400">Laporkan kerusakan atau masalah fasilitas sekolah.</p>
          </button>

          <!-- Perundungan -->
          <button
            type="button"
            @click="navigateToCreateWithType('bullying')"
            class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-rose-500/40 hover:bg-slate-800/60 hover:shadow-lg hover:shadow-black/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/60 active:scale-[.99] sm:col-span-2 lg:col-span-1"
          >
            <span class="absolute left-0 top-5 bottom-5 w-[3px] rounded-r-full bg-rose-500/70" aria-hidden="true"></span>
            <div class="flex items-start justify-between gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-rose-500/25 bg-rose-500/10 text-rose-400 transition-all duration-200 group-hover:scale-110 group-hover:border-rose-500/40">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <svg class="mt-1 h-4 w-4 text-slate-600 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-rose-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </div>
            <h3 class="mt-4 text-sm font-semibold text-white">Pengaduan Bullying</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-slate-400">Laporkan perundungan secara aman dan bertanggung jawab.</p>

            <div class="mt-4 flex items-center gap-2 border-t border-slate-800/70 pt-3">
              <svg class="h-3.5 w-3.5 shrink-0 text-rose-400/80" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <p class="text-[11px] leading-snug text-slate-500">Laporan ditangani secara aman dan bertanggung jawab.</p>
            </div>
          </button>
        </div>
      </section>

      <!-- ===== Statistik ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 180ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Ringkasan Laporan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pantau perkembangan seluruh laporan yang telah Anda kirimkan.</p>
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

      <!-- ===== Riwayat laporan ===== -->
      <section class="fade-up" style="animation-delay: 270ms">
        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">

          <!-- Kepala seksi -->
          <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center gap-3">
              <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
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
              <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
              </svg>
            </button>
          </div>

          <!-- Label kolom (desktop) -->
          <div class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 lg:grid lg:grid-cols-[minmax(0,1fr)_120px_112px_104px_96px] lg:gap-x-4 sm:px-6">
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Laporan</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Prioritas</p>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tanggal</p>
            <p class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</p>
          </div>

          <!-- Daftar laporan -->
          <div v-if="reportRows.length > 0" class="divide-y divide-slate-800/70">
            <article
              v-for="row in reportRows"
              :key="row.id"
              class="group flex flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 lg:grid lg:grid-cols-[minmax(0,1fr)_120px_112px_104px_96px] lg:items-center lg:gap-x-4 lg:gap-y-0 lg:py-4"
            >
              <!-- Kolom utama -->
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded border border-emerald-500/20 bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-emerald-400">{{ row.report_code }}</span>
                  <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="row.typeBadge.class">{{ row.typeBadge.label }}</span>
                </div>
                <h3 class="mt-2 truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white">{{ row.title }}</h3>
              </div>

              <!-- Meta: status, prioritas, tanggal -->
              <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 lg:contents">
                <div>
                  <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="row.statusBadge.class">
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                    {{ row.statusBadge.label }}
                  </span>
                </div>

                <div class="flex items-center gap-2">
                  <div class="flex items-end gap-[3px]" aria-hidden="true">
                    <span class="h-1.5 w-[3px] rounded-[1px]" :class="row.priority.level >= 1 ? row.priority.bar : 'bg-slate-700'"></span>
                    <span class="h-2 w-[3px] rounded-[1px]" :class="row.priority.level >= 2 ? row.priority.bar : 'bg-slate-700'"></span>
                    <span class="h-2.5 w-[3px] rounded-[1px]" :class="row.priority.level >= 3 ? row.priority.bar : 'bg-slate-700'"></span>
                  </div>
                  <span class="text-[11px] font-medium" :class="row.priority.text">{{ row.priority.label }}</span>
                </div>

                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                  <svg class="h-3.5 w-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span>{{ formatDate(row.created_at) }}</span>
                </div>
              </div>

              <!-- Aksi -->
              <div class="mt-1 flex justify-end lg:mt-0">
                <button
                  type="button"
                  @click="router.push(`/reports/${row.id}`)"
                  class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-3.5 py-2 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:bg-emerald-500/10 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 sm:w-auto"
                >
                  Detail
                  <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            </article>
          </div>

          <!-- Keadaan kosong -->
          <div v-else class="flex flex-col items-center px-6 py-16 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-200">Belum ada laporan</p>
            <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">Riwayat laporan Anda akan muncul di sini setelah laporan pertama dikirim.</p>
            <router-link to="/reports/new" class="mt-6 inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97]">
              Buat Laporan Pertama
            </router-link>
          </div>

          <!-- Kaki daftar -->
          <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 sm:px-6">
            <p class="text-[11px] text-slate-500">
              Menampilkan <span class="font-semibold text-slate-400">{{ reports.length }}</span> laporan Anda
            </p>
            <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
              <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              Laporan perundungan dirahasiakan
            </p>
          </div>
        </div>
      </section>
    </main>

    <!-- ============ Footer ============ -->
    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
          <svg class="h-3.5 w-3.5 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          Setiap laporan dijaga kerahasiaannya
        </p>
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
/* Entrance: fade-up halus dengan stagger antar seksi */
.fade-up {
  opacity: 0;
  animation: fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
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

@media (prefers-reduced-motion: reduce) {
  .fade-up { animation: none; opacity: 1; }
  .stat-bar { animation: none; }
}
</style>