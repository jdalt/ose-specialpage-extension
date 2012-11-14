/*
 *  dynamic.js
 */

var emailWrap;

$j(document).ready(function() {
	console.log('$j says document ready');
		
	$j('#ose-truefan-message').keyup(function(){$j('#quote-area p').html($j('#ose-truefan-message').val());});

	// Execute conditional of correct template
	// The specific html to wrap these elements is stored in the template in a script tag
	if(emailWrap) {
		// TODO: Consider moving this into a global in script tag within the template to keep separation of concerns clear
		$j('.mw-htmlform-field-HTMLTextArray').html(
				'<div class="email-list-combo">'+
					'<div class="email-input-cluster">'+
						'<div class="mw-label">'+
							'<label>Name</label>'+
						'</div>'+
						'<div class="mw-input">'+
							'<input class="email-names" type="text" />'+
						'</div>'+
					'</div>'+
					'<div class="email-input-cluster">'+
						'<div class="mw-label">'+
							'<label for="ose-truefan-email-input">Email</label>'+
						'</div>'+
						'<div class="mw-input">'+
							'<input class="email-addresses" type="text" name="wpEmailInput[]"/>'+
						'</div>'+
					'</div>'+
				'</div>');
		$j('.email-addresses').keyup(addEmailInput);
		$j('#mw-form-section-email .mw-htmlform-field-HTMLTextArray').children().wrapAll(emailWrap);
		$j('#email-list-modal').prepend(emailHelpMessage);
		$j('#email-list-modal').append(emailButton);

		// TODO: Consider moving this out of the conditional global above, should be harmless in all cases
		for (var source in autoReplaceArray) {
  			if (autoReplaceArray.hasOwnProperty(source)) {
				var sourceHtml = $j(source).html();
				$j(autoReplaceArray[source]).html(sourceHtml);
				$j(source).empty();
  			}
		}

		for (var source in autoInputArray) {
  			if (autoInputArray.hasOwnProperty(source)) {
				var sourceText = $j(source).html();
				$j(autoInputArray[source]).val(sourceText);
				$j(source).empty();
  			}
		}
	}

});

function runOnloadHook()
{
	console.log('runonloadhook');
}

function addEmailInput(e)
{
	// Any input that contains value creates a single new input, and pays the process forward
	if(this.value != '') {
		$j(this).unbind('keyup');
		// FIXME: for tags are incorrect
		$j('#email-input-container').append(
				'<div class="email-list-combo">'+
					'<div class="email-input-cluster">'+
						'<div class="mw-label">'+
							'<label>Name</label>'+
						'</div>'+
						'<div class="mw-input">'+
							'<input class="email-names" type="text" />'+
						'</div>'+
					'</div>'+
					'<div class="email-input-cluster">'+
						'<div class="mw-label">'+
							'<label for="ose-truefan-email-input">Email</label>'+
						'</div>'+
						'<div class="mw-input">'+
							'<input class="email-addresses" type="text" name="wpEmailInput[]"/>'+
						'</div>'+
					'</div>'+
				'</div>');
		$j('#trueFanForm .email-input-cluster input:last').keyup(addEmailInput);
		console.log('added email input');
	}
}

function processEmail()
{
	var addresses = new Array();
	// TODO: Remove empty email inputs
	$j('.modal-input-overlay').addClass('inactive');
	$j('#share-email-preview').empty('');
	$j('#email-input-container .email-list-combo').each( function(index) {
		var name = $j(this).find('.email-names').val();
		var address = $j(this).find('.email-addresses').val();
		if(address != '') {
			//TODO: use norm < and > for hidden field string
			addresses.push(name + ':<' + address + '>');
			console.log(address);
		}
	});
	console.log(addresses);
	$j('input[name=wpEmailList]').val(addresses.join(','));

	$j('#share-email-preview').append('<h3>Email List</h3>');
	for(var i=0; i<addresses.length; i++) {
		addresses[i] = addresses[i].replace('<','&lt');
		addresses[i] = addresses[i].replace('>','&gt');
		addresses[i] = addresses[i].replace(':',' ');
		$j('#share-email-preview').append('<div class="email-preview-item"><img src="/w/extensions/ShareOSE/images/email_sm.png" /><div class="preview-address">'+addresses[i]+'</div></div>');
	}
}
