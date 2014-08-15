/* global Handlebars */

Handlebars.registerHelper('score', function() {
	if(this.score) {
		var score = Math.round( this.score / 10 );
		var imageName = 'rating/s' + score + '.png';
		
		return new Handlebars.SafeString('<img src="' + OC.imagePath('core', imageName) + '">');
	}
});

OC.Settings = OC.Settings || {};
OC.Settings.Apps = OC.Settings.Apps || {

	State: {
		currentCategory: null
	},

	loadCategories: function() {
		var categories = [
			{displayName: 'Enabled', id: '0'}
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
	},

	enableApp:function(appid, active, element, groups) {
		groups = groups || [];
		var appItem = $('div#app-'+appid+'');
		element.val(t('settings','Please wait....'));
		if(active && !groups.length) {
			$.post(OC.filePath('settings','ajax','disableapp.php'),{appid:appid},function(result) {
				if(!result || result.status !== 'success') {
					if (result.data && result.data.message) {
						OC.Settings.Apps.showErrorMessage(result.data.message);
						appItem.data('errormsg', result.data.message);
					} else {
						OC.Settings.Apps.showErrorMessage(t('settings', 'Error while disabling app'));
						appItem.data('errormsg', t('settings', 'Error while disabling app'));
					}
					element.val(t('settings','Disable'));
					appItem.addClass('appwarning');
				}
				else {
					OC.Settings.Apps.removeNavigation(appid);
					appItem.fadeOut(function() {
						appItem.remove();
					});
				}
			},'json');
		} else {
			$.post(OC.filePath('settings','ajax','enableapp.php'),{appid: appid, groups: groups},function(result) {
				if(!result || result.status !== 'success') {
					if (result.data && result.data.message) {
						OC.Settings.Apps.showErrorMessage(result.data.message);
						appItem.data('errormsg', result.data.message);
					} else {
						OC.Settings.Apps.showErrorMessage(t('settings', 'Error while enabling app'));
						appItem.data('errormsg', t('settings', 'Error while disabling app'));
					}
					element.val(t('settings','Enable'));
					appItem.addClass('appwarning');
				} else {
					OC.Settings.Apps.addNavigation(appid);
					appItem.fadeOut(function() {
						appItem.remove();
					});
				}
			},'json')
				.fail(function() {
					OC.Settings.Apps.showErrorMessage(t('settings', 'Error while enabling app'));
					appItem.data('errormsg', t('settings', 'Error while enabling app'));
					appItem.data('active',false);
					appItem.addClass('appwarning');
					OC.Settings.Apps.removeNavigation(appid);
					element.val(t('settings','Enable'));
				});
		}
	},
	removeNavigation: function(appid){
		$.getJSON(OC.filePath('settings', 'ajax', 'navigationdetect.php'), {app: appid}).done(function(response){
			if(response.status === 'success'){
				var navIds=response.nav_ids;
				for(var i=0; i< navIds.length; i++){
					$('#apps ul').children('li[data-id="'+navIds[i]+'"]').remove();
				}
			}
		});
	},
	addNavigation: function(appid){
		$.getJSON(OC.filePath('settings', 'ajax', 'navigationdetect.php'), {app: appid}).done(function(response){
			if(response.status === 'success'){
				var navEntries=response.nav_entries;
				for(var i=0; i< navEntries.length; i++){
					var entry = navEntries[i];
					var container = $('#apps ul');

					if(container.children('li[data-id="'+entry.id+'"]').length === 0){
						var li=$('<li></li>');
						li.attr('data-id', entry.id);
						var img= $('<img class="app-icon"/>').attr({ src: entry.icon});
						var a=$('<a></a>').attr('href', entry.href);
						var filename=$('<span></span>');
						filename.text(entry.name);
						a.prepend(filename);
						a.prepend(img);
						li.append(a);

						// append the new app as last item in the list
						// which is the "add apps" entry with the id
						// #apps-management
						$('#apps-management').before(li);

						// scroll the app navigation down
						// so the newly added app is seen
						$('#navigation').animate({
							scrollTop: $('#navigation').height()
						}, 'slow');

						// draw attention to the newly added app entry
						// by flashing it twice
						$('#header .menutoggle')
							.animate({opacity: 0.5})
							.animate({opacity: 1})
							.animate({opacity: 0.5})
							.animate({opacity: 1})
							.animate({opacity: 0.75});

						if (!SVGSupport() && entry.icon.match(/\.svg$/i)) {
							$(img).addClass('svg');
							replaceSVG();
						}
					}
				}
			}
		});
	},
};

$(document).ready(function () {
	OC.Settings.Apps.loadCategories();

	$(document).on('click', 'ul#apps-categories li', function () {
		var categoryId = $(this).data('categoryId');

		OC.Settings.Apps.loadCategory(categoryId);
	});

	$(document).on('click', '#apps-list input.enable', function () {
		var appId = $(this).data('appid');
		var element = $(this);
		var active = $(this).data('active');

		OC.Settings.Apps.enableApp(appId, active, element);
	});

	$(document).on('change', '#group_select').change(function() {
		var element = $('#app-content input.enable');
		var groups = $(this).val();
		var appid = element.data('appid');
		if (appid) {
			OC.Settings.Apps.enableApp(appid, false, element, groups);
			var li = $('[data-id="'+appid+'"]');
			var app = OC.get('appData_' + $(li).data('id'));
			app.groups = groups;
			li.data('groups', groups);
			li.attr('data-groups', JSON.stringify(groups));
		}
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
