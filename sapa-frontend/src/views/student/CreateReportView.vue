<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useForm } from 'vee-validate'
import * as yup from 'yup'
import { toast } from 'vue-sonner'
import reportService from '@/services/reportService'

const router = useRouter()
const route = useRoute()

const isLoading = ref(false)
const selectedCategory = ref('aspirasi')

const imageFiles = ref([])
const imagePreviews = ref([])
const fileInputRef = ref(null)
const isDragging = ref(false)

const TYPE_MAP = {
  aspirasi: 'aspiration',
  fasilitas: 'facility',
  bullying: 'bullying',
}

/* ---------------------------------- */
/* Kanal laporan (tetap sama)         */
/* ---------------------------------- */
const categories = [
  {
    id: 'aspirasi',
    label: 'Aspirasi',
    desc: 'Usulan/saran untuk sekolah',
    icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'
  },
  {
    id: 'fasilitas',
    label: 'Fasilitas',
    desc: 'Laporan kerusakan/sarpras',
    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
  },
  {
    id: 'bullying',
    label: 'Bullying',
    desc: 'Pengaduan perundungan',
    icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
  }
]

const CATEGORY_STYLE = {
  aspirasi: {
    selected: 'border-purple-500/60 bg-purple-500/[0.06]',
    tile: 'border-purple-500/40 bg-purple-500/15 text-purple-400',
    check: 'border-purple-500 bg-purple-500 text-slate-950',
    topBar: 'from-purple-500/70 to-transparent',
    guideBadge: 'border-purple-500/30 bg-purple-500/10 text-purple-400',
    guideIcon: 'text-purple-400',
  },
  fasilitas: {
    selected: 'border-cyan-500/60 bg-cyan-500/[0.06]',
    tile: 'border-cyan-500/40 bg-cyan-500/15 text-cyan-400',
    check: 'border-cyan-500 bg-cyan-500 text-slate-950',
    topBar: 'from-cyan-500/70 to-transparent',
    guideBadge: 'border-cyan-500/30 bg-cyan-500/10 text-cyan-400',
    guideIcon: 'text-cyan-400',
  },
  bullying: {
    selected: 'border-rose-500/60 bg-rose-500/[0.06]',
    tile: 'border-rose-500/40 bg-rose-500/15 text-rose-400',
    check: 'border-rose-500 bg-rose-500 text-slate-950',
    topBar: 'from-rose-500/70 to-transparent',
    guideBadge: 'border-rose-500/30 bg-rose-500/10 text-rose-400',
    guideIcon: 'text-rose-400',
  },
}

const CATEGORY_GUIDE = {
  aspirasi: {
    label: 'Aspirasi',
    tips: [
      'Jelaskan usulan Anda secara spesifik dan terukur.',
      'Sertakan manfaat usulan bagi seluruh warga sekolah.',
      'Aspirasi yang membangun akan diprioritaskan penanganannya.',
    ],
  },
  fasilitas: {
    label: 'Fasilitas',
    tips: [
      'Cantumkan lokasi kerusakan secara spesifik (ruang, lantai, gedung).',
      'Jelaskan dampak kerusakan terhadap kegiatan belajar.',
      'Lampirkan foto bukti agar penanganan lebih cepat.',
    ],
  },
  bullying: {
    label: 'Perundungan',
    tips: [
      'Tuliskan kronologi kejadian secara berurutan dan objektif.',
      'Cantumkan waktu serta lokasi kejadian.',
      'Anda dapat mengirim secara anonim — identitas tetap dilindungi.',
    ],
  },
}

const FACILITY_CATEGORIES = [
  { value: 'electricity', label: 'Listrik' },
  { value: 'furniture', label: 'Perabot' },
  { value: 'sanitation', label: 'Sanitasi' },
  { value: 'building', label: 'Bangunan' },
  { value: 'other', label: 'Lainnya' },
]
const DAMAGE_LEVELS = [
  { value: 'minor', label: 'Ringan' },
  { value: 'moderate', label: 'Sedang' },
  { value: 'severe', label: 'Berat' },
]
const ASPIRATION_CATEGORIES = [
  { value: 'academic', label: 'Akademik' },
  { value: 'facility_policy', label: 'Kebijakan Fasilitas' },
  { value: 'school_policy', label: 'Kebijakan Sekolah' },
  { value: 'other', label: 'Lainnya' },
]

const activeStyle = computed(() => CATEGORY_STYLE[selectedCategory.value] || CATEGORY_STYLE.aspirasi)
const activeGuide = computed(() => CATEGORY_GUIDE[selectedCategory.value] || CATEGORY_GUIDE.aspirasi)
const categoryCards = categories.map(c => ({ ...c, style: CATEGORY_STYLE[c.id] }))

const contentHint = computed(() => ({
  aspirasi: 'Jelaskan usulan Anda beserta manfaatnya bagi sekolah.',
  fasilitas: 'Sertakan lokasi kerusakan dan dampaknya terhadap kegiatan belajar.',
  bullying: 'Tuliskan kronologi, waktu, dan lokasi kejadian secara berurutan.',
}[selectedCategory.value]))

const followUpSteps = [
  { label: 'Menunggu', desc: 'Laporan masuk antrean petugas', dot: 'bg-amber-500' },
  { label: 'Ditinjau', desc: 'Verifikasi kelengkapan laporan', dot: 'bg-blue-500' },
  { label: 'Diproses', desc: 'Penanganan oleh pihak sekolah', dot: 'bg-emerald-500' },
  { label: 'Selesai', desc: 'Laporan ditindaklanjuti tuntas', dot: 'bg-slate-500' },
]

const schema = yup.object({
  category: yup.string().required('Pilih jenis laporan'),

  title: yup.string().when('category', {
    is: (val) => val !== 'bullying',
    then: (s) => s.required('Judul laporan wajib diisi').min(10, 'Judul minimal 10 karakter'),
    otherwise: (s) => s.notRequired(),
  }),

  content: yup.string().required('Isi laporan wajib diisi').min(20, 'Isi laporan minimal 20 karakter'),

  is_anonymous: yup.boolean(),

  // Khusus fasilitas
  location: yup.string().when('category', {
    is: 'fasilitas',
    then: (s) => s.required('Lokasi kerusakan wajib diisi'),
    otherwise: (s) => s.notRequired(),
  }),
  facilityCategory: yup.string().when('category', {
    is: 'fasilitas',
    then: (s) => s.required('Pilih kategori fasilitas'),
    otherwise: (s) => s.notRequired(),
  }),
  damageLevel: yup.string().notRequired(),

  // Khusus aspirasi
  aspirationCategory: yup.string().when('category', {
    is: 'aspirasi',
    then: (s) => s.required('Pilih kategori aspirasi'),
    otherwise: (s) => s.notRequired(),
  }),

  // Khusus bullying
  reporterRelation: yup.string().when('category', {
    is: 'bullying',
    then: (s) => s.required('Pilih posisi Anda'),
    otherwise: (s) => s.notRequired(),
  }),
  incidentDate: yup.string().notRequired(),
})

const { handleSubmit, defineField, errors, setFieldValue } = useForm({
  validationSchema: schema,
  initialValues: {
    category: 'aspirasi',
    is_anonymous: false,
  }
})

const [title, titleAttrs] = defineField('title')
const [content, contentAttrs] = defineField('content')
const [is_anonymous] = defineField('is_anonymous')
const [location, locationAttrs] = defineField('location')
const [facilityCategory] = defineField('facilityCategory')
const [damageLevel] = defineField('damageLevel')
const [aspirationCategory] = defineField('aspirationCategory')
const [reporterRelation] = defineField('reporterRelation')
const [incidentDate] = defineField('incidentDate')

onMounted(() => {
  if (route.query.type && ['aspirasi', 'fasilitas', 'bullying'].includes(route.query.type)) {
    selectedCategory.value = route.query.type
    setFieldValue('category', route.query.type)
  }
})

const selectCategory = (val) => {
  selectedCategory.value = val
  setFieldValue('category', val)
}

const MAX_FILES = 3

const processImageFile = (file) => {
  if (!file) return

  if (!file.type.startsWith('image/') && file.type !== 'application/pdf') {
    toast.error('Format berkas tidak didukung. Unggah PNG, JPG, JPEG, atau PDF.')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    toast.error('Ukuran berkas maksimal 2MB!')
    return
  }
  if (imageFiles.value.length >= MAX_FILES) {
    toast.error(`Maksimal ${MAX_FILES} lampiran.`)
    return
  }

  imageFiles.value.push(file)
  if (file.type.startsWith('image/')) {
    imagePreviews.value.push({ name: file.name, url: URL.createObjectURL(file), isImage: true })
  } else {
    imagePreviews.value.push({ name: file.name, url: null, isImage: false })
  }
}

const handleImageChange = (e) => {
  Array.from(e.target.files).forEach(processImageFile)
  e.target.value = ''
}

const onDrop = (e) => {
  isDragging.value = false
  Array.from(e.dataTransfer?.files || []).forEach(processImageFile)
}

const triggerFilePick = () => fileInputRef.value?.click()

const removeImage = (index) => {
  const preview = imagePreviews.value[index]
  if (preview?.url) URL.revokeObjectURL(preview.url)
  imageFiles.value.splice(index, 1)
  imagePreviews.value.splice(index, 1)
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

const onSubmit = handleSubmit(async (values) => {
  isLoading.value = true
  try {
    const formData = new FormData()
    formData.append('description', values.content)
    formData.append('is_anonymous', values.is_anonymous ? '1' : '0')

    let response

    if (selectedCategory.value === 'aspirasi') {
      formData.append('title', values.title)
      formData.append('category', values.aspirationCategory)
      formData.append('is_public', '1')
      response = await reportService.submitAspiration(Object.fromEntries(formData))
    }

    if (selectedCategory.value === 'fasilitas') {
      formData.append('title', values.title)
      formData.append('location', values.location)
      formData.append('category', values.facilityCategory)
      if (values.damageLevel) formData.append('damage_level', values.damageLevel)
      imageFiles.value.forEach((file) => formData.append('attachments[]', file))
      response = await reportService.submitFacilityReport(formData)
    }

    if (selectedCategory.value === 'bullying') {
      formData.append('reporter_relation', values.reporterRelation)
      if (values.incidentDate) formData.append('incident_date', values.incidentDate)
      imageFiles.value.forEach((file) => formData.append('attachments[]', file))
      response = await reportService.submitBullyingReport(formData)
    }

    toast.success('Laporan berhasil dikirim!', {
      description: response?.message || 'Laporan Anda akan segera ditinjau oleh pihak sekolah.'
    })

    router.push('/')
  } catch (error) {
    const validationErrors = error?.response?.data?.errors
    if (validationErrors) {
      const firstError = Object.values(validationErrors)[0]?.[0]
      toast.error(firstError || 'Periksa kembali data yang kamu isi.')
    } else {
      toast.error(error?.response?.data?.message || 'Gagal mengirim laporan. Silakan coba lagi.')
    }
  } finally {
    isLoading.value = false
  }
})

const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas ============ -->
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">

        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
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

          <span class="hidden h-6 w-px bg-slate-800 sm:block" aria-hidden="true"></span>

          <router-link to="/" class="flex min-w-0 items-center gap-2.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60">
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
          </router-link>
        </div>

        <transition name="chip">
          <span
            v-if="is_anonymous"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-medium text-emerald-400"
          >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
            </svg>
            Anonim Aktif
          </span>
        </transition>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <section class="fade-up">
        <div class="flex items-center gap-2.5">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-400/90">Formulir Pelaporan</p>
        </div>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Buat Laporan Baru</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-400">Sampaikan aspirasi, kendala fasilitas, atau pengaduan perundungan secara aman.</p>
      </section>

      <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">

        <!-- ===== Formulir ===== -->
        <form
          @submit="onSubmit"
          class="fade-up relative min-w-0 space-y-7 overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-5 sm:p-6 lg:p-8"
          style="animation-delay: 90ms"
        >
          <span class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r transition-colors duration-300" :class="activeStyle.topBar" aria-hidden="true"></span>

          <!-- 01 · Jenis laporan -->
          <section class="space-y-3.5">
            <div class="flex items-center gap-2.5">
              <span class="font-mono text-[11px] font-semibold text-emerald-500/80">01</span>
              <h2 class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Jenis Laporan</h2>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Jenis laporan">
              <button
                v-for="cat in categoryCards"
                :key="cat.id"
                type="button"
                :aria-pressed="selectedCategory === cat.id"
                @click="selectCategory(cat.id)"
                class="group relative flex flex-col rounded-xl border p-4 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="selectedCategory === cat.id ? cat.style.selected : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'"
              >
                <span
                  class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full border transition-all duration-200"
                  :class="selectedCategory === cat.id ? cat.style.check + ' scale-100 opacity-100' : 'scale-50 border-slate-700 bg-slate-900 opacity-0'"
                >
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </span>

                <span
                  class="flex h-10 w-10 items-center justify-center rounded-lg border transition-colors duration-200"
                  :class="selectedCategory === cat.id ? cat.style.tile : 'border-slate-800 bg-slate-900 text-slate-500 group-hover:text-slate-300'"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="cat.icon" />
                  </svg>
                </span>

                <span class="mt-3.5 text-sm font-semibold" :class="selectedCategory === cat.id ? 'text-white' : 'text-slate-200'">{{ cat.label }}</span>
                <span class="mt-1 text-[11px] leading-snug text-slate-500">{{ cat.desc }}</span>
              </button>
            </div>

            <div v-if="selectedCategory === 'bullying'" class="note-enter flex items-start gap-2.5 rounded-lg border border-rose-500/25 bg-rose-500/[0.05] px-3.5 py-3">
              <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-400/90" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <p class="text-[11px] leading-relaxed text-slate-400">
                Laporan perundungan <span class="font-medium text-rose-300">ditangani secara aman dan bertanggung jawab</span> oleh petugas berwenang. Identitas Anda akan dilindungi.
              </p>
            </div>
          </section>

          <!-- 02 · Detail spesifik per jenis (BARU — wajib diisi sesuai backend) -->
          <section class="space-y-3.5 border-t border-slate-800/70 pt-6">
            <div class="flex items-center gap-2.5">
              <span class="font-mono text-[11px] font-semibold text-emerald-500/80">02</span>
              <h2 class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Detail Tambahan</h2>
            </div>

            <!-- Fasilitas -->
            <div v-if="selectedCategory === 'fasilitas'" class="space-y-3.5">
              <div class="space-y-1.5">
                <label class="text-[11px] font-medium text-slate-400">Lokasi Kerusakan</label>
                <input
                  v-model="location"
                  v-bind="locationAttrs"
                  type="text"
                  placeholder="Contoh: Toilet Lantai 2 Gedung B"
                  class="w-full rounded-lg border bg-slate-950/60 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-600 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  :class="errors.location ? 'border-rose-500/60' : 'border-slate-800'"
                />
                <p v-if="errors.location" class="text-[11px] font-medium text-rose-400">{{ errors.location }}</p>
              </div>

              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
  <!-- Kategori Fasilitas -->
  <div class="space-y-1.5">
    <label class="text-[11px] font-medium text-slate-400">Kategori Fasilitas</label>
    <div class="relative">
      <!-- Icon Filter/Kategori Kiri -->
      <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
      </svg>

      <!-- Select Dropdown -->
      <select
        v-model="facilityCategory"
        class="w-full cursor-pointer appearance-none rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
        :class="errors.facilityCategory ? 'border-rose-500/60' : 'border-slate-800'"
      >
        <!-- Placeholder (Diisi default & tidak bisa dipilih kembali) -->
        <option value="" disabled selected hidden class="bg-slate-900 text-slate-500">
          Pilih kategori
        </option>
        <option
          v-for="opt in FACILITY_CATEGORIES"
          :key="opt.value"
          :value="opt.value"
          class="bg-slate-900 text-slate-100"
        >
          {{ opt.label }}
        </option>
      </select>

      <!-- Icon Chevron Panah Kanan -->
      <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </div>

    <!-- Pesan Error Validation -->
    <p v-if="errors.facilityCategory" class="text-[11px] font-medium text-rose-400">
      {{ errors.facilityCategory }}
    </p>
  </div>

  <!-- Tingkat Kerusakan (Opsional) -->
  <div class="space-y-1.5">
    <label class="text-[11px] font-medium text-slate-400">
      Tingkat Kerusakan <span class="text-slate-600">(opsional)</span>
    </label>
    <div class="relative">
      <!-- Icon Indicator/Gauge Kiri -->
      <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>

      <!-- Select Dropdown -->
      <select
        v-model="damageLevel"
        class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
      >
        <!-- Placeholder Opsional (Bisa dipilih kembali untuk mengosongkan pilihan) -->
        <option
          v-for="opt in DAMAGE_LEVELS"
          :key="opt.value"
          :value="opt.value"
          class="bg-slate-900 text-slate-100"
        >
          {{ opt.label }}
        </option>
      </select>

      <!-- Icon Chevron Panah Kanan -->
      <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </div>
  </div>
</div>
            </div>

            <!-- Aspirasi -->
<div v-else-if="selectedCategory === 'aspirasi'" class="space-y-1.5">
  <label class="text-[11px] font-medium text-slate-400">Kategori Aspirasi</label>
  <div class="relative">
    <!-- Icon Filter/Kategori Kiri -->
    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
    </svg>

    <!-- Select Dropdown -->
    <select
      v-model="aspirationCategory"
      class="w-full cursor-pointer appearance-none rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
      :class="errors.aspirationCategory ? 'border-rose-500/60' : 'border-slate-800'"
    >
      <option
        v-for="opt in ASPIRATION_CATEGORIES"
        :key="opt.value"
        :value="opt.value"
        class="bg-slate-900 text-slate-100"
      >
        {{ opt.label }}
      </option>
    </select>

    <!-- Icon Chevron Panah Kanan -->
    <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
    </svg>
  </div>

  <!-- Pesan Error Validation -->
  <p v-if="errors.aspirationCategory" class="text-[11px] font-medium text-rose-400">
    {{ errors.aspirationCategory }}
  </p>
</div>

            <!-- Bullying -->
            <div v-else class="space-y-3.5">
              <div class="space-y-1.5">
  <label class="text-[11px] font-medium text-slate-400">
    Posisi Anda
  </label>

  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
    <!-- Opsi 1: Korban langsung -->
    <label
      class="group relative flex cursor-pointer items-center justify-between rounded-lg border p-3 transition-all duration-200"
      :class="reporterRelation === 'victim' 
        ? 'border-rose-500/50 bg-rose-500/[0.06] ring-1 ring-rose-500/30' 
        : 'border-slate-800 bg-slate-950/60 hover:border-slate-700 hover:bg-slate-900/60'"
    >
      <input 
        type="radio" 
        value="victim" 
        v-model="reporterRelation" 
        class="sr-only" 
      />
      <div class="flex items-center gap-2.5">
        <!-- Icon Korban -->
        <span 
          class="flex h-7 w-7 items-center justify-center rounded-md border transition-colors duration-200"
          :class="reporterRelation === 'victim' ? 'border-rose-500/40 bg-rose-500/15 text-rose-400' : 'border-slate-800 bg-slate-900 text-slate-500 group-hover:text-slate-400'"
        >
          <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </span>
        <span class="text-xs font-semibold" :class="reporterRelation === 'victim' ? 'text-slate-100' : 'text-slate-300'">
          Korban langsung
        </span>
      </div>

      <!-- Custom Radio Indicator Ring -->
      <span
        class="flex h-4 w-4 items-center justify-center rounded-full border transition-colors duration-200"
        :class="reporterRelation === 'victim' ? 'border-rose-400 bg-rose-500' : 'border-slate-700 bg-slate-900'"
      >
        <span v-if="reporterRelation === 'victim'" class="h-1.5 w-1.5 rounded-full bg-slate-950"></span>
      </span>
    </label>

    <!-- Opsi 2: Saksi -->
    <label
      class="group relative flex cursor-pointer items-center justify-between rounded-lg border p-3 transition-all duration-200"
      :class="reporterRelation === 'witness' 
        ? 'border-rose-500/50 bg-rose-500/[0.06] ring-1 ring-rose-500/30' 
        : 'border-slate-800 bg-slate-950/60 hover:border-slate-700 hover:bg-slate-900/60'"
    >
      <input 
        type="radio" 
        value="witness" 
        v-model="reporterRelation" 
        class="sr-only" 
      />
      <div class="flex items-center gap-2.5">
        <!-- Icon Saksi -->
        <span 
          class="flex h-7 w-7 items-center justify-center rounded-md border transition-colors duration-200"
          :class="reporterRelation === 'witness' ? 'border-rose-500/40 bg-rose-500/15 text-rose-400' : 'border-slate-800 bg-slate-900 text-slate-500 group-hover:text-slate-400'"
        >
          <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </span>
        <span class="text-xs font-semibold" :class="reporterRelation === 'witness' ? 'text-slate-100' : 'text-slate-300'">
          Saksi
        </span>
      </div>

      <!-- Custom Radio Indicator Ring -->
      <span
        class="flex h-4 w-4 items-center justify-center rounded-full border transition-colors duration-200"
        :class="reporterRelation === 'witness' ? 'border-rose-400 bg-rose-500' : 'border-slate-700 bg-slate-900'"
      >
        <span v-if="reporterRelation === 'witness'" class="h-1.5 w-1.5 rounded-full bg-slate-950"></span>
      </span>
    </label>
  </div>

  <!-- Pesan Error Validation -->
  <p v-if="errors.reporterRelation" class="text-[11px] font-medium text-rose-400">
    {{ errors.reporterRelation }}
  </p>
</div>

              <div class="space-y-1.5">
                <label class="text-[11px] font-medium text-slate-400">Tanggal Kejadian <span class="text-slate-600">(opsional)</span></label>
                <input
                  v-model="incidentDate"
                  type="date"
                  :max="new Date().toISOString().split('T')[0]"
                  class="sapa-date-input w-full rounded-lg border border-slate-800 bg-slate-950/60 px-3.5 py-2.5 text-sm text-slate-100 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                />
              </div>
            </div>
          </section>

          <!-- 02 · Judul (disembunyikan untuk bullying, backend generate otomatis) -->
          <section v-if="selectedCategory !== 'bullying'" class="space-y-3.5 border-t border-slate-800/70 pt-6">
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-2.5">
                <span class="font-mono text-[11px] font-semibold text-emerald-500/80">02</span>
                <label for="report-title" class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Judul Laporan</label>
              </div>
              <span
                class="font-mono text-[10px] tabular-nums"
                :class="(title?.length || 0) >= 10 ? 'text-emerald-500/80' : 'text-slate-600'"
                title="Minimal 10 karakter"
              >{{ title?.length || 0 }} karakter</span>
            </div>

            <div class="space-y-1.5">
              <input
                id="report-title"
                v-model="title"
                v-bind="titleAttrs"
                type="text"
                placeholder="Contoh: AC Rusak / Usulan Event Coding"
                class="w-full rounded-lg border bg-slate-950/60 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-600 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                :class="errors.title ? 'border-rose-500/60' : 'border-slate-800'"
              />
              <p v-if="errors.title" class="flex items-center gap-1.5 text-[11px] font-medium text-rose-400">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                {{ errors.title }}
              </p>
            </div>
          </section>

          <!-- 03 · Isi laporan -->
          <section class="space-y-3.5 border-t border-slate-800/70 pt-6">
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-2.5">
                <span class="font-mono text-[11px] font-semibold text-emerald-500/80">03</span>
                <label for="report-content" class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Isi Laporan / Detail Informasi</label>
              </div>
              <span
                class="font-mono text-[10px] tabular-nums"
                :class="(content?.length || 0) >= 20 ? 'text-emerald-500/80' : 'text-slate-600'"
                title="Minimal 20 karakter"
              >{{ content?.length || 0 }} karakter</span>
            </div>

            <div class="space-y-1.5">
              <textarea
                id="report-content"
                v-model="content"
                v-bind="contentAttrs"
                rows="6"
                placeholder="Jelaskan secara detail kronologi, lokasi, atau ide masukan Anda..."
                class="w-full resize-none rounded-lg border bg-slate-950/60 px-3.5 py-2.5 text-sm leading-relaxed text-slate-100 placeholder-slate-600 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                :class="errors.content ? 'border-rose-500/60' : 'border-slate-800'"
              ></textarea>

              <p v-if="errors.content" class="flex items-center gap-1.5 text-[11px] font-medium text-rose-400">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                {{ errors.content }}
              </p>
              <p v-else class="text-[11px] text-slate-500">{{ contentHint }}</p>
            </div>
          </section>

          <!-- 04 · Lampiran (multi-file, max 3) — disembunyikan untuk aspirasi (backend tidak dukung) -->
          <section v-if="selectedCategory !== 'aspirasi'" class="space-y-3.5 border-t border-slate-800/70 pt-6">
            <div class="flex items-center gap-2.5">
              <span class="font-mono text-[11px] font-semibold text-emerald-500/80">04</span>
              <h2 class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Lampiran Bukti <span class="font-normal normal-case tracking-normal text-slate-600">(opsional, maks 3 berkas)</span></h2>
            </div>

            <input
              id="report-image-input"
              ref="fileInputRef"
              type="file"
              accept="image/*,application/pdf"
              multiple
              class="hidden"
              @change="handleImageChange"
            />

            <label
              v-if="imageFiles.length < MAX_FILES"
              for="report-image-input"
              class="flex h-32 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 text-center transition-all duration-200"
              :class="isDragging ? 'border-emerald-500/70 bg-emerald-500/[0.06]' : 'border-slate-700/80 bg-slate-950/40 hover:border-emerald-500/50 hover:bg-slate-950/70'"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="onDrop"
            >
              <div class="pointer-events-none flex flex-col items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-700 bg-slate-900 text-slate-400">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <p class="mt-3 text-xs text-slate-400"><span class="font-semibold text-emerald-400">Klik untuk unggah</span> atau seret berkas ke sini</p>
                <p class="mt-1 text-[10px] text-slate-600">PNG, JPG, JPEG, PDF · Maks. 2MB per berkas · Maks. {{ MAX_FILES }} berkas</p>
              </div>
            </label>

            <div v-if="imagePreviews.length" class="space-y-2.5">
              <div
                v-for="(preview, index) in imagePreviews"
                :key="index"
                class="note-enter flex items-center gap-4 rounded-xl border border-slate-800 bg-slate-950/40 p-3.5"
              >
                <img v-if="preview.isImage" :src="preview.url" alt="Pratinjau lampiran" class="h-16 w-16 shrink-0 rounded-lg border border-slate-800 object-cover" />
                <div v-else class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-900 text-slate-500 text-[10px] font-semibold">PDF</div>

                <div class="min-w-0 flex-1">
                  <p class="truncate text-xs font-medium text-slate-200">{{ preview.name }}</p>
                  <p class="mt-0.5 font-mono text-[10px] text-slate-500">{{ formatFileSize(imageFiles[index]?.size) }} · siap diunggah</p>
                </div>

                <button
                  type="button"
                  @click="removeImage(index)"
                  class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-1.5 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-rose-500/40 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60"
                >
                  <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Hapus
                </button>
              </div>
            </div>
          </section>

          <!-- 05 · Identitas -->
          <section class="space-y-3.5 border-t border-slate-800/70 pt-6">
            <div class="flex items-center gap-2.5">
              <span class="font-mono text-[11px] font-semibold text-emerald-500/80">05</span>
              <h2 class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Identitas Pelapor</h2>
            </div>

            <label
              for="anonymous"
              class="flex cursor-pointer select-none items-center gap-4 rounded-xl border p-4 transition-colors duration-200"
              :class="is_anonymous ? 'border-emerald-500/30 bg-emerald-500/[0.05]' : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'"
            >
              <input id="anonymous" v-model="is_anonymous" type="checkbox" class="peer sr-only" />

              <span
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border transition-colors duration-200"
                :class="is_anonymous ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-400' : 'border-slate-700 bg-slate-900 text-slate-500'"
              >
                <svg v-if="is_anonymous" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </span>

              <span class="min-w-0 flex-1">
                <span class="block text-xs font-semibold text-slate-100">Kirim sebagai Anonim</span>
                <span class="mt-1 block text-[11px] leading-relaxed text-slate-500">
                  {{ is_anonymous
                    ? 'Identitas Anda disembunyikan dari tampilan publik, namun tetap tercatat untuk keperluan verifikasi dan tindak lanjut.'
                    : 'Nama dan kelas Anda akan tertera pada laporan. Identitas tetap hanya dapat diakses oleh petugas berwenang.' }}
                </span>
              </span>

              <span
                class="relative h-5 w-9 shrink-0 rounded-full transition-colors duration-200 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400/60 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-slate-900"
                :class="is_anonymous ? 'bg-emerald-500' : 'bg-slate-700'"
              >
                <span
                  class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200"
                  :class="is_anonymous ? 'translate-x-4' : 'translate-x-0'"
                ></span>
              </span>
            </label>
          </section>

          <!-- Aksi -->
          <section class="border-t border-slate-800/70 pt-6">
            <div class="flex flex-col-reverse gap-4 sm:flex-row sm:items-center sm:justify-between">
              <p class="max-w-xs text-[11px] leading-relaxed text-slate-600">
                Dengan mengirim laporan, Anda menyatakan bahwa informasi yang disampaikan benar dan bertanggung jawab.
              </p>

              <div class="flex flex-col gap-2.5 sm:flex-row">
                <button
                  type="button"
                  @click="router.back()"
                  class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
                >
                  Batal
                </button>

                <button
                  type="submit"
                  :disabled="isLoading"
                  class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                >
                  <svg v-if="!isLoading" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                  </svg>
                  <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                  {{ isLoading ? 'Memproses...' : 'Kirim Laporan' }}
                </button>
              </div>
            </div>
          </section>
        </form>

        <!-- ===== Sidebar panduan ===== -->
        <aside class="space-y-5 lg:sticky lg:top-20">
          <div class="fade-up rounded-xl border border-slate-800 bg-slate-900 p-5" style="animation-delay: 150ms">
            <div class="flex items-center justify-between gap-3">
              <h3 class="text-sm font-bold tracking-tight text-slate-100">Panduan Pengisian</h3>
              <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2 py-0.5 text-[10px] font-medium" :class="activeStyle.guideBadge">
                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                {{ activeGuide.label }}
              </span>
            </div>

            <ul class="mt-4 space-y-3">
              <li v-for="(tip, i) in activeGuide.tips" :key="i" class="flex gap-2.5">
                <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" :class="activeStyle.guideIcon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <p class="text-[11px] leading-relaxed text-slate-400">{{ tip }}</p>
              </li>
            </ul>
          </div>

          <div v-if="selectedCategory === 'bullying'" class="note-enter flex items-start gap-3 rounded-xl border border-amber-500/25 bg-amber-500/[0.05] p-4">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-400/90" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <div class="min-w-0">
              <p class="text-xs font-semibold text-amber-300">Butuh pertolongan segera?</p>
              <p class="mt-1 text-[11px] leading-relaxed text-slate-500">Jika Anda atau teman Anda berada dalam bahaya langsung, segera hubungi Guru BK atau orang dewasa yang Anda percaya.</p>
            </div>
          </div>

          <div class="fade-up rounded-xl border border-slate-800 bg-slate-900 p-5" style="animation-delay: 200ms">
            <h3 class="text-sm font-bold tracking-tight text-slate-100">Alur Tindak Lanjut</h3>

            <ol class="mt-4 space-y-3">
              <li v-for="(step, i) in followUpSteps" :key="step.label" class="flex items-center gap-2.5">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="step.dot" aria-hidden="true"></span>
                <p class="text-[11px] text-slate-400">
                  <span class="font-medium text-slate-300">{{ step.label }}</span>
                  <span class="text-slate-600"> — {{ step.desc }}</span>
                </p>
              </li>
            </ol>

            <p class="mt-4 flex items-start gap-1.5 border-t border-slate-800/70 pt-3 text-[11px] leading-relaxed text-slate-600">
              <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              Identitas pelapor hanya dapat diakses oleh petugas berwenang.
            </p>
          </div>
        </aside>
      </div>
    </main>

    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="text-[11px] text-slate-600">Setiap laporan dijaga kerahasiaannya</p>
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
.note-enter {
  animation: note-in 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes note-in {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}
.chip-enter-active,
.chip-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.chip-enter-from,
.chip-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .note-enter { animation: none; opacity: 1; }
  .chip-enter-active,
  .chip-leave-active { transition: none; }
}

/* Styling Ikon Picker Kalender Bawaan Browser */
.sapa-date-input::-webkit-calendar-picker-indicator {
  filter: invert(0.8) sepia(0) saturate(0) hue-rotate(0deg) brightness(0.9);
  cursor: pointer;
  opacity: 0.5;
  transition: opacity 0.2s ease;
}

.sapa-date-input::-webkit-calendar-picker-indicator:hover {
  opacity: 1;
}
</style>