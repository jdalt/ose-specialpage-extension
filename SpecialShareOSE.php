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
 
class SpecialShareOSE extends SpecialPage {

	protected $mTrueFanDb;
	 
	/**
	 * Load requests and build TrueFansDb
	 * TODO: ? Is there a way to pass in the database? Would be better for tests...
	 */
	public function __construct() {
		global $wgRequest;
		tfDebug('***  Constructing SpecialShareOSE  ***');
		parent::__construct( 'ShareOSE');

		$this->loadRequest();
		$this->mDb = new TrueFansDb(); // Unit Test Faerie: *Glares* "Beware the new operator!"
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
		
		$this->mReqName = $wgRequest->getText('wpName');
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
	 * @param $par ?? Perhaps these are parameters for a function in the parent class...
	 */
	public function execute( $par ) {
		global $wgUser, $wgOut, $wgRequest, $wgScriptPath;

		$this->setHeaders();
		$this->outputHeader();
		$wgOut->addExtensionStyle($wgScriptPath.'/extensions/ShareOSE/style.css');
		$wgOut->addScriptFile($wgScriptPath.'/extensions/ShareOSE/dynamic.js');
		
		$url = $this->getTitle()->getLocalUrl();
		
		// Request logic. POST > GET. Empty request = 'welcome' get request. 
		if($this->mReqPostPage) {
			$this->handlePostRequest($this->mReqPostPage);
		} elseif($this->mReqGetPage) {
			$this->handleViewPage($this->mReqGetPage);
		} else {
			// Empty requests, go to the welcome page
			$this->handleViewPage('welcome');
		}
		
		// Global HTML added to every page
		$wgOut->addHTML('<ul id="special-links"><li><a href="'.$url.'?page=welcome">True Fans</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=myprofile">Your Video</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=submit">Submit a Video</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=subscribe">Become a True Fan</a></li>');
		$wgOut->addHTML('<li><a href="'.$url.'?page=viewall">View All Submissions</a></li></ul>');
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
	protected function handleViewPage($getRequest)
	{
		global $wgUser, $wgOut;

		// Request specific HTML dependent upon the request
		switch($getRequest) {
			case 'welcome':
				$wgOut->addHTML('<p>This is the welcome page. It will be a marvelous gallery of true fans once I build it.</p>');
				break;
			case 'view':
				$profile = $this->mDb->getUser($this->mReqId);
				$wgOut->addHTML("<h3>{$profile['name']}</h3>");
				$wgOut->addHTML("<iframe src='http://www.youtube.com/embed/".$profile['video_id']."'>No iframes.</iframe>");
				$wgOut->addHTML("<p>".$profile['video_message']."</p>");
				break;
			
			case 'subscribe':
				$wgOut->addHTML('<p>True Fans: As a project working for the common good, we rely on a growing group of'.
									 'crowd-funders called True Fans to keep our mission alive. The True Fans program is a'.
									 'monthly donation with options for $10, $20, $30, $50, and $100 per month for 24 months. </p>');
				$wgOut->addHTML($this->getPayPalButton());	
				break;
			
			case 'myprofile':
				// !! Not checking for email...check not logged in result
				if(!$wgUser->isLoggedIn()) {
					$wgOut->addHTML('<p>You are not logged in.</p>'); 
				} else {
					$profile = $this->mDb->getUserByEmail($wgUser->getEmail());
					if(!$profile){
						$wgOut->addHTML('<p>Unable to find profile. You need to submit a video.</p>'); 
					} else {
						$wgOut->addHTML("<h3>{$profile['name']} </h3><h3>{$profile['email']}</h3>");
						$wgOut->addHTML("<iframe src='http://www.youtube.com/embed/".$profile['video_id']."'>No iframes.</iframe>");
						$wgOut->addHTML("<p>".$profile['video_message']."</p>");
						$wgOut->addHTML("<p><strong>Email List: </strong>".$profile['email_invite_list']."</p>");
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
					// Intelligently decide the logged in user's needs based on DB contents
					$userEmail = $wgUser->getEmail();
					$profile = $this->mDb->getUserByEmail($userEmail);

					$form = NULL;

					if(!$profile['video_id']) {
						// No video_id: create a 'video' form
						$userName = '';
						if(!$profile) {
							// The user doesn't exist in the true fans database
							$wgOut->addHTML('<p>Create a True Fan profile and submit a video. </p>');
							$userName = $wgUser->getRealName();
						} else {
							// The user exists but hasn't submitted a video_id
							$wgOut->addHTML('<p>Add a video to your True Fan profile.</p>');
							$userName = $profile['name'];
						}

						$form = new TrueFanForm($this->getTitle(), 'video');
						$form->setFieldAttr('Name', 'default', $userName);
						$form->setFieldAttr('Email', 'default', $userEmail);					

					} elseif(!$profile['video_message']) {
						// No video_message: create an 'invite' form 
					 	// User exists with video and but no message and email list
						$wgOut->addHTML('<p>Add a message and invite friends to view your video.</p>');

						$form = new TrueFanForm($this->getTitle(), 'invite');
						$form->setFieldAttr('Id', 'default', $profile['id']);
					} else {
						// We have all necessary information, allow the user to edit their information
						// We need a 'combo' editing form
						// TODO: build combo form
						$wgOut->addHTML('<p>Edit your message or email invitation.</p>');

						$form = new TrueFanForm($this->getTitle(), 'edit');
						$form->setFieldAttr('Name', 'default', $profile['name']);
						$form->setFieldAttr('Email', 'default', $profile['email']);					
						$form->setFieldAttr('VideoId', 'default', $profile['video_id']);									
						$form->setFieldAttr('Message', 'default', $profile['video_message']);
						$form->setFieldAttr('EmailInput', 'default', $profile['email_invite_list']);
					}

					// Display available true fan profile information
					if($profile) {
						$wgOut->addHTML("<div><span>{$profile['name']}: </span><span>{$profile['email']}</span></div>");
					}
					if($profile['video_id']) {
						$wgOut->addHTML("<iframe src='http://www.youtube.com/embed/".$profile['video_id']."'>No iframes.</iframe>");
					}
					$form->show();
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
		global $wgUser, $wgOut;

		// You must be logged in to submit data. Anything else is nonsense.
		if(!$wgUser->isLoggedIn()) {
			tfDebug("!!Attempt to post without being logged in!!");	
			return;
		}

		// This switch handles posted input from our forms
		switch($postRequest) {
			case 'video':
				$wgOut->addHTML("<p>Received form from: <strong>".$this->mReqName."</strong></p>");
				// ?? Does following work for id=0 or must db start at 1??
				if($id = $this->mDb->addUser($this->mReqName, $this->mReqEmail, $this->mReqVideoId)) {
					$wgOut->addHTML("<p>Added request to DB.</p>");
				} else {
					if($this->mDb->isDuplicateEmail($this->mReqEmail)) {
						$wgOut->addHTML("<p>Unable to add user. Your email was already entered.</p>");
					} else if(!$this->mDb->extractVideoId($this->mReqVideoId)) {
						$wgOut->addHTML("<p>Unable to extract video id from URL.</p>");
					} else {
						$wgOut->addHTML("<p>Unable to add request to DB.</p>");
					}
				}
				$this->handleViewPage('submit'); // throw it back viewing pages
				break;
			case 'invite':
				// Get rid of empty strings	
				foreach($this->mReqInviteEmails as $key => $email) {
					if($email == '') {
						unset($this->mReqInviteEmails[$key]);
					}
				}
				if($this->mDb->updateInvitation($this->mReqInviteId, $this->mReqInviteMessage, $this->mReqInviteEmails)) { 
					$wgOut->addHTML("<p>{$this->mReqInviteMessage}</p><ul>"); 
					foreach($this->mReqInviteEmails as $email) {
						$wgOut->addHTML("<li>$email</li>");
					}
					$wgOut->addHTML("</ul>");	
					$link = $this->getTitle()->getFullUrl()."?page=view&id={$this->mReqInviteId}";
					$wgOut->addHTML("<span>View your video and message at this link: </span><a href='$link'>$link</a>");
				} else {
					$wgOut->addHTML("<p>Unable to insert message and email list :< </p>");
				}
				break;
			default:
				$wgOut->addHTML("<p>Unknown request posted.</p>");
				break;
		}
	}
	
	/**
	 * Heredoc text drop of paypal button.
	 * You can modify css and add things, but don't mess with names and 
	 * values of fields. 
	 * @return String Button text
	 * TODO: If we upgrade OSE wiki to > 1.18 we could probably rewrite
	 * this in code and gain a handle on i81n of text fields.
	 */
	private function getPayPalButton()
	{
		// 
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

/**
 * Class to manage the various forms that interact with the database.
 * Basically this amounts to managing MW HTMLForm[s] and their descriptors.
 */
class TrueFanForm
{
	protected $mDescriptor;
	protected $mForm;
	protected $mTitle;
	protected $mType;	
	protected $mFormBuilt;
	protected $mFormLoaded;

	/**
	 * Builds TrueFanForm by filling descriptor and initialize state variables
	 * @param Title $title MW Title object with url info that determines form post action
	 * @param String $type the type of form to create
	 */
	public function __construct($title, $type='video')
	{
		$this->mType = $type;
		$this->initializeDescriptor();
		$this->mTitle = $title;
		$this->mFormBuilt = false;
		$this->mFormLoaded = false;
	}

	/**
	 * Desc 
	 * @param Contect $var
	 * @return 
	 */
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

	/**
	 * Builds unbuilt form and outputs html.
	 */
	public function show()
	{
		$this->build();
		$this->mForm->show();
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
	 * Builds an unbuilt form and loads the data that was posted
	 * @param String $field Desired field
	 * @return Mixed Posted data  
	 * *Caution* MW 1.16 won't return the values of hidden fields. We can create
	 * readonly fields and perhaps css hide them, but only none hidden fields load.
	 */
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
					'type' => 'text',
					'section' => $this->mType,
					'id' => 'ose-truefan-name',
					'label' => 'Name',
					'size' => 20,
					'readonly' => true,
				),
				'Email' => array(
					'type' => 'text',
					'section' => $this->mType,
					'id' => 'ose-truefan-email',
					'label' => 'Email',
					'size' => 20,
					'readonly' => true,
				),
				'VideoId' => array(
					'type' => 'text',
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
		case 'edit':
			$this->mDescriptor = array(
				'Page' => array(
					'type' => 'hidden',
					'default' => $this->mType, 
					'section' => $this->mType,
				),
				//?? Allow name change ??
				'Name' => array(
					'type' => 'text',
					'section' => $this->mType,
					'id' => 'ose-truefan-name',
					'label' => 'Name',
					'size' => 20,
					'readonly' => true,
				),
				'Email' => array(
					'type' => 'text',
					'section' => $this->mType,
					'id' => 'ose-truefan-email',
					'label' => 'Email',
					'size' => 20,
					'readonly' => true,
				),
				'VideoId' => array(
					'type' => 'text',
					'section' => $this->mType,
					'id' => 'ose-truefan-url',
					'label' => 'Video Id',
					'size' => 20,
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

	// !!! Not intended for production use !!! This will inject lots of extension specific !!!
	// !!! logging into the master log which should only be for important errors !!!
	wfDebug($str."\n"); 
}
