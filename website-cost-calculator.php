<?php

/**
 * Plugin Name: Website Cost Calculator
 * Plugin URI: https://yourwebsite.com
 * Description: A custom website cost calculator with admin controls
 * Version: 1.1
 * Author: Krishna Wishvajith
 * Author URI: https://yourwebsite.com
 * License: GPL2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Website_Cost_Calculator
{

    public function __construct()
    {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Shortcode
        add_shortcode('website_calculator', array($this, 'calculator_shortcode'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // AJAX handlers
        add_action('wp_ajax_get_calculator_options', array($this, 'get_calculator_options_ajax'));
        add_action('wp_ajax_nopriv_get_calculator_options', array($this, 'get_calculator_options_ajax'));
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Website Calculator',
            'Website Calculator',
            'manage_options',
            'website-calculator',
            array($this, 'admin_page'),
            'dashicons-calculator',
            30
        );
    }

    public function register_settings()
    {
        register_setting('wcc_settings_group', 'wcc_website_types');
        register_setting('wcc_settings_group', 'wcc_calculator_options');
    }

    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'toplevel_page_website-calculator') {
            return;
        }

        wp_enqueue_style('wcc-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_script('wcc-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.1', true);
    }

    public function enqueue_frontend_scripts()
    {
        wp_enqueue_style('wcc-frontend-style', plugin_dir_url(__FILE__) . 'frontend-style.css');
        wp_enqueue_script('wcc-frontend-script', plugin_dir_url(__FILE__) . 'frontend-script.js', array('jquery'), '1.1', true);

        wp_localize_script('wcc-frontend-script', 'wccAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcc_nonce')
        ));
    }

    public function admin_page()
    {
        // Handle form submission
        if (isset($_POST['wcc_save_settings']) && check_admin_referer('wcc_save_settings', 'wcc_nonce')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        $website_types = get_option('wcc_website_types', array('Business / Corporate Websites', 'E-Commerce Websites', 'Portfolio Websites', 'Blockchain Websites', 'LMS / Online Course Platforms', 'AI-Integrated Websites'));
        $calculator_options = get_option('wcc_calculator_options', $this->get_default_options());

        include plugin_dir_path(__FILE__) . 'admin-template.php';
    }

    private function save_settings()
    {
        // Save website types
        if (isset($_POST['website_types'])) {
            $website_types = array_map('sanitize_text_field', $_POST['website_types']);
            update_option('wcc_website_types', $website_types);
        }

        // Save calculator options for each website type
        $all_options = array();
        if (isset($_POST['website_type_key'])) {
            foreach ($_POST['website_type_key'] as $type_index => $website_type) {
                $options = array();

                if (isset($_POST['option_name'][$type_index]) && is_array($_POST['option_name'][$type_index])) {
                    foreach ($_POST['option_name'][$type_index] as $opt_index => $name) {
                        if (!empty($name)) {
                            $options[] = array(
                                'name' => sanitize_text_field($name),
                                'hours' => floatval($_POST['option_hours'][$type_index][$opt_index]),
                                'price' => floatval($_POST['option_price'][$type_index][$opt_index]),
                                'default_enabled' => isset($_POST['option_default'][$type_index][$opt_index]) ? 1 : 0,
                                'user_can_toggle' => isset($_POST['option_user_toggle'][$type_index][$opt_index]) ? 1 : 0,
                                'is_base_field' => isset($_POST['option_base_field'][$type_index][$opt_index]) ? 1 : 0,
                                'additional_hours' => floatval($_POST['option_additional_hours'][$type_index][$opt_index]),
                                'additional_price' => floatval($_POST['option_additional_price'][$type_index][$opt_index])
                            );
                        }
                    }
                }

                $all_options[sanitize_text_field($website_type)] = $options;
            }
        }

        update_option('wcc_calculator_options', $all_options);
    }

    private function get_default_options()
    {
        return array(
            'Business / Corporate Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 16, 'price' => 2400, 'default_enabled' => 1, 'user_can_toggle' => 1, 'is_base_field' => 1, 'additional_hours' => 5, 'additional_price' => 750),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Onsite SEO Optimization', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1, 'is_base_field' => 1, 'additional_hours' => 8, 'additional_price' => 1200),
                array('name' => 'Contact Forms & Lead Generation', 'hours' => 8, 'price' => 1200, 'default_enabled' => 0, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Blog Section Integration', 'hours' => 12, 'price' => 1800, 'default_enabled' => 0, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Multi-Language Feature (Per Language)', 'hours' => 15, 'price' => 2250, 'default_enabled' => 0, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Content Migration', 'hours' => 10, 'price' => 1500, 'default_enabled' => 0, 'user_can_toggle' => 1, 'is_base_field' => 1, 'additional_hours' => 3, 'additional_price' => 450),
                array('name' => 'Project Management & Client Communication', 'hours' => 10, 'price' => 1500, 'default_enabled' => 1, 'user_can_toggle' => 1, 'is_base_field' => 1, 'additional_hours' => 4, 'additional_price' => 600)
            ),
            'E-Commerce Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1, 'is_base_field' => 1, 'additional_hours' => 6, 'additional_price' => 900),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Product Catalog Setup & Management', 'hours' => 30, 'price' => 4500, 'default_enabled' => 1, 'user_can_toggle' => 1, 'is_base_field' => 1, 'additional_hours' => 10, 'additional_price' => 1500),
                array('name' => 'Payment Gateway Integration', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Shopping Cart & Checkout System', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Inventory Management System', 'hours' => 25, 'price' => 3750, 'default_enabled' => 0, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Customer Account System', 'hours' => 18, 'price' => 2700, 'default_enabled' => 0, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Order Management Dashboard', 'hours' => 20, 'price' => 3000, 'default_enabled' => 0, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Multi-Currency Support', 'hours' => 12, 'price' => 1800, 'default_enabled' => 0, 'user_can_toggle' => 1, 'is_base_field' => 0, 'additional_hours' => 0, 'additional_price' => 0),
                array('name' => 'Project Management & Client Communication', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1, 'is_base_field' => 1, 'additional_hours' => 5, 'additional_price' => 750)
            ),
            // Add similar structure for other website types...
        );
    }

    public function calculator_shortcode($atts)
    {
        $website_types = get_option('wcc_website_types', array('Business / Corporate Websites', 'E-Commerce Websites', 'Portfolio Websites', 'Blockchain Websites', 'LMS / Online Course Platforms', 'AI-Integrated Websites'));
        $calculator_options = get_option('wcc_calculator_options', $this->get_default_options());

        // Get options for first website type
        $first_type = reset($website_types);
        $current_options = isset($calculator_options[$first_type]) ? $calculator_options[$first_type] : array();

        ob_start();
        include plugin_dir_path(__FILE__) . 'frontend-template.php';
        return ob_get_clean();
    }

    public function get_calculator_options_ajax()
    {
        check_ajax_referer('wcc_nonce', 'nonce');

        $website_type = isset($_POST['website_type']) ? sanitize_text_field($_POST['website_type']) : '';
        $calculator_options = get_option('wcc_calculator_options', $this->get_default_options());

        $options = isset($calculator_options[$website_type]) ? $calculator_options[$website_type] : array();

        wp_send_json_success(array('options' => $options));
    }
}

// Initialize the plugin
new Website_Cost_Calculator();

// Activation hook
register_activation_hook(__FILE__, 'wcc_activate_plugin');

function wcc_activate_plugin()
{
    // Create template and script files
    wcc_create_admin_template();
    wcc_create_frontend_template();
    
    // Set defaults if not exist
    if (!get_option('wcc_website_types')) {
        update_option('wcc_website_types', array(
            'Business / Corporate Websites',
            'E-Commerce Websites',
            'Portfolio Websites',
            'Blockchain Websites',
            'LMS / Online Course Platforms',
            'AI-Integrated Websites'
        ));
    }
}

function wcc_create_admin_template()
{
    $content = <<<'PHP'
<div class="wrap wcc-admin-wrap">
    <h1>Website Cost Calculator Settings</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wcc_save_settings', 'wcc_nonce'); ?>
        
        <div class="wcc-admin-section">
            <h2>Website Types</h2>
            <div id="website-types-container">
                <?php foreach ($website_types as $index => $type): ?>
                    <div class="website-type-row">
                        <input type="text" name="website_types[]" value="<?php echo esc_attr($type); ?>" placeholder="Website Type" readonly>
                        <button type="button" class="button remove-type">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button" id="add-website-type">Add Website Type</button>
        </div>
        
        <div class="wcc-admin-section">
            <h2>Calculator Options by Website Type</h2>
            <div id="website-type-tabs">
                <?php foreach ($website_types as $type_index => $type): ?>
                    <button type="button" class="wcc-tab-button <?php echo $type_index === 0 ? 'active' : ''; ?>" data-tab="tab-<?php echo $type_index; ?>">
                        <?php echo esc_html($type); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div id="website-type-tab-content">
                <?php foreach ($website_types as $type_index => $type): ?>
                    <div class="wcc-tab-panel <?php echo $type_index === 0 ? 'active' : ''; ?>" id="tab-<?php echo $type_index; ?>">
                        <h3><?php echo esc_html($type); ?> Options</h3>
                        <input type="hidden" name="website_type_key[]" value="<?php echo esc_attr($type); ?>">
                        
                        <div class="wcc-help-text">
                            <strong>Base Field:</strong> Check this if the option cost should increase per landing page. 
                            <br>Example: If pages = 2, total = Base Hours/Price + Additional Hours/Price
                        </div>
                        
                        <table class="widefat wcc-options-table">
                            <thead>
                                <tr>
                                    <th style="width: 200px;">Option Name</th>
                                    <th style="width: 80px;">Base Hours</th>
                                    <th style="width: 100px;">Base Price ($)</th>
                                    <th style="width: 80px;">Default On</th>
                                    <th style="width: 80px;">User Toggle</th>
                                    <th style="width: 80px;">Base Field</th>
                                    <th style="width: 90px;">Add. Hours/Page</th>
                                    <th style="width: 100px;">Add. Price/Page ($)</th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody class="calculator-options-container" data-type-index="<?php echo $type_index; ?>">
                                <?php 
                                $type_options = isset($calculator_options[$type]) ? $calculator_options[$type] : array();
                                foreach ($type_options as $opt_index => $option): 
                                    // Ensure new fields exist
                                    $is_base_field = isset($option['is_base_field']) ? $option['is_base_field'] : 0;
                                    $additional_hours = isset($option['additional_hours']) ? $option['additional_hours'] : 0;
                                    $additional_price = isset($option['additional_price']) ? $option['additional_price'] : 0;
                                ?>
                                    <tr class="option-row">
                                        <td><input type="text" name="option_name[<?php echo $type_index; ?>][]" value="<?php echo esc_attr($option['name']); ?>" class="regular-text" required></td>
                                        <td><input type="number" name="option_hours[<?php echo $type_index; ?>][]" value="<?php echo esc_attr($option['hours']); ?>" step="0.1" min="0" required></td>
                                        <td><input type="number" name="option_price[<?php echo $type_index; ?>][]" value="<?php echo esc_attr($option['price']); ?>" step="0.01" min="0" required></td>
                                        <td><input type="checkbox" name="option_default[<?php echo $type_index; ?>][<?php echo $opt_index; ?>]" <?php checked($option['default_enabled'], 1); ?>></td>
                                        <td><input type="checkbox" name="option_user_toggle[<?php echo $type_index; ?>][<?php echo $opt_index; ?>]" <?php checked($option['user_can_toggle'], 1); ?>></td>
                                        <td><input type="checkbox" class="base-field-checkbox" name="option_base_field[<?php echo $type_index; ?>][<?php echo $opt_index; ?>]" <?php checked($is_base_field, 1); ?>></td>
                                        <td><input type="number" class="additional-field" name="option_additional_hours[<?php echo $type_index; ?>][]" value="<?php echo esc_attr($additional_hours); ?>" step="0.1" min="0" <?php echo $is_base_field ? '' : 'disabled'; ?>></td>
                                        <td><input type="number" class="additional-field" name="option_additional_price[<?php echo $type_index; ?>][]" value="<?php echo esc_attr($additional_price); ?>" step="0.01" min="0" <?php echo $is_base_field ? '' : 'disabled'; ?>></td>
                                        <td><button type="button" class="button remove-option">Remove</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="button" class="button add-calculator-option" data-type-index="<?php echo $type_index; ?>">Add Option</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="wcc_save_settings" class="button-primary" value="Save Settings">
        </p>
    </form>
</div>
PHP;

    file_put_contents(plugin_dir_path(__FILE__) . 'admin-template.php', $content);
}

function wcc_create_frontend_template()
{
    $content = <<<'PHP'
<div class="wcc-calculator">
    <div class="wcc-header">
        <div class="wcc-input-group">
            <label style="color: #ffffff">Website Type</label>
            <select id="wcc-website-type">
                <?php foreach ($website_types as $type): ?>
                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="wcc-input-group">
            <label style="color: #ffffff">No. Of Unique Landing Pages</label>
            <div class="wcc-number-input">
                <button type="button" class="wcc-decrease">-</button>
                <input type="number" id="wcc-num-pages" value="1" min="1">
                <button type="button" class="wcc-increase">+</button>
            </div>
        </div>
    </div>
    
    <h2 class="wcc-title" id="wcc-calculator-title"><?php echo esc_html(reset($website_types)); ?> Website Quote Calculator</h2>
    
    <div class="wcc-options-header">
        <div class="wcc-col-select">SELECT:</div>
        <div class="wcc-col-name"></div>
        <div class="wcc-col-hours">HOURS:</div>
        <div class="wcc-col-price">PRICE:</div>
    </div>
    
    <div class="wcc-options" id="wcc-options-container">
        <?php foreach ($current_options as $index => $option): ?>
            <?php 
            $disabled = (!$option['user_can_toggle'] && $option['default_enabled']) ? 'disabled' : '';
            $checked = $option['default_enabled'] ? 'checked' : '';
            $is_base_field = isset($option['is_base_field']) ? $option['is_base_field'] : 0;
            $additional_hours = isset($option['additional_hours']) ? $option['additional_hours'] : 0;
            $additional_price = isset($option['additional_price']) ? $option['additional_price'] : 0;
            $multiply = strpos(strtolower($option['name']), 'landing page') !== false ? '1' : '0';
            ?>
            <div class="wcc-option-row">
                <div class="wcc-col-select">
                    <label class="wcc-toggle <?php echo $disabled; ?>">
                        <input type="checkbox" 
                               class="wcc-option-checkbox" 
                               data-index="<?php echo $index; ?>"
                               data-hours="<?php echo $option['hours']; ?>"
                               data-price="<?php echo $option['price']; ?>"
                               data-multiply="<?php echo $multiply; ?>"
                               data-base-field="<?php echo $is_base_field; ?>"
                               data-additional-hours="<?php echo $additional_hours; ?>"
                               data-additional-price="<?php echo $additional_price; ?>"
                               <?php echo $checked; ?>
                               <?php echo $disabled; ?>>
                        <span class="wcc-toggle-slider"></span>
                        <span class="wcc-toggle-label"><?php echo $checked ? 'Yes' : 'No'; ?></span>
                    </label>
                </div>
                <div class="wcc-col-name"><?php echo esc_html($option['name']); ?></div>
                <div class="wcc-col-hours option-hours"><?php echo $option['hours']; ?></div>
                <div class="wcc-col-price option-price">$<?php echo number_format($option['price'], 2); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="wcc-total">
        <div class="wcc-total-label">Estimated Price:</div>
        <div class="wcc-total-hours"><span id="wcc-total-hours">0</span>h</div>
        <div class="wcc-total-price">$<span id="wcc-total-price">0.00</span></div>
    </div>
</div>
PHP;

    file_put_contents(plugin_dir_path(__FILE__) . 'frontend-template.php', $content);
}