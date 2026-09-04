<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// State user dari Pinia
const currentUser = computed(() => authStore.user || {})

// State Modal Edit Profil
const isEditModalOpen = ref(false)
const isSaving = ref(false)
const profileForm = ref({
  name: '',
  bio: '',
  class_name: ''
})

// Buka Modal & Isi Form
const openEditModal = () => {
  profileForm.value = {
    name: currentUser.value.name || '',
    bio: currentUser.value.bio || '',
    class_name: currentUser.value.class_name || currentUser.value.class || ''
  }
  isEditModalOpen.value = true
}

// Simpan Perubahan Profil
const handleSaveProfile = async () => {
  isSaving.value = true
  try {
    // Jika authStore punya method update, panggil di sini:
    if (authStore.updateProfile) {
      await authStore.updateProfile(profileForm.value)
    } else {
      // Fallback mutasi langsung ke state user
      authStore.user = { ...authStore.user, ...profileForm.value }
    }
    isEditModalOpen.value = false
  } catch (error) {
    console.error('Gagal memperbarui profil:', error)
  } finally {
    isSaving.value = false
  }
}

// Active Tab Aktivitas
const activeTab = ref('aspirations')

// Data Dummy Aktivitas
const myAspirations = ref([
  {
    id: 2,
    title: 'Penyediaan Loker Penyimpanan Buku di Kelas',
    content: 'Siswa sering membawa buku pelajaran yang terlalu berat setiap hari. Mohon disediakan loker di belakang kelas.',
    category: 'Aspirasi',
    createdAt: '2 hari yang lalu',
    likesCount: 18,
    status: 'Selesai'
  }
])

const myReports = ref([
  {
    id: 101,
    title: 'Kerusakan Proyektor di Ruang Kelas XI RPL 2',
    content: 'Proyektor mati total saat jam pelajaran produktif.',
    category: 'Fasilitas',
    createdAt: '5 hari yang lalu',
    status: 'Diproses'
  }
])

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
    
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
        <button
          type="button"
          @click="router.back()"
          class="group inline-flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-1.5 text-xs font-semibold text-slate-400 transition hover:border-slate-700 hover:text-slate-100 active:scale-95"
        >
          <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali</span>
        </button>

        <h1 class="text-sm font-semibold text-slate-200">Profil Saya</h1>
        <div class="w-16"></div>
      </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
      
      <section class="relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 p-6 sm:p-8 backdrop-blur-sm">
        
        <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-emerald-500/10 blur-3xl"></div>

        <div class="relative flex flex-col items-center sm:flex-row sm:items-start gap-6">
          
          <div class="relative shrink-0">
            <div class="flex h-24 w-24 items-center justify-center rounded-2xl border-2 border-emerald-500/40 bg-slate-800 text-3xl font-bold text-emerald-400 shadow-xl shadow-emerald-500/10">
              {{ currentUser.name ? currentUser.name.charAt(0).toUpperCase() : 'U' }}
            </div>
            <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-slate-950 ring-4 ring-slate-950" title="Akun Aktif">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
            </span>
          </div>

          <div class="flex-1 text-center sm:text-left">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div>
                <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                  {{ currentUser.name || 'Siswa SAPA' }}
                </h2>
                <p class="text-xs text-slate-400 font-mono mt-0.5">
                  {{ currentUser.email || 'email@sekolah.id' }}
                </p>
              </div>

              <button
                type="button"
                @click="openEditModal"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-200 transition hover:border-emerald-500/50 hover:bg-slate-700 hover:text-white active:scale-95"
              >
                <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                </svg>
                <span>Edit Profil</span>
              </button>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-center sm:justify-start gap-2">
              <span class="inline-flex items-center rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-400 capitalize">
                {{ currentUser.role || 'Siswa' }}
              </span>
              <span class="inline-flex items-center rounded-md border border-slate-800 bg-slate-950/80 px-2.5 py-1 text-xs font-medium text-slate-300">
                {{ currentUser.class_name || currentUser.class || 'Kelas belum diatur' }}
              </span>
            </div>

            <p class="mt-4 text-xs sm:text-sm text-slate-300 leading-relaxed max-w-2xl">
              {{ currentUser.bio || 'Belum ada bio yang ditambahkan.' }}
            </p>
          </div>
        </div>

        <div class="mt-8 grid grid-cols-3 gap-3 border-t border-slate-800/80 pt-6">
          <div class="text-center">
            <p class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Aspirasi</p>
            <p class="mt-1 text-xl sm:text-2xl font-black text-white">{{ myAspirations.length }}</p>
          </div>
          <div class="text-center border-x border-slate-800/80">
            <p class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Laporan</p>
            <p class="mt-1 text-xl sm:text-2xl font-black text-white">{{ myReports.length }}</p>
          </div>
          <div class="text-center">
            <p class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Dukungan</p>
            <p class="mt-1 text-xl sm:text-2xl font-black text-red-400 flex items-center justify-center gap-1">
              <span>18</span>
              <svg class="w-4 h-4 fill-red-400" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            </p>
          </div>
        </div>

      </section>

      <section class="mt-8">
        
        <div class="flex items-center gap-2 border-b border-slate-800 pb-3">
          <button
            type="button"
            @click="activeTab = 'aspirations'"
            class="rounded-lg px-3.5 py-2 text-xs font-semibold transition-all"
            :class="activeTab === 'aspirations' 
              ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' 
              : 'text-slate-400 hover:text-slate-200'"
          >
            Aspirasi Saya ({{ myAspirations.length }})
          </button>
          <button
            type="button"
            @click="activeTab = 'reports'"
            class="rounded-lg px-3.5 py-2 text-xs font-semibold transition-all"
            :class="activeTab === 'reports' 
              ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' 
              : 'text-slate-400 hover:text-slate-200'"
          >
            Laporan Saya ({{ myReports.length }})
          </button>
        </div>

        <div v-if="activeTab === 'aspirations'" class="mt-6">
          <div v-if="myAspirations.length > 0" class="grid grid-cols-1 gap-4">
            <article 
              v-for="item in myAspirations" 
              :key="item.id"
              class="rounded-xl border border-slate-800 bg-slate-900 p-5 transition hover:border-slate-700"
            >
              <div class="flex items-start justify-between gap-3">
                <span class="inline-flex items-center rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-semibold tracking-wider uppercase text-emerald-400">
                  {{ item.category }}
                </span>
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-medium" :class="getStatusBadge(item.status)">
                  {{ item.status }}
                </span>
              </div>
              <h3 class="mt-3 text-base font-bold text-white tracking-tight">{{ item.title }}</h3>
              <p class="mt-2 text-xs leading-relaxed text-slate-400">{{ item.content }}</p>
              <div class="mt-4 flex items-center justify-between border-t border-slate-800/80 pt-3 text-xs text-slate-500">
                <span>Diposting {{ item.createdAt }}</span>
                <div class="flex items-center gap-1 text-red-400">
                  <svg class="w-4 h-4 fill-red-400" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                  <span class="font-semibold">{{ item.likesCount }} Dukungan</span>
                </div>
              </div>
            </article>
          </div>
          <div v-else class="rounded-xl border border-dashed border-slate-800 bg-slate-900/40 py-12 text-center text-xs text-slate-500">
            Belum ada aspirasi yang pernah dibuat.
          </div>
        </div>

        <div v-if="activeTab === 'reports'" class="mt-6">
          <div v-if="myReports.length > 0" class="grid grid-cols-1 gap-4">
            <article 
              v-for="item in myReports" 
              :key="item.id"
              class="rounded-xl border border-slate-800 bg-slate-900 p-5 transition hover:border-slate-700"
            >
              <div class="flex items-start justify-between gap-3">
                <span class="inline-flex items-center rounded-md border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-[10px] font-semibold tracking-wider uppercase text-amber-400">
                  {{ item.category }}
                </span>
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-medium" :class="getStatusBadge(item.status)">
                  {{ item.status }}
                </span>
              </div>
              <h3 class="mt-3 text-base font-bold text-white tracking-tight">{{ item.title }}</h3>
              <p class="mt-2 text-xs leading-relaxed text-slate-400">{{ item.content }}</p>
              <div class="mt-4 flex items-center justify-between border-t border-slate-800/80 pt-3 text-xs text-slate-500">
                <span>Dilaporkan {{ item.createdAt }}</span>
              </div>
            </article>
          </div>
          <div v-else class="rounded-xl border border-dashed border-slate-800 bg-slate-900/40 py-12 text-center text-xs text-slate-500">
            Belum ada laporan pengaduan yang dikirim.
          </div>
        </div>

      </section>

    </main>

    <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
        
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
          <h3 class="text-base font-bold text-white">Edit Profil Saya</h3>
          <button @click="isEditModalOpen = false" class="text-slate-400 hover:text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleSaveProfile" class="mt-5 space-y-4">
          
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Lengkap</label>
            <input 
              v-model="profileForm.name"
              type="text" 
              required
              class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kelas / Rombel</label>
            <input 
              v-model="profileForm.class_name"
              type="text" 
              placeholder="Contoh: XI RPL 2"
              class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Bio Singkat</label>
            <textarea 
              v-model="profileForm.bio"
              rows="3"
              placeholder="Tuliskan sedikit informasi tentang diri Anda..."
              class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
            ></textarea>
          </div>

          <div class="mt-6 flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="isEditModalOpen = false"
              class="rounded-xl border border-slate-800 px-4 py-2 text-xs font-semibold text-slate-400 hover:bg-slate-800 hover:text-white"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="isSaving"
              class="rounded-xl bg-emerald-500 px-4 py-2 text-xs font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-50"
            >
              {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>

        </form>

      </div>
    </div>

  </div>
</template>