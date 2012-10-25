VG = {};

VG.events = {
	'#methodSelection, #httpMethodSelection': {
		change: function (e) {
			VG.api.updateForm(e);
		}
	},
	
	'#exampleUrlLink': {
		click: function () {
			$(this).text(($('#exampleUrls').is(':visible') ? 'Show' : 'Hide ') + ' Example URL\'s');
			$('#exampleUrls').toggle();
		}
	},
	
	'#apiForm': {
		submit: function (e) {
			e.preventDefault();			
			
			var apiMethod = VG.api.methods[$('#methodSelection').val()],
				httpMethod = apiMethod.httpMethods[$('#httpMethodSelection').val()],
				$buttons = $('input[type=button], input[type=submit]', this).attr('disabled', 'disabled'),
				$output = $("#output pre").text(''),
				$loader = $('#loader').show();
				parameters = {
					method: apiMethod.path,
					httpMethod: httpMethod.name
				};
						
			$.each(apiMethod.pathParameters, function (idx, name) {
				var regexp = new RegExp('\{' + name + '\}'),
					value = $('#apiForm input[type=text][name=' + name + ']').val();
				
				if (parameters.method.match(regexp)) {
					parameters.method = parameters.method.replace(regexp, value);
				} else {					
					parameters.method += '/' + value;
				}
			});
			
			$.get('apirequest.php?&' + $.param(parameters) + '&' + $('#apiForm input[type=text][value!=""]').serialize(), function (data) {
				$output.text(FormatJSON(data));
				prettyPrint();
				$loader = $('#loader').hide();
				$buttons.removeAttr('disabled');
			});
			
			return false;
		}
	}

};

VG.api = {
	updateForm: function (evt) {
		var apiMethod = VG.api.methods[$('#methodSelection').val()],
			$httpMethodSelection = $('#httpMethodSelection');
		
		if (!$httpMethodSelection[0]) {
			$('#httpMethodTemplate').tmpl({ methods: apiMethod.httpMethods }).appendTo('#httpMethods');
		} else if (evt.target != $httpMethodSelection.get(0)) {
			var tmplItem = $httpMethodSelection.tmplItem();
			tmplItem.data = { methods: apiMethod.httpMethods };
			tmplItem.update();
		}
		
		VG.api.updateFields(apiMethod);
	},
	
	updateFields: function (apiMethod) {
		$('#apiForm .field').not('.header, .footer').remove();

        $('#meta h3').html(apiMethod.url);
        $('#meta p').html(apiMethod.description + '');
        
		var selectedHttpMethodIdx = $('#httpMethodSelection').val(),
			requiredFields = $.merge([], apiMethod.httpMethods[selectedHttpMethodIdx].required),
			fields = $.merge([], apiMethod.pathParameters),
			fields = $.merge(fields, requiredFields),
			fields = $.merge(fields, apiMethod.httpMethods[selectedHttpMethodIdx].optional),
			requiredFields = $.merge(requiredFields, apiMethod.pathParameters);
		
		$('#fieldsTemplate').tmpl({ fields: fields, requiredFields: requiredFields }).insertBefore('#apiForm .field.footer');
	}
	
};
