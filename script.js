jQuery(document).ready(function($) { 

	// !Silverpop API check
	$( '#btn-test-crontab' ).click( function(e){
		e.preventDefault();

		var spinner = $( '.spinner', this );

		var enable_job = $( '#settings-rpnrdisable-enable_cron_job' ).val(),
			run_job = $( '#settings-rpnrdisable-run_cron_job' ).val();


		// Remove existing response
		$( '.test-crontab-response' ).fadeOut( function(){ $( this ).remove() });

		spinner.show();

		$.get( ajaxurl,
			{
				action: 'test_crontab',
				enable_job: enable_job,
				run_job: run_job
			}
		).done( function( response ) {
			spinner.hide();
			$( '.tool-box' ).after(response);
		});
	});
});