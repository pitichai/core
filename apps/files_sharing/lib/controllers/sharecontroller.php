<?php
/**
 * @author Clark Tomlinson <clark@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @copyright 2014 Clark Tomlinson & Lukas Reschke
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\Controllers;

use OC;
use OC\Files\Filesystem;
use OC_Files;
use OC_Util;
use OCP\Template;
use OCP;
use OCP\JSON;
use OCP\Share;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\IApi;
use OC\URLGenerator;
use OC\AppConfig;
use OCP\ILogger;
use OCA\Files_Sharing\Helper;
use OCP\User;

class ShareController extends Controller {

	protected $userSession;
	protected $appConfig;
	protected $api;
	protected $urlGenerator;
	protected $userManager;
	protected $logger;

	public function __construct($appName,
								IRequest $request,
								OC\User\Session $userSession,
								AppConfig $appConfig,
								IApi $api,
								URLGenerator $urlGenerator,
								OC\User\Manager $userManager,
								ILogger $logger) {
		parent::__construct($appName, $request);

		$this->userSession = $userSession;
		$this->appConfig = $appConfig;
		$this->api = $api;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->logger = $logger;
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 * @param string|null $password
	 *
	 * TODO: Password protection
	 * TODO: Have shared directories working
	 * TODO: Have downloads properly working
	 * TODO: Redirect old links to the new one
	 * @return TemplateResponse
	 */
	public function showShare($token, $password = null) {
		\OC_User::setIncognitoMode(true);

		// Check whether sharing is enabled
		if (!$this->isSharingEnabled()) {
			return new TemplateResponse('core', '404', null, 'guest');
		}

		$linkItem = Share::getShareByToken($token, false);

		// Check whether share exists
		if($linkItem === false) {
			return new TemplateResponse('core', '404', null, 'guest');
		}

		// Check whether user is authenticated

		$type = $linkItem['item_type'];
		$fileSource = $linkItem['file_source'];
		$shareOwner = $linkItem['uid_owner'];
		$path = null;
		$rootLinkItem = Share::resolveReShare($linkItem);

		if (isset($rootLinkItem['uid_owner'])) {
			// Check if user still exists
			if($this->userManager->get($rootLinkItem['uid_owner']) === null) {
				return new TemplateResponse('core', '404', null, 'guest');
			}
			OC_Util::tearDownFS();
			OC_Util::setupFS($rootLinkItem['uid_owner']);
			$path = \OC\Files\Filesystem::getPath($linkItem['file_source']);
		}

		if (isset($path)) {
			if (!isset($linkItem['item_type'])) {
				$this->logger->error('No item type set for share id: ' . $linkItem['id'], array('app' => $this->appName));
				return new TemplateResponse('core', '404', null, 'guest');
			}
		}

		$tmplValue = array();
		$tmplValue['mimetype'] = \OC\Files\Filesystem::getMimeType($path);
		$tmplValue['filename'] = basename($linkItem['file_target']);
		$tmplValue['directoryPath'] = $linkItem['file_target'];
		$tmplValue['sharingToken'] = $linkItem['token'];
		$tmplValue['downloadURL'] = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.downloadshare', array('token' => $tmplValue['sharingToken'])));
		$tmplValue['server2serversharing'] = Helper::isOutgoingServer2serverShareEnabled();
		$tmplValue['protected'] = isset($linkItem['share_with']) ? 'true' : 'false';
		$tmplValue['displayName'] = User::getDisplayName($shareOwner);

		if(\OC\Files\Filesystem::is_dir($path)) {
			// FOR FOLDERS ONLY --- START
			$maxUploadFilesize = OCP\Util::maxUploadFilesize($path);
			$freeSpace = OCP\Util::freeSpace($path);
			$uploadLimit = OCP\Util::uploadLimit();

			$folder = new Template('files', 'list', '');
			$folder->assign('dir', $path);
			$folder->assign('dirToken', $linkItem['token']);
			$folder->assign('permissions', OCP\PERMISSION_READ);
			$folder->assign('isPublic', true);
			$folder->assign('publicUploadEnabled', 'no');
			$folder->assign('files', array());
			$folder->assign('uploadMaxFilesize', $maxUploadFilesize);
			$folder->assign('uploadMaxHumanFilesize', OCP\Util::humanFileSize($maxUploadFilesize));
			$folder->assign('freeSpace', $freeSpace);
			$folder->assign('uploadLimit', $uploadLimit); // PHP upload limit
			$folder->assign('usedSpacePercent', 0);
			$folder->assign('trash', false);
			$tmplValue['folder'] = $folder->fetchPage();
			return new TemplateResponse($this->appName, 'public-folder', $tmplValue, 'base');
		}

		return new TemplateResponse($this->appName, 'public-single', $tmplValue, 'base');
	}

	/**
	 * Someone wants to reset their password:
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 */
	public function downloadShare($token) {
		/*if (is_array($args)) {
			$path = self::getPath($args['token']);
		} else {
			$path = self::getPath($args);
		}*/
		\OC_User::setIncognitoMode(true);

		$path = self::getPath($token);

		// Check whether sharing is enabled
		if (!$this->isSharingEnabled()) {
			return new TemplateResponse('core', '404', null, 'guest');
		}

		$basePath = $path;
		if (isset($_GET['path']) && Filesystem::isReadable($basePath . $_GET['path'])) {
			$getPath = Filesystem::normalizePath($_GET['path']);
			$path .= $getPath;
		}

		$dir = dirname($path);
		$file = basename($path);
		OC_Files::get($dir, $file, $_SERVER['REQUEST_METHOD'] == 'HEAD');
	}

	/**
	 * Check whether sharing is enabled
	 * @return bool
	 */
	private function isSharingEnabled() {
		// FIXME: Check whether the files_sharing app is enabled, this is currently done here since the route is globally defined
		if(!$this->api->isAppEnabled($this->appName)) {
			return false;
		}

		// Check whether public sharing is enabled
		if($this->appConfig->getValue('core', 'shareapi_allow_links', 'yes') !== 'yes') {
			return false;
		}

		return true;
	}

	/**
	 * @param $token
	 * @return null|string
	 */
	private function getPath($token) {
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
