<?php
/**
 * SpecialShareOSE.php - the html entry point for displaying gluing this thing together
 */

require_once('class.TrueFansDb.php');
 
class SpecialShareOSE extends SpecialPage {

	protected $mTrueFanDb;
	 
	public function __construct() {
		global $wgRequest;
		tfDebug('***  Constructing SpecialShareOSE  ***');
		parent::__construct( 'ShareOSE');

		$this->loadRequest();
		$this->mDb = new TrueFansDb(); // not good to do for unit testing...
	}

	/** Misc variables **/
	protected $mRequestPosted;
	protected $mReqPostPage; 
	
	protected $mReqName;
	protected $mReqEmail;
	protected $mReqVideoId;
	
	protected $mReqInviteEmails;
	protected $mReqInviteMessage;
	protected $mReqInviteId;

	protected $mReqGetPage;

	/**
	 * Initialize instance variables from request.
	 */
	protected function loadRequest() {
		global $wgUser, $wgRequest;

		// MediaWiki prefixes 'wp' to names in the form $descriptor
		// !! Security Concern - we need to guarantee requests come 
		// !! through the proper way GET or POST. How w/o using form?

		$this->mRequestPosted = $wgRequest->wasPosted();
		$this->mReqPostPage = $wgRequest->getText('Page');
		
		$this->mReqName = $wgRequest->getText('Name');
		$this->mReqEmail = $wgRequest->getText('wpEmail');
		$this->mReqVideoId = $wgRequest->getText('wpVideoId');

		$this->mReqInviteEmails = $wgRequest->getArray('wpEmailInput');
		$this->mReqInviteMessage = $wgRequest->getText('wpMessage');
		$this->mReqInviteId = $wgRequest->getText('Id');
		
		$this->mReqGetPage = $wgRequest->getText('page');
		$this->mReqId = $wgRequest->getText('id');
	}

	/**
	 * Special page entry point
	 */
	public function execute( $par ) {
		global $wgUser, $wgOut, $wgRequest, $wgScriptPath;

		$this->setHeaders();
		$this->outputHeader();
		$wgOut->addExtensionStyle($wgScriptPath.'/extensions/ShareOSE/style.css');
		$wgOut->addScriptFile($wgScriptPath.'/extensions/ShareOSE/dynamic.js');
		
		$url = $this->getTitle()->getLocalUrl();
		$full_url = $this->getTitle()->getFullUrl(); 

		echo "<strong>$full_url</strong><span>{$this->getTitle()->getFragment()}</span>";

		// Request specific HTML dependent upon the request
		switch($this->mReqGetPage) {
			case 'view':
				$profile = $this->mDb->getUser($this->mReqId);
				$wgOut->addHTML("<div><span>{$profile['name']}: </span><span>{$profile['email']}</span></div>");
				$wgOut->addHTML("<iframe src='http://www.youtube.com/embed/".$profile['video_id']."'>No iframes.</iframe>");
				$wgOut->addHTML("<p>".$profile['video_message']."</p>");

				break;
	
			case 'invite':
				if($wgUser->isLoggedIn()) {
					$profile = $this->mDb->getUserByEmail($wgUser->getEmail());
					$id = $profile['id'];
					if($id) {	
						$wgOut->addHTML('<p>Invite your friends to become part of OSE.</p>');
						$wgOut->addHTML("<div><span>{$profile['name']}: </span><span>{$profile['email']}</span></div>");
						$wgOut->addHTML("<iframe src='http://www.youtube.com/embed/".$profile['video_id']."'>No iframes.</iframe>");
						
	
						$inviteForm = new TrueFanForm($this->getTitle(), 'invite');
						$inviteForm->setFieldDefault('Id', $id); 
						
						if($profile['video_message'] && $profile['email_invite_list']) {
							$inviteForm->setFieldDefault('Message', $profile['video_message']);
							$inviteForm->setFieldDefault('EmailInput', $profile['email_invite_list']);
						}
						$inviteForm->show();	
					} else {
						$wgOut->addHTML("You must create a True Fan profile and submit a video before inviting people to become True Fans.</p>");
						$wgOut->addHTML("<a href='?page=home'>Create Profile</a>"); // TODO: cleanup this url 
					}
				} else {
					$wgOut->addHTML("<p>You must be logged in to invite people to become true fans.</p>");
				}
				break;
			
			case 'subscribe':
				$wgOut->addHTML('<p>True Fans: As a project working for the common good, we rely on a growing group of'.
									 'crowd-funders called True Fans to keep our mission alive. The True Fans program is a'.
									 'monthly donation with options for $10, $20, $30, $50, and $100 per month for 24 months. </p>');
				$wgOut->addHTML($this->getPayPalButton());	
				break;
			
			case 'viewall':
				$result = $this->mDb->getAllEntries();
				$wgOut->addHTML('<table id="submissions"><tbody>');
				foreach($result as $row) {
					$wgOut->addHTML('<tr>');
					$wgOut->addHTML("<td><a href='?page=view&id=".$row['id']."'>{$row['id']} </a></td>");
					// This is sort of future proofed for further column additions to TrueFanDb
					foreach($row as $key => $val) {
						if($key != 'id') {
							$wgOut->addHTML("<td>$val</td>");
						}
					}
					$wgOut->addHTML('</tr>');
				}
				$wgOut->addHTML('</table></tbody>');
				break;
			
			default:
				if($this->mRequestPosted) {
					if($this->mReqPostPage === 'video') {
						$wgOut->addHTML("<p>Received form from: <strong>".$this->mReqName."</strong></p>");
						if($id = $this->mDb->addUser($this->mReqName, $this->mReqEmail, $this->mReqVideoId)) {
							$wgOut->addHTML("<p>Added request to DB.</p>");
							$profile = $this->mDb->getUser($id);
							$wgOut->addHTML("<div><span>{$profile['name']}: </span><span>{$profile['email']}</span></div>");
							$wgOut->addHTML("<iframe src='http://www.youtube.com/embed/".$profile['video_id']."'>No iframes.</iframe>");

							$wgOut->addHTML('<p>Invite your friends to become part of OSE.</p>');
							$inviteForm = new TrueFanForm($this->getTitle(), 'invite');
							$inviteForm->setFieldDefault('Id', $id); // I can also get this info from the $wgUser...which may be better for release
							$inviteForm->show();	

						} else {
							if($this->mDb->isDuplicateEmail($this->mReqEmail)) {
								$wgOut->addHTML("<p>Unable to add user. Your email was already entered.</p>");
							} else if(!$this->mDb->extractVideoId($this->mReqVideoId)) {
								$wgOut->addHTML("<p>Unable to extract video id from URL.</p>");
							} else {
								$wgOut->addHTML("<p>Unable to add request to DB.</p>");
							}
						}
					} elseif($this->mReqPostPage === 'invite') {
						if($this->mDb->addInvitation($this->mReqInviteId, $this->mReqInviteMessage, $this->mReqInviteEmails)) { 
							$wgOut->addHTML("<p>{$this->mReqInviteMessage}</p><ul>"); 
							foreach($this->mReqInviteEmails as $email) {
								$wgOut->addHTML("<li>$email</li>");
							}
							$wgOut->addHTML("</ul>");	
							$link = $full_url."?page=view&id={$this->mReqInviteId}";
							$wgOut->addHTML("<span>View your video and message at this link: </span><a href='$link'>$link</a>");
						} else {
							$wgOut->addHTML("<p>Unable to insert message and email list :< </p>");
						}
					} else {
						$wgOut->addHTML("<p>Unknown request posted.</p>");
					}
				} else {
					// TODO: Make login a condition for all input pages...
					if($wgUser->isLoggedIn()) {
						$wgOut->addHTML('<p>Your login info was added to the form.</p>');
						$postPage = Title::newFromText($url.'?page=invite'); 
						$videoForm = new TrueFanForm($postPage);
						$videoForm->setFieldDefault('Name', $wgUser->getRealName());
						$videoForm->setFieldDefault('NameDisplay', $wgUser->getRealName());
						$videoForm->setFieldDefault('Email', $wgUser->getEmail());
						$videoForm->show();
					} else {
						$wgOut->addHTML('<p>You must log in to submit a video.</p>');
					}
				}
				break;
		}
		// Global HTML added to every page
		$wgOut->addHTML('<ul id="special-links"><li><a href="'.$url.'?page=home">Submit A Video</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=invite">Invite Friends</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=subscribe">Become a True Fan</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=viewall">View All Submissions</a></li></ul>');
	}
	
	private function getPayPalButton()
	{
		// ugly text drop of paypal button, you can modify css and add things, but don't mess with names and values of fields
		$str = <<<EOT
		<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="hosted_button_id" value="LW3QK7UZWFZ2Y">
		<table>
		<tr><td><input type="hidden" name="on0" value=""></td></tr><tr><td><select class="serious-button" name="os0">
			<option value="Standard">Standard : $10.00 USD - monthly</option>
			<option value="Gold">Gold : $20.00 USD - monthly</option>
			<option value="Gold Extra">Gold Extra : $30.00 USD - monthly</option>
			<option value="Platinum">Platinum : $50.00 USD - monthly</option>
			<option value="Angel">Angel : $100.00 USD - monthly</option>
		</select> </td></tr>
		</table>
		<input type="hidden" name="currency_code" value="USD">
		<input type="submit" id="paypal-submit" class="serious-button" name="submit" value="Subscribe">
		<!-- <img alt="" border="0" src="https://www.sandbox.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"> -->
		</form>
EOT;
	return $str;
}
}


class TrueFanForm
{
	protected $mDescriptor;
	protected $mForm;
	protected $mTitle;
	protected $mType;	
	protected $mFormBuilt;
	protected $mFormLoaded;

	/*
	 * @param $title a MW Title object with url info for post action
	 * @param $type the type of form to create
	 */
	public function __construct($title, $type='video')
	{
		$this->mType = $type;
		$this->initializeDescriptor();
		$this->mTitle = $title;
		$this->mFormBuilt = false;
		$this->mFormLoaded = false;
	}

	private function build()
	{
		if(!$this->mFormBuilt) {
			// Reminder: 2nd param in constructor creates messagePrefix which is used to 
			// name the fieldset. (text of which is .i18n file)
			$this->mForm = new HTMLForm($this->mDescriptor, "trueFanForm"); 
			$this->mForm->setSubmitText(wfMsg("trueFanSubmitText-".$this->mType)); 
			$this->mForm->setSubmitName('submit');
			$this->mForm->setId("trueFanId-".$this->mType);
			$this->mForm->setTitle($this->mTitle);
			//$this->mForm->loadData();
			$this->mFormBuilt = true;
		}
	}

	// builds and shows the form
	public function show()
	{
		$this->build();
		$this->mForm->show();
	}

	// only works with an **unbuilt** form, once the form is built you can't change descriptor
	public function setFieldDefault($field, $value)
	{
		if(!$this->mFormBuilt) {
			$this->mDescriptor[$field]['default'] = $value;
		} else {
			tfDebug("Can't add default to a built form.");
		}
	}

	// !! MW 1.16 this function won't return the values of hidden fields, making it useless
	// as a replacement to the jumble of code in SpecialPage::loadRequest()
	public function getData($field)
	{ 
		if(!$this->mFormLoaded) {
			$this->build();
			$this->mForm->loadData();
			$this->mFormLoaded = true;
			tfDebug("Loaded {$this->mType}");
		}
		$data = $this->mForm->mFieldData[$field]; 
		tfDebug("$field is $data");
		return $data;
	}

		
	private function initializeDescriptor()
	{
		// TODO: Internationalize all labels 
		switch($this->mType) {
		case 'video':
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
					'section' => $this->mType,
				),
				'NameDisplay' => array(
					'type' => 'info',
					'value' => '',
					'section' => $this->mType,
					'id' => 'ose-truefan-name',
					'size' => 20,
				),
				'Name' => array(
					'type' => 'hidden',
					'section' => $this->mType,
				),
				'Email' => array(
					'type' => 'text',
					'section' => $this->mType,
					'id' => 'ose-truefan-email',
					'label' => 'Email',
					'size' => 20,
				),
				'VideoId' => array(
					'type' => 'text',
					'section' => $this->mType,
					'id' => 'ose-truefan-url',
					'label' => 'Url of Video',
					'size' => 20,
				),
			);
			break;
		case 'invite':
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
					'section' => $this->mType,
				),
				'Message' => array(
					'type' => 'textarea',
					'section' => $this->mType,
					'id' => 'ose-truefan-message',
					'label' => 'Message',
					'rows' => 5,
				),
				'EmailInput' => array(
					'type' => 'text',
					'section' => $this->mType,
					'id' => 'ose-truefan-email-input',
					'label' => 'Email',
					'name' => 'EmailInput[]', /* >= MW 1.17 */
					'size' => 20,
					'cssclass' => 'changeme',
				),
				'Id' => array(
					'type' => 'hidden',
					'section' => $this->mType,
				),

			);
			break;
		}
	}
}



/**
 * Syntactic sugar. Outputs to local extension log.
*/
function tfDebug($str)
{
	wfDebug($str."\n"); // !!! Not intended for production use !!! This will inject lots of extension specific logging into the master log which should only be for important errors !!!
	wfDebugLog( 'ShareOSE', $str);
}
