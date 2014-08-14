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
		$('#app-content')
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
					$('#app-content').append(html);
				});
			},
			complete: function() {
				$('#app-content').removeClass('icon-loading');
			}
		});
	}
};

$(document).ready(function () {
	OC.Settings.Apps.loadCategories();

	$(document).on('click', 'ul#apps-categories li', function () {
		var categoryId = $(this).data('categoryId');

		OC.Settings.Apps.loadCategory(categoryId);
	});

});
