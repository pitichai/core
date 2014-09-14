<?php
/** @var $l OC_L10N */
/** @var $_ array */

OCP\Util::addScript('files', 'file-upload');
OCP\Util::addStyle('files_sharing', 'public');
OCP\Util::addStyle('files_sharing', 'mobile');
OCP\Util::addScript('files_sharing', 'public');
OCP\Util::addScript('files', 'fileactions');
OCP\Util::addScript('files', 'jquery.iframe-transport');
OCP\Util::addScript('files', 'jquery.fileupload');
OCP\Util::addStyle('files', 'files');
OCP\Util::addStyle('files', 'upload');
OCP\Util::addScript('files', 'filesummary');
OCP\Util::addScript('files', 'breadcrumb');
OCP\Util::addScript('files', 'files');
OCP\Util::addScript('files', 'filelist');
OCP\Util::addscript('files', 'keyboardshortcuts');
?>

<div id="notification-container">
	<div id="notification" style="display: none;"></div>
</div>

<input type="hidden" id="filesApp" name="filesApp" value="1">
<input type="hidden" id="isPublic" name="isPublic" value="1">
<input type="hidden" name="dir" value="true" id="dir">
<input type="hidden" name="downloadURL" value="<?php p($_['downloadURL']) ?>" id="downloadURL">
<input type="hidden" name="sharingToken" value="<?php p($_['sharingToken']) ?>" id="sharingToken">
<input type="hidden" name="filename" value="<?php p($_['filename']) ?>" id="filename">
<input type="hidden" name="mimetype" value="<?php p($_['mimetype']) ?>" id="mimetype">
<header><div id="header" class="<?php p((isset($_['folder']) ? 'share-folder' : 'share-file')) ?>">
		<a href="<?php print_unescaped(link_to('', 'index.php')); ?>"
		   title="" id="owncloud">
			<div class="logo-wide svg"></div>
		</a>
		<div id="logo-claim" style="display:none;"><?php p($theme->getLogoClaim()); ?></div>
		<div class="header-right">
			<span id="details">
				<?php
				if ($_['server2serversharing']) {
					?>
					<span id="save" data-protected="<?php p($_['protected']) ?>"
						  data-owner="<?php p($_['displayName']) ?>" data-name="<?php p($_['filename']) ?>">
					<button id="save-button"><?php p($l->t('Add to your ownCloud')) ?></button>
					<form class="save-form hidden" action="#">
						<input type="text" id="remote_address" placeholder="example.com/owncloud"/>
						<button id="save-button-confirm" class="icon-confirm svg"></button>
					</form>
				</span>
				<?php } ?>
				<a href="<?php p($_['downloadURL']); ?>" id="download" class="button">
					<img class="svg" alt="" src="<?php print_unescaped(OCP\image_path('core', 'actions/download.svg')); ?>"/>
					<span id="download-text"><?php p($l->t('Download'))?></span>
				</a>
			</span>
		</div>
	</div></header>
<div id="content">
	<div id="preview">
		<?php print_unescaped($_['folder']); ?>
	</div>

</div>
<footer>
	<p class="info">
		<?php print_unescaped($theme->getLongFooter()); ?>
	</p>
</footer>
