import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [

  // ================= PROFILE (DYNAMIC REDIRECT & ROLE BASED) =================
  {
    path: '/profile',
    name: 'profile',
    redirect: (to) => {
      const authStore = useAuthStore()
      if (authStore.hasRole('counselor')) return { name: 'counselor-profile' }
      if (authStore.hasRole('student')) return { name: 'student-profile' }
      return { name: 'student-profile' } // default fallback
    },
    meta: { requiresAuth: true },
  },
  {
    path: '/student/profile',
    name: 'student-profile',
    component: () => import('@/views/student/ProfileView.vue'),
    meta: { requiresAuth: true, roles: ['student'] },
  },
  {
    path: '/counselor/profile',
    name: 'counselor-profile',
    component: () => import('@/views/counselor/ProfileView.vue'),
    meta: { requiresAuth: true, roles: ['counselor'] },
  },

  // ================= PUBLIC =================
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { guestOnly: true },
  },

  // ================= SISWA =================
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/views/student/DashboardView.vue'),
    meta: { requiresAuth: true, roles: ['student'] },
  },
  {
    path: '/reports',
    name: 'reports-list',
    component: () => import('@/views/student/ReportListView.vue'),
    meta: { requiresAuth: true, roles: ['student'] },
  },
  {
    path: '/reports/new',
    name: 'report-create',
    component: () => import('@/views/student/CreateReportView.vue'),
    meta: { requiresAuth: true, roles: ['student'] },
  },
  {
    path: '/reports/:id',
    name: 'report-detail',
    component: () => import('@/views/student/ReportDetailView.vue'),
    meta: { requiresAuth: true },
    props: true,
  },
  {
    path: '/aspirations',
    name: 'aspiration-feed',
    component: () => import('@/views/student/AspirationFeedView.vue'),
    meta: { requiresAuth: true },
  },

  // ================= STAFF =================
  {
    path: '/staff/facility-queue',
    name: 'staff-facility-queue',
    component: () => import('@/views/staff/FacilityQueueView.vue'),
    meta: { requiresAuth: true, roles: ['staff'] },
  },

  // ================= COUNSELOR (BK) =================
  {
    path: '/counselor/bullying-queue',
    name: 'counselor-bullying-queue',
    component: () => import('@/views/counselor/BullyingQueueView.vue'),
    meta: { requiresAuth: true, roles: ['counselor'] },
  },
  {
    path: '/counselor/bullying-reports/:id',
    name: 'counselor-bullying-detail',
    component: () => import('@/views/counselor/BullyingDetailView.vue'),
    meta: { requiresAuth: true, roles: ['counselor'] },
    props: true,
  },
  {
    path: '/counselor/archived-reports',
    name: 'counselor-archived-reports',
    component: () => import('@/views/counselor/ArchivedReportsView.vue'),
    meta: { requiresAuth: true, roles: ['counselor'] },
  },

  // ================= ADMIN =================
  {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: () => import('@/views/admin/DashboardView.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    component: () => import('@/views/admin/UserManagementView.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
  },

  // ================= FALLBACK =================
  {
    path: '/unauthorized',
    name: 'unauthorized',
    component: () => import('@/views/errors/UnauthorizedView.vue'),
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/errors/NotFoundView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  // Tunggu pengecekan auth awal selesai dulu (hanya sekali, saat app pertama load)
  if (!authStore.isInitialized) {
    await authStore.fetchCurrentUser()
  }

  // langsung arahkan ke dashboard sesuai role-nya, sebelum cek meta.roles
  if (to.path === '/' && authStore.isAuthenticated && !authStore.isStudent) {
    return authStore.defaultRoute
  }

  // Halaman yang butuh login, tapi user belum login
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Halaman khusus tamu (login/register), tapi user sudah login
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return { name: 'dashboard' }
  }

  // Cek role — kalau route punya batasan role dan user tidak punya salah satunya
  if (to.meta.roles && !to.meta.roles.some((role) => authStore.hasRole(role))) {
    return { name: 'unauthorized' }
  }

  return true
})

export default router