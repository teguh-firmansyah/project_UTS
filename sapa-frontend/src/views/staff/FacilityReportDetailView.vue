<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const logoFailed = ref(false)
const isSubmitting = ref(false)

/* ---------------------------------- */
/* Navigasi (behavior existing)       */
/* ---------------------------------- */
const goBack = () => {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/staff/facility-queue')
  }
}

const handleLogout = async () => {
  if (authStore.logout) {
    await authStore.logout()
  }
  toast.success('Berhasil keluar dari sistem.')
  router.push({ name: 'login' })
}

/* ---------------------------------- */
/* Data detail laporan (existing)     */
/* ---------------------------------- */
const report = ref({
  id: route.params.id || 101,
  ticket_code: 'FAS-2026-001',
  title: 'AC Ruang Kelas 12 IPA 2 Mati Total',
  category: 'Elektronik & Kelistrikan',
  location: 'Gedung A - Lantai 2 (Kelas 12 IPA 2)',
  damage_level: 'Berat',
  reporter_name: 'Ahmad Rizky (Guru)',
  is_anonymous: false,
  description: 'AC di kelas 12 IPA 2 tiba-tiba mati saat KBM berlangsung dan mengeluarkan bau hangus tipis. Remote tidak merespon dan MCB tidak terputus.',
  created_at: '2026-09-14 08:30',
  status: 'Menunggu',
  attachments: [
    { id: 1, url: 'https://placehold.co/600x400/1e293b/e2e8f0?text=Foto+AC+Mati' },
    { id: 2, url: 'https://placehold.co/600x400/1e293b/e2e8f0?text=Foto+Remote' }
  ],
  logs: [
    { id: 1, status: 'Menunggu', note: 'Laporan telah diterima dalam antrian.', created_at: '2026-09-14 08:30' }
  ]
})

/* ---------------------------------- */
/* Form update status (existing)      */
/* ---------------------------------- */
const form = ref({
  status: 'Diproses',
  note: ''
})

const formErrors = ref({})

watch(() => form.value.note, () => { if (formErrors.value.note) formErrors.value.note = '' })

const notesLength = computed(() => (form.value.note || '').length)

/* ---------------------------------- */
/* Update status (behavior existing,  */
/* alert diganti toast + error inline)*/
/* ---------------------------------- */
const handleUpdateStatus = async () => {
  if (!form.value.note.trim()) {
    formErrors.value.note = 'Catatan perkembangan wajib diisi.'
    toast.error('Harap isi catatan perkembangan/penanganan.')
    return
  }

  isSubmitting.value = true

  // Simulasi Request API (logika existing dipertahankan)
  setTimeout(() => {
    report.value.status = form.value.status
    report.value.logs.unshift({
      id: Date.now(),
      status: form.value.status,
      note: form.value.note,
      created_at: new Date().toISOString().replace('T', ' ').substring(0, 16)
    })

    form.value.note = ''
    formErrors.value = {}
    isSubmitting.value = false

    toast.success('Status perbaikan berhasil diperbarui!', {
      description: `Tiket ${report.value.ticket_code} kini berstatus: ${report.value.status}.`
    })
  }, 600)
}

/* ---------------------------------- */
/* Helper badge — warna diselaraskan  */
/* sistem (Menunggu=amber, Ditinjau=  */
/* blue, Diproses=emerald, Selesai=   */
/* slate, Ditolak=red)                */
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

/* Badge tingkat kerusakan (skala signifikansi sistem) */
const getDamageLevelBadge = (level) => {
  switch (level) {
    case 'Berat': return 'bg-rose-500/10 text-rose-400 border-rose-500/20'
    case 'Sedang': return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    case 'Ringan': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20'
  }
}

/* Border indikator tingkat kerusakan (selalu tampil) */
const getDamageBorderClass = (level) => {
  switch (level) {
    case 'Berat': return 'border-l-rose-500'
    case 'Sedang': return 'border-l-amber-500'
    case 'Ringan': return 'border-l-emerald-500'
    default: return 'border-l-slate-700'
  }
}

/* Bar sinyal tingkat kerusakan */
const getDamageMeta = (level) => {
  const map = {
    'Berat': { label: 'Berat', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    'Sedang': { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    'Ringan': { label: 'Ringan', level: 1, text: 'text-emerald-400', bar: 'bg-emerald-500' },
  }
  return map[level] || map['Ringan']
}

const damage = computed(() => getDamageMeta(report.value.damage_level))

/* Opsi status — kartu radio semantik, nilai identik dengan select asli */
const statusOptions = [
  { value: 'Ditinjau', label: 'Ditinjau — Inspeksi Lapangan', desc: 'Verifikasi lokasi & kondisi kerusakan', dot: 'bg-blue-400', active: 'border-blue-500/50 bg-blue-500/[0.06] text-blue-400' },
  { value: 'Diproses', label: 'Diproses — Perbaikan Berlangsung', desc: 'Teknisi sedang mengerjakan perbaikan', dot: 'bg-emerald-400', active: 'border-emerald-500/50 bg-emerald-500/[0.06] text-emerald-400' },
  { value: 'Selesai', label: 'Selesai — Fasilitas Normal', desc: 'Perbaikan tuntas & terverifikasi', dot: 'bg-slate-300', active: 'border-slate-500/60 bg-slate-500/10 text-slate-300' },
  { value: 'Ditolak', label: 'Ditolak — Bukan Wewenang / Invalid', desc: 'Di luar lingkup sarpras / tidak valid', dot: 'bg-red-400', active: 'border-red-500/50 bg-red-500/[0.06] text-red-400' },
]

/* Metadata kasus — data yang sudah ada, kini ditampilkan */
const parseDateTime = (d) => {
  if (!d) return null
  const date = new Date(String(d).replace(' ', 'T'))
  return isNaN(date.getTime()) ? null : date
}

const caseMeta = computed(() => [
  { label: 'Kategori', value: report.value.category, icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
  { label: 'Lokasi Kerusakan', value: report.value.location, icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' },
  { label: 'Waktu Pelaporan', value: formatDateTime(report.value.created_at), icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
  { label: 'Pelapor', value: report.value.is_anonymous ? 'Anonim' : report.value.reporter_name, icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
])

function formatDateTime(d) {
  const date = parseDateTime(d)
  return date ? date.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : (d || '—')
}

/* ---------------------------------- */
/* Modal perbesar lampiran            */
/* ---------------------------------- */
const previewImage = ref(null)

const openPreview = (img) => { previewImage.value = img }
const closePreview = () => { previewImage.value = null }

const handleEscKey = (e) => {
  if (e.key === 'Escape' && previewImage.value) closePreview()
}

watch(previewImage, (v) => {
  document.body.style.overflow = v ? 'hidden' : ''
})

onMounted(() => window.addEventListener('keydown', handleEscKey))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscKey)
  document.body.style.overflow = ''
})

/* Turunan tampilan */
const printDate = new Date().toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' })
const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas (tidak tercetak) ============ -->
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md print:hidden">
      <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">

        <!-- Kembali + merek panel -->
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
                <span class="rounded border border-cyan-500/30 bg-cyan-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-cyan-400">Staff Sarpras</span>
              </div>
              <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Detail &amp; Tindak Lanjut Perbaikan</p>
            </div>
          </div>
        </div>

        <!-- Konteks tiket + aksi -->
        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
          <span class="hidden rounded-md border border-cyan-500/20 bg-cyan-500/10 px-2 py-1 font-mono text-[11px] font-medium text-cyan-400 sm:inline-flex">
            {{ report.ticket_code }}
          </span>

          <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1 text-[11px] font-medium" :class="getStatusBadge(report.status)">
            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
            {{ report.status }}
          </span>

          <div class="hidden h-6 w-px bg-slate-800 sm:block"></div>

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
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
      <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">

        <!-- ===== Kolom tiket (dapat dicetak) ===== -->
        <div class="min-w-0 space-y-6">

          <!-- Kepala dokumen cetak -->
          <div class="hidden border-b border-slate-300 pb-4 print:block">
            <div class="flex items-end justify-between gap-4">
              <div>
                <p class="text-lg font-extrabold tracking-tight">SAPA</p>
                <p class="mt-0.5 text-[10px] font-medium uppercase tracking-[0.16em]">Sistem Layanan Aspirasi &amp; Pengaduan Sekolah — Panel Sarpras</p>
              </div>
              <div class="text-right text-[11px] leading-relaxed">
                <p class="font-semibold">Dokumen Tindak Lanjut Perbaikan Fasilitas</p>
                <p class="font-mono">{{ report.ticket_code }} · Status: {{ report.status }}</p>
                <p>Dicetak {{ printDate }}</p>
              </div>
            </div>
          </div>

          <!-- ===== Kartu tiket ===== -->
          <section class="print-card fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/25 border-l-[3px]"
                   :class="getDamageBorderClass(report.damage_level)">
            <span class="absolute inset-x-0 top-0 z-10 h-[2px] bg-gradient-to-r from-cyan-500/70 via-cyan-500/20 to-transparent print:hidden" aria-hidden="true"></span>
            <div class="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-cyan-500/10 blur-3xl print:hidden" aria-hidden="true"></div>

            <div class="relative z-10 space-y-6 p-5 sm:p-6 lg:p-7">

              <!-- Kepala tiket -->
              <div class="flex flex-col gap-4 border-b border-slate-800/70 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded border border-cyan-500/20 bg-cyan-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-cyan-400">{{ report.ticket_code }}</span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-950/50 px-2.5 py-0.5">
                      <span class="flex items-end gap-[3px]" aria-hidden="true">
                        <span class="h-1.5 w-[3px] rounded-[1px]" :class="damage.level >= 1 ? damage.bar : 'bg-slate-700'"></span>
                        <span class="h-2 w-[3px] rounded-[1px]" :class="damage.level >= 2 ? damage.bar : 'bg-slate-700'"></span>
                        <span class="h-2.5 w-[3px] rounded-[1px]" :class="damage.level >= 3 ? damage.bar : 'bg-slate-700'"></span>
                      </span>
                      <span class="text-[11px] font-semibold" :class="damage.text">Kerusakan {{ damage.label }}</span>
                    </span>
                    <span class="hidden items-center gap-1.5 rounded-full border border-slate-800 bg-slate-950/50 px-2.5 py-0.5 text-[10px] font-medium text-slate-400 sm:inline-flex">
                      {{ report.category }}
                    </span>
                  </div>
                  <h1 class="mt-3 text-xl font-extrabold leading-snug tracking-tight text-white sm:text-2xl">{{ report.title }}</h1>
                  <p class="mt-1.5 text-xs text-slate-500">Laporan kerusakan fasilitas · Pelapor: {{ report.is_anonymous ? 'Anonim' : report.reporter_name }}</p>
                </div>

                <div class="shrink-0 rounded-lg border border-slate-800 bg-slate-950/50 px-3.5 py-3 sm:text-right">
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Waktu Pelaporan</p>
                  <p class="mt-1 text-xs font-semibold text-slate-300">{{ formatDateTime(report.created_at) }}</p>
                </div>
              </div>

              <!-- Metadata kunci -->
              <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div v-for="m in caseMeta" :key="m.label" class="flex items-start gap-2.5 rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
                  <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="m.icon" />
                  </svg>
                  <div class="min-w-0">
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">{{ m.label }}</dt>
                    <dd class="mt-1 text-xs font-medium leading-snug text-slate-200">{{ m.value }}</dd>
                  </div>
                </div>
              </dl>

              <!-- Deskripsi kerusakan -->
              <div class="space-y-3 border-t border-slate-800/70 pt-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Deskripsi Kerusakan</p>
                <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-4 text-sm leading-relaxed text-slate-300">
                  {{ report.description }}
                </div>
              </div>

              <!-- Lampiran foto -->
              <div v-if="report.attachments.length" class="space-y-3 border-t border-slate-800/70 pt-5">
                <div class="flex items-center justify-between gap-3">
                  <div class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Foto Lampiran Kerusakan</p>
                  </div>
                  <span class="whitespace-nowrap text-[10px] text-slate-600">{{ report.attachments.length }} foto · klik untuk memperbesar</span>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                  <figure
                    v-for="img in report.attachments"
                    :key="img.id"
                    class="group relative cursor-pointer overflow-hidden rounded-lg border border-slate-800 bg-slate-950 print:cursor-default"
                    @click="openPreview(img)"
                  >
                    <img
                      :src="img.url"
                      :alt="'Foto kerusakan ' + report.ticket_code"
                      loading="lazy"
                      class="h-32 w-full object-cover transition-transform duration-500 group-hover:scale-[1.04] sm:h-36"
                    />
                    <span class="pointer-events-none absolute inset-0 flex items-center justify-center gap-2 bg-slate-950/50 text-[11px] font-semibold text-white opacity-0 backdrop-blur-[2px] transition-opacity duration-200 group-hover:opacity-100 print:hidden">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0zM10 7v6m3-3H7" />
                      </svg>
                      Perbesar
                    </span>
                  </figure>
                </div>
              </div>

              <!-- Strip transparansi -->
              <p class="flex items-center gap-1.5 border-t border-slate-800/70 pt-4 text-[11px] text-slate-600">
                <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Pelapor dapat memantau perkembangan perbaikan ini secara transparan.
              </p>
            </div>
          </section>

          <!-- ===== Timeline perkembangan ===== -->
          <section class="print-card fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 90ms">
            <div class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4 sm:px-6">
              <div class="flex items-center gap-2.5">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                  <h2 class="text-sm font-bold tracking-tight text-slate-100">Riwayat Perkembangan Perbaikan</h2>
                  <p class="mt-0.5 text-[11px] text-slate-500">Setiap tindak lanjut tercatat dan terlihat oleh pelapor.</p>
                </div>
              </div>
              <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400">{{ report.logs.length }} Catatan</span>
            </div>

            <ol class="px-5 py-5 sm:px-6">
              <li
                v-for="(log, i) in report.logs"
                :key="log.id"
                class="relative flex gap-3.5"
                :class="i < report.logs.length - 1 ? 'pb-6' : ''"
              >
                <!-- Rel & titik -->
                <div class="flex flex-col items-center">
                  <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-500 bg-emerald-500 text-slate-950">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </span>
                  <span
                    v-if="i < report.logs.length - 1"
                    class="mt-1 w-px flex-1 bg-emerald-500/40"
                    aria-hidden="true"
                  ></span>
                </div>

                <!-- Isi catatan -->
                <div class="min-w-0 flex-1 pt-0.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold text-slate-100">{{ log.status }}</p>
                    <span
                      v-if="i === 0 && report.logs.length > 1"
                      class="inline-flex items-center gap-1 rounded border border-emerald-500/30 bg-emerald-500/15 px-1.5 py-px text-[10px] font-semibold text-emerald-400"
                    >
                      <span class="h-1 w-1 animate-pulse rounded-full bg-emerald-400" aria-hidden="true"></span>
                      Terbaru
                    </span>
                  </div>
                  <p class="mt-0.5 font-mono text-[10px] text-slate-600">{{ log.created_at }}</p>
                  <p class="mt-1.5 text-xs leading-relaxed text-slate-400">{{ log.note }}</p>
                </div>
              </li>
            </ol>

            <p class="flex items-center gap-1.5 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 text-[11px] text-slate-600 sm:px-6">
              <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              Jejak perkembangan tidak dapat diubah setelah tercatat
            </p>
          </section>
        </div>

        <!-- ===== Panel aksi (tidak tercetak) ===== -->
        <aside class="space-y-6 print:hidden lg:sticky lg:top-20">
          <section class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/25" style="animation-delay: 140ms">
            <div class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4">
              <div>
                <h2 class="text-sm font-bold tracking-tight text-slate-100">Update Status Perbaikan</h2>
                <p class="mt-0.5 text-[11px] text-slate-500">Perbarui progres &amp; dokumentasikan penanganan</p>
              </div>
              <span class="font-mono text-[9px] font-semibold uppercase tracking-[0.18em] text-slate-600">Aksi</span>
            </div>

            <form @submit.prevent="handleUpdateStatus" class="space-y-5 p-5">

              <!-- Status: kartu radio semantik -->
              <div class="space-y-2.5">
                <label class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Status Baru</label>
                <div class="space-y-1.5" role="radiogroup" aria-label="Status perbaikan">
                  <button
                    v-for="opt in statusOptions"
                    :key="opt.value"
                    type="button"
                    :aria-pressed="form.status === opt.value"
                    @click="form.status = opt.value"
                    class="flex w-full items-center gap-2.5 rounded-lg border px-3 py-2 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                    :class="form.status === opt.value ? opt.active : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'"
                  >
                    <span class="h-2 w-2 shrink-0 rounded-full transition-colors duration-200" :class="form.status === opt.value ? opt.dot : 'bg-slate-600'" aria-hidden="true"></span>
                    <span class="min-w-0 flex-1">
                      <span class="block text-xs font-semibold text-slate-100">{{ opt.label }}</span>
                      <span class="block text-[10px] leading-snug text-slate-500">{{ opt.desc }}</span>
                    </span>
                    <svg v-if="form.status === opt.value" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Catatan penanganan -->
              <div class="space-y-2 border-t border-slate-800/70 pt-4">
                <div class="flex items-center justify-between">
                  <label for="handling-note" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Catatan Penanganan</label>
                  <span class="font-mono text-[10px] tabular-nums" :class="notesLength > 0 ? 'text-emerald-500/80' : 'text-slate-600'">{{ notesLength }} karakter</span>
                </div>
                <textarea
                  id="handling-note"
                  v-model="form.note"
                  rows="5"
                  placeholder="Contoh: Teknisi telah mengganti kapasitor AC yang terbakar..."
                  :aria-invalid="!!formErrors.note || undefined"
                  class="w-full resize-none rounded-lg border bg-slate-950/60 px-3.5 py-2.5 text-sm leading-relaxed text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  :class="formErrors.note ? 'border-red-400/60' : 'border-slate-800'"
                ></textarea>
                <p v-if="formErrors.note" class="flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                  <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                  </svg>
                  {{ formErrors.note }}
                </p>
                <p class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500">
                  <svg class="mt-px h-3 w-3 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Catatan ini akan terlihat oleh pelapor sebagai perkembangan perbaikan.
                </p>
              </div>

              <!-- Submit -->
              <button
                type="submit"
                :disabled="isSubmitting"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
              >
                <svg v-if="!isSubmitting" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ isSubmitting ? 'Menyimpan...' : 'Perbarui Status' }}
              </button>
            </form>
          </section>
        </aside>
      </div>
    </main>

    <!-- ============ Footer (tidak tercetak) ============ -->
    <footer class="border-t border-slate-800/70 print:hidden">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="text-[11px] text-slate-600">Perbaikan fasilitas didokumentasikan secara transparan</p>
      </div>
    </footer>

    <!-- ============ Modal perbesar lampiran ============ -->
    <div
      v-if="previewImage"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 print:hidden"
      role="dialog"
      aria-modal="true"
      aria-label="Pratinjau foto kerusakan"
    >
      <div class="backdrop-in absolute inset-0 bg-slate-950/90 backdrop-blur-md" aria-hidden="true" @click="closePreview"></div>

      <div class="modal-panel relative w-full max-w-4xl overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-2 shadow-2xl shadow-black/50">
        <button
          type="button"
          @click="closePreview"
          aria-label="Tutup"
          class="absolute right-4 top-4 z-10 flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 bg-slate-950/80 text-slate-400 backdrop-blur-sm transition-colors duration-200 hover:bg-slate-800 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <img :src="previewImage.url" :alt="'Bukti kerusakan diperbesar ' + report.ticket_code" class="max-h-[80vh] w-full rounded-lg object-contain" />
        <p class="px-3 pb-1 pt-2 text-center text-[11px] text-slate-500">{{ report.ticket_code }} · Foto lampiran kerusakan</p>
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

/* ================================================================
   CETAK DOKUMEN — tema gelap dibalik menjadi dokumen terang
   (senada dengan halaman detail BK) agar hasil cetak terbaca.
   ================================================================ */
@media print {
  @page {
    margin: 12mm;
  }

  .sapa-root {
    background: #ffffff !important;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }

  .print-card {
    background: #ffffff !important;
    border-color: #cbd5e1 !important;
    border-left-color: #cbd5e1 !important;
    box-shadow: none !important;
    break-inside: avoid;
  }

  .print-card,
  .print-card * {
    color: #1e293b !important;
    border-color: #e2e8f0 !important;
  }

  .print-card [class*='bg-'] {
    background-color: transparent !important;
  }

  .print-card img {
    background-color: #f1f5f9 !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .backdrop-in,
  .modal-panel { animation: none; opacity: 1; }
}
</style>