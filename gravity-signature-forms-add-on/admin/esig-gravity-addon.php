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

        /**
         * Initialize the add-on.
         *
         * Registers custom hooks on top of the parent GFFeedAddOn initialization,
         * including the owner-email collision validation for form submissions.
         *
         * @since 2.0.4
         *
         * @return void
         */
        public function init() {
            parent::init();
            add_filter( 'gform_validation', array( $this, 'validate_owner_email_collision' ) );
        }

        /**
         * Validate that the signer email does not match the Auto-Add My Signature owner email.
         *
         * When a Gravity Form is linked to a Stand-Alone Document that has Auto-Add My
         * Signature enabled, the owner's signature is automatically joined to every copy
         * of that template. Allowing the owner's email as the signer email would produce a
         * broken duplicate-signing state — the copied document would appear fully signed
         * immediately and the invitee would never receive a real signing step.
         *
         * This validation fires via `gform_validation` BEFORE the form entry is saved, so
         * no orphaned document copies or GF entries are created when a collision is detected.
         * The email field is flagged with an inline validation error visible to the submitter.
         *
         * @since 2.0.4
         *
         * @param array $validation_result {
         *     GF validation context array passed by the `gform_validation` filter.
         *
         *     @type bool  $is_valid Whether the form passed all validation checks.
         *     @type array $form     The current Gravity Forms form array, including fields.
         * }
         *
         * @return array Modified $validation_result with `is_valid` set to false and the
         *               configured email field flagged when an owner email collision is detected.
         *               Returned unchanged when no collision exists or prerequisites are missing.
         */
        public function validate_owner_email_collision( $validation_result ) {

            if ( ! function_exists( 'WP_E_Sig' ) || ! class_exists( 'esig_sad_document' ) ) {
                return $validation_result;
            }

            $form    = $validation_result['form'];
            $form_id = absint( $form['id'] );

            // Fetch all active GF feeds for this form that belong to this add-on.
            $feeds = GFAPI::get_feeds( null, $form_id, $this->_slug, true );
            if ( empty( $feeds ) || ! is_array( $feeds ) ) {
                return $validation_result;
            }

            $sad = new esig_sad_document();
            $api = WP_E_Sig();

            foreach ( $feeds as $feed ) {

                // Skip inactive feeds.
                if ( empty( $feed['is_active'] ) ) {
                    continue;
                }

                $sad_page_id = rgar( $feed['meta'], 'esig_gf_sad' );
                if ( ! $sad_page_id ) {
                    continue;
                }

                $document_id = $sad->get_sad_id( $sad_page_id );
                if ( ! $document_id ) {
                    continue;
                }

                // Only act on stand-alone documents.
                if ( 'stand_alone' !== $api->document->getStatus( $document_id ) ) {
                    continue;
                }

                /*
                 * Auto-Add My Signature email-collision guard (TRL-1607).
                 *
                 * Check if Auto-Add My Signature is enabled on the linked template.
                 * If the submitted signer email matches the auto-add owner email, block
                 * the form submission with an inline field validation error before any
                 * document copy or GF entry is created.
                 *
                 * @since 2.0.4
                 */
                $auto_add_enabled      = $api->meta->get( $document_id, 'auto_add_signature' );
                $auto_add_signature_id = $api->meta->get( $document_id, 'auto_add_signature_id' );

                if ( ! $auto_add_enabled || ! $auto_add_signature_id ) {
                    continue;
                }

                // Resolve the auto-add owner's email via the signature model.
                if ( ! class_exists( '\WpEsignature\Models\Signature' ) ) {
                    continue;
                }

                $signature_model  = \WpEsignature\Models\Signature::getInstance();
                $auto_add_user_id = $signature_model->getuserid_by_signature_id( $auto_add_signature_id );

                if ( ! $auto_add_user_id ) {
                    continue;
                }

                $auto_add_user = $api->user->getUserByID( $auto_add_user_id );

                if ( ! $auto_add_user || empty( $auto_add_user->user_email ) ) {
                    continue;
                }

                // Get the submitted value from the configured email field.
                $email_field_id  = rgar( $feed['meta'], 'esig_signer_email' );
                $submitted_email = rgpost( 'input_' . $email_field_id );

                if ( empty( $submitted_email ) ) {
                    continue;
                }

                // Case-insensitive comparison — email addresses are not case-sensitive.
                if ( strtolower( trim( $auto_add_user->user_email ) ) !== strtolower( trim( $submitted_email ) ) ) {
                    continue;
                }

                // Collision detected: mark the email field as failed with an inline error.
                $validation_result['is_valid'] = false;

                foreach ( $form['fields'] as &$field ) {
                    if ( (int) $field->id === (int) $email_field_id ) {
                        $field->failed_validation  = true;
                        $field->validation_message = esc_html__(
                            'This email address belongs to the document owner and cannot be used as a signer.',
                            'esig-gf'
                        );
                        break;
                    }
                }
                unset( $field );

                $validation_result['form'] = $form;

                // One collision is enough — stop checking further feeds.
                return $validation_result;
            }

            return $validation_result;
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

            $prefix         = version_compare( GFForms::$version, '2.5', '>=' ) ? '_gform_setting_' : '_gaddon_setting_';
            $enableReminder = ESIG_POST( $prefix . 'esig_reminder_email' );

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
            if ( version_compare( GFForms::$version, '2.5', '<' ) ) {
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
            if ( version_compare( GFForms::$version, '2.5', '<' ) ) {
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

            if ( version_compare( GFForms::$version, '2.5', '<' ) ) {
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
            // getting document titile ; 
            $document = \WpEsignature\Models\Document::getInstance()->getDocument($document_id);

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

            if ( ! class_exists( 'esig_sad_document' ) ) {
                return false;
            }

            // Guard: skip if the linked WordPress page is trashed, drafted, or
            // deleted. Published, private, and password-protected pages are all
            // allowed. See TRL-1621.
            $page_status = get_post_status( absint( $sad_page_id ) );
            if ( false === $page_status || 'trash' === $page_status || 'draft' === $page_status ) {
                return false;
            }

            $sad = new esig_sad_document();

            $document_id = $sad->get_sad_id( $sad_page_id );

            $docStatus = WP_E_Sig()->document->getStatus( $document_id );

            if ( 'stand_alone' !== $docStatus ) {
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

        /**
         * Retrieve the signer's name from a Gravity Forms entry field.
         *
         * For Name-type fields the value is assembled via GF_Field::get_value_entry_detail()
         * using a version-aware call (GF 2.9.29 changed the 2nd parameter from a currency
         * string to the full entry array; GFCommon::get_lead_field_display() was deprecated
         * in GF 2.9.31). For all other field types the raw entry value is returned directly.
         *
         * @since 2.0.3
         *
         * @param int   $form_id  Gravity Forms form ID.
         * @param int   $field_id Gravity Forms field ID mapped to the signer name.
         * @param array $entry    Full GF entry array.
         *
         * @return string|false Formatted signer name, or false when the field is not found.
         */
        public function esig_get_signer_name( $form_id, $field_id, $entry ) {

            $forms  = GFAPI::get_form( $form_id );
            $fields = GFFormsModel::get_field( $forms, $field_id );

            if ( ! $fields instanceof GF_Field ) {
                return false;
            }

            $input_type = GFFormsModel::get_input_type( $fields );

            if ( 'name' === $input_type ) {
                $value = GFFormsModel::get_lead_field_value( $entry, $fields );

                if ( class_exists( 'GFCommon' ) && version_compare( GFCommon::$version, '2.9.29', '>=' ) ) {
                    // GF 2.9.29+ expects the full entry array; GFCommon::get_lead_field_display()
                    // was deprecated in 2.9.31 so call get_value_entry_detail() directly.
                    return $fields->get_value_entry_detail( $value, $entry, false, 'html' );
                }

                // GF < 2.9.29 — pass currency string as 2nd parameter.
                $currency = rgar( $entry, 'currency' );
                return $fields->get_value_entry_detail( $value, $currency, false, 'html' );
            }

            return isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : false;
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
            $doc = $api->document->copyDocument($old_doc_id, $args);
            $doc_id = $doc->get('document_id');

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

        /**
         * Build the list of Stand Alone Document pages for the feed settings dropdown.
         *
         * Includes published Stand Alone Documents, as well as trashed or draft documents/pages
         * (labeled with status) so that existing feed selections are not lost when a document
         * or page is moved to trash. Permanently deleted orphans are excluded unless they
         * match the currently selected feed setting. See TRL-1617, TRL-1620.
         *
         * @since  2.0.4
         * @access private
         *
         * @return array GF feed settings choices array.
         */
        private function get_sad_documents() {

            if ( ! function_exists( 'WP_E_Sig' ) ) {
                return array();
            }

            if ( ! class_exists( 'esig_sad_document' ) ) {
                return array();
            }

            $api = WP_E_Sig();
            $sad = new esig_sad_document();

            $sad_pages = $sad->esig_get_sad_pages();

            $choices   = array();
            $choices[] = array(
                'label' => __( 'Please select a stand alone document', 'esig-gf' ),
                'value' => '',
            );

            if ( empty( $sad_pages ) || ! is_array( $sad_pages ) ) {
                return $choices;
            }

            $current_sad_id = absint( $this->get_setting( 'esig_gf_sad' ) );

            foreach ( $sad_pages as $page ) {

                $page_id     = absint( $page->page_id );
                $document_id = absint( $page->document_id );

                if ( ! $page_id && ! $document_id ) {
                    continue;
                }

                $document_status = $document_id ? $api->document->getStatus( $document_id ) : null;
                $page_status     = $page_id ? get_post_status( $page_id ) : false;
                $is_current      = ( $current_sad_id > 0 && $page_id === $current_sad_id );

                // Permanently deleted document record and non-existent post:
                // skip unless this is the feed's currently selected setting.
                if ( empty( $document_status ) && false === $page_status && ! $is_current ) {
                    continue;
                }

                $title = get_the_title( $page_id );
                if ( empty( $title ) ) {
                    $title = __( 'Document #', 'esig-gf' ) . $document_id;
                }

                // Determine appropriate label suffix based on status.
                if ( 'trash' === $page_status || 'trash' === $document_status ) {
                    $label = $title . ' ' . __( '(Trashed)', 'esig-gf' );
                } elseif ( 'draft' === $page_status || 'draft' === $document_status ) {
                    $label = $title . ' ' . __( '(Draft)', 'esig-gf' );
                } elseif ( empty( $document_status ) || false === $page_status ) {
                    $label = $title . ' ' . __( '(Deleted)', 'esig-gf' );
                } else {
                    $label = $title;
                }

                $choices[] = array(
                    'label' => $label,
                    'value' => $page_id,
                );
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