/**
 * 
 */


(function($){

        // next step click from sif pop
        $( "#esig-gravity-create" ).click(function() {
 
                   var form_id= $('select[name="esig_gravity_form_id"]').val();
                   
                   
                   $("#esig-gravity-form-first-step").hide();
                  
                   // jquery ajax to get form field . 
                   jQuery.post(esigAjax.ajaxurl,{ action:"esig_gravity_form_fields",form_id:form_id},function( data ){ 
				      $("#esig-gf-field-option").html(data);
				},"html");
                   
                   $("#esig-gf-second-step").show();                        
  
        });
 
        // gravity add to document button clicked 
        $( "#esig-gravity-insert" ).click(function() {
 
                   var form_id= $('input[name="esig_gf_form_id"]').val() ;
                   
                   var field_id =$('select[name="esig_gf_field_id"]').val();
                    var displayType =$('select[name="esig_gravity_value_display_type"]').val();

                if (field_id == "all") {
                        //$("#esig_formidableform_field_id").each(function () {
                        $('select#esig_gf_field_id').find('option').each(function () {

                                // Add $(this).val() to your list
                                let allField = $(this).val();
                                if (allField == "all") return true;
                                var return_text = '<p> [esiggravity formid="' + form_id + '" field_id="' + allField + '" display="' + displayType + '" ] </p>';
                                esig_sif_admin_controls.insertContent(return_text);
                        });
                }
                else {
                        var return_text = ' [esiggravity formid="' + form_id + '" field_id="' + field_id + '" display="' + displayType + '" ] ';
                        esig_sif_admin_controls.insertContent(return_text);
                }
                   // 
                
             tb_remove();
           
            
                   
        });
        
        
        $('#select-gravity-form-list').click(function(){
            
            
          
            $(".chosen-drop").show(0, function () { 
				$(this).parents("div").css("overflow", "visible");
				});
            
            
            
        });
        
        

	
})(jQuery);

