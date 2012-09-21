$(document).ready(function(){
	$('#uin').bind('blur', captcha_init);
	$('#login').bind('click', user_login);
	$('#get').bind('click', url_get);
	setInterval(user_cookies, 1000);
});

function captcha_init()
{
	var uin = $('#uin').val();
	if (uin.toString().length < 5) return;
	$('#get').val('GET(of '+uin+')');
	$('#vfcode').val('loading...');
	$.get('server.php?do=check&uin='+uin, function(data){
		if (data.indexOf('?') > 0)
		{
			var uin = $('#uin').val();
			var uinc = data.substr(0, data.indexOf('?'));
			$('#uin_enc').val(uinc);
			$.get('server.php?do=captcha&uin='+uin, function(data){
				$('#captcha').html('<img src="'+data+'" onload="$(\'#vfcode\').val(\'\');" />');
			});
		}
		else
		{
			var uin = data.substr(0, data.indexOf(':'));
			var vfcode = data.substr(data.indexOf(':') + 1, data.length - data.indexOf(':'));
			$('#uin_enc').val(uin);
			$('#vfcode').val(vfcode);
		}
	});
}

function user_login()
{
	var uin = $('#uin').val();
	var password = $('#password').val();
	var vfcode = $('#vfcode').val();
	$('#result').html('loading...');
	$.get('server.php?do=login&uin='+uin+'&password='+password+'&vfcode='+vfcode, function(data){
		$('#result').html(data);
	});
}

function url_get()
{
	var uin = $('#uin').val();
	var url = $('#url').val();
	$.get('server.php?do=url&uin='+uin+'&url='+encodeURIComponent(url), function(data){
		$('#response').val(data);
	});
}

function user_cookies()
{
	var uin = $('#uin').val();
	$.get('server.php?do=cookies&uin='+uin, function(data){
		$('#cookies').html(data);
	});
}