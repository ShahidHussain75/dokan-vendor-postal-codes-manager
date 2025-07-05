<?php
/**
 * Plugin Name: Dokan Vendor Postal Codes Manager
 * Description: Allows admin to manage postal codes and vendors to select their delivery areas
 * Version: 1.0.0
 * Author: Shahid Hussain
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create postal codes table on plugin activation
 */
function vpc_create_postal_codes_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vendor_postal_codes';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        postal_code varchar(10) NOT NULL,
        area_name varchar(100) NOT NULL,
        state varchar(100) DEFAULT '',
        csv_batch varchar(50) DEFAULT NULL,
        csv_imported_at datetime DEFAULT NULL,
        status tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Add state column if it doesn't exist
    $check_state = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'state'");
    if (empty($check_state)) {
        $wpdb->query("ALTER TABLE $table_name ADD state varchar(100) DEFAULT ''");
    }

    // Add CSV batch tracking columns if they don't exist
    $check_csv_batch = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'csv_batch'");
    if (empty($check_csv_batch)) {
        $wpdb->query("ALTER TABLE $table_name ADD csv_batch varchar(50) DEFAULT NULL");
        $wpdb->query("ALTER TABLE $table_name ADD csv_imported_at datetime DEFAULT NULL");
    }
}

/**
 * Add default cuisines on plugin activation
 */
function vpc_add_default_cuisines() {
    $default_cuisines = array(
        'Burger' => array(
            'description' => 'Delicious burgers and sandwiches',
            'image' => 'burger.jpg'
        ),
        'Pizza' => array(
            'description' => 'Italian pizzas and pasta',
            'image' => 'pizza.jpg'
        ),
        'Chinese' => array(
            'description' => 'Traditional Chinese cuisine',
            'image' => 'chinese.jpg'
        ),
        'Indian' => array(
            'description' => 'Authentic Indian dishes',
            'image' => 'indian.jpg'
        ),
        'Mexican' => array(
            'description' => 'Spicy Mexican food',
            'image' => 'mexican.jpg'
        ),
        'Sushi' => array(
            'description' => 'Japanese sushi and rolls',
            'image' => 'sushi.jpg'
        ),
        'Thai' => array(
            'description' => 'Thai specialties',
            'image' => 'thai.jpg'
        ),
        'Mediterranean' => array(
            'description' => 'Mediterranean delicacies',
            'image' => 'mediterranean.jpg'
        ),
        'Fast Food' => array(
            'description' => 'Quick and tasty fast food',
            'image' => 'fast-food.jpg'
        ),
        'Healthy' => array(
            'description' => 'Healthy and nutritious options',
            'image' => 'healthy.jpg'
        ),
        'Desserts' => array(
            'description' => 'Sweet treats and desserts',
            'image' => 'desserts.jpg'
        ),
        'Beverages' => array(
            'description' => 'Drinks and refreshments',
            'image' => 'beverages.jpg'
        )
    );

    foreach ($default_cuisines as $cuisine_name => $cuisine_data) {
        if (!term_exists($cuisine_name, 'cuisine')) {
            $term = wp_insert_term($cuisine_name, 'cuisine', array(
                'description' => $cuisine_data['description']
            ));

            if (!is_wp_error($term)) {
                // You can add custom term meta here if needed
                // add_term_meta($term['term_id'], 'cuisine_image', $cuisine_data['image']);
            }
        }
    }
}

/**
 * Drop tables on plugin deactivation
 */
function vpc_deactivate() {
    // Do nothing on deactivation to preserve data
}

register_activation_hook(__FILE__, 'vpc_create_postal_codes_table');
register_activation_hook(__FILE__, 'vpc_add_default_cuisines');
register_deactivation_hook(__FILE__, 'vpc_deactivate');

// Add uninstall hook for clean removal if needed
register_uninstall_hook(__FILE__, 'vpc_uninstall');

/**
 * Clean up plugin data only when uninstalling
 */
function vpc_uninstall() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vendor_postal_codes';
    
    // Only drop tables when explicitly uninstalling
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    // Clean up any related user meta
    delete_metadata('user', 0, '_vendor_postal_codes', '', true);
    delete_metadata('user', 0, '_vendor_postal_prices', '', true);
}

/**
 * Add admin menu for postal codes management
 */
function vpc_add_admin_menu() {
    add_menu_page(
        'Manage Postal Codes',
        'Postal Codes',
        'manage_options',
        'manage-postal-codes',
        'vpc_postal_codes_page',
        'dashicons-location',
        30
    );
}
add_action('admin_menu', 'vpc_add_admin_menu');

/**
 * List all postal codes in a table
 */
function vpc_list_postal_codes() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vendor_postal_codes';
    
    // Handle edit form submission
    if (isset($_POST['edit_postal_code']) && check_admin_referer('edit_postal_code_nonce')) {
        $id = intval($_POST['postal_code_id']);
        $postal_code = sanitize_text_field($_POST['postal_code']);
        $area_name = sanitize_text_field($_POST['area_name']);
        $state = sanitize_text_field($_POST['state']);
        
        $wpdb->update(
            $table_name,
            array(
                'postal_code' => $postal_code,
                'area_name' => $area_name,
                'state' => $state
            ),
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        echo '<div class="notice notice-success"><p>Postal code updated successfully!</p></div>';
    }

    // Get the postal code to edit
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $editing_code = null;
    if ($edit_id) {
        $editing_code = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
    }
    
    $postal_codes = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    
    <?php if ($editing_code): ?>
        <!-- Edit Form -->
        <div class="edit-form-container" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h3>Edit Postal Code</h3>
            <form method="post" action="">
                <?php wp_nonce_field('edit_postal_code_nonce'); ?>
                <input type="hidden" name="postal_code_id" value="<?php echo esc_attr($editing_code->id); ?>">
                <table class="form-table">
                    <tr>
                        <th><label for="postal_code">Postal Code</label></th>
                        <td>
                            <input type="text" name="postal_code" id="postal_code" value="<?php echo esc_attr($editing_code->postal_code); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="area_name">Area Name</label></th>
                        <td>
                            <input type="text" name="area_name" id="area_name" value="<?php echo esc_attr($editing_code->area_name); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="state">State/Province</label></th>
                        <td>
                            <input type="text" name="state" id="state" value="<?php echo esc_attr($editing_code->state); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="edit_postal_code" class="button button-primary" value="Update Postal Code">
                    <a href="?page=manage-postal-codes" class="button">Cancel</a>
                </p>
            </form>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Postal Code</th>
                <th>Area Name</th>
                <th>State/Province</th>
                <th>Import Batch</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($postal_codes): ?>
                <?php foreach ($postal_codes as $code): ?>
                    <tr>
                        <td><?php echo esc_html($code->postal_code); ?></td>
                        <td><?php echo esc_html($code->area_name); ?></td>
                        <td><?php echo esc_html($code->state ?? ''); ?></td>
                        <td>
                            <?php 
                            if (isset($code->csv_batch) && !empty($code->csv_batch)) {
                                echo esc_html(date('F j, Y', strtotime($code->csv_imported_at)));
                            } else {
                                echo 'Manual Entry';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="?page=manage-postal-codes&edit=<?php echo esc_attr($code->id); ?>" 
                               class="button button-small">
                                Edit
                            </a>
                            <a href="?page=manage-postal-codes&action=delete&id=<?php echo esc_attr($code->id); ?>" 
                               class="button button-small button-link-delete"
                               onclick="return confirm('Are you sure you want to delete this postal code?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No postal codes found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <style>
    .edit-form-container {
        max-width: 800px;
    }
    .form-table th {
        width: 150px;
    }
    .button-small {
        margin-right: 5px !important;
    }
    </style>
    <?php
}

/**
 * Admin page for managing postal codes
 */
function vpc_postal_codes_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vendor_postal_codes';
    
    // Handle CSV import
    if (isset($_POST['import_csv']) && isset($_FILES['csv_file']) && check_admin_referer('import_csv_nonce')) {
        // Increase execution time and memory limit
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '256M');
        
        $file = $_FILES['csv_file'];
        if ($file['type'] == 'text/csv' || $file['type'] == 'application/vnd.ms-excel') {
            try {
                $handle = fopen($file['tmp_name'], 'r');
                if ($handle === false) {
                    throw new Exception('Could not open CSV file');
                }
                
                $imported = 0;
                $batch_id = 'csv_' . date('YmdHis');
                $batch_size = 100; // Process 100 records at a time
                $values = array();
                
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    if (count($data) >= 2) {
                        $area_name = sanitize_text_field($data[0]);
                        $postal_code = sanitize_text_field($data[1]);
                        $state = isset($data[2]) ? sanitize_text_field($data[2]) : '';
                        
                        $values[] = $wpdb->prepare(
                            "(%s, %s, %s, %s, %s)",
                            $postal_code,
                            $area_name,
                            $state,
                            $batch_id,
                            current_time('mysql')
                        );
                        
                        // Insert in batches
                        if (count($values) >= $batch_size) {
                            $query = "INSERT INTO $table_name 
                                     (postal_code, area_name, state, csv_batch, csv_imported_at) 
                                     VALUES " . implode(", ", $values);
                            
                            $wpdb->query($query);
                            $imported += count($values);
                            $values = array();
                            
                            // Give the server a tiny break
                            usleep(100000); // 0.1 second pause
                        }
                    }
                }
                
                // Insert any remaining records
                if (!empty($values)) {
                    $query = "INSERT INTO $table_name 
                             (postal_code, area_name, state, csv_batch, csv_imported_at) 
                             VALUES " . implode(", ", $values);
                    
                    $wpdb->query($query);
                    $imported += count($values);
                }
                
                fclose($handle);
                echo '<div class="notice notice-success"><p>Successfully imported ' . $imported . ' postal codes!</p></div>';
                
            } catch (Exception $e) {
                echo '<div class="notice notice-error"><p>Error importing CSV: ' . esc_html($e->getMessage()) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Please upload a valid CSV file!</p></div>';
        }
    }

    // Handle CSV batch deletion
    if (isset($_POST['delete_batch']) && isset($_POST['batch_id']) && check_admin_referer('delete_batch_nonce')) {
        $batch_id = sanitize_text_field($_POST['batch_id']);
        $wpdb->delete($table_name, array('csv_batch' => $batch_id));
        echo '<div class="notice notice-success"><p>CSV batch deleted successfully!</p></div>';
    }

    // Handle single postal code addition
    if (isset($_POST['add_postal_code']) && check_admin_referer('add_postal_code_nonce')) {
        $postal_code = sanitize_text_field($_POST['postal_code']);
        $area_name = sanitize_text_field($_POST['area_name']);
        $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
        
        $wpdb->insert(
            $table_name,
            array(
                'postal_code' => $postal_code,
                'area_name' => $area_name,
                'state' => $state
            ),
            array('%s', '%s', '%s')
        );
        
        echo '<div class="notice notice-success"><p>Postal code added successfully!</p></div>';
    }

    // Handle single postal code deletion
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $wpdb->delete(
            $table_name,
            array('id' => $_GET['id']),
            array('%d')
        );
        
        echo '<div class="notice notice-success"><p>Postal code deleted successfully!</p></div>';
    }

    // Get CSV batches
    $csv_batches = $wpdb->get_results("
        SELECT csv_batch, 
               COUNT(*) as record_count, 
               MIN(csv_imported_at) as imported_at
        FROM $table_name 
        WHERE csv_batch IS NOT NULL 
        GROUP BY csv_batch 
        ORDER BY csv_imported_at DESC
    ");
    ?>
    <div class="wrap">
        <h1>Manage Postal Codes</h1>
        
        <!-- CSV Import Form -->
        <div class="postbox">
            <h2 class="hndle"><span>Import Postal Codes from CSV</span></h2>
            <div class="inside">
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('import_csv_nonce'); ?>
                    <p>Upload a CSV file with format: Area Name;Postal Code;State</p>
                    <p>Example: Zwochau;4509;Sachsen</p>
                    <input type="file" name="csv_file" accept=".csv" required>
                    <input type="submit" name="import_csv" class="button button-primary" value="Import CSV">
                </form>
            </div>
        </div>

        <!-- CSV Batches -->
        <?php if (!empty($csv_batches)): ?>
        <div class="postbox">
            <h2 class="hndle"><span>Imported CSV Files</span></h2>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Import Date</th>
                            <th>Records</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($csv_batches as $batch): ?>
                            <tr>
                                <td><?php echo date('F j, Y g:i a', strtotime($batch->imported_at)); ?></td>
                                <td><?php echo number_format($batch->record_count); ?> records</td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('delete_batch_nonce'); ?>
                                        <input type="hidden" name="batch_id" value="<?php echo esc_attr($batch->csv_batch); ?>">
                                        <input type="submit" name="delete_batch" 
                                               class="button button-small button-link-delete" 
                                               value="Delete"
                                               onclick="return confirm('Are you sure you want to delete this CSV batch? This will remove <?php echo $batch->record_count; ?> records.');">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Add new postal code form -->
        <div class="postbox">
            <h2 class="hndle"><span>Add New Postal Code</span></h2>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('add_postal_code_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="postal_code">Postal Code</label></th>
                            <td>
                                <input type="text" name="postal_code" id="postal_code" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="area_name">Area Name</label></th>
                            <td>
                                <input type="text" name="area_name" id="area_name" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="state">State/Province</label></th>
                            <td>
                                <input type="text" name="state" id="state" class="regular-text">
                                <p class="description">Optional: Enter state or province name</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="add_postal_code" class="button button-primary" value="Add Postal Code">
                    </p>
                </form>
            </div>
        </div>

        <!-- List all postal codes -->
        <div class="postbox">
            <h2 class="hndle"><span>All Postal Codes</span></h2>
            <div class="inside">
                <?php vpc_list_postal_codes(); ?>
            </div>
        </div>

        <style>
        .postbox {
            margin-bottom: 20px;
        }
        .postbox .hndle {
            padding: 10px;
            border-bottom: 1px solid #ccd0d4;
        }
        .postbox .inside {
            padding: 15px;
        }
        .wp-list-table {
            margin-top: 10px;
        }
        </style>
    </div>
    <?php
}

/**
 * Add postal code selection fields to vendor settings
 */
function vpc_add_vendor_postal_codes($user_id) {
        global $wpdb;

    // Get the stored postal codes (now an array of postal_code strings)
    $vendor_saved_postal_codes = get_user_meta($user_id, '_vendor_postal_codes', true);
    if (!is_array($vendor_saved_postal_codes)) {
        $vendor_saved_postal_codes = array();
    }

    // Get the stored postal prices (keyed by area_id)
    $vendor_postal_prices = get_user_meta($user_id, '_vendor_postal_prices', true);
    if (!is_array($vendor_postal_prices)) {
        $vendor_postal_prices = array();
    }

    // Get all available postal codes with area names and states from the database
    $postal_codes = $wpdb->get_results("SELECT id, postal_code, area_name, state FROM {$wpdb->prefix}vendor_postal_codes ORDER BY postal_code ASC");

    // Create a mapping from postal_code to area_id for easier lookup
    $saved_area_ids = array();
    if (!empty($vendor_saved_postal_codes)) {
        $placeholders = implode(', ', array_fill(0, count($vendor_saved_postal_codes), '%s'));
        $query = $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}vendor_postal_codes WHERE postal_code IN ({$placeholders})",
            $vendor_saved_postal_codes
        );
        $results = $wpdb->get_col($query);
        if ($results) {
            $saved_area_ids = array_map('intval', $results);
        }
    }
    ?>
  <div class="dokan-form-group">
        <label class="dokan-w3 dokan-control-label" for="vendor_postal_codes">
            <?php _e('Delivery Areas', 'dokan-lite'); ?>
        </label>
        <div class="dokan-w5 dokan-text-left">
            <select name="vendor_postal_codes[]" id="vendor_postal_codes" class="dokan-form-control" multiple="multiple">
                <?php foreach ($postal_codes as $code): ?>
                    <option value="<?php echo esc_attr($code->id); ?>"
                            <?php echo in_array($code->id, $saved_area_ids) ? 'selected="selected"' : ''; ?>>
                        <?php echo esc_html($code->postal_code . ' - ' . $code->area_name . ' (' . $code->state . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="dokan-form-group" id="postal_prices_container">
        <label class="dokan-w3 dokan-control-label">
            <?php _e('Delivery Prices', 'dokan-lite'); ?>
        </label>
        <div class="dokan-w5 dokan-text-left" id="postal_prices_fields">
            <?php
            // Only show price fields for currently selected areas (based on saved_area_ids)
            if (!empty($saved_area_ids)) {
                foreach ($saved_area_ids as $area_id) {
                    // Get area details for display
                    $area_details = $wpdb->get_row($wpdb->prepare(
                        "SELECT postal_code, area_name, state FROM {$wpdb->prefix}vendor_postal_codes WHERE id = %d LIMIT 1",
                        $area_id
                    ));

                    if (!$area_details) continue;

                    $display_text = $area_details->postal_code . ' - ' . $area_details->area_name . ' (' . $area_details->state . ')';
                    $price = isset($vendor_postal_prices[$area_id]['price']) ? $vendor_postal_prices[$area_id]['price'] : '';
                    $min_order = isset($vendor_postal_prices[$area_id]['min_order']) ? $vendor_postal_prices[$area_id]['min_order'] : '';
                    ?>
                    <div class="postal-price-field" data-code="<?php echo esc_attr($area_id); ?>">
                        <label><?php echo esc_html($display_text); ?></label>
                        <div class="postal-field-row">
                            <div class="postal-field-col">
                                <label class="postal-sub-label">Delivery Price</label>
                                <input type="number"
                                       step="0.01"
                                       min="0.01"
                                       name="vendor_postal_prices[<?php echo esc_attr($area_id); ?>][price]"
                                       value="<?php echo esc_attr($price); ?>"
                                       class="dokan-form-control postal-price-input"
                                       placeholder="Enter delivery price"
                                       required />
                            </div>
                            <div class="postal-field-col">
                                <label class="postal-sub-label">Minimum Order Value</label>
                                <input type="number"
                                       step="0.01"
                                       min="0.01"
                                       name="vendor_postal_prices[<?php echo esc_attr($area_id); ?>][min_order]"
                                       value="<?php echo esc_attr($min_order); ?>"
                                       class="dokan-form-control postal-min-order-input"
                                       placeholder="Enter minimum order value"
                                       required />
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Initialize select2 for better dropdown experience
        $('#vendor_postal_codes').select2({
            width: '100%',
            placeholder: 'Select delivery areas'
        });

        // Handle selection changes
// Handle selection changes - FIXED VERSION
$('#vendor_postal_codes').on('change', function() {
        var selectedIds = $(this).val() || [];
        var pricesContainer = $('#postal_prices_fields');
        var existingPrices = <?php echo json_encode($vendor_postal_prices); ?>;
        
        // 1. Capture CURRENT field values before clearing
        var currentValues = {};
        pricesContainer.find('.postal-price-field').each(function() {
            var id = $(this).data('code');  // Now using ID
            currentValues[id] = {
                price: $(this).find('.postal-price-input').val(),
                min_order: $(this).find('.postal-min-order-input').val()
            };
        });

        // 2. Now clear container
        pricesContainer.empty();

    // 3. Rebuild using COMBINED data (current + saved)
        selectedIds.forEach(function(id) {
            var optionText = $('#vendor_postal_codes option[value="' + id + '"]').text();
            
            // Prioritize current values, fallback to saved data
            var priceVal = '';
            var minOrderVal = '';
            
            if (currentValues[id]) {
                // Use unsaved changes if available
                priceVal = currentValues[id].price;
                minOrderVal = currentValues[id].min_order;
            } else if (existingPrices[id]) {
                // Use saved data otherwise
                priceVal = existingPrices[id].price;
                minOrderVal = existingPrices[id].min_order;
            }

            // Build field with proper values
            var priceField = $('<div class="postal-price-field" data-code="' + id + '">' +
                '<label>' + optionText + '</label>' +
                '<div class="postal-field-row">' +
                    '<div class="postal-field-col">' +
                        '<label class="postal-sub-label">Delivery Price</label>' +
                        '<input type="number" step="0.01" min="0.01" name="vendor_postal_prices[' + id + '][price]" ' +
                        'value="' + priceVal + '" class="dokan-form-control postal-price-input" ' +
                        'placeholder="Enter delivery price" required />' +
                    '</div>' +
                    '<div class="postal-field-col">' +
                        '<label class="postal-sub-label">Minimum Order Value</label>' +
                        '<input type="number" step="0.01" min="0.01" name="vendor_postal_prices[' + id + '][min_order]" ' +
                        'value="' + minOrderVal + '" class="dokan-form-control postal-min-order-input" ' +
                        'placeholder="Enter minimum order value" required />' +
                    '</div>' +
                '</div>' +
                '</div>');
                
            pricesContainer.append(priceField);

    });
});

        // Handle form submission validation
        $('form').on('submit', function(e) {
            var hasError = false;
            var errorMessage = '';

            $('.postal-price-field').each(function() {
                var priceInput = $(this).find('.postal-price-input');
                var minOrderInput = $(this).find('.postal-min-order-input');
             
                var areaId = $(this).data('code');
                var optionText = $('#vendor_postal_codes option[value="' + areaId + '"]').text();

                var priceValue = parseFloat(priceInput.val()) || 0;
                var minOrderValue = parseFloat(minOrderInput.val()) || 0;

                if (priceValue <= 0) {
                    hasError = true;
                    errorMessage += 'Delivery price for postal code ' + optionText + ' must be greater than 0.\n';
                    priceInput.css('border-color', '#dc3232');
                }

                if (minOrderValue <= 0) {
                    hasError = true;
                    errorMessage += 'Minimum order value for postal code ' + optionText + ' must be greater than 0.\n';
                    minOrderInput.css('border-color', '#dc3232');
                }
            });

            if (hasError) {
                e.preventDefault();
                alert(errorMessage);
                return false;
            }
        });

        // Remove error styling on input
        $(document).on('input', '.postal-price-input, .postal-min-order-input', function() {
            $(this).css('border-color', '');
        });
    });
    </script>

    <style>
    .select2-container--default .select2-selection--multiple {
        border-color: #ddd;
    }
    .postal-price-field {
        margin-bottom: 10px;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: 3px;
    }
    .postal-price-field label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .postal-field-row {
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }
    .postal-field-col {
        flex: 1;
    }
    .postal-sub-label {
        display: block;
        margin-bottom: 5px;
        font-weight: normal;
        font-size: 12px;
        color: #666;
    }
    .postal-price-input,
    .postal-min-order-input {
        width: 100% !important;
    }
    @media (max-width: 768px) {
        .postal-field-row {
            flex-direction: column;
            gap: 10px;
        }
    }

    </style>
    <?php
}

/**
 * Save vendor's postal code selections and prices
 */
function vpc_save_vendor_postal_codes($user_id) {
    global $wpdb;

    if (!isset($_POST['vendor_postal_codes']) || !is_array($_POST['vendor_postal_codes'])) {
        update_user_meta($user_id, '_vendor_postal_codes', array());
        update_user_meta($user_id, '_vendor_postal_prices', array());
        return;
    }

    $area_ids = array_map('intval', $_POST['vendor_postal_codes']);

    $postal_prices = array();
    $validation_errors = array();
     $postal_code_list = array();
    if (isset($_POST['vendor_postal_prices']) && is_array($_POST['vendor_postal_prices'])) {
        foreach ($_POST['vendor_postal_prices'] as $area_id => $data) {
            if (in_array($area_id, $area_ids)) {
                $price = isset($data['price']) ? floatval($data['price']) : 0;
                $min_order = isset($data['min_order']) ? floatval($data['min_order']) : 0;
                
                if ($price <= 0) {
                    $validation_errors[] = "Delivery price for area ID {$area_id} must be greater than 0.";
                }
                
                if ($min_order <= 0) {
                    $validation_errors[] = "Minimum order value for area ID {$area_id} must be greater than 0.";
                }
                
                // Get area details by ID
                $area = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, postal_code, area_name, state 
                     FROM {$wpdb->prefix}vendor_postal_codes 
                     WHERE id = %d 
                     LIMIT 1",
                    intval($area_id)
                ));

                if (!$area) continue;
                
                // Store both ID and postal code
                $postal_prices[$area_id] = [
                    'id' => $area_id,
                    'price' => $price,
                    'min_order' => $min_order,
                    'postal_code' => $area->postal_code,
                    'area_name' => $area->area_name,
                    'state' => $area->state
                ];
if (!in_array($area->postal_code, $postal_code_list)) {
                    $postal_code_list[] = $area->postal_code;
                }
            }
        }
    }
    
    if (!empty($validation_errors)) {
        set_transient('vendor_postal_validation_errors_' . $user_id, $validation_errors, 30);
        return;
    }
    
    update_user_meta($user_id, '_vendor_postal_prices', $postal_prices);
    update_user_meta($user_id, '_vendor_postal_codes', $postal_code_list);
}
// Add action hooks
add_action('dokan_settings_after_store_email', 'vpc_add_vendor_postal_codes');
add_action('dokan_store_profile_saved', 'vpc_save_vendor_postal_codes');

/**
 * Display validation errors (NEW FUNCTION)
 */
function vpc_display_vendor_validation_errors($user_id) {
    $errors = get_transient('vendor_postal_validation_errors_' . $user_id);
    if ($errors) {
        echo '<div class="dokan-alert dokan-alert-danger">';
        echo '<strong>Validation Errors:</strong><br>';
        foreach ($errors as $error) {
            echo '• ' . esc_html($error) . '<br>';
        }
        echo '</div>';
        delete_transient('vendor_postal_validation_errors_' . $user_id);
    }
}

// Add this action to display errors in vendor dashboard
add_action('dokan_settings_before_store_email', function() {
    if (dokan_is_seller_dashboard()) {
        $user_id = get_current_user_id();
        vpc_display_vendor_validation_errors($user_id);
    }
});

/**
 * Enqueue required scripts and styles
 */
function vpc_enqueue_scripts() {
    if (dokan_is_seller_dashboard() || (isset($_GET['page']) && $_GET['page'] === 'manage-postal-codes')) {
        // Enqueue Select2
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        
        // Enqueue our custom styles and scripts
        wp_enqueue_style('vpc-admin', plugins_url('css/admin.css', __FILE__));
        wp_enqueue_script('vpc-admin', plugins_url('js/admin.js', __FILE__), array('jquery', 'select2'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'vpc_enqueue_scripts');
add_action('admin_enqueue_scripts', 'vpc_enqueue_scripts');

/**
 * Register Cuisine Taxonomy
 */
function vpc_register_cuisine_taxonomy() {
    $labels = array(
        'name'              => _x('Cuisines', 'taxonomy general name'),
        'singular_name'     => _x('Cuisine', 'taxonomy singular name'),
        'search_items'      => __('Search Cuisines'),
        'all_items'         => __('All Cuisines'),
        'parent_item'       => __('Parent Cuisine'),
        'parent_item_colon' => __('Parent Cuisine:'),
        'edit_item'         => __('Edit Cuisine'),
        'update_item'       => __('Update Cuisine'),
        'add_new_item'      => __('Add New Cuisine'),
        'new_item_name'     => __('New Cuisine Name'),
        'menu_name'         => __('Cuisines'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'           => $labels,
        'show_ui'          => true,
        'show_admin_column' => true,
        'query_var'        => true,
        'rewrite'          => array('slug' => 'cuisine'),
        'show_in_rest'     => true,
    );

    register_taxonomy('cuisine', array('product'), $args);
}
add_action('init', 'vpc_register_cuisine_taxonomy');

/**
 * Add image field to cuisine taxonomy
 */
function vpc_add_cuisine_image_field() {
    ?>
    <div class="form-field term-group">
        <label for="cuisine_image"><?php _e('Cuisine Image'); ?></label>
        <input type="hidden" id="cuisine_image_id" name="cuisine_image_id" class="custom_media_url" value="">
        <div id="cuisine_image_wrapper">
            <img src="" style="max-width:100px;display:none;" />
        </div>
        <p>
            <input type="button" class="button button-secondary cuisine_media_button" id="cuisine_media_button" name="cuisine_media_button" value="<?php _e('Add Image'); ?>" />
            <input type="button" class="button button-secondary cuisine_media_remove" id="cuisine_media_remove" name="cuisine_media_remove" value="<?php _e('Remove Image'); ?>" />
        </p>
    </div>
    <?php
}
add_action('cuisine_add_form_fields', 'vpc_add_cuisine_image_field', 10, 2);

/**
 * Edit cuisine image field
 */
function vpc_edit_cuisine_image_field($term) {
    $image_id = get_term_meta($term->term_id, 'cuisine_image_id', true);
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row">
            <label for="cuisine_image"><?php _e('Cuisine Image'); ?></label>
        </th>
        <td>
            <input type="hidden" id="cuisine_image_id" name="cuisine_image_id" value="<?php echo $image_id; ?>">
            <div id="cuisine_image_wrapper">
                <?php if ($image_id) : ?>
                    <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                <?php endif; ?>
            </div>
            <p>
                <input type="button" class="button button-secondary cuisine_media_button" value="<?php _e('Add Image'); ?>" />
                <input type="button" class="button button-secondary cuisine_media_remove" value="<?php _e('Remove Image'); ?>" />
            </p>
        </td>
    </tr>
    <?php
}
add_action('cuisine_edit_form_fields', 'vpc_edit_cuisine_image_field', 10, 2);

/**
 * Save cuisine image
 */
function vpc_save_cuisine_image($term_id) {
    if (isset($_POST['cuisine_image_id']) && '' !== $_POST['cuisine_image_id']) {
        $image_id = (int) $_POST['cuisine_image_id'];
        update_term_meta($term_id, 'cuisine_image_id', $image_id);
    } else {
        delete_term_meta($term_id, 'cuisine_image_id');
    }
}
add_action('edited_cuisine', 'vpc_save_cuisine_image');
add_action('create_cuisine', 'vpc_save_cuisine_image');

/**
 * Add cuisine admin scripts
 */
function vpc_add_cuisine_admin_scripts() {
    if (!did_action('wp_enqueue_media')) {
        wp_enqueue_media();
    }
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function cuisine_media_upload(button_class) {
                var _custom_media = true,
                    _orig_send_attachment = wp.media.editor.send.attachment;
                $('body').on('click', button_class, function(e) {
                    var button_id = '#' + $(this).attr('id');
                    var send_attachment_bkp = wp.media.editor.send.attachment;
                    var button = $(button_id);
                    _custom_media = true;
                    wp.media.editor.send.attachment = function(props, attachment) {
                        if (_custom_media) {
                            $('#cuisine_image_id').val(attachment.id);
                            $('#cuisine_image_wrapper').html('<img class="custom_media_image" src="" style="max-width:100px;display:none;" />');
                            $('#cuisine_image_wrapper .custom_media_image').attr('src', attachment.url).show();
                        } else {
                            return _orig_send_attachment.apply(button_id, [props, attachment]);
                        }
                    }
                    wp.media.editor.open(button);
                    return false;
                });
            }
            
            cuisine_media_upload('.cuisine_media_button');
            
            $('body').on('click', '.cuisine_media_remove', function() {
                $('#cuisine_image_id').val('');
                $('#cuisine_image_wrapper').html('<img class="custom_media_image" src="" style="max-width:100px;display:none;" />');
            });
            
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.data && settings.data.indexOf('action=add-tag') !== -1) {
                    $('#cuisine_image_wrapper').html('');
                    $('#cuisine_image_id').val('');
                }
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'vpc_add_cuisine_admin_scripts');

// Add cuisine meta box to vendor dashboard
function vpc_add_vendor_cuisine_meta_box() {
    add_meta_box(
        'vendor_cuisines',
        __('Restaurant Cuisines'),
        'vpc_vendor_cuisine_meta_box_callback',
        'product',
        'side'
    );
}
add_action('add_meta_boxes', 'vpc_add_vendor_cuisine_meta_box');

function vpc_vendor_cuisine_meta_box_callback($post) {
    $terms = get_terms(array(
        'taxonomy' => 'cuisine',
        'hide_empty' => false,
    ));

    $current_cuisines = wp_get_object_terms($post->ID, 'cuisine', array('fields' => 'ids'));
    ?>
    <div class="vendor-cuisines-box">
        <?php foreach ($terms as $term): ?>
            <label>
                <input type="checkbox" 
                       name="product_cuisines[]" 
                       value="<?php echo esc_attr($term->term_id); ?>"
                       <?php checked(in_array($term->term_id, $current_cuisines)); ?>>
                <?php echo esc_html($term->name); ?>
            </label><br>
        <?php endforeach; ?>
    </div>
    <?php
}

// Save cuisine meta box data
function vpc_save_cuisine_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['product_cuisines'])) {
        $cuisines = array_map('intval', $_POST['product_cuisines']);
        wp_set_object_terms($post_id, $cuisines, 'cuisine');
    }
}
add_action('save_post', 'vpc_save_cuisine_meta_box');



