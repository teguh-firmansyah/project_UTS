<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isLoading = ref(true)
const isSubmittingComment = ref(false)
const newComment = ref('')

const report = ref(null)

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
    aspirasi: { label: 'Aspirasi', class: 'bg-purple-500/10 text-purple-400 border-purple-500/20' },
    fasilitas: { label: 'Fasilitas', class: 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20' },
    bullying: { label: 'Perundungan', class: 'bg-rose-500/10 text-rose-400 border-rose-500/20' },
    // alias defensif (kunci inggris)
    aspiration: { label: 'Aspirasi', class: 'bg-purple-500/10 text-purple-400 border-purple-500/20' },
    facility: { label: 'Fasilitas', class: 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20' },
  }
  return map[type] || { label: type, class: 'bg-slate-700 text-slate-300' }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

/* ---------------------------------- */
/* Fetch detail (behavior existing)   */
/* ---------------------------------- */
const fetchReportDetail = async () => {
  isLoading.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 600))

    report.value = {
      id: route.params.id,
      report_code: `REP-2026-00${route.params.id}`,
      title: 'AC Ruang Kelas 12 IPA 1 Rusak dan Bising',
      category: 'fasilitas',
      status: 'in_progress',
      content: 'Sudah 3 hari AC di ruang kelas 12 IPA 1 tidak dingin dan mengeluarkan suara bising yang mengganggu konsentrasi belajar saat jam pelajaran berlangsung. Mohon untuk segera diperbaiki oleh tim sarpras sekolah.',
      image_url: 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&w=800&q=80',
      is_anonymous: false,
      created_at: '2026-03-01T08:30:00Z',
      user: {
        name: 'Ahmad Fauzi',
        class_name: '12 IPA 1',
        avatar: null
      },
      comments: [
        {
          id: 101,
          user_name: 'Tim Sarpras Sekolah',
          user_role: 'admin',
          comment: 'Laporan telah kami terima dan diteruskan ke tim teknisi AC. Penanganan dijadwalkan hari ini pukul 13.00 WIB.',
          created_at: '2026-03-01T10:15:00Z'
        },
        {
          id: 102,
          user_name: 'Ahmad Fauzi',
          user_role: 'student',
          comment: 'Baik, terima kasih atas respons cepatnya Pak!',
          created_at: '2026-03-01T10:30:00Z'
        }
      ]
    }
  } catch (error) {
    toast.error('Gagal memuat detail laporan.')
  } finally {
    isLoading.value = false
  }
}

/* ---------------------------------- */
/* Komentar (behavior existing +      */
/* pengaman double-submit)            */
/* ---------------------------------- */
const handleAddComment = async () => {
  if (!newComment.value.trim()) return
  if (isSubmittingComment.value) return

  isSubmittingComment.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 400))

    const createdComment = {
      id: Date.now(),
      user_name: authStore.user?.name || 'Siswa',
      user_role: 'student',
      comment: newComment.value.trim(),
      created_at: new Date().toISOString()
    }

    report.value.comments.push(createdComment)
    newComment.value = ''
    toast.success('Komentar berhasil ditambahkan!')
  } catch (error) {
    toast.error('Gagal mengirim komentar. Silakan coba lagi.')
  } finally {
    isSubmittingComment.value = false
  }
}

const onCommentKeydown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
    e.preventDefault()
    handleAddComment()
  }
}

/* ---------------------------------- */
/* Turunan tampilan                   */
/* ---------------------------------- */
const initialsOf = (name) => {
  const n = (name || '').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/)
  return (parts.length > 1 ? parts[0][0] + parts[parts.length - 1][0] : n.slice(0, 2)).toUpperCase()
}

const imageFailed = ref(false)
const hasImage = computed(() => !!report.value?.image_url && !imageFailed.value)

const isBullying = computed(() => report.value?.category === 'bullying')
const privacyNote = computed(() =>
  isBullying.value
    ? 'Laporan perundungan dirahasiakan dan hanya ditinjau oleh petugas berwenang. Identitas Anda dilindungi sepenuhnya.'
    : 'Identitas pelapor dan isi laporan hanya dapat dilihat oleh petugas berwenang.'
)

const typeBar = {
  aspirasi: 'from-purple-500/70 to-transparent',
  fasilitas: 'from-cyan-500/70 to-transparent',
  bullying: 'from-rose-500/70 to-transparent',
  aspiration: 'from-purple-500/70 to-transparent',
  facility: 'from-cyan-500/70 to-transparent',
}

/* ---------------------------------- */
/* Linimasa status                    */
/* ---------------------------------- */
const WORKFLOW = ['pending', 'reviewing', 'in_progress', 'resolved']
const STEP_META = {
  pending:     { label: 'Menunggu', desc: 'Laporan dalam antrean tindak lanjut petugas' },
  reviewing:   { label: 'Ditinjau',   desc: 'Verifikasi kelengkapan & validitas laporan' },
  in_progress: { label: 'Diproses',   desc: 'Penanganan sedang dilakukan oleh pihak sekolah' },
  resolved:    { label: 'Selesai',    desc: 'Laporan telah ditindaklanjuti sampai tuntas' },
}

const timeline = computed(() => {
  if (!report.value) return []
  const status = report.value.status

  const steps = [
    { key: 'submitted', state: 'done', label: 'Laporan Dikirim', desc: 'Laporan diterima & tercatat pada sistem', date: report.value.created_at },
  ]

  if (status === 'rejected') {
    steps.push({ key: 'rejected', state: 'done', tone: 'danger', label: 'Ditolak', desc: 'Laporan tidak dapat diproses lebih lanjut' })
    return steps
  }

  const currentIdx = WORKFLOW.indexOf(status)
  WORKFLOW.forEach((key, i) => {
    let state = 'todo'
    if (status === 'resolved') state = 'done'
    else if (i < currentIdx) state = 'done'
    else if (i === currentIdx) state = 'active'
    steps.push({ key, state, ...STEP_META[key] })
  })
  return steps
})

/* ---------------------------------- */
/* Meta sidebar                       */
/* ---------------------------------- */
const metaRows = computed(() => {
  if (!report.value) return []
  return [
    { label: 'Kode Laporan', kind: 'code',   value: report.value.report_code },
    { label: 'Kategori',     kind: 'badge',  badge: getTypeBadge(report.value.category) },
    { label: 'Status',       kind: 'badge',  badge: getStatusBadge(report.value.status) },
    { label: 'Dibuat',       kind: 'text',   value: formatDate(report.value.created_at) },
    { label: 'Pelapor',      kind: 'text',   value: report.value.is_anonymous ? 'Anonim' : (report.value.user?.name || '—') },
  ]
})

const currentYear = new Date().getFullYear()

onMounted(() => {
  fetchReportDetail()
})
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas ============ -->
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">

        <button
          type="button"
          @click="router.back()"
          class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
        >
          <svg class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali</span>
        </button>

        <div v-if="report" class="flex min-w-0 items-center gap-2">
          <span class="hidden rounded-md border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 font-mono text-[11px] font-medium text-emerald-400 sm:inline-flex">
            {{ report.report_code }}
          </span>
          <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1 text-[11px] font-medium" :class="getStatusBadge(report.status).class">
            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
            {{ getStatusBadge(report.status).label }}
          </span>
        </div>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- ===== Skeleton pemuatan ===== -->
      <div v-if="isLoading" class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]" aria-busy="true">
        <p class="sr-only">Memuat detail laporan…</p>
        <div class="space-y-6">
          <div class="animate-pulse space-y-5 rounded-xl border border-slate-800 bg-slate-900 p-6">
            <div class="flex gap-2">
              <div class="h-5 w-20 rounded-full bg-slate-800"></div>
              <div class="h-5 w-24 rounded-full bg-slate-800"></div>
            </div>
            <div class="h-6 w-3/4 rounded bg-slate-800"></div>
            <div class="flex items-center gap-3 border-t border-slate-800 pt-4">
              <div class="h-9 w-9 rounded-full bg-slate-800"></div>
              <div class="space-y-1.5">
                <div class="h-3 w-28 rounded bg-slate-800"></div>
                <div class="h-2.5 w-16 rounded bg-slate-800"></div>
              </div>
            </div>
            <div class="space-y-2 border-t border-slate-800 pt-4">
              <div class="h-3 w-full rounded bg-slate-800"></div>
              <div class="h-3 w-11/12 rounded bg-slate-800"></div>
              <div class="h-3 w-4/5 rounded bg-slate-800"></div>
            </div>
            <div class="h-48 w-full max-w-lg rounded-lg bg-slate-800"></div>
          </div>
          <div class="h-56 animate-pulse rounded-xl border border-slate-800 bg-slate-900"></div>
        </div>
        <div class="hidden space-y-6 lg:block">
          <div class="h-80 animate-pulse rounded-xl border border-slate-800 bg-slate-900"></div>
          <div class="h-44 animate-pulse rounded-xl border border-slate-800 bg-slate-900"></div>
        </div>
      </div>

      <!-- ===== Gagal dimuat ===== -->
      <div v-else-if="!report" class="fade-up mx-auto max-w-md rounded-xl border border-slate-800 bg-slate-900 px-6 py-12 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
          </svg>
        </div>
        <p class="mt-4 text-sm font-semibold text-slate-200">Laporan tidak dapat dimuat</p>
        <p class="mt-1 text-xs leading-relaxed text-slate-500">Terjadi kendala saat memuat detail laporan. Silakan coba beberapa saat lagi.</p>
        <div class="mt-6 flex items-center justify-center gap-3">
          <button
            type="button"
            @click="fetchReportDetail"
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97]"
          >
            Coba Lagi
          </button>
          <button
            type="button"
            @click="router.push('/reports')"
            class="inline-flex items-center rounded-lg border border-slate-700 bg-slate-800/60 px-4 py-2 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97]"
          >
            Kembali ke Daftar
          </button>
        </div>
      </div>

      <!-- ===== Detail laporan ===== -->
      <div v-else class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">

        <!-- Kolom utama -->
        <div class="min-w-0 space-y-6">

          <!-- Kartu laporan -->
          <article class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
            <span class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r" :class="typeBar[report.category] || 'from-emerald-500/70 to-transparent'" aria-hidden="true"></span>

            <div class="space-y-6 p-5 sm:p-6 lg:p-7">
              <!-- Lencana + judul -->
              <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="getTypeBadge(report.category).class">{{ getTypeBadge(report.category).label }}</span>
                  <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="getStatusBadge(report.status).class">
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                    {{ getStatusBadge(report.status).label }}
                  </span>
                </div>
                <h1 class="text-xl font-extrabold leading-snug tracking-tight text-white sm:text-2xl">{{ report.title }}</h1>
              </div>

              <!-- Pelapor + waktu -->
              <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800/70 pt-4">
                <div class="flex min-w-0 items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[11px] font-bold text-emerald-400">
                    <svg v-if="report.is_anonymous" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <template v-else>{{ initialsOf(report.user?.name) }}</template>
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-xs font-semibold text-slate-100">{{ report.is_anonymous ? 'Pelapor Anonim' : report.user?.name }}</p>
                    <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ report.is_anonymous ? 'Identitas pelapor dilindungi' : (report.user?.class_name || 'Siswa') }}</p>
                  </div>
                </div>

                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                  <svg class="h-3.5 w-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span>{{ formatDate(report.created_at) }}</span>
                </div>
              </div>

              <!-- Deskripsi -->
              <div class="space-y-3 border-t border-slate-800/70 pt-4">
                <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Deskripsi Laporan</p>
                <p class="text-sm leading-relaxed whitespace-pre-line text-slate-300">{{ report.content }}</p>
              </div>

              <!-- Lampiran -->
              <div v-if="hasImage" class="space-y-3 border-t border-slate-800/70 pt-4">
                <div class="flex items-center gap-2">
                  <svg class="h-3.5 w-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                  </svg>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Lampiran Bukti Foto</p>
                </div>
                <figure class="group relative max-w-lg overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                  <img
                    :src="report.image_url"
                    alt="Bukti laporan"
                    loading="lazy"
                    class="h-auto max-h-80 w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                    @error="imageFailed = true"
                  />
                </figure>
              </div>
            </div>
          </article>

          <!-- Diskusi -->
          <section class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 90ms">
            <div class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4 sm:px-6">
              <div class="flex items-center gap-2.5">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <h2 class="text-sm font-bold tracking-tight text-slate-100">Tanggapan &amp; Diskusi</h2>
              </div>
              <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400">{{ report.comments.length }} Tanggapan</span>
            </div>

            <!-- Daftar komentar -->
            <div class="space-y-3.5 p-5 sm:p-6">
              <div v-if="report.comments.length === 0" class="flex flex-col items-center py-8 text-center">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </div>
                <p class="mt-3 text-sm font-semibold text-slate-200">Belum ada tanggapan</p>
                <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">Pihak sekolah dan Anda dapat berdiskusi langsung pada laporan ini.</p>
              </div>

              <article
                v-for="(item, idx) in report.comments"
                :key="item.id"
                class="comment-enter relative rounded-lg border p-4"
                :class="item.user_role === 'admin' ? 'border-emerald-500/25 bg-emerald-500/[0.04]' : 'border-slate-800 bg-slate-950/40'"
                :style="{ animationDelay: (idx * 60) + 'ms' }"
              >
                <span v-if="item.user_role === 'admin'" class="absolute bottom-3 left-0 top-3 w-[2px] rounded-r-full bg-emerald-500/60" aria-hidden="true"></span>

                <div class="flex items-start justify-between gap-3">
                  <div class="flex min-w-0 items-center gap-2.5">
                    <div
                      class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                      :class="item.user_role === 'admin' ? 'bg-emerald-500 text-slate-950' : 'border border-slate-600 bg-slate-700 text-slate-200'"
                    >
                      {{ initialsOf(item.user_name) }}
                    </div>
                    <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                      <p class="truncate text-xs font-semibold text-slate-100">{{ item.user_name }}</p>
                      <span
                        v-if="item.user_role === 'admin'"
                        class="inline-flex items-center gap-1 rounded border border-emerald-500/30 bg-emerald-500/15 px-1.5 py-px text-[10px] font-semibold text-emerald-400"
                      >
                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                        </svg>
                        Petugas
                      </span>
                    </div>
                  </div>
                  <p class="shrink-0 text-[10px] text-slate-500">{{ formatDate(item.created_at) }}</p>
                </div>

                <p class="mt-2.5 pl-[34px] text-xs leading-relaxed text-slate-300">{{ item.comment }}</p>
              </article>
            </div>

            <!-- Formulir komentar -->
            <form @submit.prevent="handleAddComment" class="border-t border-slate-800/70 bg-slate-950/30 p-5 sm:p-6">
              <div class="flex gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[10px] font-bold text-emerald-400">
                  {{ initialsOf(authStore.user?.name) }}
                </div>

                <div class="min-w-0 flex-1 space-y-3">
                  <textarea
                    v-model="newComment"
                    rows="3"
                    placeholder="Tulis pesan atau tanggapan terkait laporan ini..."
                    @keydown="onCommentKeydown"
                    class="w-full resize-none rounded-lg border border-slate-800 bg-slate-950/60 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  ></textarea>

                  <div class="flex items-center justify-between gap-3">
                    <p class="hidden text-[10px] text-slate-600 sm:block">Ctrl + Enter untuk mengirim</p>
                    <button
                      type="submit"
                      :disabled="isSubmittingComment || !newComment.trim()"
                      class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                    >
                      <svg v-if="!isSubmittingComment" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                      </svg>
                      <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                      </svg>
                      {{ isSubmittingComment ? 'Mengirim...' : 'Kirim Tanggapan' }}
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </section>
        </div>

        <!-- Sidebar -->
        <aside class="space-y-6 lg:sticky lg:top-20">

          <!-- Linimasa status -->
          <div class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 140ms">
            <div class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4">
              <h2 class="text-sm font-bold tracking-tight text-slate-100">Status Laporan</h2>
              <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2 py-0.5 text-[10px] font-medium" :class="getStatusBadge(report.status).class">
                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                {{ getStatusBadge(report.status).label }}
              </span>
            </div>

            <ol class="px-5 py-5">
              <li
                v-for="(step, i) in timeline"
                :key="step.key"
                class="relative flex gap-3.5"
                :class="i < timeline.length - 1 ? 'pb-6' : ''"
              >
                <!-- Rel & titik -->
                <div class="flex flex-col items-center">
                  <span
                    class="relative flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                    :class="step.tone === 'danger'
                      ? 'border-rose-500 bg-rose-500 text-slate-950'
                      : step.state === 'done'
                        ? 'border-emerald-500 bg-emerald-500 text-slate-950'
                        : step.state === 'active'
                          ? 'border-emerald-400 bg-emerald-500/20 text-emerald-400'
                          : 'border-slate-700 bg-slate-900 text-slate-600'"
                  >
                    <template v-if="step.state === 'done'">
                      <svg v-if="step.tone === 'danger'" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      <svg v-else class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                    </template>

                    <template v-else-if="step.state === 'active'">
                      <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-40" aria-hidden="true"></span>
                      <span class="relative h-1.5 w-1.5 rounded-full bg-emerald-400" aria-hidden="true"></span>
                    </template>

                    <span v-else class="h-1 w-1 rounded-full bg-slate-600" aria-hidden="true"></span>
                  </span>

                  <span
                    v-if="i < timeline.length - 1"
                    class="mt-1 w-px flex-1"
                    :class="step.state === 'done' ? 'bg-emerald-500/40' : 'bg-slate-800'"
                    aria-hidden="true"
                  ></span>
                </div>

                <!-- Konten tahap -->
                <div class="min-w-0 flex-1 pt-0.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold" :class="step.state === 'todo' ? 'text-slate-500' : step.tone === 'danger' ? 'text-rose-400' : 'text-slate-100'">{{ step.label }}</p>
                    <span
                      v-if="step.state === 'active'"
                      class="inline-flex items-center gap-1 rounded border border-emerald-500/30 bg-emerald-500/15 px-1.5 py-px text-[10px] font-semibold text-emerald-400"
                    >
                      <span class="h-1 w-1 animate-pulse rounded-full bg-emerald-400" aria-hidden="true"></span>
                      Berjalan
                    </span>
                  </div>
                  <p class="mt-0.5 text-[11px] leading-snug text-slate-500">{{ step.desc }}</p>
                  <p v-if="step.date" class="mt-1 text-[10px] text-slate-600">{{ formatDate(step.date) }}</p>
                </div>
              </li>
            </ol>

            <p class="flex items-center gap-1.5 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 text-[11px] text-slate-600">
              <svg class="h-3.5 w-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Status diperbarui oleh petugas sekolah
            </p>
          </div>

          <!-- Informasi laporan -->
          <div class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 190ms">
            <div class="border-b border-slate-800/70 px-5 py-4">
              <h2 class="text-sm font-bold tracking-tight text-slate-100">Informasi Laporan</h2>
            </div>
            <dl class="divide-y divide-slate-800/60">
              <div v-for="row in metaRows" :key="row.label" class="flex items-center justify-between gap-4 px-5 py-3">
                <dt class="shrink-0 text-[11px] font-medium text-slate-500">{{ row.label }}</dt>
                <dd class="min-w-0 text-right">
                  <span v-if="row.kind === 'code'" class="rounded border border-emerald-500/20 bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-emerald-400">{{ row.value }}</span>
                  <span v-else-if="row.kind === 'badge'" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="row.badge.class">
                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                    {{ row.badge.label }}
                  </span>
                  <span v-else class="truncate text-xs text-slate-300">{{ row.value }}</span>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Kartu kepercayaan -->
          <div class="fade-up rounded-xl border border-slate-800 bg-slate-900 p-5" style="animation-delay: 240ms">
            <div class="flex items-start gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-emerald-500/25 bg-emerald-500/10 text-emerald-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-100">Kerahasiaan Terjamin</p>
                <p class="mt-1 text-[11px] leading-relaxed text-slate-500">{{ privacyNote }}</p>
              </div>
            </div>
          </div>
        </aside>
      </div>
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

/* Komentar masuk satu per satu */
.comment-enter {
  opacity: 0;
  animation: comment-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes comment-in {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .comment-enter { animation: none; opacity: 1; }
}
</style>