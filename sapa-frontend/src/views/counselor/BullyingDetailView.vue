<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { useAuthStore } from "@/stores/auth";
import reportService from "@/services/reportService";

// Import Lucide Icons
import {
  ArrowLeft,
  Printer,
  Loader2,
  Lock,
  CheckCircle2,
  Calendar,
  User,
  Clock,
  Paperclip,
  History,
  MessageSquare,
  Send,
  AlertTriangle,
  ClipboardList,
  Plus,
  X,
  ChevronDown,
  Check
} from "lucide-vue-next";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const reportId = route.params.id;
const isLoading = ref(true);
const isSubmitting = ref(false);
const showImageModal = ref(false);
const activeAttachmentUrl = ref(null);

/* ---------------------------------- */
/* Mapping status backend <-> label UI */
/* ---------------------------------- */
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
const RELATION_MAP = { victim: "Korban Langsung", witness: "Saksi" };

/* ---------------------------------- */
/* State form tindak lanjut            */
/* ---------------------------------- */
const form = ref({
  status: "",
  handling_notes: "",
  counselor_action: "Konseling Individu",
});

const formErrors = ref({});

/* ---------------------------------- */
/* Data laporan — dari API             */
/* ---------------------------------- */
const report = ref(null);

async function loadReport() {
  isLoading.value = true;
  try {
    const response = await reportService.getBullyingReportDetail(reportId);
    const r = response.data?.data || response.data || response;

    if (!r || !r.id) {
      throw new Error("Data laporan tidak valid.");
    }

    const detail = r.bullying_detail || r.bullyingDetail || {};

    report.value = {
      id: r.id,
      ticket_code: r.report_code || r.ticket_code || "—",
      category: r.title || "Laporan Perundungan",
      reporter_relation: detail.reporter_relation || "—",
      is_anonymous: Boolean(r.is_anonymous),
      reporter_name: r.reporter?.name ?? null,
      reporter_class: r.reporter?.class_name ?? null,
      incident_date: detail.incident_date
        ? new Date(detail.incident_date).toLocaleDateString("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric",
          })
        : "—",
      created_at: r.created_at
        ? new Date(r.created_at).toLocaleString("id-ID", {
            dateStyle: "medium",
            timeStyle: "short",
          })
        : "—",
      description: r.description || "Tidak ada deskripsi.",
      attachments: r.attachments ?? [],
      status: r.status,
      priority: r.priority || "—",
      handled_by: detail.handled_by_counselor_id ?? null,
      handling_notes: detail.handling_notes ?? "",

      timeline: (r.status_logs || r.statusLogs || []).map((log) => ({
        id: log.id,
        status: log.new_status,
        title: log.old_status
          ? `Status Diperbarui: ${log.new_status}`
          : "Laporan Masuk Sistem",
        description: log.note || "Tidak ada catatan.",
        time: log.created_at
          ? new Date(log.created_at).toLocaleString("id-ID", {
              dateStyle: "medium",
              timeStyle: "short",
            })
          : "—",
        actor: log.changed_by?.name || log.changedBy?.name || "Sistem",
      })),
    };

    form.value.status = r.status;
    form.value.handling_notes = detail.handling_notes || "";
  } catch (error) {
    console.error("Gagal memuat detail laporan:", error);
    toast.error("Gagal memuat detail laporan.");
  } finally {
    isLoading.value = false;
  }
}

const comments = ref([]);
const newMessage = ref("");
const isSendingMessage = ref(false);
const isLoadingComments = ref(true);

async function loadComments() {
  isLoadingComments.value = true;
  try {
    const data = await reportService.getComments(reportId);
    comments.value = (data.data ?? data).map((c) => ({
      id: c.id,
      text: c.comment,
      authorName: c.author?.name ?? "Anonim",
      isCounselor: c.author?.role === "counselor",
      isMine: c.is_mine,
      createdAt: new Date(c.created_at).toLocaleString("id-ID", {
        dateStyle: "short",
        timeStyle: "short",
      }),
    }));
  } catch {
    toast.error("Gagal memuat percakapan.");
  } finally {
    isLoadingComments.value = false;
  }
}

async function handleSendMessage() {
  if (!newMessage.value.trim() || isSendingMessage.value) return;

  isSendingMessage.value = true;
  try {
    const data = await reportService.addComment(reportId, newMessage.value.trim());
    const c = data.comment;
    comments.value.push({
      id: c.id,
      text: c.comment,
      authorName: c.author?.name ?? "Anda",
      isCounselor: c.author?.role === "counselor",
      isMine: true,
      createdAt: new Date(c.created_at).toLocaleString("id-ID", {
        dateStyle: "short",
        timeStyle: "short",
      }),
    });
    newMessage.value = "";
  } catch (error) {
    toast.error(error?.response?.data?.message || "Gagal mengirim pesan.");
  } finally {
    isSendingMessage.value = false;
  }
}

const initialsShort = (name) => {
  if (!name || typeof name !== "string") return "?";
  const parts = name.trim().split(/\s+/);
  return parts.length > 1
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.slice(0, 2).toUpperCase();
};

onMounted(() => {
  loadReport();
  loadComments();
  window.addEventListener("keydown", handleEscKey);
});

onBeforeUnmount(() => {
  window.removeEventListener("keydown", handleEscKey);
  document.body.style.overflow = "";
});

const handleEscKey = (e) => {
  if (e.key === "Escape" && showImageModal.value) showImageModal.value = false;
};

watch(showImageModal, (open) => {
  document.body.style.overflow = open ? "hidden" : "";
});

function openImage(url) {
  activeAttachmentUrl.value = url;
  showImageModal.value = true;
}

const goBack = () => {
  router.push({ name: "counselor-bullying-queue" });
};

const noteTemplates = [
  {
    label: "Konseling Awal",
    text: "Siswa pelapor telah dipanggil ke ruang BK untuk sesi konseling awal.",
  },
  {
    label: "Mediasi Damai",
    text: "Melakukan mediasi bersama pihak-pihak terkait dan menyepakati perjanjian damai.",
  },
];

const applyTemplate = (text) => {
  form.value.handling_notes = form.value.handling_notes
    ? `${form.value.handling_notes}\n${text}`
    : text;
};

watch(
  () => form.value.status,
  () => {
    if (formErrors.value.status) formErrors.value.status = "";
  }
);
watch(
  () => form.value.handling_notes,
  () => {
    if (formErrors.value.handling_notes) formErrors.value.handling_notes = "";
  }
);

const notesLength = computed(() => (form.value.handling_notes || "").length);

const handleUpdateStatus = async () => {
  formErrors.value = {};

  if (!form.value.status || !form.value.handling_notes.trim()) {
    if (!form.value.status)
      formErrors.value.status = "Pilih status laporan terlebih dahulu.";
    if (!form.value.handling_notes.trim())
      formErrors.value.handling_notes = "Catatan penanganan wajib diisi.";
    toast.error(
      "Mohon lengkapi status dan catatan penanganan terlebih dahulu."
    );
    return;
  }

  const backendStatus = STATUS_MAP_REVERSE[form.value.status];
  if (backendStatus === "pending") {
    toast.error('Status tidak dapat dikembalikan ke "Menunggu".');
    return;
  }

  isSubmitting.value = true;

  try {
    const combinedNotes = `[Tindakan: ${form.value.counselor_action}] ${form.value.handling_notes}`;

    await reportService.handleBullyingReport(reportId, {
      status: backendStatus,
      handling_notes: combinedNotes,
    });

    toast.success("Penanganan laporan berhasil diperbarui!", {
      description: `Status kasus kini: ${form.value.status}.`,
    });

    await loadReport();
  } catch (error) {
    console.error("Error handling report:", error);

    const validationErrors = error?.response?.data?.errors;
    if (validationErrors) {
      toast.error(
        Object.values(validationErrors)[0]?.[0] ||
          "Periksa kembali data yang diisi."
      );
    } else {
      toast.error(
        error?.response?.data?.message ||
          "Gagal memperbarui laporan. Silakan coba lagi."
      );
    }
  } finally {
    isSubmitting.value = false;
  }
};

const isRevealing = ref(false);
async function handleRevealIdentity() {
  isRevealing.value = true;
  try {
    const data = await reportService.revealIdentity(reportId);
    report.value.reporter_name = data.reporter.name;
    report.value.reporter_class = data.reporter.class_name;
    report.value.is_anonymous = false;
    toast.success("Identitas pelapor berhasil dibuka.");
  } catch (error) {
    toast.error(
      error?.response?.data?.message || "Gagal membuka identitas pelapor."
    );
  } finally {
    isRevealing.value = false;
  }
}

const handlePrint = () => {
  window.print();
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

const getPriority = (priority) => {
  const map = {
    Tinggi: {
      label: "Tinggi",
      level: 3,
      text: "text-rose-400",
      bar: "bg-rose-500",
    },
    Sedang: {
      label: "Sedang",
      level: 2,
      text: "text-amber-400",
      bar: "bg-amber-500",
    },
    Rendah: {
      label: "Rendah",
      level: 1,
      text: "text-slate-400",
      bar: "bg-slate-500",
    },
  };
  return map[priority] || map["Rendah"];
};

const priority = computed(() =>
  report.value ? getPriority(report.value.priority) : getPriority("Rendah")
);

const statusOptions = [
  {
    value: "Ditinjau",
    label: "Ditinjau Guru BK",
    desc: "Verifikasi bukti & keterangan awal",
    dot: "bg-blue-400",
    active: "border-blue-500/50 bg-blue-500/[0.06] text-blue-400",
  },
  {
    value: "Diproses",
    label: "Diproses / Pemanggilan",
    desc: "Penanganan aktif kasus berjalan",
    dot: "bg-emerald-400",
    active: "border-emerald-500/50 bg-emerald-500/[0.06] text-emerald-400",
  },
  {
    value: "Selesai",
    label: "Selesai — Kasus Tuntas",
    desc: "Tindak lanjut selesai & terdokumentasi",
    dot: "bg-slate-300",
    active: "border-slate-500/60 bg-slate-500/10 text-slate-300",
  },
  {
    value: "Ditolak",
    label: "Ditolak — Bukti Tidak Cukup",
    desc: "Di luar lingkup / tidak dapat diproses",
    dot: "bg-red-400",
    active: "border-red-500/50 bg-red-500/[0.06] text-red-400",
  },
];

const actionOptions = [
  { value: "Konseling Individu", label: "Konseling Individu (Pelapor)" },
  { value: "Pemanggilan Saksi", label: "Pemanggilan Saksi-Saksi" },
  { value: "Mediasi Berhadapan", label: "Mediasi Pelapor & Terlapor" },
  {
    value: "Koordinasi Wali Kelas & Orang Tua",
    label: "Koordinasi Wali Kelas & Orang Tua",
  },
  { value: "Rujukan Eksternal", label: "Rujukan Eksternal / Kasus Khusus" },
];

const caseMeta = computed(() => {
  if (!report.value) return [];
  return [
    {
      label: "Tanggal Kejadian",
      value: report.value.incident_date,
      icon: Calendar,
    },
    {
      label: "Relasi Pelapor",
      value: report.value.reporter_relation,
      icon: User,
    },
    {
      label: "Waktu Pelaporan",
      value: report.value.created_at,
      icon: Clock,
    },
  ];
});

const initialsOf = (name) => {
  if (!name || typeof name !== "string") return "?";
  const cleanName = name.trim();
  if (!cleanName) return "?";
  const parts = cleanName.split(" ").filter(Boolean);
  if (parts.length === 0) return "?";
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase();

  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
};

const printDate = new Date().toLocaleString("id-ID", {
  dateStyle: "long",
  timeStyle: "short",
});
const currentYear = new Date().getFullYear();
</script>

<template>
  <div
    class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25"
  >
    <!-- Skeleton loading full page -->
    <div v-if="isLoading" class="flex flex-1 items-center justify-center">
      <div class="flex items-center gap-3 text-slate-400">
        <Loader2 class="h-5 w-5 animate-spin" />
        <span class="text-sm">Memuat detail laporan...</span>
      </div>
    </div>

    <template v-else-if="report">
      <!-- ============ Bar atas (tidak tercetak) ============ -->
      <header
        class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md print:hidden"
      >
        <div
          class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8"
        >
          <button
            type="button"
            @click="goBack"
            class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <ArrowLeft class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5" />
            <span class="hidden sm:inline">Kembali ke Antrian</span>
            <span class="sm:hidden">Kembali</span>
          </button>

          <div class="flex min-w-0 items-center gap-2 sm:gap-3">
            <button
              type="button"
              @click="handlePrint"
              title="Cetak / simpan sebagai PDF"
              class="hidden items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 sm:inline-flex"
            >
              <Printer class="h-4 w-4" />
              <span class="hidden md:inline">Cetak Dokumen</span>
              <span class="md:hidden">Cetak</span>
            </button>

            <div class="hidden h-6 w-px bg-slate-800 sm:block"></div>

            <span
              class="hidden rounded-md border border-rose-500/20 bg-rose-500/10 px-2 py-1 font-mono text-[11px] font-medium text-rose-400 sm:inline-flex"
            >
              {{ report.ticket_code }}
            </span>

            <span
              class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1 text-[11px] font-medium"
              :class="getStatusBadge(report.status)"
            >
              <span
                class="h-1.5 w-1.5 rounded-full bg-current"
                aria-hidden="true"
              ></span>
              {{ report.status }}
            </span>
          </div>
        </div>
      </header>

      <!-- ============ Konten ============ -->
      <main
        class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10"
      >
        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
          <!-- ===== Kolom kasus (dapat dicetak) ===== -->
          <div class="min-w-0 space-y-6">
            <div class="hidden border-b border-slate-300 pb-4 print:block">
              <div class="flex items-end justify-between gap-4">
                <div>
                  <p class="text-lg font-extrabold tracking-tight">SAPA</p>
                  <p
                    class="mt-0.5 text-[10px] font-medium uppercase tracking-[0.16em]"
                  >
                    Sistem Layanan Aspirasi &amp; Pengaduan Sekolah — Panel BK
                  </p>
                </div>
                <div class="text-right text-[11px] leading-relaxed">
                  <p class="font-semibold">
                    Dokumen Penanganan Kasus Perundungan
                  </p>
                  <p class="font-mono">
                    {{ report.ticket_code }} · Status: {{ report.status }}
                  </p>
                  <p>Dicetak {{ printDate }}</p>
                </div>
              </div>
            </div>

            <!-- ===== Kartu kasus ===== -->
            <section
              class="print-card fade-up relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/25"
            >
              <span
                class="absolute inset-x-0 top-0 z-10 h-[2px] bg-gradient-to-r from-rose-500/70 via-rose-500/20 to-transparent print:hidden"
                aria-hidden="true"
              ></span>
              <div
                class="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-rose-500/10 blur-3xl print:hidden"
                aria-hidden="true"
              ></div>

              <div class="relative z-10 space-y-6 p-5 sm:p-6 lg:p-7">
                <div
                  class="flex flex-col gap-4 border-b border-slate-800/70 pb-5 sm:flex-row sm:items-start sm:justify-between"
                >
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <span
                        class="rounded border border-rose-500/20 bg-rose-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-rose-400"
                        >{{ report.ticket_code }}</span
                      >
                      <span
                        class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-950/50 px-2.5 py-0.5"
                      >
                        <span
                          class="flex items-end gap-[3px]"
                          aria-hidden="true"
                        >
                          <span
                            class="h-1.5 w-[3px] rounded-[1px]"
                            :class="
                              priority.level >= 1
                                ? priority.bar
                                : 'bg-slate-700'
                            "
                          ></span>
                          <span
                            class="h-2 w-[3px] rounded-[1px]"
                            :class="
                              priority.level >= 2
                                ? priority.bar
                                : 'bg-slate-700'
                            "
                          ></span>
                          <span
                            class="h-2.5 w-[3px] rounded-[1px]"
                            :class="
                              priority.level >= 3
                                ? priority.bar
                                : 'bg-slate-700'
                            "
                          ></span>
                        </span>
                        <span
                          class="text-[11px] font-semibold"
                          :class="priority.text"
                          >Prioritas {{ priority.label }}</span
                        >
                      </span>
                    </div>
                    <h1
                      class="mt-3 text-xl font-extrabold leading-snug tracking-tight text-white sm:text-2xl"
                    >
                      {{ report.category }}
                    </h1>
                    <p class="mt-1.5 text-xs text-slate-500">
                      Laporan perundungan · Pelapor:
                      {{ report.reporter_relation }}
                    </p>
                  </div>

                  <div
                    class="shrink-0 rounded-lg border border-slate-800 bg-slate-950/50 px-3.5 py-3 sm:text-right"
                  >
                    <p
                      class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
                    >
                      Waktu Pelaporan
                    </p>
                    <p class="mt-1 text-xs font-semibold text-slate-300">
                      {{ report.created_at }}
                    </p>
                  </div>
                </div>

                <!-- Identitas pelapor -->
                <div>
                  <div
                    v-if="report.is_anonymous"
                    class="flex items-start gap-3 rounded-xl border border-rose-500/25 bg-rose-500/[0.04] p-4"
                  >
                    <div
                      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-rose-500/25 bg-rose-500/10 text-rose-400"
                    >
                      <Lock class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                      <h3 class="text-xs font-bold text-rose-300">
                        Identitas Pelapor Dikerahasiakan (Anonim)
                      </h3>
                      <p class="mt-1.5 text-xs leading-relaxed text-slate-400">
                        Siswa mengajukan laporan ini secara rahasia. Peran
                        pelapor:
                        <span class="font-semibold text-slate-200">{{
                          report.reporter_relation
                        }}</span
                        >.
                      </p>
                      <p
                        class="mt-2 text-[11px] leading-relaxed text-rose-300/80"
                      >
                        Identitas pelapor anonim tidak dapat dibuka melalui
                        sistem — sesuai kebijakan perlindungan data pelapor.
                      </p>
                    </div>
                  </div>

                  <div
                    v-else
                    class="rounded-xl border border-slate-800 bg-slate-950/40 p-4"
                  >
                    <div
                      class="flex items-center justify-between gap-3 border-b border-slate-800/70 pb-3"
                    >
                      <h3
                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500"
                      >
                        Identitas Pelapor
                      </h3>
                      <span
                        v-if="report.reporter_name"
                        class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-400"
                      >
                        <CheckCircle2 class="h-3.5 w-3.5" />
                        Terverifikasi Siswa
                      </span>
                    </div>

                    <div
                      v-if="report.reporter_name"
                      class="mt-4 flex items-center gap-3.5"
                    >
                      <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-xs font-bold text-emerald-400"
                      >
                        {{ initialsOf(report.reporter_name) }}
                      </div>
                      <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-white">
                          {{ report.reporter_name }}
                        </p>
                        <p class="mt-0.5 truncate text-xs text-slate-400">
                          {{ report.reporter_class }} ·
                          {{ report.reporter_relation }}
                        </p>
                      </div>
                    </div>

                    <div
                      v-else
                      class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-amber-500/20 bg-amber-500/[0.04] px-3.5 py-3"
                    >
                      <p class="text-xs text-slate-400">
                        Identitas pelapor belum ditampilkan.
                      </p>
                      <button
                        type="button"
                        :disabled="isRevealing"
                        @click="handleRevealIdentity"
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-[11px] font-semibold text-amber-400 transition-all duration-150 hover:bg-amber-500/20 active:scale-[.97] disabled:opacity-50"
                      >
                        {{ isRevealing ? "Memuat..." : "Buka Identitas" }}
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Metadata kunci kasus -->
                <dl
                  class="grid grid-cols-1 gap-3 border-t border-slate-800/70 pt-5 sm:grid-cols-2"
                >
                  <div
                    v-for="m in caseMeta"
                    :key="m.label"
                    class="flex items-start gap-2.5 rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3"
                  >
                    <component
                      :is="m.icon"
                      class="mt-0.5 h-4 w-4 shrink-0 text-slate-500"
                    />
                    <div class="min-w-0">
                      <dt
                        class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
                      >
                        {{ m.label }}
                      </dt>
                      <dd
                        class="mt-1 text-xs font-medium leading-snug text-slate-200"
                      >
                        {{ m.value }}
                      </dd>
                    </div>
                  </div>
                </dl>

                <!-- Kronologi -->
                <div class="space-y-3 border-t border-slate-800/70 pt-5">
                  <p
                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500"
                  >
                    Uraian / Kronologi Kejadian
                  </p>
                  <div
                    class="rounded-lg border border-slate-800 bg-slate-950/60 p-4 text-sm leading-relaxed whitespace-pre-line text-slate-300"
                  >
                    {{ report.description }}
                  </div>
                </div>

                <!-- Lampiran bukti -->
                <div
                  v-if="report.attachments.length > 0"
                  class="space-y-3 border-t border-slate-800/70 pt-5"
                >
                  <div class="flex items-center gap-2">
                    <Paperclip class="h-3.5 w-3.5 text-slate-600" />
                    <p
                      class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500"
                    >
                      Bukti Pendukung ({{ report.attachments.length }} berkas)
                    </p>
                  </div>

                  <div class="flex flex-wrap gap-3">
                    <figure
                      v-for="att in report.attachments"
                      :key="att.id"
                      class="group relative w-full max-w-[200px] cursor-pointer overflow-hidden rounded-lg border border-slate-800 bg-slate-950 print:cursor-default"
                      @click="
                        att.file_type?.startsWith('image/') &&
                        openImage(att.url)
                      "
                    >
                      <img
                        v-if="att.file_type?.startsWith('image/')"
                        :src="att.url"
                        alt="Bukti kejadian"
                        loading="lazy"
                        class="h-32 w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                      />
                      <div
                        v-else
                        class="flex h-32 w-full items-center justify-center bg-slate-900 text-[11px] font-semibold text-slate-500"
                      >
                        Berkas PDF
                      </div>
                      <span
                        v-if="att.file_type?.startsWith('image/')"
                        class="pointer-events-none absolute inset-0 flex items-center justify-center gap-2 bg-slate-950/50 text-[10px] font-semibold text-white opacity-0 backdrop-blur-[2px] transition-opacity duration-200 group-hover:opacity-100 print:hidden"
                      >
                        Perbesar
                      </span>
                    </figure>
                  </div>
                </div>

                <p
                  class="flex items-center gap-1.5 border-t border-slate-800/70 pt-4 text-[11px] text-slate-600"
                >
                  <Lock class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />
                  Dokumen kasus bersifat rahasia — hanya untuk petugas berwenang.
                </p>
              </div>
            </section>

            <!-- ===== Timeline audit ===== -->
            <section
              class="print-card fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
              style="animation-delay: 90ms"
            >
              <div
                class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4 sm:px-6"
              >
                <div class="flex items-center gap-2.5">
                  <History class="h-4 w-4 text-emerald-400" />
                  <div>
                    <h2 class="text-sm font-bold tracking-tight text-slate-100">
                      Riwayat Audit &amp; Timeline Penanganan
                    </h2>
                    <p class="mt-0.5 text-[11px] text-slate-500">
                      Setiap tindak lanjut tercatat sebagai jejak audit kasus.
                    </p>
                  </div>
                </div>
                <span
                  class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400"
                  >{{ report.timeline.length }} Catatan</span
                >
              </div>

              <ol v-if="report.timeline.length > 0" class="px-5 py-5 sm:px-6">
                <li
                  v-for="(log, i) in report.timeline"
                  :key="log.id"
                  class="relative flex gap-3.5"
                  :class="i < report.timeline.length - 1 ? 'pb-6' : ''"
                >
                  <div class="flex flex-col items-center">
                    <span
                      class="timeline-dot flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-500 bg-emerald-500 text-slate-950"
                    >
                      <Check class="h-3 w-3 stroke-[3]" />
                    </span>
                    <span
                      v-if="i < report.timeline.length - 1"
                      class="timeline-rail mt-1 w-px flex-1 bg-emerald-500/40"
                      aria-hidden="true"
                    ></span>
                  </div>

                  <div class="min-w-0 flex-1 pt-0.5">
                    <div class="flex flex-wrap items-center gap-2">
                      <p class="text-xs font-semibold text-slate-100">
                        {{ log.title }}
                      </p>
                      <span
                        v-if="i === 0 && report.timeline.length > 1"
                        class="inline-flex items-center gap-1 rounded border border-emerald-500/30 bg-emerald-500/15 px-1.5 py-px text-[10px] font-semibold text-emerald-400"
                      >
                        <span
                          class="h-1 w-1 animate-pulse rounded-full bg-emerald-400"
                          aria-hidden="true"
                        ></span>
                        Terbaru
                      </span>
                    </div>
                    <p class="mt-0.5 font-mono text-[10px] text-slate-600">
                      {{ log.time }}
                    </p>
                    <p
                      class="mt-1.5 text-xs leading-relaxed whitespace-pre-line text-slate-400"
                    >
                      {{ log.description }}
                    </p>

                    <div class="mt-2.5 flex items-center gap-2">
                      <div
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-700 bg-slate-800 text-[9px] font-bold text-slate-300"
                      >
                        {{ initialsOf(log.actor) }}
                      </div>
                      <p class="text-[10px] text-slate-500">
                        Petugas:
                        <span class="font-semibold text-slate-400">{{
                          log.actor
                        }}</span>
                      </p>
                    </div>
                  </div>
                </li>
              </ol>

              <p
                v-else
                class="px-5 py-8 text-center text-xs text-slate-500 sm:px-6"
              >
                Belum ada riwayat penanganan.
              </p>

              <p
                class="flex items-center gap-1.5 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 text-[11px] text-slate-600 sm:px-6"
              >
                <CheckCircle2 class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />
                Jejak audit tidak dapat diubah setelah tercatat
              </p>
            </section>

            <!-- ===== Percakapan dengan Pelapor ===== -->
            <section
              class="print-card fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
              style="animation-delay: 120ms"
            >
              <div
                class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4 sm:px-6"
              >
                <div class="flex items-center gap-2.5">
                  <MessageSquare class="h-4 w-4 text-emerald-400" />
                  <div>
                    <h2 class="text-sm font-bold tracking-tight text-slate-100">
                      Percakapan dengan Pelapor
                    </h2>
                    <p class="mt-0.5 text-[11px] text-slate-500">
                      Komunikasi dua arah terkait penanganan kasus
                    </p>
                  </div>
                </div>
                <span
                  class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400"
                  >{{ comments.length }} Pesan</span
                >
              </div>

              <div class="max-h-96 space-y-3 overflow-y-auto px-5 py-5 sm:px-6">
                <div v-if="isLoadingComments" class="space-y-3">
                  <div
                    v-for="i in 3"
                    :key="i"
                    class="h-14 animate-pulse rounded-lg bg-slate-800/50"
                  ></div>
                </div>

                <p
                  v-else-if="comments.length === 0"
                  class="py-6 text-center text-xs text-slate-500"
                >
                  Belum ada percakapan. Kirim pesan untuk memulai komunikasi dengan pelapor.
                </p>

                <div
                  v-for="msg in comments"
                  :key="msg.id"
                  class="flex gap-2.5"
                  :class="msg.isMine ? 'flex-row-reverse' : ''"
                >
                  <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[9px] font-bold"
                    :class="
                      msg.isCounselor
                        ? 'bg-emerald-500 text-slate-950'
                        : 'border border-slate-600 bg-slate-700 text-slate-200'
                    "
                  >
                    {{ initialsShort(msg.authorName) }}
                  </div>

                  <div
                    class="max-w-[75%] rounded-lg px-3.5 py-2.5"
                    :class="
                      msg.isMine
                        ? 'border border-emerald-500/25 bg-emerald-500/15'
                        : 'border border-slate-700 bg-slate-800/60'
                    "
                  >
                    <p
                      class="text-[10px] font-semibold"
                      :class="msg.isMine ? 'text-emerald-400' : 'text-slate-300'"
                    >
                      {{ msg.authorName }}
                    </p>
                    <p class="mt-1 text-xs leading-relaxed whitespace-pre-line text-slate-200">
                      {{ msg.text }}
                    </p>
                    <p class="mt-1 text-[9px] text-slate-500">{{ msg.createdAt }}</p>
                  </div>
                </div>
              </div>

              <form
                @submit.prevent="handleSendMessage"
                class="flex items-center gap-2.5 border-t border-slate-800/70 bg-slate-950/30 p-4 sm:px-6"
              >
                <input
                  v-model="newMessage"
                  type="text"
                  placeholder="Tulis pesan untuk pelapor..."
                  class="flex-1 rounded-lg border border-slate-800 bg-slate-950/60 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                />
                <button
                  type="submit"
                  :disabled="isSendingMessage || !newMessage.trim()"
                  class="inline-flex shrink-0 items-center justify-center rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40"
                >
                  <Send v-if="!isSendingMessage" class="h-4 w-4" />
                  <Loader2 v-else class="h-4 w-4 animate-spin" />
                </button>
              </form>
            </section>
          </div>

          <!-- ===== Panel aksi (tidak tercetak) ===== -->
          <aside class="space-y-6 print:hidden lg:sticky lg:top-20">
            <section
              class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/25"
              style="animation-delay: 140ms"
            >
              <div
                class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-5 py-4"
              >
                <div>
                  <h2 class="text-sm font-bold tracking-tight text-slate-100">
                    Form Tindak Lanjut BK
                  </h2>
                  <p class="mt-0.5 text-[11px] text-slate-500">
                    Perbarui status &amp; dokumentasikan penanganan
                  </p>
                </div>
                <span
                  class="font-mono text-[9px] font-semibold uppercase tracking-[0.18em] text-slate-600"
                  >Aksi</span
                >
              </div>

              <!-- Kasus sudah final, form dikunci -->
              <div
                v-if="['Selesai', 'Ditolak'].includes(report.status)"
                class="p-5"
              >
                <div
                  class="flex items-start gap-3 rounded-lg border border-slate-700 bg-slate-950/50 px-3.5 py-3"
                >
                  <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-slate-500" />
                  <p class="text-xs leading-relaxed text-slate-400">
                    Kasus ini sudah berstatus
                    <span class="font-semibold text-slate-200">{{
                      report.status
                    }}</span>
                    dan tidak dapat diubah lebih lanjut dari halaman ini.
                  </p>
                </div>
              </div>

              <form
                v-else
                @submit.prevent="handleUpdateStatus"
                class="space-y-5 p-5"
              >
                <div class="space-y-2.5">
                  <label
                    class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                    >Ubah Status Laporan</label
                  >
                  <div
                    class="space-y-1.5"
                    role="radiogroup"
                    aria-label="Status laporan"
                  >
                    <button
                      v-for="opt in statusOptions"
                      :key="opt.value"
                      type="button"
                      :aria-pressed="form.status === opt.value"
                      @click="form.status = opt.value"
                      class="flex w-full items-center gap-2.5 rounded-lg border px-3 py-2 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                      :class="
                        form.status === opt.value
                          ? opt.active
                          : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'
                      "
                    >
                      <span
                        class="h-2 w-2 shrink-0 rounded-full transition-colors duration-200"
                        :class="
                          form.status === opt.value ? opt.dot : 'bg-slate-600'
                        "
                        aria-hidden="true"
                      ></span>
                      <span class="min-w-0 flex-1">
                        <span
                          class="block text-xs font-semibold text-slate-100"
                          >{{ opt.label }}</span
                        >
                        <span
                          class="block text-[10px] leading-snug text-slate-500"
                          >{{ opt.desc }}</span
                        >
                      </span>
                      <Check
                        v-if="form.status === opt.value"
                        class="h-3.5 w-3.5 shrink-0 stroke-[2.5]"
                      />
                    </button>
                  </div>
                  <p
                    v-if="formErrors.status"
                    class="flex items-center gap-1.5 text-[11px] font-medium text-red-400"
                  >
                    <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                    {{ formErrors.status }}
                  </p>
                </div>

                <div class="space-y-2.5 border-t border-slate-800/70 pt-4">
                  <label
                    for="counselor-action"
                    class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                    >Jenis Tindakan BK</label
                  >
                  <div class="relative">
                    <ClipboardList
                      class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                    />
                    <select
                      id="counselor-action"
                      v-model="form.counselor_action"
                      class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                    >
                      <option
                        v-for="a in actionOptions"
                        :key="a.value"
                        :value="a.value"
                        class="bg-slate-900 text-slate-100"
                      >
                        {{ a.label }}
                      </option>
                    </select>
                    <ChevronDown
                      class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                    />
                  </div>
                </div>

                <div class="space-y-2 border-t border-slate-800/70 pt-4">
                  <span
                    class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                    >Templat Catatan Cepat</span
                  >
                  <div class="flex flex-wrap gap-1.5">
                    <button
                      v-for="tpl in noteTemplates"
                      :key="tpl.label"
                      type="button"
                      @click="applyTemplate(tpl.text)"
                      class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-slate-800 bg-slate-950/50 px-2.5 py-1 text-[11px] font-medium text-slate-400 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
                    >
                      <Plus class="h-3 w-3" />
                      {{ tpl.label }}
                    </button>
                  </div>
                </div>

                <div class="space-y-2 border-t border-slate-800/70 pt-4">
                  <div class="flex items-center justify-between">
                    <label
                      for="handling-notes"
                      class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                      >Catatan Penanganan &amp; Tindak Lanjut</label
                    >
                    <span
                      class="font-mono text-[10px] tabular-nums"
                      :class="
                        notesLength > 0
                          ? 'text-emerald-500/80'
                          : 'text-slate-600'
                      "
                      >{{ notesLength }} karakter</span
                    >
                  </div>
                  <textarea
                    id="handling-notes"
                    v-model="form.handling_notes"
                    rows="5"
                    placeholder="Tuliskan catatan konseling atau hasil penanganan kasus secara mendalam di sini..."
                    :aria-invalid="!!formErrors.handling_notes || undefined"
                    class="w-full resize-none rounded-lg border bg-slate-950/60 px-3.5 py-2.5 text-sm leading-relaxed text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                    :class="
                      formErrors.handling_notes
                        ? 'border-red-400/60'
                        : 'border-slate-800'
                    "
                  ></textarea>
                  <p
                    v-if="formErrors.handling_notes"
                    class="flex items-center gap-1.5 text-[11px] font-medium text-red-400"
                  >
                    <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                    {{ formErrors.handling_notes }}
                  </p>
                  <p
                    class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500"
                  >
                    <Lock class="mt-px h-3 w-3 shrink-0 text-emerald-500/70" />
                    Catatan ini tersimpan sebagai dokumentasi rahasia internal
                    BK.
                  </p>
                </div>

                <div
                  v-if="report.handled_by"
                  class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3.5 py-3"
                >
                  <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-[10px] font-bold text-emerald-400"
                  >
                    {{ initialsOf(report.handled_by) }}
                  </div>
                  <div class="min-w-0">
                    <p
                      class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
                    >
                      Penanggung Jawab Terakhir
                    </p>
                    <p
                      class="mt-0.5 truncate text-xs font-semibold text-emerald-400"
                    >
                      {{ report.handled_by }}
                    </p>
                  </div>
                </div>

                <button
                  type="submit"
                  :disabled="isSubmitting"
                  class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                >
                  <Check v-if="!isSubmitting" class="h-4 w-4 stroke-[2]" />
                  <Loader2 v-else class="h-4 w-4 animate-spin" />
                  {{ isSubmitting ? "Memperbarui..." : "Simpan Tindak Lanjut" }}
                </button>
              </form>
            </section>
          </aside>
        </div>
      </main>

      <footer class="border-t border-slate-800/70 print:hidden">
        <div
          class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8"
        >
          <p class="text-[11px] text-slate-600">
            © {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan
            Sekolah
          </p>
          <p class="text-[11px] text-slate-600">
            Laporan perundungan ditangani secara rahasia oleh petugas berwenang
          </p>
        </div>
      </footer>

      <div
        v-if="showImageModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 print:hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Pratinjau bukti laporan"
      >
        <div
          class="backdrop-in absolute inset-0 bg-slate-950/90 backdrop-blur-md"
          aria-hidden="true"
          @click="showImageModal = false"
        ></div>

        <div
          class="modal-panel relative w-full max-w-4xl overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-2 shadow-2xl shadow-black/50"
        >
          <button
            type="button"
            @click="showImageModal = false"
            aria-label="Tutup"
            class="absolute right-4 top-4 z-10 flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 bg-slate-950/80 text-slate-400 backdrop-blur-sm transition-colors duration-200 hover:bg-slate-800 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <X class="h-4 w-4" />
          </button>
          <img
            :src="activeAttachmentUrl"
            alt="Bukti laporan diperbesar"
            class="max-h-[80vh] w-full rounded-lg object-contain"
          />
          <p class="px-3 pb-1 pt-2 text-center text-[11px] text-slate-500">
            {{ report.ticket_code }} · Bukti pendukung kasus
          </p>
        </div>
      </div>
    </template>
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
@media print {
  @page {
    margin: 12mm;
  }
  .sapa-root {
    background: #ffffff !important;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
  .print-card {
    background: #ffffff !important;
    border-color: #cbd5e1 !important;
    box-shadow: none !important;
    break-inside: avoid;
  }
  .print-card,
  .print-card * {
    color: #1e293b !important;
    border-color: #e2e8f0 !important;
  }
  .print-card [class*="bg-"] {
    background-color: transparent !important;
  }
  .print-card .timeline-rail {
    background-color: #cbd5e1 !important;
  }
  .print-card .timeline-dot {
    background-color: #ffffff !important;
    border-color: #64748b !important;
  }
  .print-card img {
    background-color: #f1f5f9 !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px;
  }
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