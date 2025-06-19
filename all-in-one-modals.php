
<?php
/*
 * Plugin Name: CARDET Send Message to support
 * Description: Logged-in users can send a message to Administration Email Address! - Shortcode placed on footer.
 * Version: 2.1
 * Author: CARDET Development Team
 * Author URI: https://cardet.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Common JavaScript functions
function cardet_message_common_js() {
    ?>
    <script>
        function notifyLoginRequired() {
            const message = `You need to log in to send a message!`;
            UIkit.notification({message: message, status: 'warning'});
        }
    </script>
    <?php
}
add_action('wp_footer', 'cardet_message_common_js');

// Send message to support while logged in
function send_message_button_shortcode() {
    // Get current user information
    $current_user = wp_get_current_user();
    $current_user_name = $current_user->display_name;
    $is_logged_in = is_user_logged_in();
    $post_id = get_the_ID();

    ob_start();
    ?>
    <!-- Button to trigger modal -->
    <button class="uk-button uk-button-text"
            onclick="<?php echo $is_logged_in ? "UIkit.modal('#send-message-modal').show()" : "notifyLoginRequired()" ?>">
         <span uk-icon="mail" style="transform: translateY(-1px);"></span>&nbsp;<span class="">Get support</span>
    </button>

    <!-- Modal Structure (UIkit) --> 
    <div id="send-message-modal" uk-modal>
        <div class="uk-modal-dialog uk-modal-body">
            <h4 class="uk-modal-title el-title uk-heading-small uk-text-primary">Message the support team</h4>

            <!-- Display sender's name -->
            <div class="uk-margin">
                <label class="uk-form-label">From:</label>
                <input class="uk-input" type="text" value="<?php echo esc_attr($current_user_name); ?>" readonly>
            </div>

            <!-- Subject Field -->
            <div class="uk-margin">
                <label class="uk-form-label" for="support-subject">Subject</label>
                <input class="uk-input" id="support-subject" type="text" placeholder="Enter the subject">
            </div>

            <!-- Message Textarea -->
            <div class="uk-margin">
                <label class="uk-form-label" for="support-body">Message</label>
                <textarea class="uk-textarea" id="support-body" rows="5" placeholder="Write your message here"></textarea>
            </div>

            <!-- Hidden post ID -->
            <input type="hidden" id="support-post-id" value="<?php echo esc_attr($post_id); ?>">

            <!-- Modal Footer -->
            <div class="uk-modal-footer uk-text-right">
                <button class="uk-button uk-button-primary uk-modal-close">Cancel</button>
                <button class="uk-button uk-button-default" onclick="sendSupportMessage()">Send</button>
            </div>
        </div>
    </div>

    <script>
        function sendSupportMessage() {
            const subject = document.getElementById('support-subject').value;
            const message = document.getElementById('support-body').value;
            const postId = document.getElementById('support-post-id').value;

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'send_user_message',
                    subject: subject,
                    message: message,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        UIkit.notification({message: 'Message sent successfully!', status: 'success'});
                        UIkit.modal('#send-message-modal').hide();
                    } else {
                        UIkit.notification({message: response.data.message || 'Failed to send message.', status: 'danger'});
                    }
                },
                error: function() {
                    UIkit.notification({message: 'An error occurred while sending the message.', status: 'danger'});
                }
            });
        }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('send_message_to_support', 'send_message_button_shortcode');

// Report initiative shortcode
function send_report_to_support() {
    // Get current user information
    $current_user = wp_get_current_user();
    $current_user_name = $current_user->display_name;
    $is_logged_in = is_user_logged_in();
    $post_id = get_the_ID();

    ob_start();
    ?>
    <!-- Button to trigger modal -->
    <button class="uk-button uk-button-text"
            onclick="<?php echo $is_logged_in ? "UIkit.modal('#send-report-modal').show()" : "notifyLoginRequired()" ?>">
         <span uk-icon="warning" style="transform: translateY(-1px);"></span>&nbsp;<span class="">Report Initiative</span>
    </button>

    <!-- Modal Structure (UIkit) --> 
    <div id="send-report-modal" uk-modal>
        <div class="uk-modal-dialog uk-modal-body">
            <h4 class="uk-modal-title el-title uk-heading-small uk-text-primary">Report Initiative</h4>
            <div class="uk-margin">
                <p>Please describe any unethical, inappropriate, or spam content you've encountered. Include links, user names, or specific details to help us review and take appropriate action.</p>
            </div>

            <!-- Display sender's name -->
            <div class="uk-margin">
                <label class="uk-form-label">From:</label>
                <input class="uk-input" type="text" value="<?php echo esc_attr($current_user_name); ?>" readonly>
            </div>

            <!-- Subject Field -->
            <div class="uk-margin">
                <label class="uk-form-label" for="report-subject">Subject</label>
                <input class="uk-input" id="report-subject" type="text" placeholder="Enter the subject" value="Report about initiative">
            </div>

            <!-- Message Textarea -->
            <div class="uk-margin">
                <label class="uk-form-label" for="report-body">Message</label>
                <textarea class="uk-textarea" id="report-body" rows="5" placeholder="Describe the issue in detail"></textarea>
            </div>

            <!-- Hidden post ID -->
            <input type="hidden" id="report-post-id" value="<?php echo esc_attr($post_id); ?>">

            <!-- Modal Footer -->
            <div class="uk-modal-footer uk-text-right">
                <button class="uk-button uk-button-secondary uk-modal-close">Cancel</button>
                <button class="uk-button uk-button-default" onclick="sendReportMessage()">Report</button>
            </div>
        </div>
    </div>

    <script>
        function sendReportMessage() {
            const subject = document.getElementById('report-subject').value;
            const message = document.getElementById('report-body').value;
            const postId = document.getElementById('report-post-id').value;

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'send_report_message',
                    subject: subject,
                    message: message,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        UIkit.notification({message: 'Report submitted successfully!', status: 'success'});
                        UIkit.modal('#send-report-modal').hide();
                    } else {
                        UIkit.notification({message: response.data.message || 'Failed to submit report.', status: 'danger'});
                    }
                },
                error: function() {
                    UIkit.notification({message: 'An error occurred while submitting the report.', status: 'danger'});
                }
            });
        }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('send_report_to_support', 'send_report_to_support');

// Handle support message
function handle_send_user_message() {
    if (!is_user_logged_in() || empty($_POST['subject']) || empty($_POST['message'])) {
        wp_send_json_error(['message' => 'Please complete all fields.']);
        return;
    }

    $subject = sanitize_text_field($_POST['subject']);
    $message_body = sanitize_textarea_field($_POST['message']);
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $current_user = wp_get_current_user();

    $site_name = get_bloginfo('name');
    $recipient_email = get_option('admin_email');

    if (empty($recipient_email)) {
        wp_send_json_error(['message' => 'Recipient email address not configured.']);
        return;
    }

    $email_subject = "Support Request: " . $subject;
    $email_message = "Message from " . $current_user->display_name . " (" . $current_user->user_email . "):\n\n" . $message_body;
    
    if ($post_id) {
        $post_title = get_the_title($post_id);
        $post_url = get_permalink($post_id);
        $email_message .= "\n\n---\nRelated to post: " . $post_title . "\nPost URL: " . $post_url;
    }

    $headers = ['From: ' . $site_name . ' <' . $recipient_email . '>'];

    if (wp_mail($recipient_email, $email_subject, $email_message, $headers)) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Failed to send email.']);
    }
}
add_action('wp_ajax_send_user_message', 'handle_send_user_message');

// Handle report message
function handle_send_report_message() {
    if (!is_user_logged_in() || empty($_POST['subject']) || empty($_POST['message'])) {
        wp_send_json_error(['message' => 'Please complete all fields.']);
        return;
    }

    $subject = sanitize_text_field($_POST['subject']);
    $message_body = sanitize_textarea_field($_POST['message']);
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $current_user = wp_get_current_user();

    $site_name = get_bloginfo('name');
    $recipient_email = get_option('admin_email');

    if (empty($recipient_email)) {
        wp_send_json_error(['message' => 'Recipient email address not configured.']);
        return;
    }

    $email_subject = "CONTENT REPORT: " . $subject;
    $email_message = "Report from " . $current_user->display_name . " (" . $current_user->user_email . "):\n\n" . $message_body;
    
    if ($post_id) {
        $post_title = get_the_title($post_id);
        $post_url = get_permalink($post_id);
        $email_message .= "\n\n---\nReported post: " . $post_title . "\nPost URL: " . $post_url;
    }

    $headers = ['From: ' . $site_name . ' <' . $recipient_email . '>'];

    if (wp_mail($recipient_email, $email_subject, $email_message, $headers)) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Failed to send report.']);
    }
}
add_action('wp_ajax_send_report_message', 'handle_send_report_message');
