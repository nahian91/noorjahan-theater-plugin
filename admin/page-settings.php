<?php
if (!defined('ABSPATH')) exit;

function bsc_render_settings_page() {
  // Fetch pricing tiers repeater options (Stored as JSON array)
  $pricing_tiers = get_option('bsc_pricing_tiers', array(
    array('title' => 'Standard Seating', 'amount' => '400', 'description' => 'Applied across standard Rows B, C, D.'),
    array('title' => 'VIP Motorized Recliner', 'amount' => '500', 'description' => 'Applied across premium Row A.')
  ));

  $service_charge  = get_option('bsc_service_charge_pct', 5.00);
  $enable_tax      = get_option('bsc_enable_tax', 'yes');
  $seat_hold_time  = get_option('bsc_seat_hold_timeout', 10);

  bsc_render_page_shell_open('bsc-settings');
  ?>
  <style>
    .bsc-settings-card {
      background: #ffffff;
      border: 1px solid var(--cinema-border);
      border-radius: var(--cinema-radius-md);
      padding: 1.75rem;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
      margin-bottom: 1.5rem;
    }
    .bsc-settings-card-title {
      font-size: 1.05rem;
      font-weight: 800;
      color: var(--cinema-text-main);
      display: flex;
      align-items: center;
      gap: 0.55rem;
      border-bottom: 1px solid var(--cinema-border-light);
      padding-bottom: 0.85rem;
      margin: 0 0 1.25rem 0;
    }
    .bsc-switch-box {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: #f8fafc;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      border: 1px solid var(--cinema-border);
      margin-bottom: 1rem;
    }
    .bsc-switch-box input[type="checkbox"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
    }
    .bsc-switch-box label {
      font-weight: 700;
      font-size: 0.85rem;
      color: var(--cinema-text-main);
      cursor: pointer;
    }
    .bsc-pricing-row {
      background: #f8fafc;
      border: 1px solid var(--cinema-border);
      border-radius: 8px;
      padding: 12px;
      display: grid;
      grid-template-columns: 1fr 140px 1.5fr 40px;
      gap: 10px;
      align-items: center;
      margin-bottom: 10px;
    }
    @media (max-width: 768px) {
      .bsc-pricing-row {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <!-- Subtabs Navigation -->
  <div class="bsc-subtabs-nav">
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-settings&subtab=pricing')); ?>" class="bsc-subtab-btn active">
      🎟️ <?php esc_html_e('Pricing & Surcharges', 'big-screen-cinemas'); ?>
    </a>
  </div>

  <?php if (isset($_GET['updated'])): ?>
    <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      ✓ <?php esc_html_e('Pricing configurations saved and synced successfully!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('bsc_save_settings_verify'); ?>
    <input type="hidden" name="action" value="bsc_save_settings">
    <input type="hidden" name="active_subtab" value="pricing">

    <!-- Main Settings Body -->
    <div class="bsc-settings-card">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--cinema-border-light); padding-bottom:0.85rem; margin-bottom:1.25rem;">
        <h3 class="bsc-settings-card-title" style="border:none; padding:0; margin:0;">🎟️ <?php esc_html_e('Base Ticket Pricing & Surcharges (Repeater)', 'big-screen-cinemas'); ?></h3>
        <button type="button" class="button button-secondary" id="btn_add_pricing_row">+ Add Tier</button>
      </div>

      <!-- PRICING REPEATER CONTAINER -->
      <div id="bsc_pricing_repeater_container">
        <?php foreach ($pricing_tiers as $tier): ?>
          <div class="bsc-pricing-row">
            <input type="text" name="pricing_titles[]" value="<?php echo esc_attr($tier['title'] ?? ''); ?>" placeholder="Tier Title (e.g. VIP Recliner)" required>
            <input type="number" step="0.01" name="pricing_amounts[]" value="<?php echo esc_attr($tier['amount'] ?? ''); ?>" placeholder="Amount (BDT)" required>
            <input type="text" name="pricing_descriptions[]" value="<?php echo esc_attr($tier['description'] ?? ''); ?>" placeholder="Description / Rule notes">
            <button type="button" class="bsc-btn-remove-row" onclick="removePricingRow(this)" style="background:none; border:none; color:#ef4444; font-weight:bold; cursor:pointer; font-size:1.1rem; text-align:center;">✕</button>
          </div>
        <?php endforeach; ?>
      </div>

      <hr style="border:0; border-top:1px solid var(--cinema-border-light); margin: 1.5rem 0;">

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
    <div class="bsc-settings-card" style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:1.25rem 1.75rem;">
      <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; padding:10px 24px; font-weight:900; font-size:0.95rem; border-radius:8px; height:auto;">
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
            <input type="text" name="pricing_titles[]" placeholder="Tier Title (e.g. Student Pass)" required>
            <input type="number" step="0.01" name="pricing_amounts[]" placeholder="Amount (BDT)" required>
            <input type="text" name="pricing_descriptions[]" placeholder="Description / Rule notes">
            <button type="button" class="bsc-btn-remove-row" onclick="removePricingRow(this)" style="background:none; border:none; color:#ef4444; font-weight:bold; cursor:pointer; font-size:1.1rem; text-align:center;">✕</button>
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
        alert('At least one pricing tier is required.');
      }
    }
  </script>
  <?php
  bsc_render_page_shell_close();
}