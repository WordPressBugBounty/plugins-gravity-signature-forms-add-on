/**
 * 
 */


(function($){

    /**
     * Insert content into the document editor, handling both Visual and Code/Text modes.
     *
     * When the editor is in Code/Text mode tinymce.get() returns null, which
     * causes a fatal JS error. This helper falls back to direct textarea insertion
     * so shortcodes are always placed at the cursor position regardless of mode.
     *
     * @since  2.0.2
     *
     * @param  {string} content  Shortcode or HTML string to insert.
     * @return {void}
     */
    function esigInsertContent( content ) {
        var editor = ( typeof tinymce !== 'undefined' ) ? tinymce.get( 'document_content' ) : null;

        if ( editor && ! editor.isHidden() ) {
            // Visual (WYSIWYG) mode — use the TinyMCE API.
            editor.insertContent( content );
            return;
        }

        // Code/Text mode — write directly into the visible textarea.
        var textarea = document.getElementById( 'document_content' );
        if ( ! textarea ) {
            return;
        }

        var start = textarea.selectionStart || 0;
        var end   = textarea.selectionEnd   || start;
        var text  = textarea.value          || '';

        textarea.value = text.substring( 0, start ) + content + text.substring( end );
        textarea.selectionStart = textarea.selectionEnd = start + content.length;
        textarea.focus();

        // Notify WordPress auto-save and other listeners that the content changed.
        textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );
    }

        // next step click from sif pop
        $("#esig-gravity-create").click(function() {
 
                var form_id = $('select[name="esig_gravity_form_id"]').val();
                
                // Hide first step, show second step
                $("#esig-gravity-form-first-step").hide();
                $("#esig-gf-second-step").show();
                
                // Show loading only on first load
                var isFirstLoad = $("#esig-gf-field-option").is(':empty') || $("#esig-gf-field-option").html().trim() === '';
                
                if (isFirstLoad) {
                        $("#esig-gravity-loading-container").show();
                }
                
                // AJAX to get form fields
                // Security: Include nonce in AJAX request
                // Try esigGravityAjax first, then fallback to esigAjax
                var nonce = (typeof esigGravityAjax !== 'undefined' && esigGravityAjax.nonce) 
                    ? esigGravityAjax.nonce 
                    : (typeof esigAjax !== 'undefined' && esigAjax._wpnonce) 
                        ? esigAjax._wpnonce 
                        : '';
                var ajaxUrl = (typeof esigGravityAjax !== 'undefined' && esigGravityAjax.ajaxurl) 
                    ? esigGravityAjax.ajaxurl 
                    : (typeof esigAjax !== 'undefined' && esigAjax.ajaxurl) 
                        ? esigAjax.ajaxurl 
                        : ajaxurl;
                jQuery.post(ajaxUrl, { 
                        action: "esig_gravity_form_fields", 
                        form_id: form_id,
                        nonce: nonce
                }, function(data) {
                        
                        // Hide and remove loading message
                        $("#esig-gravity-loading-container").fadeOut(200, function() {
                                $(this).remove();
                        });
                        
                        // Insert field options
                        $("#esig-gf-field-option").html(data);
                        
                        // Show all elements with proper targeting and spacing
                        setTimeout(function() {
                                // Get DOM elements directly - use step2 button ID!
                                var fieldOption = document.getElementById('esig-gf-field-option');
                                var displayType = document.getElementById('select-gravity-field-display-type');
                                var buttonWrap = document.getElementById('upload_gravity_button_step2');
                                
                                // Force inline styles with !important via setAttribute - add proper spacing
                                if (fieldOption) {
                                        fieldOption.setAttribute('style', 'display: block !important; visibility: visible !important; opacity: 1 !important; margin: 15px 0 !important;');
                                }
                                if (displayType) {
                                        displayType.setAttribute('style', 'display: block !important; visibility: visible !important; opacity: 1 !important; margin: 15px 0 !important;');
                                }
                                if (buttonWrap) {
                                        buttonWrap.setAttribute('style', 'display: block !important; visibility: visible !important; opacity: 1 !important; margin: 20px 0 !important;');
                                        
                                        // Also force the button inside visible
                                        var button = buttonWrap.querySelector('#esig-gravity-insert');
                                        if (button) {
                                                button.setAttribute('style', 'display: inline-block !important; visibility: visible !important; opacity: 1 !important;');
                                        }
                                }
                                
                        }, 100);
                        
                        // Re-initialize chosen for the dropdowns
                        setTimeout(function() {
                                if (jQuery.fn.chosen) {
                                        try {
                                                $("#esig-gf-field-option .chosen-select").chosen('destroy');
                                                $("#select-gravity-field-display-type .chosen-select").chosen('destroy');
                                        } catch(e) {}
                                        
                                        $("#esig-gf-field-option .chosen-select").chosen();
                                        $("#select-gravity-field-display-type .chosen-select").chosen();
                                }
                        }, 150);
                        
                }, "html").fail(function(xhr, status, error) {
                        $("#esig-gravity-loading-container").html('<span style="color: red;">Error loading fields. Please try again.</span>');
                });
  
        });
 
        // gravity add to document button clicked 
        $(document).on("click", "#esig-gravity-insert", function(e) {
                e.preventDefault();
 
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
                                esigInsertContent( return_text );
                        });
                }
                else {
                        var return_text = ' [esiggravity formid="' + form_id + '" field_id="' + field_id + '" display="' + displayType + '" ] ';
                        esigInsertContent( return_text );
                }
                   // 
                
             tb_remove();
           
            
                   
        });
        
        
        $('#select-gravity-form-list').click(function(){
            
            
          
            $(".chosen-drop").show(0, function () { 
				$(this).parents("div").css("overflow", "visible");
				});
            
            
            
        });
        

         // display  gravity form option popup
        $("#wpesign__gravity-sif-popup").on("click", function(e) {

                e.preventDefault();
               
                tb_show( "+ Gravity form option", "#TB_inline?inlineId=esig-gravity-option", false );
                

        });
        

	
})(jQuery);

