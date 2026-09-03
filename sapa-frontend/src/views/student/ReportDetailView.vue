<script setup>
import { ref, onMounted } from 'vue'
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

const handleAddComment = async () => {
  if (!newComment.value.trim()) return

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

onMounted(() => {
  fetchReportDetail()
})
</script>

<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 p-4 sm:p-6 lg:p-8 font-sans">
    <div class="max-w-4xl mx-auto space-y-6">
      
      <div class="flex items-center justify-between">
        <button
          @click="router.back()"
          class="inline-flex items-center space-x-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700/80 px-3.5 py-2 rounded-xl border border-slate-700/80 transition-all duration-200"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali</span>
        </button>

        <span v-if="report" class="text-xs font-mono text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
          {{ report.report_code }}
        </span>
      </div>

      <div v-if="isLoading" class="bg-slate-800 rounded-2xl border border-slate-700/80 p-8 text-center text-slate-400 space-y-3">
        <div class="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-xs">Memuat detail laporan...</p>
      </div>

      <div v-else-if="report" class="space-y-6 animate-fade-up">
        
        <div class="bg-slate-800 rounded-2xl border border-slate-700/80 shadow-2xl p-6 sm:p-8 space-y-6">
          
          <div class="border-b border-slate-700/60 pb-6 space-y-4">
            <div class="flex flex-wrap items-center gap-2">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                :class="getTypeBadge(report.category).class"
              >
                {{ getTypeBadge(report.category).label }}
              </span>

              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                :class="getStatusBadge(report.status).class"
              >
                {{ getStatusBadge(report.status).label }}
              </span>
            </div>

            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight leading-snug">
              {{ report.title }}
            </h1>

            <div class="flex items-center justify-between text-xs text-slate-400 pt-2">
              <div class="flex items-center space-x-2">
                <div class="w-7 h-7 rounded-full bg-slate-700 flex items-center justify-center text-emerald-400 font-bold border border-slate-600">
                  {{ report.is_anonymous ? 'A' : (report.user?.name?.substring(0, 1) || 'S') }}
                </div>
                <div>
                  <p class="text-slate-200 font-medium">
                    {{ report.is_anonymous ? 'Pelapor Anonim' : report.user?.name }}
                  </p>
                  <p v-if="!report.is_anonymous" class="text-[10px] text-slate-400">
                    {{ report.user?.class_name || 'Siswa' }}
                  </p>
                </div>
              </div>

              <span>{{ formatDate(report.created_at) }}</span>
            </div>
          </div>

          <div class="space-y-4">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Deskripsi Laporan</h3>
            <p class="text-slate-200 text-sm leading-relaxed whitespace-pre-line">
              {{ report.content }}
            </p>
          </div>

          <div v-if="report.image_url" class="space-y-2 pt-2">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Lampiran Bukti Foto</h3>
            <div class="relative max-w-lg rounded-xl overflow-hidden border border-slate-700 bg-slate-900 group">
              <img :src="report.image_url" alt="Bukti Laporan" class="w-full h-auto max-h-80 object-cover" />
            </div>
          </div>

        </div>

        <div class="bg-slate-800 rounded-2xl border border-slate-700/80 shadow-2xl p-6 sm:p-8 space-y-6">
          
          <div class="flex items-center justify-between border-b border-slate-700/60 pb-4">
            <div class="flex items-center space-x-2">
              <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <h2 class="text-base font-bold text-white">Tanggapan & Diskusi</h2>
            </div>
            <span class="text-xs text-slate-400">{{ report.comments.length }} Komentar</span>
          </div>

          <div class="space-y-4">
            <div v-if="report.comments.length === 0" class="py-6 text-center text-slate-500 text-xs">
              Belum ada tanggapan pada laporan ini.
            </div>

            <div
              v-for="item in report.comments"
              :key="item.id"
              class="p-4 rounded-xl border transition-all duration-150 space-y-2"
              :class="[
                item.user_role === 'admin' 
                  ? 'bg-emerald-500/5 border-emerald-500/20' 
                  : 'bg-slate-700/30 border-slate-700/60'
              ]"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div 
                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                    :class="item.user_role === 'admin' ? 'bg-emerald-500 text-slate-950' : 'bg-slate-600 text-white'"
                  >
                    {{ item.user_name.substring(0, 1) }}
                  </div>
                  <span class="text-xs font-semibold text-white">{{ item.user_name }}</span>
                  <span 
                    v-if="item.user_role === 'admin'" 
                    class="text-[10px] bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-semibold border border-emerald-500/30"
                  >
                    Petugas
                  </span>
                </div>

                <span class="text-[10px] text-slate-400">{{ formatDate(item.created_at) }}</span>
              </div>

              <p class="text-xs text-slate-300 leading-relaxed pl-8">
                {{ item.comment }}
              </p>
            </div>
          </div>

          <form @submit.prevent="handleAddComment" class="pt-4 border-t border-slate-700/60 space-y-3">
            <label class="block text-xs font-medium text-slate-300">Tulis Tanggapan Anda</label>
            <textarea
              v-model="newComment"
              rows="3"
              placeholder="Tulis pesan atau tanggapan terkait laporan ini..."
              class="w-full bg-slate-700/40 border border-slate-600/80 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-200 resize-none"
            ></textarea>

            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="isSubmittingComment || !newComment.trim()"
                class="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 active:scale-[0.98] text-slate-950 font-semibold rounded-lg text-xs transition-all duration-200 shadow-md shadow-emerald-500/10 disabled:opacity-50"
              >
                {{ isSubmittingComment ? 'Mengirim...' : 'Kirim Tanggapan' }}
              </button>
            </div>
          </form>

        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
@keyframes fadeUp {
  0% {
    opacity: 0;
    transform: translateY(12px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-up {
  animation: fadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>