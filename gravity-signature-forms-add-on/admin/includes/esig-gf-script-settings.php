<script>
    let esig_reminder_email = document.querySelector("#esig_reminder_email");

    esig_reminder_email.addEventListener("click", function(e) {

        // If reminder emails are checked 
        if (esig_reminder_email.checked) {
            document.getElementById("gform_setting_esig_send_reminder").style.visibility = "visible";
            document.getElementById("gform_setting_esig_send_reminder_repeat").style.visibility = "visible";
            document.getElementById("gform_setting_esig_send_reminder_expire").style.visibility = "visible";
        } else {
            document.getElementById("gform_setting_esig_send_reminder").style.visibility = "hidden";
            document.getElementById("gform_setting_esig_send_reminder_repeat").style.visibility = "hidden";
            document.getElementById("gform_setting_esig_send_reminder_expire").style.visibility = "hidden";
        }

    });

    // onload if reminder checkbox is unchecked hide all reminder interval 
    if (!esig_reminder_email.checked) {
        document.getElementById("gform_setting_esig_send_reminder").style.visibility = "hidden";
        document.getElementById("gform_setting_esig_send_reminder_repeat").style.visibility = "hidden";
        document.getElementById("gform_setting_esig_send_reminder_expire").style.visibility = "hidden";
    }
</script>