<?php

/**
 * Plugin Name: Website Cost Calculator
 * Plugin URI: https://yourwebsite.com
 * Description: A custom website cost calculator with admin controls
 * Version: 1.0
 * Author: Your Name
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
        wp_enqueue_script('wcc-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.0', true);
    }

    public function enqueue_frontend_scripts()
    {
        wp_enqueue_style('wcc-frontend-style', plugin_dir_url(__FILE__) . 'frontend-style.css');
        wp_enqueue_script('wcc-frontend-script', plugin_dir_url(__FILE__) . 'frontend-script.js', array('jquery'), '1.0', true);

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
                                'user_can_toggle' => isset($_POST['option_user_toggle'][$type_index][$opt_index]) ? 1 : 0
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
                array('name' => 'Site Planning & Strategy', 'hours' => 16, 'price' => 2400, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Onsite SEO Optimization', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Contact Forms & Lead Generation', 'hours' => 8, 'price' => 1200, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Blog Section Integration', 'hours' => 12, 'price' => 1800, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Multi-Language Feature (Per Language)', 'hours' => 15, 'price' => 2250, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Content Migration', 'hours' => 10, 'price' => 1500, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 10, 'price' => 1500, 'default_enabled' => 1, 'user_can_toggle' => 1)
            ),
            'E-Commerce Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Product Catalog Setup & Management', 'hours' => 30, 'price' => 4500, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Payment Gateway Integration', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Shopping Cart & Checkout System', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Inventory Management System', 'hours' => 25, 'price' => 3750, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Customer Account System', 'hours' => 18, 'price' => 2700, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Order Management Dashboard', 'hours' => 20, 'price' => 3000, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Multi-Currency Support', 'hours' => 12, 'price' => 1800, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1)
            ),
            'Portfolio Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 12, 'price' => 1800, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Gallery Design & Development', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Project Showcase Pages', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Filterable Portfolio Grid', 'hours' => 12, 'price' => 1800, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Lightbox Integration', 'hours' => 8, 'price' => 1200, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Motion Graphics & Animations', 'hours' => 15, 'price' => 2250, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 8, 'price' => 1200, 'default_enabled' => 1, 'user_can_toggle' => 1)
            ),
            'Blockchain Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 30, 'price' => 4500, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Wallet Integration', 'hours' => 35, 'price' => 5250, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Smart Contract Integration', 'hours' => 40, 'price' => 6000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'NFT Marketplace Features', 'hours' => 50, 'price' => 7500, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Token Display & Analytics', 'hours' => 25, 'price' => 3750, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Web3 Authentication', 'hours' => 20, 'price' => 3000, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Blockchain Data Visualization', 'hours' => 30, 'price' => 4500, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1)
            ),
            'LMS / Online Course Platforms' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 30, 'price' => 4500, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Course Management System', 'hours' => 40, 'price' => 6000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Student Dashboard & Profile', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Video Hosting & Streaming Integration', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Quiz & Assessment System', 'hours' => 30, 'price' => 4500, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Certificate Generation System', 'hours' => 18, 'price' => 2700, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Progress Tracking & Analytics', 'hours' => 25, 'price' => 3750, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Payment & Subscription Management', 'hours' => 20, 'price' => 3000, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Discussion Forums & Community', 'hours' => 22, 'price' => 3300, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1)
            ),
            'AI-Integrated Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 28, 'price' => 4200, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'AI Chatbot Integration', 'hours' => 35, 'price' => 5250, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Machine Learning Model Integration', 'hours' => 45, 'price' => 6750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Natural Language Processing Features', 'hours' => 40, 'price' => 6000, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'AI-Powered Recommendation System', 'hours' => 38, 'price' => 5700, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Image Recognition & Processing', 'hours' => 35, 'price' => 5250, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Predictive Analytics Dashboard', 'hours' => 30, 'price' => 4500, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'API Integration for AI Services', 'hours' => 25, 'price' => 3750, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1)
            )
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

// Create admin template file content
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
                        
                        <table class="widefat wcc-options-table">
                            <thead>
                                <tr>
                                    <th>Option Name</th>
                                    <th>Hours</th>
                                    <th>Price ($)</th>
                                    <th>Default Enabled</th>
                                    <th>User Can Toggle</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="calculator-options-container" data-type-index="<?php echo $type_index; ?>">
                                <?php 
                                $type_options = isset($calculator_options[$type]) ? $calculator_options[$type] : array();
                                foreach ($type_options as $opt_index => $option): 
                                ?>
                                    <tr class="option-row">
                                        <td><input type="text" name="option_name[<?php echo $type_index; ?>][]" value="<?php echo esc_attr($option['name']); ?>" class="regular-text" required></td>
                                        <td><input type="number" name="option_hours[<?php echo $type_index; ?>][]" value="<?php echo esc_attr($option['hours']); ?>" step="0.1" min="0" required></td>
                                        <td><input type="number" name="option_price[<?php echo $type_index; ?>][]" value="<?php echo esc_attr($option['price']); ?>" step="0.01" min="0" required></td>
                                        <td><input type="checkbox" name="option_default[<?php echo $type_index; ?>][<?php echo $opt_index; ?>]" <?php checked($option['default_enabled'], 1); ?>></td>
                                        <td><input type="checkbox" name="option_user_toggle[<?php echo $type_index; ?>][<?php echo $opt_index; ?>]" <?php checked($option['user_can_toggle'], 1); ?>></td>
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

// Create frontend template
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
            ?>
            <div class="wcc-option-row">
                <div class="wcc-col-select">
                    <label class="wcc-toggle <?php echo $disabled; ?>">
                        <input type="checkbox" 
                               class="wcc-option-checkbox" 
                               data-index="<?php echo $index; ?>"
                               data-hours="<?php echo $option['hours']; ?>"
                               data-price="<?php echo $option['price']; ?>"
                               data-multiply="<?php echo strpos(strtolower($option['name']), 'landing page') !== false ? '1' : '0'; ?>"
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

// Create files on activation
register_activation_hook(__FILE__, 'wcc_activate_plugin');

function wcc_activate_plugin()
{
    // Set default website types
    $default_types = array(
        'Business / Corporate Websites',
        'E-Commerce Websites',
        'Portfolio Websites',
        'Blockchain Websites',
        'LMS / Online Course Platforms',
        'AI-Integrated Websites'
    );

    // Only set if not already exists
    if (!get_option('wcc_website_types')) {
        update_option('wcc_website_types', $default_types);
    }

    // Set default calculator options
    if (!get_option('wcc_calculator_options')) {
        $calculator = new Website_Cost_Calculator();
        $default_options = array(
            'Business / Corporate Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 16, 'price' => 2400, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Onsite SEO Optimization', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Contact Forms & Lead Generation', 'hours' => 8, 'price' => 1200, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Blog Section Integration', 'hours' => 12, 'price' => 1800, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Multi-Language Feature (Per Language)', 'hours' => 15, 'price' => 2250, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Content Migration', 'hours' => 10, 'price' => 1500, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 10, 'price' => 1500, 'default_enabled' => 1, 'user_can_toggle' => 1)
            ),
            'E-Commerce Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Product Catalog Setup & Management', 'hours' => 30, 'price' => 4500, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Payment Gateway Integration', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Shopping Cart & Checkout System', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Inventory Management System', 'hours' => 25, 'price' => 3750, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Customer Account System', 'hours' => 18, 'price' => 2700, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Order Management Dashboard', 'hours' => 20, 'price' => 3000, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Multi-Currency Support', 'hours' => 12, 'price' => 1800, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1)
            ),
            'Portfolio Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 12, 'price' => 1800, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Gallery Design & Development', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Project Showcase Pages', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Filterable Portfolio Grid', 'hours' => 12, 'price' => 1800, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Lightbox Integration', 'hours' => 8, 'price' => 1200, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Motion Graphics & Animations', 'hours' => 15, 'price' => 2250, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 8, 'price' => 1200, 'default_enabled' => 1, 'user_can_toggle' => 1)
            ),
            'Blockchain Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 30, 'price' => 4500, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Wallet Integration', 'hours' => 35, 'price' => 5250, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Smart Contract Integration', 'hours' => 40, 'price' => 6000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'NFT Marketplace Features', 'hours' => 50, 'price' => 7500, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Token Display & Analytics', 'hours' => 25, 'price' => 3750, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Web3 Authentication', 'hours' => 20, 'price' => 3000, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Blockchain Data Visualization', 'hours' => 30, 'price' => 4500, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1)
            ),
            'LMS / Online Course Platforms' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 30, 'price' => 4500, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Course Management System', 'hours' => 40, 'price' => 6000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Student Dashboard & Profile', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Video Hosting & Streaming Integration', 'hours' => 20, 'price' => 3000, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Quiz & Assessment System', 'hours' => 30, 'price' => 4500, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Certificate Generation System', 'hours' => 18, 'price' => 2700, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Progress Tracking & Analytics', 'hours' => 25, 'price' => 3750, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Payment & Subscription Management', 'hours' => 20, 'price' => 3000, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Discussion Forums & Community', 'hours' => 22, 'price' => 3300, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1)
            ),
            'AI-Integrated Websites' => array(
                array('name' => 'Site Planning & Strategy', 'hours' => 28, 'price' => 4200, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Each Unique Landing Page Design & Development', 'hours' => 25, 'price' => 3750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'AI Chatbot Integration', 'hours' => 35, 'price' => 5250, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Machine Learning Model Integration', 'hours' => 45, 'price' => 6750, 'default_enabled' => 1, 'user_can_toggle' => 1),
                array('name' => 'Natural Language Processing Features', 'hours' => 40, 'price' => 6000, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'AI-Powered Recommendation System', 'hours' => 38, 'price' => 5700, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Image Recognition & Processing', 'hours' => 35, 'price' => 5250, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Predictive Analytics Dashboard', 'hours' => 30, 'price' => 4500, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'API Integration for AI Services', 'hours' => 25, 'price' => 3750, 'default_enabled' => 0, 'user_can_toggle' => 1),
                array('name' => 'Project Management & Client Communication', 'hours' => 15, 'price' => 2250, 'default_enabled' => 1, 'user_can_toggle' => 1)
            )
        );
        update_option('wcc_calculator_options', $default_options);
    }

    wcc_create_admin_template();
    wcc_create_frontend_template();

    // Create CSS and JS files
    $plugin_dir = plugin_dir_path(__FILE__);

    // Admin CSS
    file_put_contents($plugin_dir . 'admin-style.css', '
.wcc-admin-wrap { max-width: 1200px; }
.wcc-admin-section { background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc; }
.website-type-row { margin: 10px 0; }
.website-type-row input { width: 300px; margin-right: 10px; }
.wcc-options-table { margin-top: 15px; }
.wcc-options-table th { background: #f5f5f5; padding: 10px; }
.wcc-options-table td { padding: 10px; }
.wcc-options-table input[type="text"],
.wcc-options-table input[type="number"] { width: 100%; }
#website-type-tabs { display: flex; gap: 5px; margin-bottom: 20px; border-bottom: 2px solid #ccc; }
.wcc-tab-button { background: #f5f5f5; border: 1px solid #ccc; border-bottom: none; padding: 10px 20px; cursor: pointer; border-radius: 5px 5px 0 0; }
.wcc-tab-button.active { background: #fff; border-bottom: 2px solid #fff; margin-bottom: -2px; font-weight: bold; }
.wcc-tab-panel { display: none; padding: 20px; background: #fff; border: 1px solid #ccc; }
.wcc-tab-panel.active { display: block; }
    ');

    // Admin JS
    file_put_contents($plugin_dir . 'admin-script.js', '
jQuery(document).ready(function($) {
    var typeCounter = $(".website-type-row").length;
    
    $("#add-website-type").click(function() {
        var newType = "New Type " + (typeCounter + 1);
        $("#website-types-container").append(
            \'<div class="website-type-row"><input type="text" name="website_types[]" value="\' + newType + \'" placeholder="Website Type"><button type="button" class="button remove-type">Remove</button></div>\'
        );
        
        // Add new tab
        var newIndex = typeCounter;
        $("#website-type-tabs").append(
            \'<button type="button" class="wcc-tab-button" data-tab="tab-\' + newIndex + \'">\' + newType + \'</button>\'
        );
        
        // Add new tab panel
        $("#website-type-tab-content").append(
            \'<div class="wcc-tab-panel" id="tab-\' + newIndex + \'">\' +
            \'<h3>\' + newType + \' Options</h3>\' +
            \'<input type="hidden" name="website_type_key[]" value="\' + newType + \'">\' +
            \'<table class="widefat wcc-options-table"><thead><tr>\' +
            \'<th>Option Name</th><th>Hours</th><th>Price ($)</th><th>Default Enabled</th><th>User Can Toggle</th><th>Action</th>\' +
            \'</tr></thead>\' +
            \'<tbody class="calculator-options-container" data-type-index="\' + newIndex + \'"></tbody></table>\' +
            \'<button type="button" class="button add-calculator-option" data-type-index="\' + newIndex + \'">Add Option</button>\' +
            \'</div>\'
        );
        
        typeCounter++;
    });
    
    $(document).on("click", ".remove-type", function() {
        var index = $(this).parent().index();
        $(this).parent().remove();
        $(".wcc-tab-button").eq(index).remove();
        $(".wcc-tab-panel").eq(index).remove();
        
        // Activate first tab if active tab was removed
        if ($(".wcc-tab-button.active").length === 0) {
            $(".wcc-tab-button").first().addClass("active");
            $(".wcc-tab-panel").first().addClass("active");
        }
    });
    
    $(document).on("click", ".wcc-tab-button", function() {
        var tabId = $(this).data("tab");
        $(".wcc-tab-button").removeClass("active");
        $(this).addClass("active");
        $(".wcc-tab-panel").removeClass("active");
        $("#" + tabId).addClass("active");
    });
    
    $(document).on("input", ".website-type-row input", function() {
        var index = $(this).parent().index();
        var newName = $(this).val();
        $(".wcc-tab-button").eq(index).text(newName);
        $(".wcc-tab-panel").eq(index).find("h3").text(newName + " Options");
        $(".wcc-tab-panel").eq(index).find("input[name=\'website_type_key[]\']").val(newName);
    });
    
    $(document).on("click", ".add-calculator-option", function() {
        var typeIndex = $(this).data("type-index");
        var container = $(".calculator-options-container[data-type-index=\'" + typeIndex + "\']");
        var optIndex = container.find("tr").length;
        
        container.append(
            \'<tr class="option-row">\' +
            \'<td><input type="text" name="option_name[\' + typeIndex + \'][]" class="regular-text" required></td>\' +
            \'<td><input type="number" name="option_hours[\' + typeIndex + \'][]" step="0.1" min="0" value="0" required></td>\' +
            \'<td><input type="number" name="option_price[\' + typeIndex + \'][]" step="0.01" min="0" value="0" required></td>\' +
            \'<td><input type="checkbox" name="option_default[\' + typeIndex + \'][\' + optIndex + \']"></td>\' +
            \'<td><input type="checkbox" name="option_user_toggle[\' + typeIndex + \'][\' + optIndex + \']"></td>\' +
            \'<td><button type="button" class="button remove-option">Remove</button></td>\' +
            \'</tr>\'
        );
    });
    
    $(document).on("click", ".remove-option", function() {
        $(this).closest("tr").remove();
    });
});
    ');

    // Frontend CSS - Enhanced with elegant branding
    //file_put_contents($plugin_dir . 'frontend-style.css', '');

    file_put_contents($plugin_dir . 'frontend-script.js', '
jQuery(document).ready(function($) {
    function calculateTotal() {
        var total_hours = 0;
        var total_price = 0;
        var num_pages = parseInt($("#wcc-num-pages").val()) || 1;
        
        $(".wcc-option-checkbox:checked").each(function() {
            var hours = parseFloat($(this).data("hours")) || 0;
            var price = parseFloat($(this).data("price")) || 0;
            var multiply = $(this).data("multiply") == 1;
            
            if (multiply) {
                hours *= num_pages;
                price *= num_pages;
            }
            
            total_hours += hours;
            total_price += price;
        });
        
        $("#wcc-total-hours").text(total_hours);
        $("#wcc-total-price").text(total_price.toFixed(2).replace(/\\B(?=(\\d{3})+(?!\\d))/g, ","));
    }
    
    function loadOptions(websiteType) {
        $("#wcc-options-container").html(\'<div class="wcc-loading">Loading options...</div>\');
        
        $.ajax({
            url: wccAjax.ajaxurl,
            type: "POST",
            data: {
                action: "get_calculator_options",
                nonce: wccAjax.nonce,
                website_type: websiteType
            },
            success: function(response) {
                if (response.success && response.data.options) {
                    var options = response.data.options;
                    var html = "";
                    
                    $.each(options, function(index, option) {
                        var disabled = (!option.user_can_toggle && option.default_enabled) ? "disabled" : "";
                        var checked = option.default_enabled ? "checked" : "";
                        var yesNo = option.default_enabled ? "Yes" : "No";
                        var multiply = option.name.toLowerCase().indexOf("landing page") !== -1 ? "1" : "0";
                        
                        html += \'<div class="wcc-option-row">\';
                        html += \'<div class="wcc-col-select">\';
                        html += \'<label class="wcc-toggle \' + disabled + \'">\';
                        html += \'<input type="checkbox" class="wcc-option-checkbox" data-index="\' + index + \'" \';
                        html += \'data-hours="\' + option.hours + \'" data-price="\' + option.price + \'" \';
                        html += \'data-multiply="\' + multiply + \'" \' + checked + \' \' + disabled + \'>\';
                        html += \'<span class="wcc-toggle-slider"></span>\';
                        html += \'<span class="wcc-toggle-label">\' + yesNo + \'</span>\';
                        html += \'</label></div>\';
                        html += \'<div class="wcc-col-name">\' + option.name + \'</div>\';
                        html += \'<div class="wcc-col-hours">\' + option.hours + \'</div>\';
                        html += \'<div class="wcc-col-price">$\' + parseFloat(option.price).toFixed(2).replace(/\\B(?=(\\d{3})+(?!\\d))/g, ",") + \'</div>\';
                        html += \'</div>\';
                    });
                    
                    $("#wcc-options-container").html(html);
                    calculateTotal();
                } else {
                    $("#wcc-options-container").html(\'<div class="wcc-loading">No options available for this website type.</div>\');
                }
            },
            error: function() {
                $("#wcc-options-container").html(\'<div class="wcc-loading">Error loading options. Please try again.</div>\');
            }
        });
    }
    
    $("#wcc-website-type").change(function() {
        var selectedType = $(this).val();
        $("#wcc-calculator-title").text(selectedType + " Website Quote Calculator");
        loadOptions(selectedType);
    });
    
    $(document).on("change", ".wcc-option-checkbox", function() {
        var label = $(this).closest(".wcc-toggle").find(".wcc-toggle-label");
        label.text($(this).is(":checked") ? "Yes" : "No");
        calculateTotal();
    });
    
    $(".wcc-increase").click(function() {
        var input = $("#wcc-num-pages");
        input.val(parseInt(input.val()) + 1);
        calculateTotal();
    });
    
    $(".wcc-decrease").click(function() {
        var input = $("#wcc-num-pages");
        var val = parseInt(input.val());
        if (val > 1) {
            input.val(val - 1);
            calculateTotal();
        }
    });
    
    $("#wcc-num-pages").on("input", function() {
        if ($(this).val() < 1) $(this).val(1);
        calculateTotal();
    });
    
    // Initial calculation
    calculateTotal();
});
    ');
}

// Initialize the plugin
new Website_Cost_Calculator();
