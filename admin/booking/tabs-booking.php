<?php
if (!defined('ABSPATH')) exit;
?>
<style>
  .bsc-booking-builder-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 1.5rem;
    align-items: start;
  }
  .bsc-seat-matrix-card {
    background: #060e12;
    border-radius: var(--cinema-radius-md);
    padding: 1.75rem 2rem;
    color: #fff;
    border: 1px solid #172a33;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.15);
  }
  .bsc-seat-matrix-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    padding: 1.5rem 0;
    overflow-x: auto;
  }
  .bsc-seat-column {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    align-items: center;
  }
  .bsc-col-header {
    font-size: 0.72rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.35rem;
  }
  .bsc-seat-box {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    border: 2px solid transparent;
    font-weight: 800;
    font-size: 0.8rem;
    font-family: var(--cinema-font-mono);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
    outline: none;
  }
  .bsc-seat-box.vip {
    background: #3b1704;
    border-color: #b45309;
    color: #fde68a;
  }
  .bsc-seat-box.standard {
    background: #09231f;
    border-color: #0d9488;
    color: #99f6e4;
  }
  .bsc-seat-box:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 14px rgba(0,0,0,0.4);
  }
  .bsc-seat-box.selected {
    background: #f59e0b !important;
    border-color: #fde68a !important;
    color: #000000 !important;
    box-shadow: 0 0 16px rgba(245, 158, 11, 0.7);
    transform: scale(1.08);
  }
  .bsc-seat-box:disabled, .bsc-seat-box.occupied {
    background: #1e1215 !important;
    border-color: #ef4444 !important;
    color: #fca5a5 !important;
    cursor: not-allowed;
    opacity: 0.45;
    transform: none !important;
    box-shadow: none !important;
  }
  .bsc-screen-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding-left: 1.25rem;
    border-left: 2px dashed rgba(255, 255, 255, 0.12);
    color: #0d9488;
    font-weight: 900;
    font-size: 0.75rem;
    letter-spacing: 3px;
  }
  .bsc-seat-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    justify-content: center;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 0.8rem;
  }
  .bsc-legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #cbd5e1;
    font-weight: 600;
  }
  .bsc-legend-dot {
    width: 14px;
    height: 14px;
    border-radius: 4px;
  }
  .bsc-summary-sidebar {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: var(--cinema-radius-md);
    padding: 1.6rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
  }
  .bsc-selected-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    min-height: 44px;
    padding: 0.65rem;
    background: #f8fafc;
    border: 1px solid var(--cinema-border);
    border-radius: 8px;
    margin-bottom: 1.25rem;
    align-items: center;
  }
  .bsc-seat-chip {
    background: #0d9488;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.2rem 0.55rem;
    border-radius: 4px;
    font-family: var(--cinema-font-mono);
  }
  .bsc-bill-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    margin-bottom: 1.5rem;
  }
  .bsc-bill-table td {
    padding: 0.55rem 0;
    border-bottom: 1px dashed var(--cinema-border-light);
  }
  .bsc-bill-table tr.grand-row td {
    border-bottom: none;
    padding-top: 0.85rem;
    font-size: 1.05rem;
    font-weight: 900;
  }
  .bsc-payment-grid-two {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-top: 0.35rem;
  }
  .bsc-payment-option { position: relative; }
  .bsc-payment-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
  .bsc-payment-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.85rem 0.5rem;
    border: 1.5px solid var(--cinema-border);
    border-radius: var(--cinema-radius-sm);
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
  }
  .bsc-payment-card .icon svg { width: 20px; height: 20px; fill: currentColor; margin-bottom: 0.2rem; }
  .bsc-payment-card .title { font-size: 0.88rem; font-weight: 800; color: var(--cinema-text-main); }
  .bsc-payment-card .sub { font-size: 0.7rem; color: var(--cinema-text-muted); }
  .bsc-payment-option input[type="radio"]:checked + .bsc-payment-card {
    border-color: #0d9488;
    background: #f0fdfa;
    box-shadow: 0 0 0 1px #0d9488;
  }
  .bsc-payment-option input[type="radio"]:checked + .bsc-payment-card .title { color: #0d9488; }
  .bsc-table-actions { display: inline-flex; align-items: center; gap: 6px; }
  .bsc-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    line-height: 1;
  }
  .bsc-action-btn svg { width: 13px; height: 13px; fill: currentColor; }
  .bsc-action-btn.view { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
  .bsc-action-btn.view:hover { background: #1d4ed8; color: #fff; }
  .bsc-action-btn.edit { background: #fefce8; border-color: #fef08a; color: #a16207; }
  .bsc-action-btn.edit:hover { background: #a16207; color: #fff; }
  .bsc-action-btn.delete { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
  .bsc-action-btn.delete:hover { background: #b91c1c; color: #fff; }
  .bsc-view-layout-shell {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
    align-items: start;
  }
  .bsc-pass-main-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    overflow: hidden;
  }
  .bsc-pass-banner {
    background: linear-gradient(135deg, #09171c 0%, #063d35 60%, #0d9488 100%);
    padding: 1.75rem 2rem;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .bsc-pass-banner-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.4);
  }
  .bsc-pass-content-body { padding: 2rem; }
  .bsc-view-table-wrapper {
    border: 1px solid var(--cinema-border);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.75rem;
    background: #ffffff;
  }
  .bsc-detail-table { width: 100%; border-collapse: collapse; text-align: left; }
  .bsc-detail-table tr { border-bottom: 1px solid var(--cinema-border-light); transition: background 0.15s ease; }
  .bsc-detail-table tr:last-child { border-bottom: none; }
  .bsc-detail-table tr:hover { background: #f8fafc; }
  .bsc-detail-table th {
    width: 28%;
    padding: 1.15rem 1.25rem;
    background: #fafafa;
    color: var(--cinema-text-muted);
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    vertical-align: middle;
    border-right: 1px solid var(--cinema-border-light);
  }
  .bsc-table-cell-header { display: flex; align-items: center; gap: 0.65rem; }
  .bsc-table-icon-badge {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    background: #f1f5f9;
    color: var(--cinema-accent-teal);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .bsc-table-icon-badge svg { width: 15px; height: 15px; fill: currentColor; }
  .bsc-detail-table td { padding: 1.15rem 1.25rem; color: var(--cinema-text-main); vertical-align: middle; font-size: 0.95rem; }
  .bsc-pass-actions-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.25rem;
    border-top: 1px solid var(--cinema-border-light);
  }
  .bsc-pass-thermal-box {
    background: #ffffff;
    border: 2px dashed #0d9488;
    border-radius: 16px;
    padding: 1.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
    position: relative;
  }
  .bsc-pass-thermal-box::before, .bsc-pass-thermal-box::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background: var(--cinema-bg-canvas);
    border-radius: 50%;
    top: 95px;
  }
  .bsc-pass-thermal-box::before { left: -11px; }
  .bsc-pass-thermal-box::after { right: -11px; }
  .bsc-pass-barcode-strip {
    height: 42px;
    background: repeating-linear-gradient(90deg, #0f172a 0px, #0f172a 3px, transparent 3px, transparent 6px, #0f172a 6px, #0f172a 7px, transparent 7px, transparent 10px);
    margin: 1.25rem 0 0.5rem 0;
    border-radius: 4px;
    opacity: 0.85;
  }
  @media (max-width: 1024px) {
    .bsc-booking-builder-grid, .bsc-view-layout-shell { grid-template-columns: 1fr; }
    .bsc-detail-table th { width: 35%; }
  }
</style>

<div class="bsc-subtabs-nav">
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=all')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'all' || $subtab === 'view') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg>
    <?php esc_html_e('All Bookings', 'big-screen-cinemas'); ?>
  </a>
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=add')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'add') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    <?php esc_html_e('Add Booking', 'big-screen-cinemas'); ?>
  </a>
</div>

<?php if (isset($_GET['booking_added'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Walk-in booking issued successfully to customer!', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['booking_updated'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Booking details updated successfully!', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['booking_deleted'])): ?>
  <div style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    <?php esc_html_e('Booking record deleted successfully.', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>