(function($) {
	$.session = function(name, value) {
		var result = 0;

		if (value) {
			$.ajax({
				url: window.flexiweb.site_url + "/ajax/set_session_var.php",
				type: "POST",
				async: false,
				dataType: "text",
				data: 'var=' + name + '&value=' + value,
				success: function(data){result = true;}
			
			});
		} else {
			$.ajax({
				url: window.flexiweb.site_url + "/ajax/get_session_var.php",
				type: "POST",
				async: false,
				dataType: "text",
				data: 'var=' + name,
				success: function(data){result = data;}
			});
		}
		
		return result;
	}
})(jQuery);