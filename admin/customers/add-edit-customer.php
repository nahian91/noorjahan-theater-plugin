<?php
if (!defined('ABSPATH')) exit;

$customer = null;
$is_edit  = ($subtab === 'edit' && $customer_id > 0);

if ($is_edit) {
  $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_customers WHERE id = %d", $customer_id));
}
?>
<div class="cinema-panel" style="max-width: 680px; margin: 0 auto; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
  <h3 class="cinema-panel-title" style="margin-bottom: 1.5rem;">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    <?php echo $is_edit ? esc_html__('Edit Customer Profile', 'big-screen-cinemas') : esc_html__('Register New VIP Club Member', 'big-screen-cinemas'); ?>
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

    <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:12px; font-weight:800; font-size: 0.95rem; border-radius:8px; margin-top:0.75rem; display:flex; align-items:center; justify-content:center; gap:8px;">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
      <?php echo $is_edit ? esc_html__('Update Customer Profile', 'big-screen-cinemas') : esc_html__('Save VIP Member', 'big-screen-cinemas'); ?>
    </button>
  </form>
</div>