<script id="categories-template" type="text/x-handlebars-template">
{{#each this}}
	<li id="app-category-{{id}}" data-category-id="{{id}}"><a>{{displayName}}</a></li>
{{/each}}
</script>

<script id="app-template" type="text/x-handlebars">
	<div class="section" id="app-{{id}}">
	<div>
		<img src="{{preview}}"></img>
	</div>
	<h2>{{name}}</h2>
	<div>{{version}}</div>
	<div>{{author}}</div>
	<div>{{license}}</div>
	<div>{{detailpage}}</div>
	<div><pre>{{description}}</pre></div>
	<div>{{changed}}</div>
	<div>{{{score}}}</div>
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
	</div>
</script>

<div id="app-navigation" class="icon-loading">
	<ul id="apps-categories">

	</ul>
</div>
<div id="app-content" class="icon-loading">
</div>
