<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'
import reportService from '@/services/reportService'

import {
  BarChart3, FileText, Users, LogOut, ArrowRight, ShieldCheck, Lock,
  FileCheck, KeyRound, CheckCircle2, Clock, RefreshCw, ChevronRight,
  Activity, Settings,
} from 'lucide-vue-next'

import {
  Chart as ChartJS, Title, Tooltip, Legend, Filler, LineElement,
  PointElement, CategoryScale, LinearScale, ArcElement,
} from 'chart.js'
import { Line, Doughnut } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, Filler, LineElement, PointElement, CategoryScale, LinearScale, ArcElement)

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const logoFailed = ref(false)
const isLoading = ref(true)

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

const navItems = [
  { label: 'Analitik', to: '/admin/dashboard', icon: BarChart3 },
  { label: 'Semua Laporan', to: '/admin/reports', icon: FileText },
  { label: 'Manajemen User', to: '/admin/users', icon: Users },
  { label: 'Pengaturan', to: '/admin/settings', icon: Settings },
]

const isActive = (item) => route.path === item.to || route.path.startsWith(item.to + '/')

/* ---------------------------------- */
/* Data analitik — sekarang dari API   */
/* ---------------------------------- */
const analytics = ref(null)

async function loadAnalytics() {
  isLoading.value = true
  try {
    analytics.value = await reportService.getAdminAnalytics()
  } catch {
    toast.error('Gagal memuat data analitik.')
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAnalytics)

const stats = computed(() => {
  if (!analytics.value) {
    return { totalReports: 0, resolvedRate: 0, avgResolutionDays: 0, pendingCount: 0 }
  }
  return {
    totalReports: analytics.value.summary.total_reports,
    resolvedRate: analytics.value.summary.resolve_rate,
    avgResolutionDays: analytics.value.summary.avg_resolution_days,
    pendingCount: analytics.value.summary.pending,
  }
})

const activeSummary = computed(() => {
  if (isLoading.value) return 'Memuat ringkasan sistem...'
  const s = stats.value
  return `${s.pendingCount} laporan sedang diproses · ${s.resolvedRate}% tingkat penyelesaian`
})

const statCards = computed(() => {
  const s = stats.value
  const resolvedCount = analytics.value?.summary.resolved ?? 0
  const pendingPct = s.totalReports > 0 ? Math.round((s.pendingCount / s.totalReports) * 100) : 0
  return [
    {
      label: 'Total Semua Laporan', value: s.totalReports, badge: 'Masuk',
      badgeClass: 'border-slate-700/50 bg-slate-800/80 text-slate-300',
      caption: 'Seluruh laporan terdaftar lintas sistem', pct: 100,
      icon: FileText, num: 'text-white', tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', bar: 'bg-emerald-500',
    },
    {
      label: 'Tingkat Penyelesaian', value: s.resolvedRate + '%', badge: 'Resolved Rate',
      badgeClass: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400',
      caption: `${resolvedCount} dari ${s.totalReports} laporan tuntas`, pct: s.resolvedRate,
      icon: CheckCircle2, num: 'text-emerald-400', tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', bar: 'bg-emerald-500',
    },
    {
      label: 'Rata-Rata Waktu Selesai', value: s.avgResolutionDays + ' Hari', badge: 'Per Laporan',
      badgeClass: 'border-slate-700/50 bg-slate-800/80 text-slate-400',
      caption: 'Target internal: maks. 2 hari per laporan', pct: Math.min(Math.round((s.avgResolutionDays / 2) * 100), 100),
      icon: Clock, num: 'text-white', tile: 'border-slate-600/50 bg-slate-700/30 text-slate-300', bar: 'bg-slate-500',
    },
    {
      label: 'Sedang Diproses', value: s.pendingCount, badge: 'Pending',
      badgeClass: 'border-amber-500/20 bg-amber-500/10 text-amber-400',
      caption: `${pendingPct}% dari total laporan aktif`, pct: pendingPct,
      icon: RefreshCw, num: 'text-amber-400', tile: 'border-amber-500/25 bg-amber-500/10 text-amber-400', bar: 'bg-amber-500',
    },
  ]
})

/* ---------------------------------- */
/* Agregat bullying — dari by_status filtered, atau tambahan endpoint stats BK */
/* ---------------------------------- */
const bullyingStats = computed(() => {
  if (!analytics.value) return { total: 0, waiting: 0, inProcess: 0, resolved: 0 }
  // Catatan: ini pendekatan sederhana dari by_type + by_status gabungan (agregat lintas semua tipe).
  // Untuk breakdown status KHUSUS bullying, idealnya backend expose field terpisah —
  // sementara pakai total by_type.bullying sebagai "Total Aduan"
  return {
    total: analytics.value.by_type.bullying,
    waiting: analytics.value.by_status.pending,
    inProcess: analytics.value.by_status.reviewing + analytics.value.by_status.in_progress,
    resolved: analytics.value.by_status.resolved,
  }
})

/* ---------------------------------- */
/* Aktivitas sistem — dari API          */
/* ---------------------------------- */
const typeMeta = {
  'Fasilitas': 'border-cyan-500/20 bg-cyan-500/10 text-cyan-400',
  'Aspirasi': 'border-purple-500/20 bg-purple-500/10 text-purple-400',
  'Perundungan': 'border-amber-500/25 bg-amber-500/10 text-amber-400',
  'Sistem': 'border-slate-700 bg-slate-800/60 text-slate-300',
}

const recentActivities = computed(() => analytics.value?.recent_activities ?? [])

/* ---------------------------------- */
/* Konfigurasi chart — data sekarang dari API */
/* ---------------------------------- */
const CHART_FONT = "'Inter', ui-sans-serif, system-ui, sans-serif"

const distribution = computed(() => {
  if (!analytics.value) return []
  return [
    { label: 'Fasilitas', value: analytics.value.by_type.facility, dot: 'bg-cyan-400' },
    { label: 'Aspirasi', value: analytics.value.by_type.aspiration, dot: 'bg-purple-400' },
    { label: 'Bullying', value: analytics.value.by_type.bullying, dot: 'bg-amber-400', locked: true },
  ]
})

const distributionLegend = computed(() => {
  const total = distribution.value.reduce((acc, d) => acc + d.value, 0)
  return distribution.value.map(d => ({ ...d, pct: total > 0 ? Math.round((d.value / total) * 100) : 0 }))
})

const tooltipStyle = {
  backgroundColor: '#1e293b', borderColor: '#334155', borderWidth: 1,
  titleColor: '#f1f5f9', bodyColor: '#cbd5e1', padding: 12,
  usePointStyle: true, boxWidth: 8, boxPadding: 4,
}

const trendChartData = computed(() => {
  const trend = analytics.value?.monthly_trend
  if (!trend) return { labels: [], datasets: [] }

  return {
    labels: trend.labels,
    datasets: [
      {
        label: 'Fasilitas', data: trend.facility, borderColor: '#22d3ee',
        backgroundColor: 'rgba(34, 211, 238, 0.08)', pointBackgroundColor: '#22d3ee',
        fill: true, tension: 0.4, borderWidth: 2, pointRadius: 3, pointHoverRadius: 5,
      },
      {
        label: 'Aspirasi', data: trend.aspiration, borderColor: '#a855f7',
        backgroundColor: 'rgba(168, 85, 247, 0.08)', pointBackgroundColor: '#a855f7',
        fill: true, tension: 0.4, borderWidth: 2, pointRadius: 3, pointHoverRadius: 5,
      },
      {
        label: 'Bullying (Agregat)', data: trend.bullying, borderColor: '#f59e0b',
        backgroundColor: 'rgba(245, 158, 11, 0.08)', pointBackgroundColor: '#f59e0b',
        fill: true, tension: 0.4, borderWidth: 2, pointRadius: 3, pointHoverRadius: 5,
      },
    ],
  }
})

const trendChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: {
      position: 'top', align: 'end',
      labels: { color: '#94a3b8', usePointStyle: true, pointStyle: 'circle', boxWidth: 6, boxHeight: 6, padding: 16, font: { size: 11, family: CHART_FONT } },
    },
    tooltip: tooltipStyle,
  },
  scales: {
    x: { ticks: { color: '#64748b', font: { size: 11, family: CHART_FONT } }, grid: { color: 'rgba(30, 41, 59, 0.6)' }, border: { display: false } },
    y: { beginAtZero: true, ticks: { color: '#64748b', font: { size: 11, family: CHART_FONT }, precision: 0 }, grid: { color: 'rgba(30, 41, 59, 0.6)' }, border: { display: false } },
  },
}

const distributionChartData = computed(() => ({
  labels: ['Fasilitas', 'Aspirasi', 'Bullying (Agregat)'],
  datasets: [{
    data: distribution.value.map(d => d.value),
    backgroundColor: ['#22d3ee', '#a855f7', '#f59e0b'],
    borderColor: '#0f172a', borderWidth: 3, hoverOffset: 6,
  }],
}))

const distributionChartOptions = {
  responsive: true, maintainAspectRatio: false, cutout: '72%',
  plugins: { legend: { display: false }, tooltip: tooltipStyle },
}

const handleLogout = async () => {
  await authStore.logout()
  toast.success('Berhasil keluar dari sistem.')
  router.push({ name: 'login' })
}

const heroPhoto = 'https://picsum.photos/seed/sapaadmin/1600/900.jpg'
const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 text-slate-100 antialiased selection:bg-emerald-500/25">

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
              <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Pusat Monitoring &amp; Analitik Sistem</p>
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
              <component :is="item.icon" class="h-4 w-4" />
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
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-10 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- ===== Hero panel admin ===== -->
      <section class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <img :src="heroPhoto" alt="" aria-hidden="true" draggable="false"
             class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover opacity-20 grayscale contrast-125 brightness-[.65]" />
        <div class="pointer-events-none absolute inset-0 bg-slate-950/60" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 bg-linear-to-r from-slate-950/80 via-slate-950/30 to-transparent" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0" aria-hidden="true"
             style="background: radial-gradient(900px 420px at 10% 0%, rgba(16, 185, 129, 0.12), transparent 65%)"></div>
        <span class="absolute inset-x-0 top-0 z-10 h-0.5 bg-linear-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

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
                  <ArrowRight class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" />
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
                  <CheckCircle2 class="h-3 w-3" />
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
                <Activity class="h-5 w-5 shrink-0 text-slate-600" />
              </div>
            </aside>
          </div>

          <!-- Strip kepercayaan -->
          <div class="grid grid-cols-1 gap-4 border-t border-slate-800/70 pt-5 sm:grid-cols-3 sm:gap-6">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60 text-emerald-400">
                <Lock class="h-4 w-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Privasi Terjaga</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Data perundungan terkunci khusus Guru BK</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60 text-emerald-400">
                <FileCheck class="h-4 w-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Audit Transparan</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Setiap aksi admin tercatat pada jejak sistem</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-950/60 text-emerald-400">
                <KeyRound class="h-4 w-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-200">Akses Berjenjang</p>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Hak akses dibatasi sesuai peran pengguna</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div v-if="isLoading" class="space-y-10">
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div v-for="i in 4" :key="i" class="h-36 rounded-xl border border-slate-800 bg-slate-900/40 animate-pulse" />
  </div>
  <div class="h-96 rounded-xl border border-slate-800 bg-slate-900/40 animate-pulse" />
</div>

      <template v-else>
        <!-- ===== Statistik utama ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-0.75 rounded-full bg-emerald-500" aria-hidden="true"></span>
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
                <component :is="s.icon" class="h-5 w-5" />
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
          <span class="h-4 w-0.75 rounded-full bg-emerald-500" aria-hidden="true"></span>
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

            <div class="relative mx-auto h-52 w-full max-w-60">
              <Doughnut :data="distributionChartData" :options="distributionChartOptions" />
              <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                <p class="text-2xl font-extrabold leading-none tracking-tight tabular-nums text-white">{{ stats.totalReports }}</p>
                <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-500">Total Laporan</p>
              </div>
            </div>

            <!-- Legenda kustom -->
            <div class="mt-5 space-y-2.5 border-t border-slate-800/60 pt-4">
              <div v-for="l in distributionLegend" :key="l.label" class="flex items-center justify-between gap-3 text-xs">
                <span class="flex min-w-0 items-center gap-2">
                  <span class="h-2 w-2 shrink-0 rounded-full" :class="l.dot" aria-hidden="true"></span>
                  <span class="truncate text-slate-300">{{ l.label }}</span>
                  <Lock v-if="l.locked" class="h-3 w-3 shrink-0 text-amber-400/70" />
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
        <div class="relative overflow-hidden rounded-xl border border-amber-500/30 bg-amber-500/4 p-5 backdrop-blur-sm sm:p-6">
          <div class="mb-4 flex items-center justify-between gap-3 border-b border-amber-500/20 pb-4">
            <div class="flex min-w-0 items-center gap-2">
              <span class="relative flex h-2 w-2 shrink-0">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-60"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-400"></span>
              </span>
              <h3 class="truncate text-sm font-bold tracking-tight text-slate-100">Statistik Agregat Bullying</h3>
            </div>
            <span class="inline-flex shrink-0 items-center gap-1 rounded border border-amber-500/25 bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-400">
              <Lock class="h-3 w-3" />
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
            <Lock class="mt-px h-3.5 w-3.5 shrink-0 text-amber-400/70" />
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
              <ChevronRight class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" />
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
                    <Lock v-if="act.type === 'Perundungan'" class="h-2.5 w-2.5" />
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
              <ShieldCheck class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />
              Seluruh aksi admin tercatat pada jejak audit sistem
            </span>
            <span class="whitespace-nowrap">{{ recentActivities.length }} aktivitas terbaru</span>
          </p>
        </div>
      </section>
      </template>
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