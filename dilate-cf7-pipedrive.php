<?php
/*
Plugin Name: Forminator Pipedrive Integration
Description: Sends Forminator submissions to Pipedrive.
Version: 1.2
Author: Dilate Integration & Morgan Leek
*/

if (!defined('ABSPATH')) exit;

// Activation hook
function cf7_pipedrive_activate() {
    if (!get_option('cf7_pipedrive_settings')) {
        add_option('cf7_pipedrive_settings', ['api_token' => '', 'last_synced' => '']);
    }
}
register_activation_hook(__FILE__, 'cf7_pipedrive_activate');

// Deactivation hook
function cf7_pipedrive_deactivate() {
    delete_option('cf7_pipedrive_settings'); // Optional: Remove settings
}
register_deactivation_hook(__FILE__, 'cf7_pipedrive_deactivate');

// add_action('wpcf7_before_send_mail', 'cf7_to_pipedrive');

// function cf7_to_pipedrive($contact_form) {
// }

// Add an admin menu page
add_action('admin_menu', 'cf7_pipedrive_add_admin_menu');

function cf7_pipedrive_add_admin_menu() {
    add_options_page(
        'Pipedrive', 
        'Pipedrive', 
        'manage_options', 
        'cf7-pipedrive', 
        'cf7_pipedrive_settings_page'
    );
}

// Settings page callback
function cf7_pipedrive_settings_page() {
    // Check if the user has submitted the form
    // var_dump($_POST['cf7_pipedrive_api_token']);
    
    if (isset($_POST['cf7_pipedrive_api_token'])) {

        $api_token = sanitize_text_field($_POST['cf7_pipedrive_api_token']);
        update_option('cf7_pipedrive_settings', ['api_token' => $api_token]);

        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    }

    // Retrieve the stored API token
    $settings = get_option('cf7_pipedrive_settings');
    $api_token = $settings['api_token'] ?? '';
    ?>
    <div class="wrap">
        <h1>Contact Form 7 to Pipedrive Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('cf7_pipedrive_save_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cf7_pipedrive_api_token">Pipedrive API Token</label></th>
                    <td>
                        <input type="text" name="cf7_pipedrive_api_token" id="cf7_pipedrive_api_token" value="<?php echo esc_attr($api_token); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

function is_triggered( $entry, $module_id, $field_data_array ) {
    // Shipping 22872
    // Exact Price 22864
    
    // error_log( 'entry :: ', 0 );
    // error_log( print_r( $field_data_array[4]['value'], true ), 0 );
    // error_log( 'module_id :: ', 0 );
    // error_log( print_r( $module_id, true ), 0 );
    // error_log( 'field_data_array :: ', 0 );
    // error_log( print_r( $field_data_array, true ), 0 );

    // $post_id = get_the_ID();
    // $url = get_permalink();

    // Pipedrive API Key
    $settings = get_option('cf7_pipedrive_settings');
    $pipedrive_api_token = $settings['api_token'] ?? '';
    if( empty( $pipedrive_api_token ) ) return;

    // Endpoint for creating a person (lead) in Pipedrive
    $persons_endpoint = "https://api.pipedrive.com/v1/persons?api_token={$pipedrive_api_token}";

    // Endpoint for creating a lead in Pipedrive
    $leads_endpoint = "https://api.pipedrive.com/v1/leads?api_token={$pipedrive_api_token}";
    
    // $submission = WPCF7_Submission::get_instance();
    // if (!$submission) return;

    // $form_id = $module_id;
    // $posted_data = $submission->get_posted_data();
    
    $data = [];
    $note = "";

    // error_log( print_r( $field_data_array[2]['value'], true ), 0 );
    
    // Only proceed if the correct form ID is submitted
    if( $module_id == 22872 || $module_id == 22864 ) { // 13415 & 20153
        // Prepare the lead data (for both person and lead)
        $data = [
            'visible_to' => 1, // You can modify visibility as needed
            '11c8a62c8e3e67b47c39455e9d4642adcc8bf7fc' => $field_data_array[0]['value'], // 'productname' // Custom field for product name
            'name' => $field_data_array[1]['value'], // 'your-name'
            'email' => [['value' => $field_data_array[2]['value'], 'primary' => true]], // 'your-email'
            '110c8318de2bb6efe3d4f32ef9d773ef529af74d' => $field_data_array[3]['value'], // 'product-number' // Custom field for product number
            'bced2c991ee12c85a46c41cf2bf64eaab13a3136' => $field_data_array[4]['value'], // 'select-location' // Select location
            '86ad3f581010d8552eae6952aafe88e43c6e88be' => $field_data_array[5]['value'], // 'product-message' // Product message
            '55f8fc9267e581d52bf99b497b41de0c9fe4d93e' => $field_data_array[6]['value'], // 'select-timber' // Timber selection
            'd791ff75732dd92c2eda661c5e5268b6c2e9e580' => $field_data_array[7]['value'], // 'product-size' // Product size
            'f66a1182fd1b84d5a6da88658be41b927f5f7b6a' => $field_data_array[8]['value'], // $field_data_array['referer-page'], // Referrer page
            'fee9b9e355cd16844cf195250579235b1ea4eef5' => $field_data_array[8]['value'], // $field_data_array['handl_url'] // Form URL
        ];
        
        $note = '<strong>Submit time:</strong> ' . date('F d, Y h:i:sa') . '<br/>' .
                '<strong>productname:</strong> ' . ($field_data_array[0]['value'] ?? 'N/A') . '<br/>' .
                '<strong>your-name:</strong> ' . ($field_data_array[1]['value'] ?? 'N/A') . '<br/>' .
                '<strong>your-email:</strong> ' . ($field_data_array[2]['value'] ?? 'N/A') . '<br/>' .
                '<strong>product-number:</strong> ' . ($field_data_array[3]['value'] ?? 'N/A') . '<br/>' .
                '<strong>select-location:</strong> ' . ($field_data_array[4]['value'] ?? 'N/A') . '<br/>' .
                '<strong>product-message:</strong> ' . ($field_data_array[5]['value'] ?? 'N/A') . '<br/>' .
                '<strong>select-timber:</strong> ' . ($field_data_array[6]['value'] ?? 'N/A') . '<br/>' .
                '<strong>product-size:</strong> ' . ($field_data_array[7]['value'] ?? 'N/A') . '<br/>' .
                '<strong>referer-page:</strong> ' . ($field_data_array[8]['value'] ?? 'N/A') . '<br/>' .
                '<strong>formUrl:</strong> ' . ($field_data_array[8]['value'] ?? 'N/A') . '<br/>';
                
        $activity_subject = "Form submit: Product popup form";
    }

    
    if ($module_id == 22615) { // 13416
        // Prepare the lead data (for both person and lead)
        $data = [
            'name' => $field_data_array[0]['value'],
            'email' => [['value' => $field_data_array[1]['value'], 'primary' => true]],
            'visible_to' => 1, // You can modify visibility as needed
            'c5bff58ed8c23fd9435751d8c65f395c31ea5c9e' => $field_data_array[3]['value'],
            'f66a1182fd1b84d5a6da88658be41b927f5f7b6a' => $url, // $posted_data['referer-page'], // Referrer page
            'fee9b9e355cd16844cf195250579235b1ea4eef5' => $url, // $posted_data['handl_url'] // Form URL
        ];
        
        $note = '<strong>Submit time:</strong> ' . date('F d, Y h:i:sa') . '<br/>' .
              '<strong>your-name:</strong> ' . ($field_data_array[0]['value'] ?? 'N/A') . '<br/>' .
              '<strong>your-email:</strong> ' . ($field_data_array[1]['value'] ?? 'N/A') . '<br/>' .
              '<strong>message:</strong> ' . ($field_data_array[3]['value'] ?? 'N/A') . '<br/>' .
              '<strong>referer-page:</strong> ' . ($url ?? 'N/A') . '<br/>' .
              '<strong>formUrl:</strong> ' . ($url ?? 'N/A') . '<br/>';
        
        $activity_subject = "Form submit: Contact form";
        
    }

    // error_log( print_r( $data, true ), 0 );
    // error_log( print_r( $note, true ), 0 );
    
    if( $module_id != 22872 || $module_id != 22864 || $module_id != 22615 ) {
        return;
    } 

    // Send the person data to Pipedrive
    $persons_response = wp_remote_post($persons_endpoint, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode( $data )
    ]);
    
    // Handle any errors from the API response
    if( is_wp_error( $persons_response ) ) {
        error_log( 'Pipedrive API Error (Person): ' . $persons_response->get_error_message() );
        return;
    }

    $person_body = json_decode( wp_remote_retrieve_body( $persons_response ), true );
    
    if( isset( $person_body['data']['id'] ) ) {
        // Successfully created the person, now get the person ID
        $person_id = $person_body['data']['id'];

        // Create the lead in Pipedrive
        $lead_data = [
            'title' => "Lead Created: " . $field_data_array[1]['value'],
            'person_id' => $person_id, // Associate lead with the person
            'visible_to' => "1", // Adjust visibility
        ];

        // Send the lead data to Pipedrive
        $lead_response = wp_remote_post( $leads_endpoint, [
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'body' => json_encode( $lead_data )
        ] );

        if( is_wp_error( $lead_response ) ) {
            error_log( 'Pipedrive API Error (Lead): ' . $lead_response->get_error_message() );
        } else {
            error_log( 'Pipedrive Lead Response: ' . wp_remote_retrieve_body( $lead_response ) );
        }

        // Add an activity for this person in Pipedrive
        $activity_endpoint = "https://api.pipedrive.com/v1/activities?api_token={$pipedrive_api_token}";
        

        // Prepare activity data
        $activity_data = [
            'subject' => $activity_subject,
            'type' => 'Form Submitted', // Type of activity (e.g., call, meeting, etc.)
            'due_date' => date('Y-m-d'), // Due date (today)
            'person_id' => $person_id, // Associate activity with person
            'note' => $note
        ];

        // Send the activity data to Pipedrive
        $activity_response = wp_remote_post( $activity_endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode( $activity_data ) 
        ] );

        if( is_wp_error( $activity_response ) ) {
            error_log( 'Pipedrive API Error (Activity): ' . $activity_response->get_error_message() );
        } else {
            error_log( 'Pipedrive Activity Response: ' . wp_remote_retrieve_body( $activity_response ) );
        }
    } else {
        error_log( 'Pipedrive Person Response Error: ' . wp_remote_retrieve_body( $persons_response ) );
    }
}

// function cf7_to_pipedrive_test($contact_form) {
//     $submission = WPCF7_Submission::get_instance();
//     if (!$submission) return;

//     $form_id = $contact_form->id();
//     $posted_data = $submission->get_posted_data();
    
//     $settings = get_option('cf7_pipedrive_settings');
//     $pipedrive_api_token = $settings['api_token'] ?? '';
//     if (empty($pipedrive_api_token)) return;

//     // Endpoint for creating a person (lead) in Pipedrive
//     $persons_endpoint = "https://api.pipedrive.com/v1/persons?api_token={$pipedrive_api_token}";
    
//     // Endpoint for creating a lead in Pipedrive
//     $leads_endpoint = "https://api.pipedrive.com/v1/leads?api_token={$pipedrive_api_token}";
    

//     $lead_data = [
//         'title' => "Lead Created: " . $posted_data['your-email'],
//         'person_id' => 455, // Associate lead with the person
//         'visible_to' => "1", // Adjust visibility
//     ];

//     $lead_response = wp_remote_post($leads_endpoint, [
//         'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
//         'body' => json_encode($lead_data)
//     ]);


//     if (is_wp_error($lead_response)) {
//         error_log('Pipedrive API Error (Lead): ' . $lead_response->get_error_message());
//     } else {
//         error_log('Pipedrive Lead Response: ' . wp_remote_retrieve_body($lead_response));
//     }
// }

add_action( 'forminator_custom_form_submit_before_set_fields', 'is_triggered', 20, 3 );

function jarrimber_add_product_name( $html, $field ) {
    if( isset( $field['custom-class'] ) && $field['custom-class'] === "product-field" ) {
        if( get_post_type() === "product" && is_single() ) {
            $title = get_the_title();

            $regex = [
                "/value=\"\"/i", 
            ];
            $replace = [
                'value="' . $title . '"',
            ];
    
            return preg_replace( $regex, $replace, $html );
        }   
    }

    return $html;
}

add_filter( 'forminator_field_text_markup', 'jarrimber_add_product_name', 20, 2 );