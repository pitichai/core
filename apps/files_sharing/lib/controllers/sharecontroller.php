<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 9/11/14, 9:41 AM
 * @link http:/www.clarkt.com
 * @copyright Clark Tomlinson © 2014
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\Controllers;


use OC;
use OC\Files\Filesystem;
use OC_Files;
use OC_Util;
use OCP\App;
use OCP\JSON;
use OCP\Share;

class ShareController {
	/**
	 * @param $args
	 * @throws OC\NeedsUpdateException
	 */
	public static function showShare($args) {
		OC_Util::checkAppEnabled('files_sharing');

		$token = $args['token'];

		\OC_App::loadApp('files_sharing');
		\OC_User::setIncognitoMode(true);

		require_once \OC_App::getAppPath('files_sharing') . '/public.php';
	}

	/**
	 * @param $args
	 * @throws OC\NeedsUpdateException
	 */
	public static function downloadShare($args) {
		if (is_array($args)) {
			$path = self::getPath($args['token']);
		} else {
			$path = self::getPath($args);
		}

		OC_Util::checkAppEnabled('files_sharing');

		\OC_App::loadApp('files_sharing');
		\OC_User::setIncognitoMode(true);

		if (!App::isEnabled('files_encryption')) {
			// encryption app requires the session to store the keys in
			OC::$server->getSession()->close();
		}


		$basePath = $path;
		if (isset($_GET['path']) && Filesystem::isReadable($basePath . $_GET['path'])) {
			$getPath = Filesystem::normalizePath($_GET['path']);
			$path .= $getPath;
		}

		$dir = dirname($path);
		$file = basename($path);
		// Download the file
		if (!App::isEnabled('files_encryption')) {
			// encryption app requires the session to store the keys in
			OC::$server->getSession()->close();
		}
		if (isset($_GET['files'])) { // download selected files
			$files = urldecode($_GET['files']);
			$files_list = json_decode($files);
			// in case we get only a single file
			if ($files_list === null) {
				$files_list = array($files);
			}
			OC_Files::get($path, $files_list, $_SERVER['REQUEST_METHOD'] == 'HEAD');
		} else {
			OC_Files::get($dir, $file, $_SERVER['REQUEST_METHOD'] == 'HEAD');
		}
	}

	/**
	 * @param $token
	 * @return null|string
	 */
	public static function getPath($token) {
		$linkItem = Share::getShareByToken($token, false);
		$path = null;
		if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
			// seems to be a valid share
			$rootLinkItem = Share::resolveReShare($linkItem);
			if (isset($rootLinkItem['uid_owner'])) {
				JSON::checkUserExists($rootLinkItem['uid_owner']);
				OC_Util::tearDownFS();
				OC_Util::setupFS($rootLinkItem['uid_owner']);
				$path = Filesystem::getPath($linkItem['file_source']);
			}
		}
		return $path;
	}
}