<script setup>
import { useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import * as yup from 'yup'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const schema = yup.object({
  name: yup.string().required('Nama wajib diisi').min(3, 'Nama minimal 3 karakter'),
  email: yup.string().required('Email wajib diisi').email('Format email tidak valid'),
  identity_number: yup.string().required('NIS wajib diisi'),
  class_name: yup.string().required('Kelas wajib diisi'),
  password: yup.string().required('Password wajib diisi').min(8, 'Password minimal 8 karakter'),
  password_confirmation: yup
    .string()
    .required('Konfirmasi password wajib diisi')
    .oneOf([yup.ref('password')], 'Konfirmasi password tidak cocok'),
})

const { handleSubmit, defineField, errors } = useForm({ validationSchema: schema })

const [name, nameAttrs] = defineField('name')
const [email, emailAttrs] = defineField('email')
const [identity_number, identityAttrs] = defineField('identity_number')
const [class_name, classAttrs] = defineField('class_name')
const [password, passwordAttrs] = defineField('password')
const [password_confirmation, passwordConfirmAttrs] = defineField('password_confirmation')

const onSubmit = handleSubmit(async (values) => {
  try {
    await authStore.register(values)
    toast.success('Registrasi berhasil! Selamat datang di SAPA.')
    router.push('/')
  } catch {
    toast.error(authStore.error || 'Registrasi gagal, periksa kembali data kamu.')
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-10">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
      <h1 class="text-xl font-semibold text-gray-900 mb-1">Daftar Akun SAPA</h1>
      <p class="text-sm text-gray-500 mb-6">Khusus siswa aktif</p>

      <form @submit="onSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
          <input v-model="name" v-bind="nameAttrs" type="text"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          <p v-if="errors.name" class="text-xs text-red-500 mt-1">{{ errors.name }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="email" v-bind="emailAttrs" type="email"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">NIS</label>
            <input v-model="identity_number" v-bind="identityAttrs" type="text"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            <p v-if="errors.identity_number" class="text-xs text-red-500 mt-1">{{ errors.identity_number }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
            <input v-model="class_name" v-bind="classAttrs" type="text" placeholder="XII RPL 1"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            <p v-if="errors.class_name" class="text-xs text-red-500 mt-1">{{ errors.class_name }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input v-model="password" v-bind="passwordAttrs" type="password"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          <p v-if="errors.password" class="text-xs text-red-500 mt-1">{{ errors.password }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
          <input v-model="password_confirmation" v-bind="passwordConfirmAttrs" type="password"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          <p v-if="errors.password_confirmation" class="text-xs text-red-500 mt-1">{{ errors.password_confirmation }}</p>
        </div>

        <button type="submit" :disabled="authStore.isLoading"
          class="w-full bg-indigo-600 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-indigo-700 transition disabled:opacity-50">
          {{ authStore.isLoading ? 'Memproses...' : 'Daftar' }}
        </button>
      </form>

      <p class="text-sm text-gray-500 text-center mt-6">
        Sudah punya akun?
        <router-link to="/login" class="text-indigo-600 font-medium">Masuk</router-link>
      </p>
    </div>
  </div>
</template>