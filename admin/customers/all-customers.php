<?php
/**
 * Customer Module - All Customers Directory View
 *
 * Path: admin/customers/all-customers.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
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
  .bsc-action-btn.view:hover { background: #1d4ed8; color: #fff; }
  .bsc-action-btn.edit {
    background: #fefce8;
    border-color: #fef08a;
    color: #a16207;
  }
  .bsc-action-btn.edit:hover { background: #a16207; color: #fff; }
  .bsc-action-btn.delete {
    background: #fef2f2;
    border-color: #fecaca;
    color: #b91c1c;
  }
  .bsc-action-btn.delete:hover { background: #b91c1c; color: #fff; }
</style>

<div class="cinema-panel" style="margin: 0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
  <div class="cinema-panel-header" style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0;">
    <div>
      <h3 class="cinema-panel-title" style="margin:0 0 4px 0; font-size:1.15rem; font-weight:800; color:#0f172a;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style="vertical-align:-4px; margin-right:4px;"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        <?php echo esc_html( sprintf( __( 'Club Members (%d)', 'bsc-cinema-manager' ), count( $customers ) ) ); ?>
      </h3>
      <span style="font-size:0.78rem; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'Manage club member profiles and contact directory.', 'bsc-cinema-manager' ); ?></span>
    </div>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-customers&subtab=add' ) ); ?>" class="button button-primary" style="background:#064e3b; border-color:#064e3b; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      <?php esc_html_e( 'Add Customer', 'bsc-cinema-manager' ); ?>
    </a>
  </div>

  <div class="cinema-table-wrap" style="overflow-x:auto;">
    <table class="cinema-data-table" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr style="border-bottom:1px solid #e2e8f0; background:#f8fafc;">
          <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'CUSTOMER', 'bsc-cinema-manager' ); ?></th>
          <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'PHONE', 'bsc-cinema-manager' ); ?></th>
          <th style="padding:10px 12px; text-align:right;"><?php esc_html_e( 'ACTIONS', 'bsc-cinema-manager' ); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if ( ! empty( $customers ) ) : foreach ( $customers as $c ) :
          $view_url   = admin_url( 'admin.php?page=bsc-customers&subtab=view&id=' . absint( $c->id ) );
          $edit_url   = admin_url( 'admin.php?page=bsc-customers&subtab=edit&id=' . absint( $c->id ) );
          $delete_url = wp_nonce_url(
            admin_url( 'admin.php?page=bsc-customers&subtab=delete&id=' . absint( $c->id ) ),
            'bsc_delete_customer_' . absint( $c->id )
          );
        ?>
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:12px;">
              <strong><?php echo esc_html( $c->name ); ?></strong><br>
              <small style="color:var(--cinema-text-muted, #64748b);"><?php echo esc_html( $c->email ? $c->email : __( 'No email', 'bsc-cinema-manager' ) ); ?></small>
            </td>
            <td style="padding:12px;"><span style="font-family:var(--cinema-font-mono, monospace); font-size:0.85rem; color:#334155; font-weight:700;"><?php echo esc_html( $c->phone ); ?></span></td>
            <td style="padding:12px; text-align:right; white-space:nowrap;">
              <div class="bsc-table-actions">
                <a href="<?php echo esc_url( $view_url ); ?>" class="bsc-action-btn view" title="<?php esc_attr_e( 'View', 'bsc-cinema-manager' ); ?>">
                  <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                  <span><?php esc_html_e( 'View', 'bsc-cinema-manager' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $edit_url ); ?>" class="bsc-action-btn edit" title="<?php esc_attr_e( 'Edit', 'bsc-cinema-manager' ); ?>">
                  <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                  <span><?php esc_html_e( 'Edit', 'bsc-cinema-manager' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $delete_url ); ?>" class="bsc-action-btn delete" onclick="return confirm('<?php echo esc_js( __( 'Permanently remove this customer record?', 'bsc-cinema-manager' ) ); ?>');" title="<?php esc_attr_e( 'Delete', 'bsc-cinema-manager' ); ?>">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  <span><?php esc_html_e( 'Delete', 'bsc-cinema-manager' ); ?></span>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; else : ?>
          <tr><td colspan="3" style="text-align:center; padding:2.5rem; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'No customers registered yet.', 'bsc-cinema-manager' ); ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>