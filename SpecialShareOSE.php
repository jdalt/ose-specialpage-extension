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
		$this->mPostMessage = '';
		$this->mDb = new TrueFansDb(); // Unit Test Faerie: *Glares* "Beware the new operator!"
		if($wgUser->isLoggedIn()) {
			$this->mTfProfile = $this->mDb->getUserByForeignId($this->getMwId()); // Unit Testing Kosher?
		}
	}

	/** Misc variables **/
	protected $mReqPostPage; 
	protected $mPostedForm;
	protected $mReqGetPage;
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
				if(!$wgUser->isLoggedIn()) {
					$wgOut->addHTML('<p>You are not logged in.</p>'); 
				} else {
					if(!$this->mTfProfile){
						$wgOut->addHTML('<p>Unable to find profile. You need to submit a video.</p>'); 
					} else {
						$wgOut->addHTML("<h3>{$this->mTfProfile['name']} </h3><h3>{$this->mTfProfile['email']}</h3>");
						$wgOut->addHTML("<iframe src='http://www.youtube.com/embed/".$this->mTfProfile['video_id']."'>No iframes.</iframe>");
						$wgOut->addHTML("<p>".$this->mTfProfile['video_message']."</p>");
						$wgOut->addHTML("<p><strong>Email List: </strong>".$this->mTfProfile['email_invite_list']."</p>");
					}
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

			case 'submit':
				// Only logged in users can submit videos. This ensures valid emails and no spam.
				if(!$wgUser->isLoggedIn()) {
					$wgOut->addHTML('<p>You must log in to submit a video.</p>');
				} else {

					$form = $template = NULL;

					// Intelligently decide the logged in user's needs based on DB contents
					if(!$this->mTfProfile) {
						// Precondition: You don't have a this->mTfProfile in TrueFansDb
				
						// This loads the youtubeUploader
						// !!! Terrible law of demeter violations here...need a better approach to templating
						global $wgScriptPath;
						$wgOut->addScriptFile($wgScriptPath.'/extensions/ShareOSE/youtubeUploader.js');
     					
						$template = 'templates/upload_video.html';

						//$wgOut->addHTML('<img class="gear" src="'.$wgScriptPath.'/extensions/ShareOSE/gear.png" /><div id="widget"></div> <div id="player"></div><div id="status-container"><span>Status: </span><span id="status"></span></div>');
						
						$form = new TrueFanForm($this, 'video');
						$form->addPreMessage('<p>Create a True Fan Profile and submit a video. </p>');
						$form->setFieldAttr('Name', 'default', $wgUser->getRealName());
						// TODO: Internationalize
						$form->setFieldAttr('Name', 'help', 'Your name was added from your wiki profile. You may edit it.');
						$form->setFieldAttr('Email', 'default', $wgUser->getEmail());					
					} elseif(!$this->mTfProfile['video_message']) {
					 	// Precondition: User exists with video_id but no message and email list
						// No video_message: create an 'invite' form 
						
						$template = 'templates/write_message.html';

						$form = new TrueFanForm($this, 'invite');
						$form->addPreMessage('<p>Add a message and invite friends to view your video.</p>');
					} else {
						// We have all necessary information, allow the user to edit their information
						// We need an 'edit' form
						
						$template = 'templates/share_with_friends.html';

						$form = new TrueFanForm($this, 'edit');
						$form->addPreMessage('<p>Edit your message or email invitation.</p>');
						$form->setFieldAttr('Name', 'default', $this->mTfProfile['name']);
						$form->setFieldAttr('Email', 'default', $this->mTfProfile['email']);					
						$form->setFieldAttr('VideoId', 'default', $this->mTfProfile['video_id']);									
						$form->setFieldAttr('Message', 'default', $this->mTfProfile['video_message']);
						$form->setFieldAttr('EmailInput', 'default', $this->mTfProfile['email_invite_list']);
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
		

		if($this->mPostedForm->show()) { // checks edit token and fires trySubmit --> will not display form if submit is successful 
			$wgOut->addHTML($this->mPostMessage);
			if($postRequest != 'invite') { 
				$this->mTfProfile = $this->mDb->getUserByForeignId($this->getMwId()); // update the profile that the viewer will use.:w
				$this->handleViewPage('submit'); // throw it back viewing pages
			} 
		} 
	}


	/****** Utility Functions *******/

	/**
	 * Accumulates messages from post callback of TrueFansForm that are displayed at the top of the page alone or before next form. 
	 */
	public function addPostMessage($str)
	{
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
		return $this->getTitle()->getFullUrl()."?page=view&id={$this->mTfProfile['id']}"; 
	}

	/**
	 * Loads template for a page
	 * @return String The link.
	 */
	function loadTemplate($path, $profile=NULL, $form=NULL)
	{
		global $wgOut, $wgScriptPath;
		
		$user_name = $user_message = $user_video_id = "Undefined";
		if($profile != NULL) {
			$user_name = $profile['name'];
			$user_message = $profile['video_message'];
			$user_video_id = $profile['video_id'];
		}
			
		
		$str = file_get_contents($path, FILE_USE_INCLUDE_PATH);

		// Below will load the code as php allowing us to do fun php stuff like il8n
    	/*if (is_file($path)) {
        ob_start();
        include $path;
        $str = ob_get_clean();
		}*/
		
		$patterns = array();
		$replacements = array();
		$patterns[0] = '/\{\{PATH\}\}/';
		$replacements[0] = $wgScriptPath.'/extensions/ShareOSE/';
		$patterns[1] = '/\{\{USER_NAME\}\}/';
		$replacements[1] = $user_name;
		$patterns[2] = '/\{\{USER_MESSAGE\}\}/';
		$replacements[2] = $user_message;
		$patterns[3] = '/\{\{USER_VIDEO_ID\}\}/';
		$replacements[3] = $user_video_id;
		$patterns[4] = '/\{\{FORM\}\}/';
		$replacements[4] = $form;

		//preg_match('/\{\{\w\}\}/g', $str, $matches)
		$str = preg_replace($patterns, $replacements, $str); 

		$wgOut->addHTML($str);
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
	public function show()
	{
		$this->build();
		return $this->mForm->show();
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
			case 'video':
				//FIXME: Potential XSS security flaw - non-escaped $formFields directly displayed, consider getting a return form db of escaped via htmlspecialchars
				$this->mPage->addPostMessage("<p>Received form from: <strong>".$formFields['Name']."</strong></p>");
				if($this->mPage->mDb->addUser($this->mPage->getMwId(), $formFields['Name'], $formFields['Email'], $formFields['VideoId'])) { 
					$this->mPage->addPostMessage("<p>Added request to DB.</p>");
					return true;
				} else {
					if(!$this->mPage->mDb->extractVideoId($formFields['VideoId'])) {
						return 'Unable to extract video id from URL.';
					} 
					return 'Unable to add request to DB.';
				}
				break;

			case 'invite':
				//if(($this->mPage->mTfProfile['vide[MaCo_message'] != $formFields['Message']) || ($this->mPage->mTfProfile['email_invite_list'] != $formFields['EmailInput'])) {
				if($this->mPage->mDb->updateInvitation($this->mPage->mTfProfile['id'], $formFields['Message'], $formFields['EmailInput'])) { 
					//FIXME: Potential XSS security flaw - non-escaped $formFields directly displayed, consider getting a return form db of escaped via htmlspecialchars
					$this->mPage->addPostMessage("<p>{$formFields['Message']}</p><ul>"); 
					$emails = explode(',', $formFields['EmailInput']);
					foreach($emails as $email) {
						$this->mPage->addPostMessage("<li>$email</li>");
					}
					$this->mPage->addPostMessage("</ul>");	
					$link = $this->mPage->getUserViewProfileLink();
					$this->mPage->addPostMessage("<span>View your video and message at this link: </span><p><a href='$link'>$link</a></p>");
					return true;
				} else {
					return 'Unable to add invitation.';
				}
				break;

			case 'edit':
				// TODO: Make name editable and report change to name.
				// Should there be a generalized update function mDm->update($id, $field, $value)?
				// Or should I just update the entire profile no matter what mDb->updateAll($fields)?
				//if($this->mPage->mTfProfile['name'] != $formFields['Name'])

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
					if(!$this->mPage->mDb->updateInvitation($this->mPage->mTfProfile['id'], $formFields['Message'], $formFields['EmailInput'])) {
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
		case 'video':
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
		case 'invite':
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
				'EmailInput' => array(
					'class' => 'HTMLTextArray',
					'section' => $this->mType,
					'id' => 'ose-truefan-email-input',
					'label' => 'Email',
					'size' => 20,
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
