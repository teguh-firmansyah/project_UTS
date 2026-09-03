<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const search = ref('')
const selectedStatus = ref('all')

const reports = ref([
  {
    id: 1,
    report_code: 'REP-2026-001',
    title: 'AC Ruang Kelas 12 IPA 1 Rusak dan Bising',
    type: 'facility',
    status: 'in_progress',
    created_at: '2026-03-01T08:30:00Z',
  },
  {
    id: 2,
    report_code: 'REP-2026-002',
    title: 'Usulan Penambahan Ekskul Coding & Robotik',
    type: 'aspiration',
    status: 'pending',
    created_at: '2026-03-02T10:15:00Z',
  },
  {
    id: 3,
    report_code: 'REP-2026-003',
    title: 'Laporan Kerusakan Lampu Lapangan Basket',
    type: 'facility',
    status: 'resolved',
    created_at: '2026-02-20T14:00:00Z',
  }
])

const filteredReports = computed(() => {
  return reports.value.filter(item => {
    const matchesSearch = item.title.toLowerCase().includes(search.value.toLowerCase()) ||
                          item.report_code.toLowerCase().includes(search.value.toLowerCase())
    const matchesStatus = selectedStatus.value === 'all' || item.status === selectedStatus.value
    return matchesSearch && matchesStatus
  })
})

const getStatusBadge = (status) => {
  const map = {
    pending: { label: 'Menunggu', class: 'bg-amber-500/10 text-amber-400 border-amber-500/20' },
    reviewing: { label: 'Ditinjau', class: 'bg-blue-500/10 text-blue-400 border-blue-500/20' },
    in_progress: { label: 'Diproses', class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' },
    resolved: { label: 'Selesai', class: 'bg-slate-500/10 text-slate-300 border-slate-500/20' },
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
    month: 'long',
    year: 'numeric'
  })
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 p-4 sm:p-6 lg:p-8 font-sans">
    <div class="max-w-6xl mx-auto space-y-6">
      
      <div class="flex items-center justify-between">
        <button
          @click="router.push('/')"
          class="inline-flex items-center space-x-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700/80 px-3.5 py-2 rounded-xl border border-slate-700/80 transition-all duration-200 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali ke Dashboard</span>
        </button>

        <router-link
          to="/reports/new"
          class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-semibold px-4 py-2 rounded-lg text-xs transition-all duration-200 shadow-md shadow-emerald-500/10 flex items-center space-x-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Buat Laporan Baru</span>
        </router-link>
      </div>

      <div class="bg-slate-800 rounded-2xl border border-slate-700/80 p-6 sm:p-8 shadow-xl space-y-6">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-white tracking-wide">Semua Laporan Saya</h1>
          <p class="text-xs text-slate-400 mt-1">Daftar lengkap semua laporan yang pernah diajukan.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <input
            v-model="search"
            type="text"
            placeholder="Cari kode atau judul laporan..."
            class="flex-1 bg-slate-700/40 border border-slate-600/80 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
          />
          <select
            v-model="selectedStatus"
            class="bg-slate-700/40 border border-slate-600/80 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"
          >
            <option value="all">Semua Status</option>
            <option value="pending">Menunggu</option>
            <option value="in_progress">Diproses</option>
            <option value="resolved">Selesai</option>
          </select>
        </div>

        <div class="divide-y divide-slate-700/60 border-t border-slate-700/60 pt-2">
          <div v-if="filteredReports.length === 0" class="py-8 text-center text-xs text-slate-500">
            Laporan tidak ditemukan.
          </div>

          <div
            v-for="item in filteredReports"
            :key="item.id"
            class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-700/20 px-3 rounded-xl transition-colors"
          >
            <div class="space-y-1">
              <div class="flex items-center space-x-2">
                <span class="text-xs font-mono text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                  {{ item.report_code }}
                </span>
                <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border" :class="getTypeBadge(item.type).class">
                  {{ getTypeBadge(item.type).label }}
                </span>
                <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border" :class="getStatusBadge(item.status).class">
                  {{ getStatusBadge(item.status).label }}
                </span>
              </div>
              <h3 class="text-sm font-semibold text-white">{{ item.title }}</h3>
              <p class="text-xs text-slate-400">Tanggal: {{ formatDate(item.created_at) }}</p>
            </div>

            <button
              @click="router.push(`/reports/${item.id}`)"
              class="px-3.5 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-xs font-medium border border-slate-600 self-end sm:self-center cursor-pointer"
            >
              Lihat Detail
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>