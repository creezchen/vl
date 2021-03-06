var FormRepeater = function () {

    return {
        //main function to initiate the module
        init: function () {
        	$('.mt-repeater:not(.frank)').each(function(){
        		$(this).repeater({
        			show: function () {
	                	$(this).slideDown();
                        $('.date-picker').datepicker({
                            rtl: App.isRTL(),
                            orientation: "left",
                            autoclose: true
                        });
						$('.timepicker-24').timepicker({
							autoclose: true,
							minuteStep: 1,
							showSeconds: false,
							showMeridian: false
						});
		            },

		            hide: function (deleteElement) {
		                //if(confirm('Are you sure you want to delete this element?')) {
		                    $(this).slideUp(deleteElement);
		                //}
		            },

		            ready: function (setIndexes) {

		            }

        		});
        	});
        }

    };

}();

jQuery(document).ready(function() {
    FormRepeater.init();
});
