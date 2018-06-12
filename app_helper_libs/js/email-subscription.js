jQuery(function() {
	//removing header image on home page
	var formType = jQuery('#subscription-api-form-type').val();
	if(formType == 'type-1') {
		jQuery('.block-email-subscription .email-subscription-header-img').remove();
		var changeCol = jQuery('.block-email-subscription .change-col');
		changeCol.removeClass('col-lg-8');
		changeCol.removeClass('col-md-8');
	}
	
	jQuery("#email-subscription-api-form1-submit").on('click', function(e) {
		if(validateNameField() && validateEmailField()) {
			if(formType == 'type-1') {
				return true;
			}
			else {
				e.preventDefault();
				// hide signup content
		        jQuery('.subscription-form-type-1').slideUp();
		        // open options content
		        jQuery('.subscribe-details-block').slideDown();
			}
		}
		return false;
	});
	
	jQuery(".subscription-name").on('blur', function(e){
		return validateNameField();
	});
	
	jQuery(".subscription-email").on('blur', function(e){
		return validateEmailField();
	});
	
	/*jQuery("#email-subscribe-newsletter-btn").on('click', function(e) {
		e.preventDefault();
		//validate name field
		validateNameField();
	    
	    //validate email field
		validateEmailField();
	    
	    // hide signup content
        jQuery('.subscription-form-type-1').slideUp();
        // open options content
        jQuery('.subscribe-details-block').slideDown();
    });*/
	
	jQuery('#email-subscribe-cancel-button').on('click', function(e){
		// show signup content
        jQuery('.subscription-form-type-1').slideDown();
        // hide options content
        jQuery('.subscribe-details-block').hide();
        scrollToSubscriptionApiBlock();
	});
	
	jQuery('.app-social-below-media .subscribe-svg').on('click', function(e){
		scrollToSubscriptionApiBlock();
	});
	
	function validateNameField() {
		var inputNameElem = jQuery('#email-subscription .subscription-name');
		inputNameElem.next('.validator').remove();
		var inputName = inputNameElem.val();
	    var nameRegx = /([a-zA-ZÀ-ž- ])$/;
	    if(inputName.trim() == '') {
	    	inputNameElem.after('<span class="validator">Full Name is required</span>');
	    	return false;
	    }
	    else if(!nameRegx.test(inputName)) {
	    	inputNameElem.after('<span class="validator">Full Name accepts only alphabets</span>');
	    	return false;
	    }
	    else if(!nameRegx.test(inputName) || inputName.length > 50) {
	    	inputNameElem.after('<span class="validator">Full Name cannot exceed 50 characters</span>');
	    	return false;
	    }
	    return true;
	}
	
	function validateEmailField() {
		var inputEmailElem = jQuery('#email-subscription .subscription-email');
		inputEmailElem.next('.validator').remove();
		var inputEmail = inputEmailElem.val();
	    var emailRegx = /[A-Za-z]+[A-Za-z0-9._]+@[A-Za-z]+\.[A-Za-z.]{2,5}$/;
	    if(inputEmail == '') {
	    	inputEmailElem.after('<span class="validator">Email is required</span>');
	    	return false;
	    }
	    else if(!emailRegx.test(inputEmail)) {
	    	inputEmailElem.after('<span class="validator">Check the email address format</span>');
	    	return false;
	    }
	    return true;
	}
	
	function scrollToSubscriptionApiBlock() {
		jQuery('html, body').animate({
	        scrollTop: jQuery('.email-subscription-header-img').offset().top - 100
	    }, 'slow');
	}
});