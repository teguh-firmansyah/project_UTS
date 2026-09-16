<template>
  <div class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25">

    <!-- ============ Bar atas ============ -->
    <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3 md:h-16 md:py-0">

          <!-- Merek -->
          <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20">
              <img v-if="!logoFailed" src="@/assets/logo/logo sapa.jpeg" alt="Logo SAPA" class="h-full w-full object-cover" @error="logoFailed = true" />
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
              <component :is="item.icon" class="h-3.5 w-3.5" />
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
            <LogOut class="h-5 w-5" />
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
            <Plus class="h-4 w-4 transition-transform duration-300 group-hover:rotate-90" />
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
                <component :is="s.icon" class="h-5 w-5" />
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
              <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
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
                <X class="h-3.5 w-3.5" />
              </button>
            </div>

            <!-- Filter peran -->
            <div class="relative w-full sm:w-56">
              <Users class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
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
              <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
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
                <X class="h-3 w-3" />
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
            <span class="absolute bottom-3 left-0 top-3 w-[3px] rounded-r-full bg-emerald-500/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100" aria-hidden="true"></span>

            <div class="flex min-w-0 items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold transition-colors duration-150" :class="user.avatarClass">
                {{ getInitials(user.name) }}
              </div>
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white">{{ user.name }}</p>
                <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ user.email }}</p>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents">
              <div class="min-w-0">
                <p class="truncate font-mono text-xs text-slate-400">{{ user.nip_nisn || '—' }}</p>
              </div>

              <div>
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider" :class="user.roleBadge">
                  <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                  {{ user.role }}
                </span>
              </div>

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
                <Pencil class="h-3.5 w-3.5" />
              </button>

              <button
                type="button"
                @click="openResetPasswordModal(user)"
                title="Reset kata sandi"
                aria-label="Reset kata sandi"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 hover:border-amber-500/30 hover:bg-amber-500/10 hover:text-amber-400 active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
              >
                <KeyRound class="h-3.5 w-3.5" />
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
                <Ban v-if="user.isActive" class="h-3.5 w-3.5" />
                <CheckCircle2 v-else class="h-3.5 w-3.5" />
              </button>

              <button
                type="button"
                @click="openDeleteModal(user)"
                title="Hapus pengguna"
                aria-label="Hapus pengguna"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60"
              >
                <Trash2 class="h-3.5 w-3.5" />
              </button>
            </div>
          </article>
        </div>

        <!-- Keadaan kosong -->
        <div v-else class="flex flex-col items-center px-6 py-16 text-center">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
            <Search v-if="hasActiveFilters" class="h-6 w-6" />
            <UserX v-else class="h-6 w-6" />
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
            <Plus class="h-4 w-4" />
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
              <ChevronLeft class="h-3.5 w-3.5 transition-transform duration-150 group-hover:-translate-x-0.5" />
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
              <ChevronRight class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5" />
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
          <Lock class="h-3.5 w-3.5 text-emerald-500/70" />
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
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Badan modal -->
        <form @submit.prevent="handleSaveUser" class="modal-scroll min-h-0 flex-1 space-y-5 overflow-y-auto p-5">

          <!-- Nama -->
          <div>
            <label for="uf-name" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Nama Lengkap</label>
            <div class="relative">
              <User class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
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
              <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
              {{ userErrors.name }}
            </p>
          </div>

          <!-- Email -->
          <div>
            <label for="uf-email" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Alamat Email</label>
            <div class="relative">
              <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
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
              <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
              {{ userErrors.email }}
            </p>
          </div>

          <!-- NIP / NISN -->
          <div>
            <label for="uf-nip" class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">NIP / NISN</label>
            <div class="relative">
              <IdCard class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
              <input
                id="uf-nip"
                v-model="userForm.nip_nisn"
                type="text"
                autocomplete="off"
                placeholder="Masukkan NIP atau NISN"
                :aria-invalid="!!userErrors.nip_nisn || undefined"
                class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3 font-mono text-sm text-slate-100 placeholder-slate-600 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                :class="userErrors.nip_nisn ? 'border-red-400/60' : 'border-slate-800'"
              />
            </div>
            <p v-if="userErrors.nip_nisn" class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400">
              <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
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
                <Check v-if="userForm.role === opt.value" class="h-3.5 w-3.5 shrink-0 text-emerald-400" />
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
            <Lock class="mt-px h-3 w-3 shrink-0 text-emerald-500/70" />
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
              <Check class="h-4 w-4" />
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
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
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
            <Lock class="mt-px h-3 w-3 shrink-0 text-amber-400/70" />
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
              <KeyRound class="h-4 w-4" />
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
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
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

          <div v-if="selectedUser?.role === 'Admin'" class="flex items-start gap-2.5 rounded-lg border border-rose-500/25 bg-rose-500/[0.06] px-3.5 py-3">
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-rose-400/90" />
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
              <Trash2 class="h-4 w-4" />
              Hapus Akun
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { 
  Users, 
  UserCheck, 
  GraduationCap, 
  ShieldCheck, 
  LogOut, 
  Plus, 
  Search, 
  X, 
  ChevronDown, 
  Pencil, 
  KeyRound, 
  Ban, 
  CheckCircle2, 
  Trash2, 
  UserX, 
  ChevronLeft, 
  ChevronRight, 
  Lock, 
  User, 
  Mail, 
  IdCard, 
  Check, 
  AlertTriangle,
  BarChart3,
  FileText,
  Settings
} from 'lucide-vue-next'

const route = useRoute()

// State Logo & Form
const logoFailed = ref(false)
const currentYear = new Date().getFullYear()

// Navigasi Bar
const navItems = [
  { label: 'Analitik', to: '/admin/dashboard', icon: BarChart3 },
  { label: 'Semua Laporan', to: '/admin/reports', icon: FileText },
  { label: 'Manajemen User', to: '/admin/users', icon: Users },
  { label: 'Pengaturan', to: '/admin/settings', icon: Settings },
]

const isActive = (item) => route?.path === item.to

// Statistik Cards Data
const statCards = ref([
  { label: 'Total Pengguna', value: '124', num: 'text-slate-100', tile: 'border-slate-700 bg-slate-800 text-emerald-400', bar: 'bg-emerald-500', pct: 100, caption: 'Semua akun terdaftar', icon: Users },
  { label: 'Siswa', value: '86', num: 'text-emerald-400', tile: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400', bar: 'bg-emerald-500', pct: 69, caption: '69% dari total akun', icon: GraduationCap },
  { label: 'Pengguna Aktif', value: '110', num: 'text-blue-400', tile: 'border-blue-500/30 bg-blue-500/10 text-blue-400', bar: 'bg-blue-500', pct: 88, caption: 'Status aktif saat ini', icon: UserCheck },
  { label: 'Admin & Staf', value: '12', num: 'text-purple-400', tile: 'border-purple-500/30 bg-purple-500/10 text-purple-400', bar: 'bg-purple-500', pct: 10, caption: 'Pengelola hak akses', icon: ShieldCheck }
])

// Filter & Table States
const searchQuery = ref('')
const selectedRole = ref('all')
const selectedStatus = ref('all')
const currentPage = ref(1)
const itemsPerPage = ref(10)

const statusPills = [
  { label: 'Semua', value: 'all', active: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' },
  { label: 'Aktif', value: 'Aktif', active: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' },
  { label: 'Nonaktif', value: 'Nonaktif', active: 'border-slate-600 bg-slate-800 text-slate-200' }
]

const pillIdle = 'border-slate-800 bg-slate-950/40 text-slate-400 hover:border-slate-700'

// Dummy Users Data
const users = ref([
  { id: 1, name: 'Ahmad Dahlan', email: 'ahmad@sekolah.sch.id', nip_nisn: '1029384756', role: 'Siswa', status: 'Aktif', isActive: true, last_login: '10 Min yang lalu' },
  { id: 2, name: 'Siti Nurhaliza, M.Pd.', email: 'siti.bk@sekolah.sch.id', nip_nisn: '198503152010012003', role: 'Guru BK', status: 'Aktif', isActive: true, last_login: '1 Jam yang lalu' },
  { id: 3, name: 'Budi Santoso', email: 'budi.admin@sekolah.sch.id', nip_nisn: '197805202005011002', role: 'Admin', status: 'Aktif', isActive: true, last_login: 'Sekarang' },
  { id: 4, name: 'Eko Prasetyo', email: 'eko.sarpras@sekolah.sch.id', nip_nisn: '198211102008041001', role: 'Guru', status: 'Nonaktif', isActive: false, last_login: '3 Hari yang lalu' }
])

const totalUsers = computed(() => users.value.length)

const statusCounts = computed(() => {
  return users.value.reduce((acc, u) => {
    acc.all = (acc.all || 0) + 1
    acc[u.status] = (acc[u.status] || 0) + 1
    return acc
  }, { all: 0 })
})

const hasActiveFilters = computed(() => searchQuery.value !== '' || selectedRole.value !== 'all' || selectedStatus.value !== 'all')

const filteredUsers = computed(() => {
  return users.value.filter(u => {
    const matchesSearch = u.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          u.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          u.nip_nisn.includes(searchQuery.value)
    const matchesRole = selectedRole.value === 'all' || u.role === selectedRole.value
    const matchesStatus = selectedStatus.value === 'all' || u.status === selectedStatus.value
    return matchesSearch && matchesRole && matchesStatus
  })
})

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / itemsPerPage.value) || 1)

const tableRows = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredUsers.value.slice(start, start + itemsPerPage.value).map(u => ({
    ...u,
    avatarClass: getRoleAvatarClass(u.role),
    roleBadge: getRoleBadgeClass(u.role)
  }))
})

// Modals State
const showUserModal = ref(false)
const showResetModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const selectedUser = ref(null)

const userForm = ref({ name: '', email: '', nip_nisn: '', role: 'Siswa', status: 'Aktif' })
const userErrors = ref({})

const roleOptions = [
  { value: 'Siswa', label: 'Siswa', desc: 'Akses terbatas untuk aspirasi', active: 'border-blue-500/50 bg-blue-500/10', dot: 'bg-blue-400' },
  { value: 'Guru', label: 'Guru (Sarpras)', desc: 'Penanganan laporan fasilitas', active: 'border-emerald-500/50 bg-emerald-500/10', dot: 'bg-emerald-400' },
  { value: 'Guru BK', label: 'Guru BK', desc: 'Akses laporan bimbingan & perundungan', active: 'border-amber-500/50 bg-amber-500/10', dot: 'bg-amber-400' },
  { value: 'Admin', label: 'Admin', desc: 'Akses penuh ke seluruh sistem', active: 'border-purple-500/50 bg-purple-500/10', dot: 'bg-purple-400' },
]

// Helpers
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : ''

const getRoleAvatarClass = (role) => {
  switch (role) {
    case 'Admin': return 'border-purple-500/40 bg-purple-500/10 text-purple-300'
    case 'Guru BK': return 'border-amber-500/40 bg-amber-500/10 text-amber-300'
    case 'Guru': return 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300'
    default: return 'border-blue-500/40 bg-blue-500/10 text-blue-300'
  }
}

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'Admin': return 'border-purple-500/30 bg-purple-500/10 text-purple-400'
    case 'Guru BK': return 'border-amber-500/30 bg-amber-500/10 text-amber-400'
    case 'Guru': return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
    default: return 'border-blue-500/30 bg-blue-500/10 text-blue-400'
  }
}

// Handlers
const clearFilters = () => {
  searchQuery.value = ''
  selectedRole.value = 'all'
  selectedStatus.value = 'all'
}

const prevPage = () => { if (currentPage.value > 1) currentPage.value-- }
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++ }

const openCreateModal = () => {
  isEditing.value = false
  userForm.value = { name: '', email: '', nip_nisn: '', role: 'Siswa', status: 'Aktif' }
  userErrors.value = {}
  showUserModal.value = true
}

const openEditModal = (user) => {
  isEditing.value = true
  selectedUser.value = user
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

const toggleUserStatus = (user) => {
  user.isActive = !user.isActive
  user.status = user.isActive ? 'Aktif' : 'Nonaktif'
}

const handleSaveUser = () => {
  userErrors.value = {}
  if (!userForm.value.name) userErrors.value.name = 'Nama wajib diisi'
  if (!userForm.value.email) userErrors.value.email = 'Email wajib diisi'

  if (Object.keys(userErrors.value).length === 0) {
    if (isEditing.value) {
      const idx = users.value.findIndex(u => u.id === selectedUser.value.id)
      if (idx !== -1) users.value[idx] = { ...users.value[idx], ...userForm.value, isActive: userForm.value.status === 'Aktif' }
    } else {
      users.value.push({
        id: Date.now(),
        ...userForm.value,
        isActive: userForm.value.status === 'Aktif',
        last_login: 'Belum pernah'
      })
    }
    showUserModal.value = false
  }
}

const handleConfirmReset = () => {
  showResetModal.value = false
}

const handleDeleteUser = () => {
  users.value = users.value.filter(u => u.id !== selectedUser.value.id)
  showDeleteModal.value = false
}

const handleLogout = () => {
  // Tambahkan logika logout sesuai kebutuhan
}
</script>
<style scoped>
/* ==========================================================================
   Animasi & Performa
   ========================================================================== */

/* Keyframes Muncul Modal */
@keyframes modalIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Keyframes Fade Background Backdrop */
@keyframes backdropIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Keyframes Entri Elemen Halaman */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Keyframes Baris Tabel */
@keyframes cardEnter {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Utilitas Kelas Animasi */
.backdrop-in {
  animation: backdropIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.modal-panel {
  animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  will-change: transform, opacity;
}

.fade-up {
  animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  will-change: transform, opacity;
}

.card-enter {
  animation: cardEnter 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  will-change: transform, opacity;
}

/* Transisi Bar Statistik */
.stat-bar {
  transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

/* ==========================================================================
   Kustomisasi Scrollbar
   ========================================================================== */

/* Sembunyikan Scrollbar Navigasi Horizontal (Mobile) */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Scrollbar Halus untuk Modal */
.modal-scroll::-webkit-scrollbar {
  width: 5px;
}
.modal-scroll::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.6);
}
.modal-scroll::-webkit-scrollbar-thumb {
  background: rgba(51, 65, 85, 0.8);
  border-radius: 9999px;
}
.modal-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(16, 185, 129, 0.5);
}
</style>