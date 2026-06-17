 $latestUpdate = config('app.latest_update', []);

    $showLatestUpdate =
        is_array($latestUpdate) &&
        ($latestUpdate['enabled'] ?? false) &&
        filled($latestUpdate['id'] ?? null) &&
        filled($latestUpdate['title'] ?? null);

    $currentUser = auth()->user();

    $initials = collect(
        preg_split('/\s+/', trim($currentUser->name ?? 'User'))
    )
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $initials = $initials ?: 'U';

    $isSuperAdmin = method_exists($currentUser, 'isSuperAdmin')
        ? $currentUser->isSuperAdmin()
        : ($currentUser->role ?? null) === 'super_admin';

    $settingsRoute = match (true) {
        Route::has('admin.settings.index') => route('admin.settings.index'),
        Route::has('settings.index') => route('settings.index'),
        default => null,
    };

    $profileRoute = Route::has('profile.edit')
        ? route('profile.edit')
        : null;
@endphp

<nav
    x-data="{
        mobileOpen: false,
        accountOpen: false,
        updateVisible: false,
        updateStorageKey: @js(
            $showLatestUpdate
                ? 'dismissed_admin_update_' . $latestUpdate['id']
                : null
        ),

        init() {
            if (this.updateStorageKey) {
                this.updateVisible =
                    localStorage.getItem(this.updateStorageKey) !== '1';
            }

            this.$watch('mobileOpen', value => {
                document.body.classList.toggle(
                    'overflow-hidden',
                    value
                );
            });
        },

        closeMobile() {
            this.mobileOpen = false;
        },

        dismissUpdate() {
            if (this.updateStorageKey) {
                localStorage.setItem(
                    this.updateStorageKey,
                    '1'
                );
            }

            this.updateVisible = false;
        }
    }"
    @keydown.escape.window="
        mobileOpen = false;
        accountOpen = false;
    "
    class="relative z-50"
>
    {{-- Latest product update --}}
    @if($showLatestUpdate)
        <div
            x-show="updateVisible"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-2 opacity-0"
            class="border-b border-blue-500/20 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 text-white"
        >
            <div class="mx-auto flex max-w-7xl items-start gap-3 px-4 py-3 sm:items-center sm:px-6 lg:px-8">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/15 ring-1 ring-inset ring-white/20">
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 3v2m6.364.636-1.414 1.414M21 12h-2M5 12H3m4.05-4.95L5.636 5.636M8 17h8m-7 4h6m3-9a6 6 0 1 0-12 0c0 2.2 1.2 3.5 2.2 4.5.5.5.8 1.1.8 1.5h6c0-.4.3-1 .8-1.5C16.8 15.5 18 14.2 18 12Z"
                        />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="rounded-full bg-white/15 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-blue-50 ring-1 ring-inset ring-white/20">
                            New
                        </span>

                        <p class="text-sm font-semibold">
                            {{ $latestUpdate['title'] }}
                        </p>
                    </div>

                    @if(filled($latestUpdate['description'] ?? null))
                        <p class="mt-1 text-sm leading-5 text-blue-100 sm:mt-0 sm:inline">
                            {{ $latestUpdate['description'] }}
                        </p>
                    @endif
                </div>

                <div class="flex shrink-0 items-center gap-1">
                    @if(
                        filled($latestUpdate['url'] ?? null) &&
                        filled($latestUpdate['link_text'] ?? null)
                    )
                        <a
                            href="{{ $latestUpdate['url'] }}"
                            class="hidden rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/70 sm:inline-flex"
                        >
                            {{ $latestUpdate['link_text'] }}

                            <svg
                                class="ml-1.5 h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m9 18 6-6-6-6"
                                />
                            </svg>
                        </a>
                    @endif

                    <button
                        type="button"
                        @click="dismissUpdate()"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-100 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/70"
                        aria-label="Dismiss update"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            @if(
                filled($latestUpdate['url'] ?? null) &&
                filled($latestUpdate['link_text'] ?? null)
            )
                <div class="border-t border-white/10 px-4 py-2 sm:hidden">
                    <a
                        href="{{ $latestUpdate['url'] }}"
                        class="inline-flex items-center text-xs font-semibold text-white"
                    >
                        {{ $latestUpdate['link_text'] }}

                        <svg
                            class="ml-1.5 h-3.5 w-3.5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m9 18 6-6-6-6"
                            />
                        </svg>
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- Main navigation --}}
    <div class="border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">
                {{-- Brand --}}
                <div class="flex min-w-0 items-center gap-8">
                    <a
                        href="{{ route('dashboard') }}"
                        class="flex shrink-0 items-center gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white shadow-sm shadow-blue-600/20">
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"
                                />
                            </svg>
                        </div>

                        <div class="hidden min-w-0 sm:block">
                            <p class="truncate text-sm font-bold tracking-tight text-slate-900">
                                AI Sales Agent
                            </p>

                            <p class="truncate text-[11px] font-medium text-slate-500">
                                {{ $isSuperAdmin ? 'System administration' : 'Sales workspace' }}
                            </p>
                        </div>
                    </a>

                    {{-- Desktop links --}}
                    <div class="hidden items-center gap-1 lg:flex">
                        <a
                            href="{{ route('dashboard') }}"
                            class="group inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition
                                {{ request()->routeIs('dashboard')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                }}"
                        >
                            <svg
                                class="h-4 w-4 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 11.5 12 4l9 7.5M5 10v10h14V10M9 20v-6h6v6"
                                />
                            </svg>

                            Dashboard
                        </a>

                        <a
                            href="{{ route('admin.leads.index') }}"
                            class="group inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition
                                {{ request()->routeIs('admin.leads.*')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                }}"
                        >
                            <svg
                                class="h-4 w-4 {{ request()->routeIs('admin.leads.*') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                />
                                <circle cx="9" cy="7" r="4"/>
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19 8v6M22 11h-6"
                                />
                            </svg>

                            Leads
                        </a>

                        <a
                            href="{{ route('admin.conversations.index') }}"
                            class="group relative inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition
                                {{ request()->routeIs('admin.conversations.*')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                }}"
                        >
                            <svg
                                class="h-4 w-4 {{ request()->routeIs('admin.conversations.*') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"
                                />
                            </svg>

                            Conversations

                            @isset($waitingLiveChatCount)
                                @if($waitingLiveChatCount > 0)
                                    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                        {{ $waitingLiveChatCount > 99 ? '99+' : $waitingLiveChatCount }}
                                    </span>
                                @endif
                            @endisset
                        </a>

                        <a
                            href="{{ route('admin.websites.index') }}"
                            class="group inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition
                                {{ request()->routeIs('admin.websites.*')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                }}"
                        >
                            <svg
                                class="h-4 w-4 {{ request()->routeIs('admin.websites.*') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <circle cx="12" cy="12" r="9"/>
                                <path
                                    stroke-linecap="round"
                                    d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"
                                />
                            </svg>

                            Websites
                        </a>

                        @if($isSuperAdmin && Route::has('admin.tenants.index'))
                            <a
                                href="{{ route('admin.tenants.index') }}"
                                class="group inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition
                                    {{ request()->routeIs('admin.tenants.*')
                                        ? 'bg-violet-50 text-violet-700'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    }}"
                            >
                                <svg
                                    class="h-4 w-4 {{ request()->routeIs('admin.tenants.*') ? 'text-violet-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                                    <path
                                        stroke-linecap="round"
                                        d="M8 8h8M8 12h8M8 16h5"
                                    />
                                </svg>

                                Tenants
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Desktop account area --}}
                <div class="hidden items-center gap-3 lg:flex">
                    @if(Route::has('admin.websites.create'))
                        <a
                            href="{{ route('admin.websites.create') }}"
                            class="inline-flex h-9 items-center justify-center gap-2 rounded-xl bg-blue-600 px-3.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 5v14M5 12h14"
                                />
                            </svg>

                            Add Website
                        </a>
                    @endif

                    <div
                        class="relative"
                        @click.outside="accountOpen = false"
                    >
                        <button
                            type="button"
                            @click="accountOpen = !accountOpen"
                            :aria-expanded="accountOpen"
                            class="flex items-center gap-3 rounded-xl border border-transparent px-2 py-1.5 text-left transition hover:border-slate-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-800 to-slate-950 text-xs font-bold text-white shadow-sm">
                                {{ $initials }}
                            </div>

                            <div class="hidden max-w-40 xl:block">
                                <p class="truncate text-sm font-semibold text-slate-800">
                                    {{ $currentUser->name }}
                                </p>

                                <p class="truncate text-xs text-slate-500">
                                    {{ $isSuperAdmin ? 'System Administrator' : 'Tenant User' }}
                                </p>
                            </div>

                            <svg
                                class="h-4 w-4 text-slate-400 transition"
                                :class="{ 'rotate-180': accountOpen }"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m6 9 6 6 6-6"
                                />
                            </svg>
                        </button>

                        <div
                            x-show="accountOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="translate-y-1 scale-95 opacity-0"
                            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                            x-transition:leave-end="translate-y-1 scale-95 opacity-0"
                            class="absolute right-0 mt-2 w-72 origin-top-right overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10"
                        >
                            <div class="border-b border-slate-100 px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 text-sm font-bold text-white">
                                        {{ $initials }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">
                                            {{ $currentUser->name }}
                                        </p>

                                        <p class="truncate text-xs text-slate-500">
                                            {{ $currentUser->email }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-2">
                                @if($profileRoute)
                                    <a
                                        href="{{ $profileRoute }}"
                                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900"
                                    >
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition group-hover:bg-white group-hover:text-blue-600">
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true"
                                            >
                                                <circle cx="12" cy="8" r="4"/>
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M4 21a8 8 0 0 1 16 0"
                                                />
                                            </svg>
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block font-semibold">
                                                Edit profile
                                            </span>

                                            <span class="block text-xs font-normal text-slate-500">
                                                Personal and account details
                                            </span>
                                        </span>
                                    </a>
                                @endif

                                @if($settingsRoute)
                                    <a
                                        href="{{ $settingsRoute }}"
                                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900"
                                    >
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition group-hover:bg-white group-hover:text-blue-600">
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true"
                                            >
                                                <circle cx="12" cy="12" r="3"/>
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.08V21h-4v-.08A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.08-.4H3v-4h.08A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.08V3h4v.08A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.4.27.73.63 1 1 .2.34.34.71.4 1.08V11h.2v4h-.08A1.7 1.7 0 0 0 19.4 15Z"
                                                />
                                            </svg>
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block font-semibold">
                                                Settings
                                            </span>

                                            <span class="block text-xs font-normal text-slate-500">
                                                Workspace preferences
                                            </span>
                                        </span>
                                    </a>
                                @endif
                            </div>

                            <div class="border-t border-slate-100 p-2">
                                <form
                                    method="POST"
                                    action="{{ route('logout') }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50"
                                    >
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 transition group-hover:bg-white">
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M10 17l5-5-5-5M15 12H3M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"
                                                />
                                            </svg>
                                        </span>

                                        Log out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Mobile trigger --}}
                <button
                    type="button"
                    @click="mobileOpen = !mobileOpen"
                    :aria-expanded="mobileOpen"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 lg:hidden"
                    aria-label="Open navigation menu"
                >
                    <svg
                        x-show="!mobileOpen"
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 7h16M4 12h16M4 17h16"
                        />
                    </svg>

                    <svg
                        x-show="mobileOpen"
                        x-cloak
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile backdrop --}}
    <div
        x-show="mobileOpen"
        x-cloak
        x-transition.opacity
        @click="closeMobile()"
        class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm lg:hidden"
        aria-hidden="true"
    ></div>

    {{-- Mobile drawer --}}
    <aside
        x-show="mobileOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 flex w-[86%] max-w-sm flex-col bg-white shadow-2xl lg:hidden"
        aria-label="Mobile navigation"
    >
        <div class="flex h-16 items-center justify-between border-b border-slate-200 px-4">
            <a
                href="{{ route('dashboard') }}"
                class="flex items-center gap-3"
            >
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white">
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"
                        />
                    </svg>
                </div>

                <div>
                    <p class="text-sm font-bold text-slate-900">
                        AI Sales Agent
                    </p>

                    <p class="text-[11px] text-slate-500">
                        {{ $isSuperAdmin ? 'System administration' : 'Sales workspace' }}
                    </p>
                </div>
            </a>

            <button
                type="button"
                @click="closeMobile()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close navigation menu"
            >
                <svg
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18 18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-3 py-4">
            <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                Workspace
            </p>

            <div class="space-y-1">
                <a
                    href="{{ route('dashboard') }}"
                    @click="closeMobile()"
                    class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold
                        {{ request()->routeIs('dashboard')
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-slate-700 hover:bg-slate-100'
                        }}"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 11.5 12 4l9 7.5M5 10v10h14V10M9 20v-6h6v6"
                        />
                    </svg>

                    Dashboard
                </a>

                <a
                    href="{{ route('admin.leads.index') }}"
                    @click="closeMobile()"
                    class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold
                        {{ request()->routeIs('admin.leads.*')
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-slate-700 hover:bg-slate-100'
                        }}"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                        />
                        <circle cx="9" cy="7" r="4"/>
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M19 8v6M22 11h-6"
                        />
                    </svg>

                    Leads
                </a>

                <a
                    href="{{ route('admin.conversations.index') }}"
                    @click="closeMobile()"
                    class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold
                        {{ request()->routeIs('admin.conversations.*')
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-slate-700 hover:bg-slate-100'
                        }}"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"
                        />
                    </svg>

                    <span class="flex-1">
                        Conversations
                    </span>

                    @isset($waitingLiveChatCount)
                        @if($waitingLiveChatCount > 0)
                            <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                {{ $waitingLiveChatCount > 99 ? '99+' : $waitingLiveChatCount }}
                            </span>
                        @endif
                    @endisset
                </a>

                <a
                    href="{{ route('admin.websites.index') }}"
                    @click="closeMobile()"
                    class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold
                        {{ request()->routeIs('admin.websites.*')
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-slate-700 hover:bg-slate-100'
                        }}"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="9"/>
                        <path
                            stroke-linecap="round"
                            d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"
                        />
                    </svg>

                    Websites
                </a>

                @if($isSuperAdmin && Route::has('admin.tenants.index'))
                    <a
                        href="{{ route('admin.tenants.index') }}"
                        @click="closeMobile()"
                        class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold
                            {{ request()->routeIs('admin.tenants.*')
                                ? 'bg-violet-50 text-violet-700'
                                : 'text-slate-700 hover:bg-slate-100'
                            }}"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <path
                                stroke-linecap="round"
                                d="M8 8h8M8 12h8M8 16h5"
                            />
                        </svg>

                        Tenants
                    </a>
                @endif
            </div>

            <div class="my-5 border-t border-slate-200"></div>

            <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                Account
            </p>

            <div class="space-y-1">
                @if($profileRoute)
                    <a
                        href="{{ $profileRoute }}"
                        @click="closeMobile()"
                        class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        <svg
                            class="h-5 w-5 text-slate-500"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="8" r="4"/>
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 21a8 8 0 0 1 16 0"
                            />
                        </svg>

                        Edit profile
                    </a>
                @endif

                @if($settingsRoute)
                    <a
                        href="{{ $settingsRoute }}"
                        @click="closeMobile()"
                        class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        <svg
                            class="h-5 w-5 text-slate-500"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="12" r="3"/>
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19 12a7 7 0 1 1-14 0 7 7 0 0 1 14 0ZM12 3v2M12 19v2M3 12h2M19 12h2"
                            />
                        </svg>

                        Settings
                    </a>
                @endif
            </div>
        </div>

        {{-- Mobile account footer --}}
        <div class="border-t border-slate-200 bg-slate-50 px-4 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-800 to-slate-950 text-sm font-bold text-white">
                    {{ $initials }}
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-900">
                        {{ $currentUser->name }}
                    </p>

                    <p class="truncate text-xs text-slate-500">
                        {{ $currentUser->email }}
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-red-500 transition hover:bg-red-50 hover:text-red-600"
                        aria-label="Log out"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M10 17l5-5-5-5M15 12H3M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"
                            />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>
</nav