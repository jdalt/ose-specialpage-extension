/*
 *  dynamic.js
 */


$j(document).ready(function() {
	console.log('$j says document ready');
	$j('#ose-truefan-email-input').blur(addEmailInput);
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
		$j('#trueFanForm tbody:first').append('<tr><td class="mw-label"><label>Email</label></td><td><input type="text" name="wpEmailInput[]"/></td></tr>');
		$j('#trueFanForm tbody:first td input:last').blur(addEmailInput);
		console.log('added email input');
	}
}
