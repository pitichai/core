<?php
/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC_JSON::checkAdminUser();

$l = OC_L10N::get('settings');

$categories = array(
	array('id' => 0, 'displayName' => (string)$l->t('Installed') ),
	array('id' => 1, 'displayName' => (string)$l->t('Not Installed') ),
);

if(OC_Config::getValue('appstoreenabled', true)) {
	// apps from external repo via OCS
	$ocs = OC_OCSClient::getCategories();
	foreach($ocs as $k => $v) {
		$categories[] = array(
			'id' => $k,
			'displayName' => str_replace('ownCloud ', '', $v)
		);
	}
}

OCP\JSON::success($categories);
