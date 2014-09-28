<?php
/**
 * @author Clark Tomlinson <clark@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @copyright 2014 Clark Tomlinson & Lukas Reschke
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
use OCP\Template;
use OCP;
use OCP\JSON;
use OCP\Share;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\IApi;
use OC\URLGenerator;
use OC\AppConfig;
use OCP\ILogger;
use OCA\Files_Sharing\Helper;
use OCP\User;

/**
 * Class ShareController
 *
 * @package OCA\Files_Sharing\Controllers
 */
class ShareController extends Controller {

	protected $userSession;
	protected $appConfig;
	protected $config;
	protected $api;
	protected $urlGenerator;
	protected $userManager;
	protected $logger;

	/***
	 * @param string $appName
	 * @param IRequest $request
	 * @param OC\User\Session $userSession
	 * @param AppConfig $appConfig
	 * @param IApi $api
	 * @param URLGenerator $urlGenerator
	 * @param OC\User\Manager $userManager
	 * @param ILogger $logger
	 */
	public function __construct($appName,
								IRequest $request,
								OC\User\Session $userSession,
								AppConfig $appConfig,
								OCP\IConfig $config,
								IApi $api,
								URLGenerator $urlGenerator,
								OC\User\Manager $userManager,
								ILogger $logger) {
		parent::__construct($appName, $request);

		$this->userSession = $userSession;
		$this->appConfig = $appConfig;
		$this->config = $config;
		$this->api = $api;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->logger = $logger;
	}


	/**
	 * Checks whether the current user is allowed to access the shared file
	 * @param string $shareID
	 * @return bool
	 */
	private function isAuthenticated($shareID) {
		if ($this->userSession->getSession()->exists('public_link_authenticated')
			&& $this->userSession->getSession()->get('public_link_authenticated') === $shareID) {
			return true;
		}
		return false;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 *
	 * @return TemplateResponse|RedirectResponse
	 */
	public function showAuthenticate($token) {
		$linkItem = Share::getShareByToken($token, false);

		if($this->isAuthenticated($linkItem['id'])) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.showShare', array('token' => $token)));
		}

		return new TemplateResponse($this->appName, 'authenticate', null, 'guest');
	}

	/**
	 * @PublicPage
	 *
	 * Authenticates against password-protected shares
	 * @param $password
	 * @param $token
	 * @return RedirectResponse|TemplateResponse
	 */
	public function authenticate($password = '', $token) {
		$linkItem = Share::getShareByToken($token, false);
		if($linkItem === false) {
			return new TemplateResponse('core', '404', null, 'guest');
		}

		if ($linkItem['share_type'] == OCP\Share::SHARE_TYPE_LINK) {
			$forcePortable = (CRYPT_BLOWFISH != 1);
			$hasher = new \PasswordHash(8, $forcePortable);
			if (!($hasher->CheckPassword($password.$this->config->getSystemValue('passwordsalt', ''),
				$linkItem['share_with']))) {
				return new TemplateResponse($this->appName, 'authenticate', array('wrongpw' => true), 'guest');
			} else {
				// Save item id in session for future requests
				$this->userSession->getSession()->set('public_link_authenticated', $linkItem['id']);
			}
		} else {
			$this->logger->error('Unknown share type '.$linkItem['share_type']
				.' for share id '.$linkItem['id'], array('app' => $this->appName));
			header('HTTP/1.0 404 Not Found');
			return new TemplateResponse('core', '404', null, 'guest');
		}

		return new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.showShare', array('token' => $token)));
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 *
	 * TODO: Refactor properly
	 * @return TemplateResponse
	 */
	public function showShare($token) {
		\OC_User::setIncognitoMode(true);

		// Check whether share exists
		$linkItem = Share::getShareByToken($token, false);
		if($linkItem === false) {
			return new TemplateResponse('core', '404', null, 'guest');
		}

		$linkItem = OCP\Share::getShareByToken($token, false);
		if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
			// seems to be a valid share
			$shareOwner = $linkItem['uid_owner'];
			$path = null;
			$rootLinkItem = OCP\Share::resolveReShare($linkItem);
			if (isset($rootLinkItem['uid_owner'])) {
				OCP\JSON::checkUserExists($rootLinkItem['uid_owner']);
				OC_Util::tearDownFS();
				OC_Util::setupFS($rootLinkItem['uid_owner']);
				$path = \OC\Files\Filesystem::getPath($linkItem['file_source']);
			}
		}

		// Share is password protected - check whether the user is permitted to access the share
		if (isset($linkItem['share_with'])) {
			if(!$this->isAuthenticated($linkItem['id'])) {
				return new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.authenticate',
					array('token' => $token)));
			}
		}

		if (isset($_GET['path']) && Filesystem::isReadable($path . $_GET['path'])) {
			$getPath = Filesystem::normalizePath($_GET['path']);
			$path .= $getPath;
		} else {
			$getPath = '';
		}
		$dir = dirname($path);
		$file = basename($path);

		$shareTmpl = array();
		$shareTmpl['displayName'] = \OCP\User::getDisplayName($shareOwner);
		$shareTmpl['filename'] = $file;
		$shareTmpl['directory_path'] = $linkItem['file_target'];
		$shareTmpl['mimetype'] = \OC\Files\Filesystem::getMimeType($path);
		$shareTmpl['dirToken'] = $linkItem['token'];
		$shareTmpl['sharingToken'] = $token;
		$shareTmpl['server2serversharing'] = Helper::isOutgoingServer2serverShareEnabled();
		$shareTmpl['protected'] = isset($linkItem['share_with']) ? 'true' : 'false';

		// Show file list
		if (\OC\Files\Filesystem::is_dir($path)) {
			$shareTmpl['dir'] = $getPath;
			$files = array();
			$maxUploadFilesize=OCP\Util::maxUploadFilesize($path);

			$freeSpace=OCP\Util::freeSpace($path);
			$uploadLimit=OCP\Util::uploadLimit();
			$folder = new OCP\Template('files', 'list', '');
			$folder->assign('dir', $getPath);
			$folder->assign('dirToken', $linkItem['token']);
			$folder->assign('permissions', OCP\PERMISSION_READ);
			$folder->assign('isPublic', true);
			$folder->assign('publicUploadEnabled', 'no');
			$folder->assign('files', $files);
			$folder->assign('uploadMaxFilesize', $maxUploadFilesize);
			$folder->assign('uploadMaxHumanFilesize', OCP\Util::humanFileSize($maxUploadFilesize));
			$folder->assign('freeSpace', $freeSpace);
			$folder->assign('uploadLimit', $uploadLimit); // PHP upload limit
			$folder->assign('usedSpacePercent', 0);
			$folder->assign('trash', false);
			$shareTmpl['folder'] = $folder->fetchPage();
		} else {
			$shareTmpl['dir'] = $dir;
		}
		$shareTmpl['downloadURL'] = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.downloadShare', array('token' => $token));

		return new TemplateResponse($this->appName, 'public', $shareTmpl, 'base');
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $token
	 * @param string $files
	 * @return RedirectResponse If the user is not authenticated
	 */
	public function downloadShare($token, $files = null) {
		\OC_User::setIncognitoMode(true);

		$linkItem = OCP\Share::getShareByToken($token, false);

		// Share is password protected - check whether the user is permitted to access the share
		if (isset($linkItem['share_with'])) {
			if(!$this->isAuthenticated($linkItem['id'])) {
				return new RedirectResponse($this->urlGenerator->linkToRoute('files_sharing.sharecontroller.authenticate',
					array('token' => $token)));
			}
		}

		if (!$this->api->isAppEnabled('files_encryption')) {
			// encryption app requires the session to store the keys in
			$this->userSession->getSession()->close();
		}

		$path = self::getPath($token);

		if (!is_null($files)) { // download selected files
			$files_list = json_decode($files);
			// in case we get only a single file
			if ($files_list === NULL ) {
				$files_list = array($files);
			}
			OC_Files::get($path, $files_list, $_SERVER['REQUEST_METHOD'] == 'HEAD');
		} else {
			OC_Files::get(dirname($path), basename($path), $_SERVER['REQUEST_METHOD'] == 'HEAD');
		}
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
