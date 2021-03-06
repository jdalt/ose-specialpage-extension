<?php
/**
 * SpecialTrueFans.php -- the html entry point for displaying and gluing pages that manage TrueFansDb.
 * Copyright 2012 by Jacob Dalton	
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @file SpecialTrueFans.php
 * @author Jacob Dalton <jacobrdalton@gmail.com>
 * @ingroup Extensions
*/

// class.TrueFansDb.php is automatically included and defines constants for the field names
require_once('SexyForm.php');
 
class SpecialTrueFans extends SpecialPage {
	// This variables directly affect the html structure of page and contents of the db
	public $mDb;
	public $mTfProfile;
	public $mFormStep;
	public $mEditRedirect;
	public $mErrorMessage;
	public $mStatusMessage;
	
	// GET Request variables. These are loaded manually unlike POST variables.	
	protected $mReqGetPage;
	protected $mReqId;
	protected $mReqPostPage; 
	protected $mPostedForm;
	 
	/**
	 * Load requests and build TrueFansDb
	 */
	public function __construct() {
		global $wgUser, $wgRequest;
		tfDebug('--Constructing SpecialTrueFans--');
		
		parent::__construct( 'TrueFans');
		
		// Initialize string member variables
		$this->mErrorMessage = $this->mStatusMessage = '';
		$this->mFormStep = 'upload';
		$this->mEditRedirect = ''; // we'll make the real initialization where it's used

		// Initialize GET request variables. 	
		$this->mReqGetPage = $wgRequest->getText('page');
		$this->mReqId = $wgRequest->getText('id');

		// Build the almighty database.
		$this->mDb = new TrueFansDb(); 
		
		// If the user is logged in load their profile via their wiki id
		$this->mTfProfile = NULL;
		if($wgUser->isLoggedIn()) {
			$this->mTfProfile = $this->mDb->getUserByForeignId($this->getMwId());
		}
		
		// Use the POST variable Page to build correct form to auto load the rest of the POST request.
		$this->mReqPostPage = $wgRequest->getText('wpPage');
		if($wgRequest->wasPosted()) {
			$this->mPostedForm = new TrueFanForm($this, $this->mReqPostPage);
		}
	}

	/**
	 * Special page entry point. This function outputs universal scripts
	 * and then hands off to handleViewPage according to content of POST 
	 * GET or default requests.  
	 * @param $par 
	 */
	public function execute( $par ) {
		global $wgUser, $wgOut, $wgScriptPath;

		// Basic style and interaction scripts for these pages
		$this->setHeaders();
		$this->outputHeader();
		$wgOut->addExtensionStyle($wgScriptPath.'/extensions/TrueFans/style.css');
		$wgOut->addScriptFile($wgScriptPath.'/extensions/TrueFans/dynamic.js');
		$wgOut->addScriptFile('//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.4.4/underscore-min.js');
		
		// Request logic. POST > GET. Empty request = 'welcome' GET request. 
		if($this->mReqPostPage) { // Handle POST
			// You must be logged in to submit data. Anything else is nonsense.
			if(!$wgUser->isLoggedIn()) {
				$this->handleViewPage('login');
			} else {
				// loadHandledForm will check load data from the POST request and check the edit token to prevent CSRF attacks
				$formReturn = $this->mPostedForm->loadHandledForm();
				if($formReturn === true) { 
					switch($this->mReqPostPage){
						case 'share':
							$this->handleViewPage('finish');
							break;
						case 'edit':
							$this->handleViewPage($this->mEditRedirect); 
							break;

						default:
							// Update the profile that the viewer will use.
							$this->mTfProfile = $this->mDb->getUserByForeignId($this->getMwId()); 
							// Throw control into the page viewing system. Submit will select a page based on $this->mFormStep
							$this->handleViewPage('submit'); 
							break;
					}
				} else {
					$this->mPostedForm->clearRequests();
					$this->mErrorMessage = $formReturn;
					$this->handleViewPage('submit');
				}
			}
		} elseif($this->mReqGetPage) { // Handle GET
			$this->handleViewPage($this->mReqGetPage);
		} else { // Empty requests, go to the welcome page
			$this->handleViewPage('welcome');
		}

		$this->loadTemplate('noun_project_credits.html');
	}

	/**
	 * The main html display function.
	 * Outputs page specific html according to page request and
	 * (for forms) based on the contents of $this->mFormStep
	 * Page specific HTML is injected here using $this->loadTemplate
	 * @param String $request page requested from GET or
	 * explicitly called by $this->execute or called recursively.
	 */
	public function handleViewPage($request)
	{
		global $wgUser;
		
		// Request specific HTML dependent upon the request
		switch($request) {
			case 'welcome':
				global $wgScriptPath, $wgOut;
				$wgOut->addScriptFile($wgScriptPath.'/extensions/TrueFans/youtubeplayer.js');
				$this->loadTemplate('welcome.html');
				break;
			case 'view':
				global $wgScriptPath, $wgOut;
				$wgOut->addScriptFile($wgScriptPath.'/extensions/TrueFans/youtubeplayer.js');
				$profile = $this->mDb->getUser($this->mReqId);
				if($profile) {
					$this->loadTemplate('view.html', $profile);
				} else {
					$this->loadTemplate('no_profile_exists.html');
				}
				break;
			
			case 'myprofile':
				global $wgScriptPath, $wgOut;
				$wgOut->addScriptFile($wgScriptPath.'/extensions/TrueFans/youtubeplayer.js');
				// TODO: Add extra information like before - email and contacts sent to...?
				if(!$wgUser->isLoggedIn()) {
					$this->handleViewPage('login');
					return;
				} else {
					if(!$this->mTfProfile){
						$this->loadTemplate('no_profile_created.html');
					} else {
						$this->loadTemplate('view.html', $this->mTfProfile);
					}
				}
				break;

			case 'finish':
				if($this->mTfProfile) {
					$this->loadTemplate('finish.html', $this->mTfProfile);
				} else {
					// Error, profile deleted or never existed and sent to finish...
					tfDebug('User without profile attempted to send a message.');
					$this->loadTemplate('no_profile_created.html');
				}
				break;

			case 'login':
				global $wgScriptPath;
				//TODO: redundancy--put in it's own case, put into the template
				$replace = array();
				if(!$this->mReqGetPage) {
					$this->mReqGetPage  = 'submit';
				}
				$replace['LOGIN_LINK'] = $wgScriptPath.'/index.php?title=Special:UserLogin&returnto=Special:TrueFans&returntoquery=page='.$this->mReqGetPage; // TODO: find a universal way to retrieve full url to interwiki link without this ridiculous manual url
				$replace['OPENID_LOGIN_LINK'] = $wgScriptPath.'/index.php?title=Special:OpenIDLogin&returnto=Special:TrueFans&returntoquery=page='.$this->mReqGetPage; // TODO: find a universal way to retrieve full url to interwiki link without this ridiculous manual url
				$this->loadTemplate('login.html', NULL, $replace);
				break;

			case 'submit':
				// Only logged in users can submit videos.
				if(!$wgUser->isLoggedIn()) {
					$this->handleViewPage('login');					
					return;
				} else {
					
					// All form steps require $form and $templates objects
					$form = $template = NULL;
					
					switch($this->mFormStep) {
						case 'upload':
							// Precondition: You don't have a $this->mTfProfile in the database
							if($this->mTfProfile != NULL) {	
								$this->mFormStep = 'edit';
								$this->handleViewPage('submit');
								return;
							} 

							//TODO: load this through the templating system
							global $wgScriptPath, $wgOut;
							$wgOut->addScriptFile($wgScriptPath.'/extensions/TrueFans/youtubeUploader.js');

							$template = 'upload_video.html';
							$form = new TrueFanForm($this, 'upload');
							$form->setFieldAttr('Name', 'default', $wgUser->getRealName());
							//$form->setFieldAttr('Name', 'help', 'Your name was added from your wiki profile. You may edit it.'); // TODO: style this or change html structure
							$form->setFieldAttr('Email', 'default', $wgUser->getEmail());					

							break;

						case 'write':
							// Precondition: User exists in db
							$template = 'write_message.html';
							$form = new TrueFanForm($this, 'write');
							break;

						case 'share':
							// Precondition: User exists in db
							global $wgScriptPath, $wgOut;
							//$wgOut->addScriptFile($wgScriptPath.'/extensions/TrueFans/facebook.js');
							$wgOut->addExtensionStyle($wgScriptPath.'/extensions/TrueFans/lib/tdfriendselector.css');
							$template = 'share_with_friends.html';
							$form = new TrueFanForm($this, 'share');
							break;
						
						case 'edit':
							// Precondition: User exists in db
							// Users are bounced to this form explicity or get sent here by 'upload' when profile exists
							$template = 'edit.html';
							$form = new TrueFanForm($this, 'edit');
							// We're using htmlspecialchars_decode because db entries htmlspecialchars encoded. 
							// This allows us to sanely round trip editing so that apostrophes don't matastisize in textboxes.
							$form->setFieldAttr('Name', 'default', htmlspecialchars_decode($this->mTfProfile[TF_NAME], ENT_QUOTES));
							$form->setFieldAttr('VideoId', 'default', $this->mTfProfile[TF_VIDEO_ID]);									
							$form->setFieldAttr('VideoMessage', 'default', htmlspecialchars_decode($this->mTfProfile[TF_VIDEO_MESSAGE], ENT_QUOTES));
							break;

						default:
							tfDebug('Invalid form step reached within submit page switch statement.');
							break;
					}
					
					$profile = NULL;
					if($this->mTfProfile) {
						$profile = $this->mTfProfile;
					}
					$replace = array();
					$replace['FORM'] = $form->writeHandledForm();
					$this->loadTemplate($template, $profile, $replace);
				}
				break;

			default:
				// TODO: Consider - Should we display an error template?
				tfDebug('Unknown GET request: '.$request);
				break;

		}
	}

	/**
	 * This function replaces {{TAGS}} with the $contentsof['TAGS'] = 'inAnAssociativeArray'; 
	 * @param $strContents The target string
	 * @param $replaceList The associative array with tags and replacements
	 * @return String with things replaced
	 */
	function replaceTemplateTags($strContents, $replaceList)
	{
		$patterns = array();
		$replacements = array();
		foreach($replaceList as $templateTag => $replacement) {
			$patterns[] = '/\{\{'.$templateTag.'\}\}/';	
			$replacements[] = $replacement;
		}
		return preg_replace($patterns, $replacements, $strContents); 
	}

	/**
	 * Loads template for a page and outputs content as HTML.
	 * @param String $path A path relative to *template* directory.
	 * @param Array $profile An array representing a True Fan profile to use in tag replacement.
	 * @param Array $extraReplace An array of custom tags and their replacements.
	 * @return String The link.
	 */
	function loadTemplate($path, $profile=NULL, $extraReplace=NULL)
	{
		global $wgOut, $wgScriptPath;

		// These are default replacement tags for templates
		$templateStr = array();
		$templateStr['PATH'] = $wgScriptPath.'/extensions/TrueFans/';
		$templateStr['BASE_URL'] = $this->getTitle()->getLocalUrl();
		$templateStr['ERROR_MESSAGE'] = $this->mErrorMessage;
		$templateStr['STATUS_MESSAGE'] = $this->mStatusMessage;
		$templateStr['USER_VIDEO_LINK'] = $this->getUserViewProfileLink();

		if($profile != NULL) {
			$templateStr['USER_NAME'] = $profile[TF_NAME];
			$templateStr['USER_MESSAGE'] = $profile[TF_VIDEO_MESSAGE];
			$templateStr['USER_VIDEO_ID'] = $profile[TF_VIDEO_ID];
		}

		if($extraReplace != NULL) {
			$templateStr = $templateStr + $extraReplace;
		}
			
		// Below will load the file as php allowing us to do fun php stuff like il8n and conditional buttons
		$full_path = dirname(__FILE__).'/templates/'.$path;
    	if (is_file($full_path)) {
        	ob_start();
        	include $full_path;
        	$templateContents = ob_get_clean();
		} else {
			tfDebug('Could not find file at '.$full_path.'<br />'); // Throw an exception?
		}

		$preparedHtml = $this->replaceTemplateTags($templateContents, $templateStr);
		$wgOut->addHTML($preparedHtml);
	}

	/****** Utility Functions *******/

	/**
	 * Returns foreign Id for TrueFansDb
	 * @return String foreign_id
	 */
	public function getMwId()
	{
		global $wgUser;
		return 'mw:'.$wgUser->getId();
	}

	/**
	 * Returns link to view profile page for logged in user.
	 * @return String The link.
	 */
	function getUserViewProfileLink() 
	{
		//TODO: test not logged in case
		return $this->getTitle()->getFullUrl()."?page=view&id={$this->mTfProfile[TF_ID]}"; 
	}
}

/**
 * Class to manage the various forms that interact with the database.
 * Basically this class is a manager of MediaWiki HTMLForm[s] objects
 * and their descriptors. This allows us to assemble the descriptor
 * over multiple calls and build the form at the same time we show it.
 */
class TrueFanForm
{
	protected $mDescriptor;
	protected $mForm;
	protected $mPage;
	protected $mType;	
	protected $mPreText;
	protected $mFormBuilt;
	protected $mFormLoaded;

	/**
	 * Builds TrueFanForm by filling descriptor and initialize state variables.
	 * @param SpecialPage $page MW SpecialPage object that contains title and 
	 * methods used in post callback function.
	 * @param String $type the type of form to create
	 */
	public function __construct($page, $type='video')
	{
		$this->mType = $type;
		$this->initializeDescriptor();
		$this->mPage = $page;
		$this->mPreText = '';
		$this->mFormBuilt = false;
		$this->mFormLoaded = false;
	}

	/**
	 * This function builds the form from descriptor and sets necessary member variables.
	 */
	private function build()
	{
		if(!$this->mFormBuilt) {
			// Reminder: 2nd param in constructor creates messagePrefix which is used to 
			// name the fieldset. (text of which is .i18n file)
			
			$this->mForm = new SexyForm($this->mDescriptor, $this->mPage->getContext(), "trueFanForm"); 
			$this->mForm->setSubmitText(wfMsg("trueFanSubmitText-".$this->mType)); 
			$this->mForm->setSubmitName('submit');
			$this->mForm->setSubmitCallback(array($this, 'formCallback'));
			$this->mForm->setId("trueFanForm");
			$this->mForm->addPreText($this->mPreText);
			$this->mFormBuilt = true;
		}
	}

	/**
	 * Wraps HTMLForm function which builds unbuilt form, analyzes requests, and outputs html or submits form	
	 */
	public function loadHandledForm()
	{
		$this->build();
		return $this->mForm->loadForm();
	}

	/**
	 * Builds and loads unbuilt form and outputs html as a string.
	 * This function is useful to avoid interference of posted fields from
	 * other forms that may trigger a trySubmit from the HTMLForm::show()
	 * function.
	 * @return String An HTML string for writing the form
	 */
	public function writeHandledForm()
	{
		// You must load a form to have the default fields filled in.
		// This is down automatically for the ::show() function but
		// must be done manually when using ::displayForm()
		$this->load(); 
		// Anything other than false will be printed as an error by HTMLForm::displayForm
		return $this->mForm->displayForm(false); 
	}

	/**
	 * Wraps HTMLForm functoin; loads data into form. 
	 */
	private function load() 
	{
		if(!$this->mFormLoaded) {
			$this->build();
			$this->mForm->loadData();
			$this->mFormLoaded = true;
		}
	}
	
	/**
	 * Changes attribute of a field in the descriptor of
	 * an *unbuilt* form. Once built, this won't do anything
	 * @param String $field The field to alter
	 * @param String $attr The attribute to alter
	 * @param Mixed $value The desired value of the attribute
	 */
	public function setFieldAttr($field, $attr, $value)
	{
		if(!$this->mFormBuilt) {
			$this->mDescriptor[$field][$attr] = $value;
		} else {
			tfDebug("Can't add $attr to a built form.");
		}
	}
	
	/**
	 * This is a callback function that handles post requests for TrueFanForm(s).
	 * This method makes extensive use of $mPage member variable to access database
	 * and add html feedback to the special page.
	 * @param Array $formFields The data passed into form from HTMLForm class.
	 * @return Mixed Returns true on success and a String message on an error.
	 * The error message is then output via the template via a member variable on the page
	 * Also the page will manually clear the form on errors and load from the database instead
	 */
	public function formCallback($formFields)
	{ 
		switch($formFields['Page']) {
			case 'upload':
				//TODO: Guarantee that we're XSS safe and that we can round trip text with special characters
				if($this->mPage->mDb->addUser($this->mPage->getMwId(), $formFields['Name'], $formFields['Email'], $formFields['VideoId'])) { 
					$this->mPage->mFormStep = 'write';
					return true;
				} else {
					$this->mPage->mFormStep = 'upload';
					if($formFields['VideoId'] == '') {
						return 'You did not upload a video or paste a video url.';
					} elseif(!$this->mPage->mDb->extractVideoId($formFields['VideoId'])) {
						return 'Unable to extract video id from URL.';
					} 
					return 'Unable to add request to DB.';
				}
				break;

			case 'write':
				if($this->mPage->mDb->updateVideoMessage($this->mPage->mTfProfile[TF_ID], $formFields['VideoMessage'])) { 
					$this->mPage->mFormStep = 'share';
				} else {
					// With the updateVideoMessage function configured to return true on a none update this else should never be tripped
					$this->mPage->mFormStep = 'write';
					return 'Unable to add invitation.';
				}
				return true;
				break;

			case 'share':
				global $wgOut;
					
				$this->mPage->mFormStep = 'share';
			
				//TODO: Create more meaningful error returns.
				if($formFields['EmailList'] && $formFields['SendEmails']) {
					$emailArray = explode(',', $formFields['EmailList']);
					$templateMessage = $formFields['FriendMessage'];
					$link = $this->mPage->getUserViewProfileLink();
					// \n is temporary until we build html email message
					$templateMessage .= '<br /><br /><a href="'.$link.'">'.$link.'</a>';

					// initialize error collector variable
					$errors = NULL;
					foreach($emailArray as $friendAddress) {
						list($name, $address) = explode(':',$friendAddress);
						$address = str_replace(array('<','>'),'',$address);
						$currentMessage = 'Hey '.$name.',<br /><br />'.$templateMessage;

						// This code actually sends the emails
						if(Sanitizer::validateEmail($address)) {
							global $wgPasswordSender, $wgSitename;
							$friendAddress = str_replace(':',' ',$friendAddress);

							$sendTo = new MailAddress($address, $name);
							//TODO: Check with Elifarley that there is a valid reply email for this project
							// any in domain email can be used fakeemail, truefans, just need to make sure other side is ready to receive mails...
							$from = new MailAddress('truefanstories@opensourceecology.org', $wgSitename);
							tfDebug($wgPasswordSender);
							$replyto = new MailAddress($this->mPage->mTfProfile[TF_EMAIL]);
							$subject = 'Open Source Ecology';
							$contentType = 'text/html'; // TODO: Change when we switch to html formatted emails
							$resultStatus = UserMailer::send($sendTo, $from, $subject, $currentMessage, $replyto, $contentType);
							if($resultStatus->isGood() !== true) {
								$errors .= $resultStatus->getWikiText().'\n';
							} 
						} elseif(!Sanitizer::validateEmail($address)) {
							// Consider: could create a warning system that allows submit to finish but reports back to user
							$errors .= 'Invalid email: '.$address.'\n';
						}
						$friendAddress = str_replace('<','&lt',$friendAddress);
						$friendAddress = str_replace('>','&gt',$friendAddress);
						$wgOut->addHtml($friendAddress.'<br />');
						$wgOut->addHtml(str_replace('\n','<br />',$currentMessage).'<br /><br />');
					} 
					if($errors) {
						tfDebug($errors);
						$errors = str_replace('\n','<br />',$errors);
						return $errors;
					}
		
				}
				return true;
				break;

			case 'edit':
				// Consider - We could have a simpler database class with a single update function and
				// we would reduce database requests. However I think the current code is better in terms 
				// of usability because the user is told exactly why their submission wasn't accepted.

				$this->mPage->mEditRedirect = 'myprofile';
				
				// HTMLForm is too dull to understand this...no other way of checking if a submit button was actually submitted
				if(isset($_POST['wpResendMessages'])) {
					$this->mPage->mEditRedirect = 'submit';
					$this->mPage->mFormStep = 'share';
				} elseif(isset($_POST['wpDeleteProfile'])) {
					if(!$this->mPage->mDb->deleteUser($this->mPage->mTfProfile[TF_ID])) {
						return 'Failed to delete profile.';
					} else {
						$this->mPage->mStatusMessage .= 'Your profile has been deleted. ';
						$this->mPage->mEditRedirect = 'welcome';
					}			
				} elseif(isset($_POST['wpCancel'])) {
					$this->mPage->mEditRedirect = 'myprofile'; // TODO: redund...
				} else {
					if($this->mPage->mTfProfile[TF_NAME] != htmlspecialchars($formFields['Name'], ENT_QUOTES)) {
						if(!$this->mPage->mDb->updateName($this->mPage->mTfProfile[TF_ID], $formFields['Name'])) {
							return 'Unable to update your name.';
						} else {
							$this->mPage->mStatusMessage .= 'Updated your name. ';
						}
					}
	
					if($this->mPage->mTfProfile[TF_VIDEO_ID] != $this->mPage->mDb->extractVideoId($formFields['VideoId'])) {
						if(!$this->mPage->mDb->updateVideoId($this->mPage->mTfProfile[TF_ID], $formFields['VideoId'], true)) {
							return 'Unable to update your video.'; 
						} else {
							$this->mPage->mStatusMessage .= 'Updated video id. ';
						}
					}
					
					if($this->mPage->mTfProfile[TF_VIDEO_MESSAGE] != htmlspecialchars($formFields['VideoMessage'], ENT_QUOTES)) {
						if(!$this->mPage->mDb->updateVideoMessage($this->mPage->mTfProfile[TF_ID], $formFields['VideoMessage'])) {
							return 'Unable to update your video message.';
						} else {
							$this->mPage->mStatusMessage .= 'Updated video message. ';
						}
					}
				}
				// Reload the profile
				$this->mPage->mTfProfile = $this->mPage->mDb->getUser($this->mPage->mTfProfile[TF_ID]); 
				$this->clearRequests();
				return true;
				break;

		}

		// Anything that falls through to here is likely a curl request or some other tomfoolery
		tfDebug('Unknown request posted: '.$formFields['Page']);
		return false;
	}

	/**
	 * Clears requests for a loaded form so that a newly built form can
	 * load without interference from fields with the same name.
	 */
	public function clearRequests()
	{
		global $wgRequest;
		// Make sure we've filled our forms fields so we can use them to empty $wgRequest
		$this->build(); 

		foreach($this->mForm->mFieldData as $field => $value) {
			$wgRequest->setVal('wp'.$field, NULL);
		}
	}

	/**
	 * Fills the mDescriptor with desired form according to contents
	 * of mType (passed in and set immediately upon object construction).
	 */
	private function initializeDescriptor()
	{
		// TODO: Internationalize all labels 
		switch($this->mType) {
		case 'upload':
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
				),
				'Name' => array(
					'class' => 'HTMLSexyTextField',
					'section' => $this->mType,
					'id' => 'ose-truefan-name',
					'label' => 'Name',
					'size' => 20,
				),
				'VideoId' => array(
					'class' => 'HTMLSexyTextField',
					'section' => $this->mType,
					'id' => 'ose-truefan-url',
					'label' => 'Video Url',
					'size' => 20,
				),
				'Email' => array(
					'class' => 'HTMLReturnableHiddenField',
					'default' => 'Invalid Email', 
				),
			);
			break;
		case 'write':
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
				),
				'VideoMessage' => array(
					'class' => 'HTMLSexyTextArea',
					'section' => $this->mType,
					'id' => 'ose-truefan-message',
					'label' => 'Video Message',
					'rows' => 5,
				),
			);
			break;

		case 'share':
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
				),
				'ContactDisplay' => array(
					'type' => 'info',
					'default' => '',
					'section' => 'contact-area',
				),
				'EmailInput' => array(
					'class' => 'HTMLTextArray',
					'section' => 'email',
					'id' => 'ose-truefan-email-input',
					'label' => 'Email',
					'size' => 20,
				),
				'SendEmails' => array(
					'class' => 'HTMLSexyCheckField',
					'section' => 'email',
					'id' => 'ose-truefan-email-check',
					'label' => 'Send Email Messages',
				),
				'FriendMessage' => array(
					'class' => 'HTMLSexyTextArea',
					'section' => $this->mType,
					'id' => 'ose-truefan-friends-message',
					'label' => 'Contact Message',
					'rows' => 5,
				),
				'EmailList' => array(
					'class' => 'HTMLReturnableHiddenField',
					'default' => '', 
				),
			);
			break;

		case 'edit':
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
				),
				'Name' => array(
					'class' => 'HTMLSexyTextField',
					'section' => 'upload',
					'id' => 'ose-truefan-name',
					'label' => 'Name',
					'size' => 20,
				),
				'VideoId' => array(
					'class' => 'HTMLSexyTextField',
					'section' => 'upload',
					'id' => 'ose-truefan-url',
					'label' => 'Video Id',
					'size' => 20,
				),
				'VideoMessage' => array(
					'class' => 'HTMLSexyTextArea',
					'section' => $this->mType,
					'id' => 'ose-truefan-message',
					'label' => 'Video Message',
					'rows' => 5,
				),
				'DeleteProfile' => array(
					'type' => 'submit',
					'section' => $this->mType,
					'id' => 'ose-truefan-delete-submit',
					'default' => 'Delete Profile',
				),
				'ResendMessages' => array(
					'type' => 'submit',
					'section' => $this->mType,
					'id' => 'ose-truefan-resend-submit',
					'default' => 'Send Messages',
				),
				'Cancel' => array(
					'type' => 'submit',
					'section' => $this->mType,
					'id' => 'ose-truefan-resend-submit',
					'default' => 'Cancel',
				),
			);
			break;

		// Basically this is here to prevent the edge case where someone messes with the form
		// and changes the Page field to some nonesense--this prevents error messages from
		// being coughed up and allows us to log such weird behavior.
		default:	
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
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
	wfDebugLog( 'TrueFans', $str);
}
