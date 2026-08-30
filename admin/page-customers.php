<?php
if (!defined('ABSPATH')) exit;

function bsc_render_customers_page() {
  global $wpdb;
  $table_customers = $wpdb->prefix . 'bsc_customers';
  $table_bookings  = $wpdb->prefix . 'bsc_bookings';

  $subtab      = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'all';
  $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

  bsc_render_page_shell_open('bsc-customers');
  ?>
  <div class="bsc-subtabs-nav">
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-customers&subtab=all')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'all') ? 'active' : ''; ?>"><?php esc_html_e('All Customers', 'big-screen-cinemas'); ?></a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-customers&subtab=add')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'add') ? 'active' : ''; ?>"><?php esc_html_e('➕ Add Customer', 'big-screen-cinemas'); ?></a>
    <?php if ($subtab === 'view'): ?>
      <span class="bsc-subtab-btn active"><?php esc_html_e('Customer Profile', 'big-screen-cinemas'); ?></span>
    <?php endif; ?>
    <?php if ($subtab === 'edit'): ?>
      <span class="bsc-subtab-btn active"><?php esc_html_e('Edit Customer', 'big-screen-cinemas'); ?></span>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['customer_added'])): ?>
    <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      ✓ <?php esc_html_e('Customer account created successfully!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['customer_updated'])): ?>
    <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      ✓ <?php esc_html_e('Customer details updated successfully!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['customer_deleted'])): ?>
    <div style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      ✕ <?php esc_html_e('Customer removed from registry!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php
  /* ==========================================================================
     01. VIEW MODE: IN-ADMIN CUSTOMER DOSSIER & BOOKING HISTORY
     ========================================================================== */
  if ($subtab === 'view' && $customer_id > 0):
    $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_customers WHERE id = %d", $customer_id));
    if (!$customer):
      echo '<div class="cinema-panel"><p>' . esc_html__('Customer record not found.', 'big-screen-cinemas') . '</p></div>';
    else:
      // Fetch matching historical bookings by customer email or phone
      $customer_history = array();
      if (!empty($customer->email) || !empty($customer->phone)) {
        $customer_history = $wpdb->get_results($wpdb->prepare(
          "SELECT * FROM $table_bookings WHERE (customer_email = %s AND customer_email != '') OR (customer_phone = %s AND customer_phone != '') ORDER BY id DESC",
          $customer->email,
          $customer->phone
        ));
      }
  ?>
    <div class="cinema-panel">
      <div class="cinema-panel-header" style="border-bottom: 1px solid var(--cinema-border-light); padding-bottom: 1.25rem;">
        <div>
          <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
            <span class="cinema-badge-pill active"><?php echo esc_html($customer->tier); ?></span>
            <span class="cinema-voucher-tag"><?php echo esc_html($customer->membership_id); ?></span>
          </div>
          <h2 style="font-size: 1.85rem; font-weight: 900; margin: 0 0 0.35rem 0; color: var(--cinema-text-main);">
            <?php echo esc_html($customer->name); ?>
          </h2>
          <span style="font-size: 0.82rem; color: var(--cinema-text-muted); font-family: var(--cinema-font-mono);">
            Member Registered: <strong><?php echo esc_html($customer->created_at); ?></strong>
          </span>
        </div>

        <div class="bsc-action-btn-group">
          <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-customers&subtab=edit&id=' . $customer->id)); ?>" class="button button-primary" style="background: #ca8a04; border-color: #ca8a04; font-weight:700;">
            ✏️ <?php esc_html_e('Edit Customer', 'big-screen-cinemas'); ?>
          </a>
          <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bsc_delete_customer&id=' . $customer->id), 'bsc_delete_customer_' . $customer->id)); ?>" class="button" style="color:#dc2626; border-color:#fca5a5; background:#fff;" onclick="return confirm('Permanently remove this customer record?');">
            🗑️ <?php esc_html_e('Delete', 'big-screen-cinemas'); ?>
          </a>
          <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-customers&subtab=all')); ?>" class="button button-secondary">
            ← <?php esc_html_e('Back to List', 'big-screen-cinemas'); ?>
          </a>
        </div>
      </div>

      <!-- Telemetry Metric Cards -->
      <div class="bsc-view-stats-row" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
        <div class="bsc-stat-card-mini">
          <div class="k"><?php esc_html_e('Contact Phone', 'big-screen-cinemas'); ?></div>
          <div class="v"><?php echo esc_html($customer->phone ?: 'Not provided'); ?></div>
        </div>
        <div class="bsc-stat-card-mini">
          <div class="k"><?php esc_html_e('Email Address', 'big-screen-cinemas'); ?></div>
          <div class="v" style="font-size:0.95rem; word-break:break-all;"><?php echo esc_html($customer->email ?: 'Not provided'); ?></div>
        </div>
        <div class="bsc-stat-card-mini">
          <div class="k"><?php esc_html_e('Total Visits', 'big-screen-cinemas'); ?></div>
          <div class="v" style="color:var(--cinema-accent-teal);"><?php echo intval($customer->total_visits); ?> Screenings</div>
        </div>
        <div class="bsc-stat-card-mini">
          <div class="k"><?php esc_html_e('Reward Points', 'big-screen-cinemas'); ?></div>
          <div class="v" style="color:var(--cinema-accent-amber); font-family:var(--cinema-font-mono);"><?php echo number_format($customer->reward_points); ?> PTS</div>
        </div>
      </div>

      <!-- Customer Ticket Purchase History -->
      <div style="border-top: 1px solid var(--cinema-border-light); padding-top: 1.5rem;">
        <h4 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 800; display:flex; align-items:center; gap:0.5rem;">
          🎟️ <?php esc_html_e('Screening Passes & Ticket Log', 'big-screen-cinemas'); ?> (<?php echo count($customer_history); ?>)
        </h4>

        <div class="cinema-table-wrap">
          <table class="cinema-data-table">
            <thead>
              <tr>
                <th>TOKEN</th>
                <th>MOVIE</th>
                <th>AUDITORIUM</th>
                <th>SHOWTIME</th>
                <th>SEATS</th>
                <th>AMOUNT</th>
                <th>DATE</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($customer_history)): foreach ($customer_history as $h): ?>
                <tr>
                  <td><span class="cinema-voucher-tag"><?php echo esc_html($h->booking_token); ?></span></td>
                  <td><strong><?php echo esc_html($h->movie_title); ?></strong></td>
                  <td><?php echo esc_html($h->auditorium); ?></td>
                  <td><span class="cinema-badge-pill active" style="font-size:0.7rem;"><?php echo esc_html($h->showtime); ?></span></td>
                  <td><code><?php echo esc_html($h->seats); ?></code></td>
                  <td><strong style="color:var(--cinema-accent-teal);">BDT <?php echo number_format($h->total_amount, 2); ?></strong></td>
                  <td><small style="color:var(--cinema-text-muted);"><?php echo esc_html($h->booking_date); ?></small></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="7"><?php esc_html_e('No ticket bookings linked to this customer yet.', 'big-screen-cinemas'); ?></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  <?php
    endif;
  /* ==========================================================================
     02. ADD / EDIT CUSTOMER FORM
     ========================================================================== */
  elseif ($subtab === 'add' || $subtab === 'edit'):
    $customer = null;
    $is_edit  = ($subtab === 'edit' && $customer_id > 0);

    if ($is_edit) {
      $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_customers WHERE id = %d", $customer_id));
    }
  ?>
    <div class="cinema-panel" style="max-width: 650px; margin: 0 auto;">
      <h3 class="cinema-panel-title" style="margin-bottom: 1.5rem;">
        👥 <?php echo $is_edit ? esc_html__('Edit Customer Details', 'big-screen-cinemas') : esc_html__('Add VIP Club Customer', 'big-screen-cinemas'); ?>
      </h3>

      <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php
        if ($is_edit) {
          wp_nonce_field('bsc_edit_customer_verify');
          echo '<input type="hidden" name="action" value="bsc_edit_customer">';
          echo '<input type="hidden" name="customer_id" value="' . esc_attr($customer->id) . '">';
        } else {
          wp_nonce_field('bsc_add_customer_verify');
          echo '<input type="hidden" name="action" value="bsc_add_customer">';
        }
        ?>

        <div class="bsc-form-group">
          <label><?php esc_html_e('Customer Full Name', 'big-screen-cinemas'); ?> *</label>
          <input type="text" name="customer_name" required value="<?php echo esc_attr($customer->name ?? ''); ?>" placeholder="e.g. Mahfuzur Rahman">
        </div>

        <div class="bsc-form-grid-2">
          <div class="bsc-form-group">
            <label><?php esc_html_e('Phone Number', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="customer_phone" required value="<?php echo esc_attr($customer->phone ?? '+880 '); ?>" placeholder="+880 1700-000000">
          </div>
          <div class="bsc-form-group">
            <label><?php esc_html_e('Email Address', 'big-screen-cinemas'); ?></label>
            <input type="email" name="customer_email" value="<?php echo esc_attr($customer->email ?? ''); ?>" placeholder="customer@example.com">
          </div>
        </div>

        <div class="bsc-form-grid-3">
          <div class="bsc-form-group">
            <label><?php esc_html_e('Membership Tier', 'big-screen-cinemas'); ?> *</label>
            <select name="customer_tier">
              <?php
                $tiers = array('VIP CLUB', 'STANDARD CLUB', 'PREMIER ELITE');
                $selected_tier = $customer->tier ?? 'VIP CLUB';
                foreach ($tiers as $t) {
                  echo '<option value="' . esc_attr($t) . '" ' . selected($selected_tier, $t, false) . '>' . esc_html($t) . '</option>';
                }
              ?>
            </select>
          </div>
          <div class="bsc-form-group">
            <label><?php esc_html_e('Total Visits', 'big-screen-cinemas'); ?></label>
            <input type="number" name="total_visits" min="0" value="<?php echo esc_attr($customer->total_visits ?? 1); ?>">
          </div>
          <div class="bsc-form-group">
            <label><?php esc_html_e('Reward Points', 'big-screen-cinemas'); ?></label>
            <input type="number" name="reward_points" min="0" value="<?php echo esc_attr($customer->reward_points ?? 100); ?>">
          </div>
        </div>

        <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:10px; font-weight:800; font-size: 0.95rem; margin-top: 0.5rem;">
          <?php echo $is_edit ? esc_html__('Update Customer Profile', 'big-screen-cinemas') : esc_html__('Save VIP Member', 'big-screen-cinemas'); ?>
        </button>
      </form>
    </div>

  <?php
  /* ==========================================================================
     03. ALL CUSTOMERS TABLE WITH VIEW, EDIT, DELETE ACTIONS
     ========================================================================== */
  else:
    $customers = $wpdb->get_results("SELECT * FROM $table_customers ORDER BY id DESC");
  ?>
    <div class="cinema-panel" style="margin: 0;">
      <div class="cinema-panel-header">
        <h3 class="cinema-panel-title">👥 <?php echo esc_html(sprintf(__('VIP Club Customers (%d)', 'big-screen-cinemas'), count($customers))); ?></h3>
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
              <th style="text-align:center;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($customers)): foreach ($customers as $c):
              $view_url   = admin_url('admin.php?page=bsc-customers&subtab=view&id=' . $c->id);
              $edit_url   = admin_url('admin.php?page=bsc-customers&subtab=edit&id=' . $c->id);
              $delete_url = wp_nonce_url(admin_url('admin-post.php?action=bsc_delete_customer&id=' . $c->id), 'bsc_delete_customer_' . $c->id);
            ?>
              <tr>
                <td>
                  <strong><?php echo esc_html($c->name); ?></strong><br>
                  <small style="color:var(--cinema-text-muted);"><?php echo esc_html($c->email ?: 'No email'); ?></small>
                </td>
                <td><code><?php echo esc_html($c->membership_id); ?></code></td>
                <td><span class="cinema-badge-pill active"><?php echo esc_html($c->tier); ?></span></td>
                <td><?php echo intval($c->total_visits); ?> Screenings</td>
                <td style="color:var(--cinema-accent-teal); font-weight:800; font-family:var(--cinema-font-mono);">
                  <?php echo number_format($c->reward_points); ?> PTS
                </td>
                <td><span style="font-family:var(--cinema-font-mono); font-size:0.8rem;"><?php echo esc_html($c->phone); ?></span></td>
                <td style="text-align:center;">
                  <div class="bsc-action-btn-group" style="justify-content:center;">
                    <a href="<?php echo esc_url($view_url); ?>" class="bsc-btn-action view" title="<?php esc_attr_e('View Profile & Bookings', 'big-screen-cinemas'); ?>">👁 <?php esc_html_e('View', 'big-screen-cinemas'); ?></a>
                    <a href="<?php echo esc_url($edit_url); ?>" class="bsc-btn-action edit" title="<?php esc_attr_e('Edit Details', 'big-screen-cinemas'); ?>">✏️ <?php esc_html_e('Edit', 'big-screen-cinemas'); ?></a>
                    <a href="<?php echo esc_url($delete_url); ?>" class="bsc-btn-action delete" onclick="return confirm('Permanently remove this customer record?');" title="<?php esc_attr_e('Delete Customer', 'big-screen-cinemas'); ?>">🗑️ <?php esc_html_e('Delete', 'big-screen-cinemas'); ?></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="7"><?php esc_html_e('No customers registered yet.', 'big-screen-cinemas'); ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
  <?php
  bsc_render_page_shell_close();
}