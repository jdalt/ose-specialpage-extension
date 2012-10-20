/*
 *  dynamic.js
 */


$j(document).ready(function() {
	console.log('$j says document ready');
	$j('#ose-truefan-email-input').keyup(addEmailInput);
});

function runOnloadHook()
{
	console.log('runonloadhook');
}

function addEmailInput(e)
{
	// Any input that contains value creates a single new input, and pays the process forward
	console.log(this.value);
	if(this.value != '') {
		$j(this).unbind('keyup');
		$j('#trueFanForm tbody:first').append('<tr><td class="mw-label"><label>Email</label></td><td><input type="text" name="wpEmailInput[]"/></td></tr>');
		$j('#trueFanForm tbody:first td input:last').keyup(addEmailInput);
		console.log('added email input');
	}
}
