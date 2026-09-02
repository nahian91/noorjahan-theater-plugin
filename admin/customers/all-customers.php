<?php
if (!defined('ABSPATH')) exit;
?>
<div class="cinema-panel" style="margin: 0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
  <div class="cinema-panel-header">
    <div>
      <h3 class="cinema-panel-title">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        <?php echo esc_html(sprintf(__('VIP Club Customers (%d)', 'big-screen-cinemas'), count($customers))); ?>
      </h3>
      <span style="font-size:0.78rem; color:var(--cinema-text-muted);"><?php esc_html_e('Manage loyalty members, rewards points balance, and contact directory.', 'big-screen-cinemas'); ?></span>
    </div>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-customers&subtab=add')); ?>" class="button button-primary" style="background:#064e3b; border-color:#064e3b; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      <?php esc_html_e('Add Customer', 'big-screen-cinemas'); ?>
    </a>
  </div>

  <div class="cinema-table-wrap">
    <table class="cinema-data-table">
      <thead>
        <tr>
          <th>CUSTOMER</th>
          <th>MEMBERSHIP ID</th>
          <th>TIER</th>
          <th>VISITS</th>
          <th>REWARD PTS</th>
          <th>PHONE</th>
          <th style="text-align:right;">ACTIONS</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($customers)): foreach ($customers as $c):
          $view_url   = admin_url('admin.php?page=bsc-customers&subtab=view&id=' . $c->id);
          $edit_url   = admin_url('admin.php?page=bsc-customers&subtab=edit&id=' . $c->id);
          $delete_url = wp_nonce_url(admin_url('admin.php?page=bsc-customers&subtab=delete&id=' . $c->id), 'bsc_delete_customer_' . $c->id);
        ?>
          <tr>
            <td>
              <strong><?php echo esc_html($c->name); ?></strong><br>
              <small style="color:var(--cinema-text-muted);"><?php echo esc_html($c->email ?: 'No email'); ?></small>
            </td>
            <td><span class="cinema-voucher-tag"><?php echo esc_html($c->membership_id); ?></span></td>
            <td><span class="cinema-badge-pill active" style="font-size:0.68rem;"><?php echo esc_html($c->tier); ?></span></td>
            <td><strong><?php echo intval($c->total_visits); ?></strong> <span style="font-size:0.75rem; color:var(--cinema-text-muted);">Screenings</span></td>
            <td style="color:var(--cinema-accent-teal); font-weight:800; font-family:var(--cinema-font-mono);">
              <?php echo number_format($c->reward_points); ?> PTS
            </td>
            <td><span style="font-family:var(--cinema-font-mono); font-size:0.85rem;"><?php echo esc_html($c->phone); ?></span></td>
            <td style="text-align:right; white-space:nowrap;">
              <div class="bsc-table-actions">
                <a href="<?php echo esc_url($view_url); ?>" class="bsc-action-btn view">
                  <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                  <span>View</span>
                </a>
                <a href="<?php echo esc_url($edit_url); ?>" class="bsc-action-btn edit">
                  <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                  <span>Edit</span>
                </a>
                <a href="<?php echo esc_url($delete_url); ?>" class="bsc-action-btn delete" onclick="return confirm('Permanently remove this customer record?');">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  <span>Delete</span>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="7" style="text-align:center; padding:2.5rem; color:var(--cinema-text-muted);"><?php esc_html_e('No customers registered yet.', 'big-screen-cinemas'); ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>