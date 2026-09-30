<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { useAuthStore } from "@/stores/auth";
import reportService from "@/services/reportService";
import {
  ArrowLeft,
  LogOut,
  Layers,
  MapPin,
  Clock,
  User,
  Image as ImageIcon,
  ZoomIn,
  ShieldCheck,
  History,
  Check,
  AlertTriangle,
  Lock,
  X,
  Loader2,
  Send,
  MessageSquare,
  Camera,
  Upload,
} from "lucide-vue-next";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const reportId = route.params.id;
const logoFailed = ref(false);
const isLoading = ref(true);
const isSubmitting = ref(false);

const STATUS_MAP = {
  pending: "Menunggu",
  reviewing: "Ditinjau",
  in_progress: "Diproses",
  resolved: "Selesai",
  rejected: "Ditolak",
};
const STATUS_MAP_REVERSE = {
  Ditinjau: "reviewing",
  Diproses: "in_progress",
  Selesai: "resolved",
  Ditolak: "rejected",
};
const CATEGORY_MAP = {
  electricity: "Elektronik & Kelistrikan",
  sanitation: "Sanitasi & Plambing",
  building: "Bangunan & Mebel",
  other: "Lainnya",
};
const DAMAGE_MAP = { minor: "Ringan", moderate: "Sedang", severe: "Berat" };

const report = ref(null);

async function loadReport() {
  isLoading.value = true;
  try {
    const data = await reportService.getReportDetail(reportId);
    const r = data.data ?? data;

    report.value = {
      id: r.id,
      ticket_code: r.report_code,
      title: r.title,
      category: CATEGORY_MAP[r.detail?.category] ?? "—",
      location: r.detail?.location ?? "—",
      damage_level: DAMAGE_MAP[r.detail?.damage_level] ?? "Ringan",
      reporter_name: r.reporter?.name ?? "—",
      is_anonymous: r.is_anonymous,
      description: r.description,
      created_at: r.created_at,
      status: STATUS_MAP[r.status] ?? r.status,
      attachments: r.attachments ?? [],
      logs: (r.status_logs ?? []).map((log) => ({
        id: log.id,
        status: STATUS_MAP[log.new_status] ?? log.new_status,
        note: log.note || "Tidak ada catatan.",
        created_at: formatDateTime(log.created_at),
      })),
    };

    form.value.status =
      report.value.status !== "Menunggu" ? report.value.status : "Ditinjau";
  } catch (error) {
    toast.error("Gagal memuat detail laporan.");
  } finally {
    isLoading.value = false;
  }
}

/* Pisahkan lampiran berdasarkan fase */
const beforePhotos = computed(
  () => report.value?.attachments.filter((a) => a.phase !== "after") ?? [],
);
const afterPhotos = computed(
  () => report.value?.attachments.filter((a) => a.phase === "after") ?? [],
);

/* ---------------------------------- */
/* Chat / komentar                     */
/* ---------------------------------- */
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
      isMine: c.is_mine,
      createdAt: formatDateTime(c.created_at),
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
    const data = await reportService.addComment(
      reportId,
      newMessage.value.trim(),
    );
    const c = data.comment;
    comments.value.push({
      id: c.id,
      text: c.comment,
      authorName: c.author?.name ?? "Anda",
      isMine: true,
      createdAt: formatDateTime(c.created_at),
    });
    newMessage.value = "";
  } catch (error) {
    toast.error(error?.response?.data?.message || "Gagal mengirim pesan.");
  } finally {
    isSendingMessage.value = false;
  }
}

onMounted(() => {
  loadReport();
  loadComments();
  window.addEventListener("keydown", handleEscKey);
});
onBeforeUnmount(() => {
  window.removeEventListener("keydown", handleEscKey);
  document.body.style.overflow = "";
});

/* ---------------------------------- */
/* Upload foto "sesudah perbaikan"     */
/* ---------------------------------- */
const afterPhotoFiles = ref([]);
const isUploadingPhotos = ref(false);
const afterPhotoInputRef = ref(null);

function handleAfterPhotoSelect(e) {
  const files = Array.from(e.target.files);
  e.target.value = "";
  if (
    afterPhotos.value.length + afterPhotoFiles.value.length + files.length >
    3
  ) {
    toast.error("Maksimal 3 foto hasil perbaikan.");
    return;
  }
  afterPhotoFiles.value.push(...files);
}

function removeStagedPhoto(index) {
  afterPhotoFiles.value.splice(index, 1);
}

async function handleUploadAfterPhotos() {
  if (afterPhotoFiles.value.length === 0) return;
  isUploadingPhotos.value = true;
  try {
    const data = await reportService.uploadAttachments(
      reportId,
      afterPhotoFiles.value,
    );
    report.value.attachments.push(...data.attachments.map((a) => ({ ...a })));
    afterPhotoFiles.value = [];
    toast.success("Foto hasil perbaikan berhasil ditambahkan.");
  } catch (error) {
    toast.error(error?.response?.data?.message || "Gagal mengunggah foto.");
  } finally {
    isUploadingPhotos.value = false;
  }
}

/* ---------------------------------- */
/* Update status                       */
/* ---------------------------------- */
const form = ref({ status: "Ditinjau", note: "" });
const formErrors = ref({});
watch(
  () => form.value.note,
  () => {
    if (formErrors.value.note) formErrors.value.note = "";
  },
);
const notesLength = computed(() => (form.value.note || "").length);

async function handleUpdateStatus() {
  if (!form.value.note.trim()) {
    formErrors.value.note = "Catatan perkembangan wajib diisi.";
    toast.error("Harap isi catatan perkembangan/penanganan.");
    return;
  }

  isSubmitting.value = true;
  try {
    await reportService.updateReportStatus(reportId, {
      status: STATUS_MAP_REVERSE[form.value.status],
      note: form.value.note,
    });
    toast.success("Status perbaikan berhasil diperbarui!", {
      description: `Tiket ${report.value.ticket_code} kini berstatus: ${form.value.status}.`,
    });
    form.value.note = "";
    formErrors.value = {};
    await loadReport();
  } catch (error) {
    toast.error(error?.response?.data?.message || "Gagal memperbarui status.");
  } finally {
    isSubmitting.value = false;
  }
}

/* ---------------------------------- */
/* Helpers                             */
/* ---------------------------------- */
const goBack = () => router.push({ name: "staff-facility-queue" });
const handleLogout = async () => {
  await authStore.logout();
  toast.success("Berhasil keluar dari sistem.");
  router.push({ name: "login" });
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

const getDamageBorderClass = (level) => {
  switch (level) {
    case "Berat":
      return "border-l-rose-500";
    case "Sedang":
      return "border-l-amber-500";
    case "Ringan":
      return "border-l-emerald-500";
    default:
      return "border-l-slate-700";
  }
};

const getDamageMeta = (level) => {
  const map = {
    Berat: {
      label: "Berat",
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
    Ringan: {
      label: "Ringan",
      level: 1,
      text: "text-emerald-400",
      bar: "bg-emerald-500",
    },
  };
  return map[level] || map["Ringan"];
};

const damage = computed(() =>
  report.value
    ? getDamageMeta(report.value.damage_level)
    : getDamageMeta("Ringan"),
);

const statusOptions = [
  {
    value: "Ditinjau",
    label: "Ditinjau — Inspeksi Lapangan",
    desc: "Verifikasi lokasi & kondisi kerusakan",
    dot: "bg-blue-400",
    active: "border-blue-500/50 bg-blue-500/[0.06] text-blue-400",
  },
  {
    value: "Diproses",
    label: "Diproses — Perbaikan Berlangsung",
    desc: "Teknisi sedang mengerjakan perbaikan",
    dot: "bg-emerald-400",
    active: "border-emerald-500/50 bg-emerald-500/[0.06] text-emerald-400",
  },
  {
    value: "Selesai",
    label: "Selesai — Fasilitas Normal",
    desc: "Perbaikan tuntas & terverifikasi",
    dot: "bg-slate-300",
    active: "border-slate-500/60 bg-slate-500/10 text-slate-300",
  },
  {
    value: "Ditolak",
    label: "Ditolak — Bukan Wewenang / Invalid",
    desc: "Di luar lingkup sarpras / tidak valid",
    dot: "bg-red-400",
    active: "border-red-500/50 bg-red-500/[0.06] text-red-400",
  },
];

function formatDateTime(d) {
  if (!d) return "—";
  return new Date(d).toLocaleString("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

const caseMeta = computed(() =>
  report.value
    ? [
        { label: "Kategori", value: report.value.category, icon: Layers },
        {
          label: "Lokasi Kerusakan",
          value: report.value.location,
          icon: MapPin,
        },
        {
          label: "Waktu Pelaporan",
          value: formatDateTime(report.value.created_at),
          icon: Clock,
        },
        {
          label: "Pelapor",
          value: report.value.is_anonymous
            ? "Anonim"
            : report.value.reporter_name,
          icon: User,
        },
      ]
    : [],
);

const initialsOf = (name) => {
  if (!name || typeof name !== "string") return "?";
  const parts = name.trim().split(/\s+/);
  return parts.length > 1
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.slice(0, 2).toUpperCase();
};

const previewImage = ref(null);
const openPreview = (img) => {
  previewImage.value = img;
};
const closePreview = () => {
  previewImage.value = null;
};
const handleEscKey = (e) => {
  if (e.key === "Escape" && previewImage.value) closePreview();
};
watch(previewImage, (v) => {
  document.body.style.overflow = v ? "hidden" : "";
});

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
    <!-- ============ Bar atas ============ -->
    <header
      class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md print:hidden"
    >
      <div
        class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8"
      >
        <!-- Kembali + merek panel -->
        <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
          <button
            type="button"
            @click="goBack"
            title="Kembali"
            class="group inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs font-semibold text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-100 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <ArrowLeft
              class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5"
            />
            <span class="hidden sm:inline">Kembali</span>
          </button>

          <span
            class="hidden h-6 w-px bg-slate-800 sm:block"
            aria-hidden="true"
          ></span>

          <div class="hidden min-w-0 items-center gap-2.5 sm:flex">
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
              <span v-else class="text-sm font-extrabold text-emerald-400">
                S
              </span>
            </div>

            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <p
                  class="text-[15px] font-extrabold leading-none tracking-tight text-white"
                >
                  SAPA
                </p>

                <span
                  class="rounded border border-cyan-500/30 bg-cyan-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-cyan-400"
                >
                  Staff Sarpras
                </span>
              </div>

              <p
                class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block"
              >
                Detail &amp; Tindak Lanjut Perbaikan
              </p>
            </div>
          </div>
        </div>

        <!-- Konteks tiket + aksi -->
        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
          <template v-if="report">
            <span
              class="hidden rounded-md border border-cyan-500/20 bg-cyan-500/10 px-2 py-1 font-mono text-[11px] font-medium text-cyan-400 sm:inline-flex"
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
          </template>

          <div class="hidden h-6 w-px bg-slate-800 sm:block"></div>

          <button
            type="button"
            @click="handleLogout"
            title="Keluar dari akun"
            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/80 text-slate-500 transition-all duration-200 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50"
          >
            <LogOut class="h-5 w-5" />
          </button>
        </div>
      </div>
    </header>

    <!-- ============ Loading ============ -->
    <div
      v-if="isLoading"
      class="flex flex-1 items-center justify-center px-4 py-20"
    >
      <div class="flex flex-col items-center gap-3">
        <Loader2 class="h-7 w-7 animate-spin text-emerald-400" />
        <p class="text-xs text-slate-500">Memuat detail laporan...</p>
      </div>
    </div>

    <!-- ============ Data berhasil dimuat ============ -->
    <main
      v-else-if="report"
      class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10"
    >
      <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <!-- ===== Kolom tiket ===== -->
        <div class="min-w-0 space-y-6">
          <!-- Kepala dokumen cetak -->
          <div class="hidden border-b border-slate-300 pb-4 print:block">
            <div class="flex items-end justify-between gap-4">
              <div>
                <p class="text-lg font-extrabold tracking-tight">SAPA</p>

                <p
                  class="mt-0.5 text-[10px] font-medium uppercase tracking-[0.16em]"
                >
                  Sistem Layanan Aspirasi &amp; Pengaduan Sekolah — Panel
                  Sarpras
                </p>
              </div>

              <div class="text-right text-[11px] leading-relaxed">
                <p class="font-semibold">
                  Dokumen Tindak Lanjut Perbaikan Fasilitas
                </p>

                <p class="font-mono">
                  {{ report.ticket_code }} · Status: {{ report.status }}
                </p>

                <p>Dicetak {{ printDate }}</p>
              </div>
            </div>
          </div>

          <!-- ===== Kartu tiket ===== -->
          <section
            class="print-card fade-up relative overflow-hidden rounded-xl border border-slate-800 border-l-[3px] bg-slate-900 shadow-xl shadow-black/25"
            :class="getDamageBorderClass(report.damage_level)"
          >
            <span
              class="absolute inset-x-0 top-0 z-10 h-0.5 bg-linear-to-r from-cyan-500/70 via-cyan-500/20 to-transparent print:hidden"
              aria-hidden="true"
            ></span>

            <div
              class="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-cyan-500/10 blur-3xl print:hidden"
              aria-hidden="true"
            ></div>

            <div class="relative z-10 space-y-6 p-5 sm:p-6 lg:p-7">
              <!-- Kepala tiket -->
              <div
                class="flex flex-col gap-4 border-b border-slate-800/70 pb-5 sm:flex-row sm:items-start sm:justify-between"
              >
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <span
                      class="rounded border border-cyan-500/20 bg-cyan-500/10 px-1.5 py-0.5 font-mono text-[11px] font-medium text-cyan-400"
                    >
                      {{ report.ticket_code }}
                    </span>

                    <span
                      class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-950/50 px-2.5 py-0.5"
                    >
                      <span class="flex items-end gap-0.5" aria-hidden="true">
                        <span
                          class="h-1.5 w-0.5 rounded-[1px]"
                          :class="
                            damage.level >= 1 ? damage.bar : 'bg-slate-700'
                          "
                        ></span>

                        <span
                          class="h-2 w-0.5 rounded-[1px]"
                          :class="
                            damage.level >= 2 ? damage.bar : 'bg-slate-700'
                          "
                        ></span>

                        <span
                          class="h-2.5 w-0.5 rounded-[1px]"
                          :class="
                            damage.level >= 3 ? damage.bar : 'bg-slate-700'
                          "
                        ></span>
                      </span>

                      <span
                        class="text-[11px] font-semibold"
                        :class="damage.text"
                      >
                        Kerusakan {{ damage.label }}
                      </span>
                    </span>

                    <span
                      class="hidden items-center gap-1.5 rounded-full border border-slate-800 bg-slate-950/50 px-2.5 py-0.5 text-[10px] font-medium text-slate-400 sm:inline-flex"
                    >
                      {{ report.category }}
                    </span>
                  </div>

                  <h1
                    class="mt-3 text-xl font-extrabold leading-snug tracking-tight text-white sm:text-2xl"
                  >
                    {{ report.title }}
                  </h1>

                  <p class="mt-1.5 text-xs text-slate-500">
                    Laporan kerusakan fasilitas · Pelapor:
                    {{ report.is_anonymous ? "Anonim" : report.reporter_name }}
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
                    {{ formatDateTime(report.created_at) }}
                  </p>
                </div>
              </div>

              <!-- Metadata kunci -->
              <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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

              <!-- Deskripsi kerusakan -->
              <div class="space-y-3 border-t border-slate-800/70 pt-5">
                <p
                  class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500"
                >
                  Deskripsi Kerusakan
                </p>

                <div
                  class="rounded-lg border border-slate-800 bg-slate-950/60 p-4 text-sm leading-relaxed text-slate-300"
                >
                  {{ report.description }}
                </div>
              </div>

              <!-- Foto Sebelum Perbaikan -->
              <div
                v-if="beforePhotos.length"
                class="space-y-3 border-t border-slate-800/70 pt-5"
              >
                <div class="flex items-center gap-2">
                  <ImageIcon class="h-3.5 w-3.5 text-slate-600" />

                  <p
                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500"
                  >
                    Foto Kerusakan (Sebelum)
                  </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                  <figure
                    v-for="img in beforePhotos"
                    :key="img.id"
                    class="group relative cursor-pointer overflow-hidden rounded-lg border border-slate-800 bg-slate-950"
                    @click="openPreview(img)"
                  >
                    <img
                      :src="img.url"
                      alt="Foto sebelum perbaikan"
                      loading="lazy"
                      class="h-32 w-full object-cover transition-transform duration-500 group-hover:scale-[1.04] sm:h-36"
                    />

                    <span
                      class="pointer-events-none absolute inset-0 flex items-center justify-center gap-2 bg-slate-950/50 text-[11px] font-semibold text-white opacity-0 backdrop-blur-[2px] transition-opacity duration-200 group-hover:opacity-100"
                    >
                      <ZoomIn class="h-4 w-4" />
                      Perbesar
                    </span>
                  </figure>
                </div>
              </div>

              <!-- Foto Sesudah Perbaikan -->
              <div class="space-y-3 border-t border-slate-800/70 pt-5">
                <div class="flex items-center gap-2">
                  <Camera class="h-3.5 w-3.5 text-emerald-500" />

                  <p
                    class="text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-400"
                  >
                    Foto Hasil Perbaikan (Sesudah)
                  </p>
                </div>

                <div
                  v-if="afterPhotos.length"
                  class="grid grid-cols-2 gap-3 sm:grid-cols-3"
                >
                  <figure
                    v-for="img in afterPhotos"
                    :key="img.id"
                    class="group relative cursor-pointer overflow-hidden rounded-lg border border-emerald-500/25 bg-slate-950"
                    @click="openPreview(img)"
                  >
                    <img
                      :src="img.url"
                      alt="Foto hasil perbaikan"
                      loading="lazy"
                      class="h-32 w-full object-cover transition-transform duration-500 group-hover:scale-[1.04] sm:h-36"
                    />

                    <span
                      class="pointer-events-none absolute inset-0 flex items-center justify-center gap-2 bg-slate-950/50 text-[11px] font-semibold text-white opacity-0 backdrop-blur-[2px] transition-opacity duration-200 group-hover:opacity-100"
                    >
                      <ZoomIn class="h-4 w-4" />
                      Perbesar
                    </span>
                  </figure>
                </div>

                <p v-else class="text-xs text-slate-500">
                  Belum ada foto hasil perbaikan yang diunggah.
                </p>

                <!-- Staged files sebelum upload -->
                <div v-if="afterPhotoFiles.length" class="flex flex-wrap gap-2">
                  <div
                    v-for="(f, i) in afterPhotoFiles"
                    :key="i"
                    class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800/60 px-2.5 py-1.5 text-[11px] text-slate-300"
                  >
                    {{ f.name }}

                    <button
                      type="button"
                      @click="removeStagedPhoto(i)"
                      class="text-slate-500 hover:text-rose-400"
                    >
                      <X class="h-3 w-3" />
                    </button>
                  </div>
                </div>

                <input
                  ref="afterPhotoInputRef"
                  type="file"
                  accept="image/*"
                  multiple
                  class="hidden"
                  @change="handleAfterPhotoSelect"
                />

                <div class="flex flex-wrap gap-2">
                  <button
                    type="button"
                    @click="afterPhotoInputRef?.click()"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-2 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400"
                  >
                    <Upload class="h-3.5 w-3.5" />
                    Pilih Foto
                  </button>

                  <button
                    v-if="afterPhotoFiles.length"
                    type="button"
                    :disabled="isUploadingPhotos"
                    @click="handleUploadAfterPhotos"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-3 py-2 text-xs font-bold text-slate-950 transition-all duration-150 hover:bg-emerald-400 disabled:opacity-40"
                  >
                    <Loader2
                      v-if="isUploadingPhotos"
                      class="h-3.5 w-3.5 animate-spin"
                    />

                    <Check v-else class="h-3.5 w-3.5" />

                    {{
                      isUploadingPhotos
                        ? "Mengunggah..."
                        : `Unggah ${afterPhotoFiles.length} Foto`
                    }}
                  </button>
                </div>
              </div>

              <!-- Strip transparansi -->
              <p
                class="flex items-center gap-1.5 border-t border-slate-800/70 pt-4 text-[11px] text-slate-600"
              >
                <ShieldCheck class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />

                Pelapor dapat memantau perkembangan perbaikan ini secara
                transparan.
              </p>
            </div>
          </section>

          <!-- ===== Timeline perkembangan ===== -->
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
                    Riwayat Perkembangan Perbaikan
                  </h2>

                  <p class="mt-0.5 text-[11px] text-slate-500">
                    Setiap tindak lanjut tercatat dan terlihat oleh pelapor.
                  </p>
                </div>
              </div>

              <span
                class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400"
              >
                {{ report.logs.length }} Catatan
              </span>
            </div>

            <ol class="px-5 py-5 sm:px-6">
              <li
                v-for="(log, i) in report.logs"
                :key="log.id"
                class="relative flex gap-3.5"
                :class="i < report.logs.length - 1 ? 'pb-6' : ''"
              >
                <!-- Rel & titik -->
                <div class="flex flex-col items-center">
                  <span
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-500 bg-emerald-500 text-slate-950"
                  >
                    <Check class="h-3 w-3" />
                  </span>

                  <span
                    v-if="i < report.logs.length - 1"
                    class="mt-1 w-px flex-1 bg-emerald-500/40"
                    aria-hidden="true"
                  ></span>
                </div>

                <!-- Isi catatan -->
                <div class="min-w-0 flex-1 pt-0.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold text-slate-100">
                      {{ log.status }}
                    </p>

                    <span
                      v-if="i === 0 && report.logs.length > 1"
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
                    {{ log.created_at }}
                  </p>

                  <p class="mt-1.5 text-xs leading-relaxed text-slate-400">
                    {{ log.note }}
                  </p>
                </div>
              </li>
            </ol>

            <p
              class="flex items-center gap-1.5 border-t border-slate-800/70 bg-slate-950/40 px-5 py-3 text-[11px] text-slate-600 sm:px-6"
            >
              <ShieldCheck class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />

              Jejak perkembangan tidak dapat diubah setelah tercatat
            </p>
          </section>

          <!-- ===== Percakapan ===== -->
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
                    Komunikasi terkait penanganan laporan
                  </p>
                </div>
              </div>

              <span
                class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400"
              >
                {{ comments.length }} Pesan
              </span>
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
                Belum ada percakapan.
              </p>

              <div
                v-for="msg in comments"
                :key="msg.id"
                class="flex gap-2.5"
                :class="msg.isMine ? 'flex-row-reverse' : ''"
              >
                <div
                  class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-slate-600 bg-slate-700 text-[9px] font-bold text-slate-200"
                >
                  {{ initialsOf(msg.authorName) }}
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

                  <p
                    class="mt-1 whitespace-pre-line text-xs leading-relaxed text-slate-200"
                  >
                    {{ msg.text }}
                  </p>

                  <p class="mt-1 text-[9px] text-slate-500">
                    {{ msg.createdAt }}
                  </p>
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
                class="flex-1 rounded-lg border border-slate-800 bg-slate-950/60 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              />

              <button
                type="submit"
                :disabled="isSendingMessage || !newMessage.trim()"
                class="inline-flex shrink-0 items-center justify-center rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 hover:bg-emerald-400 disabled:opacity-40"
              >
                <Send v-if="!isSendingMessage" class="h-4 w-4" />

                <Loader2 v-else class="h-4 w-4 animate-spin" />
              </button>
            </form>
          </section>
        </div>

        <!-- ===== Panel aksi ===== -->
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
                  Update Status Perbaikan
                </h2>

                <p class="mt-0.5 text-[11px] text-slate-500">
                  Perbarui progres &amp; dokumentasikan penanganan
                </p>
              </div>

              <span
                class="font-mono text-[9px] font-semibold uppercase tracking-[0.18em] text-slate-600"
              >
                Aksi
              </span>
            </div>

            <form @submit.prevent="handleUpdateStatus" class="space-y-5 p-5">
              <!-- Status -->
              <div class="space-y-2.5">
                <label
                  class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                >
                  Status Baru
                </label>

                <div
                  class="space-y-1.5"
                  role="radiogroup"
                  aria-label="Status perbaikan"
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
                      <span class="block text-xs font-semibold text-slate-100">
                        {{ opt.label }}
                      </span>

                      <span
                        class="block text-[10px] leading-snug text-slate-500"
                      >
                        {{ opt.desc }}
                      </span>
                    </span>

                    <Check
                      v-if="form.status === opt.value"
                      class="h-3.5 w-3.5 shrink-0"
                    />
                  </button>
                </div>
              </div>

              <!-- Catatan penanganan -->
              <div class="space-y-2 border-t border-slate-800/70 pt-4">
                <div class="flex items-center justify-between">
                  <label
                    for="handling-note"
                    class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                  >
                    Catatan Penanganan
                  </label>

                  <span
                    class="font-mono text-[10px] tabular-nums"
                    :class="
                      notesLength > 0 ? 'text-emerald-500/80' : 'text-slate-600'
                    "
                  >
                    {{ notesLength }} karakter
                  </span>
                </div>

                <textarea
                  id="handling-note"
                  v-model="form.note"
                  rows="5"
                  placeholder="Contoh: Teknisi telah mengganti kapasitor AC yang terbakar..."
                  :aria-invalid="!!formErrors.note || undefined"
                  class="w-full resize-none rounded-lg border bg-slate-950/60 px-3.5 py-2.5 text-sm leading-relaxed text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  :class="
                    formErrors.note ? 'border-red-400/60' : 'border-slate-800'
                  "
                ></textarea>

                <p
                  v-if="formErrors.note"
                  class="flex items-center gap-1.5 text-[11px] font-medium text-red-400"
                >
                  <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                  {{ formErrors.note }}
                </p>

                <p
                  class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500"
                >
                  <Lock class="mt-px h-3 w-3 shrink-0 text-emerald-500/70" />

                  Catatan ini akan terlihat oleh pelapor sebagai perkembangan
                  perbaikan.
                </p>
              </div>

              <!-- Submit -->
              <button
                type="submit"
                :disabled="isSubmitting"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
              >
                <Check v-if="!isSubmitting" class="h-4 w-4" />

                <Loader2 v-else class="h-4 w-4 animate-spin" />

                {{ isSubmitting ? "Menyimpan..." : "Perbarui Status" }}
              </button>
            </form>
          </section>
        </aside>
      </div>
    </main>

    <!-- ============ Data tidak ditemukan ============ -->
    <div v-else class="flex flex-1 items-center justify-center px-4 py-20">
      <div class="text-center">
        <AlertTriangle class="mx-auto h-8 w-8 text-amber-400" />

        <h2 class="mt-4 text-sm font-semibold text-slate-200">
          Detail laporan tidak tersedia
        </h2>

        <p class="mt-1 text-xs text-slate-500">
          Laporan tidak ditemukan atau tidak dapat diakses.
        </p>

        <button
          type="button"
          @click="goBack"
          class="mt-5 inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-xs font-semibold text-slate-300 transition-colors hover:border-emerald-500/40 hover:text-emerald-400"
        >
          <ArrowLeft class="h-4 w-4" />
          Kembali ke Antrian
        </button>
      </div>
    </div>

    <!-- ============ Footer ============ -->
    <footer class="border-t border-slate-800/70 print:hidden">
      <div
        class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8"
      >
        <p class="text-[11px] text-slate-600">
          © {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan
          Sekolah
        </p>

        <p class="text-[11px] text-slate-600">
          Perbaikan fasilitas didokumentasikan secara transparan
        </p>
      </div>
    </footer>

    <!-- ============ Modal perbesar lampiran ============ -->
    <div
      v-if="previewImage && report"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 print:hidden"
      role="dialog"
      aria-modal="true"
      aria-label="Pratinjau foto kerusakan"
    >
      <div
        class="backdrop-in absolute inset-0 bg-slate-950/90 backdrop-blur-md"
        aria-hidden="true"
        @click="closePreview"
      ></div>

      <div
        class="modal-panel relative w-full max-w-4xl overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-2 shadow-2xl shadow-black/50"
      >
        <button
          type="button"
          @click="closePreview"
          aria-label="Tutup"
          class="absolute right-4 top-4 z-10 flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 bg-slate-950/80 text-slate-400 backdrop-blur-sm transition-colors duration-200 hover:bg-slate-800 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
        >
          <X class="h-4 w-4" />
        </button>

        <img
          :src="previewImage.url"
          :alt="'Bukti kerusakan diperbesar ' + report.ticket_code"
          class="max-h-[80vh] w-full rounded-lg object-contain"
        />

        <p class="px-3 pb-1 pt-2 text-center text-[11px] text-slate-500">
          {{ report.ticket_code }} · Foto lampiran kerusakan
        </p>
      </div>
    </div>
  </div>
</template>

<style>
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
/* Entrance seksi: fade-up halus dengan stagger */
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

/* Modal: latar memudar, panel naik dengan skala halus */
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

/* ================================================================
   CETAK DOKUMEN — tema gelap dibalik menjadi dokumen terang
   (senada dengan halaman detail BK) agar hasil cetak terbaca.
   ================================================================ */
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
    border-left-color: #cbd5e1 !important;
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
