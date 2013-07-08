<?php

/**
 * FacebookAdmin extension for user-specific actions.
 *
 * @package ssilverstripe-facebook
 * @subpackage extension
**/
class FacebookUserAdmin extends DataExtension {
	
	public function onAfterInit() {
		Requirements::css("silverstripe-facebook/admin/css/screen.css");
		Requirements::javascript("silverstripe-facebook/admin/javascript/FacebookAdmin.js");
	}
	
	public function updateCMSActions(FieldList $fields) {
		$facebookApp = $this->getFacebookApp();
		$facebook = $facebookApp->getFacebook();
		
		if($facebook) {			
			// Add the authorize action if consumer keys are set.
			if($facebookApp->FacebookConsumerKey && $facebookApp->FacebookConsumerSecret) {
				$user = $facebook->getUser();
				if(!$user) {
					$params = $facebookApp->getLoginUrlParams();
					if($url = $facebook->getLoginUrl($params)) {
						$fields->push(
							FormAction::create("authorize", "Authorize a Facebook Account")
								->setUseButtonTag(true)
								->addExtraClass("silverstripe-facebook-ui-action-facebook")
								->setAttribute("data-icon", "facebook")
								->setAttribute("data-role", "authorize")
								->setAttribute("data-url", $url)
						);
					}
				}
			}
		}
		return $fields;
	}
	
	/**
	 * Fetches the sites Facebook Applications.
	 *
	 * @return FacebookApp
	**/
	public function getFacebookApp() {
		return FacebookApp::get()->first();
	}
}
