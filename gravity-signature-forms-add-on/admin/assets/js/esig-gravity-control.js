(function($){

    
	// displaying fall back msg . 
       $( "#esig-gravity-addon-fallback-modal" ).dialog({
		  dialogClass: 'esig-dialog',
		  height:300,
		  width:300,
		  modal: true,
		  buttons:[ 
			{
			 text:"OK ",
			 "id":"esig-primary-dgr-btn",
			 click: function() {
			  $( this ).dialog( "close" );
			  return false ;
				}
			}]
		  
		});
	 
	// Only initialize dialog if element exists
	if ( $( "#esig-gravity-almost-done" ).length ) {
		
		// Initialize the almost done modal dialog
		$( "#esig-gravity-almost-done" ).dialog({
			dialogClass: 'esig-dialog',
			height: 350,
			width: 350,
			modal: true,
			closeOnEscape: true,
			draggable: false,
			resizable: false,
			position: { my: "center", at: "center", of: window },
			// Properly clean up when dialog closes
			close: function(event, ui) {
				// Remove the dialog and overlay completely
				$(this).dialog('destroy');
				// Ensure overlay is removed
				$('.ui-widget-overlay').remove();
			}
		});
		
		// Do later button click - close dialog
		$( "#esig-gravity-setting-later" ).click(function(e) {
			e.preventDefault();
			$( '#esig-gravity-almost-done' ).dialog( "close" );
		});
		
		// Let's go button - close dialog before navigation
		$( "#esig-gravity-lets-go" ).click(function(e) {
			// Close the dialog before navigating
			$( '#esig-gravity-almost-done' ).dialog( "close" );
			// Let the link navigate naturally
		});
	}
		
})(jQuery);
