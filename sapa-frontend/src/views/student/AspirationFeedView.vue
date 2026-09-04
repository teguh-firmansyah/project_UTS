<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

/* ---------------------------------- */
/* Data dummy aspirasi (existing)     */
/* ---------------------------------- */
const aspirations = ref([
  {
    id: 1,
    title: 'Pengadaan Event Hackathon Internal Sekolah',
    content: 'Usul diadakan lomba coding internal antar jurusan untuk mengasah skill dan portofolio siswa sebelum PKL.',
    category: 'Aspirasi',
    author: 'Siswa Anonim',
    class: '-',
    isAnonymous: true,
    createdAt: '1 hari yang lalu',
    likesCount: 42,
    isLiked: true,
    status: 'Selesai'
  },
  {
    id: 2,
    title: 'Penyediaan Loker Penyimpanan Buku di Kelas',
    content: 'Siswa sering membawa buku pelajaran yang terlalu berat setiap hari. Mohon disediakan loker di belakang kelas.',
    category: 'Aspirasi',
    author: 'Budi Santoso',
    class: 'XI RPL 2',
    isAnonymous: false,
    createdAt: '2 hari yang lalu',
    likesCount: 18,
    isLiked: false,
    status: 'Selesai'
  },
  {
    id: 3,
    title: 'Penambahan Bandwidth Wi-Fi Perpustakaan',
    content: 'Koneksi Wi-Fi di area perpustakaan sering lambat saat jam istirahat. Mohon ditingkatkan kecepatannya untuk mendukung kegiatan belajar.',
    category: 'Aspirasi',
    author: 'Siti Aminah',
    class: 'XII TKJ 1',
    isAnonymous: false,
    createdAt: '3 hari yang lalu',
    likesCount: 35,
    isLiked: true,
    status: 'Selesai'
  },
  {
    id: 4,
    title: 'Peremajaan Peralatan Olahraga Lapangan',
    content: 'Bola basket dan bola voli di ruang olahraga sudah banyak yang aus. Mohon ada pembaruan fasilitas alat olahraga.',
    category: 'Aspirasi',
    author: 'Siswa Anonim',
    class: '-',
    isAnonymous: true,
    createdAt: '5 hari yang lalu',
    likesCount: 29,
    isLiked: false,
    status: 'Selesai'
  }
])

/* ---------------------------------- */
/* State filter (existing) + urutan   */
/* ---------------------------------- */
const searchKeyword = ref('')
const activeTab = ref('all') // 'all' | 'liked'
const sortMode = ref('latest') // 'latest' | 'top' — tambahan

/* ---------------------------------- */
/* Statistik (computed existing,      */
/* kini ditampilkan di hero)          */
/* ---------------------------------- */
const totalAspirations = computed(() => aspirations.value.length)
const totalVotes = computed(() => aspirations.value.reduce((acc, curr) => acc + curr.likesCount, 0))
const likedCount = computed(() => aspirations.value.filter(a => a.isLiked).length)

const statItems = computed(() => [
  {
    label: 'Total Aspirasi',
    value: totalAspirations.value,
    icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    tile: 'border-purple-500/25 bg-purple-500/10 text-purple-400',
    num: 'text-white',
  },
  {
    label: 'Total Dukungan',
    value: totalVotes.value,
    icon: 'M12 19V5M5 12l7-7 7 7',
    tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
    num: 'text-emerald-400',
  },
  {
    label: 'Anda Dukung',
    value: likedCount.value,
    icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    tile: 'border-purple-500/25 bg-purple-500/10 text-purple-400',
    num: 'text-purple-400',
  },
])

/* ---------------------------------- */
/* Toggle like (behavior existing)    */
/* ---------------------------------- */
const toggleLike = (item) => {
  if (item.isLiked) {
    item.likesCount--
    item.isLiked = false
  } else {
    item.likesCount++
    item.isLiked = true
  }
}

/* ---------------------------------- */
/* Filter (logika existing)           */
/* ---------------------------------- */
const filteredAspirations = computed(() => {
  return aspirations.value.filter(item => {
    const matchesSearch = item.title.toLowerCase().includes(searchKeyword.value.toLowerCase()) ||
                          item.content.toLowerCase().includes(searchKeyword.value.toLowerCase())
    const matchesTab = activeTab.value === 'all' || (activeTab.value === 'liked' && item.isLiked)
    return matchesSearch && matchesTab
  })
})

/* Urutan tampilan: terbaru (urutan data) / paling didukung */
const visibleAspirations = computed(() => {
  if (sortMode.value === 'top') {
    return [...filteredAspirations.value].sort((a, b) => b.likesCount - a.likesCount)
  }
  return filteredAspirations.value
})

const hasActiveFilters = computed(() => searchKeyword.value.trim() !== '' || activeTab.value !== 'all')
const clearFilters = () => {
  searchKeyword.value = ''
  activeTab.value = 'all'
}

/* ---------------------------------- */
/* Badge status — warna diselaraskan  */
/* dengan sistem (Diproses=emerald,   */
/* Selesai=slate) + Menunggu/Ditolak  */
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

/* ---------------------------------- */
/* Turunan tampilan                   */
/* ---------------------------------- */
const initialsOf = (name) => {
  const n = (name || '').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/)
  return (parts.length > 1 ? parts[0][0] + parts[parts.length - 1][0] : n.slice(0, 2)).toUpperCase()
}

/* CTA langsung membuka kanal aspirasi pada form buat laporan */
const goToCreateAspiration = () => {
  router.push({ path: '/reports/new', query: { type: 'aspirasi' } })
}

/* Placeholder foto — ganti dengan aset sekolah bila tersedia */
const heroPhoto = 'https://picsum.photos/seed/sapaaspirasi/1600/900.jpg'
const logoFailed = ref(false)
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
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- ===== Hero kanal aspirasi ===== -->
      <section class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <!-- Foto latar: lingkungan belajar, digelapkan & desaturasi -->
        <img :src="heroPhoto" alt="" aria-hidden="true" draggable="false"
             class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover opacity-20 grayscale contrast-125 brightness-[.65]" />
        <div class="pointer-events-none absolute inset-0 bg-slate-950/60" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/30 to-transparent" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0" aria-hidden="true"
             style="background: radial-gradient(900px 400px at 12% 0%, rgba(168, 85, 247, 0.13), transparent 65%)"></div>
        <span class="absolute inset-x-0 top-0 z-10 h-[2px] bg-gradient-to-r from-purple-500/70 via-purple-500/20 to-transparent" aria-hidden="true"></span>

        <div class="relative z-10 p-6 sm:p-8 lg:p-9">
          <div class="flex items-center gap-2.5">
            <span class="relative flex h-2 w-2">
              <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-purple-400 opacity-60"></span>
              <span class="relative inline-flex h-2 w-2 rounded-full bg-purple-500"></span>
            </span>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-purple-300/90">Kanal Aspirasi · Terbuka untuk Semua Siswa</p>
          </div>

          <h1 class="mt-4 text-2xl font-extrabold leading-tight tracking-tight text-white sm:text-3xl">
            Umpan <span class="text-purple-400">Aspirasi</span>
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400">
            Wadah terbuka bagi seluruh siswa untuk menyampaikan gagasan dan inovasi.
            Berikan dukungan pada usulan yang menurut Anda penting agar dapat diprioritaskan oleh pihak sekolah.
          </p>

          <!-- Strip statistik -->
          <div class="mt-7 grid grid-cols-3 gap-3 border-t border-slate-800/70 pt-5 sm:gap-4">
            <div v-for="s in statItems" :key="s.label" class="flex items-center gap-2.5 sm:gap-3">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border sm:h-9 sm:w-9" :class="s.tile">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" :d="s.icon" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-base font-extrabold leading-none tracking-tight tabular-nums sm:text-xl" :class="s.num">{{ s.value }}</p>
                <p class="mt-1 truncate text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-500 sm:text-[10px]">{{ s.label }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Toolbar: cari, urut, filter ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 90ms">

        <div class="flex flex-col gap-3 sm:flex-row">
          <!-- Pencarian -->
          <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
            </svg>
            <input
              v-model="searchKeyword"
              type="text"
              aria-label="Cari aspirasi"
              placeholder="Cari kata kunci aspirasi..."
              class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-purple-500/60 focus:outline-none focus:ring-2 focus:ring-purple-500/15"
            />
            <button
              v-if="searchKeyword"
              type="button"
              @click="searchKeyword = ''"
              aria-label="Bersihkan pencarian"
              class="absolute right-2.5 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-500 transition-colors duration-150 hover:bg-slate-800 hover:text-slate-200"
            >
              <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Urutan -->
          <div class="relative w-full sm:w-56">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7-7-7 7M5 15l7 7 7-7" />
            </svg>
            <select
              v-model="sortMode"
              aria-label="Urutkan aspirasi"
              class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-purple-500/60 focus:outline-none focus:ring-2 focus:ring-purple-500/15"
            >
              <option value="latest" class="bg-slate-900 text-slate-100">Terbaru</option>
              <option value="top" class="bg-slate-900 text-slate-100">Paling Didukung</option>
            </select>
            <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>

        <!-- Pil filter + ringkasan -->
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            :aria-pressed="activeTab === 'all'"
            @click="activeTab = 'all'"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
            :class="activeTab === 'all' ? 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' : 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80" aria-hidden="true"></span>
            Semua
            <span class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500">{{ totalAspirations }}</span>
          </button>

          <button
            type="button"
            :aria-pressed="activeTab === 'liked'"
            @click="activeTab = 'liked'"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400/50"
            :class="activeTab === 'liked' ? 'border-purple-500/50 bg-purple-500/15 text-purple-400' : 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80" aria-hidden="true"></span>
            Didukung
            <span class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500">{{ likedCount }}</span>
          </button>

          <div class="ml-auto flex items-center gap-3 pl-2">
            <p class="whitespace-nowrap text-[11px] text-slate-500">
              Menampilkan <span class="font-semibold tabular-nums text-slate-300">{{ visibleAspirations.length }}</span> dari {{ totalAspirations }} aspirasi
            </p>
            <button
              v-if="hasActiveFilters"
              type="button"
              @click="clearFilters"
              class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-[11px] font-semibold text-slate-400 transition-all duration-150 hover:border-purple-500/40 hover:text-purple-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400/50"
            >
              Atur ulang
              <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </section>

      <!-- ===== Grid umpan ===== -->
      <section v-if="visibleAspirations.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="(item, i) in visibleAspirations"
          :key="item.id"
          class="card-enter group relative flex flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-5 transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-500/30 hover:shadow-lg hover:shadow-black/25"
          :style="{ animationDelay: (i * 60) + 'ms' }"
        >
          <!-- Aksen kanal, muncul saat hover -->
          <span class="absolute bottom-4 left-0 top-4 w-[3px] rounded-r-full bg-purple-500/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100" aria-hidden="true"></span>

          <!-- Badges -->
          <div class="flex items-start justify-between gap-3">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-purple-500/20 bg-purple-500/10 px-2.5 py-0.5 text-[11px] font-medium text-purple-400">
              <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
              Aspirasi
            </span>
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium" :class="getStatusBadge(item.status)">
              <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
              {{ item.status }}
            </span>
          </div>

          <!-- Judul + isi -->
          <div class="flex-1">
            <h3 class="mt-4 text-sm font-bold leading-snug tracking-tight text-white">{{ item.title }}</h3>
            <p class="clamp-3 mt-2 text-xs leading-relaxed text-slate-400">{{ item.content }}</p>
          </div>

          <!-- Footer: pelapor + dukungan -->
          <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-800/70 pt-4">
            <div class="flex min-w-0 items-center gap-2.5">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-purple-500/30 bg-purple-500/10 text-[10px] font-bold text-purple-400">
                <svg v-if="item.isAnonymous" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <template v-else>{{ initialsOf(item.author) }}</template>
              </div>
              <div class="min-w-0">
                <p class="truncate text-xs font-semibold text-slate-200">{{ item.isAnonymous ? 'Siswa Anonim' : item.author }}</p>
                <p class="mt-0.5 truncate text-[10px] text-slate-500">{{ item.isAnonymous ? item.createdAt : item.class + ' · ' + item.createdAt }}</p>
              </div>
            </div>

            <!-- Tombol dukungan -->
            <button
              type="button"
              @click="toggleLike(item)"
              :aria-pressed="item.isLiked"
              :aria-label="item.isLiked ? 'Batalkan dukungan' : 'Dukung aspirasi ini'"
              :title="item.isLiked ? 'Batalkan dukungan' : 'Dukung aspirasi ini'"
              class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all duration-200 active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400/60"
              :class="item.isLiked
                ? 'border-purple-500/50 bg-purple-500/10 text-purple-400'
                : 'border-slate-800 bg-slate-950/60 text-slate-400 hover:border-purple-500/40 hover:text-purple-300'"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5M5 12l7-7 7 7" />
              </svg>
              <span class="tabular-nums">{{ item.likesCount }}</span>
            </button>
          </div>
        </article>
      </section>

      <!-- ===== Keadaan kosong ===== -->
      <div v-else class="fade-up flex flex-col items-center rounded-xl border border-dashed border-slate-800 bg-slate-900/40 px-6 py-14 text-center" style="animation-delay: 120ms">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
          <svg v-if="hasActiveFilters" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
          </svg>
          <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
        </div>

        <p class="mt-4 text-sm font-semibold text-slate-200">
          {{ activeTab === 'liked'
            ? 'Belum ada aspirasi yang Anda dukung'
            : hasActiveFilters
              ? 'Aspirasi tidak ditemukan'
              : 'Belum ada aspirasi' }}
        </p>
        <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
          {{ activeTab === 'liked'
            ? 'Dukung beberapa aspirasi terlebih dahulu untuk melihatnya di sini.'
            : hasActiveFilters
              ? 'Coba gunakan kata kunci lain atau atur ulang filter.'
              : 'Jadilah yang pertama menyampaikan usulan untuk kemajuan sekolah.' }}
        </p>

        <button
          v-if="hasActiveFilters"
          type="button"
          @click="clearFilters"
          class="mt-6 inline-flex items-center gap-1.5 rounded-lg border border-purple-500/30 bg-purple-500/10 px-4 py-2 text-xs font-semibold text-purple-400 transition-all duration-200 hover:bg-purple-500 hover:text-slate-950 active:scale-[.97]"
        >
          Atur Ulang Filter
        </button>
        <button
          v-else
          type="button"
          @click="goToCreateAspiration"
          class="mt-6 inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97]"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          Kirim Aspirasi Pertama
        </button>
      </div>
    </main>

    <!-- ============ Footer ============ -->
    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
          <svg class="h-3.5 w-3.5 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Aspirasi ditampilkan terbuka — identitas pelapor anonim tetap dilindungi
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
/* Entrance seksi: fade-up halus */
.fade-up {
  opacity: 0;
  animation: fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Entrance kartu: hanya opacity (agar hover lift tetap bekerja —
   animasi fill-mode akan mengunci transform bila ikut dianimasikan) */
.card-enter {
  opacity: 0;
  animation: card-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes card-in {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* Batasi isi kartu pada 3 baris — bebas dari versi Tailwind */
.clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .card-enter { animation: none; opacity: 1; }
}
</style>