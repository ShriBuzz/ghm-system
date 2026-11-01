{{-- The offline slip (A6) prints alone: no menu, no buttons, no staff name. --}}
<style>
    @media print {
        .fi-topbar-ctn, .fi-sidebar, .fi-sidebar-close-overlay, .fi-breadcrumbs, .fi-header-actions-ctn { display: none !important; }
        .fi-main-ctn, .fi-main { margin: 0 !important; padding: 0 !important; max-width: none !important; }
    }
</style>
