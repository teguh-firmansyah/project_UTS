<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// State Data Dummy/Simulasi (Ganti dengan panggil API backend Anda)
const reports = ref([
  {
    id: 1,
    report_code: 'REP-2026-001',
    title: 'AC Ruang Kelas 12 IPA 1 Rusak dan Bising',
    type: 'facility',
    status: 'in_progress',
    priority: 'high',
    created_at: '2026-03-01T08:30:00Z',
  },
  {
    id: 2,
    report_code: 'REP-2026-002',
    title: 'Usulan Penambahan Ekskul Coding & Robotik',
    type: 'aspiration',
    status: 'pending',
    priority: 'medium',
    created_at: '2026-03-02T10:15:00Z',
  },
  {
    id: 3,
    report_code: 'REP-2026-003',
    title: 'Laporan Kerusakan Lampu Lapangan Basket',
    type: 'facility',
    status: 'resolved',
    priority: 'low',
    created_at: '2026-02-20T14:00:00Z',
  }
])

// Statistik berdasarkan data laporan
const stats = computed(() => {
  return {
    total: reports.value.length,
    pending: reports.value.filter(r => r.status === 'pending').length,
    in_progress: reports.value.filter(r => r.status === 'in_progress' || r.status === 'reviewing').length,
    resolved: reports.value.filter(r => r.status === 'resolved').length,
  }
})

// Helper format status, tipe, & badge warna
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
    aspiration: { label: 'Aspirasi', class: 'bg-purple-500/10 text-purple-400 border-purple-500/20' },
    facility: { label: 'Fasilitas', class: 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20' },
    bullying: { label: 'Perundungan', class: 'bg-rose-500/10 text-rose-400 border-rose-500/20' },
  }
  return map[type] || { label: type, class: 'bg-slate-700 text-slate-300' }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

async function handleLogout() {
  await authStore.logout()
  toast.success('Berhasil keluar dari sistem.')
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col font-sans">
    
    <!-- Top Navigation Bar -->
    <header class="bg-slate-800/80 backdrop-blur-md border-b border-slate-700/80 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          
          <!-- Logo & Brand -->
          <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center overflow-hidden border border-slate-700/80 shadow-lg shadow-emerald-500/10">
                <img 
                src="../../assets/logo sapa.jpeg" 
                alt="Logo SAPA" 
                class="w-full h-full object-cover" 
                />
            </div>
            <span class="text-white font-bold text-xl tracking-wide hidden sm:inline">SAPA</span>
            </div>

          <!-- Quick Action & User Profile Dropdown / Logout -->
          <div class="flex items-center space-x-4">
            <router-link
              to="/reports/create"
              class="bg-emerald-500 hover:bg-emerald-600 active:scale-[0.98] text-slate-950 font-semibold px-4 py-2 rounded-lg text-sm transition-all duration-200 shadow-md shadow-emerald-500/10 flex items-center space-x-1.5"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              <span>Buat Laporan</span>
            </router-link>

            <!-- Logout Button -->
            <button
              @click="handleLogout"
              title="Keluar"
              class="p-2 text-slate-400 hover:text-red-400 hover:bg-slate-700/50 rounded-lg transition-colors duration-200"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>

        </div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 animate-fade-up">
      
      <!-- Welcome Hero Banner -->
      <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900/60 via-slate-800 to-slate-800 border border-slate-700/80 rounded-2xl p-6 md:p-8 shadow-xl">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_var(--tw-gradient-stops))] from-emerald-500/10 via-transparent to-transparent pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight">
              Selamat Datang, {{ authStore.user?.name || 'Siswa SAPA' }}! 👋
            </h1>
            <p class="text-slate-300 text-sm mt-1 max-w-2xl">
              Pantau status laporan, sampaikan aspirasi, atau laporkan kendala fasilitas dan keamanan di lingkungan sekolahmu.
            </p>
          </div>
          <div class="flex items-center space-x-3 bg-slate-900/40 backdrop-blur border border-slate-700/50 rounded-xl p-3 self-start md:self-auto">
            <div class="w-10 h-10 rounded-full bg-slate-700/80 flex items-center justify-center text-emerald-400 font-bold text-base border border-slate-600">
              {{ authStore.user?.class_name ? authStore.user.class_name.substring(0, 2) : 'ST' }}
            </div>
            <div class="text-xs">
              <p class="text-white font-medium">{{ authStore.user?.class_name || 'Kelas Tidak Set' }}</p>
              <p class="text-slate-400">NISN: {{ authStore.user?.identity_number || '-' }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Stats Cards Row -->
      <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Laporan -->
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-5 shadow-lg flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Total Laporan</p>
            <h3 class="text-2xl font-bold text-white mt-1">{{ stats.total }}</h3>
          </div>
          <div class="w-10 h-10 rounded-lg bg-slate-700/50 flex items-center justify-center text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>

        <!-- Menunggu Process -->
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-5 shadow-lg flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Menunggu</p>
            <h3 class="text-2xl font-bold text-amber-400 mt-1">{{ stats.pending }}</h3>
          </div>
          <div class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>

        <!-- Dalam Proses -->
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-5 shadow-lg flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Diproses</p>
            <h3 class="text-2xl font-bold text-emerald-400 mt-1">{{ stats.in_progress }}</h3>
          </div>
          <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </div>
        </div>

        <!-- Selesai -->
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-5 shadow-lg flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Selesai</p>
            <h3 class="text-2xl font-bold text-slate-200 mt-1">{{ stats.resolved }}</h3>
          </div>
          <div class="w-10 h-10 rounded-lg bg-slate-700/50 flex items-center justify-center text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
      </section>

      <!-- Quick Actions Grid -->
      <section>
        <h2 class="text-base font-semibold text-white mb-4">Aksi Cepat</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          
          <router-link 
            to="/reports/create?type=aspiration"
            class="group bg-slate-800 hover:bg-slate-800/90 border border-slate-700/80 hover:border-purple-500/50 rounded-xl p-5 transition-all duration-300 flex items-start space-x-4 shadow-lg hover:shadow-purple-500/5"
          >
            <div class="p-3 rounded-lg bg-purple-500/10 text-purple-400 group-hover:bg-purple-500 group-hover:text-slate-950 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-white group-hover:text-purple-300 transition-colors">Sampaikan Aspirasi</h3>
              <p class="text-xs text-slate-400 mt-1">Beri usulan atau ide perbaikan akademik dan kebijakan sekolah.</p>
            </div>
          </router-link>

          <router-link 
            to="/reports/create?type=facility"
            class="group bg-slate-800 hover:bg-slate-800/90 border border-slate-700/80 hover:border-cyan-500/50 rounded-xl p-5 transition-all duration-300 flex items-start space-x-4 shadow-lg hover:shadow-cyan-500/5"
          >
            <div class="p-3 rounded-lg bg-cyan-500/10 text-cyan-400 group-hover:bg-cyan-500 group-hover:text-slate-950 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-white group-hover:text-cyan-300 transition-colors">Laporkan Fasilitas</h3>
              <p class="text-xs text-slate-400 mt-1">Sampaikan kerusakan barang, meja, kursi, atau fasilitas sekolah.</p>
            </div>
          </router-link>

          <router-link 
            to="/reports/create?type=bullying"
            class="group bg-slate-800 hover:bg-slate-800/90 border border-slate-700/80 hover:border-rose-500/50 rounded-xl p-5 transition-all duration-300 flex items-start space-x-4 shadow-lg hover:shadow-rose-500/5"
          >
            <div class="p-3 rounded-lg bg-rose-500/10 text-rose-400 group-hover:bg-rose-500 group-hover:text-slate-950 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-white group-hover:text-rose-300 transition-colors">Aduan Perundungan</h3>
              <p class="text-xs text-slate-400 mt-1">Laporan aman & rahasia langsung ke Guru BK/Kanselor.</p>
            </div>
          </router-link>

        </div>
      </section>

      <!-- Recent Reports Table -->
      <section class="bg-slate-800 border border-slate-700/80 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-700/80 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-bold text-white">Laporan Terbaru Anda</h2>
            <p class="text-xs text-slate-400 mt-0.5">Daftar riwayat laporan dan status penanganannya.</p>
          </div>
          <router-link to="/reports" class="text-xs text-emerald-400 hover:text-emerald-300 font-medium transition-colors">
            Lihat Semua →
          </router-link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-900/50 border-b border-slate-700/80 text-xs text-slate-400 uppercase tracking-wider">
                <th class="py-3.5 px-6 font-semibold">Kode & Judul</th>
                <th class="py-3.5 px-6 font-semibold">Tipe</th>
                <th class="py-3.5 px-6 font-semibold">Status</th>
                <th class="py-3.5 px-6 font-semibold">Tanggal</th>
                <th class="py-3.5 px-6 font-semibold text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50 text-sm">
              <tr v-if="reports.length === 0">
                <td colspan="5" class="py-8 text-center text-slate-500">
                  Belum ada laporan yang dibuat.
                </td>
              </tr>
              <tr 
                v-for="item in reports" 
                :key="item.id" 
                class="hover:bg-slate-700/30 transition-colors duration-150"
              >
                <td class="py-4 px-6">
                  <span class="text-xs font-mono text-emerald-400 block mb-0.5">{{ item.report_code }}</span>
                  <span class="font-medium text-white line-clamp-1">{{ item.title }}</span>
                </td>
                <td class="py-4 px-6 whitespace-nowrap">
                  <span 
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                    :class="getTypeBadge(item.type).class"
                  >
                    {{ getTypeBadge(item.type).label }}
                  </span>
                </td>
                <td class="py-4 px-6 whitespace-nowrap">
                  <span 
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                    :class="getStatusBadge(item.status).class"
                  >
                    {{ getStatusBadge(item.status).label }}
                  </span>
                </td>
                <td class="py-4 px-6 text-slate-400 text-xs whitespace-nowrap">
                  {{ formatDate(item.created_at) }}
                </td>
                <td class="py-4 px-6 text-right whitespace-nowrap">
                  <router-link 
                    :to="`/reports/${item.id}`" 
                    class="text-xs font-medium text-slate-300 hover:text-emerald-400 bg-slate-700/50 hover:bg-slate-700 px-3 py-1.5 rounded-lg border border-slate-600/50 transition-colors"
                  >
                    Detail
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

    </main>
  </div>
</template>

<style scoped>
@keyframes fadeUp {
  0% {
    opacity: 0;
    transform: translateY(16px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-up {
  animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>