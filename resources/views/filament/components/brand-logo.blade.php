@php
    $isLogin = request()->routeIs('filament.*.auth.*') || request()->is('*/login') || request()->is('login');
@endphp

<div class="brand-logo-container {{ $isLogin ? 'is-login' : 'is-sidebar' }}" 
     style="{{ $isLogin 
        ? 'display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; width: 100%; margin: 0 auto;' 
        : 'display: inline-flex; flex-direction: row; align-items: center; justify-content: flex-start; gap: 10px; height: 100%; max-height: 38px;' }}">
    <img src="{{ asset('images/logo.png') }}" 
         alt="Mitra Abadi Nusantara Logo" 
         class="brand-logo-img" 
         style="{{ $isLogin 
            ? 'height: 54px; width: auto; object-fit: contain; margin: 0 auto 6px auto; display: block;' 
            : 'height: 32px; width: auto; object-fit: contain; display: block; flex-shrink: 0; margin: 0;' }}" />
    <span class="brand-logo-text" 
          style="{{ $isLogin 
            ? 'font-weight: 700; font-size: 1.2rem; letter-spacing: 0.025em; text-align: center; color: #0f172a; display: block; line-height: 1.2; width: 100%;' 
            : 'font-weight: 700; font-size: 0.95rem; letter-spacing: 0.015em; color: #0f172a; white-space: nowrap; line-height: 1.2; display: inline-block;' }}">
        Mitra Abadi Nusantara
    </span>
</div>

<style>
/* Reset .fi-logo container height so it doesn't clip */
.fi-logo {
    height: auto !important;
}

/* Ensure login page centers the brand logo */
.fi-simple-header .fi-logo,
.fi-simple-layout .fi-logo {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    width: 100% !important;
    margin-left: auto !important;
    margin-right: auto !important;
    text-align: center !important;
}

.fi-simple-header .brand-logo-container,
.fi-simple-layout .brand-logo-container {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    width: 100% !important;
    margin: 0 auto !important;
}

.fi-simple-header .brand-logo-img,
.fi-simple-layout .brand-logo-img {
    height: 54px !important;
    max-height: 54px !important;
    width: auto !important;
    margin: 0 auto 6px auto !important;
    display: block !important;
}

.fi-simple-header .brand-logo-text,
.fi-simple-layout .brand-logo-text {
    font-size: 1.2rem !important;
    font-weight: 700 !important;
    text-align: center !important;
    display: block !important;
    width: 100% !important;
}

/* Sidebar header layout: Row side-by-side without overflowing into borders */
.fi-sidebar-header .fi-logo,
.fi-sidebar-header-logo-ctn .fi-logo {
    display: inline-flex !important;
    align-items: center !important;
    height: 100% !important;
}

.fi-sidebar-header .brand-logo-container,
.fi-sidebar-header-logo-ctn .brand-logo-container {
    display: inline-flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 10px !important;
    height: auto !important;
    max-height: 38px !important;
}

.fi-sidebar-header .brand-logo-img,
.fi-sidebar-header-logo-ctn .brand-logo-img {
    height: 32px !important;
    max-height: 32px !important;
    width: auto !important;
    display: block !important;
    flex-shrink: 0 !important;
    margin: 0 !important;
}

.fi-sidebar-header .brand-logo-text,
.fi-sidebar-header-logo-ctn .brand-logo-text {
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    white-space: nowrap !important;
    line-height: 1.2 !important;
    display: inline-block !important;
    text-align: left !important;
    width: auto !important;
}

/* Dark mode styling */
.dark .brand-logo-text {
    color: #f8fafc !important;
}
</style>
