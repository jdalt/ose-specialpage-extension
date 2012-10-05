/*
 *  dynamic.js
 */


$j(document).ready(function() {
	console.log('$j says document ready');
	$j('#ose-truefan-email-input').blur(addEmailInput);
	$j('#ose-truefan-email-input').attr('name','wpEmailInput[]'); // fix for MW 1.16 b/c can't change name on form fields.
});

function runOnloadHook()
{
	console.log('runonloadhook');
}

function addEmailInput(e)
{
	// Any keyup except tab (which == 9) creates a new input in our form
	if(this.value != '') {
		$j(this).unbind('blur');
		$j('#trueFanId-invite tbody:first').append('<tr><td class="mw-label"><label>Email</label></td><td><input type="text" name="wpEmailInput[]"/></td></tr>');
		$j('#trueFanId-invite tbody:first td input:last').blur(addEmailInput);
		console.log('added email input');
	}
}
