<?php
class MOPPMFeedbackHandler
{
    function __construct()
    {
        add_action('admin_init', array($this, 'moppm_feedback_actions'));
    }

    function moppm_feedback_actions()
    {
       
        if (current_user_can('manage_options') && isset($_POST['option'])) { 
            switch (sanitize_text_field($_REQUEST['option'])) {
                case "moppm_skip_feedback":
                case "moppm_feedback":
                   $this->handle_feedback($_POST);
                    break;
            }
        }
    }
    function handle_feedback($postdata)
    {
         
        if (MOPPM_TEST_MODE) {
            var_dump("expression");
            exit;
            deactivate_plugins(dirname(dirname(__FILE__))."\\miniorange-password-policy-setting.php");
                return;
        }

        $nonce = sanitize_text_field($_POST['_wpnonce']);
        if (!wp_verify_nonce($nonce,'moppm_feedback')) {
        do_action('moppm_show_message', MOPPM_Messages::showMessage('ERROR'), 'ERROR');
        return;
        }
        $user = wp_get_current_user();
        $feedback_option = sanitize_text_field($_POST['option']);
        $message = 'Plugin Deactivated';
        $deactivate_reason_message = array_key_exists('moppm_query_feedback', $_POST) ? sanitize_text_field($_POST['moppm_query_feedback']) :false;
        $activation_date = get_site_option('moppm_activated_time');
        $current_date = time();
        $diff = $activation_date - $current_date;
        if ($activation_date == false) {
            $days = 'NA';
        } else {
            $days = abs(round($diff / 86400));
        }

        $reply_required = '';
        if (isset($_POST['get_reply'])) {
            $reply_required = htmlspecialchars(sanitize_text_field($_POST['get_reply']));
        }
        if (empty($reply_required)) {
            $reply_required = "don't reply";
            $message .= ' &nbsp; [Reply:<b style="color:red";>' .htmlspecialchars (esc_attr($reply_required )). '</b>,';
        } else {
            $reply_required = "yes";
            $message .= '[Reply:' . esc_attr($reply_required) . ',';
        }

        $message .= 'D:' . esc_html($days) . ',';

        $message .= ', Feedback : ' . esc_html($deactivate_reason_message) . '';

        if (isset($_POST['rate'])) {
            $rate_value = htmlspecialchars(sanitize_text_field($_POST['rate']));
        } else {
            $rate_value = "--";
        }
        $message .= ', [Rating :' . esc_html($rate_value) . ']';
        if (empty($reply_required))
             $message .= Moppm_Utility::moppm_send_configuration();
        else
            $message .= Moppm_Utility::moppm_send_configuration(true);
        
        $email = isset($_POST['query_mail'])? sanitize_email($_POST['query_mail']): '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email =esc_html(get_site_option('email'));
            if (empty($email)) {
                $email = $user->user_email;
            }
        }
        $phone = esc_html (get_option('mo_wpns_admin_phone'));
        if (!$phone) {
            $phone = '';
            }
        $feedback_reasons = new MOPPM_Api();
        $globalalert = new MOPPM_Api();
        if (!is_null($feedback_reasons)) 
        {
                if (!$globalalert->moppm_is_curl_installed()) 
                    {
                        deactivate_plugins(dirname(dirname(__FILE__))."\\miniorange-password-policy-setting.php");
                        wp_redirect('plugins.php');
                    }
               else 
               {   

                        $submited = json_decode($feedback_reasons->send_email_alert($email, $phone, $message, $feedback_option), true);
                            if (json_last_error() == JSON_ERROR_NONE) 
                            {
                               if (is_array($submited) && array_key_exists('status', $submited) && $submited['status'] == 'ERROR') 
                                {
                                    do_action('moppm_show_message', $submited['message'], 'ERROR');
                                }
                                else 
                                {
                                        if ($submited == false) {
                                        do_action('moppm_show_message', 'Error while submitting the query.', 'ERROR');
                                        }
                                }
                            }
                         deactivate_plugins(dirname(dirname(__FILE__))."\\miniorange-password-policy-setting.php");
                         do_action('moppm_show_message', 'Thank you for the feedback.', 'SUCCESS'); 
                }
        }
    }
}new MOPPMFeedbackHandler();
