<?php
/**

 *
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
		global $wgUser, $wgOut, $wgRequest;

		$this->setHeaders();
		$this->outputHeader();
		$wgOut->addExtensionStyle('extensions/ShareOSE/style.css');

		if($this->mReqPage === 'viewall') {
			$result = $this->mDb->getAllEntries();
			$wgOut->addHTML('<ul>');
			foreach($result as $row) {
				$wgOut->addHTML('<li>');
				$wgOut->addHTML("<div><a href='?page=view&id=".$row['id']."'><span>{$row['name']}: </span><span>{$row['email']}</span></a></div>");
				//foreach($row as $rkey => $rval) {
				//	$wgOut->addHTML("<span>$rval </span>");
				//}
				$wgOut->addHTML('</li>');
			}
			$wgOut->addHTML('</ul>');
		} else if($this->mReqPage === 'view' ) {
				$profile = $this->mDb->getUser($this->mReqId);
				$wgOut->addHTML("<div><span>{$profile['name']}: </span><span>{$profile['email']}</span></div>");
				$wgOut->addHTML("<iframe src='http://www.youtube.com/embed/".$profile['video_id']."'>No iframes.</iframe>");
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
			$wgOut->addHTML("<p>------------------</p>");
			$result = $this->mDb->getAllEntries();
			$wgOut->addHTML('<ul>');
			foreach($result as $row) {
				$wgOut->addHTML('<li>');
				foreach($row as $rkey => $rval) {
					$wgOut->addHTML("<span>$rval </span>");
				}
				$wgOut->addHTML('</li>');
			}
			$wgOut->addHTML('</ul>');
		} else {
			if($wgUser->isLoggedIn()) {
				$wgOut->addHTML('<p>Your login info was added to the form.</p>');
				$trueFanForm = $this->buildTrueFanForm($wgUser->getRealName(), $wgUser->getEmail());
			} else {
				$trueFanForm = $this->buildTrueFanForm();
			}
			$trueFanForm->show();
		}
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

		# Build a list of IDs for javascript insertion // !! WTF does this doe and can i use it with my own custom js??
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

/**
 * Syntactic sugar. Outputs to local extension log.
*/
function tfDebug($str)
{
	wfDebug($str."\n"); // !!! Not intended for production use !!! This will inject lots of extension specific logging into the master log which should only be for important errors !!!
	wfDebugLog( 'ShareOSE', $str);
}