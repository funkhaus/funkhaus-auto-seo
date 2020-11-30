$j = jQuery.noConflict();

/* eslint-disable */
var fhSeo = {
    
    // This pp_vars object is injected from WordPress in /emailer/init.php
    apiUrl: fh_seo_vars.api_url,
    nonce: fh_seo_vars.nonce,
    total: 0,
    completed: 0,    
    running: false,
    
    // Start any needed scripts
	init: function(){

        $j('#page-fh-seo-options #start-regenerate').click(function(){
            // Abort if running
            if(fhSeo.running) {
               return; 
            }

            // Save total
            fhSeo.total = parseInt( $j(this).data('total') );
            
            // Ask for confirmation
            var confirmed = confirm("Are you sure you want to rename and generate metadata for " + fhSeo.total + " attachments?");
            if(!confirmed) {
                return;
            }
            
            // Start send loop!
            fhSeo.started();
            fhSeo.runBatch();
        });
        
	},
	
	// Submit a batch to the custom WP API
	runBatch: async function() {
        fhSeo.batchStarted();
    	
    	try {
            var response = await fetch(fhSeo.apiUrl + '/generate', {
                method: 'POST',
                mode: 'cors', // no-cors, *cors, same-origin
                cache: 'no-cache',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': fhSeo.nonce,
                }
            });
            
            // Process response
            var data = await response.json();
            var items = data.data || [];
            
            // Save new nonce for next batch request
            var newNonce = ""
            if( data.meta && data.meta.nonce ) {
                newNonce = data.meta.nonce;
            }

            // Update progress            
            var hasMore = fhSeo.updateProgress(items);
            
            // Update nonce and batch again!
            if(hasMore && newNonce) {
                fhSeo.nonce = newNonce;
                fhSeo.runBatch();
            } else {
                fhSeo.finished();
            }
            
    	} catch(e) {
            alert("There was an error when trying to regenerate.", e);
            $j('#page-fh-seo-options .fh-seo-log').prepend('<span class="log-item status-failed">' + e + '</span>');
            console.log(e);
            fhSeo.finished();
    	}
	},
	
	// Update the progress bar on the custom WordPress admin page
	updateProgress: function(items = []){
        // Track total completed
    	fhSeo.completed = fhSeo.completed + items.length;
        var percentComplete = (fhSeo.completed / fhSeo.total ) * 100;
            percentComplete = Math.floor(percentComplete);
            
        // If no items came back for some reason, jump to 100% complete
        if( items.length === 0 ) {
            percentComplete = 100;
        }
        if(percentComplete > 100) {
            percentComplete = 100;
        }
        
        // Update progress bar
        $j('#page-fh-seo-options .fh-seo-progress').css({
            width: percentComplete + '%'
        });
        $j('#page-fh-seo-options .fh-seo-percentage').text(percentComplete + '%');

        // Update log and stats	
        $j.each(items, function( index, value ) {
            fhSeo.updateLog(value);
        });
        
        // Return if more emails to send
        return percentComplete < 100
	},
	
	// Update the log on custom WordPress admin page	
	updateLog: function(item = {}){
        // Make sure log is visible
        $j('#page-fh-seo-options').addClass('log-enabled');
        
    	var $success = $j('#page-fh-seo-options .total-success');
        var $fail = $j('#page-fh-seo-options .total-fail');
        
        var status = "failed";
        item.success = false;

        // Item contains 3 elements for each type of process thta happened to it. Check if all passed.
        item.success = item.set_color && item.set_metadata && item.set_focal_point;
        
        // Set status
        if(item.success) {
            status = "succeeded";    
            console.log(item);
        } else {
            console.warn(item);
        }
        
        // Update log
    	$j('#page-fh-seo-options .fh-seo-log').prepend('<span class="log-item status-'+ status +'">ID: '+ item.id +' - '+ status +'</span>\n');
    	
    	// Update stats
    	if( item.success ) {
        	$success.text( parseInt($success.text()) + 1 );
    	} else {
        	$fail.text( parseInt($fail.text()) + 1 );
    	}
	},

	// Function that runs once, before first batch starts
	started: function(){
        $j('#page-fh-seo-options .fh-seo-log').prepend('<span class="log-item">Started: '+ new Date().toString() +'</span>');    	
	},
	
	// Function that runs at the start of each batch
	batchStarted: function() {
        $j('#page-fh-seo-options').addClass('is-running');
        $j('#page-fh-seo-options .input, #page-fh-seo-options .button').prop( "disabled", true );
        fhSeo.running = true;    	
	},
	
	// Function that runs after all batches are finished 
	finished: function() {
    	$j('#page-fh-seo-options').removeClass('is-running');
        $j('#page-fh-seo-options .input, #page-fh-seo-options .button').prop( "disabled", false );
       	fhSeo.running = false;
       	
       	$j('#page-fh-seo-options .fh-seo-log').prepend('<span class="log-item">Finished: '+ new Date().toString() +'</span>');
	}
}
jQuery(document).ready(function() {
	fhSeo.init();
})