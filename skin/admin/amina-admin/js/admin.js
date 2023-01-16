
$(document).ready(function($) {

	//Go Top
	$().UItoTop();

	//Setup Open & Close
	$(".btn-switcher").click(function(e){
		e.preventDefault();
		var div = $("#admin-switcher");
		if (div.css("left") === "-185px") {
			$("#admin-switcher").animate({
				left: "0px"
			}); 
		} else {
			$("#admin-switcher").animate({
				left: "-185px"
			});
		}
	});
});