<?php

/**
 * FacebookContoller provides the integration functionality for the front-end
 *
 * @package silverstripe-facebook
 * @subpackage control
**/
class FacebookController extends ContentController {

	static $allowed_actions = array(
		"connect" => "->facebookLoginEnabled",
		"login" => "->facebookLoginEnabled",
		"disconnect",
	);


	public function facebookLoginEnabled() {
		$facebookApp = FacebookApp::get()->first();
		return $facebookApp->EnableFacebookLogin;
	}

	/**
	 * Return a blank form to display front-end messages
	 *
	 * @return Form
	**/
	public function Form() {
		$form = new Form($this, "Facebook", new FieldList(), new FieldList());
		$this->extend("setupForm", $form);
		return $form;
	}

	/**
	* Prevent this request as it doesn't do anything.
	*
	* @return SS_HTTPResponse
	**/
	public function index() {
		return $this->httpError(403, "Forbidden");
	}


	/** 
	 * This will connect a facebook account to a logged in Member.
	 *
	 * @param $request SS_HTTPRequest
	 * @return SS_HTTPResponse
	**/
	public function connect($request) {
		$form = $this->Form();
		$member = Member::currentUser();
		
		if($this->request->getVar("error")) {
			$form->sessionMessage("Unable to obtain access to Facebook.", "bad");
			return $this->renderWith(array("FacebookController", "Page", "Controller"));
		}


		$facebookApp = FacebookApp::get()->first();
		if($member || $facebookApp->EnableFacebookLogin) {
			$facebook = $facebookApp->getFacebook();
			if(!$facebook) {
				$form->sessionMessage("Unable to fetch Facebook Application", "bad");
				return $this->renderWith(array("FacebookController", "Page", "Controller"));
			}

			$user = $facebook->getUser();
			if(!$user) {
				$params = $facebookApp->getLoginUrlParams();
				$url = $facebook->getLoginUrl($params);
				if($url) {
					return $this->redirect($url, 302);
				} else {
					$form->sessionMessage("Unable to login to Facebook.", "bad");
				}
			} else {
				$user_profile = $facebook->api("/me");
				if($user_profile) {
					// Check whether this is a new user (signup)
					$member = Member::get()->filter("FacebookUserID", $user_profile['id'])->first();
					if($member) {
						return $this->redirect(Controller::join_links("facebook", "login"));
					} else {
						$access_token = Session::get("fb_" . $facebookApp->FacebookConsumerKey . "_access_token");
						$valid = $member->connectFacebookAccount($user_profile, $access_token);
						if($valid->valid()) {
							if($isNew) {
								$form->sessionMessage("You have signed up with Facebook.", "good");
								$this->extend("onAfterFacebookSignup", $member);
							} else {
								$form->sessionMessage("You have connected your Facebook account.", "good");
								$this->extend("onAfterFacebookConnect", $member);
							}
						} else {
							$form->sessionMessage($valid->message(), "bad");
						}
					}
				} else {
					$form->sessionmessage("Unable to retreive your Facebook account.", "bad");
				}
			}
		} else {
			$form->sessionMessage("You must be logged in to connect your Facebook account.", "bad");
		}
		return $this->renderWith(array("FacebookController", "Page", "Controller"));
	}

	/**
	 * This will disconnect a members' Facebook account from their SS account.
	 *
	 * @param $request SS_HTTPRequest
	 * @return SS_HTTPResponse
	**/
	public function disconnect($request) {
		$form = $this->Form();
		$member = Member::currentUser();

		if($member) {
			$member->disconnectFacebookAccount();
			$this->extend("onAfterFacebookDisconnect");
		}
		$form->sessionMessage("You have disconnected your account.", "good");

		return $this->renderWith(array("FacebookController", "Page", "Controller"));
	}


	/**
	 * Log the user in via an existing Facebook account connection.
	 *
	 * @return SS_HTTPResponse
	**/
	public function login() {
		$form = $this->Form();
		
		if($this->request->getVar("error")) {
			$form->sessionMessage("Unable to obtain access to Facebook.", "bad");
			return $this->renderWith(array("FacebookController", "Page", "Controller"));
		}

		$facebookApp = FacebookApp::get()->first();
		if(!$facebookApp || !$facebookApp->EnableFacebookLogin) {
			$form->sessionMessage("Facebook Login is disabled.", "bad");
		} else {
			if($member = Member::currentUser())
				$member->logOut();

			$facebook = $facebookApp->getFacebook();
			$user = $facebook->getUser();
			if($user) {
				$member = Member::get()->filter("FacebookUserID", $user)->first();
				if($member) {
					$member->logIn();
					$form->sessionMessage("You have logged in with your Facebook account.", "good");
					$member->extend("onAfterMemberLogin");
				} else if ($facebookApp->EnableFacebookSignup) {
					// Attempt to sign the user up.
					$member = new Member();

					// Load the user from Faceook
					$user_profile = $facebook->api("/me");
					if($user_profile) {
						// Fill in the required fields.
						$access_token = Session::get("fb_" . $facebookApp->FacebookConsumerKey . "_access_token");
						$signup = $member->connectFacebookAccount($user_profile, $access_token, $facebookApp->config()->get("required_user_fields"));
						if($signup->valid()) {
							$member->logIn();
							$form->sessionMessage("You have signed up with your Facbeook account.", "good");

							// Facebook Hooks
							$this->extend("onAfterFacebookSignup", $member);
						} else {
							$form->sessionMessage($signup->message(), "bad");
						}
					} else {
						$form->sessionMessage("Unable to load your Facbeook account.", "bad");
					}
				} else {
					$form->sessionMessage("Unable to log in with Facebook.", "bad");
				}
			} else {
				$params  = $facebookApp->getLoginUrlParams();
				$url = $facebook->getLoginUrl($params);
				if($url) {
					return $this->redirect($url, 302);
				} else {
					$form->sessionMessage("Unable to login to Facebook at this time.", "bad");
				}
			}
		}

		// Extend Failed facebook login
		if(!Member::currentUser()) $this->extend("onAfterFailedFacebookLogin");

		return $this->renderWith(array("FacebookController", "Page", "Controller"));
	}
}

