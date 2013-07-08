<?php

/**
 * Admin interface to mange Facebook integration.
 *
 * @package silverstripe-facebook
 * @subpackage admin
**/
class FacebookAdmin extends LeftAndMain {
	
	static $allowed_actions = array(
		"save",
		"EditForm",
	);
	
	/**
	 * CMS URL Segment
	 * 
	 * @var string
	**/
	static $url_segment = "facebook";

	/**
	 * CMS Menu Title
	 *
	 * @var string
	**/
	static $menu_title = "Facebook Integration";
	
	/**
   	 * CMS Menu icon.
   	 *
   	 * @var string - Path to image
   	**/
	static $menu_icon = "silverstripe-facebook/admin/images/menu-icons/facebook.png";
	
	/**
	 * Stores an instance of Facebook.
	 *
	 * @var Faceviij
	**/
	protected $facebook;
	
	
	public function init() {
		parent::init();
		
		// Load Facebook App
		$this->getFacebookApp();
	}

	
	public function getEditForm($id = null, $field = null) {
		$form = parent::getEditForm($id, $field);
		$form->addExtraClass("center");
		
		// Setup Fields
		$form->setFields($this->facebook->getCMSFields());
		// Setup Actions
		$form->setActions($this->getCMSActions());
		// Populate Form
		$form->loadDataFrom($this->facebook);
		
		return $form;
	}
	
	/**
	 * Add actions to the EditForm
	 *
	 * @return FieldList
	**/
	public function getCMSActions() {
		//Setup Actions
		$actions = new FieldList();
		$actions->push(
			FormAction::create("save", "Save")->setUseButtonTag(true)
				->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
		);
		$this->extend("updateCMSActions", $actions);
		return $actions;
	}
	
	/**
	 * Save the form in its current state.
	 * 
	 * @param $data array - Form data
	 * @param $form Form - Current Form
	 * @return SS_HTTPResponse
	**/
	public function save($data, $form) {
		$facebook = $this->getFacebookApp();
		$form->saveInto($facebook, $this->request);
		
		if($facebook->write()) {
			$form->sessionMessage("Facebook Application saved.", "good");
		} else {
			$form->sessionMessage("Unable to save Facebook Application", "bad");
		}
		return $this->getResponseNegotiator()->respond($this->request);
	}
	
	
	/**
	 * Returns the Facebook App for the current site.
	 *
	 * @return FacebookApp
	**/
	public function getFacebookApp() {
		if($this->facebook) return $this->facebook;
		return $this->facebook = FacebookApp::get()->first();
	}
}

?>
