<?php

/**
 * This class is built purely to rewire the display code to break free of 
 * table based forms. Very little is changed between this code and orginal
 * HTMLForm.
 */

class SexyForm extends HTMLForm
{
	function displaySection( $fields, $sectionName = '' ) {
		$tableHtml = '';
		$subsectionHtml = '';
		$hasLeftColumn = false;

		foreach( $fields as $key => $value ) {
			if ( is_object( $value ) ) {
				$v = empty( $value->mParams['nodata'] )
					? $this->mFieldData[$key]
					: $value->getDefault();
				$tableHtml .= $value->getTableRow( $v );

				if( $value->getLabel() != '&nbsp;' )
					$hasLeftColumn = true;
			} elseif ( is_array( $value ) ) {
				$section = $this->displaySection( $value, $key );
				$attribs['id'] = 'mw-form-section-'.$key;
				$subsectionHtml .= Html::rawElement( 'div', $attribs, "\n$section\n" ) . "\n";
			}
		}

		$classes = array();
		if( !$hasLeftColumn ) // Avoid strange spacing when no labels exist
			$classes[] = 'mw-htmlform-nolabel';
		$attribs = array(
			'class' => implode( ' ', $classes ), 
		);
		if ( $sectionName ) 
			$attribs['id'] = Sanitizer::escapeId( "mw-htmlform-$sectionName" );

		//$tableHtml = Html::rawElement( 'table', $attribs,
		//	Html::rawElement( 'tbody', array(), "\n$tableHtml\n" ) ) . "\n";


		return $subsectionHtml . "\n" . $tableHtml;
	}

	/**
	 * Copy of the original HTMLForm::displayForm method but which outputs to string so we can template it.
	 */
	function displayForm( $submitResult ) {

		if ( $submitResult !== false ) {
			$this->displayErrors( $submitResult );
		}

		$html = ''
			. $this->mHeader
			. $this->getBody()
			. $this->getHiddenFields()
			. $this->getButtons()
		;

		$html = $this->wrapForm( $html );

		return '' .  $this->mPre . $html . $this->mPost;
	}
	
	function loadForm() {
		$html = '';

		self::addJS();

		# Load data from the request.
		$this->loadData();

		# Try a submission
		global $wgUser, $wgRequest;
		$editToken = $wgRequest->getVal( 'wpEditToken' );

		$result = false;
		if ( $wgUser->matchEditToken( $editToken ) )
			$result = $this->trySubmit();

		return $result;
	}

}

/* Each input class has to overridden so that it doesn't
 * output <tr> tags.
 */
class HTMLSexyTextField extends HTMLTextField
{
	/**
	 * Get the complete table row for the input, including help text,
	 * labels, and whatever.
	 * @param $value String the value to set the input to.
	 * @return String complete HTML table row.
	 */
	function getTableRow( $value ) 
	{
		# Check for invalid data.
		global $wgRequest;

		$errors = $this->validate( $value, $this->mParent->mFieldData );
		if ( $errors === true || !$wgRequest->wasPosted() ) {
			$errors = '';
		} else {
			$errors = Html::rawElement( 'span', array( 'class' => 'error' ), $errors );
		}

		$html = $this->getLabelHtml();
		$html .= Html::rawElement( 'div', array( 'class' => 'mw-input' ),
							$this->getInputHTML( $value ) ."\n$errors" );

		$fieldType = get_class( $this );

		$html = Html::rawElement( 'div', array( 'class' => "mw-htmlform-field-$fieldType" ),
							$html ) . "\n";

		$helptext = null;
		if ( isset( $this->mParams['help-message'] ) ) {
			$msg = $this->mParams['help-message'];
			$helptext = wfMsgExt( $msg, 'parseinline' );
			if ( wfEmptyMsg( $msg, $helptext ) ) {
				# Never mind
				$helptext = null;
			}
		} elseif ( isset( $this->mParams['help'] ) ) {
			$helptext = $this->mParams['help'];
		}

		if ( !is_null( $helptext ) ) {
			$row = Html::rawElement( 'div', array( 'colspan' => 2, 'class' => 'htmlform-tip' ),
				$helptext );
			$row = Html::rawElement( 'div', array(), $row );
			$html .= "$row\n";
		}

		return $html;
	}

	function getLabelHtml() {
		# Don't output a for= attribute for labels with no associated input.
		# Kind of hacky here, possibly we don't want these to be <label>s at all.
		$for = array();
		if ( $this->needsLabel() ) {
			$for['for'] = $this->mID;
		}
		return Html::rawElement( 'div', array( 'class' => 'mw-label' ),
					Html::rawElement( 'label', $for, $this->getLabel() )
				);		
	}
}

/**
 * It's textField that loads as an array -- so you can have multiple repeated textfields.
 */
class HTMLTextArray extends HTMLSexyTextField
{
	protected $mRequestName;
	function __construct( $params ) {
		parent::__construct( $params );
		$this->mRequestName = $this->mName;
		$this->mName .= '[]';
	}

	function loadDataFromRequest( $request ) {
		// Arrays don't work with getCheck so we'll look at the edit token and then inspect the array we get
		if( $request->getCheck( 'wpEditToken' ) ) {
			$emailArray = $request->getArray($this->mRequestName);
			
			$emailStr = '';
			if($emailArray) {
				foreach($emailArray as $key => $email) {
					if($email == '') {
						unset($emailArray[$key]);
					}
				}
				$emailStr = implode(', ', $emailArray);
			} else {
				// there's no array, so we'll take the default if it was set
				return $this->getDefault();
			}

			return $emailStr; 
		} else {
			return $this->getDefault();
		}
	}
}

class HTMLSexyCheckField extends HTMLCheckField
{
	function getTableRow( $value ) 
	{
		# Check for invalid data.
		global $wgRequest;

		$errors = $this->validate( $value, $this->mParent->mFieldData );
		if ( $errors === true || !$wgRequest->wasPosted() ) {
			$errors = '';
		} else {
			$errors = Html::rawElement( 'span', array( 'class' => 'error' ), $errors );
		}

		$html = ''; // $this->getLabelHtml();
		$html .= Html::rawElement( 'div', array( 'class' => 'mw-input' ),
						$this->getInputHTML( $value ) ."\n$errors" );

		$fieldType = get_class( $this );

		$html = Html::rawElement( 'div', array( 'class' => "mw-htmlform-field-$fieldType" ),
							$html ) . "\n";

		$helptext = null;
		if ( isset( $this->mParams['help-message'] ) ) {
			$msg = $this->mParams['help-message'];
			$helptext = wfMsgExt( $msg, 'parseinline' );
			if ( wfEmptyMsg( $msg, $helptext ) ) {
				# Never mind
				$helptext = null;
			}
		} elseif ( isset( $this->mParams['help'] ) ) {
			$helptext = $this->mParams['help'];
		}

		if ( !is_null( $helptext ) ) {
			$row = Html::rawElement( 'div', array( 'colspan' => 2, 'class' => 'htmlform-tip' ),
				$helptext );
			$row = Html::rawElement( 'div', array(), $row );
			$html .= "$row\n";
		}

		return $html;
	}
}

class HTMLSexyTextArea extends HTMLTextAreaField
{
	function getTableRow( $value ) 
	{
		# Check for invalid data.
		global $wgRequest;

		$errors = $this->validate( $value, $this->mParent->mFieldData );
		if ( $errors === true || !$wgRequest->wasPosted() ) {
			$errors = '';
		} else {
			$errors = Html::rawElement( 'span', array( 'class' => 'error' ), $errors );
		}

		$html = $this->getLabelHtml();
		$html .= Html::rawElement( 'div', array( 'class' => 'mw-input' ),
							$this->getInputHTML( $value ) ."\n$errors" );

		$fieldType = get_class( $this );

		$html = Html::rawElement( 'div', array( 'class' => "mw-htmlform-field-$fieldType" ),
							$html ) . "\n";

		$helptext = null;
		if ( isset( $this->mParams['help-message'] ) ) {
			$msg = $this->mParams['help-message'];
			$helptext = wfMsgExt( $msg, 'parseinline' );
			if ( wfEmptyMsg( $msg, $helptext ) ) {
				# Never mind
				$helptext = null;
			}
		} elseif ( isset( $this->mParams['help'] ) ) {
			$helptext = $this->mParams['help'];
		}

		if ( !is_null( $helptext ) ) {
			$row = Html::rawElement( 'div', array( 'colspan' => 2, 'class' => 'htmlform-tip' ),
				$helptext );
			$row = Html::rawElement( 'div', array(), $row );
			$html .= "$row\n";
		}

		return $html;
	}
}

/* This override is done so that we can stuff new data
 * in hidden fields and have it autoload correctly.
 * All that's changed is that the actual name is reported
 * back--this augments the fields name with 'wp' and
 * allows the autoloader to pick it up.
 */
class HTMLReturnableHiddenField extends HTMLHiddenField
{
	public function getTableRow( $value ){
		$this->mParent->addHiddenField( 
			$this->mName,
			$this->mParams['default']
		);
		return '';
	}	
}
