<?php
/*
Plugin Name: CARDET User-to-User Messaging
Description: Allows logged-in users to message profile authors via UIkit modal. Uses shortcode [send_user_message].
Version: 1.0
Author: CARDET Development Team
License: GPL v2 or later
*/

function cardet_user_message_modal_shortcode() {
    // Must be on an author archive page
    if (!is_author()) return '';

    $author = get_queried_object();
    if (!$author instanceof WP_User) return '';

    $recipient_id = $author->ID;
    $recipient_name = esc_html($author->display_name);
    $current_user = wp_get_current_user();
    $is_logged_in = is_user_logged_in();

    ob_start();
    ?>
    <!-- Send Message Button -->
    <button class="uk-button uk-button-default"
            onclick="<?php echo $is_logged_in ? "UIkit.modal('#send-user-message-modal').show()" : "notifyLoginRequired()" ?>">
        <span uk-icon="mail" style="transform: translateY(-1px);"></span>&nbsp;Message <?php echo $recipient_name; ?>
    </button>

    <!-- UIkit Modal -->
    <div id="send-user-message-modal" uk-modal>
        <div class="uk-modal-dialog uk-modal-body">
            <h4 class="uk-modal-title">Message <?php echo $recipient_name; ?></h4>

            <div class="uk-margin">
                <label class="uk-form-label" for="user-message-subject">Subject</label>
                <input class="uk-input" id="user-message-subject" type="text" placeholder="Enter subject">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label" for="user-message-body">Message</label>
                <textarea class="uk-textarea" id="user-message-body" rows="5" placeholder="Write your message"></textarea>
            </div>

            <div class="uk-modal-footer uk-text-right">
                <button class="uk-button uk-button-default uk-modal-close">Cancel</button>
                <button class="uk-button uk-button-primary" onclick="sendUserMessage(<?php echo $recipient_id; ?>)">Send</button>
            </div>
        </div>
    </div>

    <script>
    function sendUserMessage(recipient_id) {
        const subject = document.getElementById('user-message-subject').value;
        const message = document.getElementById('user-message-body').value;

        jQuery.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'cardet_send_user_message',
                recipient_id: recipient_id,
                subject: subject,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    UIkit.notification({message: 'Message sent successfully!', status: 'success'});
                    UIkit.modal('#send-user-message-modal').hide();
                } else {
                    UIkit.notification({message: response.data.message || 'Failed to send message.', status: 'danger'});
                }
            },
            error: function() {
                UIkit.notification({message: 'An error occurred.', status: 'danger'});
            }
        });
    }

    function notifyLoginRequired() {
        UIkit.notification({message: 'You must be logged in to send a message.', status: 'warning'});
    }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('send_user_message', 'cardet_user_message_modal_shortcode');


// Handle AJAX message sending
function cardet_handle_send_user_message() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in.']);
        return;
    }

    $recipient_id = intval($_POST['recipient_id']);
    $subject = sanitize_text_field($_POST['subject']);
    $message_body = sanitize_textarea_field($_POST['message']);

    if (!$recipient_id || empty($subject) || empty($message_body)) {
        wp_send_json_error(['message' => 'Missing data.']);
        return;
    }

    $recipient = get_userdata($recipient_id);
    if (!$recipient || !is_email($recipient->user_email)) {
        wp_send_json_error(['message' => 'Invalid recipient.']);
        return;
    }

    $sender = wp_get_current_user();
    $sender_name = esc_html($sender->display_name);
    $sender_profile_url = get_author_posts_url($sender->ID);

    // Compose message
    $site_name = get_bloginfo('name');
    $from_email = 'no-reply@' . parse_url(home_url(), PHP_URL_HOST);

    $email_subject = $subject;
    $email_message = "You have received a new message from {$sender_name}:\n\n";
    $email_message .= "{$message_body}\n\n";


    $email_message .= "--------------------------------------------\n";
    $email_message .= "Please do not respond to this email as it was sent from a no-reply address.\n";
    $email_message .= "To respond, visit the sender's profile and use the messaging system.: {$sender_profile_url}\n\n";
    $email_message .= "Regards,\n";
    $email_message .= "{$site_name}";



    $headers = ['From: ' . $site_name . ' <' . $from_email . '>'];

    if (wp_mail($recipient->user_email, $email_subject, $email_message, $headers)) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Failed to send email.']);
    }
}
add_action('wp_ajax_cardet_send_user_message', 'cardet_handle_send_user_message');
