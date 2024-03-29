$(document)
.on("submit", "form.js-register, form.js-login", function(event) {
	event.preventDefault();

	var _form = $(this);
	var _error = $(".js-error", _form);

	var dataObj = {
		email: $("input[type='email']", _form).val(),
        password: $("input[type='password']", _form).val(),
		act: (_form.hasClass('js-register')) ? 'registr' : 'login'
	};

	if(dataObj.email.length < 6) {
		_error
			.text("Пожалуйста введите валидный email адресс.")
			.show();
		return false;
	} else if (dataObj.password.length < 4) {
		_error
			.text("Пожалуйста введите пароль не короче 4 знаков.")
			.show();
		return false;
	}

	// Assuming the code gets this far, we can start the ajax process
	_error.hide();

	$.ajax({
		type: 'POST',
		url: '/',
		data: dataObj,
		dataType: 'json',
		async: true,
	})
	.done(function ajaxDone(data) {
        //alert(data);
        console.log(data);
		// Whatever data is 
		if(data.redirect !== undefined) {
            
			window.location = data.redirect;
		} else if(data.error !== undefined) {
			_error
				.text(data.error)
				.show();
		}
	})
	.fail(function ajaxFailed(e){
		// This failed
        console.log(e); 
	})
	.always(function ajaxAlwaysDoThis(data) {
		// Always do
		console.log('Always');
	})

	return false;
})