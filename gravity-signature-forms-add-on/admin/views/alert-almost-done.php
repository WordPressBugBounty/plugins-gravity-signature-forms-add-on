

<div id="esig-gravity-almost-done" style="display: none;"> 

        	<div class="esig-dialog-header">
        	  <h3><?php _e('Almost there... you\'re 50% complete','esig'); ?></h3>
		   
		  
		  
		   <h2><?php _e('Lets head over to your form settings to complete setup','esig'); ?></h2>
		</div>
        
         <div > <img src="<?php echo esc_url(plugins_url("gravity-form.png",__FILE__)) ; ?>" style="border: 1px solid #efefef; width: 550px; height:250px" /> </div>
        
        <div class="esig-updater-button">

		  <span> <a href="#" class="button esig-secondary-btn"  id="esig-gravity-setting-later"> <?php _e('I\'LL DO THIS LATER','esig');?> </a></span>
           <span> <a href="admin.php?page=gf_edit_forms&view=settings&subview=esig-gf&id=<?php echo esc_attr($data['form_id']); ?>" class="form__btn btn btn--secondary btn--fit" id="esig-gravity-lets-go"> <?php _e('LET\'S GO NOW!','esig');?> </a></span>

		</div>

 </div>