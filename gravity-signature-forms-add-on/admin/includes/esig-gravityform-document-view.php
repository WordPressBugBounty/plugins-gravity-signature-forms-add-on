<?php
/**
 *
 * @package ESIG_GRAVITYFORM_DOCUMENT_VIEW
 * @author  Abu Shoaib <abushoaib73@gmail.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (! class_exists('esig_gravityform_document_view')) :
class esig_gravityform_document_view {
    
    
            /**
        	 * Initialize the plugin by loading admin scripts & styles and adding a
        	 * settings page and menu.
        	 * @since     0.1
        	 */
        	final function __construct() {
        
        	}
        	
        	/**
        	 *  This is add document view which is used to load content in 
        	 *  esig view document page
        	 *  @since 1.1.0
        	 */
        	
        	final function add_document_view()
        	{
        	    
        	    if(!function_exists('WP_E_Sig'))
                                return ;
                    
                    
                    if(!class_exists("RGFormsModel"))
                              return;
        	    
        	    $api = WP_E_Sig();
        	    $assets_dir = ESIGN_ASSETS_DIR_URI;
        	    
        	     $more_option_page = ''; 
        	   
        	    
        	    $more_option_page .= '<div id="esig-gravity-option" class="esign-form-panel" style="display:none;">
        	        
        	        
                	               <div align="center"><img src="' . esc_url($assets_dir) .'/images/logo.png" width="200px" height="45px" alt="Sign Documents using WP E-Signature" width="100%" style="text-align:center;"></div>
                    			
                                    
                    				<div id="esig-gravity-form-first-step">
                        				
                                        	<div align="center" class="esig-popup-header esign-form-header">'.__('What Are You Trying To Do?', 'esig').'</div>
                                            	
                        				<p id="create_gravity" align="center">';
                                	    
                                	    $more_option_page .=	'
                        			
                        				<p id="select-gravity-form-list" align="center">
                                	    
                        		        <select data-placeholder="Choose a Option..." class="chosen-select" tabindex="2" id="esig-gravity-form-id" name="esig_gravity_form_id">
                        			     <option value="sddelect">'.__('Select a Gravity Form', 'esig').'</option>';
                                	    
                                	     $gravity_forms = RGFormsModel::get_forms(); //GFAPI::get_forms();
                                	   
                                	    foreach($gravity_forms as $forms)
                                	    {
                                	    
                                	        $more_option_page .=	'<option value="'. $forms->id . '">'. $forms->title .'</option>';
                                	    }
                                	    
                                	    $more_option_page .='</select>
                                	    
                        				</p>
                         	  
                                	    </p>
                                	    
                                        <p id="upload_gravity_button" align="center">
                                           <a href="#" id="esig-gravity-create" class="button-primary esig-button-large">'.__('Next Step', 'esig').'</a>
                                         </p>
                                     
                                    </div>  <!-- Frist step end here  --> ';
                            
                                    
                 $more_option_page .='<!-- Gravity form second step start here -->
                                            <div id="esig-gf-second-step" style="display:none;">
                                            
                                        	<div align="center" class="esig-popup-header esign-form-header">'.__('What gravity form field data would you like to insert?', 'esig').'</div>
                                            
                                            <p id="esig-gf-field-option" align="center"> </p>
                                            
                                            <p id="select-gravity-field-display-type" align="center">
                                	    
                        		        <select data-placeholder="Choose a Option..." class="chosen-select" tabindex="2" id="esig-gravity-form-id" name="esig_gravity_value_display_type">
                        			     <option value="value">'.__('Select a display type', 'esig').'</option>
                                          
                                         
                                           <option value="value">'.__('Display value', 'esig').'</option>
                                           <option value="label">'.__('Display label', 'esig').'</option>
                                           <option value="label_value">'.__('Display label + value', 'esig').'</option>';
                                	   
                                           
                                	    $more_option_page .='</select>
                                	    
                        				</p>
                                            
                                             <p id="upload_gravity_button" align="center">
                                           <a href="#" id="esig-gravity-insert" class="button-primary esig-button-large">'.__('Add to Document', 'esig').'</a>
                                         </p>
                                            
                                            </div>
                                    <!-- Gravity form second step end here -->';           
                                    
                                    
        	    
        	    $more_option_page .= '</div><!--- gravity option end here -->' ;
        	    
        	    
        	    return $more_option_page ; 
        	}
        	
        	
        	final function add_document_view_modal()
        	{
        	    if(! function_exists('WP_E_Sig'))
        	        return ;
                    
                    
        	     $api = WP_E_Sig();
        	    $assets_dir = ESIGN_ASSETS_DIR_URI;
        	    
        	    $more_option_page = '' ; 
        	    
        	    // first modal button start here 
        	    $more_option_page .= '<div id="esig-settings-col3">
       
                            				<div class="esign-signing-options">
                       
                                                <a href="#" data-toggle="modal" data-target=".esig-gravity-modal-md" id="gravity_view_show">
                                    				<div id="esig-add-gravity" class="esig-doc-options esig-add-document-hover">
                                    					<div class="icon"></div>
                                    					<div class="text">'.__('+ Gravity Form','esig').'</div>
                                    				</div>
                                                </a>
                       
                                                <!-- sad document benefits start -->
                       
                                                <div class="benefits">
                                					<p>' .  __('Gravity Form Benefits','esig') .'</p>
                                					<div class="plus-li">'. __('1 signer','esig').'</div>
                                					<div class="plus-li">'. __('Same document for everyone','esig').'</div>
                                					<div class="plus-li">'. __('Stored on a Wordpress page','esig').'</div>
                                					<div class="plus-li">'. __('Great for automating contracts','esig').'</div>
                                				</div>
                     
                            			   </div>
        	    
	                                 </div>';
        	    // first modal start here 
        	    
        	 $more_option_page .= '   <div class="modal fade esig-gravity-modal-md" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
        	    <div class="modal-dialog modal-md">
        	    <div class="modal-content">
        	        
                         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                   
        	     <!--  modal content start  here -->
        	       <p>&nbsp;</p> 
        	     <div align="center"><img src="' . $assets_dir .'/images/logo.png" width="200px" height="45px" alt="Sign Documents using WP E-Signature" width="100%" style="text-align:center;"></div>
                 <h2 class="esign-form-header">'.__('What Are You Trying To Do?', 'esig').'</h2>
                            	    
        	     <p id="create_gravity" align="center">';
                            	    
                            	    $more_option_page .=	'
                    				<form id="esig_create_template" name="esig-view-document" action="" method="post">
                    				<p id="select-gravity-form-list" align="center">
                            	    
                    		        <select data-placeholder="Choose a Option..." class="chosen-select" width="100%" tabindex="2" id="esig-gravity-form-field" name="esig_gravity_form_id">
                    			<option value="sddelect">'.__('Select Gravity Form Field', 'esig').'</option>';
                            	    
                            	    $gravity_forms = GFAPI::get_forms();
                            	    
                            	    foreach($gravity_forms as $forms)
                            	    {
                            	    
                            	        $more_option_page .=	'<option value="'. $forms['id'] . '">'. $forms['title'] .'</option>';
                            	    }
                            	    
                            	    $more_option_page .='</select>
                            	    
                    				</p>
                            	    </form>
                   </p>
        	       <p>&nbsp;</p>
                           	        
                   <p id="upload_gravity_button" align="center">
                            <a href="#" id="esig-gravity-create" class="button-primary esig-button-large">'.__('Next Step', 'esig').'</a>
                    </p>
                    <p>&nbsp;</p>             
        	     <!-- modal content end here -->
        	    </div>
        	    </div>
        	    </div>';
        	    
        	    
        	    
        	    return $more_option_page ;
        	    
        	}
	   
    }
endif ; 
