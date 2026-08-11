@php
  use Illuminate\Support\Facades\Route;
@endphp

<ul class="menu-sub">
  @if (isset($menu))
    @foreach ($menu as $submenu)
      @if (!menuItemVisible($submenu))
        @continue
      @endif

      {{-- active menu method --}}
      @php
        $activeClass = null;
        $active = 'active open';
        $currentRouteName = Route::currentRouteName();
        $routeMatchesSlug = function (?string $routeName, mixed $slug): bool {
            if (! is_string($slug) || $routeName === null) {
                return false;
            }

            return $routeName === $slug || str_starts_with($routeName, $slug . '.');
        };

        if ($routeMatchesSlug($currentRouteName, $submenu->slug)) {
            $activeClass = 'active';
        } elseif (isset($submenu->submenu)) {
            if (gettype($submenu->slug) === 'array') {
                foreach ($submenu->slug as $slug) {
                    if ($routeMatchesSlug($currentRouteName, $slug)) {
                        $activeClass = $active;
                    }
                }
            } else {
                if ($routeMatchesSlug($currentRouteName, $submenu->slug)) {
                    $activeClass = $active;
                }
            }
        }
      @endphp

      <li class="menu-item {{ $activeClass }}">
        <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}"
          class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
          @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
          @if (isset($submenu->icon))
            <i class="{{ $submenu->icon }}"></i>
          @endif
          <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
          @isset($submenu->badge)
            <div class="badge rounded-pill bg-{{ $submenu->badge[0] }} text-uppercase ms-auto">{{ $submenu->badge[1] }}
            </div>
          @endisset
        </a>

        {{-- submenu --}}
        @if (isset($submenu->submenu))
          @include('layouts.sections.menu.submenu', ['menu' => $submenu->submenu])
        @endif
      </li>
    @endforeach
  @endif
</ul>
