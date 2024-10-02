
(function($){


    $("form").submit(function(e){
       
        

       
        var firstInverval = parseInt($("input[name=_gform_setting_esig_send_reminder]").val());
        var secondInverval = parseInt($("input[name=_gform_setting_esig_send_reminder_repeat]").val());
        var lastInverval = parseInt( $("input[name=_gform_setting_esig_send_reminder_expire]").val());
        error = false;
       
        if($('#esig_reminder_email').is(':checked')){

            if (firstInverval.length === 0 ) {
                error = true;

                if (document.getElementById('first-reminder-error') === null) {  
                    $( "#gform_setting_esig_send_reminder" ).after( "<p id='first-reminder-error' style='color:red'> First reminder is required</p>" );
                }
                
            }else{
                $( "#first-reminder-error" ).remove();
            }
            
            if(secondInverval.length === 0){
                error = true;
                if (document.getElementById('second-reminder-error') === null) {  
                    $( "#gform_setting_esig_send_reminder_repeat" ).after( "<p id='second-reminder-error' style='color:red'>Second reminder is required</p>" );
                }

            }else{
                $( "#second-reminder-error" ).remove();

            }
            
            if(lastInverval.length === 0){
                error = true;
                if (document.getElementById('last-reminder-error') === null) {
                    $( "#gform_setting_esig_send_reminder_expire" ).after( "<p id='last-reminder-error' style='color:red' >Last reminder is required</p>" );
                    }
            }else{

                $( "#last-reminder-error" ).remove();

            }
            
            if (parseInt(secondInverval) <= parseInt(firstInverval)) {
                error = true;
                if (document.getElementById('second-reminder-error') === null) {  
                    $( "#gform_setting_esig_send_reminder_repeat" ).after( "<p id='second-reminder-error' style='color:red'>Second reminder should be greater than first reminder</p>" );
                }
            }else{
                $( "#second-reminder-error" ).remove();
            }

            if (parseInt(lastInverval) <= parseInt(secondInverval)) {
                error = true;
                if (document.getElementById('last-reminder-error') === null) {
                $( "#gform_setting_esig_send_reminder_expire" ).after( "<p id='last-reminder-error' style='color:red' >Last reminder should be greater than second reminder</p>" );
                }
            }else{
                $( "#last-reminder-error" ).remove();
            }

            if (error) {
                e.preventDefault();
                exit;
            }else{
                return true;
            }
            
        }

        
        
    
    });

})(jQuery);