<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  Filler,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  ArcElement
} from 'chart.js'
import { Line, Doughnut } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, Filler, LineElement, PointElement, CategoryScale, LinearScale, ArcElement)

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const logoFailed = ref(false)

/* ---------- Turunan identitas ---------- */
const initials = computed(() => {
  const name = authStore.user?.name || 'Administrator'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const firstName = computed(() => (authStore.user?.name || 'Administrator').trim().split(/\s+/)[0])

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 11) return 'Selamat pagi'
  if (h < 15) return 'Selamat siang'
  if (h < 18) return 'Selamat sore'
  return 'Selamat malam'
})

/* ---------- Navigasi panel ---------- */
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

/* ---------- Data metrik ---------- */
const stats = ref({
  totalReports: 342,
  resolvedRate: 88.5,
  avgResolutionDays: 1.8,
  pendingCount: 14,
})

const activeSummary = computed(() => {
  const s = stats.value
  return `${s.pendingCount} laporan sedang diproses · ${s.resolvedRate}% tingkat penyelesaian`
})

const statCards = computed(() => {
  const s = stats.value
  const resolvedCount = Math.round(s.totalReports * (s.resolvedRate / 100))
  const pendingPct = Math.round((s.pendingCount / s.totalReports) * 100)
  return [
    {
      label: 'Total Semua Laporan',
      value: s.totalReports,
      badge: 'Masuk',
      badgeClass: 'border-slate-700/50 bg-slate-800/80 text-slate-300',
      caption: 'Seluruh laporan terdaftar lintas sistem',
      pct: 100,
      icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
      num: 'text-white',
      tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
      bar: 'bg-emerald-500',
    },
    {
      label: 'Tingkat Penyelesaian',
      value: s.resolvedRate + '%',
      badge: 'Resolved Rate',
      badgeClass: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400',
      caption: `${resolvedCount} dari ${s.totalReports} laporan tuntas`,
      pct: s.resolvedRate,
      icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      num: 'text-emerald-400',
      tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
      bar: 'bg-emerald-500',
    },
    {
      label: 'Rata-Rata Waktu Selesai',
      value: s.avgResolutionDays + ' Hari',
      badge: 'Per Laporan',
      badgeClass: 'border-slate-700/50 bg-slate-800/80 text-slate-400',
      caption: 'Target internal: maks. 2 hari per laporan',
      pct: Math.round((s.avgResolutionDays / 2) * 100),
      icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
      num: 'text-white',
      tile: 'border-slate-600/50 bg-slate-700/30 text-slate-300',
      bar: 'bg-slate-500',
    },
    {
      label: 'Sedang Diproses',
      value: s.pendingCount,
      badge: 'Pending',
      badgeClass: 'border-amber-500/20 bg-amber-500/10 text-amber-400',
      caption: `${pendingPct}% dari total laporan aktif`,
      pct: pendingPct,
      icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
      num: 'text-amber-400',
      tile: 'border-amber-500/25 bg-amber-500/10 text-amber-400',
      bar: 'bg-amber-500',
    },
  ]
})

/* ---------- Agregat bullying (privasi) ---------- */
const bullyingStats = ref({
  total: 24,
  waiting: 3,
  inProcess: 5,
  resolved: 16,
})

/* ---------- Aktivitas sistem ---------- */
const typeMeta = {
  'Fasilitas': 'border-cyan-500/20 bg-cyan-500/10 text-cyan-400',
  'Aspirasi': 'border-purple-500/20 bg-purple-500/10 text-purple-400',
  'Perundungan': 'border-amber-500/25 bg-amber-500/10 text-amber-400',
  'Sistem': 'border-slate-700 bg-slate-800/60 text-slate-300',
}

const recentActivities = ref([
  { id: 1, code: 'LAP-2026-089', type: 'Fasilitas', text: 'Laporan fasilitas (AC Ruang 12) diperbarui menjadi SELESAI', time: '12 menit lalu' },
  { id: 2, code: 'LAP-2026-088', type: 'Aspirasi', text: 'Aspirasi baru masuk: Usulan menu kantin sehat', time: '1 jam lalu' },
  { id: 3, code: 'SYS-AUDIT', type: 'Sistem', text: 'Penambahan user baru dengan role [Staff Sarpras]', time: '3 jam lalu' },
  { id: 4, code: 'LAP-2026-087', type: 'Perundungan', text: 'Aduan perundungan diteruskan ke panel Guru BK — detail terkunci untuk admin', time: '5 jam lalu' },
  { id: 5, code: 'SYS-AUDIT', type: 'Sistem', text: 'Backup basis data harian berhasil dijalankan', time: '6 jam lalu' },
])

/* ---------- Konfigurasi chart ---------- */
const CHART_FONT = "'Inter', ui-sans-serif, system-ui, sans-serif"

const distribution = [
  { label: 'Fasilitas', value: 189, dot: 'bg-cyan-400' },
  { label: 'Aspirasi', value: 129, dot: 'bg-purple-400' },
  { label: 'Bullying', value: 24, dot: 'bg-amber-400', locked: true },
]

const distributionLegend = computed(() => {
  const total = distribution.reduce((acc, d) => acc + d.value, 0)
  return distribution.map(d => ({ ...d, pct: Math.round((d.value / total) * 100) }))
})

const tooltipStyle = {
  backgroundColor: '#1e293b',
  borderColor: '#334155',
  borderWidth: 1,
  titleColor: '#f1f5f9',
  bodyColor: '#cbd5e1',
  padding: 12,
  usePointStyle: true,
  boxWidth: 8,
  boxPadding: 4,
}

const trendChartData = {
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep'],
  datasets: [
    {
      label: 'Fasilitas',
      data: [12, 19, 15, 22, 18, 25, 30, 28, 20],
      borderColor: '#22d3ee',
      backgroundColor: 'rgba(34, 211, 238, 0.08)',
      pointBackgroundColor: '#22d3ee',
      fill: true,
      tension: 0.4,
      borderWidth: 2,
      pointRadius: 3,
      pointHoverRadius: 5,
    },
    {
      label: 'Aspirasi',
      data: [8, 12, 10, 14, 11, 16, 18, 15, 12],
      borderColor: '#a855f7',
      backgroundColor: 'rgba(168, 85, 247, 0.08)',
      pointBackgroundColor: '#a855f7',
      fill: true,
      tension: 0.4,
      borderWidth: 2,
      pointRadius: 3,
      pointHoverRadius: 5,
    },
    {
      label: 'Bullying (Agregat)',
      data: [2, 4, 3, 5, 2, 6, 4, 3, 2],
      borderColor: '#f59e0b',
      backgroundColor: 'rgba(245, 158, 11, 0.08)',
      pointBackgroundColor: '#f59e0b',
      fill: true,
      tension: 0.4,
      borderWidth: 2,
      pointRadius: 3,
      pointHoverRadius: 5,
    },
  ],
}

const trendChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: {
      position: 'top',
      align: 'end',
      labels: {
        color: '#94a3b8',
        usePointStyle: true,
        pointStyle: 'circle',
        boxWidth: 6,
        boxHeight: 6,
        padding: 16,
        font: { size: 11, family: CHART_FONT },
      },
    },
    tooltip: tooltipStyle,
  },
  scales: {
    x: {
      ticks: { color: '#64748b', font: { size: 11, family: CHART_FONT } },
      grid: { color: 'rgba(30, 41, 59, 0.6)' },
      border: { display: false },
    },
    y: {
      beginAtZero: true,
      ticks: { color: '#64748b', font: { size: 11, family: CHART_FONT }, precision: 0 },
      grid: { color: 'rgba(30, 41, 59, 0.6)' },
      border: { display: false },
    },
  },
}

const distributionChartData = {
  labels: ['Fasilitas', 'Aspirasi', 'Bullying (Agregat)'],
  datasets: [
    {
      data: distribution.map(d => d.value),
      backgroundColor: ['#22d3ee', '#a855f7', '#f59e0b'],
      borderColor: '#0f172a',
      borderWidth: 3,
      hoverOffset: 6,
    },
  ],
}

const distributionChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '72%',
  plugins: {
    legend: { display: false },
    tooltip: tooltipStyle,
  },
}

/* ---------- Handlers ---------- */
const handleLogout = async () => {
  if (authStore.logout) {
    await authStore.logout()
  }
  toast.success('Berhasil keluar dari sistem.')
  router.push({ name: 'login' })
}

/* ---------- Aset ---------- */
const heroPhoto = 'https://picsum.photos/seed/sapaadmin/1600/900.jpg'
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
              <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Pusat Monitoring &amp; Analitik Sistem</p>
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
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-10 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- ===== Hero panel admin ===== -->
      <section class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <img :src="heroPhoto" alt="" aria-hidden="true" draggable="false"
             class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover opacity-20 grayscale contrast-125 brightness-[.65]" />
        <div class="pointer-events-none absolute inset-0 bg-slate-950/60" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/30 to-transparent" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0" aria-hidden="true"
             style="background: radial-gradient(900px 420px at 10% 0%, rgba(16, 185, 129, 0.12), transparent 65%)"></div>
        <span class="absolute inset-x-0 top-0 z-10 h-[2px] bg-gradient-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

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

              <h1 class="mt-4 text-3xl font-extrabold leading-[1.1] tracking-tight text-white sm:text-4xl">
                Dashboard Analitik <span class="text-emerald-400">Sistem.</span>
              </h1>

              <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-400 sm:text-[15px]">
                Ringkasan performa dan metrik laporan lintas sistem secara real-time.
              </p>

              <div class="mt-6 flex flex-wrap items-center gap-3">
                <router-link
                  to="/admin/reports"
                  class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                >
                  <span>Kelola Laporan</span>
                  <svg class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                  </svg>
                </router-link>

                <div class="inline-flex items-center gap-2 rounded-full border border-slate-700/80 bg-slate-950/70 py-1.5 pl-3 pr-4 text-xs text-slate-300 backdrop-blur-sm">
                  <span class="h-2 w-2 rounded-full bg-emerald-400" aria-hidden="true"></span>
                  <span class="font-medium">{{ activeSummary }}</span>
                </div>
              </div>
            </div>

            <!-- Kartu administrator -->
            <aside class="rounded-xl border border-slate-800 bg-slate-950/70 p-5 backdrop-blur-sm">
              <div class="flex items-center justify-between gap-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Administrator</p>
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
                  <p class="truncate text-sm font-semibold text-white">{{ authStore.user?.name || 'Administrator' }}</p>
                  <p class="mt-0.5 truncate text-xs text-slate-400">Administrator Sistem</p>
                </div>
              </div>

              <div class="mt-5 flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/70 px-3.5 py-3">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status Sistem</p>
                  <p class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-emerald-400">
                    <span class="relative flex h-2 w-2">
                      <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                      <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                    </span>
                    Operasional
                  </p>
                </div>
                <svg class="h-5 w-5 shrink-0 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
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
                <p class="text-xs font-semibold text-slate-200">Privasi Terjaga</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Data perundungan terkunci khusus Guru BK</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Audit Transparan</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Setiap aksi admin tercatat pada jejak sistem</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Akses Berjenjang</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Hak akses dibatasi sesuai peran pengguna</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Statistik utama ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Ringkasan Sistem</h2>
            <p class="mt-0.5 text-xs text-slate-500">Metrik kinerja utama lintas kanal pelaporan.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="s in statCards"
            :key="s.label"
            class="group rounded-xl border border-slate-800 bg-slate-900/40 p-5 backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-700/80"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-xs font-medium text-slate-400">{{ s.label }}</p>
                <p class="mt-2.5 text-3xl font-extrabold tracking-tight tabular-nums" :class="s.num">{{ s.value }}</p>
              </div>
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border transition-transform duration-200 group-hover:scale-110" :class="s.tile">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" :d="s.icon" />
                </svg>
              </div>
            </div>
            <div class="mt-4 flex items-center justify-between gap-3">
              <div class="h-1 flex-1 overflow-hidden rounded-full bg-slate-800">
                <div class="stat-bar h-full rounded-full" :class="s.bar" :style="{ width: s.pct + '%' }"></div>
              </div>
              <span class="whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[10px] font-semibold" :class="s.badgeClass">{{ s.badge }}</span>
            </div>
            <p class="mt-2 truncate text-[10px] text-slate-500">{{ s.caption }}</p>
          </article>
        </div>
      </section>

      <!-- ===== Analitik ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 180ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Analitik Laporan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Tren dan distribusi laporan sepanjang periode berjalan.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

          <!-- Tren per bulan -->
          <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-5 backdrop-blur-sm sm:p-6 lg:col-span-2">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-slate-800/60 pb-4">
              <div>
                <h3 class="text-sm font-bold tracking-tight text-slate-100">Tren Laporan Per Bulan</h3>
                <p class="mt-0.5 text-[11px] text-slate-500">Perbandingan volume laporan antar tipe sepanjang tahun 2026</p>
              </div>
              <span class="rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1 font-mono text-[11px] font-medium text-slate-400">2026</span>
            </div>
            <div class="relative h-64 w-full sm:h-72">
              <Line :data="trendChartData" :options="trendChartOptions" />
            </div>
          </div>

          <!-- Distribusi tipe -->
          <div class="flex flex-col rounded-xl border border-slate-800 bg-slate-900/40 p-5 backdrop-blur-sm sm:p-6">
            <div class="mb-4 border-b border-slate-800/60 pb-4">
              <h3 class="text-sm font-bold tracking-tight text-slate-100">Distribusi Tipe Laporan</h3>
              <p class="mt-0.5 text-[11px] text-slate-500">Proporsi kategori laporan terdaftar</p>
            </div>

            <div class="relative mx-auto h-52 w-full max-w-[240px]">
              <Doughnut :data="distributionChartData" :options="distributionChartOptions" />
              <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                <p class="text-2xl font-extrabold leading-none tracking-tight tabular-nums text-white">{{ stats.totalReports }}</p>
                <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-500">Total Laporan</p>
              </div>
            </div>

            <!-- Legenda kustom dengan nilai -->
            <div class="mt-5 space-y-2.5 border-t border-slate-800/60 pt-4">
              <div v-for="l in distributionLegend" :key="l.label" class="flex items-center justify-between gap-3 text-xs">
                <span class="flex min-w-0 items-center gap-2">
                  <span class="h-2 w-2 shrink-0 rounded-full" :class="l.dot" aria-hidden="true"></span>
                  <span class="truncate text-slate-300">{{ l.label }}</span>
                  <svg v-if="l.locked" class="h-3 w-3 shrink-0 text-amber-400/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                </span>
                <span class="shrink-0 font-semibold tabular-nums text-slate-400">
                  {{ l.value }} <span class="font-normal text-slate-600">·</span> <span class="font-normal text-slate-500">{{ l.pct }}%</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Privasi bullying + aktivitas sistem ===== -->
      <section class="fade-up grid grid-cols-1 gap-6 lg:grid-cols-3" style="animation-delay: 270ms">

        <!-- Agregat bullying -->
        <div class="relative overflow-hidden rounded-xl border border-amber-500/30 bg-amber-500/[0.04] p-5 backdrop-blur-sm sm:p-6">
          <div class="mb-4 flex items-center justify-between gap-3 border-b border-amber-500/20 pb-4">
            <div class="flex min-w-0 items-center gap-2">
              <span class="relative flex h-2 w-2 shrink-0">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-60"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-400"></span>
              </span>
              <h3 class="truncate text-sm font-bold tracking-tight text-slate-100">Statistik Agregat Bullying</h3>
            </div>
            <span class="inline-flex shrink-0 items-center gap-1 rounded border border-amber-500/25 bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-400">
              <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              BK Exclusive
            </span>
          </div>

          <p class="mb-5 text-xs leading-relaxed text-slate-400">
            Sesuai protokol privasi, data aduan perundungan hanya ditampilkan secara agregat.
            Akses narasi &amp; identitas dibatasi khusus Guru BK.
          </p>

          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg border border-slate-800/80 bg-slate-950/60 p-3.5">
              <span class="text-[11px] font-medium text-slate-400">Total Aduan</span>
              <p class="mt-1 text-xl font-bold tabular-nums text-white">{{ bullyingStats.total }}</p>
            </div>
            <div class="rounded-lg border border-slate-800/80 bg-slate-950/60 p-3.5">
              <span class="text-[11px] font-medium text-slate-400">Menunggu</span>
              <p class="mt-1 text-xl font-bold tabular-nums text-amber-400">{{ bullyingStats.waiting }}</p>
            </div>
            <div class="rounded-lg border border-slate-800/80 bg-slate-950/60 p-3.5">
              <span class="text-[11px] font-medium text-slate-400">Proses BK</span>
              <p class="mt-1 text-xl font-bold tabular-nums text-blue-400">{{ bullyingStats.inProcess }}</p>
            </div>
            <div class="rounded-lg border border-slate-800/80 bg-slate-950/60 p-3.5">
              <span class="text-[11px] font-medium text-slate-400">Selesai</span>
              <p class="mt-1 text-xl font-bold tabular-nums text-emerald-400">{{ bullyingStats.resolved }}</p>
            </div>
          </div>

          <p class="mt-5 flex items-start gap-1.5 border-t border-amber-500/20 pt-3.5 text-[11px] leading-relaxed text-slate-500">
            <svg class="mt-px h-3.5 w-3.5 shrink-0 text-amber-400/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Narasi lengkap, identitas pelapor &amp; korban tidak ditampilkan pada panel admin.
          </p>
        </div>

        <!-- Aktivitas sistem -->
        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/40 backdrop-blur-sm lg:col-span-2">
          <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/70 px-5 py-4 sm:px-6">
            <div>
              <h3 class="text-sm font-bold tracking-tight text-slate-100">Aktivitas Lintas Sistem Terbaru</h3>
              <p class="mt-0.5 text-[11px] text-slate-500">Log perubahan status dan riwayat aksi sistem</p>
            </div>
            <router-link
              to="/admin/reports"
              class="group inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-400 transition-all duration-200 hover:bg-emerald-500 hover:text-slate-950 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
            >
              <span>Lihat Semua</span>
              <svg class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>
          </div>

          <div class="space-y-3 p-5 sm:p-6">
            <div
              v-for="(act, i) in recentActivities"
              :key="act.id"
              class="card-enter flex flex-col gap-2 rounded-lg border border-slate-800/60 bg-slate-950/50 p-3.5 transition-colors duration-150 hover:border-slate-700/80 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
              :style="{ animationDelay: (i * 60) + 'ms' }"
            >
              <div class="min-w-0 space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded border border-slate-700/60 bg-slate-800/60 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate-300">{{ act.code }}</span>
                  <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="typeMeta[act.type]">
                    <svg v-if="act.type === 'Perundungan'" class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    {{ act.type }}
                  </span>
                </div>
                <p class="text-xs font-medium leading-relaxed text-slate-200">{{ act.text }}</p>
              </div>
              <span class="shrink-0 font-mono text-[11px] text-slate-500 sm:pl-4">{{ act.time }}</span>
            </div>
          </div>

          <p class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 text-[11px] text-slate-600 sm:px-6">
            <span class="flex items-center gap-1.5">
              <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              Seluruh aksi admin tercatat pada jejak audit sistem
            </span>
            <span class="whitespace-nowrap">{{ recentActivities.length }} aktivitas terbaru</span>
          </p>
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

/* Entrance item: hanya opacity — interaksi hover tetap bekerja */
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
  .stat-bar { animation: none; opacity: 1; }
}
</style>