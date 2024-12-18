/////////////////////////////////////////////////////////////////////////////

// Send message to support while logged in
function send_message_button_shortcode() {
    // Get current user information
    $current_user = wp_get_current_user();
    $current_user_name = $current_user->display_name;
    $is_logged_in = is_user_logged_in();

    // Generate the button and modal markup
    ob_start();
    ?>

    <!-- Button to trigger modal -->
    <button class="uk-button uk-button-text"
            onclick="<?php echo $is_logged_in ? "UIkit.modal('#send-message-modal').show()" : "notifyLoginRequired()" ?>">
        Send a Message
    </button>

    <!-- Modal Structure (UIkit) --> 
    <div id="send-message-modal" uk-modal>
        <div class="uk-modal-dialog uk-modal-body">
            <h4 class="uk-modal-title">Send a Message</h4>

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
                <button class="uk-button uk-button-primary uk-modal-close">Cancel</button>
                <button class="uk-button uk-button-default" onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>

    <script>
        function sendMessage() {
            // Get message fields
            const subject = document.getElementById('message-subject').value;
            const message = document.getElementById('message-body').value;

            // Perform AJAX request to send the message
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'send_user_message',
                    subject: subject,
                    message: message
                },
                success: function(response) {
                    if (response.success) {
                        UIkit.notification({message: 'Message sent successfully! We will get back to you shortly.', status: 'success'});
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

        function notifyLoginRequired() {
            const message = `You need to log in to send a message!`;
            UIkit.notification({message: message, status: 'warning'});
        }
    </script>
   
    <?php
    return ob_get_clean();
}
add_shortcode('send_message', 'send_message_button_shortcode');


// Handle the messaging request
function handle_send_user_message() {
    // Check if the user is logged in and required fields are provided
    if (!is_user_logged_in() || empty($_POST['subject']) || empty($_POST['message'])) {
        wp_send_json_error(['message' => 'Please complete all fields.']);
        return;
    }

    $subject = sanitize_text_field($_POST['subject']);
    $message_body = sanitize_textarea_field($_POST['message']);
    $current_user = wp_get_current_user();

    // Dynamically fetch the site name and support email
    $site_name = get_bloginfo('name'); // Get the site's name
    $recipient_email = get_option('admin_email'); // Get the administration email

    if (empty($recipient_email)) {
        wp_send_json_error(['message' => 'Recipient email address not configured.']);
        return;
    }

    // Prepare email
    $email_subject = "Support Request: " . $subject;
    $email_message = "Message from " . $current_user->display_name . " (" . $current_user->user_email . "):\n\n" . $message_body;

    // Dynamic "From" header with site name and admin email
    $headers = ['From: ' . $site_name . ' <' . $recipient_email . '>'];

    // Send the email
    if (wp_mail($recipient_email, $email_subject, $email_message, $headers)) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Failed to send email.']);
    }
}
add_action('wp_ajax_send_user_message', 'handle_send_user_message');


/////////////////////////////////////////////////////////////////////////////
