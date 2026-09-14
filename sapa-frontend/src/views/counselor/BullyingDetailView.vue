<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const reportId = route.params.id
const isSubmitting = ref(false)
const showImageModal = ref(false)

/* ---------------------------------- */
/* State form tindak lanjut (existing)*/
/* ---------------------------------- */
const form = ref({
  status: '',
  handling_notes: '',
  counselor_action: 'Konseling Individu'
})

const formErrors = ref({})

/* ---------------------------------- */
/* Data laporan bullying (existing)   */
/* ---------------------------------- */
const report = ref({
  id: reportId,
  ticket_code: 'RPT-2026-004',
  category: 'Cyberbullying & Ancaman',
  reporter_relation: 'Korban Langsung',
  is_anonymous: false, // Ubah ke true untuk testing mode anonim
  reporter_name: 'Ahmad Faisal',
  reporter_class: 'XI RPL 2',
  reporter_nisn: '0051234981',
  incident_location: 'Grup WhatsApp Kelas / Area Parkir Belakang',
  incident_date: '11 September 2026',
  created_at: '11 Sep 2026, 09:15 WIB',
  description: 'Saya mendapatkan pesan ancaman dan ejekan yang merendahkan di grup media sosial dari beberapa siswa satu angkatan. Selain itu, kemarin sepulang sekolah helm saya juga disembunyikan di area parkir belakang. Hal ini membuat saya takut dan tidak nyaman untuk datang ke sekolah.',
  attachment_url: 'https://placehold.co/800x600/0f172a/34d399?text=Bukti+Tangkap+Layar+Chat',
  status: 'Menunggu',
  priority: 'Tinggi',
  handled_by: null,
  handling_notes: '',
  timeline: [
    {
      id: 1,
      status: 'Menunggu',
      title: 'Laporan Masuk Sistem',
      description: 'Laporan perundungan berhasil dikirim dan masuk antrian utama BK.',
      time: '11 Sep 2026, 09:15 WIB',
      actor: 'Sistem SAPA'
    }
  ]
})

onMounted(() => {
  form.value.status = report.value.status
  form.value.handling_notes = report.value.handling_notes || ''
  window.addEventListener('keydown', handleEscKey)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscKey)
  document.body.style.overflow = ''
})

/* Escape menutup modal bukti */
const handleEscKey = (e) => {
  if (e.key === 'Escape' && showImageModal.value) showImageModal.value = false
}

watch(showImageModal, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

/* Kembali: fallback ke antrian bila dibuka langsung */
const goBack = () => {
  if (typeof window !== 'undefined' && window.history.state?.back) {
    router.back()
  } else {
    router.push('/counselor/bullying-reports')
  }
}

/* ---------------------------------- */
/* Templat respon cepat (existing)    */
/* ---------------------------------- */
const noteTemplates = [
  { label: 'Konseling Awal', text: 'Siswa pelapor telah dipanggil ke ruang BK untuk sesi konseling awal.' },
  { label: 'Mediasi Damai', text: 'Melakukan mediasi bersama pihak-pihak terkait dan menyepakati perjanjian damai.' },
]

const applyTemplate = (text) => {
  if (form.value.handling_notes) {
    form.value.handling_notes += `\n${text}`
  } else {
    form.value.handling_notes = text
  }
}

/* Bersihkan error saat field diedit */
watch(() => form.value.status, () => { if (formErrors.value.status) formErrors.value.status = '' })
watch(() => form.value.handling_notes, () => { if (formErrors.value.handling_notes) formErrors.value.handling_notes = '' })

const notesLength = computed(() => (form.value.handling_notes || '').length)

/* ---------------------------------- */
/* Submit (behavior existing,         */
/* alert diganti toast + error inline)*/
/* ---------------------------------- */
const handleUpdateStatus = async () => {
  formErrors.value = {}

  if (!form.value.status || !form.value.handling_notes.trim()) {
    if (!form.value.status) formErrors.value.status = 'Pilih status laporan terlebih dahulu.'
    if (!form.value.handling_notes.trim()) formErrors.value.handling_notes = 'Catatan penanganan wajib diisi.'
    toast.error('Mohon lengkapi status dan catatan penanganan terlebih dahulu.')
    return
  }

  isSubmitting.value = true
  try {
    const now = new Date().toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
    const counselorName = authStore.user?.name || 'Guru Bimbingan Konseling'

    report.value.status = form.value.status
    report.value.handling_notes = form.value.handling_notes
    report.value.handled_by = counselorName

    // Tambah log timeline (logika existing)
    report.value.timeline.unshift({
      id: Date.now(),
      status: form.value.status,
      title: `Status Diperbarui: ${form.value.status}`,
      description: `[Tindakan: ${form.value.counselor_action}] ${form.value.handling_notes}`,
      time: now,
      actor: counselorName
    })

    toast.success('Penanganan laporan berhasil diperbarui!', {
      description: `Status kasus kini: ${form.value.status}.`
    })
  } catch (error) {
    console.error('Gagal memperbarui laporan:', error)
    toast.error('Gagal memperbarui laporan. Silakan coba lagi.')
  } finally {
    isSubmitting.value = false
  }
}

/* ---------------------------------- */
/* Cetak laporan (existing)           */
/* ---------------------------------- */
const handlePrint = () => {
  window.print()
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

/* Prioritas — bar sinyal ala antrian BK */
const getPriority = (priority) => {
  const map = {
    'Tinggi': { label: 'Tinggi', level: 3, text: 'text-rose-400', bar: 'bg-rose-500' },
    'Sedang': { label: 'Sedang', level: 2, text: 'text-amber-400', bar: 'bg-amber-500' },
    'Rendah': { label: 'Rendah', level: 1, text: 'text-slate-400', bar: 'bg-slate-500' },
  }
  return map[priority] || map['Rendah']
}

const priority = computed(() => getPriority(report.value.priority))

/* Opsi status — kartu radio semantik, nilai identik dengan select asli */
const statusOptions = [
  { value: 'Menunggu', label: 'Menunggu Respon', desc: 'Laporan baru masuk antrian BK', dot: 'bg-amber-400', active: 'border-amber-500/50 bg-amber-500/[0.06] text-amber-400' },
  { value: 'Ditinjau', label: 'Ditinjau Guru BK', desc: 'Verifikasi bukti & keterangan awal', dot: 'bg-blue-400', active: 'border-blue-500/50 bg-blue-500/[0.06] text-blue-400' },
  { value: 'Diproses', label: 'Diproses / Pemanggilan', desc: 'Penanganan aktif kasus berjalan', dot: 'bg-emerald-400', active: 'border-emerald-500/50 bg-emerald-500/[0.06] text-emerald-400' },
  { value: 'Selesai', label: 'Selesai — Kasus Tuntas', desc: 'Tindak lanjut selesai & terdokumentasi', dot: 'bg-slate-300', active: 'border-slate-500/60 bg-slate-500/10 text-slate-300' },
  { value: 'Ditolak', label: 'Ditolak — Bukti Tidak Cukup', desc: 'Di luar lingkup / tidak dapat diproses', dot: 'bg-red-400', active: 'border-red-500/50 bg-red-500/[0.06] text-red-400' },
]

/* Opsi jenis tindakan (nilai identik dengan select asli) */
const actionOptions = [
  { value: 'Konseling Individu', label: 'Konseling Individu (Pelapor)' },
  { value: 'Pemanggilan Saksi', label: 'Pemanggilan Saksi-Saksi' },
  { value: 'Mediasi Berhadapan', label: 'Mediasi Pelapor & Terlapor' },
  { value: 'Koordinasi Wali Kelas & Orang Tua', label: 'Koordinasi Wali Kelas & Orang Tua' },
  { value: 'Rujukan Eksternal', label: 'Rujukan Eksternal / Kasus Khusus' },
]

/* Metadata kasus — data yang sudah ada, kini ditampilkan */
const caseMeta = computed(() => [
  { label: 'Tanggal Kejadian', value: report.value.incident_date, icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
  { label: 'Lokasi Kejadian', value: report.value.incident_location, icon: 'M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' },
  { label: 'Relasi Pelapor', value: report.value.reporter_relation, icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
  { label: 'Waktu Pelaporan', value: report.value.created_at, icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
])

/* Turunan tampilan */
const initialsOf = (name) => {
  const n = (name || '').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/)
  return (parts.length > 1 ? parts[0][0] + parts[parts.length - 1][0] : n.slice(0, 2)).toUpperCase()
}

const printDate = new Date().toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' })
const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas (tidak tercetak) ============ -->
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md print:hidden">
      <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">

        <button
          type="button"
          @click="goBack"
          class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
        >
          <svg class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span class="hidden sm:inline">Kembali ke Antrian</span>
          <span class="sm:hidden">Kembali</span>
        </button>

        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
          <button
            type="button"
            @click="handlePrint"
            title="Cetak / simpan sebagai PDF"
            class="hidden items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 sm:inline-flex"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-4 0h-4v4h4v-4z" />
            </svg>
            <span class="hidden md:inline">Cetak Dokumen</span>
            <span class="md:hidden">Cetak</span>
          </button>

          <div class="hidden h-6 w-px bg-slate-800 sm:block"></div>

          <span class="hidden rounded-md border border-rose-500/20 bg-rose-500/10 px-2 py-1 font-mono text-[11px] font-medium text-rose-400 sm:inline-flex">
            {{ report.ticket_code }}
          </span>

          <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1 text-[11px] font-medium" :class="getStatusBadge(report.status)">
            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
            {{ report.status }}
          </span>
        </div>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
      <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">

        <!-- ===== Kolom kasus (dapat dicetak) ===== -->
        <div class="min-w-0 space-y-6">

          <!-- Kepala dokumen cetak -->
          <div class="hidden border-b border-slate-300 pb-4 print:block">
            <div class="flex items-end justify-between gap-4">
              <div>
                <p class="text-lg font-extrabold tracking-tight">SAPA</p>
                <p class="mt-0.5 text-[10px] font-medium uppercase tracking-[0.16em]">Sistem Layanan Aspirasi &amp; Pengaduan Sekolah — Panel BK</p>
              </div>
              <div class="text-right text-[11px] leading-relaxed">
                <p class="font-semibold">Dokumen Penanganan Kasus Perundungan</p>
                <p class="font-mono">{{ report.ticket_code }} · Status: {{ report.status }}</p>
                <p>Dicetak {{ printDate }}</p>
              </div>
            </div>
          </div>

          <!-- ===== Kartu kasus ===== -->
          <section class="print-card fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/25">
            <span class="absolute inset-x-0 top-0 z-10 h-[2px] bg-gradient-to-r from-rose-500/70 via-rose-500/20 to-transparent print:hidden" aria-hidden="true"></span>
            <div class="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-rose-500/10 blur-3xl print:hidden" aria-hidden="true"></div>

            <div class="relative z-10 space-y-6 p-5 sm:p-6 lg:p-7">

              <!-- Kepala kasus -->
              <div class="flex flex-col gap-4 border-b border-slate-800/70 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded border border-rose-500/20 bg-rose-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-rose-400">{{ report.ticket_code }}</span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-950/50 px-2.5 py-0.5">
                      <span class="flex items-end gap-[3px]" aria-hidden="true">
                        <span class="h-1.5 w-[3px] rounded-[1px]" :class="priority.level >= 1 ? priority.bar : 'bg-slate-700'"></span>
                        <span class="h-2 w-[3px] rounded-[1px]" :class="priority.level >= 2 ? priority.bar : 'bg-slate-700'"></span>
                        <span class="h-2.5 w-[3px] rounded-[1px]" :class="priority.level >= 3 ? priority.bar : 'bg-slate-700'"></span>
                      </span>
                      <span class="text-[11px] font-semibold" :class="priority.text">Prioritas {{ priority.label }}</span>
                    </span>
                  </div>
                  <h1 class="mt-3 text-xl font-extrabold leading-snug tracking-tight text-white sm:text-2xl">{{ report.category }}</h1>
                  <p class="mt-1.5 text-xs text-slate-500">Laporan perundungan · Pelapor: {{ report.reporter_relation }}</p>
                </div>

                <div class="shrink-0 rounded-lg border border-slate-800 bg-slate-950/50 px-3.5 py-3 sm:text-right">
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Waktu Pelaporan</p>
                  <p class="mt-1 text-xs font-semibold text-slate-300">{{ report.created_at }}</p>
                </div>
              </div>

              <!-- Identitas pelapor -->
              <div>
                <!-- Anonim -->
                <div v-if="report.is_anonymous" class="flex items-start gap-3 rounded-xl border border-rose-500/25 bg-rose-500/[0.04] p-4">
                  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-rose-500/25 bg-rose-500/10 text-rose-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <h3 class="text-xs font-bold text-rose-300">Identitas Pelapor Dikerahasiakan (Anonim)</h3>
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-400">
                      Siswa mengajukan laporan ini secara rahasia. Identitas personal disembunyikan oleh sistem untuk keamanan pelapor.
                      Peran pelapor: <span class="font-semibold text-slate-200">{{ report.reporter_relation }}</span>.
                    </p>
                  </div>
                </div>

                <!-- Terbuka -->
                <div v-else class="rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                  <div class="flex items-center justify-between gap-3 border-b border-slate-800/70 pb-3">
                    <h3 class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Identitas Pelapor</h3>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-400">
                      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Terverifikasi Siswa
                    </span>
                  </div>

                  <div class="mt-4 flex items-center gap-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-xs font-bold text-emerald-400">
                      {{ initialsOf(report.reporter_name) }}
                    </div>
                    <div class="min-w-0">
                      <p class="truncate text-sm font-semibold text-white">{{ report.reporter_name }}</p>
                      <p class="mt-0.5 truncate text-xs text-slate-400">{{ report.reporter_class }} · {{ report.reporter_relation }}</p>
                    </div>
                  </div>

                  <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-slate-800/70 pt-4 sm:grid-cols-3">
                    <div>
                      <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Kelas / Rombel</dt>
                      <dd class="mt-1 text-xs font-medium text-slate-200">{{ report.reporter_class }}</dd>
                    </div>
                    <div>
                      <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">NISN</dt>
                      <dd class="mt-1 font-mono text-xs text-slate-300">{{ report.reporter_nisn }}</dd>
                    </div>
                    <div>
                      <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600">Posisi / Peran</dt>
                      <dd class="mt-1 text-xs font-medium text-emerald-400">{{ report.reporter_relation }}</dd>
                    </div>
                  </dl>
                </div>
              </div>

              <!-- Metadata kunci kasus -->
              <dl class="grid grid-cols-1 gap-3 border-t border-slate-800/70 pt-5 sm:grid-cols-2">
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

              <!-- Kronologi -->
              <div class="space-y-3 border-t border-slate-800/70 pt-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Uraian / Kronologi Kejadian</p>
                <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-4 text-sm leading-relaxed whitespace-pre-line text-slate-300">
                  {{ report.description }}
                </div>
              </div>

              <!-- Lampiran bukti -->
              <div v-if="report.attachment_url" class="space-y-3 border-t border-slate-800/70 pt-5">
                <div class="flex items-center gap-2">
                  <svg class="h-3.5 w-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                  </svg>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Bukti Pendukung (Lampiran)</p>
                </div>

                <figure class="group relative w-full max-w-lg cursor-pointer overflow-hidden rounded-lg border border-slate-800 bg-slate-950 print:cursor-default" @click="showImageModal = true">
                  <img
                    :src="report.attachment_url"
                    alt="Bukti kejadian"
                    loading="lazy"
                    class="h-auto max-h-72 w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                  />
                  <span class="pointer-events-none absolute inset-0 flex items-center justify-center gap-2 bg-slate-950/50 text-xs font-semibold text-white opacity-0 backdrop-blur-[2px] transition-opacity duration-200 group-hover:opacity-100 print:hidden">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0zM10 7v6m3-3H7" />
                    </svg>
                    Klik untuk Memperbesar
                  </span>
                </figure>
              </div>

              <!-- Strip kerahasiaan dokumen -->
              <p class="flex items-center gap-1.5 border-t border-slate-800/70 pt-4 text-[11px] text-slate-600">
                <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Dokumen kasus bersifat rahasia — hanya untuk petugas berwenang.
              </p>
            </div>
          </section>

          <!-- ===== Timeline audit ===== -->
          <section class="print-card fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 90ms">
            <div class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4 sm:px-6">
              <div class="flex items-center gap-2.5">
                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                  <h2 class="text-sm font-bold tracking-tight text-slate-100">Riwayat Audit &amp; Timeline Penanganan</h2>
                  <p class="mt-0.5 text-[11px] text-slate-500">Setiap tindak lanjut tercatat sebagai jejak audit kasus.</p>
                </div>
              </div>
              <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400">{{ report.timeline.length }} Catatan</span>
            </div>

            <ol class="px-5 py-5 sm:px-6">
              <li
                v-for="(log, i) in report.timeline"
                :key="log.id"
                class="relative flex gap-3.5"
                :class="i < report.timeline.length - 1 ? 'pb-6' : ''"
              >
                <!-- Rel & titik -->
                <div class="flex flex-col items-center">
                  <span class="timeline-dot flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-500 bg-emerald-500 text-slate-950">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </span>
                  <span
                    v-if="i < report.timeline.length - 1"
                    class="timeline-rail mt-1 w-px flex-1 bg-emerald-500/40"
                    aria-hidden="true"
                  ></span>
                </div>

                <!-- Isi catatan -->
                <div class="min-w-0 flex-1 pt-0.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold text-slate-100">{{ log.title }}</p>
                    <span
                      v-if="i === 0 && report.timeline.length > 1"
                      class="inline-flex items-center gap-1 rounded border border-emerald-500/30 bg-emerald-500/15 px-1.5 py-px text-[10px] font-semibold text-emerald-400"
                    >
                      <span class="h-1 w-1 animate-pulse rounded-full bg-emerald-400" aria-hidden="true"></span>
                      Terbaru
                    </span>
                  </div>
                  <p class="mt-0.5 font-mono text-[10px] text-slate-600">{{ log.time }}</p>
                  <p class="mt-1.5 text-xs leading-relaxed whitespace-pre-line text-slate-400">{{ log.description }}</p>

                  <div class="mt-2.5 flex items-center gap-2">
                    <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-700 bg-slate-800 text-[9px] font-bold text-slate-300">
                      {{ initialsOf(log.actor) }}
                    </div>
                    <p class="text-[10px] text-slate-500">
                      Petugas: <span class="font-semibold text-slate-400">{{ log.actor }}</span>
                    </p>
                  </div>
                </div>
              </li>
            </ol>

            <p class="flex items-center gap-1.5 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 text-[11px] text-slate-600 sm:px-6">
              <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              Jejak audit tidak dapat diubah setelah tercatat
            </p>
          </section>
        </div>

        <!-- ===== Panel aksi (tidak tercetak) ===== -->
        <aside class="space-y-6 print:hidden lg:sticky lg:top-20">
          <section class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/25" style="animation-delay: 140ms">
            <div class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4">
              <div>
                <h2 class="text-sm font-bold tracking-tight text-slate-100">Form Tindak Lanjut BK</h2>
                <p class="mt-0.5 text-[11px] text-slate-500">Perbarui status &amp; dokumentasikan penanganan</p>
              </div>
              <span class="font-mono text-[9px] font-semibold uppercase tracking-[0.18em] text-slate-600">Aksi</span>
            </div>

            <form @submit.prevent="handleUpdateStatus" class="space-y-5 p-5">

              <!-- Status: kartu radio semantik -->
              <div class="space-y-2.5">
                <label class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Ubah Status Laporan</label>
                <div class="space-y-1.5" role="radiogroup" aria-label="Status laporan">
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
                <p v-if="formErrors.status" class="flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                  <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                  </svg>
                  {{ formErrors.status }}
                </p>
              </div>

              <!-- Jenis tindakan -->
              <div class="space-y-2.5 border-t border-slate-800/70 pt-4">
                <label for="counselor-action" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Jenis Tindakan BK</label>
                <div class="relative">
                  <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                  </svg>
                  <select
                    id="counselor-action"
                    v-model="form.counselor_action"
                    class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  >
                    <option v-for="a in actionOptions" :key="a.value" :value="a.value" class="bg-slate-900 text-slate-100">{{ a.label }}</option>
                  </select>
                  <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>

              <!-- Templat cepat -->
              <div class="space-y-2 border-t border-slate-800/70 pt-4">
                <span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Templat Catatan Cepat</span>
                <div class="flex flex-wrap gap-1.5">
                  <button
                    v-for="tpl in noteTemplates"
                    :key="tpl.label"
                    type="button"
                    @click="applyTemplate(tpl.text)"
                    class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-slate-800 bg-slate-950/50 px-2.5 py-1 text-[11px] font-medium text-slate-400 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
                  >
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ tpl.label }}
                  </button>
                </div>
              </div>

              <!-- Catatan penanganan -->
              <div class="space-y-2 border-t border-slate-800/70 pt-4">
                <div class="flex items-center justify-between">
                  <label for="handling-notes" class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Catatan Penanganan &amp; Tindak Lanjut</label>
                  <span class="font-mono text-[10px] tabular-nums" :class="notesLength > 0 ? 'text-emerald-500/80' : 'text-slate-600'">{{ notesLength }} karakter</span>
                </div>
                <textarea
                  id="handling-notes"
                  v-model="form.handling_notes"
                  rows="5"
                  placeholder="Tuliskan catatan konseling atau hasil penanganan kasus secara mendalam di sini..."
                  :aria-invalid="!!formErrors.handling_notes || undefined"
                  class="w-full resize-none rounded-lg border bg-slate-950/60 px-3.5 py-2.5 text-sm leading-relaxed text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  :class="formErrors.handling_notes ? 'border-red-400/60' : 'border-slate-800'"
                ></textarea>
                <p v-if="formErrors.handling_notes" class="flex items-center gap-1.5 text-[11px] font-medium text-red-400">
                  <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                  </svg>
                  {{ formErrors.handling_notes }}
                </p>
                <p class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500">
                  <svg class="mt-px h-3 w-3 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Catatan ini tersimpan sebagai dokumentasi rahasia internal BK.
                </p>
              </div>

              <!-- Penanggung jawab -->
              <div v-if="report.handled_by" class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3.5 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[10px] font-bold text-emerald-400">
                  {{ initialsOf(report.handled_by) }}
                </div>
                <div class="min-w-0">
                  <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Penanggung Jawab Terakhir</p>
                  <p class="mt-0.5 truncate text-xs font-semibold text-emerald-400">{{ report.handled_by }}</p>
                </div>
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
                {{ isSubmitting ? 'Memperbarui...' : 'Simpan Tindak Lanjut' }}
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
        <p class="text-[11px] text-slate-600">Laporan perundungan ditangani secara rahasia oleh petugas berwenang</p>
      </div>
    </footer>

    <!-- ============ Modal perbesar bukti ============ -->
    <div
      v-if="showImageModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 print:hidden"
      role="dialog"
      aria-modal="true"
      aria-label="Pratinjau bukti laporan"
    >
      <div class="backdrop-in absolute inset-0 bg-slate-950/90 backdrop-blur-md" aria-hidden="true" @click="showImageModal = false"></div>

      <div class="modal-panel relative w-full max-w-4xl overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-2 shadow-2xl shadow-black/50">
        <button
          type="button"
          @click="showImageModal = false"
          aria-label="Tutup"
          class="absolute right-4 top-4 z-10 flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 bg-slate-950/80 text-slate-400 backdrop-blur-sm transition-colors duration-200 hover:bg-slate-800 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <img :src="report.attachment_url" alt="Bukti laporan diperbesar" class="max-h-[80vh] w-full rounded-lg object-contain" />
        <p class="px-3 pb-1 pt-2 text-center text-[11px] text-slate-500">{{ report.ticket_code }} · Bukti pendukung kasus</p>
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
   agar "Cetak Dokumen" menghasilkan berkas kasus yang dapat dibaca
   (teks putih di atas kertas putih tidak akan terlihat).
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
    box-shadow: none !important;
    break-inside: avoid;
  }

  .print-card,
  .print-card * {
    color: #1e293b !important;
    border-color: #e2e8f0 !important;
  }

  /* Blok gelap & lapisan semantik menjadi transparan */
  .print-card [class*='bg-'] {
    background-color: transparent !important;
  }

  /* Elemen struktural timeline tetap terlihat sebagai garis abu-abu */
  .print-card .timeline-rail {
    background-color: #cbd5e1 !important;
  }

  .print-card .timeline-dot {
    background-color: #ffffff !important;
    border-color: #64748b !important;
  }

  /* Gambar bukti dicetak dengan bingkai halus */
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