<?php
class SpecialShareOSE extends SpecialUpload {
//class SpecialShareOSE extends SpecialUpload {
        function __construct() {
                parent::__construct( 'ShareOSE' );
        }
 
        function execute( $par ) {
                global $wgRequest, $wgOut;
 
                $this->setHeaders();
 
                # Get request data from, e.g.
                $param = $wgRequest->getText('param');
 
                # Do stuff
                # ...
                $output = 'Proof of concept only..';
				$wgOut->addWikiText( $output );
				# define form with labels above text fields for better readability
				$sampleMessage = 'Hey <INSERT NAME>, You, me and a thousand other visionairies are teaming up to create a Global Village Construction set.  This is a high-performance, modular, do-it-yourself, low-cost platform - that allows for the easy fabrication of the 50 different industrial machines that it takes - to build a small, sustainable civilization with modern comforts.  We are all giving $10 month (ie. 2 cups of coffee) for 2 years to make this happen and create a better world.  You even get enshrined on their web site as a True Fan and your name immortalized in the documentation as a founding True Fan to impress your grandchildren.  Check out the link below to see a video I made for you and more info.';
				$form = 
				'<form action="http://microfundingtest.openfarmtech.org/wiki/Special:ShareOSE" method="post" class="visualClear" enctype="multipart/form-data" id="mw-upload-form">
					
					<!-- Email -->
					<fieldset>
						<legend>Email to send to Friend/colleague</legend>
						<table id="mw-htmlform-source">
							<tbody>
								<tr>
									<td>Name:</td>
								</tr>
								<tr>
									<td class="mw-input"><input name="contactName" size="40" type="text" />
									</td>
								</tr>
								<tr>
									<td>Email Address:</td>
								</tr>
								<tr>
									<td class="mw-input"><input name="contactEmail" size="40" type="text" />
									</td>
								</tr>
								<tr>
									<td>Message to friend/colleague(update or create your own):</td>									
								</tr>
								<tr class="mw-htmlform-field-HTMLTextAreaField ">
									<td class="mw-input">
										<textarea id="mw-input-wptextarea" rows="5" cols="80" name="emailContent">' . $sampleMessage . '</textarea>
									</td>
								</tr>
							</tbody>
						</table>
					</fieldset>
					
					<!-- Upload Video -->
					<fieldset>
						<legend>Upload personalized intro video</legend>
						<table id="mw-htmlform-source">
							<tbody>
								<tr class="mw-htmlform-field-UploadSourceField"><td class="mw-label"><label for="wpSourceTypeFile">Video file to upload:</label></td>
								<td class="mw-input"><input id="wpUploadFile" name="wpUploadFile" size="60" type="file" />
								</td>
								</tr>
								<tr><td colspan="2" class="htmlform-tip">Maximum file size: 5 MB  (a file on your computer)</td></tr>
								<tr class="mw-htmlform-field-HTMLInfoField"><td class="mw-label"><label></label></td><td class="mw-input">
								</td></tr>
							</tbody>
						</table>
					</fieldset>
					
					<!-- Please Read -->
					<fieldset>
						<legend>Please Read </legend>						
						Once you click on button below and your introductory video successfully uploads, an email will be sent to your friend/colleague containing your message and a link to the Join True Fans web page with your video at the top.
					</fieldset>
					<input id="wpEditToken" type="hidden" value="ff8f0b5c225c9b0b0f1b6913da85c329+\" name="wpEditToken" />
					<input type="hidden" value="Special:Upload" name="title" />
					<input type="hidden" name="wpDestFileWarningAck" />
					<input type="submit" value="Upload file & email message" name="wpUpload" title="Start upload [s]" accesskey="s" class="mw-htmlform-submit" />
				</form>';
				$wgOut->addHTML( $form );

        }
}