<?php
if (!defined('ABSPATH')) exit;
?>
<style>
  .bsc-table-actions {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
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
  .bsc-action-btn svg {
    width: 13px;
    height: 13px;
    fill: currentColor;
  }
  .bsc-action-btn.view {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1d4ed8;
  }
  .bsc-action-btn.view:hover {
    background: #1d4ed8;
    color: #fff;
  }
  .bsc-action-btn.edit {
    background: #fefce8;
    border-color: #fef08a;
    color: #a16207;
  }
  .bsc-action-btn.edit:hover {
    background: #a16207;
    color: #fff;
  }
  .bsc-action-btn.delete {
    background: #fef2f2;
    border-color: #fecaca;
    color: #b91c1c;
  }
  .bsc-action-btn.delete:hover {
    background: #b91c1c;
    color: #fff;
  }
  .bsc-movie-view-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    align-items: start;
  }
  .bsc-movie-poster-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: var(--cinema-radius-md);
    padding: 1.25rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
  }
  .bsc-movie-poster-card img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    border: 1px solid var(--cinema-border);
    display: block;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
  }
  .bsc-movie-details-main {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: var(--cinema-radius-md);
    padding: 1.75rem 2rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
  }
  .bsc-movie-details-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1px solid var(--cinema-border-light);
    padding-bottom: 1.25rem;
    margin-bottom: 1.5rem;
  }
  .bsc-movie-spec-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid var(--cinema-border);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.5rem;
  }
  .bsc-movie-spec-table tr {
    border-bottom: 1px solid var(--cinema-border-light);
    transition: background 0.15s ease;
  }
  .bsc-movie-spec-table tr:last-child { border-bottom: none; }
  .bsc-movie-spec-table tr:hover { background: #f8fafc; }
  .bsc-movie-spec-table th {
    width: 28%;
    padding: 1rem 1.25rem;
    background: #fafafa;
    color: var(--cinema-text-muted);
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    vertical-align: middle;
    border-right: 1px solid var(--cinema-border-light);
  }
  .bsc-table-cell-header { display: flex; align-items: center; gap: 0.65rem; }
  .bsc-table-icon-badge {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #f1f5f9;
    color: var(--cinema-accent-teal);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .bsc-table-icon-badge svg { width: 14px; height: 14px; fill: currentColor; }
  .bsc-movie-spec-table td {
    padding: 1rem 1.25rem;
    color: var(--cinema-text-main);
    vertical-align: middle;
    font-size: 0.92rem;
  }
  @media (max-width: 992px) {
    .bsc-movie-view-layout { grid-template-columns: 1fr; }
    .bsc-movie-spec-table th { width: 35%; }
  }
</style>

<div class="bsc-subtabs-nav">
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-movies&subtab=all')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'all' || $subtab === 'view' || $subtab === 'edit') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
    <?php esc_html_e('All Movies', 'big-screen-cinemas'); ?>
  </a>
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-movies&subtab=add')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'add') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    <?php esc_html_e('Add Movie', 'big-screen-cinemas'); ?>
  </a>
</div>

<?php if (isset($_GET['movie_added'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Movie published successfully to database!', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['movie_updated'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Movie details updated successfully!', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['movie_deleted'])): ?>
  <div style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    <?php esc_html_e('Movie deleted successfully from database!', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>