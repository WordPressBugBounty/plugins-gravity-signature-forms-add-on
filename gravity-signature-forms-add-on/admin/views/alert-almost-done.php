

<div id="esig-gravity-almost-done" style="display: none;"> 

        	<div class="esig-dialog-header">
        	<div class="esig-alert">
            	<span class="esig-icon-esig-alert"></span>
            </div>
		   <h3><?php _e('Almost there... you\'re 50% complete','esig'); ?></h3>
		   
		   <p class="esig-updater-text"><?php 
		   
		   $esig_user= new WP_E_User();
		    
		    $wpid = get_current_user_id();
		    
		    $users = $esig_user->getUserByWPID($wpid); 
		    echo $users->first_name . ","; 
		   
		   ?>
		   
		   
		  <?php _e('Congrats on setting up your document! You\'ve got part 1 of 2 complete! Now you need to
          head over to the "Form Settings" tab for the Gravity Form you are trying to connect it to.' ,'esig'); ?> </p>
		</div>
        
         <div > <img src="<?php echo esc_url(plugins_url("gravity-form.png",__FILE__)) ; ?>" style="border: 1px solid #efefef; width: 500px; height:250px" /> </div>
        
        <div class="esig-updater-button">

		  <span> <a href="#" class="button esig-secondary-btn"  id="esig-gravity-setting-later"> <?php _e('I\'LL DO THIS LATER','esig');?> </a></span>
           <span> <a href="admin.php?page=gf_edit_forms&view=settings&subview=esig-gf&id=<?php echo esc_attr($data['form_id']); ?>" class="button esig-dgr-btn" id="esig-gravity-lets-go"> <?php _e('LET\'S GO NOW!','esig');?> </a></span>

		</div>

 </div>