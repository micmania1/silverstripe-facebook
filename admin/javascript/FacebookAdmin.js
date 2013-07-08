jQuery(document).ready(function($)
{
    $('.silverstripe-facebook-ui-action-facebook').entwine({
        onmatch: function ()
        {
         	$(this).click(function() {
         		var url = $(this).attr("data-url");
         		if(url) window.location = $(this).attr("data-url");
         		return false;
         	}); 
		}
    });
});
