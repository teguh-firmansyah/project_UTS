<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue-sonner";
import {
  ArrowLeft,
  AlertTriangle,
  Calendar,
  Paperclip,
  MessageSquare,
  Check,
  Send,
  Loader2,
  X,
  Clock,
  ShieldCheck,
  Lock,
} from "lucide-vue-next";
import reportService from "@/services/reportService";

const route = useRoute();
const router = useRouter();

const isLoading = ref(true);
const isSubmittingComment = ref(false);
const newComment = ref("");
const imageFailed = ref(false);

const report = ref(null);

/* Mapping tipe backend (en) -> kategori UI (id) yang dipakai style/badge */
const TYPE_TO_CATEGORY = {
  aspiration: "aspirasi",
  facility: "fasilitas",
  bullying: "bullying",
};

async function loadReport() {
  isLoading.value = true;
  try {
    const data = await reportService.getReportDetail(route.params.id);
    const r = data.data ?? data;

    report.value = {
      id: r.id,
      report_code: r.report_code,
      title: r.title,
      category: TYPE_TO_CATEGORY[r.type] ?? r.type,
      status: r.status,
      content: r.description,
      created_at: r.created_at,
      is_anonymous: r.is_anonymous,
      image_url: r.attachments?.[0]?.url ?? null,
      user: r.reporter
        ? { name: r.reporter.name, class_name: r.reporter.class_name }
        : null,
      comments: [],
    };

    await loadComments();
  } catch (error) {
    if (error.response?.status === 403 || error.response?.status === 404) {
      report.value = null;
    } else {
      toast.error("Gagal memuat detail laporan.");
    }
  } finally {
    isLoading.value = false;
  }
}

async function loadComments() {
  try {
    const data = await reportService.getComments(route.params.id);
    report.value.comments = (data.data ?? data).map((c) => ({
      id: c.id,
      user_name: c.user?.name ?? c.author?.name ?? "Anonim",
      user_role: c.is_internal ? "admin" : "user",
      comment: c.comment,
      created_at: c.created_at,
    }));
  } catch {
    toast.error("Gagal memuat tanggapan.");
  }
}

onMounted(loadReport);

const getStatusBadge = (status) => {
  const map = {
    pending: {
      label: "Menunggu",
      class: "bg-amber-500/10 text-amber-400 border-amber-500/20",
    },
    reviewing: {
      label: "Ditinjau",
      class: "bg-blue-500/10 text-blue-400 border-blue-500/20",
    },
    in_progress: {
      label: "Diproses",
      class: "bg-emerald-500/10 text-emerald-400 border-emerald-500/20",
    },
    resolved: {
      label: "Selesai",
      class: "bg-slate-500/10 text-slate-300 border-slate-500/20",
    },
    rejected: {
      label: "Ditolak",
      class: "bg-red-500/10 text-red-400 border-red-500/20",
    },
  };
  return map[status] || { label: status, class: "bg-slate-700 text-slate-300" };
};

const getTypeBadge = (type) => {
  const map = {
    aspirasi: {
      label: "Aspirasi",
      class: "bg-purple-500/10 text-purple-400 border-purple-500/20",
    },
    fasilitas: {
      label: "Fasilitas",
      class: "bg-cyan-500/10 text-cyan-400 border-cyan-500/20",
    },
    bullying: {
      label: "Perundungan",
      class: "bg-rose-500/10 text-rose-400 border-rose-500/20",
    },
  };
  return map[type] || { label: type, class: "bg-slate-700 text-slate-300" };
};

const formatDate = (dateString) => {
  if (!dateString) return "-";
  return new Date(dateString).toLocaleDateString("id-ID", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const handleAddComment = async () => {
  if (!newComment.value.trim() || isSubmittingComment.value) return;

  isSubmittingComment.value = true;
  try {
    const data = await reportService.addComment(
      route.params.id,
      newComment.value.trim(),
    );
    const c = data.comment;
    report.value.comments.unshift({
      id: c.id,
      user_name: c.author?.name ?? "Anda",
      user_role: "user",
      comment: c.comment,
      created_at: c.created_at,
    });
    newComment.value = "";
    toast.success("Tanggapan berhasil dikirim!");
  } catch (error) {
    toast.error(error?.response?.data?.message || "Gagal mengirim tanggapan.");
  } finally {
    isSubmittingComment.value = false;
  }
};

const onCommentKeydown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
    e.preventDefault();
    handleAddComment();
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

const hasImage = computed(
  () => !!report.value?.image_url && !imageFailed.value,
);
const isBullying = computed(() => report.value?.category === "bullying");
const privacyNote = computed(() =>
  isBullying.value
    ? "Laporan perundungan dirahasiakan dan hanya ditinjau oleh petugas berwenang. Identitas Anda dilindungi sepenuhnya."
    : "Identitas pelapor dan isi laporan hanya dapat dilihat oleh petugas berwenang.",
);

const typeBar = {
  aspirasi: "from-purple-500/70 to-transparent",
  fasilitas: "from-cyan-500/70 to-transparent",
  bullying: "from-rose-500/70 to-transparent",
};

const WORKFLOW = ["pending", "reviewing", "in_progress", "resolved"];
const STEP_META = {
  pending: {
    label: "Menunggu",
    desc: "Laporan dalam antrean tindak lanjut petugas",
  },
  reviewing: {
    label: "Ditinjau",
    desc: "Verifikasi kelengkapan & validitas laporan",
  },
  in_progress: {
    label: "Diproses",
    desc: "Penanganan sedang dilakukan oleh pihak sekolah",
  },
  resolved: {
    label: "Selesai",
    desc: "Laporan telah ditindaklanjuti sampai tuntas",
  },
};

const timeline = computed(() => {
  if (!report.value) return [];
  const status = report.value.status;
  const steps = [
    {
      key: "submitted",
      state: "done",
      label: "Laporan Dikirim",
      desc: "Laporan diterima & tercatat pada sistem",
      date: report.value.created_at,
    },
  ];

  if (status === "rejected") {
    steps.push({
      key: "rejected",
      state: "done",
      tone: "danger",
      label: "Ditolak",
      desc: "Laporan tidak dapat diproses lebih lanjut",
    });
    return steps;
  }

  const currentIdx = WORKFLOW.indexOf(status);
  WORKFLOW.forEach((key, i) => {
    let state = "todo";
    if (status === "resolved") state = "done";
    else if (i < currentIdx) state = "done";
    else if (i === currentIdx) state = "active";
    steps.push({ key, state, ...STEP_META[key] });
  });
  return steps;
});

const metaRows = computed(() => {
  if (!report.value) return [];
  return [
    { label: "Kode Laporan", kind: "code", value: report.value.report_code },
    {
      label: "Kategori",
      kind: "badge",
      badge: getTypeBadge(report.value.category),
    },
    {
      label: "Status",
      kind: "badge",
      badge: getStatusBadge(report.value.status),
    },
    {
      label: "Dibuat",
      kind: "text",
      value: formatDate(report.value.created_at),
    },
    {
      label: "Pelapor",
      kind: "text",
      value: report.value.is_anonymous
        ? "Anonim"
        : report.value.user?.name || "—",
    },
  ];
});

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

        <div v-if="report" class="flex min-w-0 items-center gap-2">
          <span
            class="hidden rounded-md border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 font-mono text-[11px] font-medium text-emerald-400 sm:inline-flex"
          >
            {{ report.report_code }}
          </span>
          <span
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1 text-[11px] font-medium"
            :class="getStatusBadge(report.status).class"
          >
            <span
              class="h-1.5 w-1.5 rounded-full bg-current"
              aria-hidden="true"
            ></span>
            {{ getStatusBadge(report.status).label }}
          </span>
        </div>
      </div>
    </header>

    <main
      class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10"
    >
      <div
        v-if="isLoading"
        class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]"
        aria-busy="true"
      >
        <p class="sr-only">Memuat detail laporan…</p>
        <div class="space-y-6">
          <div
            class="animate-pulse space-y-5 rounded-xl border border-slate-800 bg-slate-900 p-6"
          >
            <div class="flex gap-2">
              <div class="h-5 w-20 rounded-full bg-slate-800"></div>
              <div class="h-5 w-24 rounded-full bg-slate-800"></div>
            </div>
            <div class="h-6 w-3/4 rounded bg-slate-800"></div>
            <div class="flex items-center gap-3 border-t border-slate-800 pt-4">
              <div class="h-9 w-9 rounded-full bg-slate-800"></div>
              <div class="space-y-1.5">
                <div class="h-3 w-28 rounded bg-slate-800"></div>
                <div class="h-2.5 w-16 rounded bg-slate-800"></div>
              </div>
            </div>
            <div class="space-y-2 border-t border-slate-800 pt-4">
              <div class="h-3 w-full rounded bg-slate-800"></div>
              <div class="h-3 w-11/12 rounded bg-slate-800"></div>
              <div class="h-3 w-4/5 rounded bg-slate-800"></div>
            </div>
            <div class="h-48 w-full max-w-lg rounded-lg bg-slate-800"></div>
          </div>
          <div
            class="h-56 animate-pulse rounded-xl border border-slate-800 bg-slate-900"
          ></div>
        </div>
        <div class="hidden space-y-6 lg:block">
          <div
            class="h-80 animate-pulse rounded-xl border border-slate-800 bg-slate-900"
          ></div>
          <div
            class="h-44 animate-pulse rounded-xl border border-slate-800 bg-slate-900"
          ></div>
        </div>
      </div>

      <div
        v-else-if="!report"
        class="fade-up mx-auto max-w-md rounded-xl border border-slate-800 bg-slate-900 px-6 py-12 text-center"
      >
        <div
          class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400"
        >
          <AlertTriangle class="h-6 w-6" />
        </div>
        <p class="mt-4 text-sm font-semibold text-slate-200">
          Laporan tidak ditemukan
        </p>
        <p class="mt-1 text-xs leading-relaxed text-slate-500">
          Laporan yang Anda cari tidak tersedia atau telah dihapus.
        </p>
        <div class="mt-6 flex items-center justify-center gap-3">
          <button
            type="button"
            @click="router.push('/reports')"
            class="inline-flex items-center rounded-lg border border-slate-700 bg-slate-800/60 px-4 py-2 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97]"
          >
            Kembali ke Daftar
          </button>
        </div>
      </div>

      <div
        v-else
        class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]"
      >
        <div class="min-w-0 space-y-6">
          <article
            class="fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
          >
            <span
              class="absolute inset-x-0 top-0 h-0.5 bg-linear-to-r"
              :class="
                typeBar[report.category] || 'from-emerald-500/70 to-transparent'
              "
              aria-hidden="true"
            ></span>

            <div class="space-y-6 p-5 sm:p-6 lg:p-7">
              <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                  <span
                    class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                    :class="getTypeBadge(report.category).class"
                    >{{ getTypeBadge(report.category).label }}</span
                  >
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                    :class="getStatusBadge(report.status).class"
                  >
                    <span
                      class="h-1.5 w-1.5 rounded-full bg-current"
                      aria-hidden="true"
                    ></span>
                    {{ getStatusBadge(report.status).label }}
                  </span>
                </div>
                <h1
                  class="text-xl font-extrabold leading-snug tracking-tight text-white sm:text-2xl"
                >
                  {{ report.title }}
                </h1>
              </div>

              <div
                class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800/70 pt-4"
              >
                <div class="flex min-w-0 items-center gap-3">
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[11px] font-bold text-emerald-400"
                  >
                    <Lock v-if="report.is_anonymous" class="h-4 w-4" />
                    <template v-else>{{
                      initialsOf(report.user?.name)
                    }}</template>
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-xs font-semibold text-slate-100">
                      {{
                        report.is_anonymous
                          ? "Pelapor Anonim"
                          : report.user?.name
                      }}
                    </p>
                    <p class="mt-0.5 truncate text-[11px] text-slate-500">
                      {{
                        report.is_anonymous
                          ? "Identitas pelapor dilindungi"
                          : report.user?.class_name || "Siswa"
                      }}
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                  <Calendar class="h-3.5 w-3.5 text-slate-600" />
                  <span>{{ formatDate(report.created_at) }}</span>
                </div>
              </div>

              <div class="space-y-3 border-t border-slate-800/70 pt-4">
                <p
                  class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500"
                >
                  Deskripsi Laporan
                </p>
                <p
                  class="text-sm leading-relaxed whitespace-pre-line text-slate-300"
                >
                  {{ report.content }}
                </p>
              </div>

              <div
                v-if="hasImage"
                class="space-y-3 border-t border-slate-800/70 pt-4"
              >
                <div class="flex items-center gap-2">
                  <Paperclip class="h-3.5 w-3.5 text-slate-600" />
                  <p
                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500"
                  >
                    Lampiran Bukti Foto
                  </p>
                </div>
                <figure
                  class="group relative max-w-lg overflow-hidden rounded-lg border border-slate-800 bg-slate-950"
                >
                  <img
                    :src="report.image_url"
                    alt="Bukti laporan"
                    loading="lazy"
                    class="h-auto max-h-80 w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                    @error="imageFailed = true"
                  />
                </figure>
              </div>
            </div>
          </article>

          <section
            class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
            style="animation-delay: 90ms"
          >
            <div
              class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4 sm:px-6"
            >
              <div class="flex items-center gap-2.5">
                <MessageSquare class="h-4 w-4 text-emerald-400" />
                <h2 class="text-sm font-bold tracking-tight text-slate-100">
                  Tanggapan &amp; Diskusi
                </h2>
              </div>
              <span
                class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400"
                >{{ report.comments?.length || 0 }} Tanggapan</span
              >
            </div>

            <div class="space-y-3.5 p-5 sm:p-6">
              <div
                v-if="!report.comments || report.comments.length === 0"
                class="flex flex-col items-center py-8 text-center"
              >
                <div
                  class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400"
                >
                  <MessageSquare class="h-5 w-5" />
                </div>
                <p class="mt-3 text-sm font-semibold text-slate-200">
                  Belum ada tanggapan
                </p>
                <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
                  Pihak sekolah dan Anda dapat berdiskusi langsung pada laporan
                  ini.
                </p>
              </div>

              <article
                v-for="(item, idx) in report.comments"
                :key="item.id"
                class="comment-enter relative rounded-lg border p-4"
                :class="
                  item.user_role === 'admin'
                    ? 'border-emerald-500/25 bg-emerald-500/4'
                    : 'border-slate-800 bg-slate-950/40'
                "
                :style="{ animationDelay: idx * 60 + 'ms' }"
              >
                <span
                  v-if="item.user_role === 'admin'"
                  class="absolute bottom-3 left-0 top-3 w-0.5 rounded-r-full bg-emerald-500/60"
                  aria-hidden="true"
                ></span>

                <div class="flex items-start justify-between gap-3">
                  <div class="flex min-w-0 items-center gap-2.5">
                    <div
                      class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                      :class="
                        item.user_role === 'admin'
                          ? 'bg-emerald-500 text-slate-950'
                          : 'border border-slate-600 bg-slate-700 text-slate-200'
                      "
                    >
                      {{ initialsOf(item.user_name) }}
                    </div>
                    <div
                      class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1"
                    >
                      <p class="truncate text-xs font-semibold text-slate-100">
                        {{ item.user_name }}
                      </p>
                      <span
                        v-if="item.user_role === 'admin'"
                        class="inline-flex items-center gap-1 rounded border border-emerald-500/30 bg-emerald-500/15 px-1.5 py-px text-[10px] font-semibold text-emerald-400"
                      >
                        <Check class="h-2.5 w-2.5" />
                        Petugas
                      </span>
                    </div>
                  </div>
                  <p class="shrink-0 text-[10px] text-slate-500">
                    {{ formatDate(item.created_at) }}
                  </p>
                </div>

                <p class="mt-2.5 pl-8.5 text-xs leading-relaxed text-slate-300">
                  {{ item.comment }}
                </p>
              </article>
            </div>

            <form
              @submit.prevent="handleAddComment"
              class="border-t border-slate-800/70 bg-slate-950/30 p-5 sm:p-6"
            >
              <div class="flex gap-3">
                <div
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[10px] font-bold text-emerald-400"
                >
                  ME
                </div>

                <div class="min-w-0 flex-1 space-y-3">
                  <textarea
                    v-model="newComment"
                    rows="3"
                    placeholder="Tulis pesan atau tanggapan terkait laporan ini..."
                    @keydown="onCommentKeydown"
                    class="w-full resize-none rounded-lg border border-slate-800 bg-slate-950/60 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  ></textarea>

                  <div class="flex items-center justify-between gap-3">
                    <p class="hidden text-[10px] text-slate-600 sm:block">
                      Ctrl + Enter untuk mengirim
                    </p>
                    <button
                      type="submit"
                      :disabled="isSubmittingComment || !newComment.trim()"
                      class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                    >
                      <Send v-if="!isSubmittingComment" class="h-4 w-4" />
                      <Loader2 v-else class="h-4 w-4 animate-spin" />
                      {{
                        isSubmittingComment ? "Mengirim..." : "Kirim Tanggapan"
                      }}
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </section>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-20">
          <div
            class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
            style="animation-delay: 140ms"
          >
            <div
              class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4"
            >
              <h2 class="text-sm font-bold tracking-tight text-slate-100">
                Status Laporan
              </h2>
              <span
                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2 py-0.5 text-[10px] font-medium"
                :class="getStatusBadge(report.status).class"
              >
                <span
                  class="h-1.5 w-1.5 rounded-full bg-current"
                  aria-hidden="true"
                ></span>
                {{ getStatusBadge(report.status).label }}
              </span>
            </div>

            <ol class="px-5 py-5">
              <li
                v-for="(step, i) in timeline"
                :key="step.key"
                class="relative flex gap-3.5"
                :class="i < timeline.length - 1 ? 'pb-6' : ''"
              >
                <div class="flex flex-col items-center">
                  <span
                    class="relative flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                    :class="
                      step.tone === 'danger'
                        ? 'border-rose-500 bg-rose-500 text-slate-950'
                        : step.state === 'done'
                          ? 'border-emerald-500 bg-emerald-500 text-slate-950'
                          : step.state === 'active'
                            ? 'border-emerald-400 bg-emerald-500/20 text-emerald-400'
                            : 'border-slate-700 bg-slate-900 text-slate-600'
                    "
                  >
                    <template v-if="step.state === 'done'">
                      <X v-if="step.tone === 'danger'" class="h-3 w-3" />
                      <Check v-else class="h-3 w-3" />
                    </template>

                    <template v-else-if="step.state === 'active'">
                      <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-40"
                        aria-hidden="true"
                      ></span>
                      <span
                        class="relative h-1.5 w-1.5 rounded-full bg-emerald-400"
                        aria-hidden="true"
                      ></span>
                    </template>

                    <span
                      v-else
                      class="h-1 w-1 rounded-full bg-slate-600"
                      aria-hidden="true"
                    ></span>
                  </span>

                  <span
                    v-if="i < timeline.length - 1"
                    class="mt-1 w-px flex-1"
                    :class="
                      step.state === 'done'
                        ? 'bg-emerald-500/40'
                        : 'bg-slate-800'
                    "
                    aria-hidden="true"
                  ></span>
                </div>

                <div class="min-w-0 flex-1 pt-0.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <p
                      class="text-xs font-semibold"
                      :class="
                        step.state === 'todo'
                          ? 'text-slate-500'
                          : step.tone === 'danger'
                            ? 'text-rose-400'
                            : 'text-slate-100'
                      "
                    >
                      {{ step.label }}
                    </p>
                    <span
                      v-if="step.state === 'active'"
                      class="inline-flex items-center gap-1 rounded border border-emerald-500/30 bg-emerald-500/15 px-1.5 py-px text-[10px] font-semibold text-emerald-400"
                    >
                      <span
                        class="h-1 w-1 animate-pulse rounded-full bg-emerald-400"
                        aria-hidden="true"
                      ></span>
                      Berjalan
                    </span>
                  </div>
                  <p class="mt-0.5 text-[11px] leading-snug text-slate-500">
                    {{ step.desc }}
                  </p>
                  <p v-if="step.date" class="mt-1 text-[10px] text-slate-600">
                    {{ formatDate(step.date) }}
                  </p>
                </div>
              </li>
            </ol>

            <p
              class="flex items-center gap-1.5 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 text-[11px] text-slate-600"
            >
              <Clock class="h-3.5 w-3.5 text-slate-600" />
              Status diperbarui oleh petugas sekolah
            </p>
          </div>

          <div
            class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
            style="animation-delay: 190ms"
          >
            <div class="border-b border-slate-800/70 px-5 py-4">
              <h2 class="text-sm font-bold tracking-tight text-slate-100">
                Informasi Laporan
              </h2>
            </div>
            <dl class="divide-y divide-slate-800/60">
              <div
                v-for="row in metaRows"
                :key="row.label"
                class="flex items-center justify-between gap-4 px-5 py-3"
              >
                <dt class="shrink-0 text-[11px] font-medium text-slate-500">
                  {{ row.label }}
                </dt>
                <dd class="min-w-0 text-right">
                  <span
                    v-if="row.kind === 'code'"
                    class="rounded border border-emerald-500/20 bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-emerald-400"
                    >{{ row.value }}</span
                  >
                  <span
                    v-else-if="row.kind === 'badge'"
                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                    :class="row.badge.class"
                  >
                    <span
                      class="h-1.5 w-1.5 rounded-full bg-current"
                      aria-hidden="true"
                    ></span>
                    {{ row.badge.label }}
                  </span>
                  <span v-else class="truncate text-xs text-slate-300">{{
                    row.value
                  }}</span>
                </dd>
              </div>
            </dl>
          </div>

          <div
            class="fade-up rounded-xl border border-slate-800 bg-slate-900 p-5"
            style="animation-delay: 240ms"
          >
            <div class="flex items-start gap-3">
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-emerald-500/25 bg-emerald-500/10 text-emerald-400"
              >
                <ShieldCheck class="h-4 w-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-100">
                  Kerahasiaan Terjamin
                </p>
                <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                  {{ privacyNote }}
                </p>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </main>

    <footer class="border-t border-slate-800/70">
      <div
        class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8"
      >
        <p class="text-[11px] text-slate-600">
          © {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan
          Sekolah
        </p>
        <p class="text-[11px] text-slate-600">
          Setiap laporan dijaga kerahasiaannya
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

.comment-enter {
  opacity: 0;
  animation: comment-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes comment-in {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .comment-enter {
    animation: none;
    opacity: 1;
  }
}
</style>
