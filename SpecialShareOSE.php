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
		$this->mDb = new TrueFansDb(); // not goot to do for unit testing...
	}

	/** Misc variables **/
	protected $mRequest;			// The WebRequest or FauxRequest this form is supposed to handle
	protected $mRequestPosted;
	
	protected $mReqName;
	protected $mReqEmail;
	protected $mReqVideoId;

	protected $mReqPage;
	
	/**
	 * Initialize instance variables from request.
	 */
	protected function loadRequest() {
		global $wgUser, $wgRequest;

		$this->mRequest = $wgRequest;
		
		$this->mRequestPosted = $this->mRequest->wasPosted();
		
		// MediaWiki prefixes 'wp' to names in the form $descriptor
		$this->mReqName = $this->mRequest->getText('wpName');
		$this->mReqEmail = $this->mRequest->getText('wpEmail');
		$this->mReqVideoId = $this->mRequest->getText('wpVideoId');

		$this->mReqPage = $this->mRequest->getText('page');
		$this->mReqId = $this->mRequest->getText('id');
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
		
		if($this->mReqPage === 'viewall') {
			$result = $this->mDb->getAllEntries();
			$wgOut->addHTML('<table id="submissions"><tbody>');
			foreach($result as $row) {
				$wgOut->addHTML('<tr>');
				$wgOut->addHTML("<td><a href='?page=view&id=".$row['id']."'>{$row['name']} </a></td><td>{$row['email']}</td><td>{$row['video_id']}</td>");
				$wgOut->addHTML('</tr>');
			}
			$wgOut->addHTML('</table></tbody>');
		} else if($this->mReqPage === 'view' ) {
			$profile = $this->mDb->getUser($this->mReqId);
			$wgOut->addHTML("<div><span>{$profile['name']}: </span><span>{$profile['email']}</span></div>");
			$wgOut->addHTML("<iframe src='http://www.youtube.com/embed/".$profile['video_id']."'>No iframes.</iframe>");
		} else if($this->mReqPage === 'invite') {
			$wgOut->addHTML('<p>Invite your friends to become part of OSE.</p>');
		} else if($this->mReqPage === 'subscribe') {
			$wgOut->addHTML('<p>True Fans: As a project working for the common good, we rely on a growing group of crowd-funders called True Fans to keep our mission alive. The True Fans program is a monthly donation with options for $10, $20, $30, $50, and $100 per month for 24 months. </p>');
			$wgOut->addHTML(getPayPalButton());
		} else if($this->mRequestPosted) {
			$wgOut->addHTML("<p>Received form from: <strong>".$this->mReqName."</strong></p>");
			if($id = $this->mDb->addUser($this->mReqName, $this->mReqEmail, $this->mReqVideoId)) {
				$wgOut->addHTML("<p>Added request to DB.</p>");
				$profile = $this->mDb->getUser($id);
				$wgOut->addHTML("<div><span>{$profile['name']}: </span><span>{$profile['email']}</span></div>");
				$wgOut->addHTML("<iframe src='http://www.youtube.com/embed/".$profile['video_id']."'>No iframes.</iframe>");
			} else {
				if($this->mDb->isDuplicateEmail($this->mReqEmail)) {
					$wgOut->addHTML("<p>Unable to add user. Your email was already entered.</p>");
				} else if(!$this->mDb->extractVideoId($this->mReqVideoId)) {
					$wgOut->addHTML("<p>Unable to extract video id from URL.</p>");
				} else {
					$wgOut->addHTML("<p>Unable to add request to DB.</p>");
				}
			}
		} else {
			if($wgUser->isLoggedIn()) {
				$wgOut->addHTML('<p>Your login info was added to the form.</p>');
				$trueFanForm = $this->buildTrueFanForm($wgUser->getRealName(), $wgUser->getEmail());
			} else {
				$wgOut->addHTML('<p>You are not logged in.</p>');
				$trueFanForm = $this->buildTrueFanForm();
			}
			$trueFanForm->show();
			echo(hash('md5',randomString()));
		}
		$wgOut->addHTML('<ul id="special-links"><li><a href="?page=home">Submit A Video</a></li>');
		$wgOut->addHTML('<li><a href="?page=invite">Invite Friends</a></li>');
		$wgOut->addHTML('<li><a href="?page=subscribe">Become a True Fan</a></li>');
		$wgOut->addHTML('<li><a href="?page=viewall">View All Submissions</a></li></ul>');
	}


	/**
	 * Get a TrueFanForm instance with title and text properly set.
	 *
	 * @return UploadForm
	 */
	protected function buildTrueFanForm($name='', $email='') {
		global $wgOut;
		
		# Initialize form
		$form = new TrueFanForm($name, $email);
		$form->setTitle( $this->getTitle() );
		
		return $form;
	}
}



/**
 * Sub class of HTMLForm that provides the form 
 */
class TrueFanForm extends HTMLForm {
	protected $mSourceIds;

	public function __construct($name='',$email='') { //maybe session key ... ?
		global $wgLang;

		$descriptor = $this->getFormDescriptor();
		$descriptor['Name']['default'] = $name;
		$descriptor['Email']['default'] = $email;
		
		parent::__construct( $descriptor, 'true-fan-form' );

		# Set some form properties
		$this->setSubmitText( 'Submit Form'); //wfMsg( 'uploadbtn' ) ); // internationalize later
		$this->setSubmitName( 'wpTrueFanSubmit' );
		$this->setSubmitTooltip( 'upload' );
		$this->setId( 'mw-ose-truefan-form' );

		# Build a list of IDs for javascript insertion // !! WTF does this do and can i use it with my own custom js?? must interface with wiki js, usable 4 me?
		$this->mSourceIds = array();
		foreach ( $descriptor as $key => $field ) {
			if ( !empty( $field['id'] ) )
				$this->mSourceIds[] = $field['id'];
		}

	}


	/**
	 * Get the descriptor. 
	 * 
	 * @return array Descriptor array
	 */
	protected function getFormDescriptor() {
		$descriptor = array(
			'Name' => array(
				'type' => 'text',
				'section' => 'main',
				'id' => 'ose-truefan-name',
				'label' => 'Your Name',
				'size' => 20,
			),
			'Email' => array(
				'type' => 'text',
				'section' => 'main',
				'id' => 'ose-truefan-email',
				'label' => 'Email',
				'size' => 20,
			),
			'VideoId' => array(
				'type' => 'text',
				'section' => 'main',
				'id' => 'ose-truefan-url',
				'label' => 'Url of Video',
				'size' => 20,
			),
		);
		return $descriptor;
	}

	/**
	 * Empty function; submission is handled elsewhere.
	 * 
	 * @return bool false
	 */
	function trySubmit() {
		return false;
	}
}

function getPayPalButton()
{
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
	<img alt="" border="0" src="https://www.sandbox.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
	</form>
EOT;
	return $str;
}

function randomString($bits = 256)
{
    $bytes = ceil($bits / 8);
    $return = '';
    for ($i = 0; $i < $bytes; $i++) {
        $return .= chr(mt_rand(0, 255));
    }
    return $return;
}

/**
 * Syntactic sugar. Outputs to local extension log.
*/
function tfDebug($str)
{
	wfDebug($str."\n"); // !!! Not intended for production use !!! This will inject lots of extension specific logging into the master log which should only be for important errors !!!
	wfDebugLog( 'ShareOSE', $str);
}
