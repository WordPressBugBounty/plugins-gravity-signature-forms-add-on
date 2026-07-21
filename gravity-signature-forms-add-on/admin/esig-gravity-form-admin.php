<?php

/**
 *
 * @package ESIG_GRAVITY_Admin
 * @author  Abu Shoaib <abushoaib73@gmail.com>
 */
if (!class_exists('ESIG_GRAVITY_Admin')) :

    class ESIG_GRAVITY_Admin extends ESIG_GF_VALUE {

        /**
         * Instance of this class.
         * @since    1.0.1
         * @var      object
         */
        protected static $instance = null;
        private $plugin_slug, $documents_table, $document_view;

        /**
         * Slug of the plugin screen.
         * @since    1.0.1
         * @var      string
         */
        protected $plugin_screen_hook_suffix = null;

        /**
         * Initialize the plugin by loading admin scripts & styles and adding a
         * settings page and menu.
         * @since     0.1
         */
        private function __construct() {

            /*
             * Call $plugin_slug from public plugin class.
             */
            $plugin = ESIG_GRAVITY::get_instance();
            $this->plugin_slug = $plugin->get_plugin_slug();
            global $wpdb;
            $this->documents_table = $wpdb->prefix . 'esign_documents';

            $this->document_view = new esig_gravityform_document_view();

            // Load admin style sheet and JavaScript.
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            // Add an action link pointing to the options page.
            // adding action
            

            // add document more contents filter 
            add_filter('esig_admin_more_document_contents', array($this, 'document_add_data'), 10, 1);

            add_filter('esig_sif_buttons_filter', array($this, 'add_sif_gravity_buttons'), 10, 1);
            add_filter('esig_text_editor_sif_menu', array($this, 'add_sif_gf_text_menu'), 10, 1);

            //doing shortcode 
            add_shortcode('esiggravity', array($this, 'render_shortcode_esiggravity'));

            // ajax here 
            add_action('wp_ajax_esig_gravity_form_fields', array($this, 'esig_gravity_form_fields'));
           // add_action('wp_ajax_nopriv_esig_gravity_form_fields', array($this, 'esig_gravity_form_fields'));

            // Gravity core checking fallback. 
            //add_action('admin_notices', array($this, 'esig_gravity_addon_requirement'));
            //add_filter('esig_notices_display', array($this, 'esig_gravity_addon_requirement_modal'), 10, 1);

            add_action('admin_init', array($this, 'esig_almost_done_gravity_settings'));

            
            
            add_filter('gform_confirmation', array($this, 'reroute_confirmation'), 20, 4);

            add_filter('gform_confirmation', array($this, 'paypal_reroute_confirmation'), 19, 4);

            add_filter('show_sad_invite_link', array($this, 'show_sad_invite_link'), 10, 3);

            add_filter('esig_invite_not_sent', array($this, 'show_invite_error'), 10, 2);

            add_action('esig_signature_loaded', array($this, 'after_sign_check_next_agreement'), 99, 1);
            add_action('esig_agreement_after_display', array($this, 'esig_agreement_before_display'), 10, 1);

            add_action('woocommerce_add_to_cart', array($this, 'reroute_gravity_agreement'),99999999);
        }

      
        final function reroute_gravity_agreement() {

            if(!function_exists("esig_get_ip"))
            {
                return false;
            }
            //$wcCrated = get_transient("esig-gf-agreement-created" . esig_get_ip());
            $doubleAgreement = get_transient("esig-gf-wc-agreement" . esig_get_ip());
           
            if (!empty($doubleAgreement)) {
                $redirect = get_transient("esig-gf-redirect-" . esig_get_ip());
                delete_transient("esig-gf-wc-agreement" . esig_get_ip());
                delete_transient("esig-gf-redirect-" . esig_get_ip());
                delete_transient("esig-gf-agreement-created" . esig_get_ip());
                
                wp_redirect(html_entity_decode($redirect));
                exit;
            }
            return false;
        }

        final function esig_agreement_before_display($args) {
            
            if (!ESIG_GF_SETTINGS::is_gf_esign_required()) {
                return;
            }
            $all_done = true;
            $temp_data = ESIG_GF_SETTINGS::get_temp_settings();
            foreach ($temp_data as $invite => $data) {
                if($invite == "entry_id") { continue; } 
                if ($data['signed'] == "no") {
                    $all_done = false;
                }
            }
            if ($all_done) {
                ESIG_GF_SETTINGS::delete_temp_settings();
            }
        }

        final function after_sign_check_next_agreement($args) {

            $document_id = $args['document_id'];

            if (!ESIG_GF_SETTINGS::is_gravity_requested_agreement($document_id)) {
                return false;
            }
            if (!ESIG_GF_SETTINGS::is_gf_esign_required()) {
                return false;
            }

            $invite_hash = WP_E_Sig()->invite->getInviteHash_By_documentID($document_id);
            ESIG_GF_SETTINGS::save_esig_gf_meta($invite_hash, "signed", "yes");

            $temp_data = ESIG_GF_SETTINGS::get_temp_settings();

     
            foreach ($temp_data as $invite => $data) {
                if($invite == "entry_id") { continue; } 
                if ($data['signed'] == "no") {
                    $invite_url = ESIG_GF_SETTINGS::get_invite_url($invite);
                     wp_send_json_success(array(
                        "success" => true,
                        'redirect' => $invite_url,
                    ));
                    exit;
                }
            }
        }

        final function show_invite_error($ret, $docId) {

            $doc = WP_E_Sig()->document->getDocument($docId);
            if (!isset($doc->document_content)) {
                return $show;
            }
            $document_content = $doc->document_content;
            $document_raw = WP_E_Sig()->signature->decrypt(ENCRYPTION_KEY, $document_content);

            if (has_shortcode($document_raw, 'esiggravity')) {

                $ret = true;
                return $ret;
            }
            return $ret;
        }

        final function show_sad_invite_link($show, $doc, $page_id) {
            if (!isset($doc->document_content)) {
                return $show;
            }
            $document_content = $doc->document_content;
            $document_raw = WP_E_Sig()->signature->decrypt(ENCRYPTION_KEY, $document_content);

            if (has_shortcode($document_raw, 'esiggravity')) {

                $show = false;
                return $show;
            }
            return $show;
        }
        
        
        public function paypal_reroute_confirmation($confirmation, $form, $lead, $ajax) {

            $gf_paypal_return = rgget('gf_paypal_return', $_GET);
            
            if(!$gf_paypal_return){
                return $confirmation;
            }
            
            $temp_data = ESIG_GF_SETTINGS::get_temp_settings();
            
            if(!$temp_data){
                return $confirmation;
            }

            foreach ($temp_data as $invite => $data) {
                if ($data['signed'] == "no") {
                    $invite_url = ESIG_GF_SETTINGS::get_invite_url($invite);
                    wp_redirect($invite_url);
                    exit;
                }
            }
            
            return $confirmation;
        }

        /**
         * Override the GF confirmation with the e-signature redirect URL.
         *
         * Runs at priority 20 so it executes after payment add-ons such as
         * GF Stripe (which hook gform_confirmation at priority 20 or lower).
         * The redirect URL is resolved in two steps:
         *
         *   1. IP-keyed transient — set by process_feed() in the same request;
         *      works for every standard and inline-payment flow.
         *   2. Database fallback — looks up the document that process_feed()
         *      already wrote to the DB via esig_gravity_entry_id meta; used
         *      when the transient is unavailable (e.g. Stripe 3D Secure causes
         *      a cross-request flow where the user's IP may change between the
         *      feed run and the confirmation).
         *
         * @since 2.0.4
         *
         * @param array|string $confirmation GF confirmation value.
         * @param array        $form         GF form array.
         * @param array        $lead         GF entry array.
         * @param bool         $ajax         Whether the form was submitted via AJAX.
         *
         * @return array|string Modified confirmation with redirect, or original.
         */
        public function reroute_confirmation( $confirmation, $form, $lead, $ajax ) {

            if ( ! function_exists( 'WP_E_Sig' ) ) {
                return $confirmation;
            }

            $post_wc_form_id = rgget( 'wc_gforms_form_id', $_POST );
            $form_id         = rgget( 'form_id', $lead );
            $entry_id        = absint( rgget( 'id', $lead ) );

            // Skip — WooCommerce GForms handles its own redirect.
            if ( $post_wc_form_id == $form_id ) {
                return $confirmation;
            }

            if ( ! $entry_id ) {
                return $confirmation;
            }

            // Step 1: IP-keyed transient (same-request, standard flow).
            $transient_key  = 'esig-gf-redirect-' . $entry_id . esig_get_ip();
            $redirect_url   = get_transient( $transient_key );

            if ( $redirect_url ) {
                delete_transient( $transient_key );
                return array( 'redirect' => html_entity_decode( $redirect_url ) );
            }

            // Step 2: DB fallback (cross-request flow, e.g. Stripe 3D Secure).
            // process_feed() already saved esig_gravity_entry_id on the document;
            // look it up directly — no IP or TTL dependency.
            $doc_id = WP_E_Sig()->meta->metadata_by_keyvalue( 'esig_gravity_entry_id', $entry_id );

            if ( ! $doc_id ) {
                return $confirmation;
            }

            // Only redirect when the signing logic for this form is set to redirect.
            $feeds = GFAPI::get_feeds( 'esig-gf', $form_id );
            $is_redirect_feed = false;

            if ( is_array( $feeds ) ) {
                foreach ( $feeds as $feed ) {
                    if ( isset( $feed['meta']['esign_gf_logic'] ) && 'redirect' === $feed['meta']['esign_gf_logic'] && ! empty( $feed['is_active'] ) ) {
                        $is_redirect_feed = true;
                        break;
                    }
                }
            }

            if ( ! $is_redirect_feed ) {
                return $confirmation;
            }

            $invite_hash = WP_E_Sig()->invite->getInviteHash_By_documentID( $doc_id );

            if ( ! $invite_hash ) {
                return $confirmation;
            }

            $doc          = WP_E_Sig()->document->getDocument( $doc_id );
            $redirect_url = WP_E_Sig()->invite->get_invite_url( $invite_hash, $doc->document_checksum );

            if ( ! $redirect_url ) {
                return $confirmation;
            }

            return array( 'redirect' => html_entity_decode( $redirect_url ) );
        }
        

        final function esig_almost_done_gravity_settings() {

            if (!function_exists('WP_E_Sig'))
                return;

            // getting sad document id 
            $sad_document_id = ESIG_GET('id');
             $documentStatus = esigget('document_status');
             if ($documentStatus != "stand_alone") {
                return;
            }


            if (!$sad_document_id) {
                return;
            }
            // creating esignature api here 
            $api = WP_E_Sig();

            $documents = $api->document->getDocument($sad_document_id);

            //print_r($documents);

            $document_content = $documents->document_content;
            $document_raw = $api->signature->decrypt(ENCRYPTION_KEY, $document_content);

            if (has_shortcode($document_raw, 'esiggravity')) {
                //echo get_shortcode_regex();
                preg_match_all('/' . get_shortcode_regex() . '/s', $document_raw, $matches, PREG_SET_ORDER);

                $gravity_shortcode = '';
                $gravityFormid= ''; 
                foreach ($matches as $match) {
                    if (in_array('esiggravity', $match)) {
                        
                         $atts = shortcode_parse_atts($match[0]);

                        extract(shortcode_atts(array(
                            'formid' => '',
                            'field_id' => '', //foo is a default value
                                        ), $atts, 'esiggravity'));


                         $formid = esig_gf_clean_doublecodes($formid);                                        
                        
                        if(is_numeric($formid)){
                            $gravityFormid =$formid ; 
                            break;
                        }
                    }
                }

                $api->document->saveFormIntegration($sad_document_id, 'gravity');

               

                //admin.php?page=gf_edit_forms&view=settings&subview=esig-gf&id=2

                $data = array("form_id" => $gravityFormid);

                $display_notice = dirname(__FILE__) . '/views/alert-almost-done.php';
                $api->view->renderPartial('', $data, true, '', $display_notice);

                //include_once "views/alert-almost-done.php" ; 
            }
        }

        final function esig_gravity_addon_requirement() {
            if (class_exists('GFForms') && function_exists("WP_E_Sig") && class_exists('ESIG_SAD_Admin') && class_exists('ESIG_SIF_Admin'))
                return;


            include_once "views/alert-modal.php";
        }

        /**
         *  Showing fallback modal for rquirement to run this plugins. 
         * 
         */
        final function esig_gravity_addon_requirement_modal($msg) {
            if (class_exists('GFForms') && function_exists("WP_E_Sig") && class_exists('ESIG_SAD_Admin') && class_exists('ESIG_SIF_Admin'))
                return;

            ob_start();
            include_once "views/alert-modal.php";
            $msg .= ob_get_contents();
            ob_end_clean();

            return $msg;
        }

        public function render_shortcode_esiggravity($atts) {

           
            extract(shortcode_atts(array(
                'formid' => '',
                'field_id' => '', //foo is a default value
                'display' => 'value',
                'option' => 'default',
                            ), $atts, 'esiggravity'));                          

            if (!function_exists('WP_E_Sig'))
                return;

            // creating esignature api 
            $api = new WP_E_Api();

            $csum = esigget( 'csum' );

            if ( ! empty( $csum ) ) {
                $document_id = $api->document->document_id_by_csum( $csum );
            } else {
                // esigget() decodes ?wpesig= tokens, ?did= checksums, and ?document_id= params —
                // all request-scoped. get_option() is a shared DB value that can hold a stale ID
                // from a previous request, so we treat it as a last resort only.
                $document_id = esigget( 'document_id' );
                if ( empty( $document_id ) ) {
                    $document_id = get_option( 'esig_global_document_id' );
                }
            }
           
            // getting document meta for gravity form 
            $formid = esig_gf_clean_doublecodes($formid);
            $field_id = esig_gf_clean_doublecodes($field_id);

          
            if (empty($formid) || empty($field_id)) {
                return false;
            }


            $entry_id = self::getEntryId(); 
            $newFormId = ESIG_GF_SETTINGS::getFormId($document_id); 
            
            

            if (empty($entry_id)) {
                $oldVersion = true;
                $entry_id = ESIG_GF_SETTINGS::entryId($document_id);
                if (empty($entry_id)) return false;
            }else{
                $oldVersion = false;
            }

            $allowOtherFormData = apply_filters("esig_gravity_allow_otherform_data", false);
            if (!wp_validate_boolean($allowOtherFormData)) {
                if ($newFormId != $formid) return false;
            }
      
          
            $value = self::generate_value(filter_var($formid, FILTER_SANITIZE_NUMBER_INT), filter_var($field_id, FILTER_SANITIZE_NUMBER_INT), $entry_id, $document_id,$oldVersion, $display, $option);
          
           
            $fieldType  = self::get_field_type(filter_var($formid, FILTER_SANITIZE_NUMBER_INT), filter_var($field_id, FILTER_SANITIZE_NUMBER_INT)) ; 
            
          
            if ($fieldType == 'html') {
                return $value;
            }
           
            if ($fieldType == 'post_image') {
                return $value;
            }
           
            return self::display_value($value, $document_id,$fieldType);
        }

        public function esig_gravity_form_fields() {
            // Security: Verify nonce for AJAX request
            check_ajax_referer('esig-gravity-ajax-nonce', 'nonce');
            
            // Security: Verify user capabilities
            if (!is_user_logged_in() || !current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => __('Insufficient permissions', 'esig-gf')));
                die();
            }

            if (!function_exists('WP_E_Sig'))
                return;

            // Security: Sanitize form_id input
            $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
            $gravity_form = GFAPI::get_form($form_id);

            $html = '';

            $html .= '<select id="esig_gf_field_id" name="esig_gf_field_id" class="chosen-select" style="width:250px;">';
            $html .= '<option value="all">Insert all fields</option>';
            foreach ($gravity_form['fields'] as $field) {

                if ($field->type == 'captcha') {
                    continue;
                }
                if ($field->type == 'page') {
                    continue;
                }
                // Security: Escape option values and labels to prevent XSS
                $escaped_field_id = esc_attr($field->id);
                $escaped_field_label = esc_html($field->label);
                $html .= '<option value="' . $escaped_field_id . '">' . $escaped_field_label . '</option>';
            }
            
            // Security: Escape form_id in hidden input
            $escaped_form_id = esc_attr($form_id);
            $html .= '</select><input type="hidden" name="esig_gf_form_id" value="' . $escaped_form_id . '">';

            echo $html;

            die();
        }

        public function add_sif_gravity_buttons($sif_menu) {

            $sif_menu .= '<a class="dropdown-item bridge-plugin-integration" id="wpesign__gravity-sif-popup" href="#">Gravity Form Data</a>';

            // $plugins['esig_sif'] = plugin_dir_url(__FILE__) . 'assets/js/esig-gravity-sif-buttons.js';
            return $sif_menu;
        }

        public function add_sif_gf_text_menu($sif_menu) {

            $esig_type = ESIG_GET('esig_type');
            $document_id = ESIG_GET('document_id');

            if (empty($esig_type) && !empty($document_id)) {
                $document_type = WP_E_Sig()->document->getDocumenttype($document_id);
                if ($document_type == "stand_alone") {
                    $esig_type = "sad";
                }
            }

            if ($esig_type != 'sad') {
                return $sif_menu;
            }
            $sif_menu['Gravity'] = array('label' => "Gravity Form Data");
            return $sif_menu;
        }

        /**
         *  searching for email address in submitted data . 
         */
        private function check_gf_form_email($entry) {
            foreach ($entry as $key => $value) {
                if (filter_var($value, FILTER_VALIDATE_EMAIL)) {

                    return $value;
                    break;
                }
            }

            return false;
        }

        /**
         *  execute this function when gravity form submit 
         *  @Since 1.0
         */
        final function esig_gravity_form_submit($entry, $form) {
            $form_id = $form['id'];

            if (!function_exists('WP_E_Sig'))
                return;

            $api = WP_E_Sig();
            global $wpdb;
            // settings gravity form for signature
            $document_id = $api->setting->get_generic('esig_gravity_form_' . $form_id);

            $email_address = $this->check_gf_form_email($entry);

            // copying document from previous doc and creating new document for new entry 
            if ($email_address) {
                $old_doc = $api->document->getDocument($document_id);

                $new_document_id = $api->document->copy($document_id);

                // updating document title 
                $wpdb->query($wpdb->prepare(
                                "UPDATE " . $this->documents_table . " SET document_title = '%s' where document_id = %d", $old_doc->document_title . ' - ' . $email_address, $new_document_id));


                // get new document details 
                $doc = $api->document->getDocument($new_document_id);

                // Get Owner
                $super_admin_id = $api->user->esig_get_super_admin_id();
                $owner = $api->user->getUserByID($super_admin_id);

                // Create the user
                $recipient = array(
                    "user_email" => $email_address,
                    "first_name" => $entry[1]
                );

                $recipient['id'] = $api->user->insert($recipient);
                // Create the invitation?
                $invitation = array(
                    "recipient_id" => $recipient['id'],
                    "recipient_email" => $email_address,
                    "recipient_name" => $entry[1],
                    "document_id" => $new_document_id,
                    "document_title" => $doc->document_title,
                    "sender_name" => $owner->first_name . ' ' . $owner->last_name,
                    "sender_email" => $owner->user_email,
                    "document_checksum" => $doc->document_checksum,
                );

                // saving invitation and getting invite hash 
                $invite_controller = new WP_E_invitationsController();

                if ($invite_controller->saveThenSend($invitation, $doc)) {
                    
                }
            }
        }

        /**
         * Filter:
         * Adds options to the document-add and document-edit screens
         */
        public function document_add_data($more_contents) {
            $more_contents .= $this->document_view->add_document_view();
            return $more_contents;
        }

        /**
         * Filter: 
         * Show sad document in view document opton 
         * Since 1.0.0
         */
        public function show_gravity_actions($more_option_page, $args) {

            $more_option_page .= $this->document_view->add_document_view_modal();
            return $more_option_page;
        }

        /**
         * Register and enqueue admin-specific JavaScript.
         *
         * @since     1.0.0
         * @return    null    Return early if no settings page is registered.
         */
        public function enqueue_admin_scripts() {

            $screen = get_current_screen();
            $admin_screens = array(
                'admin_page_esign-add-document',
                'admin_page_esign-edit-document',
                'e-signature_page_esign-view-document',
                'e-signature_page_esign-setup',

            );

            // Add/Edit Document scripts
            if (in_array(esig_gf_get("id",$screen), $admin_screens)) {

                // wp_enqueue_style( $this->plugin_slug . '-admin-style', plugins_url( 'assets/css/esig_template.css', __FILE__ ));
                wp_enqueue_script('jquery');
                wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('assets/js/esig-add-gravity.js', __FILE__), array('jquery', 'jquery-ui-dialog'), ESIG_GRAVITY::VERSION, true);
                
                // Security: Localize script with nonce for AJAX requests
                wp_localize_script($this->plugin_slug . '-admin-script', 'esigGravityAjax', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('esig-gravity-ajax-nonce'),
                ));
            }

            $page = esig_gf_get("page");

            if(esig_gf_get("subview") == 'esig-gf'){
                wp_enqueue_script('esig-gravity-setting-script', plugins_url('assets/js/esig-gravity-setting.js', __FILE__), array('jquery', 'jquery-ui-dialog'), ESIG_GRAVITY::VERSION, true);
            }

            if ($page == "esign-docs") {
                // Enqueue jQuery UI CSS for dialog styling (use local version from core plugin if available)
                if (defined('ESIGN_ASSETS_DIR_URI')) {
                    wp_enqueue_style('jquery-ui-css', ESIGN_ASSETS_DIR_URI . '/public/css/vendor/jquery-ui.css', array(), false, 'all');
                } else {
                    // Fallback to CDN if core plugin assets not available
                    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css', array(), '1.12.1', 'all');
                }
                // Enqueue custom Gravity Forms styles for dialog fixes
                wp_enqueue_style('esig-gravity-dialog-styles', plugins_url('assets/css/esig-gravity-dialog-styles.css', __FILE__), array(), '1.0.0', 'all');
                wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('assets/js/esig-gravity-control.js', __FILE__), array('jquery', 'jquery-ui-dialog'), ESIG_GRAVITY::VERSION, true);
            }

            if (esig_gf_get("id",$screen) == "toplevel_page_gf_edit_forms") {
                wp_enqueue_style('esig-gravity-styles', plugins_url('assets/css/esig-gravity.css', __FILE__), array(), null, false);
            }

            if (esig_gf_get("id",$screen) == "admin_page_esign-gravity-about" || esig_gf_get("id",$screen) == "toplevel_page_esign-gravity-about") {

                wp_enqueue_script('esign-iframe-script', plugins_url('assets/js/esign-iframe.js', __FILE__), array('jquery', 'jquery-ui-dialog'), '0.0.1', true);          
                wp_register_style( 'esig_gf_enqueue_style', plugins_url('about/assets/css/esig-about.css', __FILE__), false, '1.0.0' );
                wp_enqueue_style( 'esig_gf_enqueue_style' );
                wp_enqueue_style( 'esig-google-fonts', 'https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@200;300;400;600;700;900&display=swap', false );
                wp_enqueue_style( 'esig-snip-styles', plugins_url('about/assets/css/snip-styles.css', __FILE__), false, '0.0.1' );
            
            }
        }

        /**
         * Return an instance of this class.
         * @since     0.1
         * @return    object    A single instance of this class.
         */
        public static function get_instance() {

            // If the single instance hasn't been set, set it now.
            if (null == self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        /**
         *  When new document is created. it saves new gravity documents 
         *  @since 1.0.0
         */
        final function gravity_after_save($args) {

            global $wpdb;
            $doc_id = $args['document']->document_id;

            if (!function_exists('WP_E_Sig'))
                return;

            $api = WP_E_Sig();

            // checking if not add gravity document return 
            if (ESIG_POST('add_gravity') == 'Add Gravity') {
                $document_status = 'esig_gravity';
            } else if (ESIG_POST('save_gravity') == 'Save as Draft') {
                $document_status = 'draft';
            } else {
                return;
            }

            // changing document status 
            $wpdb->update($this->documents_table, array('document_type' => 'esig_gravity', 'document_status' => $document_status), array('document_id' => $doc_id), array('%s', '%s'), array('%d')
            );

            // settings gravity form siging documents 
            $gf_form_id = ESIG_POST('esig_gravity_form_id');
            // settings gravity form for signature
            $api->setting->set('esig_gravity_form_' . $gf_form_id, $doc_id);
        }

        /**
         * Filter:
         * Adds filter link to top of document index page
         */
        public function document_index_data($template_data) {

            global $wpdb;

            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) " .
                            "FROM {$this->documents_table} " .
                            "WHERE document_type = '%s' AND document_status != '%s' " .
                            " AND document_status !='%s'", 'esig_gravity', 'trash', 'archive'));

            $url = "admin.php?page=esign-docs&amp;document_status=esig_gravity";
            $css_class = '';

            if (ESIG_POST('document_status') == 'esig_gravity') {
                $css_class = 'class="current"';
            }

            if (array_key_exists('document_filters', $template_data)) {
                $template_data['document_filters'] .= "| <a $css_class href=\"$url\">Gravity Forms</a> ($count)  ";
            }

            return $template_data;
        }

    }

    

endif;

