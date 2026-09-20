<?php
/**
 * Gallery Module - Master Controller & Save Handler
 *
 * Path: admin/page-gallery.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// 1. FORM SUBMISSION PROCESSOR (admin-post.php)
// =============================================================================
add_action( 'admin_post_bsc_save_gallery', 'bsc_handle_save_gallery' );

function bsc_handle_save_gallery() {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized user action.', 'bcs-screen-manager' ), 403 );
    }

    check_admin_referer( 'bsc_save_gallery_verify', 'bsc_gallery_nonce' );

    $raw_urls         = isset( $_POST['gallery_urls'] ) && is_array( $_POST['gallery_urls'] ) ? wp_unslash( $_POST['gallery_urls'] ) : array();
    $raw_categories   = isset( $_POST['gallery_categories'] ) && is_array( $_POST['gallery_categories'] ) ? wp_unslash( $_POST['gallery_categories'] ) : array();
    $raw_titles       = isset( $_POST['gallery_titles'] ) && is_array( $_POST['gallery_titles'] ) ? wp_unslash( $_POST['gallery_titles'] ) : array();
    $raw_descriptions = isset( $_POST['gallery_descriptions'] ) && is_array( $_POST['gallery_descriptions'] ) ? wp_unslash( $_POST['gallery_descriptions'] ) : array();

    $clean_gallery      = array();
    $allowed_categories = array( 'screens', 'lounges', 'dining' );

    $count = count( $raw_urls );
    for ( $i = 0; $i < $count; $i++ ) {
        $url = isset( $raw_urls[ $i ] ) ? esc_url_raw( trim( $raw_urls[ $i ] ) ) : '';
        if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
            continue;
        }

        $category = isset( $raw_categories[ $i ] ) ? sanitize_key( $raw_categories[ $i ] ) : 'screens';
        if ( ! in_array( $category, $allowed_categories, true ) ) {
            $category = 'screens';
        }

        $title       = isset( $raw_titles[ $i ] ) ? sanitize_text_field( $raw_titles[ $i ] ) : '';
        $description = isset( $raw_descriptions[ $i ] ) ? sanitize_textarea_field( $raw_descriptions[ $i ] ) : '';

        $clean_gallery[] = array(
            'url'         => $url,
            'category'    => $category,
            'title'       => $title,
            'description' => $description,
        );
    }

    update_option( 'bsc_gallery_images', $clean_gallery );

    wp_safe_redirect( admin_url( 'admin.php?page=bsc-gallery&updated=1' ) );
    exit;
}

// =============================================================================
// 2. MAIN PAGE RENDER CALLBACK
// =============================================================================
function bsc_render_gallery_page() {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
    }

    // Ensure WordPress Media Uploader scripts and styles are enqueued
    wp_enqueue_media();

    $gallery_images = get_option( 'bsc_gallery_images', array(
        array(
            'url'         => 'https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?w=1200&auto=format&fit=crop&q=85',
            'category'    => 'screens',
            'title'       => '70ft Curved Silver Screen',
            'description' => 'High-frame-rate curved giant screen with dual 4K laser projection.',
        ),
        array(
            'url'         => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1200&auto=format&fit=crop&q=85',
            'category'    => 'lounges',
            'title'       => 'Auditorium Suites',
            'description' => 'Premium auditorium seating with swivel dining tables.',
        ),
        array(
            'url'         => 'https://images.unsplash.com/photo-1585647347483-22b66260dfff?w=1200&auto=format&fit=crop&q=85',
            'category'    => 'dining',
            'title'       => 'Signature Food Combos',
            'description' => 'Chef-prepared hot combos, butter popcorn tubs, and artisan beverages.',
        ),
    ) );

    if ( ! is_array( $gallery_images ) ) {
        $gallery_images = array();
    }

    if ( function_exists( 'bsc_render_page_shell_open' ) ) {
        bsc_render_page_shell_open( 'bsc-gallery' );
    }
    ?>
    <div class="bsc-gallery-mgmt-panel">
        <div class="bsc-panel-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--bsc-border-light, #f1f5f9); padding-bottom:1.25rem;">
            <div>
                <h3 class="bsc-panel-title" style="margin:0; font-size:1.25rem; font-weight:900; display:flex; align-items:center; gap:0.5rem;">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                    <?php esc_html_e( 'Multiplex Gallery Showcase Management', 'bcs-screen-manager' ); ?>
                </h3>
                <span style="font-size:0.82rem; color:var(--bsc-text-muted, #64748b); display:block; margin-top:2px;">
                    <?php esc_html_e( 'Manage luxury auditoriums, laser screen views, auditorium lounges, and gourmet food photo cards.', 'bcs-screen-manager' ); ?>
                </span>
            </div>

            <button type="button" class="button button-primary" id="btn_upload_gallery_img" style="background:#064e3b; border-color:#064e3b; font-weight:800; display:inline-flex; align-items:center; gap:6px; padding:7px 14px;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                <?php esc_html_e( 'Upload / Add Photos', 'bcs-screen-manager' ); ?>
            </button>
        </div>

        <?php if ( isset( $_GET['updated'] ) ) : ?>
            <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                <?php esc_html_e( 'Gallery changes saved and synced live!', 'bcs-screen-manager' ); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'bsc_save_gallery_verify', 'bsc_gallery_nonce' ); ?>
            <input type="hidden" name="action" value="bsc_save_gallery">

            <div id="bsc_gallery_grid" class="bsc-gallery-grid-deck">
                <?php if ( ! empty( $gallery_images ) ) : foreach ( $gallery_images as $img ) : ?>
                    <div class="bsc-gallery-card-item">
                        <div>
                            <div class="bsc-gallery-thumb-wrap">
                                <img src="<?php echo esc_url( $img['url'] ); ?>" alt="<?php echo esc_attr( $img['title'] ?? '' ); ?>">
                                <input type="hidden" name="gallery_urls[]" value="<?php echo esc_url( $img['url'] ); ?>">
                            </div>
                            
                            <div class="bsc-gallery-card-body">
                                <div class="bsc-gallery-field">
                                    <label>
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                                        <?php esc_html_e( 'Category', 'bcs-screen-manager' ); ?>
                                    </label>
                                    <select name="gallery_categories[]">
                                        <option value="screens" <?php selected( $img['category'] ?? '', 'screens' ); ?>><?php esc_html_e( 'Auditoriums & Screens', 'bcs-screen-manager' ); ?></option>
                                        <option value="lounges" <?php selected( $img['category'] ?? '', 'lounges' ); ?>><?php esc_html_e( 'Lounges & Seating', 'bcs-screen-manager' ); ?></option>
                                        <option value="dining" <?php selected( $img['category'] ?? '', 'dining' ); ?>><?php esc_html_e( 'Gourmet Concessions', 'bcs-screen-manager' ); ?></option>
                                    </select>
                                </div>

                                <div class="bsc-gallery-field">
                                    <label>
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4v3h5.5v12h3V7H19V4z"/></svg>
                                        <?php esc_html_e( 'Title / Heading', 'bcs-screen-manager' ); ?>
                                    </label>
                                    <input type="text" name="gallery_titles[]" value="<?php echo esc_attr( $img['title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. 70ft Curved Silver Screen', 'bcs-screen-manager' ); ?>" required>
                                </div>

                                <div class="bsc-gallery-field">
                                    <label>
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                                        <?php esc_html_e( 'Lightbox Description', 'bcs-screen-manager' ); ?>
                                    </label>
                                    <textarea name="gallery_descriptions[]" rows="2" placeholder="<?php esc_attr_e( 'Brief description displayed on full view...', 'bcs-screen-manager' ); ?>"><?php echo esc_textarea( $img['description'] ?? '' ); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bsc-gallery-card-footer">
                            <button type="button" class="bsc-btn-remove-photo">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                <span><?php esc_html_e( 'Delete Photo', 'bcs-screen-manager' ); ?></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; else : ?>
                    <p id="empty_gallery_text" style="color:var(--bsc-text-muted, #64748b); grid-column:1/-1; text-align:center; padding:3.5rem; background:#f8fafc; border:1px dashed var(--bsc-border, #e2e8f0); border-radius:12px;">
                        <?php esc_html_e( 'No gallery photos found. Click "+ Upload / Add Photos" to add images.', 'bcs-screen-manager' ); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div style="margin-top:2rem; background:#f8fafc; border-top:1px solid var(--bsc-border, #e2e8f0); padding:1.25rem; border-radius:12px; display:flex; justify-content:flex-end;">
                <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; padding:9px 24px; font-weight:800; font-size:0.95rem; border-radius:6px; display:inline-flex; align-items:center; gap:6px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                    <?php esc_html_e( 'Save Gallery Changes', 'bcs-screen-manager' ); ?>
                </button>
            </div>
        </form>
    </div>

    <?php
    $gallery_version = defined( 'BSC_VERSION' ) ? BSC_VERSION : '1.0.0';
    wp_register_script( 'bsc-gallery-runtime', false, array(), $gallery_version, true );
    wp_enqueue_script( 'bsc-gallery-runtime' );

    $gallery_i18n = array(
        'mediaTitle'       => __( 'Select or Upload Multiplex Photos', 'bcs-screen-manager' ),
        'mediaBtn'         => __( 'Insert into Gallery', 'bcs-screen-manager' ),
        'category'         => __( 'Category', 'bcs-screen-manager' ),
        'catScreens'       => __( 'Auditoriums & Screens', 'bcs-screen-manager' ),
        'catLounges'       => __( 'Lounges & Seating', 'bcs-screen-manager' ),
        'catDining'        => __( 'Gourmet Concessions', 'bcs-screen-manager' ),
        'titleLabel'       => __( 'Title / Heading', 'bcs-screen-manager' ),
        'descLabel'        => __( 'Lightbox Description', 'bcs-screen-manager' ),
        'deleteBtn'        => __( 'Delete Photo', 'bcs-screen-manager' ),
        'titlePlaceholder' => __( 'e.g. 70ft Curved Silver Screen', 'bcs-screen-manager' ),
        'descPlaceholder'  => __( 'Brief description displayed on full view...', 'bcs-screen-manager' ),
        'defaultTitle'     => __( 'Multiplex Visual', 'bcs-screen-manager' ),
    );

    $js_script = '
    (function() {
        const i18n = ' . wp_json_encode( $gallery_i18n ) . ';

        document.addEventListener("DOMContentLoaded", function() {
            const btnUpload = document.getElementById("btn_upload_gallery_img");
            const grid      = document.getElementById("bsc_gallery_grid");
            const emptyText = document.getElementById("empty_gallery_text");

            if (grid) {
                grid.addEventListener("click", function(e) {
                    const removeBtn = e.target.closest(".bsc-btn-remove-photo");
                    if (removeBtn) {
                        const card = removeBtn.closest(".bsc-gallery-card-item");
                        if (card) {
                            card.remove();
                            if (grid.querySelectorAll(".bsc-gallery-card-item").length === 0 && emptyText) {
                                emptyText.style.display = "block";
                            }
                        }
                    }
                });
            }

            if (btnUpload) {
                btnUpload.addEventListener("click", function(e) {
                    e.preventDefault();

                    if (typeof wp === "undefined" || !wp.media) {
                        return;
                    }

                    const frame = wp.media({
                        title: i18n.mediaTitle,
                        button: { text: i18n.mediaBtn },
                        multiple: true,
                        library: { type: "image" }
                    });

                    frame.on("select", function() {
                        const attachments = frame.state().get("selection").toJSON();
                        if (emptyText) {
                            emptyText.style.display = "none";
                        }

                        attachments.forEach(function(att) {
                            const card = document.createElement("div");
                            card.className = "bsc-gallery-card-item";

                            const thumbWrap = document.createElement("div");
                            thumbWrap.className = "bsc-gallery-thumb-wrap";

                            const img = document.createElement("img");
                            img.src = att.url;
                            img.alt = att.title || att.filename || i18n.defaultTitle;

                            const hiddenUrl = document.createElement("input");
                            hiddenUrl.type = "hidden";
                            hiddenUrl.name = "gallery_urls[]";
                            hiddenUrl.value = att.url;

                            thumbWrap.appendChild(img);
                            thumbWrap.appendChild(hiddenUrl);

                            const cardBody = document.createElement("div");
                            cardBody.className = "bsc-gallery-card-body";

                            // Category field
                            const catField = document.createElement("div");
                            catField.className = "bsc-gallery-field";
                            const catLabel = document.createElement("label");
                            catLabel.textContent = i18n.category;
                            const catSelect = document.createElement("select");
                            catSelect.name = "gallery_categories[]";

                            const opt1 = new Option(i18n.catScreens, "screens", true, true);
                            const opt2 = new Option(i18n.catLounges, "lounges");
                            const opt3 = new Option(i18n.catDining, "dining");
                            catSelect.add(opt1);
                            catSelect.add(opt2);
                            catSelect.add(opt3);

                            catField.appendChild(catLabel);
                            catField.appendChild(catSelect);

                            // Title field
                            const titleField = document.createElement("div");
                            titleField.className = "bsc-gallery-field";
                            const titleLabel = document.createElement("label");
                            titleLabel.textContent = i18n.titleLabel;
                            const titleInput = document.createElement("input");
                            titleInput.type = "text";
                            titleInput.name = "gallery_titles[]";
                            titleInput.value = att.title || att.filename || i18n.defaultTitle;
                            titleInput.placeholder = i18n.titlePlaceholder;
                            titleInput.required = true;

                            titleField.appendChild(titleLabel);
                            titleField.appendChild(titleInput);

                            // Description field
                            const descField = document.createElement("div");
                            descField.className = "bsc-gallery-field";
                            const descLabel = document.createElement("label");
                            descLabel.textContent = i18n.descLabel;
                            const descText = document.createElement("textarea");
                            descText.name = "gallery_descriptions[]";
                            descText.rows = 2;
                            descText.placeholder = i18n.descPlaceholder;
                            descText.value = att.caption || att.description || "";

                            descField.appendChild(descLabel);
                            descField.appendChild(descText);

                            cardBody.appendChild(catField);
                            cardBody.appendChild(titleField);
                            cardBody.appendChild(descField);

                            const topWrap = document.createElement("div");
                            topWrap.appendChild(thumbWrap);
                            topWrap.appendChild(cardBody);

                            // Footer & Delete button
                            const cardFooter = document.createElement("div");
                            cardFooter.className = "bsc-gallery-card-footer";
                            const removeBtn = document.createElement("button");
                            removeBtn.type = "button";
                            removeBtn.className = "bsc-btn-remove-photo";

                            const removeSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                            removeSvg.setAttribute("viewBox", "0 0 24 24");
                            removeSvg.setAttribute("aria-hidden", "true");
                            const removePath = document.createElementNS("http://www.w3.org/2000/svg", "path");
                            removePath.setAttribute("d", "M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z");
                            removeSvg.appendChild(removePath);

                            const removeSpan = document.createElement("span");
                            removeSpan.textContent = i18n.deleteBtn;

                            removeBtn.appendChild(removeSvg);
                            removeBtn.appendChild(removeSpan);
                            cardFooter.appendChild(removeBtn);

                            card.appendChild(topWrap);
                            card.appendChild(cardFooter);

                            grid.appendChild(card);
                        });
                    });

                    frame.open();
                });
            }
        });
    })();
    ';

    wp_add_inline_script( 'bsc-gallery-runtime', $js_script );

    if ( function_exists( 'bsc_render_page_shell_close' ) ) {
        bsc_render_page_shell_close();
    }
}