<?php

/**
 *
 * @package esignRatingWidget
 * @author  Arafat Rahman <arafatrahmank@gmail.com>
 */
if (!class_exists('esignRatingWidgetGravity')) :

    class esignRatingWidgetGravity{

        /**
         * Instance of this class.
         * @since    1.0.1
         * @var      object
         */
        protected static $instance = null;
        public $name;
        private $feedbackURL,$rattingURL;

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
        public function __construct() {
            /*
             * Call $plugin_slug from public plugin class.
             */

            $this->feedbackURL = 'https://www.approveme.com/plugin-feedback/';
            $this->rattingURL = 'https://wordpress.org/support/plugin/gravity-signature-forms-add-on/reviews/#new-post';
            
            add_action('esig_admin_notices', array($this, 'esignRatingWidget'));
            add_action('admin_enqueue_scripts', array($this, 'enqueueAdminStyles'));
            add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
            add_action('wp_ajax_esig_gravity_ratting_widget_remove', array($this, 'esigGravityRattingWidgetRemove'));
        
        }

        public function esigGravityRattingWidgetRemove() {
            
            // Verify nonce for security (using same nonce as other Gravity Forms admin actions)
            if (!check_ajax_referer('esig_gravity_form_fields', 'esig_gf_nonce', false)) {
                wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'esig-gf')));
                return;
            }

            // Check user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'esig-gf')));
                return;
            }

            // Check E-Signature plugin is available
            if (!function_exists('WP_E_Sig')) {
                wp_send_json_error(array('message' => __('E-Signature plugin is not available.', 'esig-gf')));
                return;
            }

            // Check current user is e-signature sender
            if (!WP_E_Sig()->user->checkEsigAdmin(get_current_user_id())) {
                wp_send_json_error(array('message' => __('You are not authorized to perform this action.', 'esig-gf')));
                return;
            }

            // Update option with proper sanitization
            $result = update_option('remove_rating_widget_gravity', 'Yes');
            
            if ($result) {
                wp_send_json_success(array('message' => __('Rating widget hidden successfully.', 'esig-gf')));
            } else {
                wp_send_json_error(array('message' => __('Failed to update settings.', 'esig-gf')));
            }
        }
        
         public function enqueueAdminStyles() {
            $screen = get_current_screen();
            $current = $screen->id;
            
            if (($current == 'toplevel_page_esign-docs')) {
                wp_enqueue_style('esig-gravity-rating-widget-admin-styles', plugins_url('assets/css/esign-rating-widget.css', __FILE__), array(), '0.1.1');
            }
        }


        public function enqueueAdminScripts() {



            $screen = get_current_screen();
            $current = $screen->id;          

            
            if (($current == 'toplevel_page_esign-docs')) {
              
                 wp_enqueue_script('gravity-rating-widget-admin-script', plugins_url('assets/js/rating-widget-control.js', __FILE__), array('jquery', 'jquery-ui-dialog'), esigGetVersion(), true);
                 
                 // Localize script with nonce for AJAX security (using same nonce as other Gravity Forms admin actions)
                 wp_localize_script('gravity-rating-widget-admin-script', 'esigGravityAjax', array(
                     'ajaxurl' => admin_url('admin-ajax.php'),
                     'esig_gf_nonce' => wp_create_nonce('esig_gravity_form_fields')
                 ));
            }

        }
        
        
        
        public static function checkSignedDoc($metakey){
            
            $alldocid = WP_E_Sig()->meta->getall_bykey($metakey);
            $arrayValue = json_decode(json_encode($alldocid),true);

            $signature = 0;
            foreach ($arrayValue as $value) {
                

                $getStatus = WP_E_Sig()->document->getStatus($value['document_id']);
                
                if($getStatus == 'signed'){
                    $signature++;
                }
            }

            if($signature >= 5) return true;
            
            return false;
           
           
            
        }


        public function esignRatingWidget(){
            
             if (!function_exists('WP_E_Sig')) return false;            
              $screen = get_current_screen();
                           
              if( $screen->id != 'toplevel_page_esign-docs') return false;
              $checkWidget = get_option('remove_rating_widget_gravity');             

              if($checkWidget == "Yes") return false;
              $checkRequierment = self::checkSignedDoc('esig_gravity_entry_id');
              if(!wp_validate_boolean($checkRequierment)) return false;
              $api = new WP_E_Api();
       
              $data = array("form_name" => 'Gravity Form',"feedback_url"=>$this->feedbackURL,"plugin_url"=>$this->rattingURL);
              $displayNotice = dirname(__FILE__) . '/views/esig-ratting-widget-view.php';
              $api->view->renderPartial('', $data, true, '', $displayNotice);
          
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
        

        

    }

    

    
endif;

