{{-- The QR poster: its own layout on screen, and alone on an A4 page when printed. --}}
<style>
    .ghm-poster { display: grid; justify-items: center; gap: 0.5rem; padding: 2rem 1rem; text-align: center; color: #1b2428; background: #fff; border-radius: 0.75rem; }
    .ghm-poster p { margin: 0; font-size: 1.125rem; }
    .ghm-poster .ghm-poster-title { font-size: 2rem; font-weight: 700; line-height: 1.3; }
    .ghm-poster-code { width: min(100%, 22rem); height: auto; margin: 1rem 0; image-rendering: pixelated; }
    .ghm-poster .ghm-poster-url { margin-top: 0.75rem; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 1.25rem; overflow-wrap: anywhere; }
    @media print {
        @page { size: A4 portrait; margin: 15mm; }
        .fi-topbar-ctn, .fi-sidebar, .fi-sidebar-close-overlay, .fi-breadcrumbs, .fi-header, .fi-header-actions-ctn { display: none !important; }
        .fi-main-ctn, .fi-main { margin: 0 !important; padding: 0 !important; max-width: none !important; }
        html, body { background: #fff !important; }
        .ghm-poster { padding: 0; }
        .ghm-poster .ghm-poster-title { font-size: 30pt; }
        .ghm-poster p { font-size: 16pt; }
        .ghm-poster-code { width: 12cm; }
    }
</style>
