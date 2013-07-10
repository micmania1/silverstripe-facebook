<?php

/**
 * Provides the extra fields needed to store a user
 *
 * @package silverstripe-facebook
 * @subpackage extension
**/
class FacebookUser extends DataExtension {

	/**
	 * Stores the users' instance of Facebook
	 *
	 * @var Facebook
	**/
	static $facebook;

	/**
	 * Whether or not to allow duplicate Facebook accounts on the same DataObject type.
	 *
	 * @var boolean
	**/
	protected $allowDuplicateFacebookAccounts = false;

	/**
	 * Whether or not to overwrite existing Facebook account data on the current DataObject.
	 *
	 * @var boolean
	**/
	protected $overwriteExistingFacebookAccount = false;
    

	static $db = array(
		"FacebookUserID" => "Varchar(255)",
		"FacebookAccessToken" => "Varchar(255)"
	);
    

	/**
	 * This method holds the functionality to complete the oauth flow through the CMS
	 *
	 * @param $fields FieldList
	**/
	public function updateCMSFields(FieldList $fields) {
		// Remove fields that may have been added elsewhere.
		$fields->removeByName("FacebookUser");
		$fields->removeByName("FacebookUserID");
		$fields->removeByName("FacebookAccessToken");
		
		// Add our new fields.
		$fields->addFieldsToTab("Root.Main",
			ToggleCompositeField::create('FacebookUser', 'Facebook User',
				array(
					TextField::create("FacebookUserID", "User ID"),
					TextField::create("FacebookAccessToken", "Access Token")
				)
			)->setHeadingLevel(4)
		);
	}
    
	/**
	 * Set whether or not to allow duplicate facebook accounts on this type of DataObject
	 *
	 * @param $bool boolean
	**/
	public function setAllowDuplicateFacebookAccounts($bool) {
		$this->allowDuplicateFacebookAccounts = (bool) $bool;
	}

	/**
	 * Fetch whether or not we're allowing duplicate facebook accounts on this dataobject type.
	 *
	 * @return boolean
	**/
	public function getAllowDuplicateFacebookAccounts() {
		return (bool) $this->allowDuplicateFacebookAccounts;
	}

	/**
	 * Set whether or not we should overwrite existing records.
	 *
	 * @param $bool boolean
	**/
	public function setOverwriteExistingFacebookAccount($bool) {
		$this->overwriteExistingFacebookAccount = (bool) $bool;
	}

	/**
	 * Get whether or not we can overwrite existing facebook account data.
	 *
	 * @return boolean
	**/
	public function getOverwriteExistingFacebookAccount() {
		return (bool) $this->overwriteExistingFacebookAccount;
	}


	/**
	 * Validate writing of the Facebook Account
	 *
	 * @param $validation ValidationResult
	**/
	public function validate(ValidationResult $validation) {
		if($this->owner->FacebookUserID && $this->owner->FacebookAccessToken) {
		    // Check to see if the user is already connected to an account & whether it matters.
		    if($this->getOverwriteExistingFacebookAccount() == false) {
		    	$changed = $this->owner->getChangedFields();
		        if($this->owner->isChanged("FacebookUserID") && trim($changed['FacebookUserID']['before']) != "") {
		           	$validation->error("You already have a Facebook account connected.");
		        }
		    }
		    
		    // Check to see if there are other types of this DataObject also connected to this Facebook account (if it matters)
		    if($this->getAllowDuplicateFacebookAccounts() == false) {
		        $duplicate = DataList::create($this->owner->ClassName)
		            ->filter("FacebookUserID", $this->owner->FacebookUserID);
		        
		        // Exclude the current user from the search.
		        if($this->owner->ID)
		            $duplicate = $duplicate->exclude("ID", $this->owner->ID);
		            
		        if($duplicate->first())
		            $validation->error("Your Facebook account is already connected to another " .  $this->owner->singular_name() . ".");
		    }
		}
	}



	/**
	 * This connects the given facebook account to the current DataObject
	 *
	 * @param $user array - data rutned by calling $facebook->api("/me");
	 * @param $access_token string - Facebook User Access Token
	 * @param $required_fields array - fields that should exist within the array.
	 *
	 * @return ValidationResult
	**/
	public function connectFacebookAccount($user, $access_token, $required_fields = array()) {
		$validation = new ValidationResult();
		// Check required fields exist.
		$required = array_merge($required_fields, array("id"));
		foreach($required as $r) {
			if(!isset($user[$r])) {
				$validation->error("A required fields was missing: " . $r);
				return $validation;
			}
		}

		// Write our values to the DataObject
		$this->owner->FacebookUserID = $user['id']; // Required field.
		$this->owner->FacebookAccessToken = $access_token;
		if(isset($user['email']) && !$this->owner->Email && Email::validEmailAddress($user['email'])) $this->owner->Email = $user['email'];
		if(isset($user['first_name']) && !$this->owner->FirstName) $this->owner->FirstName = $user['first_name'];
		if(isset($user['last_name']) && !$this->owner->Surname) $this->owner->Surname = $user['last_name'];

		// Facebook hook
		$this->owner->extend("beforeConnectFacebookAccount", $validation, $user, $access_token);

		$memberValidation = $this->owner->validate();
		if($memberValidation->valid()) {
			if(!$this->owner->write()) {
				$validation->error("Unable to create your account.");
			}
		} else {
			$this->owner->extend("invalidFacebookConnect", $memberValidation);
			return $memberValidation;
		}
		return $validation;
	}



	/**
	 * This will disconnect a DataObject from its associated Facebook account.
	 *
	 * @return boolean
	**/
	public function disconnectFacebookAccount() {
		// Write our values to the DataObject
		$this->owner->FacebookUserID = "";
		$this->owner->FacebookAccessToken = "";
		
		return (bool) $this->owner->write();
	}



	/**
	 * Clears out the facebook session keys.
	**/
	public function memberLoggedOut() {
		$facebookApp = FacebookApp::get()->first();

		if($facebookApp) {
			foreach(array('state', 'code', 'access_token', 'user_id') as $key) {
				Session::clear("fb_" . $facebookApp->FacebookConsumerKey . "_" . $key);
			}
		}
	}
}
