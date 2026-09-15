<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const logoFailed = ref(false)

/* ---------------------------------- */
/* Navigasi panel (aktif via route)   */
/* ---------------------------------- */
const navItems = [
  {
    label: 'Analitik',
    to: '/admin/dashboard',
    icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
  },
  {
    label: 'Semua Laporan',
    to: '/admin/reports',
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  },
  {
    label: 'Manajemen User',
    to: '/admin/users',
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  },
]

const isActive = (item) => route.path === item.to || route.path.startsWith(item.to + '/')

/* ---------------------------------- */
/* State filter & paginasi (existing) */
/* ---------------------------------- */
const searchQuery = ref('')
const selectedRole = ref('all')
const selectedStatus = ref('all')
const currentPage = ref(1)
const itemsPerPage = ref(10)

/* ---------------------------------- */
/* State modal (existing)             */
/* ---------------------------------- */
const showUserModal = ref(false)
const showResetModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const selectedUser = ref(null)

const userForm = ref({
  id: null,
  name: '',
  email: '',
  nip_nisn: '',
  role: 'Siswa',
  status: 'Aktif'
})

const userErrors = ref({})

/* ---------------------------------- */
/* Data pengguna (existing, verbatim) */
/* ---------------------------------- */
const users = ref([
  {
    id: 1,
    name: 'Ahmad Rizky',
    email: 'ahmad.rizky@sekolah.sch.id',
    nip_nisn: '0051234567',
    role: 'Siswa',
    status: 'Aktif',
    last_login: '15 Sep 2026, 08:30'
  },
  {
    id: 2,
    name: 'Dra. Endang Lestari',
    email: 'endang.bk@sekolah.sch.id',
    nip_nisn: '197803122005012001',
    role: 'Guru BK',
    status: 'Aktif',
    last_login: '14 Sep 2026, 14:15'
  },
  {
    id: 3,
    name: 'Bambang Subagyo, S.Pd',
    email: 'bambang.sarpras@sekolah.sch.id',
    nip_nisn: '198205142008021003',
    role: 'Guru',
    status: 'Aktif',
    last_login: '12 Sep 2026, 10:00'
  },
  {
    id: 4,
    name: 'Admin Utama SAPA',
    email: 'admin.sapa@sekolah.sch.id',
    nip_nisn: '199001012015031001',
    role: 'Admin',
    status: 'Aktif',
    last_login: '15 Sep 2026, 14:00'
  },
  {
    id: 5,
    name: 'Siti Aminah',
    email: 'siti.aminah@sekolah.sch.id',
    nip_nisn: '0057654321',
    role: 'Siswa',
    status: 'Nonaktif',
    last_login: '01 Agu 2026, 09:20'
  }
])

/* ---------------------------------- */
/* Metrik (computed existing, kini    */
/* dengan resep kartu sistem)         */
/* ---------------------------------- */
const totalUsers = computed(() => users.value.length)
const activeUsers = computed(() => users.value.filter(u => u.status === 'Aktif').length)
const totalAdmins = computed(() => users.value.filter(u => u.role === 'Admin').length)
const totalTeachers = computed(() => users.value.filter(u => u.role === 'Guru' || u.role === 'Guru BK').length)

const statCards = computed(() => {
  const pct = (n) => (totalUsers.value > 0 ? Math.round((n / totalUsers.value) * 100) : 0)
  return [
    {
      label: 'Total Pengguna',
      value: totalUsers.value,
      caption: 'Seluruh akun terdaftar pada sistem',
      pct: 100,
      icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
      num: 'text-white',
      tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
      bar: 'bg-emerald-500',
    },
    {
      label: 'Akun Aktif',
      value: activeUsers.value,
      caption: `${pct(activeUsers.value)}% dari total pengguna`,
      pct: pct(activeUsers.value),
      icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      num: 'text-emerald-400',
      tile: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
      bar: 'bg-emerald-500',
    },
    {
      label: 'Tenaga Pendidik',
      value: totalTeachers.value,
      caption: 'Guru, Guru BK & petugas sarpras',
      pct: pct(totalTeachers.value),
      icon: 'M12 14l9-5-9-5-9 5 9 5z',
      num: 'text-cyan-400',
      tile: 'border-cyan-500/25 bg-cyan-500/10 text-cyan-400',
      bar: 'bg-cyan-500',
    },
    {
      label: 'Administrator',
      value: totalAdmins.value,
      caption: 'Akses penuh sistem & analitik',
      pct: pct(totalAdmins.value),
      icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
      num: 'text-blue-400',
      tile: 'border-blue-500/25 bg-blue-500/10 text-blue-400',
      bar: 'bg-blue-500',
    },
  ]
})

/* ---------------------------------- */
/* Filter (logika existing)           */
/* ---------------------------------- */
const filteredUsers = computed(() => {
  return users.value.filter(u => {
    const matchSearch = u.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        u.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        u.nip_nisn.includes(searchQuery.value)
    const matchRole = selectedRole.value === 'all' || u.role === selectedRole.value
    const matchStatus = selectedStatus.value === 'all' || u.status === selectedStatus.value

    return matchSearch && matchRole && matchStatus
  })
})

const hasActiveFilters = computed(() =>
  searchQuery.value.trim() !== '' || selectedRole.value !== 'all' || selectedStatus.value !== 'all'
)

const clearFilters = () => {
  searchQuery.value = ''
  selectedRole.value = 'all'
  selectedStatus.value = 'all'
}

/* ---------------------------------- */
/* Paginasi (logika existing +        */
/* reset halaman saat filter berubah) */
/* ---------------------------------- */
const totalPages = computed(() => Math.ceil(filteredUsers.value.length / itemsPerPage.value) || 1)
const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredUsers.value.slice(start, start + itemsPerPage.value)
})

watch([searchQuery, selectedRole, selectedStatus], () => {
  currentPage.value = 1
})

watch(totalPages, (tp) => {
  if (currentPage.value > tp) currentPage.value = tp
})

const prevPage = () => { if (currentPage.value > 1) currentPage.value-- }
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++ }

/* ---------------------------------- */
/* Helper (existing + peran sistem)   */
/* ---------------------------------- */
const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

/* Warna peran diselaraskan dengan chip panel: Siswa=emerald, Guru=cyan, BK=rose, Admin=blue */
const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'Admin': return 'bg-blue-500/10 text-blue-400 border-blue-500/20'
    case 'Guru BK': return 'bg-rose-500/10 text-rose-400 border-rose-500/20'
    case 'Guru': return 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20'
    case 'Siswa': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    default: return 'bg-slate-800 text-slate-300 border-slate-700'
  }
}

const getRoleAvatarClass = (role) => {
  switch (role) {
    case 'Admin': return 'border-blue-500/30 bg-blue-500/10 text-blue-400'
    case 'Guru BK': return 'border-rose-500/30 bg-rose-500/10 text-rose-400'
    case 'Guru': return 'border-cyan-500/30 bg-cyan-500/10 text-cyan-400'
    case 'Siswa': return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
    default: return 'border-slate-700 bg-slate-800 text-slate-300'
  }
}

/* Opsi peran — kartu radio (nilai identik dengan select asli) */
const roleOptions = [
  { value: 'Siswa', label: 'Siswa', desc: 'Mengirim laporan & aspirasi', dot: 'bg-emerald-400', active: 'border-emerald-500/50 bg-emerald-500/[0.06] text-emerald-400' },
  { value: 'Guru', label: 'Guru (Sarpras)', desc: 'Menangani laporan fasilitas', dot: 'bg-cyan-400', active: 'border-cyan-500/50 bg-cyan-500/[0.06] text-cyan-400' },
  { value: 'Guru BK', label: 'Guru BK', desc: 'Menangani kasus perundungan', dot: 'bg-rose-400', active: 'border-rose-500/50 bg-rose-500/[0.06] text-rose-400' },
  { value: 'Admin', label: 'Admin', desc: 'Akses penuh sistem & analitik', dot: 'bg-blue-400', active: 'border-blue-500/50 bg-blue-500/[0.06] text-blue-400' },
]

/* Pil status + hitungan */
const statusPills = [
  { value: 'all', label: 'Semua', active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Aktif', label: 'Aktif', active: 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400' },
  { value: 'Nonaktif', label: 'Nonaktif', active: 'border-slate-600 bg-slate-700/40 text-slate-200' },
]

const pillIdle = 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'

const statusCounts = computed(() => {
  const counts = { all: users.value.length }
  for (const u of users.value) counts[u.status] = (counts[u.status] || 0) + 1
  return counts
})

/* Baris siap-render */
const tableRows = computed(() =>
  paginatedUsers.value.map(u => ({
    ...u,
    roleBadge: getRoleBadgeClass(u.role),
    avatarClass: getRoleAvatarClass(u.role),
    isActive: u.status === 'Aktif',
  }))
)

/* ---------------------------------- */
/* Handler modal (existing)           */
/* ---------------------------------- */
const openCreateModal = () => {
  isEditing.value = false
  userForm.value = { id: null, name: '', email: '', nip_nisn: '', role: 'Siswa', status: 'Aktif' }
  userErrors.value = {}
  showUserModal.value = true
}

const openEditModal = (user) => {
  isEditing.value = true
  userForm.value = { ...user }
  userErrors.value = {}
  showUserModal.value = true
}

const openResetPasswordModal = (user) => {
  selectedUser.value = user
  showResetModal.value = true
}

const openDeleteModal = (user) => {
  selectedUser.value = user
  showDeleteModal.value = true
}

/* ---------------------------------- */
/* Validasi formulir                  */
/* ---------------------------------- */
const validateUserForm = () => {
  const e = {}
  const f = userForm.value

  if (!f.name.trim()) e.name = 'Nama wajib diisi'
  else if (f.name.trim().length < 3) e.name = 'Nama minimal 3 karakter'

  if (!f.email.trim()) e.email = 'Email wajib diisi'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email.trim())) e.email = 'Format email tidak valid'

  if (!f.nip_nisn.trim()) e.nip_nisn = 'NIP / NISN wajib diisi'

  return e
}

watch(
  () => [userForm.value.name, userForm.value.email, userForm.value.nip_nisn],
  () => {
    for (const key of ['name', 'email', 'nip_nisn']) {
      if (userErrors.value[key]) userErrors.value[key] = ''
    }
  }
)

/* ---------------------------------- */
/* Simpan user (behavior existing +   */
/* validasi & toast)                  */
/* ---------------------------------- */
const handleSaveUser = () => {
  userErrors.value = validateUserForm()
  if (Object.keys(userErrors.value).length > 0) {
    toast.error('Mohon lengkapi data pengguna yang belum valid.')
    return
  }

  if (isEditing.value) {
    const idx = users.value.findIndex(u => u.id === userForm.value.id)
    if (idx !== -1) {
      users.value[idx] = { ...userForm.value }
    }
    toast.success('Data pengguna berhasil diperbarui.', {
      description: `${userForm.value.name} · peran ${userForm.value.role}.`
    })
  } else {
    users.value.unshift({
      ...userForm.value,
      id: Date.now(),
      last_login: 'Belum Pernah'
    })
    toast.success('Pengguna baru berhasil ditambahkan.', {
      description: `${userForm.value.name} · peran ${userForm.value.role}.`
    })
  }
  showUserModal.value = false
}

/* Hapus user (behavior existing + toast) */
const handleDeleteUser = () => {
  if (selectedUser.value) {
    const name = selectedUser.value.name
    users.value = users.value.filter(u => u.id !== selectedUser.value.id)
    toast.success(`Akun "${name}" telah dihapus dari sistem.`)
  }
  showDeleteModal.value = false
}

/* Reset password (tutup modal asli + toast) */
const handleConfirmReset = () => {
  if (selectedUser.value) {
    toast.success('Kata sandi berhasil direset ke default.', {
      description: `${selectedUser.value.name} wajib mengganti kata sandi setelah login.`
    })
  }
  showResetModal.value = false
}

/* Toggle status (behavior existing + toast) */
const toggleUserStatus = (user) => {
  user.status = user.status === 'Aktif' ? 'Nonaktif' : 'Aktif'
  toast.success(user.status === 'Aktif' ? `Akun ${user.name} diaktifkan.` : `Akun ${user.name} dinonaktifkan.`)
}

/* ---------------------------------- */
/* Modal: escape + kunci scroll body  */
/* ---------------------------------- */
const anyModalOpen = computed(() => showUserModal.value || showResetModal.value || showDeleteModal.value)

const handleEscKey = (e) => {
  if (e.key !== 'Escape') return
  if (showUserModal.value) showUserModal.value = false
  else if (showResetModal.value) showResetModal.value = false
  else if (showDeleteModal.value) showDeleteModal.value = false
}

watch(anyModalOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => window.addEventListener('keydown', handleEscKey))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscKey)
  document.body.style.overflow = ''
})

/* ---------------------------------- */
/* Logout (behavior existing)         */
/* ---------------------------------- */
const handleLogout = async () => {
  if (authStore?.logout) {
    await authStore.logout()
  }
  toast.success('Berhasil keluar dari sistem.')
  router.push({ name: 'login' })
}

const currentYear = new Date().getFullYear()
</script>

<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas ============ -->
    <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3 md:h-16 md:py-0">

          <!-- Merek -->
          <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20">
              <img v-if="!logoFailed" src="@/assets/logo sapa.jpeg" alt="Logo SAPA" class="h-full w-full object-cover" @error="logoFailed = true" />
              <span v-else class="text-sm font-extrabold text-emerald-400">S</span>
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-[15px] font-extrabold leading-none tracking-tight text-white">SAPA</p>
                <span class="rounded border border-blue-500/30 bg-blue-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-blue-400">Admin</span>
              </div>
              <p class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block">Manajemen Pengguna &amp; Hak Akses</p>
            </div>
          </div>

          <!-- Navigasi -->
          <nav class="no-scrollbar order-3 -mx-1 flex w-full items-center gap-1 overflow-x-auto pb-1 md:order-2 md:mx-0 md:w-auto md:border-l md:border-slate-800/80 md:pb-0 md:pl-6" aria-label="Navigasi utama">
            <router-link
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              :aria-current="isActive(item) ? 'page' : undefined"
              class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all duration-200"
              :class="isActive(item)
                ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                : 'border-transparent text-slate-400 hover:bg-slate-900 hover:text-slate-200'"
            >
              <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
              </svg>
              <span>{{ item.label }}</span>
            </router-link>
          </nav>

          <!-- Keluar -->
          <button
            type="button"
            @click="handleLogout"
            title="Keluar dari akun"
            class="order-2 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/80 text-slate-500 transition-all duration-200 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50 md:order-3"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main class="mx-auto w-full max-w-7xl flex-1 space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

      <!-- ===== Kepala halaman ===== -->
      <section class="fade-up">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div class="min-w-0">
            <div class="flex items-center gap-2.5">
              <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300/90">Panel Admin · Manajemen Pengguna</p>
            </div>
            <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Manajemen Pengguna</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">Kelola data pengguna, hak akses, peranan akun, serta pemantauan status aktivitas pada platform SAPA.</p>
          </div>

          <button
            type="button"
            @click="openCreateModal"
            class="group inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
          >
            <svg class="h-4 w-4 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah User Baru</span>
          </button>
        </div>
      </section>

      <!-- ===== Statistik pengguna ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex items-center gap-3">
          <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">Ringkasan Pengguna</h2>
            <p class="mt-0.5 text-xs text-slate-500">Komposisi peran dan status seluruh akun terdaftar.</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
          <article
            v-for="s in statCards"
            :key="s.label"
            class="group rounded-xl border border-slate-800 bg-slate-900/40 p-4 backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-700/80 sm:p-5"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ s.label }}</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight tabular-nums" :class="s.num">{{ s.value }}</p>
              </div>
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border transition-transform duration-200 group-hover:scale-110" :class="s.tile">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" :d="s.icon" />
                </svg>
              </div>
            </div>
            <div class="mt-4">
              <div class="h-1 w-full overflow-hidden rounded-full bg-slate-800">
                <div class="stat-bar h-full rounded-full" :class="s.bar" :style="{ width: s.pct + '%' }"></div>
              </div>
              <p class="mt-2 truncate text-[10px] text-slate-500">{{ s.caption }}</p>
            </div>
          </article>
        </div>
      </section>

      <!-- ===== Tabel pengguna ===== -->
      <section class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900" style="animation-delay: 180ms">

        <!-- Kepala seksi -->
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5">
          <div class="flex items-center gap-3">
            <span class="h-4 w-[3px] rounded-full bg-emerald-500" aria-hidden="true"></span>
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">Daftar Pengguna Terdaftar</h2>
              <p class="mt-0.5 text-xs text-slate-500">Kelola akun, peran, dan status aktivitas pengguna sistem.</p>
            </div>
          </div>
          <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400">{{ totalUsers }} Akun</span>
        </div>

        <!-- Toolbar filter -->
        <div class="space-y-4 border-b border-slate-800/80 p-4 sm:p-5">
          <div class="flex flex-col gap-3 sm:flex-row">
            <!-- Pencarian -->
            <div class="relative flex-1">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
              </svg>
              <input
                v-model="searchQuery"
                type="text"
                aria-label="Cari pengguna"
                placeholder="Cari berdasarkan Nama, Email, atau NIP/NISN..."
                class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              />
              <button
                v-if="searchQuery"
                type="button"
                @click="searchQuery = ''"
                aria-label="Bersihkan pencarian"
                class="absolute right-2.5 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-500 transition-colors duration-150 hover:bg-slate-800 hover:text-slate-200"
              >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Filter peran -->
            <div class="relative w-full sm:w-56">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <select
                v-model="selectedRole"
                aria-label="Filter peran"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              >
                <option value="all">Semua Peran</option>
                <option value="Siswa">Siswa</option>
                <option value="Guru">Guru (Sarpras)</option>
                <option value="Guru BK">Guru BK</option>
                <option value="Admin">Admin</option>
              </select>
              <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Pil status + ringkasan hasil -->
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-for="opt in statusPills"
              :key="opt.value"
              type="button"
              :aria-pressed="selectedStatus === opt.value"
              @click="selectedStatus = opt.value"
              class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
              :class="selectedStatus === opt.value ? opt.active : pillIdle"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80" aria-hidden="true"></span>
              {{ opt.label }}
              <span class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500">{{ statusCounts[opt.value] || 0 }}</span>
            </button>

            <div class="ml-auto flex items-center gap-3 pl-2">
              <p class="whitespace-nowrap text-[11px] text-slate-500">
                Menampilkan <span class="font-semibold tabular-nums text-slate-300">{{ filteredUsers.length }}</span> dari {{ totalUsers }} pengguna
              </p>
              <button
                v-if="hasActiveFilters"
                type="button"
                @click="clearFilters"
                class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-[11px] font-semibold text-slate-400 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
              >
                Atur ulang
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Label kolom (desktop lebar) -->
        <div class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.5fr)_140px_100px_105px_150px_160px] xl:gap-x-4">
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Pengguna</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">NIP / NISN</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Peran</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</p>
          <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Terakhir Aktif</p>
          <p class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</p>
        </div>

        <!-- Baris pengguna -->
        <div v-if="tableRows.length > 0" class="divide-y divide-slate-800/70">
          <article
            v-for="(user, i) in tableRows"
            :key="user.id"
            class="card-enter group relative flex flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.5fr)_140px_100px_105px_150px_160px] xl:items-center xl:gap-x-4"
            :style="{ animationDelay: (i * 60) + 'ms' }"
          >
            <!-- Aksen emerald saat hover -->
            <span class="absolute bottom-3 left-0 top-3 w-[3px] rounded-r-full bg-emerald-500/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100" aria-hidden="true"></span>

            <!-- Kolom pengguna -->
            <div class="flex min-w-0 items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold transition-colors duration-150" :class="user.avatarClass">
                {{ getInitials(user.name) }}
              </div>
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white">{{ user.name }}</p>
                <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ user.email }}</p>
              </div>
            </div>

            <!-- Meta: NIP, peran, status, aktivitas -->
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents">
              <!-- NIP / NISN -->
              <div class="min-w-0">
                <p class="truncate font-mono text-xs text-slate-400">{{ user.nip_nisn || '—' }}</p>
              </div>

              <!-- Peran -->
              <div>
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider" :class="user.roleBadge">
                  <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ user.role }}
                </span>
              </div>

              <!-- Status -->
              <div>
                <span
                  class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                  :class="user.isActive ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400' : 'border-slate-500/20 bg-slate-500/10 text-slate-300'"
                >
                  <span v-if="user.isActive" class="relative flex h-1.5 w-1.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                  </span>
                  <span v-else class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ user.status }}
                </span>
              </div>

              <!-- Terakhir aktif -->
              <div class="min-w-0">
                <p class="truncate font-mono text-[11px] text-slate-400">{{ user.last_login }}</p>
              </div>
            </div>

            <!-- Aksi -->
            <div class="flex flex-wrap items-center justify-end gap-1.5">
              <button
                type="button"
                @click="openEditModal(user)"
                title="Edit pengguna"
                aria-label="Edit pengguna"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 hover:border-slate-600 hover:text-white active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
              >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
              </button>

              <button
                type="button"
                @click="openResetPasswordModal(user)"
                title="Reset kata sandi"
                aria-label="Reset kata sandi"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 hover:border-amber-500/30 hover:bg-amber-500/10 hover:text-amber-400 active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
              >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                </svg>
              </button>

              <button
                type="button"
                @click="toggleUserStatus(user)"
                :title="user.isActive ? 'Nonaktifkan akun' : 'Aktifkan akun'"
                :aria-label="user.isActive ? 'Nonaktifkan akun' : 'Aktifkan akun'"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 active:scale-[.95] focus:outline-none focus-visible:ring-2"
                :class="user.isActive
                  ? 'hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 focus-visible:ring-rose-400/60'
                  : 'hover:border-emerald-500/30 hover:bg-emerald-500/10 hover:text-emerald-400 focus-visible:ring-emerald-400/60'"
              >
                <svg v-if="user.isActive" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </button>

              <button
                type="button"
                @click="openDeleteModal(user)"
                title="Hapus pengguna"
                aria-label="Hapus pengguna"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60"
              >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
              </button>
            </div>
          </article>
        </div>

        <!-- Keadaan kosong -->
        <div v-else class="flex flex-col items-center px-6 py-16 text-center">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
            <svg v-if="hasActiveFilters" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
            </svg>
            <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
          </div>
          <p class="mt-4 text-sm font-semibold text-slate-200">
            {{ hasActiveFilters ? 'Pengguna tidak ditemukan' : 'Belum ada pengguna terdaftar' }}
          </p>
          <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
            {{ hasActiveFilters
              ? 'Coba gunakan kata kunci lain atau atur ulang filter peran dan status.'
              : 'Tambahkan pengguna pertama untuk mulai mengelola akses sistem.' }}
          </p>

          <button
            v-if="hasActiveFilters"
            type="button"
            @click="clearFilters"
            class="mt-6 inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-xs font-semibold text-emerald-400 transition-all duration-200 hover:bg-emerald-500 hover:text-slate-950 active:scale-[.97]"
          >
            Atur Ulang Filter
          </button>
          <button
            v-else
            type="button"
            @click="openCreateModal"
            class="mt-6 inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97]"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Pengguna Pertama
          </button>
        </div>

        <!-- Kaki tabel: info + paginasi -->
        <div class="flex flex-col items-center justify-between gap-4 border-t border-slate-800/70 bg-slate-950/40 px-5 py-4 sm:flex-row sm:px-6">
          <p class="text-[11px] text-slate-500">
            Menampilkan
            <span class="font-semibold tabular-nums text-slate-300">{{ filteredUsers.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }}</span>
            sampai
            <span class="font-semibold tabular-nums text-slate-300">{{ Math.min(currentPage * itemsPerPage, filteredUsers.length) }}</span>
            dari
            <span class="font-semibold tabular-nums text-slate-300">{{ filteredUsers.length }}</span>
            entri
          </p>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="prevPage"
              :disabled="currentPage === 1"
              class="group inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-1.5 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-slate-700 hover:text-white active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
            >
              <svg class="h-3.5 w-3.5 transition-transform duration-150 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
              </svg>
              Sebelumnya
            </button>

            <span class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2.5 py-1 text-[11px] font-semibold tabular-nums text-slate-400">
              Halaman {{ currentPage }} / {{ totalPages }}
            </span>

            <button
              type="button"
              @click="nextPage"
              :disabled="currentPage >= totalPages"
              class="group inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-1.5 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-slate-700 hover:text-white active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
            >
              Selanjutnya
              <svg class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </section>
    </main>

    <!-- ============ Footer ============ -->
    <footer class="border-t border-slate-800/70">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
        <p class="text-[11px] text-slate-600">© {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan Sekolah</p>
        <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
          <svg class="h-3.5 w-3.5 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Perubahan hak akses pengguna tercatat pada jejak audit sistem
        </p>
      </div>
    </footer>

    <!-- ============ Modal form pengguna ============ -->
    <div
      v-if="showUserModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="user-form-title"
    >
      <div class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm" aria-hidden="true" @click="showUserModal = false"></div>

      <div class="modal-panel relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/50">
        <span class="absolute inset-x-0 top-0 z-20 h-[2px] bg-gradient-to-r from-emerald-500/70 via-emerald-500/20 to-transparent" aria-hidden="true"></span>

        <!-- Kepala modal -->
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-800/70 px-5 py-4">
          <div>
            <h3 id="user-form-title" class="text-sm font-bold tracking-tight text-slate-100">{{ isEditing ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' }}</h3>
            <p class="mt-0.5 text-[11px] text-slate-500">{{ isEditing ? 'Perbarui identitas, peran, dan status akun' : 'Lengkapi data akun baru pada sistem' }}</p>
          </div>
          <button
            type="button"
            @click="showUserModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Badan modal -->
        <form @submit.prevent="handleSaveUser" class="modal-scroll min-h-0 flex-1 space-y-5 overflow-y-auto p-5">

          <!-- Nama -->
          <div>
            <label for="uf-name" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Nama Lengkap</label>
            <div class="relative">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <input
                id="uf-name"
                v-model="userForm.name"
                type="text"
                autocomplete="name"
                placeholder="Masukkan nama lengkap"
                :aria-invalid="!!userErrors.name || undefined"
                class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                :class="userErrors.name ? 'border-red-400/60' : 'border-slate-800'"
              />
            </div>
            <p v-if="userErrors.name" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
              <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
              {{ userErrors.name }}
            </p>
          </div>

          <!-- Email -->
          <div>
            <label for="uf-email" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Alamat Email</label>
            <div class="relative">
              <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <input
                id="uf-email"
                v-model="userForm.email"
                type="email"
                autocomplete="email"
                placeholder="contoh@sekolah.sch.id"
                :aria-invalid="!!userErrors.email || undefined"
                class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                :class="userErrors.email ? 'border-red-400/60' : 'border-slate-800'"
              />
            </div>
            <p v-if="userErrors.email" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
              <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
              {{ userErrors.email }}
            </p>
          </div>

          <!-- NIP / NISN -->
          <div>
            <label for="uf-nip" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">NIP / NISN</label>
            <div class="relative">
              <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0h4m-6 6h2m-2 4h2m4-4h2m-2 4h2" />
              </svg>
              <input
                id="uf-nip"
                v-model="userForm.nip_nisn"
                type="text"
                autocomplete="off"
                placeholder="Masukkan NIP atau NISN"
                :aria-invalid="!!userErrors.nip_nisn || undefined"
                class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-9 pr-3 font-mono text-sm text-slate-100 placeholder-slate-600 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                :class="userErrors.nip_nisn ? 'border-red-400/60' : 'border-slate-800'"
              />
            </div>
            <p v-if="userErrors.nip_nisn" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
              <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
              {{ userErrors.nip_nisn }}
            </p>
          </div>

          <!-- Peran: kartu radio semantik -->
          <div class="space-y-2.5 border-t border-slate-800/70 pt-4">
            <span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Peran (Role)</span>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2" role="radiogroup" aria-label="Peran pengguna">
              <button
                v-for="opt in roleOptions"
                :key="opt.value"
                type="button"
                :aria-pressed="userForm.role === opt.value"
                @click="userForm.role = opt.value"
                class="flex items-center gap-2.5 rounded-lg border px-3 py-2.5 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="userForm.role === opt.value ? opt.active : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'"
              >
                <span class="h-2 w-2 shrink-0 rounded-full transition-colors duration-200" :class="userForm.role === opt.value ? opt.dot : 'bg-slate-600'" aria-hidden="true"></span>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-slate-100">{{ opt.label }}</span>
                  <span class="block text-[10px] leading-snug text-slate-500">{{ opt.desc }}</span>
                </span>
                <svg v-if="userForm.role === opt.value" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Status: kendali tersegmentasi -->
          <div class="space-y-2.5 border-t border-slate-800/70 pt-4">
            <span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Status Akun</span>
            <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Status akun">
              <button
                type="button"
                :aria-pressed="userForm.status === 'Aktif'"
                @click="userForm.status = 'Aktif'"
                class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="userForm.status === 'Aktif' ? 'border-emerald-500/50 bg-emerald-500/[0.06] text-emerald-400' : 'border-slate-800 bg-slate-950/40 text-slate-400 hover:border-slate-700'"
              >
                <span class="h-1.5 w-1.5 rounded-full" :class="userForm.status === 'Aktif' ? 'bg-emerald-400' : 'bg-slate-600'" aria-hidden="true"></span>
                Aktif
              </button>
              <button
                type="button"
                :aria-pressed="userForm.status === 'Nonaktif'"
                @click="userForm.status = 'Nonaktif'"
                class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="userForm.status === 'Nonaktif' ? 'border-slate-500/60 bg-slate-500/10 text-slate-200' : 'border-slate-800 bg-slate-950/40 text-slate-400 hover:border-slate-700'"
              >
                <span class="h-1.5 w-1.5 rounded-full" :class="userForm.status === 'Nonaktif' ? 'bg-slate-300' : 'bg-slate-600'" aria-hidden="true"></span>
                Nonaktif
              </button>
            </div>
          </div>

          <!-- Catatan hak akses -->
          <p class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500">
            <svg class="mt-px h-3 w-3 shrink-0 text-emerald-500/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Perubahan peran langsung memengaruhi hak akses kanal — Guru BK dapat mengakses data perundungan rahasia.
          </p>
        </form>

        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-slate-800/70 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showUserModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
            >
              Batal
            </button>
            <button
              type="submit"
              @click="handleSaveUser"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              {{ isEditing ? 'Simpan Perubahan' : 'Tambah User' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ Modal reset kata sandi ============ -->
    <div
      v-if="showResetModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="reset-title"
    >
      <div class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm" aria-hidden="true" @click="showResetModal = false"></div>

      <div class="modal-panel relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-amber-500/30 bg-slate-900 shadow-2xl shadow-black/50">
        <span class="absolute inset-x-0 top-0 z-20 h-[2px] bg-gradient-to-r from-amber-500/70 via-amber-500/20 to-transparent" aria-hidden="true"></span>

        <!-- Kepala modal -->
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-amber-500/20 px-5 py-4">
          <div>
            <h3 id="reset-title" class="text-sm font-bold tracking-tight text-slate-100">Reset Kata Sandi</h3>
            <p class="mt-0.5 text-[11px] text-slate-500">Atur ulang kata sandi akun ke nilai default sistem</p>
          </div>
          <button
            type="button"
            @click="showResetModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
          <!-- Ringkasan pengguna -->
          <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold" :class="selectedUser ? getRoleAvatarClass(selectedUser.role) : 'border-slate-700 bg-slate-800 text-slate-300'">
              {{ selectedUser ? getInitials(selectedUser.name) : '?' }}
            </div>
            <div class="min-w-0">
              <p class="truncate text-xs font-semibold text-slate-100">{{ selectedUser?.name }}</p>
              <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ selectedUser?.email }}</p>
            </div>
          </div>

          <p class="text-xs leading-relaxed text-slate-400">
            Konfirmasi pengaturan ulang kata sandi untuk
            <span class="font-semibold text-slate-200">{{ selectedUser?.name }}</span>.
            Kata sandi akan disetel ulang menjadi:
          </p>

          <div class="rounded-lg border border-amber-500/25 bg-amber-500/[0.06] p-3 text-center font-mono text-sm font-bold tracking-wider text-amber-300">
            SAPA2026!
          </div>

          <p class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500">
            <svg class="mt-px h-3 w-3 shrink-0 text-amber-400/70" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Pengguna wajib mengganti kata sandi default ini setelah berhasil masuk.
          </p>
        </div>

        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-amber-500/20 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showResetModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
            >
              Batal
            </button>
            <button
              type="button"
              @click="handleConfirmReset"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-amber-500/20 transition-all duration-200 hover:bg-amber-400 active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
              </svg>
              Konfirmasi Reset
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ Modal hapus pengguna ============ -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="delete-title"
    >
      <div class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm" aria-hidden="true" @click="showDeleteModal = false"></div>

      <div class="modal-panel relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-rose-500/30 bg-slate-900 shadow-2xl shadow-black/50">
        <span class="absolute inset-x-0 top-0 z-20 h-[2px] bg-gradient-to-r from-rose-500/70 via-rose-500/20 to-transparent" aria-hidden="true"></span>

        <!-- Kepala modal -->
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-rose-500/20 px-5 py-4">
          <div>
            <h3 id="delete-title" class="text-sm font-bold tracking-tight text-slate-100">Hapus Pengguna</h3>
            <p class="mt-0.5 text-[11px] text-slate-500">Tindakan permanen dan tidak dapat dibatalkan</p>
          </div>
          <button
            type="button"
            @click="showDeleteModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
          <!-- Ringkasan pengguna -->
          <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold" :class="selectedUser ? getRoleAvatarClass(selectedUser.role) : 'border-slate-700 bg-slate-800 text-slate-300'">
              {{ selectedUser ? getInitials(selectedUser.name) : '?' }}
            </div>
            <div class="min-w-0">
              <p class="truncate text-xs font-semibold text-slate-100">{{ selectedUser?.name }}</p>
              <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ selectedUser?.email }}</p>
            </div>
            <span v-if="selectedUser" class="ml-auto inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider" :class="getRoleBadgeClass(selectedUser.role)">
              {{ selectedUser.role }}
            </span>
          </div>

          <p class="text-xs leading-relaxed text-slate-400">
            Apakah Anda yakin ingin menghapus akun
            <span class="font-semibold text-slate-200">{{ selectedUser?.name }}</span>?
            Seluruh hak akses pengguna ini pada sistem akan dicabut secara permanen.
          </p>

          <!-- Peringatan khusus akun Admin -->
          <div v-if="selectedUser?.role === 'Admin'" class="flex items-start gap-2.5 rounded-lg border border-rose-500/25 bg-rose-500/[0.06] px-3.5 py-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-400/90" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-[11px] leading-relaxed text-slate-400">
              <span class="font-semibold text-rose-300">Akun Administrator.</span>
              Pastikan masih tersedia minimal satu administrator lain sebelum melanjutkan penghapusan.
            </p>
          </div>
        </div>
        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-amber-500/20 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showResetModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
            >
              Batal
            </button>
            <button
              type="button"
              @click="handleConfirmReset"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-amber-500/20 transition-all duration-200 hover:bg-amber-400 active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
              </svg>
              Konfirmasi Reset
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ Modal hapus pengguna ============ -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="delete-title"
    >
      <div class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm" aria-hidden="true" @click="showDeleteModal = false"></div>

      <div class="modal-panel relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-rose-500/30 bg-slate-900 shadow-2xl shadow-black/50">
        <span class="absolute inset-x-0 top-0 z-20 h-[2px] bg-gradient-to-r from-rose-500/70 via-rose-500/20 to-transparent" aria-hidden="true"></span>

        <!-- Kepala modal -->
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-rose-500/20 px-5 py-4">
          <div>
            <h3 id="delete-title" class="text-sm font-bold tracking-tight text-slate-100">Hapus Pengguna</h3>
            <p class="mt-0.5 text-[11px] text-slate-500">Tindakan permanen dan tidak dapat dibatalkan</p>
          </div>
          <button
            type="button"
            @click="showDeleteModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
          <!-- Ringkasan pengguna -->
          <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold" :class="selectedUser ? getRoleAvatarClass(selectedUser.role) : 'border-slate-700 bg-slate-800 text-slate-300'">
              {{ selectedUser ? getInitials(selectedUser.name) : '?' }}
            </div>
            <div class="min-w-0">
              <p class="truncate text-xs font-semibold text-slate-100">{{ selectedUser?.name }}</p>
              <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ selectedUser?.email }}</p>
            </div>
            <span v-if="selectedUser" class="ml-auto inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider" :class="getRoleBadgeClass(selectedUser.role)">
              {{ selectedUser.role }}
            </span>
          </div>

          <p class="text-xs leading-relaxed text-slate-400">
            Apakah Anda yakin ingin menghapus akun
            <span class="font-semibold text-slate-200">{{ selectedUser?.name }}</span>?
            Seluruh hak akses pengguna ini pada sistem akan dicabut secara permanen.
          </p>

          <!-- Peringatan khusus akun Admin -->
          <div v-if="selectedUser?.role === 'Admin'" class="flex items-start gap-2.5 rounded-lg border border-rose-500/25 bg-rose-500/[0.06] px-3.5 py-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-400/90" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-[11px] leading-relaxed text-slate-400">
              <span class="font-semibold text-rose-300">Akun Administrator.</span>
              Pastikan masih tersedia minimal satu administrator lain sebelum melanjutkan penghapusan.
            </p>
          </div>
        </div>

        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-rose-500/20 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showDeleteModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
            >
              Batal
            </button>
            <button
              type="button"
              @click="handleDeleteUser"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-rose-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-500/20 transition-all duration-200 hover:bg-rose-400 hover:text-slate-950 active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
              </svg>
              Hapus Akun
            </button>
          </div>
        </div>
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

/* Entrance baris: hanya opacity — interaksi hover tetap bekerja */
.card-enter {
  opacity: 0;
  animation: card-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes card-in {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* Bar statistik tumbuh dari kiri saat mount */
.stat-bar {
  transform-origin: left center;
  animation: grow-x 0.8s cubic-bezier(0.22, 1, 0.36, 1) 0.3s both;
}

@keyframes grow-x {
  from { transform: scaleX(0); }
  to   { transform: scaleX(1); }
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

/* Gulir tipis pada badan modal */
.modal-scroll::-webkit-scrollbar {
  width: 6px;
}

.modal-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.modal-scroll::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 3px;
}

/* Gulir horizontal nav mobile tanpa scrollbar */
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .card-enter,
  .stat-bar,
  .backdrop-in,
  .modal-panel { animation: none; opacity: 1; }
}
</style>