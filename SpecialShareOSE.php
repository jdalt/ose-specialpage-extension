<?php
/**
 * SpecialShareOSE.php -- the html entry point for displaying and gluing pages that manage TrueFansDb.
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
 * @file SpecialShareOSE.php
 * @author Jacob Dalton <jacobrdalton@gmail.com>
 * @ingroup Extensions
*/

require_once('class.TrueFansDb.php');
require_once('SexyForm.php');
 
class SpecialShareOSE extends SpecialPage {

	public $mDb;
	public $mTfProfile;
	public $mFormStep;
	public $mErrorMessage;
	//TODO: get rid of post message, templating all the way baby
	protected $mPostMessage;
	 
	/**
	 * Load requests and build TrueFansDb
	 * CONSIDER: Can I please the unit test faeries and pass in the database?
	 */
	public function __construct() {
		global $wgUser;
		tfDebug('***  Constructing SpecialShareOSE  ***');
		
		parent::__construct( 'ShareOSE');

		$this->loadRequest();
		$this->mPostMessage = $this->mErrorMessage = '';
		$this->mFormStep = 'upload';
		$this->mDb = new TrueFansDb(); // Unit Test Faerie: *Glares* "Beware the new operator!"
		if($wgUser->isLoggedIn()) {
			$this->mTfProfile = $this->mDb->getUserByForeignId($this->getMwId()); // Unit Testing Kosher?
		}
	}

	/** Misc variables **/
	protected $mReqGetPage;
	protected $mReqPostPage; 
	protected $mPostedForm;
	protected $mReqId;

	/**
	 * Initialize instance variables from request.
	 */
	protected function loadRequest() {
		global $wgRequest;

		// This request is loaded before the form and straight off of the global $wgRequest variable
		// because it will determine which posted form is to be built. All other posted variables
		// are loaded via forms.
		$this->mReqPostPage = $wgRequest->getText('Page');
		if($wgRequest->wasPosted()) {
			$this->mPostedForm = new TrueFanForm($this, $this->mReqPostPage);
		}
		
		$this->mReqGetPage = $wgRequest->getText('page');
		$this->mReqId = $wgRequest->getText('id');
	}

	/**
	 * Special page entry point
	 * @param $par 
	 */
	public function execute( $par ) {
		global $wgUser, $wgOut, $wgRequest, $wgScriptPath;

		$this->setHeaders();
		$this->outputHeader();
		$wgOut->addExtensionStyle($wgScriptPath.'/extensions/ShareOSE/style.css');
		$wgOut->addScriptFile($wgScriptPath.'/extensions/ShareOSE/dynamic.js');
		
		// Request logic. POST > GET. Empty request = 'welcome' GET request. 
		if($this->mReqPostPage) {
			$this->handlePostRequest($this->mReqPostPage);
		} elseif($this->mReqGetPage) {
			$this->handleViewPage($this->mReqGetPage);
		} else {
			// Empty requests, go to the welcome page
			$this->handleViewPage('welcome');
		}

		// Global HTML added to every page
		$url = $this->getTitle()->getLocalUrl();
		$wgOut->addHTML('<ul id="special-links" class="inline-links"><li><a href="'.$url.'?page=welcome">True Fans</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=myprofile">Your Video</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=submit">Submit a Video</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=subscribe">Become a True Fan</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=viewall">View All Submissions</a></li></ul>');
		$wgOut->addHTML('<h6>*Development Links*</h6>');
	}

	/**
	 * This function handles posted forms. 
	 * Adds valid input to the database and ouputs error messages. After completing 
	 * duties hands control off to handleViewPage.
	 * @param String $postRequest The Page field in each form refers to one of these
	 * handlers which handles all the other fields and puts them where they need to go.
	 */
	protected function handlePostRequest($postRequest)
	{
		// Precondition: Valid mPostedForm of type matching $postRequest
		global $wgUser, $wgOut;

		// You must be logged in to submit data. Anything else is nonsense.
		if(!$wgUser->isLoggedIn()) {
			tfDebug("!Attempt to post without being logged in!");	
			$wgOut->addHTML('Please login.');
			return;
		}
		
		$formReturn = $this->mPostedForm->loadHandledForm();
		if($formReturn === true) { // checks edit token and fires trySubmit --> will not display form if submit is successful 
			$wgOut->addHTML($this->mPostMessage);
			if($postRequest != 'share') { 
				$this->mTfProfile = $this->mDb->getUserByForeignId($this->getMwId()); // update the profile that the viewer will use.:w
				$this->handleViewPage('submit'); // throw it back viewing pages
			} else {
				$this->handleViewPage('finish');
			}
		} else {
			// TODO: insert this into the template
			$this->mErrorMessage = $formReturn;
			$this->handleViewPage('submit');
		}
	}

	/**
	 * The main html display function.
	 * Outputs page specific html according to page request or
	 * artificial page request sent from handlePostRequest().
	 * Basically this injects nearly all page specific view html
	 * except small messages that come back from post and global
	 * messages stamped on every page.
	 * @param String $getRequest page requested from GET, 
	 * handlePostRequest, or generated by execute request logic. 
	 */
	public function handleViewPage($getRequest)
	{
		global $wgUser, $wgOut;
		
		// Request specific HTML dependent upon the request
		switch($getRequest) {
			case 'welcome':
				$this->loadTemplate('templates/welcome.html');
				break;
			case 'view':
				$profile = $this->mDb->getUser($this->mReqId);
				$this->loadTemplate('templates/view.html', $profile);
				break;
			
			case 'subscribe':
				$this->loadTemplate('templates/subscribe.html');
				break;
			
			case 'myprofile':
				// TODO: Add extra information like before - email and contacts sent to...?
				if(!$wgUser->isLoggedIn()) {
					//TODO: redundancy--put in it's own case, put into the template
					$replace = array();
					$replace['LOGIN_LINK'] = '/w/index.php?title=Special:UserLogin&returnto=Special:ShareOSE'; // TODO: find a universal way to retrieve full url to interwiki link without this ridiculous manual url
					$this->loadTemplate('templates/login.html', NULL, NULL, $replace);
				} else {
					$this->loadTemplate('templates/view.html', $this->mTfProfile);
				}
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

			case 'finish':
				$this->loadTemplate('templates/finish.html', $this->mTfProfile);
				break;

			case 'submit':
				// Only logged in users can submit videos. This ensures valid emails and no spam.
				if(!$wgUser->isLoggedIn()) {
					//TODO: redundancy--put in it's own case, put into the template
					$replace = array();
					$replace['LOGIN_LINK'] = '/w/index.php?title=Special:UserLogin&returnto=Special:ShareOSE'; // TODO: find a universal way to retrieve full url to interwiki link without this ridiculous manual url
					$this->loadTemplate('templates/login.html', NULL, NULL, $replace);
				} else {
					
					$form = $template = NULL;
					
					switch($this->mFormStep) {
						case 'upload':
							// Precondition: You don't have a $this->mTfProfile in TrueFansDb
							if($this->mTfProfile != NULL) {	
								$this->mFormStep = 'edit';
								$this->handleViewPage('submit');
								return;
							} 

							global $wgScriptPath;
							$wgOut->addScriptFile($wgScriptPath.'/extensions/ShareOSE/youtubeUploader.js');
							$template = 'templates/upload_video.html';
	
							$form = new TrueFanForm($this, 'upload');
							$form->setFieldAttr('Name', 'default', $wgUser->getRealName());
							// TODO: Internationalize
							$form->setFieldAttr('Name', 'help', 'Your name was added from your wiki profile. You may edit it.');
							$form->setFieldAttr('Email', 'default', $wgUser->getEmail());					

							break;

						case 'write':
						 	// Precondition: User exists with video_id but no video_message
							// No video_message: create an 'invite' form 
							
							$template = 'templates/write_message.html';
	
							$form = new TrueFanForm($this, 'write');

							break;

						case 'share':
							$template = 'templates/share_with_friends.html';
	
							$form = new TrueFanForm($this, 'share');

							break;
						
						case 'edit':
							// Precondition: finished profile. Allow the user to edit their information.
							
							$template = 'templates/edit.html';
	
							$form = new TrueFanForm($this, 'edit');
							$form->addPreMessage('<p>Edit your message or email invitation.</p>');
							$form->setFieldAttr('Name', 'default', $this->mTfProfile['name']);
							$form->setFieldAttr('Email', 'default', $this->mTfProfile['email']);					
							$form->setFieldAttr('VideoId', 'default', $this->mTfProfile['video_id']);									
							$form->setFieldAttr('Message', 'default', $this->mTfProfile['video_message']);
							$form->setFieldAttr('EmailInput', 'default', $this->mTfProfile['email_invite_list']);
							break;

						default:
							echo 'wtf default reached';
							break;
					}
					
					$formStr = $form->displayHandledForm();
					$profile = NULL;
					if($this->mTfProfile) {
						$profile = $this->mTfProfile;
					}
					$this->loadTemplate($template, $profile, $formStr);
				}
				break;

			default:
				$wgOut->addHTML('<p>Unknown get request.</p>');
				break;

		}
	}

	/****** Utility Functions *******/

	/**
	 * Accumulates messages from post callback of TrueFansForm that are displayed at the top of the page alone or before next form. 
	 */
	public function addPostMessage($str)
	{	
		//TODO: REMOVE in favor of template based solution
		$this->mPostMessage .= $str;
	}

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
		return $this->getTitle()->getFullUrl()."?page=view&id={$this->mTfProfile['id']}"; 
	}

	/**
	 * 
	 * @return String with things replaced
	 */
	function replaceTemplateTags($strContents, $replaceList)
	{
		$patterns = array();
		$replacements = array();

		$replacements = array();
		foreach($replaceList as $templateTag => $replacement) {
			$patterns[] = '/\{\{'.$templateTag.'\}\}/';	
			$replacements[] = $replacement;
		}

		return preg_replace($patterns, $replacements, $strContents); 
	}


	/**
	 * Loads template for a page
	 * @return String The link.
	 */
	function loadTemplate($path, $profile=NULL, $form=NULL, $extraReplace=NULL)
	{
		//TODO: Extra consideration about utility of $extraReplace -- do I really need it? If so get rid of $form...template should be for things that repeat on muttiple pages and make formatting easier
		global $wgOut, $wgScriptPath;

		$templateStr = array();
		$templateStr['PATH'] = $wgScriptPath.'/extensions/ShareOSE/';
		$templateStr['ERROR_MESSAGE'] = $this->mErrorMessage;
		$templateStr['USER_VIDEO_LINK'] = $this->getUserViewProfileLink();

		if($profile != NULL) {
			$templateStr['USER_NAME'] = $profile['name'];
			$templateStr['USER_MESSAGE'] = $profile['video_message'];
			$templateStr['USER_VIDEO_ID'] = $profile['video_id'];
		}

		if($form != NULL) {
			$templateStr['FORM'] = $form;
		}

		if($extraReplace != NULL) {
			$templateStr = $templateStr + $extraReplace;
		}

		$templateContents = file_get_contents($path, FILE_USE_INCLUDE_PATH);
		$preparedHtml = $this->replaceTemplateTags($templateContents, $templateStr);
			
		// Below will load the code as php allowing us to do fun php stuff like il8n
    	/*if (is_file($path)) {
        ob_start();
        include $path;
        $str = ob_get_clean();
		}*/
		
		$wgOut->addHTML($preparedHtml);
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
			$this->mForm = new SexyForm($this->mDescriptor, "trueFanForm"); 
			$this->mForm->setSubmitText(wfMsg("trueFanSubmitText-".$this->mType)); 
			$this->mForm->setSubmitName('submit');
			$this->mForm->setSubmitCallback(array($this, 'formCallback'));
			$this->mForm->setId("trueFanForm");
			$this->mForm->setTitle($this->mPage->getTitle());
			$this->mForm->addPreText($this->mPreText);
			$this->mFormBuilt = true;
			tfDebug("Built: {$this->mType}");
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
	 * Builds and loads unbuilt form and outputs html.
	 * This function is useful to avoid interference of posted fields from
	 * other forms that may trigger a trySubmit from the HTMLForm::show()
	 * function.
	 */
	public function displayHandledForm()
	{
		// You must load a form to have the default fields filled in.
		// This is down automatically for the ::show() function but
		// must be done manually when using ::displayForm()
		$this->load(); 
		// Anything other than false will be printed as an error message.
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
			tfDebug("Loaded {$this->mType}");
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
	 * Wraps HTMLForm function to add html before form 
	 */
	public function addPreMessage($str)
	{
		$this->mPreText .= $str;
	}
	
	/**
	 * This is a callback function that handles post requests for TrueFanForm(s).
	 * This method makes extensive use of $mPage member variable to access database
	 * and add html feedback to the special page.
	 * @param Array $formFields The data passed into form from HTMLForm class.
	 * @return Mixed Returns true on success and a String message on an error.
	 * The error message is then output by HTMLForm::displayForm() method.
	 */
	public function formCallback($formFields)
	{ 
		switch($formFields['Page']) {
			case 'upload':
				//TODO: Guarantee that we're XSS safe and that we can round trip text with special characters
				if($this->mPage->mDb->addUser($this->mPage->getMwId(), $formFields['Name'], $formFields['Email'], $formFields['VideoId'])) { 
					echo 'successful submission';
					$this->mPage->mFormStep = 'write';
					return true;
				} else {
					echo 'bad sumission';
					$this->mPage->mFormStep = 'upload';
					if(!$this->mPage->mDb->extractVideoId($formFields['VideoId'])) {
						return 'Unable to extract video id from URL.';
					} 
					$this->mPage->mFormStep = 'upload';
					return 'Unable to add request to DB.';
				}
				break;

			case 'write':
				//if(($this->mPage->mTfProfile['video_message'] != $formFields['Message']) || ($this->mPage->mTfProfile['email_invite_list'] != $formFields['EmailInput'])) {
				if($this->mPage->mDb->updateVideoMessage($this->mPage->mTfProfile['id'], $formFields['Message'])) { 
					
					$this->mPage->mFormStep = 'share';
					return true;
				} else {
					$this->mPage->mFormStep = 'write';
					return 'Unable to add invitation.';
				}
				break;

			case 'share':
						
				global $wgOut;
						
				$wgOut->addHtml('<h4>Email Debug Output</h4>');
			
				if($formFields['EmailList']) {
					$emailArray = explode(',', $formFields['EmailList']);
					$templateMessage = $formFields['FriendMessage'];
					$replace = array();
					$link = $this->mPage->getUserViewProfileLink();
					$replace['EMAIL_VIDEO_LINK'] = '<a href="'.$link.'">'.$link.'</a>';
					foreach($emailArray as $friendAddress) {
						//FIXME: Potential XSS security flaw - non-escaped $formFields directly displayed, consider getting a return form db of escaped via htmlspecialchars
						
						list($name, $address) = explode(':',$friendAddress);
						$replace['FRIEND'] = $name;
						$currentMessage = $this->mPage->replaceTemplateTags($templateMessage, $replace); 

						if($formFields['SendEmails']) {
							$friendAddress = str_replace(':',' ',$friendAddress);
							$sendTo = new MailAddress($friendAddress);
							$from = new MailAddress($this->mPage->mTfProfile['email']);
							$subject = 'A Message From Open Source Ecology';
							$contentType = 'text/html';
							$result = UserMailer::send($sendTo, $from, $subject, $currentMessage, $from, $contentType);
							if($result != true) {
								//TODO: Make this smarter -- save all errors and then return them at the end
								return $result;
							} 
						}
						
						$friendAddress = str_replace('<','&lt',$friendAddress);
						$friendAddress = str_replace('>','&gt',$friendAddress);
						$wgOut->addHtml($friendAddress.'<br />');
						$wgOut->addHtml($currentMessage.'<br /><br />');
					} //TODO: delete
					$wgOut->addHtml($formFields['FriendMessage'].'<br/><br/><br/>');
				}

				//TODO: Handle emailes and incoming message, send emails with message
				return true;
				break;

			case 'edit':
				// TODO: Make name editable and report change to name.
				// Should there be a generalized update function mDm->update($id, $field, $value)?
				// Or should I just update the entire profile no matter what mDb->updateAll($fields)?
				//if($this->mPage->mTfProfile['name'] != $formFields['Name'])
				
				//$this->mPage->mRedirectRequest = 'Edit'; // edit routes back to edit

				if($this->mPage->mTfProfile['video_id'] != $this->mPage->mDb->extractVideoId($formFields['VideoId'])) {
					if(!$this->mPage->mDb->updateVideoId($this->mPage->mTfProfile['id'], $formFields['VideoId'], true)) {
						// Revert to the previous, valid, id.
						$this->mForm->mFieldData['VideoId'] = $this->mPage->mTfProfile['video_id'];
						return 'Unable to update your video.'; 
					} else {
						$this->mPage->addPostMessage("<p>Updated video id.</p>");
					}
				}
				
				if(($this->mPage->mTfProfile['video_message'] != $formFields['Message']) || ($this->mPage->mTfProfile['email_invite_list'] != $formFields['EmailInput'])) {
					if(!$this->mPage->mDb->updateStuff($this->mPage->mTfProfile['id'], $formFields['Message'], $formFields['EmailInput'])) {
						// Revert to the previous, valid, message and email list
						$this->mForm->mFieldData['Message'] = $this->mPage->mTfProfile['video_message'];
						$this->mForm->mFieldData['EmailInput'] = $this->mPage->mTfProfile['email_invite_list'];
						return 'Unable to update invitation.';
					} else {
						if($this->mPage->mTfProfile['video_message'] != $formFields['Message']) {
							$this->mPage->addPostMessage("<p>Updated video message.</p>");
						}
						if($this->mPage->mTfProfile['email_invite_list'] != $formFields['EmailInput']) {
							$this->mPage->addPostMessage("<p>Updated email invitations.</p>");
						}
					}
				}
				
				if($formFields['SendEmails']) {
					$emailArray = explode(',',$this->mPage->mTfProfile['email_invite_list']);
					foreach($emailArray as $friendAddress) {
						// TODO: Internationalize
						$sendTo = new MailAddress($friendAddress);
						$from = new MailAddress($this->mPage->mTfProfile['email']);
						$subject = 'A Message From Open Source Ecology';
						// TODO: consider putting the following into a function, it's used twice, if we change url scheme...more edits
						$link = $this->mPage->getUserViewProfileLink();
						$message = 	'<p>Hello from the interwebs. This is a message from Open Source Ecology. <strong>'
										.$this->mPage->mTfProfile['name'].'</strong> wanted to let you know that OSE is building '
										.'an open source post scarcity economy. '.$this->mPage->mTfProfile['name'].' even make a video for you: </p>'
										.'<a href="'.$link.'">'.$link.'</a>';
						$contentType = 'text/html';
						$result = UserMailer::send($sendTo, $from, $subject, $message, $from, $contentType);
						if($result != true) {
							return $result;
						}
						$this->mPage->addPostMessage("<p>Sent email to $friendAddress.</p>");
					}
				}

				// HTMLForm is too dull to understand this...no other way of checking if a submit button was actually submitted
				if(isset($_POST['DeleteProfile'])) {
					if($this->mPage->mDb->deleteUser($this->mPage->mTfProfile['id'])) {
					$this->mPage->addPostMessage('Your profile has been deleted.');
					} else {
						return 'Failed to delete profile.';
					}			
					//$this->mPage->mRedirectRequest = 'Upload_Video'; // edit routes back to edit
				}

				// !!	
				$this->clearRequests();
				return true;
				break;
		}
		return 'Unknown request.';
	}

	/**
	 * Clears requests for a loaded form so that a newly built form can
	 * load without interference from fields with the same name.
	 */
	public function clearRequests()
	{
		global $wgRequest;
		// Make sure we've filled our forms fields
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
					'section' => $this->mType,
				),
				'Name' => array(
					'class' => 'HTMLSexyTextField',
					'section' => $this->mType,
					'id' => 'ose-truefan-name',
					'label' => 'Name',
					'size' => 20,
				),
				'Email' => array(
					'class' => 'HTMLSexyTextField',
					'section' => $this->mType,
					'id' => 'ose-truefan-email',
					'label' => 'Email',
					'size' => 20,
					'readonly' => true,
				),
				'VideoId' => array(
					'class' => 'HTMLSexyTextField',
					'section' => $this->mType,
					'id' => 'ose-truefan-url',
					'label' => 'Video Url',
					'size' => 20,
				),
			);
			break;
		case 'write':
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
					'section' => $this->mType,
				),
				'Message' => array(
					'class' => 'HTMLSexyTextArea',
					'section' => $this->mType,
					'id' => 'ose-truefan-message',
					'label' => 'Message',
					'rows' => 5,
				),
			);
			break;

		case 'share':
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
					'section' => $this->mType,
				),
				'FriendMessage' => array(
					'class' => 'HTMLSexyTextArea',
					'section' => $this->mType,
					'id' => 'ose-truefan-friends-message',
					'label' => 'Message to Friends',
					'rows' => 5,
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
					'label' => 'Send Emails',
				),
				'TemplateButtons' => array(
					'type' => 'info',
					'default' => '',
					'section' => 'social-buttons',
				),
				'FacebookFriends' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
				),
				'EmailList' => array(
					'class' => 'HTMLReturnableHiddenField',
					'default' => $this->mType, 
				),
			);
			break;

		case 'edit':
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
					'section' => $this->mType,
				),
				'Name' => array(
					'class' => 'HTMLSexyTextField',
					'section' => $this->mType,
					'id' => 'ose-truefan-name',
					'label' => 'Name',
					'size' => 20,
					'readonly' => true,
				),
				'Email' => array(
					'class' => 'HTMLSexyTextField',
					'section' => $this->mType,
					'id' => 'ose-truefan-email',
					'label' => 'Email',
					'size' => 20,
					'readonly' => true,
				),
				'VideoId' => array(
					'class' => 'HTMLSexyTextField',
					'section' => $this->mType,
					'id' => 'ose-truefan-url',
					'label' => 'Video Id',
					'size' => 20,
				),
				'Message' => array(
					'class' => 'HTMLSexyTextArea',
					'section' => $this->mType,
					'id' => 'ose-truefan-message',
					'label' => 'Message',
					'rows' => 5,
					'cols' => 70,
				),
				'SendEmails' => array(
					'class' => 'HTMLSexyCheckField',
					'section' => $this->mType,
					'id' => 'ose-truefan-email-check',
					'label' => 'Send Emails',
				),
				'EmailInput' => array(
					'class' => 'HTMLTextArray',
					'section' => $this->mType,
					'id' => 'ose-truefan-email-input',
					'label' => 'Email',
					'size' => 70,

				),
				'DeleteProfile' => array(
					'type' => 'submit',
					'section' => $this->mType,
					'id' => 'ose-truefan-delete-submit',
					'default' => 'Delete Profile',
				),
			);
		}
	}
}

/**
 * Syntactic sugar. Outputs to local extension log.
 */
function tfDebug($str)
{
	wfDebugLog( 'ShareOSE', $str);
}
