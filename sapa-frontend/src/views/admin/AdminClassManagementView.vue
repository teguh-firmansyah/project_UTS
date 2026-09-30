<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { useAuthStore } from "@/stores/auth";
import classService from "@/services/classService";
import NotificationBell from "@/components/shared/NotificationBell.vue";
import {
  BarChart3,
  FileText,
  Users,
  LogOut,
  Settings,
  Lightbulb,
  GraduationCap,
  Plus,
  Search,
  X,
  ChevronDown,
  Pencil,
  Trash2,
  Check,
  AlertTriangle,
  Loader2,
  UserCheck,
} from "lucide-vue-next";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const logoFailed = ref(false);
const isLoading = ref(true);

const navItems = [
  { label: "Analitik", to: "/admin/dashboard", icon: BarChart3 },
  { label: "Semua Laporan", to: "/admin/reports", icon: FileText },
  { label: "Aspirasi", to: "/admin/aspirations", icon: Lightbulb },
  { label: "Kelas", to: "/admin/classes", icon: GraduationCap },
  { label: "Manajemen User", to: "/admin/users", icon: Users },
  { label: "Pengaturan", to: "/admin/settings", icon: Settings },
];

const isActive = (item) =>
  route.path === item.to || route.path.startsWith(item.to + "/");

/* ---------------------------------- */
/* State filter                        */
/* ---------------------------------- */
const searchQuery = ref("");
const selectedGrade = ref("all");
const selectedYear = ref("all");

const gradeOptions = ["X", "XI", "XII"];

/* ---------------------------------- */
/* Data kelas — dari API               */
/* ---------------------------------- */
const classes = ref([]);
const totalClasses = ref(0);
const currentPage = ref(1);
const totalPages = ref(1);

async function loadClasses() {
  isLoading.value = true;
  try {
    const params = { page: currentPage.value };
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    if (selectedGrade.value !== "all") params.grade = selectedGrade.value;
    if (selectedYear.value !== "all") params.academic_year = selectedYear.value;

    const data = await classService.getClasses(params);

    classes.value = data.data.map((c) => ({
      id: c.id,
      name: c.name,
      grade: c.grade,
      major: c.major,
      sequence: c.sequence,
      academicYear: c.academic_year,
      studentsCount: c.students_count ?? 0,
    }));
    totalClasses.value = data.total ?? classes.value.length;
    totalPages.value = data.last_page ?? 1;
  } catch {
    toast.error("Gagal memuat daftar kelas.");
  } finally {
    isLoading.value = false;
  }
}

onMounted(loadClasses);

let searchDebounce = null;
watch(searchQuery, () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    currentPage.value = 1;
    loadClasses();
  }, 400);
});
watch([selectedGrade, selectedYear], () => {
  currentPage.value = 1;
  loadClasses();
});
watch(currentPage, loadClasses);

/* Daftar tahun ajaran unik dari data yang sudah dimuat, untuk filter */
const availableYears = computed(() => {
  const years = new Set(classes.value.map((c) => c.academicYear));
  return Array.from(years).sort().reverse();
});

const hasActiveFilters = computed(
  () =>
    searchQuery.value.trim() !== "" ||
    selectedGrade.value !== "all" ||
    selectedYear.value !== "all",
);

const clearFilters = () => {
  searchQuery.value = "";
  selectedGrade.value = "all";
  selectedYear.value = "all";
};

/* ---------------------------------- */
/* Statistik ringkas                   */
/* ---------------------------------- */
const stats = computed(() => ({
  total: totalClasses.value,
  totalStudents: classes.value.reduce((acc, c) => acc + c.studentsCount, 0),
  emptyClasses: classes.value.filter((c) => c.studentsCount === 0).length,
}));

/* ---------------------------------- */
/* Modal tambah/edit kelas             */
/* ---------------------------------- */
const showClassModal = ref(false);
const isEditing = ref(false);
const selectedClass = ref(null);
const isSaving = ref(false);

const classForm = ref({
  grade: "X",
  major: "",
  sequence: 1,
  academic_year: "",
});
const classErrors = ref({});

const currentYear = new Date().getFullYear();
const defaultAcademicYear = `${currentYear}/${currentYear + 1}`;

const openCreateModal = () => {
  isEditing.value = false;
  classForm.value = {
    grade: "X",
    major: "",
    sequence: 1,
    academic_year: defaultAcademicYear,
  };
  classErrors.value = {};
  showClassModal.value = true;
};

const openEditModal = (cls) => {
  isEditing.value = true;
  selectedClass.value = cls;
  classForm.value = {
    grade: cls.grade,
    major: cls.major,
    sequence: cls.sequence,
    academic_year: cls.academicYear,
  };
  classErrors.value = {};
  showClassModal.value = true;
};

function validateClassForm() {
  const e = {};
  if (!classForm.value.major.trim()) e.major = "Jurusan wajib diisi";
  if (!classForm.value.sequence || classForm.value.sequence < 1)
    e.sequence = "Nomor rombel minimal 1";
  if (!classForm.value.academic_year.trim())
    e.academic_year = "Tahun ajaran wajib diisi";
  else if (!/^\d{4}\/\d{4}$/.test(classForm.value.academic_year.trim())) {
    e.academic_year = "Format tidak valid, contoh: 2024/2025";
  }
  return e;
}

async function handleSaveClass() {
  classErrors.value = validateClassForm();
  if (Object.keys(classErrors.value).length > 0) return;

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await classService.updateClass(selectedClass.value.id, classForm.value);
      toast.success("Kelas berhasil diperbarui.");
    } else {
      await classService.createClass(classForm.value);
      toast.success("Kelas berhasil ditambahkan.");
    }
    showClassModal.value = false;
    await loadClasses();
  } catch (error) {
    const validationErrors = error?.response?.data?.errors;
    if (validationErrors) {
      classErrors.value = Object.fromEntries(
        Object.entries(validationErrors).map(([k, v]) => [k, v[0]]),
      );
    }
    toast.error(error?.response?.data?.message || "Gagal menyimpan kelas.");
  } finally {
    isSaving.value = false;
  }
}

/* ---------------------------------- */
/* Hapus kelas                         */
/* ---------------------------------- */
const showDeleteModal = ref(false);
const selectedForDelete = ref(null);
const isDeleting = ref(false);

const openDeleteModal = (cls) => {
  selectedForDelete.value = cls;
  showDeleteModal.value = true;
};

async function handleConfirmDelete() {
  isDeleting.value = true;
  try {
    await classService.deleteClass(selectedForDelete.value.id);
    toast.success("Kelas berhasil dihapus.");
    showDeleteModal.value = false;
    await loadClasses();
  } catch (error) {
    toast.error(error?.response?.data?.message || "Gagal menghapus kelas.");
  } finally {
    isDeleting.value = false;
  }
}

/* ---------------------------------- */
/* Modal handling                      */
/* ---------------------------------- */
const anyModalOpen = computed(
  () => showClassModal.value || showDeleteModal.value,
);

const handleEscKey = (e) => {
  if (e.key !== "Escape") return;
  if (showClassModal.value) showClassModal.value = false;
  else if (showDeleteModal.value) showDeleteModal.value = false;
};

watch(anyModalOpen, (open) => {
  document.body.style.overflow = open ? "hidden" : "";
});

onMounted(() => window.addEventListener("keydown", handleEscKey));
onBeforeUnmount(() => {
  window.removeEventListener("keydown", handleEscKey);
  document.body.style.overflow = "";
});

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--;
};
const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++;
};

const handleLogout = async () => {
  await authStore.logout();
  toast.success("Berhasil keluar dari sistem.");
  router.push({ name: "login" });
};

const footerYear = new Date().getFullYear();
</script>

<template>
  <div
    class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25"
  >
    <!-- ============ Bar atas ============ -->
    <header
      class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md"
    >
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div
          class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3 md:h-16 md:py-0"
        >
          <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
            <div
              class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20"
            >
              <img
                v-if="!logoFailed"
                src="@/assets/logo/logo sapa.jpeg"
                alt="Logo SAPA"
                class="h-full w-full object-cover"
                @error="logoFailed = true"
              />
              <span v-else class="text-sm font-extrabold text-emerald-400"
                >S</span
              >
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <p
                  class="text-[15px] font-extrabold leading-none tracking-tight text-white"
                >
                  SAPA
                </p>
                <span
                  class="rounded border border-blue-500/30 bg-blue-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-blue-400"
                  >Admin</span
                >
              </div>
              <p
                class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block"
              >
                Sistem Layanan Aspirasi &amp; Pengaduan sekolah
              </p>
            </div>
          </div>

          <nav
            class="no-scrollbar order-3 -mx-1 flex w-full items-center gap-1 overflow-x-auto pb-1 md:order-2 md:mx-0 md:w-auto md:border-l md:border-slate-800/80 md:pb-0 md:pl-6"
            aria-label="Navigasi utama"
          >
            <router-link
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              :aria-current="isActive(item) ? 'page' : undefined"
              class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all duration-200"
              :class="
                isActive(item)
                  ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                  : 'border-transparent text-slate-400 hover:bg-slate-900 hover:text-slate-200'
              "
            >
              <component :is="item.icon" class="h-3.5 w-3.5" />
              <span>{{ item.label }}</span>
            </router-link>
          </nav>

          <div class="order-2 flex items-center gap-2 md:order-3">
            <NotificationBell detail-route-name="admin-report-detail" />

            <button
              type="button"
              @click="handleLogout"
              title="Keluar dari akun"
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/80 text-slate-500 transition-all duration-200 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50"
            >
              <LogOut class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main
      class="mx-auto w-full max-w-7xl flex-1 space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10"
    >
      <!-- ===== Kepala halaman ===== -->
      <section class="fade-up">
        <div
          class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
        >
          <div class="min-w-0">
            <div class="flex items-center gap-2.5">
              <span
                class="h-2 w-2 rounded-full bg-emerald-500"
                aria-hidden="true"
              ></span>
              <p
                class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300/90"
              >
                Panel Admin · Manajemen Kelas
              </p>
            </div>
            <h1
              class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl"
            >
              Manajemen Kelas &amp; Rombel
            </h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
              Kelola daftar kelas, jurusan, dan tahun ajaran untuk penempatan
              siswa.
            </p>
          </div>

          <button
            type="button"
            @click="openCreateModal"
            class="group inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
          >
            <Plus
              class="h-4 w-4 transition-transform duration-300 group-hover:rotate-90"
            />
            <span>Tambah Kelas</span>
          </button>
        </div>
      </section>

      <!-- ===== Statistik ringkas ===== -->
      <section
        class="fade-up grid grid-cols-1 gap-4 sm:grid-cols-3"
        style="animation-delay: 60ms"
      >
        <div
          class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/40 p-4 backdrop-blur-sm"
        >
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-emerald-500/25 bg-emerald-500/10 text-emerald-400"
          >
            <GraduationCap class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="text-2xl font-extrabold tabular-nums text-white">
              {{ stats.total }}
            </p>
            <p
              class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500"
            >
              Total Kelas
            </p>
          </div>
        </div>
        <div
          class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/40 p-4 backdrop-blur-sm"
        >
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-blue-500/25 bg-blue-500/10 text-blue-400"
          >
            <UserCheck class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="text-2xl font-extrabold tabular-nums text-blue-400">
              {{ stats.totalStudents }}
            </p>
            <p
              class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500"
            >
              Siswa Ditempatkan
            </p>
          </div>
        </div>
        <div
          class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/40 p-4 backdrop-blur-sm"
        >
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-amber-500/25 bg-amber-500/10 text-amber-400"
          >
            <AlertTriangle class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="text-2xl font-extrabold tabular-nums text-amber-400">
              {{ stats.emptyClasses }}
            </p>
            <p
              class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500"
            >
              Kelas Kosong
            </p>
          </div>
        </div>
      </section>

      <!-- ===== Tabel kelas ===== -->
      <section
        class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
        style="animation-delay: 120ms"
      >
        <div
          class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5"
        >
          <div class="flex items-center gap-3">
            <span
              class="h-4 w-0.75 rounded-full bg-emerald-500"
              aria-hidden="true"
            ></span>
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">
                Daftar Kelas
              </h2>
              <p class="mt-0.5 text-xs text-slate-500">
                Kelola rombongan belajar dan penempatan siswa.
              </p>
            </div>
          </div>
          <span
            class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400"
            >{{ totalClasses }} Kelas</span
          >
        </div>

        <!-- Toolbar filter -->
        <div class="space-y-4 border-b border-slate-800/80 p-4 sm:p-5">
          <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
              <Search
                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
              <input
                v-model="searchQuery"
                type="text"
                aria-label="Cari kelas"
                placeholder="Cari nama kelas, contoh: XI RPL 1"
                class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              />
              <button
                v-if="searchQuery"
                type="button"
                @click="searchQuery = ''"
                aria-label="Bersihkan pencarian"
                class="absolute right-2.5 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-500 transition-colors duration-150 hover:bg-slate-800 hover:text-slate-200"
              >
                <X class="h-3.5 w-3.5" />
              </button>
            </div>

            <div class="relative w-full sm:w-44">
              <select
                v-model="selectedGrade"
                aria-label="Filter tingkat"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 px-3.5 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              >
                <option value="all">Semua Tingkat</option>
                <option v-for="g in gradeOptions" :key="g" :value="g">
                  Kelas {{ g }}
                </option>
              </select>
              <ChevronDown
                class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
            </div>

            <div class="relative w-full sm:w-48">
              <select
                v-model="selectedYear"
                aria-label="Filter tahun ajaran"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 px-3.5 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              >
                <option value="all">Semua Tahun Ajaran</option>
                <option v-for="y in availableYears" :key="y" :value="y">
                  {{ y }}
                </option>
              </select>
              <ChevronDown
                class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
            </div>
          </div>

          <div class="flex items-center justify-between gap-3">
            <p class="whitespace-nowrap text-[11px] text-slate-500">
              Menampilkan
              <span class="font-semibold tabular-nums text-slate-300">{{
                classes.length
              }}</span>
              dari {{ totalClasses }} kelas
            </p>
            <button
              v-if="hasActiveFilters"
              type="button"
              @click="clearFilters"
              class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-[11px] font-semibold text-slate-400 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
            >
              Atur ulang
              <X class="h-3 w-3" />
            </button>
          </div>
        </div>

        <!-- Label kolom -->
        <div
          class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1fr)_130px_130px_140px_150px] xl:gap-x-4"
        >
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Nama Kelas
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Jurusan
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Tahun Ajaran
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Jumlah Siswa
          </p>
          <p
            class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Aksi
          </p>
        </div>

        <!-- Skeleton -->
        <div v-if="isLoading" class="divide-y divide-slate-800/70">
          <div v-for="i in 5" :key="i" class="px-5 py-4 sm:px-6">
            <div class="h-4 w-32 rounded bg-slate-800 animate-pulse mb-2"></div>
            <div class="h-3 w-48 rounded bg-slate-800 animate-pulse"></div>
          </div>
        </div>

        <!-- Baris kelas -->
        <div
          v-else-if="classes.length > 0"
          class="divide-y divide-slate-800/70"
        >
          <article
            v-for="cls in classes"
            :key="cls.id"
            class="group relative flex flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1fr)_130px_130px_140px_150px] xl:items-center xl:gap-x-4"
          >
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-100">{{ cls.name }}</p>
              <p class="mt-0.5 text-[11px] text-slate-500">
                Tingkat {{ cls.grade }} · Rombel {{ cls.sequence }}
              </p>
            </div>

            <div
              class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents"
            >
              <div>
                <span
                  class="inline-flex items-center rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-medium text-emerald-400"
                  >{{ cls.major }}</span
                >
              </div>
              <div>
                <span class="font-mono text-xs text-slate-400">{{
                  cls.academicYear
                }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <UserCheck class="h-3.5 w-3.5 text-slate-500" />
                <span class="text-xs font-semibold tabular-nums text-slate-300"
                  >{{ cls.studentsCount }} siswa</span
                >
              </div>
            </div>

            <div class="flex items-center justify-end gap-1.5">
              <button
                type="button"
                @click="openEditModal(cls)"
                title="Edit kelas"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 hover:border-slate-600 hover:text-white active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
              >
                <Pencil class="h-3.5 w-3.5" />
              </button>
              <button
                type="button"
                @click="openDeleteModal(cls)"
                title="Hapus kelas"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60"
              >
                <Trash2 class="h-3.5 w-3.5" />
              </button>
            </div>
          </article>
        </div>

        <!-- Keadaan kosong -->
        <div v-else class="flex flex-col items-center px-6 py-16 text-center">
          <div
            class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400"
          >
            <Search v-if="hasActiveFilters" class="h-6 w-6" />
            <GraduationCap v-else class="h-6 w-6" />
          </div>
          <p class="mt-4 text-sm font-semibold text-slate-200">
            {{
              hasActiveFilters
                ? "Kelas tidak ditemukan"
                : "Belum ada kelas terdaftar"
            }}
          </p>
          <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
            {{
              hasActiveFilters
                ? "Coba gunakan kata kunci lain atau atur ulang filter."
                : "Tambahkan kelas pertama untuk mulai menempatkan siswa."
            }}
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
            <Plus class="h-4 w-4" />
            Tambah Kelas Pertama
          </button>
        </div>

        <!-- Paginasi -->
        <div
          v-if="totalPages > 1"
          class="flex items-center justify-between gap-3 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 sm:px-6"
        >
          <button
            type="button"
            @click="prevPage"
            :disabled="currentPage === 1"
            class="inline-flex items-center rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-1.5 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
          >
            Sebelumnya
          </button>
          <span class="text-[11px] text-slate-500"
            >Halaman {{ currentPage }} / {{ totalPages }}</span
          >
          <button
            type="button"
            @click="nextPage"
            :disabled="currentPage >= totalPages"
            class="inline-flex items-center rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-1.5 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
          >
            Selanjutnya
          </button>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-800/70">
      <div
        class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8"
      >
        <p class="text-[11px] text-slate-600">
          © {{ footerYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan
          Sekolah
        </p>
      </div>
    </footer>

    <!-- ============ Modal tambah/edit kelas ============ -->
    <div
      v-if="showClassModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="class-form-title"
    >
      <div
        class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        aria-hidden="true"
        @click="showClassModal = false"
      ></div>

      <div
        class="modal-panel relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/50"
      >
        <span
          class="absolute inset-x-0 top-0 z-20 h-0.5 bg-linear-to-r from-emerald-500/70 via-emerald-500/20 to-transparent"
          aria-hidden="true"
        ></span>

        <div
          class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-800/70 px-5 py-4"
        >
          <div>
            <h3
              id="class-form-title"
              class="text-sm font-bold tracking-tight text-slate-100"
            >
              {{ isEditing ? "Edit Kelas" : "Tambah Kelas Baru" }}
            </h3>
            <p class="mt-0.5 text-[11px] text-slate-500">
              {{
                isEditing
                  ? "Perbarui data kelas"
                  : "Lengkapi data rombongan belajar baru"
              }}
            </p>
          </div>
          <button
            type="button"
            @click="showClassModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <form
          @submit.prevent="handleSaveClass"
          class="modal-scroll min-h-0 flex-1 space-y-5 overflow-y-auto p-5"
        >
          <div>
            <label
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Tingkat</label
            >
            <select
              v-model="classForm.grade"
              class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 px-3.5 text-sm text-slate-100 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
            >
              <option v-for="g in gradeOptions" :key="g" :value="g">
                Kelas {{ g }}
              </option>
            </select>
          </div>

          <div>
            <label
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Jurusan</label
            >
            <input
              v-model="classForm.major"
              type="text"
              placeholder="Contoh: RPL, TKJ, AKL"
              :aria-invalid="!!classErrors.major || undefined"
              class="w-full rounded-lg border bg-slate-950/60 py-2.5 px-3.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              :class="
                classErrors.major ? 'border-red-400/60' : 'border-slate-800'
              "
            />
            <p
              v-if="classErrors.major"
              class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400"
            >
              <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
              {{ classErrors.major }}
            </p>
          </div>

          <div>
            <label
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Nomor Rombel</label
            >
            <input
              v-model.number="classForm.sequence"
              type="number"
              min="1"
              placeholder="1"
              :aria-invalid="!!classErrors.sequence || undefined"
              class="w-full rounded-lg border bg-slate-950/60 py-2.5 px-3.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              :class="
                classErrors.sequence ? 'border-red-400/60' : 'border-slate-800'
              "
            />
            <p
              v-if="classErrors.sequence"
              class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400"
            >
              <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
              {{ classErrors.sequence }}
            </p>
          </div>

          <div>
            <label
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Tahun Ajaran</label
            >
            <input
              v-model="classForm.academic_year"
              type="text"
              placeholder="2024/2025"
              :aria-invalid="!!classErrors.academic_year || undefined"
              class="w-full rounded-lg border bg-slate-950/60 py-2.5 px-3.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              :class="
                classErrors.academic_year
                  ? 'border-red-400/60'
                  : 'border-slate-800'
              "
            />
            <p
              v-if="classErrors.academic_year"
              class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400"
            >
              <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
              {{ classErrors.academic_year }}
            </p>
          </div>

          <p
            class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500"
          >
            <Check class="mt-px h-3 w-3 shrink-0 text-emerald-500/70" />
            Nama kelas akan otomatis terbentuk, contoh: "{{ classForm.grade }}
            {{ classForm.major || "..." }} {{ classForm.sequence }}"
          </p>
        </form>

        <div class="shrink-0 border-t border-slate-800/70 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showClassModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto"
            >
              Batal
            </button>
            <button
              type="button"
              @click="handleSaveClass"
              :disabled="isSaving"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
            >
              <Check v-if="!isSaving" class="h-4 w-4" />
              <Loader2 v-else class="h-4 w-4 animate-spin" />
              {{
                isSaving
                  ? "Menyimpan..."
                  : isEditing
                    ? "Simpan Perubahan"
                    : "Tambah Kelas"
              }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ Modal hapus kelas ============ -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="delete-class-title"
    >
      <div
        class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        aria-hidden="true"
        @click="showDeleteModal = false"
      ></div>

      <div
        class="modal-panel relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-rose-500/30 bg-slate-900 shadow-2xl shadow-black/50"
      >
        <span
          class="absolute inset-x-0 top-0 z-20 h-0.5 bg-linear-to-r from-rose-500/70 via-rose-500/20 to-transparent"
          aria-hidden="true"
        ></span>

        <div
          class="flex shrink-0 items-start justify-between gap-4 border-b border-rose-500/20 px-5 py-4"
        >
          <div>
            <h3
              id="delete-class-title"
              class="text-sm font-bold tracking-tight text-slate-100"
            >
              Hapus Kelas
            </h3>
            <p class="mt-0.5 text-[11px] text-slate-500">
              Tindakan permanen dan tidak dapat dibatalkan
            </p>
          </div>
          <button
            type="button"
            @click="showDeleteModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
          <div
            class="rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3"
          >
            <p class="text-sm font-semibold text-slate-200">
              {{ selectedForDelete?.name }}
            </p>
            <p class="mt-0.5 text-[11px] text-slate-500">
              {{ selectedForDelete?.academicYear }} ·
              {{ selectedForDelete?.studentsCount }} siswa
            </p>
          </div>

          <p class="text-xs leading-relaxed text-slate-400">
            Apakah Anda yakin ingin menghapus kelas ini secara permanen?
          </p>

          <div
            v-if="selectedForDelete?.studentsCount > 0"
            class="flex items-start gap-2.5 rounded-lg border border-amber-500/25 bg-amber-500/6 px-3.5 py-3"
          >
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-400/90" />
            <p class="text-[11px] leading-relaxed text-slate-400">
              <span class="font-semibold text-amber-300"
                >Kelas masih memiliki siswa.</span
              >
              Pindahkan siswa ke kelas lain terlebih dahulu sebelum menghapus
              kelas ini.
            </p>
          </div>
        </div>

        <div class="shrink-0 border-t border-rose-500/20 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showDeleteModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto"
            >
              Batal
            </button>
            <button
              type="button"
              @click="handleConfirmDelete"
              :disabled="isDeleting || selectedForDelete?.studentsCount > 0"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-rose-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-500/20 transition-all duration-200 hover:bg-rose-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
            >
              <Trash2 v-if="!isDeleting" class="h-4 w-4" />
              <Loader2 v-else class="h-4 w-4 animate-spin" />
              {{ isDeleting ? "Menghapus..." : "Hapus Permanen" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-up {
  opacity: 0;
  animation: fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes fade-up {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.backdrop-in {
  animation: backdrop-in 0.25s ease both;
}
@keyframes backdrop-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
.modal-panel {
  animation: modal-in 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes modal-in {
  from {
    opacity: 0;
    transform: translateY(16px) scale(0.97);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
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
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .backdrop-in,
  .modal-panel {
    animation: none;
    opacity: 1;
  }
}
</style>
