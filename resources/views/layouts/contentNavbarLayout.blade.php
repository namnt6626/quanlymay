@isset($pageConfigs)
  {!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@extends('layouts/commonMaster')

@php
  /* Display elements */
  $contentNavbar = $contentNavbar ?? true;
  $containerNav = $containerNav ?? 'container-xxl';
  $isNavbar = $isNavbar ?? true;
  $isMenu = $isMenu ?? true;
  $isFlex = $isFlex ?? false;
  $isFooter = $isFooter ?? true;
  $customizerHidden = $customizerHidden ?? '';

  /* HTML Classes */
  $navbarDetached = 'navbar-detached';
  $menuFixed = isset($configData['menuFixed']) ? $configData['menuFixed'] : '';
  if (isset($navbarType)) {
      $configData['navbarType'] = $navbarType;
  }
  $navbarType = isset($configData['navbarType']) ? $configData['navbarType'] : '';
  $footerFixed = isset($configData['footerFixed']) ? $configData['footerFixed'] : '';
  $menuCollapsed = isset($configData['menuCollapsed']) ? $configData['menuCollapsed'] : '';

  /* Content classes */
  $container = $container ?? 'container-xxl';

@endphp

@section('layoutContent')
  <div class="layout-wrapper layout-content-navbar {{ $isMenu ? '' : 'layout-without-menu' }}">
    <div class="layout-container">

      @if ($isMenu)
        @include('layouts/sections/menu/verticalMenu')
      @endif


      <!-- Layout page -->
      <div class="layout-page d-flex flex-column">

        @if ($isMenu)
          <button type="button"
            class="btn btn-primary btn-icon rounded-circle shadow layout-menu-toggle d-xl-none position-fixed top-0 start-0 m-3"
            style="z-index: 1080;" aria-label="Mở menu">
            <i class="icon-base bx bx-menu icon-md"></i>
          </button>
        @endif

        {{-- Below commented code read by artisan command while installing jetstream. !! Do not remove if you want to use jetstream. --}}
        {{-- <x-banner /> --}}


        <!-- Content wrapper -->
        <div class="content-wrapper flex-grow-1 d-flex flex-column">

          <!-- Content -->
          @if ($isFlex)
            <div class="{{ $container }} d-flex flex-column align-items-stretch flex-grow-1 p-0">
            @else
              <div class="{{ $container }} flex-grow-1 container-p-y">
          @endif

          @include('components.ui.breadcrumb')
          @yield('content')

        </div>
        <!-- / Content -->

        <div class="content-backdrop fade"></div>
      </div>
      <!--/ Content wrapper -->
    </div>
    <!-- / Layout page -->
  </div>

  @if ($isMenu)
    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>
  @endif
  <!-- Drag Target Area To SlideIn Menu On Small Screens -->
  <div class="drag-target"></div>
  <!-- / Layout wrapper -->
  </div>
@endsection
