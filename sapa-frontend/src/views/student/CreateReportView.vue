<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useForm } from 'vee-validate'
import * as yup from 'yup'
import { toast } from 'vue-sonner'

const router = useRouter()
const route = useRoute()

const isLoading = ref(false)
const selectedCategory = ref('aspirasi')

const imageFile = ref(null)
const imagePreview = ref(null)

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
    icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
  }
]

const schema = yup.object({
  title: yup.string().required('Judul laporan wajib diisi').min(5, 'Judul minimal 5 karakter'),
  content: yup.string().required('Isi laporan wajib diisi').min(15, 'Isi laporan minimal 15 karakter'),
  category: yup.string().required('Pilih jenis laporan'),
  is_anonymous: yup.boolean(),
})

const { handleSubmit, defineField, errors, setFieldValue } = useForm({
  validationSchema: schema,
  initialValues: {
    category: 'aspirasi',
    is_anonymous: false
  }
})

const [title, titleAttrs] = defineField('title')
const [content, contentAttrs] = defineField('content')
const [is_anonymous] = defineField('is_anonymous')

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

const handleImageChange = (e) => {
  const file = e.target.files[0]
  if (!file) return

  if (file.size > 2 * 1024 * 1024) {
    toast.error('Ukuran gambar maksimal 2MB!')
    return
  }

  imageFile.value = file
  imagePreview.value = URL.createObjectURL(file)
}

const removeImage = () => {
  imageFile.value = null
  imagePreview.value = null
}

const onSubmit = handleSubmit(async (values) => {
  isLoading.value = true
  try {
    const formData = new FormData()
    formData.append('title', values.title)
    formData.append('content', values.content)
    formData.append('category', values.category)
    formData.append('is_anonymous', values.is_anonymous ? 1 : 0)
    
    if (imageFile.value) {
      formData.append('image', imageFile.value)
    }

    toast.success('Laporan berhasil dikirim!', {
      description: 'Laporan Anda akan segera ditinjau oleh pihak sekolah.'
    })
    
    router.push('/')
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Gagal mengirim laporan. Silakan coba lagi.')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 p-4 sm:p-6 lg:p-8 flex items-center justify-center font-sans">
    <div class="w-full max-w-3xl bg-slate-800 rounded-2xl border border-slate-700/80 shadow-2xl p-6 sm:p-8 animate-fade-up">
      
      <div class="mb-8 border-b border-slate-700/60 pb-5">
        <div class="flex items-center space-x-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-white tracking-wide">Buat Laporan Baru</h1>
            <p class="text-xs text-slate-400 mt-0.5">Sampaikan aspirasi, kendala fasilitas, atau pengaduan perundungan secara aman.</p>
          </div>
        </div>
      </div>

      <form @submit="onSubmit" class="space-y-6">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-2">Pilih Jenis Laporan</label>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <button
              v-for="cat in categories"
              :key="cat.id"
              type="button"
              @click="selectCategory(cat.id)"
              :class="[
                'flex flex-col items-center justify-center p-3.5 rounded-xl border text-center transition-all duration-200 cursor-pointer',
                selectedCategory === cat.id 
                  ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400 shadow-lg shadow-emerald-500/10' 
                  : 'bg-slate-700/30 border-slate-600/80 text-slate-400 hover:border-slate-500'
              ]"
            >
              <svg class="w-6 h-6 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="cat.icon" />
              </svg>
              <span class="text-sm font-semibold text-white">{{ cat.label }}</span>
              <span class="text-[10px] text-slate-400 mt-0.5">{{ cat.desc }}</span>
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Judul Laporan</label>
          <input
            v-model="title"
            v-bind="titleAttrs"
            type="text"
            placeholder="Contoh: AC Rusak / Usulan Event Coding / Laporan Bullying"
            class="w-full bg-slate-700/40 border border-slate-600/80 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300"
          />
          <p v-if="errors.title" class="text-xs text-red-400 mt-1">{{ errors.title }}</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Isi Laporan / Detail Informasi</label>
          <textarea
            v-model="content"
            v-bind="contentAttrs"
            rows="5"
            placeholder="Jelaskan secara detail kronologi, lokasi, atau ide masukan Anda..."
            class="w-full bg-slate-700/40 border border-slate-600/80 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all duration-300 resize-none"
          ></textarea>
          <p v-if="errors.content" class="text-xs text-red-400 mt-1">{{ errors.content }}</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Lampiran Bukti Foto (Opsional)</label>
          
          <div v-if="!imagePreview" class="relative">
            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-600 hover:border-emerald-500/80 rounded-xl cursor-pointer bg-slate-700/20 hover:bg-slate-700/40 transition-all duration-300">
              <div class="flex flex-col items-center justify-center pt-5 pb-6">
                <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-xs text-slate-400"><span class="font-semibold text-emerald-400">Klik untuk unggah</span> atau seret foto ke sini</p>
                <p class="text-[10px] text-slate-500 mt-1">PNG, JPG, JPEG (Maks. 2MB)</p>
              </div>
              <input type="file" class="hidden" accept="image/*" @change="handleImageChange" />
            </label>
          </div>

          <div v-else class="relative w-full max-w-xs rounded-xl overflow-hidden border border-slate-600 bg-slate-900">
            <img :src="imagePreview" alt="Preview Lampiran" class="w-full h-36 object-cover" />
            <button
              type="button"
              @click="removeImage"
              class="absolute top-2 right-2 p-1.5 bg-slate-900/80 hover:bg-red-500 text-slate-300 hover:text-white rounded-lg transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="flex items-center space-x-3 bg-slate-700/20 p-3.5 rounded-xl border border-slate-700/60">
          <input
            id="anonymous"
            v-model="is_anonymous"
            type="checkbox"
            class="w-4 h-4 rounded border-slate-600 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-slate-800 bg-slate-700 cursor-pointer"
          />
          <label for="anonymous" class="text-xs text-slate-300 cursor-pointer select-none">
            Kirim sebagai <span class="font-semibold text-white">Anonim</span> (Nama & identitas Anda tidak akan ditampilkan ke publik)
          </label>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-700/60">
          <button
            type="button"
            @click="router.back()"
            class="px-5 py-2.5 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-700/50 rounded-lg transition-colors"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="isLoading"
            class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 active:scale-[0.98] text-slate-950 font-semibold rounded-lg text-xs transition-all duration-200 shadow-md shadow-emerald-500/10 disabled:opacity-50"
          >
            {{ isLoading ? 'Memproses...' : 'Kirim Laporan' }}
          </button>
        </div>

      </form>
    </div>
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
  animation: fadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>