<?php

if (class_exists("GFForms")) {

    GFForms::include_feed_addon_framework();

    class GFEsignAddOn extends GFFeedAddOn {

        protected $_version = "1.1.0";
        protected $_min_gravityforms_version = "1.9.2";
        protected $_slug = "esig-gf";
        protected $_path = "gravity-signature-forms-add-on/gravity-signature-forms-addon.php";
        protected $_full_path = __FILE__;
        protected $_title = "WP E-Signature - Digital Signature Workflow";
        protected $_short_title = "WP E-Signature";
        
        private static $_instance = null;
        /**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.5.5.3
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_wp_e_signature';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.5.5.3
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_wp_e_signature';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.5.5.3
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_wp_e_signature_uninstall';

	/**
	 * Defines the capabilities needed for the Post Creation Add-On
	 *
	 * @since  1.5.5.3
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_wp_e_signature', 'gravityforms_wp_e_signature_uninstall' );

        //  protected $_capabilities = array();

        public static function get_instance() {
            if (self::$_instance == null) {
                self::$_instance = new GFEsignAddOn();
            }

            return self::$_instance;
        }

        public function feed_settings_fields() {

            return array(
                array(
                    "title" => __("WP E-Signature Settings", "esig-gf"),
                    "fields" => array(
                        array(
                            "label" => __("Signing Logic", "esig-gf"),
                            "type" => "select",
                            "name" => "esign_gf_logic",
                            "choices" => $this->get_gf_logic()
                        ),
                        array(
                            "label" => __("Select Stand Alone Document", "esig-gf"),
                            "type" => "select",
                            "name" => "esig_gf_sad",
                            "tooltip" => "",
                            "choices" => $this->get_sad_documents(),
                            "required" => true
                        ),
                        array(
                            "label" => "",
                            "type" => "esig_sub_field",
                            "name" => "esig_create_document",
                            "class" => "gf_sub_settings_cell"
                        ),
                        array(
                            "label" => __("Signer Name", "esig-gf"),
                            "type" => "select",
                            "name" => "esig_signer_name",
                            "tooltip" => __("Select the name field from your gravity form.  This field is what the signers full name will be on their WP E-Signature contract.", "esig-gf"),
                            'choices' => $this->get_field_choice('name'),
                            "required" => true
                        ),
                        array(
                            "label" => __("Signer E-mail", "esig-gf"),
                            "type" => "select",
                            "name" => "esig_signer_email",
                            "tooltip" => __("Select the email field of your signer from your gravity form fields.  This field is what the signers email address will be on their WP E-Signature contract.", "esig-gf"),
                            "validation_callback" => array($this, 'esig_select_valiation'),
                            'choices' => $this->get_field_choice('email'),
                            "required" => true
                        ),
                        array(
                            "label" => "",
                            "type" => "select",
                            "name" => "esig_display_option",
                            "choices" => $this->esig_display_option(),
                            "required" => false
                        ),
                        array(
                            'type' => 'feed_condition',
                            'name' => 'esig_condition',
                            'label' => __('Feed Condition', 'esig-gf'),
                            'instructions' => __('Process W-signature workflow IF', 'esig-gf')
                        ),
                        array(
                            "label" => __("Signing Reminder Emails", "esig-gf"),
                            "type" => "checkbox",
                            "name" => "esig_reminder_email",
                            "choices" => array(
                                array(
                                    "label" => __("Enable signing reminder emails. If/When user has not signed the document", "esig-gf"),
                                    "name" => "esig_reminder_email"
                                )
                            ),
                            "required" => false
                        ),
                        array(
                            "label" => " ",
                            "type" => "esig_reminder",
                            "name" => "esig_send_reminder",
                            "class" => "small small-text",
                            'size' => '3',
                            "validation_callback" => array($this, 'reminderValidation'),
                        ),
                        array(
                            "label" => " ",
                            "type" => "esig_reminder_repeat",
                            "name" => "esig_send_reminder_repeat",
                            "class" => "small small-text",
                            'size' => '3',
                            "validation_callback" => array($this, 'reminderValidation'),
                        ),
                        array(
                            "label" => " ",
                            "type" => "esig_reminder_expire",
                            "name" => "esig_send_reminder_expire",
                            "class" => "small small-text",
                            'size' => '3',
                            "validation_callback" => array($this, 'reminderValidation'),
                        ),
                        array(
                            "label" => "",
                            "type" => "esig_script_load",
                            "name" => "esig_script_load",
                            "class" => "gf_sub_settings_cell"
                        ),
                    )
                )
            );
        }

        /**
         * Return the plugin's icon for the plugin/form settings menu.
         *
         * @since 1.5.7.5
         *
         * @return string
         */
        public function get_menu_icon()
        {
            
            return file_get_contents(ESIG_GRAVITY_ADDON_PATH . '/admin/assets/images/pen_icon_gray.svg');
        }

        // esignature gravity addon custom email fields 
        protected function settings_esig_select($field, $echo = true) {
            //	$field['type'] = 'select'; // making sure type is set to select
            //	$attributes    = $this->get_field_attributes( $field );

            $value = $this->get_setting($field['name']);

            $html = '';

            $html .= '<select name="_gaddon_setting_' . $field['name'] . '">';


            $form_fields = $this->get_field_choice('all');

            foreach ($form_fields as $key => $option) {
                $selected = ($option['value'] == $value) ? "selected" : " ";
                $html .= '<option value="' . $option['value'] . '" ' . $selected . '>' . $option['label'] . '</option>';
            }

            $html .= '</select>';

            if (!$this->esig_valid_email($value)) {
                $html .= '<span class="esig-error">' . __('Please select an valid email field', 'esig-gf') . '</span>';
            }

            echo $html;
        }

        public function esig_valid_email($value, $form_id = false) {
            if (!function_exists('WP_E_Sig'))
                return;
            // form id 
            $form_id = ESIG_GET('id');

            $forms = GFAPI::get_form($form_id);

            $fields = GFFormsModel::get_field($forms, $value);

            $input_type = GFFormsModel::get_input_type($fields);



            //
            if ($input_type != 'email') {
                return false;
            }

            return true;
        }

        protected function esig_select_valiation($field, $field_setting) {
            
            if (!function_exists('WP_E_Sig'))
                return;

            $value = $this->get_setting($field['name']);

            $form_id = ESIG_GET('id');

            $forms = GFAPI::get_form($form_id);

            $fields = GFFormsModel::get_field($forms, $value);

            $input_type = GFFormsModel::get_input_type($fields);

            if (empty($value)) {
                $this->set_field_error($field, __('Please select an valid email fields', 'esig-gf'));
            }

            if ($input_type != 'email') {
                $this->set_field_error($field, __('Please select an valid email field', 'esig-gf'));
            }

            // If there are still active lists, this is valid.
        }

        public function reminderValidation($field, $field_setting) 
        {
            $value = $this->get_setting($field['name']);
            
            if(!function_exists("ESIG_POST"))
            {
                return false;
            }

            $enableReminder = ESIG_POST("_gform_setting_esig_reminder_email"); 

            if(!$enableReminder)
            {
                return false;
            } 

            if (empty($value)) 
            {
                $this->set_field_error($field, __('Value can not be empty', 'esig-gf'));
                return false;
            }
            if ($value > 0) {
                return true;
            } else {
                $this->set_field_error($field, __('Only positive number is allowed', 'esig-gf'));
            }
        }

        public function settings_esig_reminder($field, $echo = true) {
           
            $value = $this->get_setting($field['name']);
            if(GFForms::$version < 2.5){
                $submitType = "_gaddon_setting_";
            }
            else {
               $submitType = "_gform_setting_"; 
            }
            $html = esc_html__('Send the first reminder to the signer', 'esig-gf') . '<input type="text" name="' . $submitType . $field['name'] . '" maxlength="3" min="0" oninput="this.value = (!isNaN(Math.abs(this.value)) && this.value>0)?Math.abs(this.value):null" style="margin-left:1%;width:75px;" class="' . $field['class'] . '" size="' . $field['size'] . '" value="' . $value . '"> ' . __("days after the initial signing request.", "esig-gf");
            if($echo){
                echo $html;
            }
            return $html;
        }

        public function settings_esig_reminder_repeat($field, $echo = true) {

            $value = $this->get_setting($field['name']);
            if(GFForms::$version < 2.5){
                $submitType = "_gaddon_setting_";
            }
            else {
               $submitType = "_gform_setting_"; 
            }
            $html = esc_html__('Send the second reminder to the signer', 'esig-gf') . ' <input type="text" name="' . $submitType . $field['name'] . '" style="margin-left:1%;width:75px;" maxlength="3" min="0" oninput="this.value = (!isNaN(Math.abs(this.value)) && this.value>0)?Math.abs(this.value):null" class="' . $field['class'] . '" size="' . $field['size'] . '" value="' . $value . '"> ' . __('days after the initial signing request.', 'esig-gf');
             if($echo){
                echo $html;
            }
            return $html;
        }

        public function settings_esig_reminder_expire($field, $echo = true) {

            $value = $this->get_setting($field['name']);
            
            if(GFForms::$version < 2.5){
                $submitType = "_gaddon_setting_";
            }
            else {
               $submitType = "_gform_setting_"; 
            }
          
            $html = esc_html__('Send the last reminder to the signer', 'esig-gf') . ' <input type="text" name="' . $submitType . $field['name'] . '" class="' . $field['class'] . '" style="margin-left:1%;width:75px;" min="0" oninput="this.value = (!isNaN(Math.abs(this.value)) && this.value>0)?Math.abs(this.value):null" maxlength="3" size="' . $field['size'] . '" value="' . $value . '"> ' . __('days after the initial signing request.', 'esig-gf');
             if($echo){
                echo $html;
            }
            return $html;
        }

        public function settings_esig_script_load($field, $echo = true)
        {
            
            include_once ESIG_GRAVITY_ADDON_PATH . "/admin/includes/esig-gf-script-settings.php";

            return false;
        }

        public function settings_esig_sub_field($field, $echo = true) {
            $html = __('If you would like to you can <a href="edit.php?post_type=esign&page=esign-add-document&esig_type=sad">create new document</a>', 'esig-gf');
             if($echo){
                echo $html;
            }
            return $html;
        }

        // fgravity list title 
        public function feed_list_title() {

            // if (!$this->get_feeds($_GET['id'])) {
            $url = add_query_arg(array('fid' => '0'));

            $add_new = " <a class='add-new-h2' href='{$url}'>" . __('Add New', 'gravityforms') . '</a>';
            // } else {
            //  $add_new = "";
            //}

            return '<span class="esig-icon-wp-e-signature"></span>' . sprintf(__('%s Workflow', 'esig-gf'), $this->get_short_title()) . $add_new;
        }

        public function feed_settings_title() {
            return '<span class="esig-icon-wp-e-signature"></span>' . esc_html__('WP E-Signature - Digital Signature Workflow', 'esig-gf');
        }

        // setting feed no item msg 

        public function feed_list_no_item_message() {
            return __('This form does not have any WP E-Signature workflows connected to it. Let\'s go to', 'esig-gf') . ' <a href="' . add_query_arg(array('fid' => 0)) . '">' . __('create one.', 'esig-gf') . '</a>';
        }

        //setting feed list column
        public function feed_list_columns() {
            return array(
                'esig_gf_document_name' => __('Name', 'esig-gf'),
                'document_type' => __('Workflow Action', 'esig-gf'),
            );
        }

        // getting document name column 
        public function get_column_value_esig_gf_document_name($feed) {

            $sad_page_id = $feed['meta']['esig_gf_sad'];

            if (!class_exists('esig_sad_document'))
                return;

            $sad = new esig_sad_document();

            $document_id = $sad->get_sad_id($sad_page_id);
            if (!$document_id) {
                return;
            }
            $document_model = new WP_E_Document();
            // getting document titile ; 
            $document = $document_model->getDocument($document_id);

            return '<a href="edit.php?post_type=esign&page=esign-edit-document&document_id=' . $document_id . '">' . $document->document_title . '</a>';
        }

        // getting document type column value 

        public function get_column_value_document_type($feed) {

            $signing_logic = $feed['meta']['esign_gf_logic'];

            if ($signing_logic == "redirect") {
                return "Redirect user to Contract/Agreement after Submission";
            } elseif ($signing_logic == "email") {
                return "Send User an Email Requesting their Signature after Submission";
            } else {
                return "No action";
            }
        }

        // processing feed here 
        public function process_feed($feed, $entry, $form) {


            // getting document page id  from feed 
            if (ESIG_GF_SETTINGS::checkCompatability($feed, $entry, $form)) {
                return false;
            }



            $sad_page_id = $feed['meta']['esig_gf_sad'];

            if (!class_exists('esig_sad_document'))
                return false;

            $sad = new esig_sad_document();

            $document_id = $sad->get_sad_id($sad_page_id);
            
            $docStatus  = WP_E_Sig()->document->getStatus($document_id);
            
            if($docStatus !="stand_alone"){
                return false;
            }
            
            
            $form_id = $entry['form_id'];

            // getting entry and form id 
            $entry_id = $entry['id'];

           /* if (get_transient("esig-gf-agreement-created" . esig_get_ip())) {
                set_transient("esig-gf-wc-agreement" . esig_get_ip(), "yes", 120);
                delete_transient("esig-gf-agreement-created" . esig_get_ip());
                return false;
            }
            else {
                delete_transient("esig-gf-wc-agreement" . esig_get_ip());
            }*/


            $signing_logic = $feed['meta']['esign_gf_logic'];

            $signer_name_feed = $feed['meta']['esig_signer_name'];

            $signer_email_feed = $feed['meta']['esig_signer_email'];

            $display_feed = $feed['meta']['esig_display_option'];
            // getting signer name and email address from entry 
            $signer_name = $this->esig_get_signer_name($entry['form_id'], $signer_name_feed, $entry); //$entry[$signer_name_feed];

            $signer_email = $entry[$signer_email_feed];

            if (!is_email($signer_email)) {
                return false;
            }


            $feedId = $feed['id'];

            // saving entry 
            ESIG_GF_SETTINGS::saveEntry($document_id, $entry);

            // sending email invitation / redirecting . 
            $result = $this->esig_invite_document($document_id,$feed, $signer_email, $signer_name, $form_id, $entry_id, $signing_logic, $display_feed, $feedId);
            return false;
        }

        public function esig_get_signer_name($form_id, $field_id, $entry) {

            $forms = GFAPI::get_form($form_id);
            $fields = GFFormsModel::get_field($forms, $field_id);
            $input_type = GFFormsModel::get_input_type($fields);

            if ($input_type == "name") {
                $name_input = GFCommon::get_lead_field_display($fields, $entry, false, false, false);

                /* $lastKey = end(array_keys($fields['inputs']));
                  foreach ($fields['inputs'] as $key =>$field) {
                  if ($key === $lastKey) {
                  $name_input .= $entry[$field['id']];
                  }
                  else {
                  $name_input .= $entry[$field['id']]." ";
                  }
                  } */

                return $name_input;
            } else {
                return $entry[$field_id];
            }
        }

        private function enableReminder($feed,$docId)
        {
            // reminder settings here 
            $esig_reminder_enable = $feed['meta']['esig_reminder_email'];

            if ($esig_reminder_enable) {
                $send_reminder_days = isset($feed['meta']['esig_send_reminder']) ? $feed['meta']['esig_send_reminder'] : false;
                $send_reminder_repeat = isset($feed['meta']['esig_send_reminder_repeat']) ? $feed['meta']['esig_send_reminder_repeat'] : false;
                $send_reminder_expire = isset($feed['meta']['esig_send_reminder_expire']) ? $feed['meta']['esig_send_reminder_expire'] : false;

                $api = new WP_E_Api();
                // saving remidner meta here 
                $esig_reminders_settings = array(
                    "esig_reminder_for" => absint($send_reminder_days),
                    "esig_reminder_repeat" => absint($send_reminder_repeat),
                    "esig_reminder_expire" => absint($send_reminder_expire),
                );
                // saving into database 
                WP_E_Sig()->meta->add($docId, "esig_reminder_settings_", json_encode($esig_reminders_settings));
                // setting reminder start
                WP_E_Sig()->meta->add($docId, "esig_reminder_send_", "1");

            }

        }

        public function esig_invite_document($old_doc_id,$feed, $email, $signer_name, $form_id, $entry_id, $signing_logic, $display_feed = false, $feedId=false) {

            if (!function_exists('WP_E_Sig'))
                return;


            $api = WP_E_Sig();

            /* make it a basic document and then send to sign */
            $old_doc = $api->document->getDocument($old_doc_id);

            // Copy the document
            global $esigGfSubmission,$esigGFDisplayFeed;

            $args = array(
                "entryId" => $entry_id,
                "formId" => $form_id,
                "integrationType" => "esig-gravity",
            );
            
            $esigGfSubmission = $args;
            $esigGFDisplayFeed = $display_feed;
            $doc_id = $api->document->copy($old_doc_id,$args);

            // settings meta key for gravity form field 

            $api->meta->add($doc_id, 'esig_gravity_form_id', $form_id);

            $api->meta->add($doc_id, 'esig_gravity_entry_id', $entry_id);
            $api->meta->add($doc_id, 'esig_gravity_feed_id_' . $feedId, $entry_id);

            $api->document->saveFormIntegration($doc_id, 'gravity');

            ESIG_GF_SETTINGS::save_display_feed($doc_id, $display_feed);


            // set document timezone
            $esig_common = new WP_E_Common();
            $esig_common->set_document_timezone($doc_id);
            // Create the user
            $recipient = array(
                "user_email" => $email,
                "first_name" => $signer_name,
                "document_id" => $doc_id,
                "wp_user_id" => '',
                "user_title" => '',
                "last_name" => ''
            );

            $recipient['id'] = $api->user->insert($recipient);

            $doc_title = $old_doc->document_title . ' - ' . $signer_name;
            // Update the doc title

            $api->document->updateTitle($doc_id, $doc_title);
            $api->document->updateType($doc_id, 'normal');
            $api->document->updateStatus($doc_id, 'awaiting');

            $doc = $api->document->getDocument($doc_id);

            // trigger an action after document save .
            do_action('esig_sad_document_invite_send', array(
                'document' => $doc,
                'old_doc_id' => $old_doc_id,
                'signer_id' => $recipient['id'],
            ));


            // save reminders here 
            $this->enableReminder($feed,$doc_id);

            // Get Owner
            $owner = $api->user->getUserByID($doc->user_id);

            // Create the invitation?
            $invitation = array(
                "recipient_id" => $recipient['id'],
                "recipient_email" => $recipient['user_email'],
                "recipient_name" => $recipient['first_name'],
                "document_id" => $doc_id,
                "document_title" => $doc->document_title,
                "sender_name" => $owner->first_name . ' ' . $owner->last_name,
                "sender_email" => $owner->user_email,
                "sender_id" => 'stand alone',
                "document_checksum" => $doc->document_checksum,
                "sad_doc_id" => $old_doc_id,
            );

            $invite_controller = new WP_E_invitationsController();
            if ($signing_logic == "email") {

                if ($invite_controller->saveThenSend($invitation, $doc)) {

                    return true;
                }
            } elseif ($signing_logic == "redirect") {
                // if used redirect then other plugin can not work properly. 

                $invitation_id = $invite_controller->save($invitation);
                $invite_hash = WP_E_Sig()->invite->getInviteHash($invitation_id);
                //$invitationURL = add_query_arg(array('invite' => $invite_hash, 'csum' => $doc->document_checksum), get_permalink($esign_default_page));

                ESIG_GF_SETTINGS::save_esig_gf_meta($invite_hash, "signed", "no",$entry_id);


                //if (defined('DOING_AJAX') && DOING_AJAX) {
                if (!get_transient("esig-gf-redirect-" . $entry_id . esig_get_ip())) {
                    set_transient("esig-gf-redirect-" . $entry_id . esig_get_ip(), WP_E_Sig()->invite->get_invite_url($invite_hash, $doc->document_checksum), 120);
                   // set_transient("esig-gf-wc-agreement" . esig_get_ip(), "yes", 120);
                    //set_transient("esig-gf-agreement-created" . esig_get_ip(), "yes", 120);
                    
                }
                return true;
                // }
                // wp_redirect($invitationURL);
                // exit;
            }
        }

        // gettings gf logics 
        private function get_gf_logic() {
            $choices[] = array(
                'label' => "Redirect user to Contract/Agreement after Submission",
                'value' => "redirect",
            );

            $choices[] = array(
                'label' => "Send User an Email Requesting their Signature after Submission",
                'value' => "email",
            );

            return $choices;
        }

        // getting display option 
        private function esig_display_option() {
            $choices[] = array(
                'label' => "Underline the data that was submitted from the Gravity Form",
                'value' => "underline",
            );

            $choices[] = array(
                'label' => "Do not underline the data that was submitted from the Gravity Form",
                'value' => "not_underline",
            );

            return $choices;
        }

        // gettings sad documents 
        private function get_sad_documents() {

            // 
            if (!function_exists('WP_E_Sig'))
                return;

            $api = WP_E_Sig();


            if (!class_exists('esig_sad_document'))
                return;

            $sad = new esig_sad_document();

            $sad_pages = $sad->esig_get_sad_pages();

            $choices[] = array(
                'label' => "Please select a stand alone document",
                'value' => "",
            );


            foreach ($sad_pages as $page) {
                $document_status = $api->document->getStatus($page->document_id);

                if ($document_status != 'trash') {
                    if ('publish' === get_post_status($page->page_id)) {
                        $choices[] = array(
                            'label' => get_the_title($page->page_id),
                            'value' => $page->page_id,
                        );
                    }
                }
            }

            return $choices;
        }

        // returns field choise 
        public function get_field_choice($name) {

            $form_id = rgar($_GET , 'id');

            $gravity_form = GFAPI::get_form($form_id);

            if($name != 'name' || $name != 'email'){
                $nameChoices[] = array(
                    'label' => "Please Select Signer Name",
                    'value' => '',
                );

                $emailChoices[] = array(
                    'label' => "Please Select Signer Email",
                    'value' => '',
                );
            }

            foreach ($gravity_form['fields'] as $field) {

                if($name == 'name' && $field->type == 'name' || $field->type == 'text' || $field->type == 'hidden'){
                    $nameChoices[] = array(
                        'label' => $field->label,
                        'value' => $field->id,
                    ); 
                }

                elseif($name == 'email' && $field->type == 'email'){
                    $emailChoices[] = array(
                        'label' => $field->label,
                        'value' => $field->id,
                    ); 
                }

                
            }

            if($name == 'name') return $nameChoices;
            else return $emailChoices;
            

            
        }

    }

}

//new GFEsignAddOn();