<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import { toast } from "vue-sonner";
import {
  ArrowLeft,
  Lightbulb,
  TrendingUp,
  CheckCircle2,
  Search,
  X,
  ChevronsUpDown,
  ChevronDown,
  Lock,
  ArrowUp,
  Plus,
} from "lucide-vue-next";
import reportService from "@/services/reportService";

const router = useRouter();

const isLoading = ref(true);
const aspirations = ref([]);

const searchKeyword = ref("");
const activeTab = ref("all");
const sortMode = ref("latest");

async function loadAspirations() {
  isLoading.value = true;
  try {
    const params = { sort: sortMode.value };
    if (searchKeyword.value.trim()) params.search = searchKeyword.value.trim();
    if (activeTab.value === "liked") params.liked_only = true;

    const data = await reportService.getAspirations(params);

    aspirations.value = data.data.map((r) => ({
      id: r.id,
      title: r.title,
      content: r.description_excerpt,
      category: "Aspirasi",
      author: r.is_anonymous ? "Siswa Anonim" : (r.reporter?.name ?? "—"),
      class: r.is_anonymous ? "-" : "—", // class_name tidak di-load di ReportResource list, lihat catatan di bawah
      isAnonymous: r.is_anonymous,
      createdAt: formatRelative(r.created_at),
      likesCount: r.type_meta?.upvotes_count ?? 0,
      isLiked: r.is_liked ?? false,
      status: STATUS_MAP[r.status] ?? r.status,
    }));
  } catch {
    toast.error("Gagal memuat aspirasi.");
  } finally {
    isLoading.value = false;
  }
}

const STATUS_MAP = {
  pending: "Menunggu",
  reviewing: "Ditinjau",
  in_progress: "Diproses",
  resolved: "Selesai",
  rejected: "Ditolak",
};

function formatRelative(dateString) {
  const date = new Date(dateString);
  const diffDays = Math.floor(
    (Date.now() - date.getTime()) / (1000 * 60 * 60 * 24),
  );
  if (diffDays === 0) return "hari ini";
  if (diffDays === 1) return "1 hari yang lalu";
  if (diffDays < 30) return `${diffDays} hari yang lalu`;
  return date.toLocaleDateString("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

onMounted(loadAspirations);

// Reload dari server tiap kali filter/sort berubah — search di-debounce ringan
let searchDebounce = null;
watch(searchKeyword, () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(loadAspirations, 400);
});
watch([activeTab, sortMode], loadAspirations);

const totalAspirations = computed(() => aspirations.value.length);
const totalVotes = computed(() =>
  aspirations.value.reduce((acc, curr) => acc + curr.likesCount, 0),
);
const likedCount = computed(
  () => aspirations.value.filter((a) => a.isLiked).length,
);

const statItems = computed(() => [
  {
    label: "Total Aspirasi",
    value: totalAspirations.value,
    icon: Lightbulb,
    tile: "border-purple-500/25 bg-purple-500/10 text-purple-400",
    num: "text-white",
  },
  {
    label: "Total Dukungan",
    value: totalVotes.value,
    icon: TrendingUp,
    tile: "border-emerald-500/25 bg-emerald-500/10 text-emerald-400",
    num: "text-emerald-400",
  },
  {
    label: "Anda Dukung",
    value: likedCount.value,
    icon: CheckCircle2,
    tile: "border-purple-500/25 bg-purple-500/10 text-purple-400",
    num: "text-purple-400",
  },
]);

/* Optimistic update: ubah UI dulu, rollback kalau API gagal */
const toggleLike = async (item) => {
  const wasLiked = item.isLiked;
  const prevCount = item.likesCount;

  item.isLiked = !wasLiked;
  item.likesCount += wasLiked ? -1 : 1;

  try {
    const data = await reportService.toggleUpvote(item.id);
    item.isLiked = data.is_liked;
    item.likesCount = data.upvotes_count;
  } catch (error) {
    item.isLiked = wasLiked;
    item.likesCount = prevCount;
    toast.error("Gagal memperbarui dukungan.");
  }
};

/* Filter search dan tab sekarang di server, jadi ini tinggal alias */
const filteredAspirations = computed(() => aspirations.value);
const visibleAspirations = computed(() => aspirations.value);

const hasActiveFilters = computed(
  () => searchKeyword.value.trim() !== "" || activeTab.value !== "all",
);
const clearFilters = () => {
  searchKeyword.value = "";
  activeTab.value = "all";
};

const getStatusBadge = (status) => {
  switch (status) {
    case "Menunggu":
      return "bg-amber-500/10 text-amber-400 border-amber-500/20";
    case "Ditinjau":
      return "bg-blue-500/10 text-blue-400 border-blue-500/20";
    case "Diproses":
      return "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
    case "Selesai":
      return "bg-slate-500/10 text-slate-300 border-slate-500/20";
    case "Ditolak":
      return "bg-red-500/10 text-red-400 border-red-500/20";
    default:
      return "bg-slate-500/10 text-slate-400 border-slate-500/20";
  }
};

const initialsOf = (name) => {
  const n = (name || "").trim();
  if (!n) return "?";
  const parts = n.split(/\s+/);
  return (
    parts.length > 1 ? parts[0][0] + parts[parts.length - 1][0] : n.slice(0, 2)
  ).toUpperCase();
};

const goToCreateAspiration = () => {
  router.push({ path: "/reports/new", query: { type: "aspirasi" } });
};

const heroPhoto = "https://picsum.photos/seed/sapaaspirasi/1600/900.jpg";
const logoFailed = ref(false);
const currentYear = new Date().getFullYear();
</script>

<template>
  <div
    class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25"
  >
    <header
      class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md"
    >
      <div
        class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8"
      >
        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
          <button
            type="button"
            @click="router.back()"
            class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <ArrowLeft
              class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5"
            />
            <span>Kembali</span>
          </button>

          <span
            class="hidden h-6 w-px bg-slate-800 sm:block"
            aria-hidden="true"
          ></span>

          <router-link
            to="/"
            class="flex min-w-0 items-center gap-2.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20"
              >
                <img
                  v-if="!logoFailed"
                  src="../../assets/logo/logo sapa.jpeg"
                  alt="Logo SAPA"
                  class="h-full w-full object-cover"
                  @error="logoFailed = true"
                />
                <span v-else class="text-sm font-extrabold text-emerald-400"
                  >S</span
                >
              </div>
              <div class="hidden min-w-0 sm:block">
                <p
                  class="text-[15px] font-extrabold leading-none tracking-tight text-white"
                >
                  SAPA
                </p>
                <p
                  class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block"
                >
                  Sistem Layanan Aspirasi &amp; Pengaduan Sekolah
                </p>
              </div>
            </div>
          </router-link>
        </div>
      </div>
    </header>

    <main
      class="mx-auto w-full max-w-7xl flex-1 space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10"
    >
      <section
        class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
      >
        <img
          :src="heroPhoto"
          alt=""
          aria-hidden="true"
          draggable="false"
          class="pointer-events-none absolute inset-0 h-full w-full select-none object-cover opacity-20 grayscale contrast-125 brightness-[.65]"
        />
        <div
          class="pointer-events-none absolute inset-0 bg-slate-950/60"
          aria-hidden="true"
        ></div>
        <div
          class="pointer-events-none absolute inset-0 bg-linear-to-r from-slate-950/80 via-slate-950/30 to-transparent"
          aria-hidden="true"
        ></div>
        <div
          class="pointer-events-none absolute inset-0"
          aria-hidden="true"
          style="
            background: radial-gradient(
              900px 400px at 12% 0%,
              rgba(168, 85, 247, 0.13),
              transparent 65%
            );
          "
        ></div>
        <span
          class="absolute inset-x-0 top-0 z-10 h-0.5 bg-linear-to-r from-purple-500/70 via-purple-500/20 to-transparent"
          aria-hidden="true"
        ></span>

        <div class="relative z-10 p-6 sm:p-8 lg:p-9">
          <div class="flex items-center gap-2.5">
            <span class="relative flex h-2 w-2">
              <span
                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-purple-400 opacity-60"
              ></span>
              <span
                class="relative inline-flex h-2 w-2 rounded-full bg-purple-500"
              ></span>
            </span>
            <p
              class="text-[11px] font-semibold uppercase tracking-[0.18em] text-purple-300/90"
            >
              Kanal Aspirasi · Terbuka untuk Semua Siswa
            </p>
          </div>

          <h1
            class="mt-4 text-2xl font-extrabold leading-tight tracking-tight text-white sm:text-3xl"
          >
            Umpan <span class="text-purple-400">Aspirasi</span>
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400">
            Wadah terbuka bagi seluruh siswa untuk menyampaikan gagasan dan
            inovasi. Berikan dukungan pada usulan yang menurut Anda penting agar
            dapat diprioritaskan oleh pihak sekolah.
          </p>

          <div
            class="mt-7 grid grid-cols-3 gap-3 border-t border-slate-800/70 pt-5 sm:gap-4"
          >
            <div
              v-for="s in statItems"
              :key="s.label"
              class="flex items-center gap-2.5 sm:gap-3"
            >
              <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border sm:h-9 sm:w-9"
                :class="s.tile"
              >
                <component :is="s.icon" class="h-4 w-4" />
              </div>
              <div class="min-w-0">
                <p
                  class="text-base font-extrabold leading-none tracking-tight tabular-nums sm:text-xl"
                  :class="s.num"
                >
                  {{ s.value }}
                </p>
                <p
                  class="mt-1 truncate text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-500 sm:text-[10px]"
                >
                  {{ s.label }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex flex-col gap-3 sm:flex-row">
          <div class="relative flex-1">
            <Search
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
            />
            <input
              v-model="searchKeyword"
              type="text"
              aria-label="Cari aspirasi"
              placeholder="Cari kata kunci aspirasi..."
              class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-purple-500/60 focus:outline-none focus:ring-2 focus:ring-purple-500/15"
            />
            <button
              v-if="searchKeyword"
              type="button"
              @click="searchKeyword = ''"
              aria-label="Bersihkan pencarian"
              class="absolute right-2.5 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-500 transition-colors duration-150 hover:bg-slate-800 hover:text-slate-200"
            >
              <X class="h-3.5 w-3.5" />
            </button>
          </div>

          <div class="relative w-full sm:w-56">
            <ChevronsUpDown
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
            />
            <select
              v-model="sortMode"
              aria-label="Urutkan aspirasi"
              class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-purple-500/60 focus:outline-none focus:ring-2 focus:ring-purple-500/15"
            >
              <option value="latest" class="bg-slate-900 text-slate-100">
                Terbaru
              </option>
              <option value="top" class="bg-slate-900 text-slate-100">
                Paling Didukung
              </option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
            />
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            :aria-pressed="activeTab === 'all'"
            @click="activeTab = 'all'"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
            :class="
              activeTab === 'all'
                ? 'border-emerald-500/50 bg-emerald-500/15 text-emerald-400'
                : 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'
            "
          >
            <span
              class="h-1.5 w-1.5 rounded-full bg-current opacity-80"
              aria-hidden="true"
            ></span>
            Semua
            <span
              class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500"
              >{{ totalAspirations }}</span
            >
          </button>

          <button
            type="button"
            :aria-pressed="activeTab === 'liked'"
            @click="activeTab = 'liked'"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400/50"
            :class="
              activeTab === 'liked'
                ? 'border-purple-500/50 bg-purple-500/15 text-purple-400'
                : 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700 hover:text-slate-300'
            "
          >
            <span
              class="h-1.5 w-1.5 rounded-full bg-current opacity-80"
              aria-hidden="true"
            ></span>
            Didukung
            <span
              class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500"
              >{{ likedCount }}</span
            >
          </button>

          <div class="ml-auto flex items-center gap-3 pl-2">
            <p class="whitespace-nowrap text-[11px] text-slate-500">
              Menampilkan
              <span class="font-semibold tabular-nums text-slate-300">{{
                visibleAspirations.length
              }}</span>
              dari {{ totalAspirations }} aspirasi
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
      </section>

      <div
        v-if="isLoading"
        class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
      >
        <div
          v-for="i in 6"
          :key="i"
          class="h-52 rounded-xl border border-slate-800 bg-slate-900 animate-pulse"
        />
      </div>

      <template v-else>
        <section
          v-if="visibleAspirations.length > 0"
          class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
          <article
            v-for="(item, i) in visibleAspirations"
            :key="item.id"
            class="card-enter group relative flex flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-5 transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-500/30 hover:shadow-lg hover:shadow-black/25"
            :style="{ animationDelay: i * 60 + 'ms' }"
          >
            <span
              class="absolute bottom-4 left-0 top-4 w-0.75 rounded-r-full bg-purple-500/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
              aria-hidden="true"
            ></span>

            <div class="flex items-start justify-between gap-3">
              <span
                class="inline-flex items-center gap-1.5 rounded-full border border-purple-500/20 bg-purple-500/10 px-2.5 py-0.5 text-[11px] font-medium text-purple-400"
              >
                <span
                  class="h-1.5 w-1.5 rounded-full bg-current"
                  aria-hidden="true"
                ></span>
                Aspirasi
              </span>
              <span
                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                :class="getStatusBadge(item.status)"
              >
                <span
                  class="h-1.5 w-1.5 rounded-full bg-current"
                  aria-hidden="true"
                ></span>
                {{ item.status }}
              </span>
            </div>

            <div class="flex-1">
              <h3
                class="mt-4 text-sm font-bold leading-snug tracking-tight text-white"
              >
                {{ item.title }}
              </h3>
              <p class="clamp-3 mt-2 text-xs leading-relaxed text-slate-400">
                {{ item.content }}
              </p>
            </div>

            <div
              class="mt-5 flex items-center justify-between gap-3 border-t border-slate-800/70 pt-4"
            >
              <div class="flex min-w-0 items-center gap-2.5">
                <div
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-purple-500/30 bg-purple-500/10 text-[10px] font-bold text-purple-400"
                >
                  <Lock v-if="item.isAnonymous" class="h-3.5 w-3.5" />
                  <template v-else>{{ initialsOf(item.author) }}</template>
                </div>
                <div class="min-w-0">
                  <p class="truncate text-xs font-semibold text-slate-200">
                    {{ item.isAnonymous ? "Siswa Anonim" : item.author }}
                  </p>
                  <p class="mt-0.5 truncate text-[10px] text-slate-500">
                    {{
                      item.isAnonymous
                        ? item.createdAt
                        : item.class + " · " + item.createdAt
                    }}
                  </p>
                </div>
              </div>

              <button
                type="button"
                @click="toggleLike(item)"
                :aria-pressed="item.isLiked"
                :aria-label="
                  item.isLiked ? 'Batalkan dukungan' : 'Dukung aspirasi ini'
                "
                :title="
                  item.isLiked ? 'Batalkan dukungan' : 'Dukung aspirasi ini'
                "
                class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all duration-200 active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400/60"
                :class="
                  item.isLiked
                    ? 'border-purple-500/50 bg-purple-500/10 text-purple-400'
                    : 'border-slate-800 bg-slate-950/60 text-slate-400 hover:border-purple-500/40 hover:text-purple-300'
                "
              >
                <ArrowUp class="h-4 w-4" />
                <span class="tabular-nums">{{ item.likesCount }}</span>
              </button>
            </div>
          </article>
        </section>

        <div
          v-else
          class="fade-up flex flex-col items-center rounded-xl border border-dashed border-slate-800 bg-slate-900/40 px-6 py-14 text-center"
          style="animation-delay: 120ms"
        >
          <div
            class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400"
          >
            <Search v-if="hasActiveFilters" class="h-6 w-6" />
            <Lightbulb v-else class="h-6 w-6" />
          </div>

          <p class="mt-4 text-sm font-semibold text-slate-200">
            {{
              activeTab === "liked"
                ? "Belum ada aspirasi yang Anda dukung"
                : hasActiveFilters
                  ? "Aspirasi tidak ditemukan"
                  : "Belum ada aspirasi"
            }}
          </p>
          <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
            {{
              activeTab === "liked"
                ? "Dukung beberapa aspirasi terlebih dahulu untuk melihatnya di sini."
                : hasActiveFilters
                  ? "Coba gunakan kata kunci lain atau atur ulang filter."
                  : "Jadilah yang pertama menyampaikan usulan untuk kemajuan sekolah."
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
          <button
            v-else
            type="button"
            @click="goToCreateAspiration"
            class="mt-6 inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97]"
          >
            <Plus class="h-4 w-4" />
            Kirim Aspirasi Pertama
          </button>
        </div>
      </template>
    </main>

    <footer class="border-t border-slate-800/70">
      <div
        class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8"
      >
        <p class="text-[11px] text-slate-600">
          © {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan
          Sekolah
        </p>
        <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
          <Lock class="h-3.5 w-3.5 text-emerald-500/70" />
          Aspirasi ditampilkan terbuka — identitas pelapor anonim tetap
          dilindungi
        </p>
      </div>
    </footer>
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

.card-enter {
  opacity: 0;
  animation: card-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes card-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.clamp-3 {
  display: -webkit-box;
  -line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .card-enter {
    animation: none;
    opacity: 1;
  }
}
</style>
