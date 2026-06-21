@php
  use Illuminate\Support\Facades\Route;
  use Illuminate\Support\Facades\Auth;
@endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme d-flex flex-column">

  <div class="d-flex justify-content-end px-3 pt-3 pb-2 d-xl-none">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
      <i class="icon-base bx bx-chevron-left icon-sm d-flex align-items-center justify-content-center"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <div class="d-flex flex-column flex-grow-1 overflow-hidden">
    <ul class="menu-inner flex-grow-1 overflow-auto py-1 pt-xl-4">
      @foreach ($menuData[0]->menu as $menu)
        {{-- adding active and open class if child is active --}}

        {{-- menu headers --}}
        @if (isset($menu->menuHeader))
          <li class="menu-header small text-uppercase">
            <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
          </li>
        @else
          @if (!menuItemVisible($menu))
            @continue
          @endif

          {{-- active menu method --}}
          @php
            $activeClass = null;
            $currentRouteName = Route::currentRouteName();

            if ($currentRouteName === $menu->slug) {
                $activeClass = 'active';
            } elseif (is_string($menu->slug) && $currentRouteName && str_starts_with($currentRouteName, $menu->slug)) {
                $activeClass = 'active';
            } elseif (isset($menu->submenu)) {
                $slugs = gettype($menu->slug) === 'array' ? $menu->slug : [$menu->slug];
                foreach ($menu->submenu as $submenu) {
                    if (isset($submenu->slug)) {
                        $slugs[] = $submenu->slug;
                    }
                }
                foreach ($slugs as $slug) {
                    if (
                        $currentRouteName && str_contains($currentRouteName, $slug) and
                        strpos($currentRouteName, $slug) === 0
                    ) {
                        $activeClass = 'active open';
                    }
                }
            }
          @endphp

          {{-- main menu --}}
          <li class="menu-item {{ $activeClass }}">
            <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
              class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
              @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
              @isset($menu->icon)
                <i class="{{ $menu->icon }}"></i>
              @endisset
              <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
              @isset($menu->badge)
                <div class="badge rounded-pill bg-{{ $menu->badge[0] }} text-uppercase ms-auto">{{ $menu->badge[1] }}
                </div>
              @endisset
            </a>

            {{-- submenu --}}
            @isset($menu->submenu)
              @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
            @endisset
          </li>
        @endif
      @endforeach
    </ul>

    @auth
      @php
        $currentUser = Auth::user();
        $currentRoleName = $currentUser?->role?->ten_vai_tro ?: 'Quản trị hệ thống';
        $avatarInitial = mb_strtoupper(mb_substr($currentUser?->name ?: 'U', 0, 1));
      @endphp

      <div class="menu-user-panel px-3 pb-3 pt-2 border-top">
        <div class="dropdown dropup dropup-end w-100">
          <button type="button"
            class="btn btn-link text-decoration-none p-0 w-100 text-start dropdown-toggle hide-arrow menu-user-toggle"
            data-bs-toggle="dropdown" aria-expanded="false">
            <div class="d-flex align-items-center gap-3 rounded-3 px-2 py-2 menu-user-surface">
              <div
                class="menu-user-avatar avatar avatar-online bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0">
                <span>{{ $avatarInitial }}</span>
              </div>

              <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-truncate text-body">{{ $currentUser?->name }}</div>
                <div class="text-muted small text-truncate">{{ $currentRoleName }}</div>
              </div>

              <i class="icon-base bx bx-chevron-down text-muted ms-2 flex-shrink-0"></i>
            </div>
          </button>

          <ul class="dropdown-menu dropdown-menu-end shadow-lg user-panel-dropdown">
            @if (hasPermission('PROFILE_VIEW'))
              <li>
                <a class="dropdown-item" href="{{ route('profile.index') }}">
                  <i class="icon-base bx bx-user icon-md me-2"></i><span>Hồ sơ cá nhân</span>
                </a>
              </li>
            @endif

            @if (hasPermission('CHANGE_PASSWORD'))
              <li>
                <a class="dropdown-item" href="{{ route('profile.change-password') }}">
                  <i class="icon-base bx bx-lock-alt icon-md me-2"></i><span>Đổi mật khẩu</span>
                </a>
              </li>
            @endif

            <li>
              <div class="dropdown-divider my-1"></div>
            </li>

            <li>
              <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="dropdown-item" type="submit">
                  <i class="icon-base bx bx-power-off icon-md me-2"></i><span>Đăng xuất</span>
                </button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    @endauth
  </div>

</aside>
