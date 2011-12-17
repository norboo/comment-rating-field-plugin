jQuery(document).ready(function($) {
    if ($('form#commentform textarea[name=comment]').length > 0) {
        $('form#commentform textarea[name=comment]').after('<p><label for="rating-star">Rating</label><input name="rating-star" type="radio" class="star" value="1" /><input name="rating-star" type="radio" class="star" value="2" /><input name="rating-star" type="radio" class="star" value="3" /><input name="rating-star" type="radio" class="star" value="4" /><input name="rating-star" type="radio" class="star" value="5" /><input type="hidden" name="crfp-rating" value="0" /></p>');    
    	$('input.star').rating(); // Invoke rating plugin
    	$('div.star-rating a').bind('click', function(e) { $('input[name=crfp-rating]').val($(this).html()); }); // Stores rating in hidden field ready for POST
    	$('div.rating-cancel a').bind('click', function(e) { $('input[name=crfp-rating]').val('0'); }); // Stores rating in hidden field ready for POST
	}
});