/* global Handlebars */

OC.Settings = OC.Settings || {};
OC.Settings.Apps = OC.Settings.Apps || {

	State: {
		currentCategory: null
	},

	loadCategories: function() {
		var categories = [
			{displayName: 'Installed', id: '0'}
		];

		var source   = $("#categories-template").html();
		var template = Handlebars.compile(source);
		var html = template(categories);
		$('#apps-categories').html(html);

		OC.Settings.Apps.loadCategory(0);

		$.ajax(OC.generateUrl('settings/apps/categories'), {
			data:{},
			type:'GET',
			success:function (jsondata) {
				var html = template(jsondata);
				$('#apps-categories').html(html);
			},
			complete: function() {
				$('#app-navigation').removeClass('icon-loading');
			}
		});

	},

	loadCategory: function(categoryId) {
		if (OC.Settings.Apps.State.currentCategory === categoryId) {
			return;
		}
		$('#apps-list')
			.addClass('icon-loading')
			.html('');
		$('#app-category-' + OC.Settings.Apps.State.currentCategory).removeClass('active');
		OC.Settings.Apps.State.currentCategory = categoryId;
		$('#app-category-' + OC.Settings.Apps.State.currentCategory).addClass('active');

		$.ajax(OC.generateUrl('settings/apps/list?category={categoryId}', {
			categoryId: categoryId
		}), {
			data:{},
			type:'GET',
			success:function (apps) {
				var source   = $("#app-template").html();
				var template = Handlebars.compile(source);

				_.each(apps.apps, function(app) {
					var html = template(app);
					$('#apps-list').append(html);

					var page = $('#app-' + app.id);
					// set group select properly
					if(OC.Settings.Apps.isType(app, 'filesystem') || OC.Settings.Apps.isType(app, 'prelogin') ||
						OC.Settings.Apps.isType(app, 'authentication') || OC.Settings.Apps.isType(app, 'logging')) {
						page.find(".groups-enable").hide();
						page.find("label[for='groups_enable-"+app.id+"']").hide();
						page.find(".groups-enable").attr('checked', null);
					} else {
						$('#group_select > option').each(function (i, el) {
							app.groups = app.groups || [];
							if (app.groups.length === 0 || app.groups.indexOf(el.value) >= 0) {
								$(el).attr('selected', 'selected');
							} else {
								$(el).attr('selected', null);
							}
						});
						if (app.active) {
							if (app.groups.length) {
								$('#group_select').multiSelect();
								page.find(".groups-enable").attr('checked','checked');
							} else {
								page.find(".groups-enable").attr('checked', null);
							}
							page.find(".groups-enable").show();
							page.find("label[for='groups_enable']").show();
						} else {
							page.find(".groups-enable").hide();
							page.find("label[for='groups_enable']").hide();
						}
					}

				});
			},
			complete: function() {
				$('#apps-list').removeClass('icon-loading');
			}
		});
	},

	isType: function(app, type){
		return app.types && app.types.indexOf(type) !== -1;
	}
};

$(document).ready(function () {
	OC.Settings.Apps.loadCategories();

	$(document).on('click', 'ul#apps-categories li', function () {
		var categoryId = $(this).data('categoryId');

		OC.Settings.Apps.loadCategory(categoryId);
	});

	$(document).on('change', ".groups-enable", function() {
		if (this.checked) {
			$(this).parent().find("div.multiselect").parent().remove();
			$(this).parent().find('#group_select').multiSelect();
		} else {
			$(this).parent().find('#group_select').hide().val(null);
			$(this).parent().find("div.multiselect").parent().remove();
		}

		$(this).parent().find('#group_select').change();
	});

});
