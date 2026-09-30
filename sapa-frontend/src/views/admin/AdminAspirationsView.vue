<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { toast } from "vue-sonner";
import { useAuthStore } from "@/stores/auth";
import reportService from "@/services/reportService";
import NotificationBell from "@/components/shared/NotificationBell.vue";
import {
  BarChart3,
  FileText,
  Users,
  LogOut,
  Search,
  X,
  Filter,
  ChevronDown,
  Lock,
  Eye,
  Lightbulb,
  TrendingUp,
  MessageSquare,
  Settings,
  GraduationCap,
} from "lucide-vue-next";

const router = useRouter();
const route = useRoute();
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
const selectedStatus = ref("all");
const selectedCategory = ref("all");

const STATUS_MAP = {
  pending: "Menunggu",
  reviewing: "Ditinjau",
  in_progress: "Diproses",
  resolved: "Selesai",
  rejected: "Ditolak",
};
const STATUS_MAP_REVERSE = {
  Menunggu: "pending",
  Ditinjau: "reviewing",
  Diproses: "in_progress",
  Selesai: "resolved",
  Ditolak: "rejected",
};
const CATEGORY_MAP = {
  academic: "Akademik",
  facility_policy: "Kebijakan Fasilitas",
  school_policy: "Kebijakan Sekolah",
  other: "Lainnya",
};

/* ---------------------------------- */
/* Data aspirasi — dari API            */
/* ---------------------------------- */
const aspirations = ref([]);
const totalCount = ref(0);

async function loadAspirations() {
  isLoading.value = true;
  try {
    const params = {};
    if (selectedStatus.value !== "all")
      params.status = STATUS_MAP_REVERSE[selectedStatus.value];
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();

    const data = await reportService.getAdminAspirations(params);

    aspirations.value = data.data.map((a) => ({
      id: a.id,
      report_code: a.report_code,
      title: a.title,
      excerpt: a.description_excerpt,
      status: STATUS_MAP[a.status] ?? a.status,
      is_anonymous: a.is_anonymous,
      reporter_name: a.is_anonymous ? "Anonim" : (a.reporter?.name ?? "—"),
      reporter_class: a.is_anonymous ? "—" : (a.reporter?.class_name ?? "—"),
      category: CATEGORY_MAP[a.category] ?? a.category ?? "—",
      upvotes: a.upvotes_count,
      isPublic: a.is_public,
      commentsCount: a.comments_count,
      createdAt: formatDate(a.created_at),
    }));
    totalCount.value = data.total ?? aspirations.value.length;
  } catch {
    toast.error("Gagal memuat daftar aspirasi.");
  } finally {
    isLoading.value = false;
  }
}

onMounted(loadAspirations);

let searchDebounce = null;
watch(searchQuery, () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(loadAspirations, 400);
});
watch(selectedStatus, loadAspirations);

/* Filter kategori tetap di client karena backend belum sediakan filter khusus ini di list gabungan */
const filteredAspirations = computed(() => {
  if (selectedCategory.value === "all") return aspirations.value;
  return aspirations.value.filter((a) => a.category === selectedCategory.value);
});

const hasActiveFilters = computed(
  () =>
    searchQuery.value.trim() !== "" ||
    selectedStatus.value !== "all" ||
    selectedCategory.value !== "all",
);

const clearFilters = () => {
  searchQuery.value = "";
  selectedStatus.value = "all";
  selectedCategory.value = "all";
};

const statusPills = [
  {
    value: "all",
    label: "Semua",
    active: "border-emerald-500/50 bg-emerald-500/15 text-emerald-400",
  },
  {
    value: "Menunggu",
    label: "Menunggu",
    active: "border-amber-500/50 bg-amber-500/15 text-amber-400",
  },
  {
    value: "Diproses",
    label: "Diproses",
    active: "border-emerald-500/50 bg-emerald-500/15 text-emerald-400",
  },
  {
    value: "Selesai",
    label: "Selesai",
    active: "border-slate-600 bg-slate-700/40 text-slate-200",
  },
  {
    value: "Ditolak",
    label: "Ditolak",
    active: "border-red-500/50 bg-red-500/15 text-red-400",
  },
];

const pillIdle =
  "border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300";

const statusCounts = computed(() => {
  const counts = { all: aspirations.value.length };
  for (const a of aspirations.value)
    counts[a.status] = (counts[a.status] || 0) + 1;
  return counts;
});

const getStatusBadgeClass = (status) => {
  switch (status) {
    case "Selesai":
      return "bg-slate-500/10 text-slate-300 border-slate-500/20";
    case "Diproses":
      return "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
    case "Ditinjau":
      return "bg-blue-500/10 text-blue-400 border-blue-500/20";
    case "Menunggu":
      return "bg-amber-500/10 text-amber-400 border-amber-500/20";
    case "Ditolak":
      return "bg-red-500/10 text-red-400 border-red-500/20";
    default:
      return "bg-slate-800 text-slate-300 border-slate-700";
  }
};

const initialsOf = (name) => {
  if (!name || typeof name !== "string" || name === "—" || name === "Anonim")
    return "?";
  const parts = name.trim().split(/\s+/);
  return parts.length > 1
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.slice(0, 2).toUpperCase();
};

function formatDate(d) {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

/* Statistik ringkas */
const stats = computed(() => ({
  total: totalCount.value,
  totalVotes: aspirations.value.reduce((acc, a) => acc + a.upvotes, 0),
  totalComments: aspirations.value.reduce((acc, a) => acc + a.commentsCount, 0),
}));

/* ---------------------------------- */
/* Navigasi ke detail                  */
/* ---------------------------------- */
const goToDetail = (id) =>
  router.push({ name: "admin-report-detail", params: { id } });

const handleLogout = async () => {
  await authStore.logout();
  toast.success("Berhasil keluar dari sistem.");
  router.push({ name: "login" });
};

const currentYear = new Date().getFullYear();
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
        <div class="flex items-center gap-2.5">
          <span
            class="h-2 w-2 rounded-full bg-purple-500"
            aria-hidden="true"
          ></span>
          <p
            class="text-[11px] font-semibold uppercase tracking-[0.18em] text-purple-300/90"
          >
            Panel Admin · Pemantauan Aspirasi
          </p>
        </div>
        <h1
          class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl"
        >
          Aspirasi Siswa
        </h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-400">
          Pantau seluruh aspirasi yang disampaikan siswa — akses baca, tanpa hak
          memberi dukungan.
        </p>
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
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-purple-500/25 bg-purple-500/10 text-purple-400"
          >
            <Lightbulb class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="text-2xl font-extrabold tabular-nums text-white">
              {{ stats.total }}
            </p>
            <p
              class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500"
            >
              Total Aspirasi
            </p>
          </div>
        </div>
        <div
          class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/40 p-4 backdrop-blur-sm"
        >
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-emerald-500/25 bg-emerald-500/10 text-emerald-400"
          >
            <TrendingUp class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="text-2xl font-extrabold tabular-nums text-emerald-400">
              {{ stats.totalVotes }}
            </p>
            <p
              class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500"
            >
              Total Dukungan
            </p>
          </div>
        </div>
        <div
          class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/40 p-4 backdrop-blur-sm"
        >
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-blue-500/25 bg-blue-500/10 text-blue-400"
          >
            <MessageSquare class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="text-2xl font-extrabold tabular-nums text-blue-400">
              {{ stats.totalComments }}
            </p>
            <p
              class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500"
            >
              Total Diskusi
            </p>
          </div>
        </div>
      </section>

      <!-- ===== Tabel aspirasi ===== -->
      <section
        class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
        style="animation-delay: 120ms"
      >
        <div
          class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5"
        >
          <div class="flex items-center gap-3">
            <span
              class="h-4 w-0.75 rounded-full bg-purple-500"
              aria-hidden="true"
            ></span>
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">
                Daftar Aspirasi Siswa
              </h2>
              <p class="mt-0.5 text-xs text-slate-500">
                Akses baca — pemberian dukungan hanya oleh siswa.
              </p>
            </div>
          </div>
          <span
            class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400"
            >{{ totalCount }} Aspirasi</span
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
                aria-label="Cari aspirasi"
                placeholder="Cari judul atau isi aspirasi..."
                class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-purple-500/60 focus:outline-none focus:ring-2 focus:ring-purple-500/15"
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

            <div class="relative w-full sm:w-56">
              <Filter
                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
              <select
                v-model="selectedCategory"
                aria-label="Filter kategori"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-purple-500/60 focus:outline-none focus:ring-2 focus:ring-purple-500/15"
              >
                <option value="all">Semua Kategori</option>
                <option value="Akademik">Akademik</option>
                <option value="Kebijakan Fasilitas">Kebijakan Fasilitas</option>
                <option value="Kebijakan Sekolah">Kebijakan Sekolah</option>
                <option value="Lainnya">Lainnya</option>
              </select>
              <ChevronDown
                class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button
              v-for="opt in statusPills"
              :key="opt.value"
              type="button"
              :aria-pressed="selectedStatus === opt.value"
              @click="selectedStatus = opt.value"
              class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400/50"
              :class="selectedStatus === opt.value ? opt.active : pillIdle"
            >
              <span
                class="h-1.5 w-1.5 rounded-full bg-current opacity-80"
                aria-hidden="true"
              ></span>
              {{ opt.label }}
              <span
                class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500"
                >{{ statusCounts[opt.value] || 0 }}</span
              >
            </button>

            <div class="ml-auto flex items-center gap-3 pl-2">
              <p class="whitespace-nowrap text-[11px] text-slate-500">
                Menampilkan
                <span class="font-semibold tabular-nums text-slate-300">{{
                  filteredAspirations.length
                }}</span>
                dari {{ totalCount }} aspirasi
              </p>
              <button
                v-if="hasActiveFilters"
                type="button"
                @click="clearFilters"
                class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-[11px] font-semibold text-slate-400 transition-all duration-150 hover:border-purple-500/40 hover:text-purple-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400/50"
              >
                Atur ulang
                <X class="h-3 w-3" />
              </button>
            </div>
          </div>
        </div>

        <!-- Label kolom -->
        <div
          class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.6fr)_160px_120px_115px_105px_130px] xl:gap-x-4"
        >
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Aspirasi
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Pelapor
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Kategori
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Status
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Dukungan
          </p>
          <p
            class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Aksi
          </p>
        </div>

        <!-- Skeleton loading -->
        <div v-if="isLoading" class="divide-y divide-slate-800/70">
          <div v-for="i in 4" :key="i" class="px-5 py-4 sm:px-6">
            <div class="h-4 w-28 rounded bg-slate-800 animate-pulse mb-2"></div>
            <div class="h-4 w-64 rounded bg-slate-800 animate-pulse"></div>
          </div>
        </div>

        <!-- Baris aspirasi -->
        <div
          v-else-if="filteredAspirations.length > 0"
          class="divide-y divide-slate-800/70"
        >
          <article
            v-for="item in filteredAspirations"
            :key="item.id"
            class="group relative flex cursor-pointer flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.6fr)_160px_120px_115px_105px_130px] xl:items-center xl:gap-x-4"
            @click="goToDetail(item.id)"
          >
            <span
              class="absolute bottom-3 left-0 top-3 w-0.75 rounded-r-full bg-purple-500/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
              aria-hidden="true"
            ></span>

            <div class="min-w-0">
              <span
                class="rounded border border-purple-500/20 bg-purple-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-purple-400"
                >{{ item.report_code }}</span
              >
              <h3
                class="mt-2 truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white"
              >
                {{ item.title }}
              </h3>
              <p class="mt-0.5 truncate text-[11px] text-slate-500">
                {{ item.excerpt }}
              </p>
              <p class="mt-0.5 text-[10px] text-slate-600">
                {{ item.createdAt }}
              </p>
            </div>

            <div
              class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents"
            >
              <div class="min-w-0">
                <p
                  v-if="item.is_anonymous"
                  class="flex items-center gap-1.5 text-xs font-semibold text-slate-300"
                >
                  <Lock class="h-3.5 w-3.5 shrink-0 text-slate-500" />
                  Anonim
                </p>
                <template v-else>
                  <p class="truncate text-xs font-semibold text-slate-200">
                    {{ item.reporter_name }}
                  </p>
                  <p class="mt-0.5 truncate text-[10px] text-slate-500">
                    {{ item.reporter_class }}
                  </p>
                </template>
              </div>

              <div>
                <span
                  class="inline-flex items-center rounded-full border border-slate-700 bg-slate-800/60 px-2.5 py-0.5 text-[11px] font-medium text-slate-300"
                  >{{ item.category }}</span
                >
              </div>

              <div>
                <span
                  class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                  :class="getStatusBadgeClass(item.status)"
                >
                  <span
                    class="h-1.5 w-1.5 rounded-full bg-current"
                    aria-hidden="true"
                  ></span>
                  {{ item.status }}
                </span>
              </div>

              <div class="flex items-center gap-1.5">
                <TrendingUp class="h-3.5 w-3.5 text-emerald-400" />
                <span
                  class="text-xs font-semibold tabular-nums text-emerald-400"
                  >{{ item.upvotes }}</span
                >
              </div>
            </div>

            <div class="flex justify-end">
              <button
                type="button"
                @click.stop="goToDetail(item.id)"
                class="inline-flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-2 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-purple-500/40 hover:bg-purple-500/10 hover:text-purple-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400/60 sm:w-auto"
              >
                <Eye class="h-3.5 w-3.5" />
                <span>Lihat Detail</span>
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
            <Lightbulb v-else class="h-6 w-6" />
          </div>
          <p class="mt-4 text-sm font-semibold text-slate-200">
            {{
              hasActiveFilters
                ? "Aspirasi tidak ditemukan"
                : "Belum ada aspirasi"
            }}
          </p>
          <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
            {{
              hasActiveFilters
                ? "Coba gunakan kata kunci lain atau atur ulang filter."
                : "Aspirasi yang disampaikan siswa akan muncul di sini."
            }}
          </p>
          <button
            v-if="hasActiveFilters"
            type="button"
            @click="clearFilters"
            class="mt-6 inline-flex items-center gap-1.5 rounded-lg border border-purple-500/30 bg-purple-500/10 px-4 py-2 text-xs font-semibold text-purple-400 transition-all duration-200 hover:bg-purple-500 hover:text-slate-950 active:scale-[.97]"
          >
            Atur Ulang Filter
          </button>
        </div>

        <!-- Kaki -->
        <div
          class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 sm:px-6"
        >
          <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
            <Lock class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />
            Admin memiliki akses baca — pemberian dukungan hanya tersedia bagi
            siswa
          </p>
          <p class="whitespace-nowrap text-[11px] text-slate-600">
            {{ totalCount }} aspirasi terdaftar
          </p>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-800/70">
      <div
        class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8"
      >
        <p class="text-[11px] text-slate-600">
          © {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan
          Sekolah
        </p>
        <p class="text-[11px] text-slate-600">
          Aspirasi ditampilkan terbuka bagi seluruh warga sekolah
        </p>
      </div>
    </footer>
  </div>
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap");
.sapa-root {
  font-family:
    "Inter",
    ui-sans-serif,
    system-ui,
    -apple-system,
    "Segoe UI",
    Roboto,
    sans-serif;
}
</style>

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
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
@media (prefers-reduced-motion: reduce) {
  .fade-up {
    animation: none;
    opacity: 1;
  }
}
</style>
