<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing;

use OC\AppFramework\Utility\SimpleContainer;
use OCA\Files_Sharing\Controllers\ShareController;
use \OCP\AppFramework\App;

class Application extends App {


	public function __construct(array $urlParams=array()){
		parent::__construct('files_sharing', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('ShareController', function(SimpleContainer $c) {
			return new ShareController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('ServerContainer')->getUserSession(),
				$c->query('ServerContainer')->getAppConfig(),
				$c->getCoreApi(),
				$c->query('ServerContainer')->getUrlGenerator(),
				$c->query('ServerContainer')->getUserManager(),
				$c->query('ServerContainer')->getLogger()
			);
		});
	}


}
