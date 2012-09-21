<?php
/**

 *
 */

require_once('class.TrueFanDb.php');
 
class SpecialShareOSE extends SpecialPage {

	/**
	 * Constructor : initialise object
	 * Get data POSTed through the form and assign them to the object
	 * @param WebRequest $request Data posted.
	 */

	protected $mTrueFanDb;
	 
	public function __construct() {
		global $wgRequest;
		tfDebug('***  Constructing SpecialShareOSE  ***');
		parent::__construct( 'ShareOSE');

		$this->loadRequest();
		$this->mTrueFanDb = new TrueFanDb();
	}

	/** Misc variables **/
	protected $mRequest;			// The WebRequest or FauxRequest this form is supposed to handle
	protected $mRequestPosted;
	
	protected $mReqName;
	protected $mReqEmail;
	protected $mReqUrl;
	
	/**
	 * Initialize instance variables from request and create an Upload handler
	 *
	 * @param WebRequest $request The request to extract variables from
	 */
	protected function loadRequest() {
		global $wgUser, $wgRequest;

		$this->mRequest = $wgRequest;
		
		$this->mRequestPosted = $this->mRequest->wasPosted();
		
		// MediaWiki prefixes 'wp' to names in the form $descriptor
		$this->mReqName = $this->mRequest->getText('wpName');
		$this->mReqEmail = $this->mRequest->getText('wpEmail');
		$this->mReqUrl = $this->mRequest->getText('wpUrl');
	}

	/**
	 * Special page entry point
	 */
	public function execute( $par ) {
		global $wgUser, $wgOut, $wgRequest;

		$this->setHeaders();
		$this->outputHeader();
		
		if($this->mRequestPosted) {
			$wgOut->addHTML("<p>Received form from: ".$this->mReqName." the III. </p>");
		} else {
			$this->getUploadForm()->show();
		}
	}


	/**
	 * Get an UploadForm instance with title and text properly set.
	 *
	 * @param string $message HTML string to add to the form
	 * @param string $sessionKey Session key in case this is a stashed upload
	 * @return UploadForm
	 */
	protected function getUploadForm($sessionKey = '') {
		global $wgOut;
		
		# Initialize form
		$form = new TrueFanForm(); // /*'sessionkey' => $sessionKey*/);
		$form->setTitle( $this->getTitle() );
		
		return $form;
	}
}



/**
 * Sub class of HTMLForm that provides the form section of SpecialUpload
 */
class TrueFanForm extends HTMLForm {
	//protected $mSessionKey;
	protected $mSourceIds;

	public function __construct() { //maybe session key ... ?
		global $wgLang;

		$descriptor = $this->getFormDescriptor();

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
	 * Get the descriptor of the fieldset that contains the file description
	 * input. The section is 'description'
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
			'Url' => array(
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