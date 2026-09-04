<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Data dummy khusus aspirasi (4 data, semua Selesai)
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

const searchKeyword = ref('')
const activeTab = ref('all') // State untuk filter tab: 'all' atau 'liked'

// Hitung Statistik Otomatis
const totalAspirations = computed(() => aspirations.value.length)
const totalVotes = computed(() => aspirations.value.reduce((acc, curr) => acc + curr.likesCount, 0))

// Toggle fungsi Like
const toggleLike = (item) => {
  if (item.isLiked) {
    item.likesCount--
    item.isLiked = false
  } else {
    item.likesCount++
    item.isLiked = true
  }
}

// Filter berdasarkan Pencarian Kata Kunci & Tab Status Like
const filteredAspirations = computed(() => {
  return aspirations.value.filter(item => {
    // Match berdasarkan keyword
    const matchesSearch = item.title.toLowerCase().includes(searchKeyword.value.toLowerCase()) || 
                          item.content.toLowerCase().includes(searchKeyword.value.toLowerCase())
    
    // Match berdasarkan tab filter
    const matchesTab = activeTab.value === 'all' || (activeTab.value === 'liked' && item.isLiked)

    return matchesSearch && matchesTab
  })
})

// Badges Style berdasarkan Status
const getStatusBadge = (status) => {
  switch (status) {
    case 'Diproses': return 'bg-amber-500/10 text-amber-400 border-amber-500/30'
    case 'Ditinjau': return 'bg-blue-500/10 text-blue-400 border-blue-500/30'
    case 'Selesai': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
    default: return 'bg-slate-500/10 text-slate-400 border-slate-500/30'
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">
    
    <!-- Top Bar Navigation -->
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
          <!-- Tombol Kembali -->
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
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      
      <!-- Headline & Subtitle -->
      <section class="mb-8">
        <div class="flex items-center gap-3 mb-2">
          <span class="h-6 w-[4px] rounded-full bg-emerald-500"></span>
          <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Umpan Aspirasi</h1>
        </div>
        <p class="text-sm text-slate-400 max-w-2xl">
          Wadah terbuka bagi seluruh siswa untuk menyampaikan gagasan dan inovasi. Berikan dukungan pada usulan yang menurut Anda penting agar dapat diprioritaskan oleh pihak sekolah.
        </p>
      </section>

      <!-- Control Bar: Search Input & Tab Filter Disukai -->
      <section class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        
        <!-- Search Input -->
        <div class="relative w-full sm:w-80">
          <svg class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input 
            v-model="searchKeyword"
            type="text" 
            placeholder="Cari kata kunci aspirasi..." 
            class="w-full rounded-xl border border-slate-800 bg-slate-900/80 pl-10 pr-4 py-2 text-xs text-slate-100 placeholder-slate-500 transition focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
          />
        </div>

        <!-- Filter Tab Buttons -->
        <div class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900/80 p-1 self-start sm:self-auto">
          <button
            type="button"
            @click="activeTab = 'all'"
            class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-all"
            :class="activeTab === 'all' 
              ? 'bg-slate-800 text-white shadow-sm' 
              : 'text-slate-400 hover:text-slate-200'"
          >
            Semua
          </button>
          
          <button
            type="button"
            @click="activeTab = 'liked'"
            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all"
            :class="activeTab === 'liked' 
              ? 'bg-red-500/10 text-red-400 border border-red-500/30 shadow-sm' 
              : 'text-slate-400 hover:text-slate-200'"
          >
            <svg 
              class="h-3.5 w-3.5" 
              :class="activeTab === 'liked' ? 'fill-red-400 stroke-red-400' : 'fill-none stroke-current'" 
              stroke-width="2" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <span>Disukai</span>
          </button>
        </div>

      </section>

      <!-- Feed Grid -->
      <div v-if="filteredAspirations.length > 0" class="grid grid-cols-1 gap-5 md:grid-cols-2">
        
        <article 
          v-for="item in filteredAspirations" 
          :key="item.id"
          class="relative flex flex-col justify-between rounded-xl border border-slate-800 bg-slate-900 p-5 transition-all hover:border-slate-700/80"
        >
          <!-- Card Header -->
          <div>
            <div class="flex items-start justify-between gap-3">
              <span class="inline-flex items-center rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-semibold tracking-wider uppercase text-emerald-400">
                Aspirasi
              </span>

              <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-medium" :class="getStatusBadge(item.status)">
                {{ item.status }}
              </span>
            </div>

            <!-- Title & Content -->
            <h3 class="mt-3 text-base font-bold text-white tracking-tight leading-snug">{{ item.title }}</h3>
            <p class="mt-2 text-xs leading-relaxed text-slate-400">{{ item.content }}</p>
          </div>

          <!-- Card Footer & Actions -->
          <div class="mt-6 border-t border-slate-800/80 pt-4 flex items-center justify-between">
            
            <!-- Author Info -->
            <div class="flex items-center gap-2.5">
              <div class="flex h-7 w-7 items-center justify-center rounded-full border border-slate-700 bg-slate-800 text-slate-300 font-semibold text-[10px]">
                {{ item.isAnonymous ? '?' : item.author.charAt(0) }}
              </div>
              <div class="flex flex-col">
                <span class="text-xs font-medium text-slate-200 leading-none">
                  {{ item.isAnonymous ? 'Anonim' : item.author }}
                </span>
                <span class="text-[10px] text-slate-500 mt-0.5">
                  {{ item.isAnonymous ? item.createdAt : `${item.class} • ${item.createdAt}` }}
                </span>
              </div>
            </div>

            <!-- Like / Upvote Button -->
            <button 
              @click="toggleLike(item)"
              class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all active:scale-[.95]"
              :class="item.isLiked 
                ? 'border-red-500/50 bg-red-500/10 text-red-500' 
                : 'border-slate-800 bg-slate-950/60 text-slate-400 hover:border-slate-700 hover:text-slate-200'"
            >
              <svg 
                class="h-4 w-4 transition-transform active:scale-125" 
                :class="item.isLiked ? 'fill-red-500 stroke-red-500' : 'fill-none stroke-slate-400'"
                stroke-width="2" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
              <span>{{ item.likesCount }}</span>
            </button>
          </div>
        </article>

      </div>

      <!-- State Jika Data Kosong -->
      <div v-else class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-800 bg-slate-900/40 py-12 text-center">
        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <p class="mt-3 text-sm font-semibold text-slate-300">
          {{ activeTab === 'liked' ? 'Belum ada aspirasi yang disukai' : 'Aspirasi tidak ditemukan' }}
        </p>
        <p class="mt-1 text-xs text-slate-500">
          {{ activeTab === 'liked' ? 'Sukai beberapa aspirasi terlebih dahulu untuk melihatnya di sini.' : 'Coba ubah kata kunci pencarian Anda.' }}
        </p>
      </div>

    </main>
  </div>
</template>