<?php
/**
 * Copyright (c) 2017 Peter Vanpoucke <peter.vanpoucke@subport.be> / Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\user_external_extd;

class WebDavAuth extends Base {

	private $webDavAuthUrl;

	public function __construct($webDavAuthUrl, $filters=null) {
		parent::__construct($webDavAuthUrl, $filters);
		$this->webDavAuthUrl =$webDavAuthUrl;
	}

	/**
	 * Check if the password is correct without logging in the user
	 *
	 * @param string $uid      The username
	 * @param string $password The password
	 *
	 * @return true/false
	 */
	public function checkPassword($uid, $password) {
		if (!$this->checkUsername($uid))
		{
			OCP\Util::writeLog('user_external_extd', "ERROR: User '$uid' doesn't match filter(s).", OCP\Util::ERROR);
			return false;
		}
		$arr = explode('://', $this->webDavAuthUrl, 2);
		if( ! isset($arr) OR count($arr) !== 2) {
			\OCP\Util::writeLog('user_external_extd', 'Invalid Url: "'.$this->webDavAuthUrl.'" ', 3);
			return false;
		}
		list($protocol, $path) = $arr;
		$url= $protocol.'://'.urlencode($uid).':'.urlencode($password).'@'.$path;
		$headers = get_headers($url);
		if($headers==false) {
			\OCP\Util::writeLog('user_external_extd', 'Not possible to connect to WebDAV Url: "'.$protocol.'://'.$path.'" ', 3);
			return false;

		}
		$returnCode= substr($headers[0], 9, 3);

		if(substr($returnCode, 0, 1) === '2') {
			$this->storeUser($uid);
			return $uid;
		} else {
			return false;
		}
	}
}
