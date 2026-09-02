<?php
if (!defined('ABSPATH')) exit;

function bsc_render_settings_page() {
  // Fetch pricing tiers repeater options (Stored as JSON array)
  $pricing_tiers = get_option('bsc_pricing_tiers', array(
    array('title' => 'Standard Seating', 'amount' => get_option('bsc_standard_price', '400'), 'description' => 'Applied across standard Rows B, C, D.'),
    array('title' => 'VIP Motorized Recliner', 'amount' => get_option('bsc_vip_price', '500'), 'description' => 'Applied across premium Row A.')
  ));

  $service_charge  = get_option('bsc_service_charge_pct', 5.00);
  $enable_tax      = get_option('bsc_enable_tax', 'yes');
  $seat_hold_time  = get_option('bsc_seat_hold_timeout', 10);
  $currency_symbol = get_option('bsc_currency_symbol', 'BDT');

  bsc_render_page_shell_open('bsc-settings');
  ?>
  <style>
    .bsc-settings-card {
      background: #ffffff;
      border: 1px solid var(--cinema-border);
      border-radius: var(--cinema-radius-md);
      padding: 1.75rem 2rem;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
      margin-bottom: 1.5rem;
    }
    .bsc-settings-card-title {
      font-size: 1.15rem;
      font-weight: 800;
      color: var(--cinema-text-main);
      display: flex;
      align-items: center;
      gap: 0.55rem;
      margin: 0;
    }
    .bsc-settings-card-title svg {
      width: 18px;
      height: 18px;
      fill: currentColor;
    }
    .bsc-switch-box {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: #f8fafc;
      padding: 0.85rem 1.15rem;
      border-radius: 8px;
      border: 1px solid var(--cinema-border);
      margin-bottom: 1.25rem;
    }
    .bsc-switch-box input[type="checkbox"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
      accent-color: var(--cinema-accent-teal);
    }
    .bsc-switch-box label {
      font-weight: 700;
      font-size: 0.88rem;
      color: var(--cinema-text-main);
      cursor: pointer;
    }
    .bsc-pricing-row {
      background: #f8fafc;
      border: 1px solid var(--cinema-border);
      border-radius: 8px;
      padding: 12px 16px;
      display: grid;
      grid-template-columns: 1fr 140px 1.5fr 36px;
      gap: 12px;
      align-items: center;
      margin-bottom: 10px;
    }
    .bsc-pricing-row input {
      background: #ffffff !important;
    }
    .bsc-btn-icon-remove {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #b91c1c;
      width: 34px;
      height: 34px;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      padding: 0;
    }
    .bsc-btn-icon-remove:hover {
      background: #b91c1c;
      color: #ffffff;
    }
    .bsc-btn-icon-remove svg {
      width: 14px;
      height: 14px;
      fill: currentColor;
    }
    @media (max-width: 768px) {
      .bsc-pricing-row {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <!-- Subtabs Navigation with SVGs -->
  <div class="bsc-subtabs-nav">
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-settings&subtab=pricing')); ?>" class="bsc-subtab-btn active">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
      <?php esc_html_e('Pricing & Surcharges', 'big-screen-cinemas'); ?>
    </a>
  </div>

  <?php if (isset($_GET['updated'])): ?>
    <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
      <?php esc_html_e('Pricing configurations saved and synced successfully!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('bsc_save_settings_verify'); ?>
    <input type="hidden" name="action" value="bsc_save_settings">
    <input type="hidden" name="active_subtab" value="pricing">

    <!-- Main Settings Body -->
    <div class="bsc-settings-card">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--cinema-border-light); padding-bottom:1.25rem; margin-bottom:1.5rem;">
        <div>
          <h3 class="bsc-settings-card-title">
            <svg viewBox="0 0 24 24"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
            <?php esc_html_e('Base Ticket Pricing Tiers (Repeater)', 'big-screen-cinemas'); ?>
          </h3>
          <span style="font-size:0.8rem; color:var(--cinema-text-muted); display:block; margin-top:2px;">
            <?php esc_html_e('Configure ticket tier pricing, row assignment notes, and VIP recliner surcharge values.', 'big-screen-cinemas'); ?>
          </span>
        </div>

        <button type="button" class="button button-secondary" id="btn_add_pricing_row" style="display:inline-flex; align-items:center; gap:5px; font-weight:800;">
          <svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
          <?php esc_html_e('Add Tier', 'big-screen-cinemas'); ?>
        </button>
      </div>

      <!-- PRICING REPEATER CONTAINER -->
      <div id="bsc_pricing_repeater_container">
        <?php foreach ($pricing_tiers as $tier): ?>
          <div class="bsc-pricing-row">
            <input type="text" name="pricing_titles[]" value="<?php echo esc_attr($tier['title'] ?? ''); ?>" placeholder="<?php esc_attr_e('Tier Title (e.g. VIP Recliner)', 'big-screen-cinemas'); ?>" required>
            <input type="number" step="0.01" name="pricing_amounts[]" value="<?php echo esc_attr($tier['amount'] ?? ''); ?>" placeholder="<?php esc_attr_e('Amount', 'big-screen-cinemas'); ?> (<?php echo esc_html($currency_symbol); ?>)" required>
            <input type="text" name="pricing_descriptions[]" value="<?php echo esc_attr($tier['description'] ?? ''); ?>" placeholder="<?php esc_attr_e('Description / Rule notes', 'big-screen-cinemas'); ?>">
            <button type="button" class="bsc-btn-icon-remove" onclick="removePricingRow(this)" title="<?php esc_attr_e('Remove Tier', 'big-screen-cinemas'); ?>">
              <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
            </button>
          </div>
        <?php endforeach; ?>
      </div>

      <hr style="border:0; border-top:1px solid var(--cinema-border-light); margin: 1.75rem 0;">

      <div class="bsc-switch-box">
        <input type="checkbox" name="enable_tax" id="chk_tax" value="yes" <?php checked($enable_tax, 'yes'); ?>>
        <label for="chk_tax"><?php esc_html_e('Apply VAT / Online Service Surcharge to online checkouts', 'big-screen-cinemas'); ?></label>
      </div>

      <div class="bsc-form-grid-2">
        <div class="bsc-form-group">
          <label><?php esc_html_e('Service & VAT Surcharge Percentage (%)', 'big-screen-cinemas'); ?></label>
          <input type="number" step="0.1" name="service_charge_pct" value="<?php echo esc_attr($service_charge); ?>">
        </div>

        <div class="bsc-form-group">
          <label><?php esc_html_e('Online Seat Hold Timeout (Minutes)', 'big-screen-cinemas'); ?></label>
          <input type="number" min="1" max="60" name="seat_hold_timeout" value="<?php echo esc_attr($seat_hold_time); ?>">
          <small style="color:var(--cinema-text-muted);"><?php esc_html_e('Releases locked seats if customer does not complete payment.', 'big-screen-cinemas'); ?></small>
        </div>
      </div>
    </div>

    <!-- Bottom Save Action Bar -->
    <div class="bsc-settings-card" style="display:flex; justify-content:flex-end; align-items:center; background:#f8fafc; padding:1.25rem 2rem;">
      <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; padding:10px 24px; font-weight:800; font-size:0.95rem; border-radius:6px; display:inline-flex; align-items:center; gap:6px; height:auto;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
        <?php esc_html_e('Save Configuration', 'big-screen-cinemas'); ?>
      </button>
    </div>

  </form>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const btnAdd = document.getElementById('btn_add_pricing_row');
      const container = document.getElementById('bsc_pricing_repeater_container');

      if (btnAdd && container) {
        btnAdd.addEventListener('click', function() {
          const row = document.createElement('div');
          row.className = 'bsc-pricing-row';
          row.innerHTML = `
            <input type="text" name="pricing_titles[]" placeholder="<?php esc_attr_e('Tier Title (e.g. Student Pass)', 'big-screen-cinemas'); ?>" required>
            <input type="number" step="0.01" name="pricing_amounts[]" placeholder="<?php esc_attr_e('Amount', 'big-screen-cinemas'); ?> (<?php echo esc_html($currency_symbol); ?>)" required>
            <input type="text" name="pricing_descriptions[]" placeholder="<?php esc_attr_e('Description / Rule notes', 'big-screen-cinemas'); ?>">
            <button type="button" class="bsc-btn-icon-remove" onclick="removePricingRow(this)" title="<?php esc_attr_e('Remove Tier', 'big-screen-cinemas'); ?>">
              <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
            </button>
          `;
          container.appendChild(row);
        });
      }
    });

    function removePricingRow(btn) {
      const row = btn.closest('.bsc-pricing-row');
      if (document.querySelectorAll('#bsc_pricing_repeater_container .bsc-pricing-row').length > 1) {
        row.remove();
      } else {
        alert('<?php echo esc_js(__('At least one pricing tier is required.', 'big-screen-cinemas')); ?>');
      }
    }
  </script>
  <?php
  bsc_render_page_shell_close();
}