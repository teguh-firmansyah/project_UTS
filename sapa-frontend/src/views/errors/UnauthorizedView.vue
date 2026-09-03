<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
const authStore = useAuthStore()

/* Status autentikasi menentukan pesan & aksi yang ditawarkan */
const isAuthenticated = computed(() => !!authStore?.user)

/* Penjelasan kontekstual sesuai kondisi sesi */
const explanation = computed(() =>
  isAuthenticated.value
    ? 'Sesi login Anda aktif, namun akun ini tidak memiliki izin untuk mengakses halaman tersebut. Halaman ini kemungkinan ditujukan khusus untuk petugas sekolah.'
    : 'Halaman ini memerlukan sesi login yang aktif. Silakan masuk terlebih dahulu untuk melanjutkan.'
)
</script>

<template>
  <div class="sapa-root relative flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- Cahaya ambien emerald yang sangat halus -->
    <div class="pointer-events-none absolute inset-0" aria-hidden="true"
         style="background: radial-gradient(760px 420px at 50% 0%, rgba(16, 185, 129, 0.07), transparent 65%)"></div>

    <!-- ============ Konten ============ -->
    <main class="relative z-10 flex flex-1 items-center justify-center px-4 py-12 sm:px-6">
      <div class="fade-up relative w-full max-w-lg overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/40">

        <!-- Garis aksen rose di puncak kartu: akses ditolak -->
        <span class="absolute inset-x-0 top-0 z-20 h-[2px] bg-gradient-to-r from-rose-500/70 via-rose-500/20 to-transparent" aria-hidden="true"></span>

        <div class="p-6 sm:p-8">

          <!-- Ikon gembok + angka besar + identitas error -->
          <div class="soft-in text-center" style="animation-delay: .1s" role="alert">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-rose-500/25 bg-rose-500/10 text-rose-400">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>

            <p class="mt-5 text-7xl font-extrabold leading-none tracking-tighter tabular-nums text-slate-100 sm:text-8xl">
              4<span class="text-rose-500">0</span>3
            </p>

            <div class="mt-5 flex justify-center">
              <span class="inline-flex items-center rounded border border-rose-500/20 bg-rose-500/10 px-2 py-0.5 font-mono text-[11px] font-medium text-rose-400">
                ERR-403
              </span>
            </div>

            <h1 class="mt-4 text-xl font-bold tracking-tight text-white sm:text-2xl">Akses Tidak Diizinkan</h1>
            <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-slate-400">{{ explanation }}</p>
          </div>

          <!-- Aksi keluar — menyesuaikan status sesi -->
          <div class="soft-in mt-6" style="animation-delay: .3s">
            <div class="flex flex-col gap-2.5 sm:flex-row">
              <!-- Terotentikasi: pulang ke dashboard -->
              <router-link
                v-if="isAuthenticated"
                to="/"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Kembali ke Dashboard
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </main>
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
/* Entrance kartu: fade-up halus */
.fade-up {
  opacity: 0;
  animation: fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Blok konten menyusul kartu secara berurutan */
.soft-in {
  opacity: 0;
  animation: soft-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes soft-in {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  .fade-up,
  .soft-in { animation: none; opacity: 1; }
}
</style>