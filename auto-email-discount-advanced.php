<?php
/**
 * Plugin Name:       Auto Email Discount for WooCommerce (Domains & Specific Emails - Advanced)
 * Plugin URI:        https://example.com/plugins/auto-email-discount/
 * Description:       Automatically applies a user-defined discount for users with specific email domains OR specific email addresses in WooCommerce, with per-item discount rates, LearnDash course category discounts, and one-time options.
 * Version:           1.8.0
 * Author:            shalom
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       auto-email-discount
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * WC requires at least: 3.0.0
 * WC tested up to: latest
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'AED_PLUGIN_FILE', __FILE__ );
define( 'AED_PLUGIN_SLUG', 'auto_email_discount_settings_adv' );
define( 'AED_OPTION_NAME', 'aed_settings_adv' );
define( 'AED_USED_EMAILS_OPTION', 'aed_used_emails_adv' );

/** Display notice if WooCommerce is not active. */
function aed_woocommerce_not_active_notice_adv() {
    ?>
    <div class="error">
        <p><?php _e( 'Auto Email Discount for WooCommerce (Advanced) requires WooCommerce to be activated.', 'auto-email-discount' ); ?></p>
    </div>
    <?php
}

/** Plugin default settings */
function aed_get_default_settings_adv() {
    return array(
        'enabled'                   => false,
        'domain_discounts'          => array(),
        'specific_email_discounts'  => array(),
        'course_category_discounts' => array(),
        'discount_label'            => __( 'Special Discount', 'auto-email-discount' ),
    );
}

/** Plugin activation: set default options */
function aed_activate_adv() {
    if ( false === get_option( AED_OPTION_NAME ) ) {
        add_option( AED_OPTION_NAME, aed_get_default_settings_adv() );
    }
    if ( false === get_option( AED_USED_EMAILS_OPTION ) ) {
        add_option( AED_USED_EMAILS_OPTION, array() );
    }
}
register_activation_hook( AED_PLUGIN_FILE, 'aed_activate_adv' );

/** Initialize the plugin */
function aed_plugin_init_adv() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'aed_woocommerce_not_active_notice_adv' );
        return;
    }
    load_plugin_textdomain( 'auto-email-discount', false, dirname( plugin_basename( AED_PLUGIN_FILE ) ) . '/languages' );
    add_action( 'admin_menu', 'aed_add_admin_menu_adv' );
    add_action( 'admin_init', 'aed_register_settings_adv' );
    add_action( 'admin_enqueue_scripts', 'aed_enqueue_admin_scripts_adv' );
    add_action( 'woocommerce_cart_calculate_fees', 'aed_apply_email_based_discount_adv', 20, 1 );
    add_action( 'wp_footer', 'aed_trigger_update_checkout_on_email_change_adv' );

    add_action( 'woocommerce_checkout_create_order', 'aed_add_one_time_meta_to_order_adv', 10, 2 );
    add_action( 'woocommerce_order_status_processing', 'aed_record_used_email_on_order_complete_adv', 10, 1 );
    add_action( 'woocommerce_order_status_completed', 'aed_record_used_email_on_order_complete_adv', 10, 1 );
}
add_action( 'plugins_loaded', 'aed_plugin_init_adv' );

/** Enqueue admin scripts for settings page */
function aed_enqueue_admin_scripts_adv( $hook_suffix ) {
    if ( 'settings_page_' . AED_PLUGIN_SLUG !== $hook_suffix ) { return; }

    // JS Version updated to 1.8.0
    wp_enqueue_script( 'aed-admin-script', plugins_url( 'admin-script.js', AED_PLUGIN_FILE ), array( 'jquery' ), '1.8.0', true );

    // Localize script with course categories
    $course_categories = array();
    if ( taxonomy_exists( 'ld_course_category' ) ) {
        $terms = get_terms( array(
            'taxonomy'   => 'ld_course_category',
            'hide_empty' => false,
        ) );
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                $course_categories[] = array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                );
            }
        }
    }

    wp_localize_script( 'aed-admin-script', 'aedData', array(
        'courseCategories' => $course_categories,
    ) ); 
    
    $custom_css = "
        .aed-repeater-item { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; background: #f9f9f9; } 
        .aed-repeater-item label { margin-right: 10px; display: inline-block; vertical-align: top; }
        .aed-repeater-item .aed-product-ids-input { width: 120px; }
        .aed-repeater-item .aed-product-note-input { width: 150px; }
        .aed-search-wrapper { margin-bottom: 15px; }
        .aed-search-wrapper label { font-weight: 600; margin-right: 5px; }
        #aed-email-search { width: 300px; }
        .wp-list-table th { padding-left: 10px !important; }
        .wp-list-table td { vertical-align: middle; padding: 8px 10px; }
        .wp-list-table input[type=\"number\"] { width: 70px; }
        .wp-list-table input[type=\"email\"] { width: 100%; max-width: 250px; }
        .wp-list-table .aed-product-ids-input { width: 100%; max-width: 120px; }
        .wp-list-table .aed-product-note-input { width: 100%; max-width: 200px; }
        .aed-helper-link { font-style: italic; font-size: 12px; }
    ";
    wp_add_inline_style( 'wp-admin', $custom_css );
}

/** Add admin menu */
function aed_add_admin_menu_adv() {
    add_options_page( __( 'Advanced Email Discount Settings', 'auto-email-discount' ), __( 'Adv. Email Discount', 'auto-email-discount' ), 'manage_options', AED_PLUGIN_SLUG, 'aed_settings_page_callback_adv' );
}

/** Settings page content callback */
function aed_settings_page_callback_adv() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php settings_fields( AED_PLUGIN_SLUG ); do_settings_sections( AED_PLUGIN_SLUG ); submit_button( __( 'Save Settings', 'auto-email-discount' ) ); ?>
        </form>
    </div>
    <?php
}

/** Register settings and fields */
function aed_register_settings_adv() {
    register_setting( AED_PLUGIN_SLUG, AED_OPTION_NAME, 'aed_sanitize_settings_adv' ); 

    add_settings_section( 'aed_general_section_adv', __( 'General Settings', 'auto-email-discount' ), null, AED_PLUGIN_SLUG );
    add_settings_field( 'aed_enabled_adv', __( 'Enable Discount Feature', 'auto-email-discount' ), 'aed_render_enabled_field_adv', AED_PLUGIN_SLUG, 'aed_general_section_adv' );
    add_settings_field( 'aed_discount_label_adv', __( 'Discount Label in Cart', 'auto-email-discount' ), 'aed_render_discount_label_field_adv', AED_PLUGIN_SLUG, 'aed_general_section_adv' );
    
    add_settings_section( 'aed_domain_discounts_section_adv', __( 'Domain-Based Discounts', 'auto-email-discount' ), null, AED_PLUGIN_SLUG );
    add_settings_field( 'aed_domain_discounts_adv', __( 'Discount Rules by Domain', 'auto-email-discount' ), 'aed_render_domain_discounts_field_adv', AED_PLUGIN_SLUG, 'aed_domain_discounts_section_adv' );

    add_settings_section( 'aed_specific_email_discounts_section_adv', __( 'Specific Email Discounts', 'auto-email-discount' ), null, AED_PLUGIN_SLUG );
    add_settings_field( 'aed_specific_email_discounts_adv', __( 'Discount Rules for Specific Emails', 'auto-email-discount' ), 'aed_render_specific_email_discounts_field_adv', AED_PLUGIN_SLUG, 'aed_specific_email_discounts_section_adv' );

    add_settings_section( 'aed_course_category_discounts_section_adv', __( 'LearnDash Course Category Discounts', 'auto-email-discount' ), null, AED_PLUGIN_SLUG );
    add_settings_field( 'aed_course_category_discounts_adv', __( 'Discount Rules by Course Category', 'auto-email-discount' ), 'aed_render_course_category_discounts_field_adv', AED_PLUGIN_SLUG, 'aed_course_category_discounts_section_adv' );
}

/** Helper to get option values */
function aed_get_option_value_adv( $key, $default_override = null ) {
    $options = get_option( AED_OPTION_NAME, aed_get_default_settings_adv() );
    $default_settings = aed_get_default_settings_adv();
    $default_value = $default_override !== null ? $default_override : (isset($default_settings[$key]) ? $default_settings[$key] : '');
    return isset( $options[ $key ] ) ? $options[ $key ] : $default_value;
}

/** Render General Settings Fields */
function aed_render_enabled_field_adv() {
    $value = aed_get_option_value_adv( 'enabled' );
    echo '<label><input type="checkbox" name="' . esc_attr( AED_OPTION_NAME ) . '[enabled]" value="1" ' . checked( 1, $value, false ) . ' /> ' . __( 'Enable this discount feature.', 'auto-email-discount' ) . '</label>';
}
function aed_render_discount_label_field_adv() {
    $value = aed_get_option_value_adv( 'discount_label' );
    echo '<input type="text" name="' . esc_attr( AED_OPTION_NAME ) . '[discount_label]" value="' . esc_attr( $value ) . '" class="regular-text" />';
    echo '<p class="description">' . __( 'This base label will be shown in the cart (e.g., Special Discount). The specific percentage will be appended.', 'auto-email-discount' ) . '</p>';
}

/** Render Repeater Fields for Domains (with Admin Note) */
function aed_render_domain_discounts_field_adv() {
    $domain_discounts = aed_get_option_value_adv( 'domain_discounts', array() );
    ?>
    <div id="aed-domain-discounts-repeater">
        <?php if ( ! empty( $domain_discounts ) && is_array( $domain_discounts ) ) : ?>
            <?php foreach ( $domain_discounts as $index => $rule ) : if (is_array($rule) && isset($rule['domain']) && isset($rule['percentage'])) : ?>
                <div class="aed-repeater-item">
                    <label><?php _e( 'Domain:', 'auto-email-discount' ); ?><br/>
                        <input type="text" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[domain_discounts][<?php echo $index; ?>][domain]" value="<?php echo esc_attr( $rule['domain'] ); ?>" placeholder="example.com" />
                    </label>
                    <label><?php _e( 'Percentage (%):', 'auto-email-discount' ); ?><br/>
                        <input type="number" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[domain_discounts][<?php echo $index; ?>][percentage]" value="<?php echo esc_attr( $rule['percentage'] ); ?>" min="0" max="100" step="0.01" style="width: 70px;" />
                    </label>
                    <label><?php _e( 'One-time:', 'auto-email-discount' ); ?><br/>
                        <input type="checkbox" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[domain_discounts][<?php echo $index; ?>][one_time]" value="1" <?php checked( 1, isset( $rule['one_time'] ) ? $rule['one_time'] : 0 ); ?> />
                        <?php _e( 'One-time only', 'auto-email-discount' ); ?>
                    </label>
                    <label><?php _e( 'Product IDs:', 'auto-email-discount' ); ?><br/>
                        <input type="text" class="aed-product-ids-input" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[domain_discounts][<?php echo $index; ?>][product_ids]" value="<?php echo esc_attr( isset( $rule['product_ids'] ) ? $rule['product_ids'] : '' ); ?>" placeholder="e.g. 101, 105" />
                    </label>
                    <label><?php _e( 'Admin Note:', 'auto-email-discount' ); ?><br/>
                        <input type="text" class="aed-product-note-input" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[domain_discounts][<?php echo $index; ?>][product_note]" value="<?php echo esc_attr( isset( $rule['product_note'] ) ? $rule['product_note'] : '' ); ?>" placeholder="e.g. Intro Course" />
                    </label>
                    <button type="button" class="button aed-remove-rule-domain"><?php _e( 'Remove', 'auto-email-discount' ); ?></button>
                </div>
            <?php endif; endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" id="aed-add-domain-rule" class="button"><?php _e( 'Add Domain Rule', 'auto-email-discount' ); ?></button>
    <p class="description"><?php _e( 'Add email domains and their specific discount percentages.', 'auto-email-discount' ); ?></p>
    <p class="aed-helper-link">
        <?php _e( 'Enter comma-separated Product IDs. Leave blank to apply to entire cart.', 'auto-email-discount' ); ?>
        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" target="_blank"><?php _e( '[ Open Product ID list in new tab ]', 'auto-email-discount' ); ?></a>
    </p>
    <?php
}

/** Render Repeater Fields for Specific Emails (with Admin Note) */
function aed_render_specific_email_discounts_field_adv() {
    $specific_email_discounts = aed_get_option_value_adv( 'specific_email_discounts', array() );
    ?>
    <div class="aed-search-wrapper">
        <label for="aed-email-search"><?php _e( 'Search Rules:', 'auto-email-discount' ); ?></label> <input type="search" id="aed-email-search" class="regular-text" placeholder="<?php _e( 'Type to filter by Email, %, IDs, or Note...', 'auto-email-discount' ); ?>"> </div>

    <table class="wp-list-table widefat striped" id="aed-specific-email-table">
        <thead>
            <tr>
                <th scope="col"><?php _e( 'Email', 'auto-email-discount' ); ?></th>
                <th scope="col" style="width: 100px;"><?php _e( 'Percentage (%)', 'auto-email-discount' ); ?></th>
                <th scope="col" style="width: 120px;"><?php _e( 'One-time only', 'auto-email-discount' ); ?></th>
                <th scope="col" style="width: 130px;"><?php _e( 'Apply to Product IDs', 'auto-email-discount' ); ?></th>
                <th scope="col" style="width: 200px;"><?php _e( 'Admin Note', 'auto-email-discount' ); ?></th>
                <th scope="col" style="width: 80px;"><?php _e( 'Actions', 'auto-email-discount' ); ?></th>
            </tr>
        </thead>
        <tbody id="aed-specific-email-discounts-tbody">
            <?php if ( ! empty( $specific_email_discounts ) && is_array( $specific_email_discounts ) ) : ?>
                <?php foreach ( $specific_email_discounts as $index => $rule ) : if (is_array($rule) && isset($rule['email']) && isset($rule['percentage'])) : ?>
                    <tr>
                        <td>
                            <input type="email" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[specific_email_discounts][<?php echo $index; ?>][email]" value="<?php echo esc_attr( $rule['email'] ); ?>" placeholder="user@example.com" />
                        </td>
                        <td>
                            <input type="number" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[specific_email_discounts][<?php echo $index; ?>][percentage]" value="<?php echo esc_attr( $rule['percentage'] ); ?>" min="0" max="100" step="0.01" />
                        </td>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[specific_email_discounts][<?php echo $index; ?>][one_time]" value="1" <?php checked( 1, isset( $rule['one_time'] ) ? $rule['one_time'] : 0 ); ?> />
                                <?php _e( 'Yes', 'auto-email-discount' ); ?>
                            </label>
                        </td>
                        <td>
                            <input type="text" class="aed-product-ids-input" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[specific_email_discounts][<?php echo $index; ?>][product_ids]" value="<?php echo esc_attr( isset( $rule['product_ids'] ) ? $rule['product_ids'] : '' ); ?>" placeholder="e.g. 101, 105" />
                        </td>
                        <td>
                            <input type="text" class="aed-product-note-input" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[specific_email_discounts][<?php echo $index; ?>][product_note]" value="<?php echo esc_attr( isset( $rule['product_note'] ) ? $rule['product_note'] : '' ); ?>" placeholder="e.g. Intro Course" />
                        </td>
                        <td>
                            <button type="button" class="button aed-remove-rule"><?php _e( 'Remove', 'auto-email-discount' ); ?></button>
                        </td>
                    </tr>
                <?php endif; endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <button type="button" id="aed-add-specific-email-rule" class="button" style="margin-top: 10px;"><?php _e( 'Add Specific Email Rule', 'auto-email-discount' ); ?></button>
    <p class="description"><?php _e( 'Add specific email addresses and their specific discount percentages. List will be auto-sorted by domain, then ID, on save.', 'auto-email-discount' ); ?></p>
    <p class="aed-helper-link">
        <?php _e( 'Enter comma-separated Product IDs. Leave blank to apply to entire cart.', 'auto-email-discount' ); ?>
        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" target="_blank"><?php _e( '[ Open Product ID list in new tab ]', 'auto-email-discount' ); ?></a>
    </p>
    <?php
}

/** Render Repeater Fields for Course Category Discounts */
function aed_render_course_category_discounts_field_adv() {
    $course_category_discounts = aed_get_option_value_adv( 'course_category_discounts', array() );

    // Get LearnDash course categories
    $course_categories = array();
    if ( taxonomy_exists( 'ld_course_category' ) ) {
        $terms = get_terms( array(
            'taxonomy'   => 'ld_course_category',
            'hide_empty' => false,
        ) );
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $course_categories = $terms;
        }
    }
    ?>
    <div id="aed-course-category-discounts-repeater">
        <?php if ( ! empty( $course_category_discounts ) && is_array( $course_category_discounts ) ) : ?>
            <?php foreach ( $course_category_discounts as $index => $rule ) : if (is_array($rule) && isset($rule['category_id']) && isset($rule['percentage'])) : ?>
                <div class="aed-repeater-item">
                    <label><?php _e( 'Course Category:', 'auto-email-discount' ); ?><br/>
                        <select name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[course_category_discounts][<?php echo $index; ?>][category_id]" style="min-width: 200px;">
                            <option value=""><?php _e( '-- Select Category --', 'auto-email-discount' ); ?></option>
                            <?php foreach ( $course_categories as $term ) : ?>
                                <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $rule['category_id'], $term->term_id ); ?>>
                                    <?php echo esc_html( $term->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><?php _e( 'Percentage (%):', 'auto-email-discount' ); ?><br/>
                        <input type="number" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[course_category_discounts][<?php echo $index; ?>][percentage]" value="<?php echo esc_attr( $rule['percentage'] ); ?>" min="0" max="100" step="0.01" style="width: 70px;" />
                    </label>
                    <label><?php _e( 'One-time:', 'auto-email-discount' ); ?><br/>
                        <input type="checkbox" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[course_category_discounts][<?php echo $index; ?>][one_time]" value="1" <?php checked( 1, isset( $rule['one_time'] ) ? $rule['one_time'] : 0 ); ?> />
                        <?php _e( 'One-time only', 'auto-email-discount' ); ?>
                    </label>
                    <label><?php _e( 'Admin Note:', 'auto-email-discount' ); ?><br/>
                        <input type="text" class="aed-category-note-input" name="<?php echo esc_attr( AED_OPTION_NAME ); ?>[course_category_discounts][<?php echo $index; ?>][category_note]" value="<?php echo esc_attr( isset( $rule['category_note'] ) ? $rule['category_note'] : '' ); ?>" placeholder="e.g. Beginner Courses" style="width: 150px;" />
                    </label>
                    <button type="button" class="button aed-remove-rule-category"><?php _e( 'Remove', 'auto-email-discount' ); ?></button>
                </div>
            <?php endif; endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" id="aed-add-category-rule" class="button"><?php _e( 'Add Category Rule', 'auto-email-discount' ); ?></button>
    <p class="description"><?php _e( 'Add LearnDash course categories and their specific discount percentages. Discounts will apply to all courses in the selected category.', 'auto-email-discount' ); ?></p>
    <?php if ( empty( $course_categories ) ) : ?>
        <p class="notice notice-warning inline" style="padding: 10px;">
            <?php _e( 'No LearnDash course categories found. Please ensure LearnDash is installed and course categories are created.', 'auto-email-discount' ); ?>
        </p>
    <?php endif; ?>
    <?php
}

/** Helper function for sorting email rules by domain, then user ID. */
function aed_sort_email_rules_callback( $a, $b ) {
    if ( ! isset( $a['email'] ) || ! isset( $b['email'] ) ) { return 0; }
    $parts_a = explode( '@', $a['email'], 2 );
    $parts_b = explode( '@', $b['email'], 2 );
    if ( count( $parts_a ) !== 2 || count( $parts_b ) !== 2 ) { return strcmp( $a['email'], $b['email'] ); }
    list( $user_a, $domain_a ) = $parts_a;
    list( $user_b, $domain_b ) = $parts_b;
    $domain_cmp = strcmp( $domain_a, $domain_b );
    if ( $domain_cmp !== 0 ) { return $domain_cmp; }
    return strcmp( $user_a, $user_b );
}

/** Sanitize and validate settings before saving (with Admin Note) */
function aed_sanitize_settings_adv( $input ) {
    $new_input = array();
    $default_settings = aed_get_default_settings_adv();

    $new_input['enabled'] = isset( $input['enabled'] ) ? 1 : 0;
    $new_input['discount_label'] = isset( $input['discount_label'] ) ? sanitize_text_field( $input['discount_label'] ) : $default_settings['discount_label'];
    if ( empty( $new_input['discount_label'] ) ) {
        $new_input['discount_label'] = $default_settings['discount_label'];
    }

    // Sanitize domain discounts
    $new_input['domain_discounts'] = array();
    if ( isset( $input['domain_discounts'] ) && is_array( $input['domain_discounts'] ) ) {
        foreach ( $input['domain_discounts'] as $rule_candidate ) {
            if ( is_array( $rule_candidate ) &&
                 ! empty( $rule_candidate['domain'] ) &&
                 isset( $rule_candidate['percentage'] ) && $rule_candidate['percentage'] !== '' ) {

                $domain_value = strtolower( sanitize_text_field( trim( $rule_candidate['domain'] ) ) );
                $percentage_value = trim( $rule_candidate['percentage'] );

                if ( !empty($domain_value) && preg_match( '/^([a-z0-9]+(?:-[a-z0-9]+)*\.)+[a-z]{2,}$/i', $domain_value ) === 1 ) {
                    $new_input['domain_discounts'][] = array(
                        'domain'     => $domain_value,
                        'percentage' => max( 0, min( 100, floatval( $percentage_value ) ) ),
                        'one_time'   => isset( $rule_candidate['one_time'] ) ? 1 : 0,
                        'product_ids' => isset( $rule_candidate['product_ids'] ) ? sanitize_text_field( $rule_candidate['product_ids'] ) : '',
                        'product_note' => isset( $rule_candidate['product_note'] ) ? sanitize_text_field( $rule_candidate['product_note'] ) : '',
                    );
                }
            }
        }
    }

    // Sanitize specific email discounts
    $new_input['specific_email_discounts'] = array();
    if ( isset( $input['specific_email_discounts'] ) && is_array( $input['specific_email_discounts'] ) ) {
        foreach ( $input['specific_email_discounts'] as $rule_candidate ) {
            if ( is_array( $rule_candidate ) &&
                 ! empty( $rule_candidate['email'] ) &&
                 isset( $rule_candidate['percentage'] ) && $rule_candidate['percentage'] !== '' ) {

                $email_value = strtolower( sanitize_email( trim( $rule_candidate['email'] ) ) );
                $percentage_value = trim( $rule_candidate['percentage'] );
                
                if ( is_email( $email_value ) ) { 
                    $new_input['specific_email_discounts'][] = array(
                        'email'      => $email_value,
                        'percentage' => max( 0, min( 100, floatval( $percentage_value ) ) ),
                        'one_time'   => isset( $rule_candidate['one_time'] ) ? 1 : 0,
                        'product_ids' => isset( $rule_candidate['product_ids'] ) ? sanitize_text_field( $rule_candidate['product_ids'] ) : '',
                        'product_note' => isset( $rule_candidate['product_note'] ) ? sanitize_text_field( $rule_candidate['product_note'] ) : '',
                    );
                }
            }
        }
    }
    
    if ( ! empty( $new_input['specific_email_discounts'] ) ) {
        usort( $new_input['specific_email_discounts'], 'aed_sort_email_rules_callback' );
    }

    // Sanitize course category discounts
    $new_input['course_category_discounts'] = array();
    if ( isset( $input['course_category_discounts'] ) && is_array( $input['course_category_discounts'] ) ) {
        foreach ( $input['course_category_discounts'] as $rule_candidate ) {
            if ( is_array( $rule_candidate ) &&
                 ! empty( $rule_candidate['category_id'] ) &&
                 isset( $rule_candidate['percentage'] ) && $rule_candidate['percentage'] !== '' ) {

                $category_id = intval( $rule_candidate['category_id'] );
                $percentage_value = trim( $rule_candidate['percentage'] );

                // Verify the category exists
                if ( $category_id > 0 && term_exists( $category_id, 'ld_course_category' ) ) {
                    $new_input['course_category_discounts'][] = array(
                        'category_id'   => $category_id,
                        'percentage'    => max( 0, min( 100, floatval( $percentage_value ) ) ),
                        'one_time'      => isset( $rule_candidate['one_time'] ) ? 1 : 0,
                        'category_note' => isset( $rule_candidate['category_note'] ) ? sanitize_text_field( $rule_candidate['category_note'] ) : '',
                    );
                }
            }
        }
    }

    return $new_input;
}


/** Core discount logic (No change from v1.7.0) */
function aed_apply_email_based_discount_adv( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) { return; }

    if ( WC()->session ) {
        WC()->session->set( 'aed_is_one_time_discount', null );
    }

    $settings = get_option( AED_OPTION_NAME, aed_get_default_settings_adv() );
    if ( empty( $settings['enabled'] ) || ! $settings['enabled'] ) { return; }

    $domain_discounts = ! empty( $settings['domain_discounts'] ) && is_array($settings['domain_discounts']) ? $settings['domain_discounts'] : array();
    $specific_email_discounts = ! empty( $settings['specific_email_discounts'] ) && is_array($settings['specific_email_discounts']) ? $settings['specific_email_discounts'] : array();
    $course_category_discounts = ! empty( $settings['course_category_discounts'] ) && is_array($settings['course_category_discounts']) ? $settings['course_category_discounts'] : array();
    $base_discount_label = ! empty( $settings['discount_label'] ) ? $settings['discount_label'] : __( 'Special Discount', 'auto-email-discount' );

    if ( empty( $domain_discounts ) && empty( $specific_email_discounts ) && empty( $course_category_discounts ) ) { return; }

    $user_email_raw = '';
    if ( is_user_logged_in() ) {
        $user_email_raw = wp_get_current_user()->user_email;
    } elseif ( WC()->customer && WC()->customer->get_billing_email() ) {
        $user_email_raw = WC()->customer->get_billing_email();
    } elseif ( isset( $_POST['billing_email']) && !empty( $_POST['billing_email'] ) ) {
         $user_email_raw = sanitize_email( wp_unslash( $_POST['billing_email'] ) );
    }

    if ( empty( $user_email_raw ) ) { return; }

    $user_email_lc = strtolower( $user_email_raw );
    $found_discount_percentage = 0;
    $found_is_one_time = false; 
    $found_product_ids_string = ''; 

    $used_emails = get_option( AED_USED_EMAILS_OPTION, array() );

    $fees = $cart->get_fees();
    foreach($fees as $fee_obj) {
        if (strpos($fee_obj->name, $base_discount_label) === 0 && strpos($fee_obj->name, '%') !== false) {
            return; 
        }
    }

    // 1. Check Specific Emails
    if ( !empty( $specific_email_discounts ) ) {
        foreach ( $specific_email_discounts as $rule ) {
            if (isset($rule['email']) && isset($rule['percentage'])) {
                if ( $user_email_lc === $rule['email'] ) {
                    $is_one_time = isset( $rule['one_time'] ) ? $rule['one_time'] : 0;
                    
                    if ( $is_one_time && in_array( $user_email_lc, $used_emails ) ) {
                        $found_discount_percentage = 0; 
                        $found_is_one_time = false;
                        break; 
                    }
                    
                    $found_discount_percentage = floatval( $rule['percentage'] );
                    $found_is_one_time = $is_one_time;
                    $found_product_ids_string = isset( $rule['product_ids'] ) ? $rule['product_ids'] : '';
                    break; 
                }
            }
        }
    }

    // 2. Check Domain Emails
    if ( $found_discount_percentage <= 0 && !empty( $domain_discounts ) ) {
        $user_email_domain_parts = explode( '@', $user_email_lc );
        if (count($user_email_domain_parts) > 1) {
            $user_domain = array_pop( $user_email_domain_parts );
            foreach ( $domain_discounts as $rule ) {
                 if (isset($rule['domain']) && isset($rule['percentage'])) {
                    if ( $user_domain === $rule['domain'] ) {
                        $is_one_time = isset( $rule['one_time'] ) ? $rule['one_time'] : 0;

                        if ( $is_one_time && in_array( $user_email_lc, $used_emails ) ) {
                            $found_discount_percentage = 0;
                            $found_is_one_time = false;
                            break;
                        }

                        $found_discount_percentage = floatval( $rule['percentage'] );
                        $found_is_one_time = $is_one_time;
                        $found_product_ids_string = isset( $rule['product_ids'] ) ? $rule['product_ids'] : '';
                        break;
                    }
                }
            }
        }
    }

    // 3. Check Course Category Discounts
    $found_category_id = 0;
    if ( $found_discount_percentage <= 0 && !empty( $course_category_discounts ) ) {
        // Check if any cart items are LearnDash courses in the configured categories
        foreach ( $cart->get_cart() as $cart_item ) {
            $product_id = $cart_item['product_id'];

            // Check if this product is a LearnDash course
            $course_id = get_post_meta( $product_id, '_related_course', true );
            if ( empty( $course_id ) ) {
                // Some setups link products directly to courses, check alternative method
                $course_id = $product_id;
                if ( get_post_type( $course_id ) !== 'sfwd-courses' ) {
                    continue; // Not a course
                }
            }

            // Get course categories
            $course_terms = wp_get_post_terms( $course_id, 'ld_course_category', array( 'fields' => 'ids' ) );
            if ( is_wp_error( $course_terms ) || empty( $course_terms ) ) {
                continue;
            }

            // Check if course belongs to any configured category
            foreach ( $course_category_discounts as $rule ) {
                if ( isset( $rule['category_id'] ) && isset( $rule['percentage'] ) ) {
                    if ( in_array( $rule['category_id'], $course_terms ) ) {
                        $is_one_time = isset( $rule['one_time'] ) ? $rule['one_time'] : 0;

                        if ( $is_one_time && in_array( $user_email_lc, $used_emails ) ) {
                            continue; // Skip this rule
                        }

                        $found_discount_percentage = floatval( $rule['percentage'] );
                        $found_is_one_time = $is_one_time;
                        $found_category_id = $rule['category_id'];
                        break 2; // Break out of both loops
                    }
                }
            }
        }
    }

    // 4. Apply the fee
    if ( $found_discount_percentage > 0 ) {

        $discount_base_amount = 0;

        if ( ! empty( $found_product_ids_string ) ) {
            // Product-specific discount
            $ids = explode( ',', $found_product_ids_string );
            $ids = array_map( 'trim', $ids );
            $ids = array_map( 'intval', $ids );
            $ids = array_filter( $ids, function($id) { return $id > 0; } );

            if ( ! empty( $ids ) ) {
                foreach ( $cart->get_cart() as $cart_item ) {
                    if ( in_array( $cart_item['product_id'], $ids ) || ( $cart_item['variation_id'] && in_array( $cart_item['variation_id'], $ids ) ) ) {
                        $discount_base_amount += $cart_item['line_subtotal'];
                    }
                }
            }
        } elseif ( $found_category_id > 0 ) {
            // Category-specific discount - apply only to courses in this category
            foreach ( $cart->get_cart() as $cart_item ) {
                $product_id = $cart_item['product_id'];

                // Check if this product is a LearnDash course
                $course_id = get_post_meta( $product_id, '_related_course', true );
                if ( empty( $course_id ) ) {
                    $course_id = $product_id;
                    if ( get_post_type( $course_id ) !== 'sfwd-courses' ) {
                        continue;
                    }
                }

                // Check if course belongs to the discount category
                $course_terms = wp_get_post_terms( $course_id, 'ld_course_category', array( 'fields' => 'ids' ) );
                if ( ! is_wp_error( $course_terms ) && ! empty( $course_terms ) && in_array( $found_category_id, $course_terms ) ) {
                    $discount_base_amount += $cart_item['line_subtotal'];
                }
            }
        } else {
            // Cart-wide discount
            $discount_base_amount = $cart->get_subtotal();
        }

        $discount_amount = round( ( $discount_base_amount * $found_discount_percentage ) / 100, wc_get_price_decimals() );

        if ( $discount_amount > 0 ) {
            if ( $found_is_one_time && WC()->session ) {
                WC()->session->set( 'aed_is_one_time_discount', true );
            }
            
            $final_discount_label = $base_discount_label . ' (' . $found_discount_percentage . '%)';
            $cart->add_fee( $final_discount_label, -$discount_amount, true );
        }
    }
}

/** Save a flag to the order if a one-time discount was used. */
function aed_add_one_time_meta_to_order_adv( $order, $data ) {
    if ( WC()->session && WC()->session->get( 'aed_is_one_time_discount' ) ) {
        $order->update_meta_data( '_aed_was_one_time', true );
        WC()->session->set( 'aed_is_one_time_discount', null );
    }
}

/** Record the email address when an order with a one-time discount is processed/completed. */
function aed_record_used_email_on_order_complete_adv( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) { return; }
    $was_one_time = $order->get_meta( '_aed_was_one_time' );
    if ( $was_one_time ) {
        $email = $order->get_billing_email();
        if ( ! empty( $email ) ) {
            $email_lc = strtolower( $email );
            $used_emails = get_option( AED_USED_EMAILS_OPTION, array() );
            if ( ! in_array( $email_lc, $used_emails ) ) {
                $used_emails[] = $email_lc;
                update_option( AED_USED_EMAILS_OPTION, $used_emails );
            }
        }
    }
}


/** AJAX update trigger on email change (UX) */
function aed_trigger_update_checkout_on_email_change_adv() {
    $settings = get_option( AED_OPTION_NAME, aed_get_default_settings_adv() );
    if ( empty( $settings['enabled'] ) || ! $settings['enabled'] ) { return; }

    if ( function_exists('is_checkout') && is_checkout() && ! is_wc_endpoint_url() ) : ?>
    <script type="text/javascript">
        jQuery( function($){
            $('body').on('blur change', 'input#billing_email', function(){
                $(document.body).trigger('update_checkout');
            });
        });
    </script>
    <?php endif;
}
?>