<?php
/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC_JSON::checkAdminUser();

$l = OC_L10N::get('settings');

$category = intval($_GET['category']);
$apps = array();

switch($category) {
	// installed apps
	case 0:
		$apps = \OC_App::listAllApps(true);
		$apps = array_filter($apps, function($app) {
			return $app['active'];
		});
		break;
	// not-installed apps
	case 1:
		$apps = \OC_App::listAllApps(true);
		$apps = array_filter($apps, function($app) {
			return !$app['active'];
		});
		break;
	default:
		$apps = \OC_App::getAppstoreApps('approved', $category);
		if (!$apps) {
			$apps = array();
		}
		break;
}


OCP\JSON::success(array("apps" => $apps));
