$(document).ready(function() {
    $.metadata.setType("attr", "validate");
    var errcontainer = $('.errcontainer');

    $.validator.setDefaults({
	    //submitHandler: function() { alert("submitted!"); },
	    highlight: function(input) {
		    $(input).addClass("ui-state-error");
		    $(input).parent('.mandatory').addClass("ui-state-error");
	    },
	    unhighlight: function(input) {
		    $(input).removeClass("ui-state-error");
		    $(input).parent('.mandatory').removeClass("ui-state-error");
	    }
    });

    $("#mainform").validate({
	  errorContainer: errcontainer,
	  errorLabelContainer: $("ol", errcontainer),
	  wrapper: 'li',
    });

});
