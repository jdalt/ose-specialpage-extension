/*
 *  dynamic.js
 */


$j(document).ready(function() {
	console.log('$j says document ready');
	$j('#ose-truefan-email-input').keyup(addEmailInput);
	$j('#ose-truefan-message').keyup(function(){$j('#quote-area p').html($j('#ose-truefan-message').val());});
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
		$j('#trueFanForm .mw-htmlform-field-HTMLTextArray').append('<div class="mw-label"><label for="ose-truefan-email-input">Email</label></div><div class="mw-input"><input type="text" name="wpEmailInput[]"/></div>');
		$j('#trueFanForm .mw-htmlform-field-HTMLTextArray input:last').keyup(addEmailInput);
		console.log('added email input');
	}
}
