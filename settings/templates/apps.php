<script id="categories-template" type="text/x-handlebars-template">
{{#each this}}
	<li id="app-category-{{id}}" data-category-id="{{id}}"><a>{{displayName}}</a></li>
{{/each}}
</script>

<script id="app-template" type="text/x-handlebars">
	<div class="section" id="app-{{id}}">
	{{#if preview}}
	<div class="app-image{{#if previewAsIcon}} app-image-icon{{/if}}">
		<img src="{{preview}}" class="svg" />
	</div>
	{{/if}}
	<h2 class="app-name"><a href="{{detailpage}}" target="_blank">{{name}}</a></h2>
	<div class="app-version"> {{version}}</div>
	<div class="app-author"><?php p($l->t('by')); ?> {{author}}
		{{#if license}}
		({{license}}-<?php p($l->t('licensed')); ?>)
		{{/if}}
	</div>
	<div class="app-score">{{{score}}}</div>
	<div class="app-detailpage"></div>
	<div class="app-description"><pre>{{description}}</pre></div>
	<div class="app-changed">{{changed}}</div>
	{{#if documentation}}
	<p class="documentation">
		<?php p($l->t("Documentation:"));?>
		<span class="userDocumentation appslink">
		<a id='userDocumentation' href='{{{documentation.user}}}' target="_blank"><?php p($l->t("User Documentation:"));?></a>
		</span>
		<span class="adminDocumentation appslink">
		<a id='adminDocumentation' href='{{{documentation.admin}}}' target="_blank"><?php p($l->t("Admin Documentation:"));?></a>
		</span>
	</p>
	{{/if}}
	{{#if update}}
	<input class="update" type="submit" value="<?php p($l->t('Update to {{update}}')); ?>" data-appid="{{id}}" />
	{{/if}}
	{{#if active}}
	<input class="enable" type="submit" data-appid="{{id}}" data-active="true" value="<?php p($l->t("Disable"));?>"/>
	{{else}}
	<input class="enable" type="submit" data-appid="{{id}}" data-active="false" value="<?php p($l->t("Enable"));?>"/>
	{{/if}}

	</div>
</script>

<div id="app-navigation" class="icon-loading">
	<ul id="apps-categories">

	</ul>
</div>
<div id="app-content" class="icon-loading">
	<div id="apps-list"></div>
</div>
