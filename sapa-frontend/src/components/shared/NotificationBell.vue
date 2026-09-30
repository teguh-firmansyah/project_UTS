<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from "vue";
import { useRouter } from "vue-router";
import { toast } from "vue-sonner";
import {
  Bell,
  Check,
  Trash2,
  MessageSquare,
  UserPlus,
  RefreshCw,
} from "lucide-vue-next";
import notificationService from "@/services/notificationService";

const router = useRouter();

const isOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const isLoading = ref(false);
let pollTimer = null;

async function loadUnreadCount() {
  try {
    const data = await notificationService.getUnreadCount();
    unreadCount.value = data.count;
  } catch {
    // gagal senyap — tidak perlu ganggu UI dengan toast untuk polling background
  }
}

async function loadNotifications() {
  isLoading.value = true;
  try {
    const data = await notificationService.getNotifications();
    notifications.value = (data.data ?? data).map((n) => ({
      id: n.id,
      title: n.title,
      message: n.message,
      isRead: n.is_read,
      reportId: n.report_id,
      createdAt: formatRelative(n.created_at),
    }));
  } catch {
    // silent
  } finally {
    isLoading.value = false;
  }
}

function formatRelative(dateString) {
  const date = new Date(dateString);
  const diffMin = Math.floor((Date.now() - date.getTime()) / 60000);
  if (diffMin < 1) return "Baru saja";
  if (diffMin < 60) return `${diffMin} menit lalu`;
  const diffHour = Math.floor(diffMin / 60);
  if (diffHour < 24) return `${diffHour} jam lalu`;
  const diffDay = Math.floor(diffHour / 24);
  if (diffDay < 7) return `${diffDay} hari lalu`;
  return date.toLocaleDateString("id-ID", { day: "numeric", month: "short" });
}

async function toggleOpen() {
  isOpen.value = !isOpen.value;
  if (isOpen.value) await loadNotifications();
}

const props = defineProps({
  detailRouteName: { type: String, default: null },
});

async function handleNotificationClick(n) {
  if (!n.isRead) {
    try {
      await notificationService.markAsRead(n.id);
      n.isRead = true;
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch {}
  }
  isOpen.value = false;
  if (n.reportId && props.detailRouteName) {
    router.push({ name: props.detailRouteName, params: { id: n.reportId } });
  }
}

async function handleMarkAllRead() {
  try {
    await notificationService.markAllAsRead();
    notifications.value.forEach((n) => (n.isRead = true));
    unreadCount.value = 0;
  } catch {
    // silent
  }
}

async function handleDeleteNotification(n, event) {
  event.stopPropagation(); // cegah trigger handleNotificationClick
  try {
    await notificationService.deleteNotification(n.id);
    notifications.value = notifications.value.filter(
      (item) => item.id !== n.id,
    );
    if (!n.isRead) unreadCount.value = Math.max(0, unreadCount.value - 1);
  } catch {
    toast?.error?.("Gagal menghapus notifikasi.");
  }
}

async function handleDeleteAll() {
  try {
    await notificationService.deleteAllNotifications();
    notifications.value = [];
    unreadCount.value = 0;
  } catch {
    // silent
  }
}

function closeOnOutsideClick(e) {
  if (!e.target.closest(".notif-bell-wrapper")) isOpen.value = false;
}

onMounted(() => {
  loadUnreadCount();
  pollTimer = setInterval(loadUnreadCount, 30000); // polling tiap 30 detik
  document.addEventListener("click", closeOnOutsideClick);
});

onBeforeUnmount(() => {
  clearInterval(pollTimer);
  document.removeEventListener("click", closeOnOutsideClick);
});

const hasUnread = computed(() => unreadCount.value > 0);
</script>

<template>
  <div class="notif-bell-wrapper relative">
    <button
      type="button"
      @click.stop="toggleOpen"
      title="Notifikasi"
      class="relative flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/80 text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
    >
      <Bell class="h-4 w-4" />
      <span
        v-if="hasUnread"
        class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white"
      >
        {{ unreadCount > 9 ? "9+" : unreadCount }}
      </span>
    </button>

    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 top-11 z-50 w-80 overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/50 sm:w-96"
      >
        <div
          class="flex items-center justify-between gap-3 border-b border-slate-800/70 px-4 py-3"
        >
          <p class="text-xs font-bold text-slate-100">Notifikasi</p>
          <div class="flex items-center gap-3">
            <button
              v-if="hasUnread"
              type="button"
              @click="handleMarkAllRead"
              class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-400 hover:text-emerald-300"
            >
              <Check class="h-3 w-3" />
              Tandai dibaca
            </button>
            <button
              v-if="notifications.length > 0"
              type="button"
              @click="handleDeleteAll"
              class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-400 hover:text-rose-300"
            >
              <Trash2 class="h-3 w-3" />
              Hapus semua
            </button>
          </div>
        </div>

        <div class="max-h-96 overflow-y-auto">
          <div v-if="isLoading" class="space-y-2 p-3">
            <div
              v-for="i in 3"
              :key="i"
              class="h-14 rounded-lg bg-slate-800/50 animate-pulse"
            ></div>
          </div>

          <p
            v-else-if="notifications.length === 0"
            class="px-4 py-10 text-center text-xs text-slate-500"
          >
            Belum ada notifikasi.
          </p>

          <div
            v-for="n in notifications"
            :key="n.id"
            class="group flex w-full items-start gap-3 border-b border-slate-800/50 px-4 py-3 text-left transition-colors duration-150 hover:bg-slate-800/40"
            :class="!n.isRead ? 'bg-emerald-500/3' : ''"
          >
            <button
              type="button"
              @click="handleNotificationClick(n)"
              class="flex flex-1 items-start gap-3 text-left"
            >
              <span
                class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full"
                :class="!n.isRead ? 'bg-emerald-400' : 'bg-transparent'"
                aria-hidden="true"
              ></span>
              <div class="min-w-0 flex-1">
                <p
                  class="text-xs font-semibold"
                  :class="!n.isRead ? 'text-slate-100' : 'text-slate-400'"
                >
                  {{ n.title }}
                </p>
                <p class="mt-0.5 text-[11px] leading-relaxed text-slate-500">
                  {{ n.message }}
                </p>
                <p class="mt-1 text-[10px] text-slate-600">{{ n.createdAt }}</p>
              </div>
            </button>

            <button
              type="button"
              @click="(e) => handleDeleteNotification(n, e)"
              title="Hapus notifikasi"
              class="shrink-0 rounded-md p-1 text-slate-600 opacity-0 transition-all duration-150 hover:bg-rose-500/10 hover:text-rose-400 group-hover:opacity-100"
            >
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>
