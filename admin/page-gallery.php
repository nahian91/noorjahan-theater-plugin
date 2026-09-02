<?php
if (!defined('ABSPATH')) exit;

function bsc_render_gallery_page() {
  $gallery_images = get_option('bsc_gallery_images', array(
    array(
      'url'         => 'https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?w=1200&auto=format&fit=crop&q=85',
      'category'    => 'screens',
      'title'       => '70ft Curved Silver Screen',
      'description' => 'High-frame-rate curved giant screen with dual 4K laser projection.'
    ),
    array(
      'url'         => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1200&auto=format&fit=crop&q=85',
      'category'    => 'lounges',
      'title'       => 'Motorized Recliner Suites',
      'description' => 'Heated motorized recliners with in-seat dining swivel tables.'
    ),
    array(
      'url'         => 'https://images.unsplash.com/photo-1585647347483-22b66260dfff?w=1200&auto=format&fit=crop&q=85',
      'category'    => 'dining',
      'title'       => 'Signature Food Combos',
      'description' => 'Chef-prepared hot combos, butter popcorn tubs, and artisan beverages.'
    )
  ));

  bsc_render_page_shell_open('bsc-gallery');
  ?>
  <style>
    .bsc-gallery-mgmt-panel {
      background: #ffffff;
      border: 1px solid var(--cinema-border);
      border-radius: var(--cinema-radius-md);
      padding: 1.75rem 2rem;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
    }
    .bsc-gallery-grid-deck {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }
    .bsc-gallery-card-item {
      background: #ffffff;
      border: 1px solid var(--cinema-border);
      border-radius: 12px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .bsc-gallery-card-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
      border-color: #cbd5e1;
    }
    .bsc-gallery-thumb-wrap {
      position: relative;
      height: 165px;
      background: #0b171c;
      overflow: hidden;
    }
    .bsc-gallery-thumb-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.3s ease;
    }
    .bsc-gallery-card-item:hover .bsc-gallery-thumb-wrap img {
      transform: scale(1.04);
    }
    .bsc-gallery-card-body {
      padding: 1rem 1.15rem;
      display: flex;
      flex-direction: column;
      gap: 0.85rem;
    }
    .bsc-gallery-field {
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
    }
    .bsc-gallery-field label {
      font-size: 0.7rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--cinema-text-muted);
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }
    .bsc-gallery-field label svg {
      width: 13px;
      height: 13px;
      fill: currentColor;
    }
    .bsc-gallery-field input,
    .bsc-gallery-field select,
    .bsc-gallery-field textarea {
      width: 100%;
      padding: 0.55rem 0.75rem;
      border: 1px solid var(--cinema-border);
      border-radius: 6px;
      font-size: 0.85rem;
      color: var(--cinema-text-main);
      background: #f8fafc;
      box-sizing: border-box;
      font-family: inherit;
    }
    .bsc-gallery-field input:focus,
    .bsc-gallery-field select:focus,
    .bsc-gallery-field textarea:focus {
      background: #ffffff;
      border-color: var(--cinema-accent-teal);
      outline: none;
    }
    .bsc-gallery-card-footer {
      padding: 0 1.15rem 1rem 1.15rem;
      border-top: 1px solid var(--cinema-border-light);
      padding-top: 0.75rem;
    }
    .bsc-btn-remove-photo {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      width: 100%;
      padding: 6px 10px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 800;
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #b91c1c;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .bsc-btn-remove-photo:hover {
      background: #b91c1c;
      color: #ffffff;
    }
    .bsc-btn-remove-photo svg {
      width: 12px;
      height: 12px;
      fill: currentColor;
    }
  </style>

  <div class="bsc-gallery-mgmt-panel">
    
    <div class="cinema-panel-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--cinema-border-light); padding-bottom:1.25rem;">
      <div>
        <h3 class="cinema-panel-title" style="margin:0; font-size:1.25rem; font-weight:900; display:flex; align-items:center; gap:0.5rem;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
          <?php esc_html_e('Multiplex Gallery Showcase Management', 'big-screen-cinemas'); ?>
        </h3>
        <span style="font-size:0.82rem; color:var(--cinema-text-muted); display:block; margin-top:2px;">
          <?php esc_html_e('Manage luxury auditoriums, laser screen views, VIP recliner suites, and gourmet food photo cards.', 'big-screen-cinemas'); ?>
        </span>
      </div>

      <button type="button" class="button button-primary" id="btn_upload_gallery_img" style="background:#064e3b; border-color:#064e3b; font-weight:800; display:inline-flex; align-items:center; gap:6px; padding:7px 14px;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        <?php esc_html_e('Upload / Add Photos', 'big-screen-cinemas'); ?>
      </button>
    </div>

    <?php if (isset($_GET['updated'])): ?>
      <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        <?php esc_html_e('Gallery changes saved and synced live!', 'big-screen-cinemas'); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <?php wp_nonce_field('bsc_save_gallery_verify'); ?>
      <input type="hidden" name="action" value="bsc_save_gallery">

      <div id="bsc_gallery_grid" class="bsc-gallery-grid-deck">
        <?php if (!empty($gallery_images)): foreach ($gallery_images as $img): ?>
          <div class="bsc-gallery-card-item">
            <div>
              <div class="bsc-gallery-thumb-wrap">
                <img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($img['title'] ?? ''); ?>">
                <input type="hidden" name="gallery_urls[]" value="<?php echo esc_url($img['url']); ?>">
              </div>
              
              <div class="bsc-gallery-card-body">
                <div class="bsc-gallery-field">
                  <label>
                    <svg viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                    <?php esc_html_e('Category', 'big-screen-cinemas'); ?>
                  </label>
                  <select name="gallery_categories[]">
                    <option value="screens" <?php selected($img['category'] ?? '', 'screens'); ?>><?php esc_html_e('Auditoriums & Screens', 'big-screen-cinemas'); ?></option>
                    <option value="lounges" <?php selected($img['category'] ?? '', 'lounges'); ?>><?php esc_html_e('VIP Recliners & Lounges', 'big-screen-cinemas'); ?></option>
                    <option value="dining" <?php selected($img['category'] ?? '', 'dining'); ?>><?php esc_html_e('Gourmet Concessions', 'big-screen-cinemas'); ?></option>
                  </select>
                </div>

                <div class="bsc-gallery-field">
                  <label>
                    <svg viewBox="0 0 24 24"><path d="M5 4v3h5.5v12h3V7H19V4z"/></svg>
                    <?php esc_html_e('Title / Heading', 'big-screen-cinemas'); ?>
                  </label>
                  <input type="text" name="gallery_titles[]" value="<?php echo esc_attr($img['title'] ?? ''); ?>" placeholder="e.g. 70ft Curved Silver Screen" required>
                </div>

                <div class="bsc-gallery-field">
                  <label>
                    <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    <?php esc_html_e('Lightbox Description', 'big-screen-cinemas'); ?>
                  </label>
                  <textarea name="gallery_descriptions[]" rows="2" placeholder="<?php esc_attr_e('Brief description displayed on full view...', 'big-screen-cinemas'); ?>"><?php echo esc_textarea($img['description'] ?? ''); ?></textarea>
                </div>
              </div>
            </div>

            <div class="bsc-gallery-card-footer">
              <button type="button" class="bsc-btn-remove-photo" onclick="this.closest('.bsc-gallery-card-item').remove()">
                <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                <span><?php esc_html_e('Delete Photo', 'big-screen-cinemas'); ?></span>
              </button>
            </div>
          </div>
        <?php endforeach; else: ?>
          <p id="empty_gallery_text" style="color:var(--cinema-text-muted); grid-column:1/-1; text-align:center; padding:3.5rem; background:#f8fafc; border:1px dashed var(--cinema-border); border-radius:12px;">
            <?php esc_html_e('No gallery photos found. Click "+ Upload / Add Photos" to add images.', 'big-screen-cinemas'); ?>
          </p>
        <?php endif; ?>
      </div>

      <div style="margin-top:2rem; background:#f8fafc; border-top:1px solid var(--cinema-border); padding:1.25rem; border-radius:12px; display:flex; justify-content:flex-end;">
        <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; padding:9px 24px; font-weight:800; font-size:0.95rem; border-radius:6px; display:inline-flex; align-items:center; gap:6px;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
          <?php esc_html_e('Save Gallery Changes', 'big-screen-cinemas'); ?>
        </button>
      </div>
    </form>

  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const btnUpload = document.getElementById('btn_upload_gallery_img');
      const grid = document.getElementById('bsc_gallery_grid');
      const emptyText = document.getElementById('empty_gallery_text');

      if (btnUpload) {
        btnUpload.addEventListener('click', function(e) {
          e.preventDefault();
          const frame = wp.media({
            title: 'Select or Upload Multiplex Photos',
            button: { text: 'Insert into Gallery' },
            multiple: true,
            library: { type: 'image' }
          });

          frame.on('select', function() {
            const attachments = frame.state().get('selection').toJSON();
            if (emptyText) emptyText.style.display = 'none';

            attachments.forEach(att => {
              const card = document.createElement('div');
              card.className = 'bsc-gallery-card-item';
              
              const defaultTitle = att.title || att.filename || 'Multiplex Visual';
              const defaultCaption = att.caption || att.description || '';

              card.innerHTML = `
                <div>
                  <div class="bsc-gallery-thumb-wrap">
                    <img src="${att.url}" alt="${defaultTitle}">
                    <input type="hidden" name="gallery_urls[]" value="${att.url}">
                  </div>
                  
                  <div class="bsc-gallery-card-body">
                    <div class="bsc-gallery-field">
                      <label>
                        <svg viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                        Category
                      </label>
                      <select name="gallery_categories[]">
                        <option value="screens" selected>Auditoriums & Screens</option>
                        <option value="lounges">VIP Recliners & Lounges</option>
                        <option value="dining">Gourmet Concessions</option>
                      </select>
                    </div>

                    <div class="bsc-gallery-field">
                      <label>
                        <svg viewBox="0 0 24 24"><path d="M5 4v3h5.5v12h3V7H19V4z"/></svg>
                        Title / Heading
                      </label>
                      <input type="text" name="gallery_titles[]" value="${defaultTitle}" placeholder="e.g. 70ft Curved Silver Screen" required>
                    </div>

                    <div class="bsc-gallery-field">
                      <label>
                        <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                        Lightbox Description
                      </label>
                      <textarea name="gallery_descriptions[]" rows="2" placeholder="Brief description displayed on full view...">${defaultCaption}</textarea>
                    </div>
                  </div>
                </div>

                <div class="bsc-gallery-card-footer">
                  <button type="button" class="bsc-btn-remove-photo" onclick="this.closest('.bsc-gallery-card-item').remove()">
                    <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    <span>Delete Photo</span>
                  </button>
                </div>
              `;
              grid.appendChild(card);
            });
          });
          frame.open();
        });
      }
    });
  </script>
  <?php
  bsc_render_page_shell_close();
}