<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import {
  ArrowLeft, BarChart3, FileText, Users, LogOut, Settings, Calendar,
  Paperclip, MessageSquare, Check, Clock, ShieldCheck,
  Lock, EyeOff, AlertTriangle,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import reportService from '@/services/reportService'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const logoFailed = ref(false)
const isLoading = ref(true)
const isLoadingComments = ref(true)
const imageFailed = ref(false)

const report = ref(null)
const comments = ref([])

const navItems = [
  { label: 'Analitik', to: '/admin/dashboard', icon: BarChart3 },
  { label: 'Semua Laporan', to: '/admin/reports', icon: FileText },
  { label: 'Manajemen User', to: '/admin/users', icon: Users },
  { label: 'Pengaturan', to: '/admin/settings', icon: Settings },
]

const isActive = (item) => route.path.startsWith(item.to)

/* ---------------------------------- */
/* Mapping backend <-> label UI        */
/* ---------------------------------- */
const TYPE_TO_LABEL = { facility: 'Fasilitas', aspiration: 'Aspirasi', bullying: 'Bullying' }
const STATUS_MAP = {
  pending: 'Menunggu', reviewing: 'Ditinjau', in_progress: 'Diproses',
  resolved: 'Selesai', rejected: 'Ditolak',
}

async function loadReport() {
  isLoading.value = true
  try {
    const data = await reportService.getReportDetail(route.params.id)
    const r = data.data ?? data

    report.value = {
      id: r.id,
      report_code: r.report_code,
      title: r.title,
      type: TYPE_TO_LABEL[r.type] ?? r.type,
      status: STATUS_MAP[r.status] ?? r.status,
      priority: r.priority,
      description: r.description,
      created_at: r.created_at,
      resolved_at: r.resolved_at,
      is_anonymous: r.is_anonymous,
      reporter: r.reporter ? { name: r.reporter.name, class_name: r.reporter.class_name } : null,
      assignee: r.assignee ?? null,
      attachments: r.attachments ?? [],
      detail: r.detail ?? null,
      status_logs: (r.status_logs ?? []).map((log) => ({
        id: log.id,
        title: log.old_status ? `Status: ${STATUS_MAP[log.new_status] ?? log.new_status}` : 'Laporan Masuk Sistem',
        note: log.note || 'Tidak ada catatan.',
        time: formatDateTime(log.created_at),
        actor: log.changed_by?.name ?? 'Sistem',
      })),
    }

    await loadComments()
  } catch (error) {
    if (error.response?.status === 403 || error.response?.status === 404) {
      report.value = null
    } else {
      toast.error('Gagal memuat detail laporan.')
    }
  } finally {
    isLoading.value = false
  }
}

async function loadComments() {
  isLoadingComments.value = true
  try {
    const data = await reportService.getComments(route.params.id)
    comments.value = (data.data ?? data).map((c) => ({
      id: c.id,
      text: c.comment,
      authorName: c.author?.name ?? 'Anonim',
      isCounselor: c.author?.role === 'counselor',
      isMine: c.is_mine,
      createdAt: formatDateTime(c.created_at),
    }))
  } catch {
    toast.error('Gagal memuat percakapan.')
  } finally {
    isLoadingComments.value = false
  }
}

onMounted(loadReport)

function formatDateTime(dateString) {
  if (!dateString) return '—'
  return new Date(dateString).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
}

const initialsOf = (name) => {
  if (!name || typeof name !== 'string') return '?'
  const parts = name.trim().split(/\s+/)
  return parts.length > 1
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.slice(0, 2).toUpperCase()
}

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

const priorityMeta = computed(() => {
  const map = {
    urgent: { label: 'Mendesak', level: 4, text: 'text-rose-400', bar: 'bg-rose-500' },
    high: { label: 'Tinggi', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    medium: { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    low: { label: 'Biasa', level: 1, text: 'text-slate-400', bar: 'bg-slate-500' },
  }
  return map[report.value?.priority] || map.low
})

const hasImage = (att) => att.file_type?.startsWith('image/')

const typeAccent = { 'Fasilitas': 'from-cyan-500/70', 'Aspirasi': 'from-purple-500/70', 'Bullying': 'from-amber-500/70' }

/* Detail spesifik per tipe — sesuai field yang ada di skema */
const typeDetailRows = computed(() => {
  if (!report.value?.detail) return []
  if (report.value.type === 'Fasilitas') {
    return [
      { label: 'Lokasi', value: report.value.detail.location },
      { label: 'Kategori', value: report.value.detail.category },
      { label: 'Tingkat Kerusakan', value: report.value.detail.damage_level ?? '—' },
    ]
  }
  if (report.value.type === 'Aspirasi') {
    return [
      { label: 'Kategori', value: report.value.detail.category },
      { label: 'Dukungan', value: `${report.value.detail.upvotes_count ?? 0} suara` },
    ]
  }
  return []
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
              <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Detail Laporan</p>
            </div>
          </div>

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
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- Tombol kembali -->
      <button
  type="button"
  @click="router.push('/admin/reports')"
  class="fade-up group mb-6 inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
>
  <ArrowLeft class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5" />
  <span>Kembali ke Daftar Laporan</span>
</button>

      <!-- Skeleton loading -->
      <div v-if="isLoading" class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]" aria-busy="true">
        <div class="space-y-6">
          <div class="h-64 animate-pulse rounded-xl border border-slate-800 bg-slate-900"></div>
          <div class="h-48 animate-pulse rounded-xl border border-slate-800 bg-slate-900"></div>
        </div>
        <div class="hidden space-y-6 lg:block">
          <div class="h-56 animate-pulse rounded-xl border border-slate-800 bg-slate-900"></div>
        </div>
      </div>

      <!-- Tidak ditemukan / tidak berwenang -->
      <div v-else-if="!report" class="fade-up mx-auto max-w-md rounded-xl border border-slate-800 bg-slate-900 px-6 py-12 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
          <AlertTriangle class="h-6 w-6" />
        </div>
        <p class="mt-4 text-sm font-semibold text-slate-200">Laporan tidak ditemukan</p>
        <p class="mt-1 text-xs leading-relaxed text-slate-500">
          Laporan tidak tersedia, atau tipe laporan ini memerlukan wewenang khusus (misalnya laporan bullying yang hanya dapat diakses Guru BK).
        </p>
        <button
          type="button"
          @click="router.push('/admin/reports')"
          class="mt-6 inline-flex items-center rounded-lg border border-slate-700 bg-slate-800/60 px-4 py-2 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97]"
        >
          Kembali ke Daftar
        </button>
      </div>

      <!-- Konten utama -->
      <div v-else class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="min-w-0 space-y-6">

          <!-- ===== Kartu laporan ===== -->
          <article class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
            <span class="absolute inset-x-0 top-0 h-0.5 bg-linear-to-r to-transparent" :class="typeAccent[report.type] || 'from-emerald-500/70'" aria-hidden="true"></span>

            <div class="space-y-6 p-5 sm:p-6 lg:p-7">
              <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded border border-emerald-500/20 bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-emerald-400">{{ report.report_code }}</span>
                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-medium uppercase" :class="getTypeBadgeClass(report.type)">{{ report.type }}</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="getStatusBadgeClass(report.status)">
                      <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                      {{ report.status }}
                    </span>
                  </div>

                  <span class="inline-flex items-center gap-1 rounded-full border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[10px] font-semibold text-slate-400">
                    <EyeOff class="h-3 w-3" />
                    Akses Baca Saja
                  </span>
                </div>

                <h1 class="text-xl font-extrabold leading-snug tracking-tight text-white sm:text-2xl">{{ report.title }}</h1>
              </div>

              <!-- Pelapor -->
              <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800/70 pt-4">
                <div class="flex min-w-0 items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[11px] font-bold text-emerald-400">
                    <Lock v-if="report.is_anonymous" class="h-4 w-4" />
                    <template v-else>{{ initialsOf(report.reporter?.name) }}</template>
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-xs font-semibold text-slate-100">{{ report.is_anonymous ? 'Pelapor Anonim' : (report.reporter?.name ?? '—') }}</p>
                    <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ report.is_anonymous ? 'Identitas dilindungi' : (report.reporter?.class_name || 'Siswa') }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                  <Calendar class="h-3.5 w-3.5 text-slate-600" />
                  <span>{{ formatDateTime(report.created_at) }}</span>
                </div>
              </div>

              <!-- Detail spesifik tipe -->
              <dl v-if="typeDetailRows.length > 0" class="grid grid-cols-1 gap-3 border-t border-slate-800/70 pt-4 sm:grid-cols-2">
                <div v-for="row in typeDetailRows" :key="row.label" class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
                  <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">{{ row.label }}</dt>
                  <dd class="mt-1 text-xs font-medium text-slate-200">{{ row.value }}</dd>
                </div>
              </dl>

              <!-- Deskripsi -->
              <div class="space-y-3 border-t border-slate-800/70 pt-4">
                <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Deskripsi Laporan</p>
                <p class="text-sm leading-relaxed whitespace-pre-line text-slate-300">{{ report.description }}</p>
              </div>

              <!-- Lampiran -->
              <div v-if="report.attachments.length > 0" class="space-y-3 border-t border-slate-800/70 pt-4">
                <div class="flex items-center gap-2">
                  <Paperclip class="h-3.5 w-3.5 text-slate-600" />
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Lampiran ({{ report.attachments.length }} berkas)</p>
                </div>
                <div class="flex flex-wrap gap-3">
                  <figure
                    v-for="att in report.attachments"
                    :key="att.id"
                    class="w-full max-w-45 overflow-hidden rounded-lg border border-slate-800 bg-slate-950"
                  >
                    <img v-if="hasImage(att)" :src="att.url" alt="Lampiran" loading="lazy" class="h-28 w-full object-cover" />
                    <div v-else class="flex h-28 w-full items-center justify-center bg-slate-900 text-[11px] font-semibold text-slate-500">Berkas PDF</div>
                  </figure>
                </div>
              </div>
            </div>
          </article>

          <!-- ===== Timeline status ===== -->
          <section class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 90ms">
            <div class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4 sm:px-6">
              <div class="flex items-center gap-2.5">
                <Clock class="h-4 w-4 text-emerald-400" />
                <div>
                  <h2 class="text-sm font-bold tracking-tight text-slate-100">Riwayat Status</h2>
                  <p class="mt-0.5 text-[11px] text-slate-500">Jejak audit perubahan status laporan</p>
                </div>
              </div>
              <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400">{{ report.status_logs.length }} Catatan</span>
            </div>

            <ol v-if="report.status_logs.length > 0" class="px-5 py-5 sm:px-6">
              <li v-for="(log, i) in report.status_logs" :key="log.id" class="relative flex gap-3.5" :class="i < report.status_logs.length - 1 ? 'pb-6' : ''">
                <div class="flex flex-col items-center">
                  <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-500 bg-emerald-500 text-slate-950">
                    <Check class="h-3 w-3" />
                  </span>
                  <span v-if="i < report.status_logs.length - 1" class="mt-1 w-px flex-1 bg-emerald-500/40" aria-hidden="true"></span>
                </div>
                <div class="min-w-0 flex-1 pt-0.5">
                  <p class="text-xs font-semibold text-slate-100">{{ log.title }}</p>
                  <p class="mt-0.5 font-mono text-[10px] text-slate-600">{{ log.time }}</p>
                  <p class="mt-1.5 text-xs leading-relaxed text-slate-400">{{ log.note }}</p>
                  <p class="mt-1.5 text-[10px] text-slate-500">Oleh: <span class="font-medium text-slate-400">{{ log.actor }}</span></p>
                </div>
              </li>
            </ol>
            <p v-else class="px-5 py-8 text-center text-xs text-slate-500 sm:px-6">Belum ada riwayat.</p>
          </section>

          <!-- ===== Percakapan (chat) ===== -->
          <section class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 140ms">
            <div class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4 sm:px-6">
              <div class="flex items-center gap-2.5">
                <MessageSquare class="h-4 w-4 text-emerald-400" />
                <div>
                  <h2 class="text-sm font-bold tracking-tight text-slate-100">Percakapan Siswa &amp; Petugas</h2>
                  <p class="mt-0.5 text-[11px] text-slate-500">Komunikasi terkait penanganan laporan ini</p>
                </div>
              </div>
              <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400">{{ comments.length }} Pesan</span>
            </div>

            <div class="max-h-96 space-y-3 overflow-y-auto px-5 py-5 sm:px-6">
              <div v-if="isLoadingComments" class="space-y-3">
                <div v-for="i in 3" :key="i" class="h-14 rounded-lg bg-slate-800/50 animate-pulse"></div>
              </div>

              <p v-else-if="comments.length === 0" class="py-6 text-center text-xs text-slate-500">
                Belum ada percakapan pada laporan ini.
              </p>

              <div v-for="msg in comments" :key="msg.id" class="flex gap-2.5">
                <div
                  class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[9px] font-bold"
                  :class="msg.isCounselor ? 'bg-emerald-500 text-slate-950' : 'border border-slate-600 bg-slate-700 text-slate-200'"
                >
                  {{ initialsOf(msg.authorName) }}
                </div>

                <div class="max-w-[75%] rounded-lg border border-slate-700 bg-slate-800/60 px-3.5 py-2.5">
                  <p class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-300">
                    {{ msg.authorName }}
                    <span
                      v-if="msg.isCounselor"
                      class="inline-flex items-center gap-1 rounded border border-emerald-500/30 bg-emerald-500/15 px-1.5 py-px text-[9px] font-semibold text-emerald-400"
                    >
                      <Check class="h-2.5 w-2.5" />
                      Petugas
                    </span>
                  </p>
                  <p class="mt-1 text-xs leading-relaxed text-slate-200 whitespace-pre-line">{{ msg.text }}</p>
                  <p class="mt-1 text-[9px] text-slate-500">{{ msg.createdAt }}</p>
                </div>
              </div>
            </div>

           <!-- Admin hanya bisa melihat percakapan, tidak dapat mengirim pesan -->
<div class="flex items-center gap-2.5 border-t border-slate-800/70 bg-slate-950/30 px-5 py-3.5 sm:px-6">
  <EyeOff class="h-3.5 w-3.5 shrink-0 text-slate-500" />
  <p class="text-[11px] leading-relaxed text-slate-500">
    Admin hanya dapat memantau percakapan ini. Pengiriman pesan dilakukan oleh siswa dan petugas kanal terkait (Sarpras / BK).
  </p>
</div>
          </section>
        </div>

        <!-- ===== Sidebar info ===== -->
        <aside class="space-y-6 lg:sticky lg:top-20">
          <div class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 100ms">
            <div class="border-b border-slate-800/70 px-5 py-4">
              <h2 class="text-sm font-bold tracking-tight text-slate-100">Informasi Laporan</h2>
            </div>
            <dl class="divide-y divide-slate-800/60">
              <div class="flex items-center justify-between gap-4 px-5 py-3">
                <dt class="shrink-0 text-[11px] font-medium text-slate-500">Prioritas</dt>
                <dd class="flex items-center gap-2">
                  <div class="flex items-end gap-0.75" aria-hidden="true">
                    <span class="h-1.5 w-0.75 rounded-[1px]" :class="priorityMeta.level >= 1 ? priorityMeta.bar : 'bg-slate-700'"></span>
                    <span class="h-2 w-0.75 rounded-[1px]" :class="priorityMeta.level >= 2 ? priorityMeta.bar : 'bg-slate-700'"></span>
                    <span class="h-2.5 w-0.75 rounded-[1px]" :class="priorityMeta.level >= 3 ? priorityMeta.bar : 'bg-slate-700'"></span>
                  </div>
                  <span class="text-xs font-semibold" :class="priorityMeta.text">{{ priorityMeta.label }}</span>
                </dd>
              </div>
              <div class="flex items-center justify-between gap-4 px-5 py-3">
                <dt class="shrink-0 text-[11px] font-medium text-slate-500">Ditugaskan ke</dt>
                <dd class="truncate text-xs text-slate-300">{{ report.assignee?.name ?? 'Belum ditugaskan' }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4 px-5 py-3">
                <dt class="shrink-0 text-[11px] font-medium text-slate-500">Diselesaikan</dt>
                <dd class="truncate text-xs text-slate-300">{{ report.resolved_at ? formatDateTime(report.resolved_at) : '—' }}</dd>
              </div>
            </dl>
          </div>

          <div class="fade-up rounded-xl border border-slate-800 bg-slate-900 p-5" style="animation-delay: 150ms">
            <div class="flex items-start gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-emerald-500/25 bg-emerald-500/10 text-emerald-400">
                <ShieldCheck class="h-4 w-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-100">Akses Terbatas</p>
                <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                  Admin memiliki akses baca dan dapat berkomunikasi, namun perubahan status ditangani oleh petugas kanal terkait (Sarpras / BK).
                </p>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </main>

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

<style scoped>
.fade-up { opacity: 0; animation: fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fade-up { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.no-scrollbar::-webkit-scrollbar { display: none; }
@media (prefers-reduced-motion: reduce) { .fade-up { animation: none; opacity: 1; } }
</style>