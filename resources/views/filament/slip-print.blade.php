{{-- The offline slip prints alone: no menu, no buttons, no staff name. --}}
<style>
    @media print {
        .fi-topbar-ctn, .fi-sidebar, .fi-sidebar-close-overlay, .fi-breadcrumbs, .fi-header-actions-ctn { display: none !important; }
        .fi-main-ctn, .fi-main { margin: 0 !important; padding: 0 !important; max-width: none !important; }
        /* Dark ink on white paper, also from a dark staff panel: the reference and PIN must be copied exactly. */
        html, body { background: #fff !important; }
        .fi-main, .fi-main * { color: #1b2428 !important; background-color: transparent !important; }
    }
</style>
