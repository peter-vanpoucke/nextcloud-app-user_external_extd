<?php
/**
 * Copyright (c) 2017 Peter Vanpoucke <peter.vanpoucke@subport.be> / Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * User authentication via samba (smbclient)
 *
 * @category Apps
 * @package  UserExternalExtd
 * @author   Peter Vanpoucke <peter.vanpoucke@subport.be>
 * @author   Robin Appelman <icewind@owncloud.com>
 * @license  http://www.gnu.org/licenses/agpl AGPL
 * @link     http://github.com/owncloud/apps
 */
class OC_User_SMB_extd extends \OCA\user_external_extd\Base{
	private $host;

	const SMBCLIENT = 'smbclient -L';
	const LOGINERROR = 'NT_STATUS_LOGON_FAILURE';

	/**
	 * Create new samba authentication provider
	 *
	 * @param string $host Hostname or IP of windows machine
	 */
	public function __construct($host, $filters=null) {
		parent::__construct($host, $filters);
		$this->host=$host;
	}

	/**
	 * @param string $uid
	 * @param string $password
	 * @return bool
	 */
	private function tryAuthentication($uid, $password) {
		$uidEscaped = escapeshellarg($uid);
		$password = escapeshellarg($password);
		$command = self::SMBCLIENT.' '.escapeshellarg('//' . $this->host . '/dummy').' -U'.$uidEscaped.'%'.$password;
		$lastline = exec($command, $output, $retval);
		if ($retval === 127) {
			OCP\Util::writeLog(
				'user_external_extd', 'ERROR: smbclient executable missing',
				OCP\Util::ERROR
			);
			return false;
		} else if (strpos($lastline, self::LOGINERROR) !== false) {
			//normal login error
			return false;
		} else if (strpos($lastline, 'NT_STATUS_BAD_NETWORK_NAME') !== false) {
			//login on minor error
			goto login;
		} else if ($retval != 0) {
			//some other error
			OCP\Util::writeLog(
				'user_external_extd', 'ERROR: smbclient error: ' . trim($lastline),
				OCP\Util::ERROR
			);
			return false;
		} else {
			login:
			return $uid;
		}
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
		// Check with an invalid password, if the user authenticates then fail
		$attemptWithInvalidPassword = $this->tryAuthentication($uid, base64_encode($password));
		if(is_string($attemptWithInvalidPassword)) {
			return false;
		}

		// Check with valid password
		$attemptWithValidPassword = $this->tryAuthentication($uid, $password);
		if(is_string($attemptWithValidPassword)) {
			$this->storeUser($uid);
			return $uid;
		}

		return false;
	}
}

