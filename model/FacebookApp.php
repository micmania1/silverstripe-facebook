<?php

/**
 * Facebook Application
 * 
 * @package silverstripe-facebook
 * @subpackage model
**/
class FacebookApp extends DataObject implements TemplateGlobalProvider {

	/**
	 * Stores the current instance of Facebook
	 *
	 * @var Facebook
	**/
	protected $facebook;

	static $db = array(
		"EnableFacebookLogin" => "Boolean",
		"EnableFacebookSignup" => "Boolean",
		"FacebookConsumerKey" => "Varchar(255)",
		"FacebookConsumerSecret" => "Varchar(255)",
	);

	static $defaults = array(
		"EnableFacebookLogin" => true,
		"EnableFacebookSignup" => true,
	);

	/**
	 * A list of permissions that will be requested a user logs in.
	 * {@link https://developers.facebook.com/docs/reference/login/#permissions}
	 * 
	 * @var array
	**/
	static $permissions = array();

	/**
	 * Retuired Fields for signup when querying $facebook->api("/me")
	 *
	 * @var array
	**/
	static $required_user_fields = array();

	

	/**
	 * Return Global variables for use in templates.
	 *
	 * @return array
	**/
	static public function get_template_global_variables() {
		return array
		(
			"FacebookConnectURL" => "connect_url",
			"FacebookLoginURL" => "login_url",
			"FacebookDisconnectURL" => "disconnect_url"
		);
	}
	

	/**
	 * Returns a URL for logged in users to connect their Facebook accounts
	 *
	 * @return string URL
	**/
	static public function connect_url() {
		return Controller::join_links("facebook", "connect");
	}
	

	/**
	 * Returns a URL for users to login with their facebook accounts.
	 *
	 * @return string URL or false when Login is disabled
	**/
	static public function login_url() {
		$facebook = FacebookApp::get()->first();
		if($facebook && $facebook->EnableFacebookLogin == 1)
			return Controller::join_links("facebook", "login");
		return false;
	}
	
	
	/**
	 * Return a URL to allow users to disassociate their Facebook accounts.
	 *
	 * @return string URL
	**/
	static public function disconnect_url() {
		return Controller::join_links("facebook", "disconnect");
	}


	public function getCMSFields() {
		$fields = new FieldList();
		$fields->push(
			new TabSet("Root",
				new Tab("Main",
					HeaderField::create("Application Settings", 3),
					TextField::create("FacebookConsumerKey", "Consumer Key"),
					PasswordField::create("FacebookConsumerSecret", "Consumer Secret"),
					OptionsetField::create("EnableFacebookLogin", "Facebook Login", array(
						0 => "Disabled",
						1 => "Enabled"
					)),
					OptionsetField::create("EnableFacebookSignup", "Facebook Signup", array(
						0 => "Disabled",
						1 => "Enabled"
					))
				)
			)
		);
		$this->extend("updateCMSFields", $fields);
		return $fields;
	}
	

	/**
	 * Setup a default Facebook app
	**/
	public function requireDefaultRecords() {
		$facebook = FacebookApp::get()->count();
		if(!$facebook) {
			$facebook = FacebookApp::create();
			$facebook->write();
		}
	}


	/**
	 * Creates and returns an instance of Facebook if the Consumer key & secret are set.
	 *
	 * @return Facebook or false
	**/    
	public function getFacebook() {
		if($this->facebook) {
			return $this->facebook;
		}
	
		if($this->FacebookConsumerKey && $this->FacebookConsumerSecret) {
			return $this->facebook = new Facebook(array(
				"appId" => $this->FacebookConsumerKey,
				"secret" => $this->FacebookConsumerSecret
			));
		}
		return false;
	}



	/**
	 * Returns the parameters to pass to Facebook->getLoginUrl()
	 *
	 * @return string array
	**/
	public function getLoginUrlParams() {
		return array(
			"scope" => array_values($this->config()->get("permissions"))
		);
	}

}
