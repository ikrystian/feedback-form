<?php
function generate_feedback_form()
{
    ob_start();
    $current_user = wp_get_current_user();
    ?>
    <form id="feedback-form" class="feedback-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
        <div class="feedback-form__group">
            <label for="first_name"><?php echo __('First name', 'feedback-form'); ?> <sup>*</sup></label>
            <input type="text" id="first_name" name="first_name"
                   placeholder="<?php echo __('First name', 'feedback-form'); ?>" required
                   value="<?php echo $current_user->user_firstname; ?>">
        </div>
        <div class="feedback-form__group">
            <label for="last_name"><?php echo __('Last name', 'feedback-form'); ?><sup>*</sup></label>
            <input type="text" id="last_name" name="last_name"
                   placeholder="<?php echo __('Last name', 'feedback-form'); ?>" required
                   value="<?php echo $current_user->user_lastname; ?>">
        </div>
        <div class="feedback-form__group">
            <label for="email"><?php echo __('Email', 'feedback-form'); ?><sup>*</sup></label>
            <input type="email" id="email" name="email" placeholder="<?php echo __('Email', 'feedback-form'); ?>"
                   required
                   value="<?php echo $current_user->user_email; ?>">
        </div>
        <div class="feedback-form__group">
            <label for='subject'><?php echo __('Subject', 'feedback-form'); ?><sup>*</sup></label>
            <input type="text" id="subject" name="subject" placeholder="<?php echo __('Subject', 'feedback-form'); ?>"
                   required>
        </div>

        <div class="feedback-form__group">
            <label for="message"><?php echo __('Message', 'feedback-form'); ?><sup>*</sup></label>
            <textarea id="message" name="message" placeholder="<?php echo __('Message', 'feedback-form'); ?>"
                      required></textarea>
        </div>

        <input type="hidden" name="action" value="handle_feedback_form">
        <footer class="feedback-form__footer">
            <button type="submit"><?php echo __('Submit', 'feedback-form'); ?></button>
        </footer>
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode('feedback_form', 'generate_feedback_form');

function enqueue_scripts()
{
    wp_enqueue_style('style-css', plugin_dir_url(__FILE__) . 'feedback-form.css');
}

add_action('wp_enqueue_scripts', 'enqueue_scripts');

add_action('wp_ajax_handle_feedback_form', 'handle_feedback_form');
add_action('wp_ajax_nopriv_handle_feedback_form', 'handle_feedback_form');

function handle_feedback_form()
{
    if (isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['subject'], $_POST['message'])) {
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $subject = sanitize_text_field($_POST['subject']);
        $message = sanitize_textarea_field($_POST['message']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'feedback';
        $wpdb->insert(
            $table_name,
            array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message
            )
        );

        wp_send_json_success([
            'message' => __('Thank you for sending us your feedback', 'feedback-form')
        ], 200);

    } else {
        wp_send_json_error(__('Missing data in request', 'feedback-form'));
    }

    die();
}

function create_feedback_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'feedback';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        first_name text NOT NULL,
        last_name text NOT NULL,
        email text NOT NULL,
        subject text NOT NULL,
        message text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_feedback_table');

function enqueue_feedback_form_script()
{
    wp_enqueue_script(
        'feedback-form-script',
        plugin_dir_url(__FILE__) . 'feedback-form.js',
        array('jquery'),
        '1.0',
        true
    );
}

add_action('wp_enqueue_scripts', 'enqueue_feedback_form_script');

function display_feedback_entries()
{
    if (!current_user_can('manage_options')) {
        return __('You are not authorized to view the content of this page.', 'feedback-form');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'feedback';
    $total_entries = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $entries_per_page = 10;
    $total_pages = ceil($total_entries / $entries_per_page);

    ob_start();
    if ($total_entries == 0):
        ?>
        <div class="no-results entries"><?php echo __('No results.', 'feedback-form'); ?></div>
    <?php else: ?>
        <div id="feedback-entries" class="feedback-entries entries"></div>
        <div id="pagination" class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="#" class="page-number" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <div id="feedback-details"></div>
    <?php
    endif;
    return ob_get_clean();
}

add_shortcode('display_feedback_entries', 'display_feedback_entries');

add_action('wp_ajax_load_feedback_entries', 'load_feedback_entries');

function load_feedback_entries()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'feedback';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $entries_per_page = 10;
    $offset = ($page - 1) * $entries_per_page;
    $entries = $wpdb->get_results("SELECT id, first_name FROM $table_name ORDER BY id DESC LIMIT $entries_per_page OFFSET $offset");

    wp_send_json_success($entries);
}

function load_feedback_details()
{
    if (!isset($_POST['id'])) {
        wp_send_json_error('Id is required.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'feedback';
    $id = intval($_POST['id']);
    $entries = $wpdb->get_results("SELECT * FROM $table_name WHERE id = $id LIMIT 1");

    wp_send_json_success($entries);
}

add_action('wp_ajax_load_feedback_details', 'load_feedback_details');
