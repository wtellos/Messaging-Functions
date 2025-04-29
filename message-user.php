<?php

function send_message_button_shortcode() {
    // Get current user information
    $current_user = wp_get_current_user();
    $current_user_name = $current_user->display_name;
    $is_logged_in = is_user_logged_in();

    // Generate the button and modal markup
    ob_start();
    ?>

    <!-- Button to trigger modal -->
    <button class="uk-button uk-button-primary"
            onclick="<?php echo $is_logged_in ? "UIkit.modal('#send-message-modal').show()" : "notifyLoginRequired('send messages')" ?>">
        Send a Message
    </button>

    <!-- Modal Structure (UIkit) -->
    <div id="send-message-modal" uk-modal>
        <div class="uk-modal-dialog uk-modal-body">
            <h2 class="uk-modal-title">Send a Message</h2>

            <!-- Display sender's name -->
            <div class="uk-margin">
                <label class="uk-form-label">From:</label>
                <input class="uk-input" type="text" value="<?php echo esc_attr($current_user_name); ?>" readonly>
            </div>

            <!-- Subject Field -->
            <div class="uk-margin">
                <label class="uk-form-label" for="message-subject">Subject</label>
                <input class="uk-input" id="message-subject" type="text" placeholder="Enter the subject">
            </div>

            <!-- Message Textarea -->
            <div class="uk-margin">
                <label class="uk-form-label" for="message-body">Message</label>
                <textarea class="uk-textarea" id="message-body" rows="5" placeholder="Write your message here"></textarea>
            </div>

            <!-- Modal Footer with Send and Cancel Buttons -->
            <div class="uk-modal-footer uk-text-right">
                <button class="uk-button uk-button-default uk-modal-close">Cancel</button>
                <button class="uk-button uk-button-primary" onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>

    <script>
    function sendMessage() {
        // Get message fields
        const subject = document.getElementById('message-subject').value;
        const message = document.getElementById('message-body').value;
        const profileUserId = <?php echo get_queried_object_id(); ?>; // Profile page user ID

        // Perform AJAX request to send the message
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'send_user_message',
                profile_user_id: profileUserId,
                subject: subject,
                message: message,
                security: '<?php echo wp_create_nonce("send_message_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    UIkit.notification({message: 'Message sent successfully!', status: 'success'});
                    UIkit.modal('#send-message-modal').hide(); // Close modal
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
add_shortcode('send_user_message', 'send_message_button_shortcode');

function handle_send_user_message() {
    // Verify nonce and validate input
    if (!check_ajax_referer('send_message_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }

    if (!is_user_logged_in() || empty($_POST['profile_user_id']) || empty($_POST['subject']) || empty($_POST['message'])) {
        wp_send_json_error(['message' => 'Please complete all fields.']);
        return;
    }

    $profile_user_id = intval($_POST['profile_user_id']);
    $subject = sanitize_text_field($_POST['subject']);
    $message_body = sanitize_textarea_field($_POST['message']);
    $current_user = wp_get_current_user();

    // Get recipient
    $recipient = get_userdata($profile_user_id);
    if (!$recipient) {
        wp_send_json_error(['message' => 'Recipient not found.']);
        return;
    }

    // Get sender's first name
    $sender_first_name = get_user_meta($current_user->ID, 'first_name', true);
    $sender_display_name = !empty($sender_first_name) ? $sender_first_name : $current_user->display_name;

    // Configure email
    $site_name = get_bloginfo('name');
    $site_email = 'noreply@' . parse_url(home_url(), PHP_URL_HOST);
    $sender_profile_url = esc_url(get_author_posts_url($current_user->ID));

    // Format email headers
    $from_header = sprintf(
        '%1$s via %2$s <%3$s>',
        $sender_display_name,
        $site_name,
        $site_email
    );

    $email_subject = "[Message] " . $subject;
    $email_message = sprintf(
        "You've received a message from %s:\n\n%s\n\n---\n\n" .
        "<b>Do not reply to this email.</b>\n" .
        "View the sender's profile to respond: %s",
        $sender_display_name,
        wpautop($message_body),
        '<a href="' . $sender_profile_url . '">View Profile</a>'
    );

    $headers = [
        'From: ' . $from_header,
        'Reply-To: ' . $site_email,
        'Content-Type: text/html; charset=UTF-8'
    ];

    // Send email
    $sent = wp_mail(
        $recipient->user_email,
        $email_subject,
        $email_message,
        $headers
    );

    if ($sent) {
        wp_send_json_success();
    } else {
        error_log('Email failed: ' . print_r(error_get_last(), true));
        wp_send_json_error(['message' => 'Failed to send message.']);
    }
}
add_action('wp_ajax_send_user_message', 'handle_send_user_message');
