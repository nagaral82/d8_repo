import Route from '@ember/routing/route';
import { get } from '@ember/object';
export default Route.extend({
	ajaxActions: Ember.inject.service('ajax-actions'),
	actions: {
		createArticle() {
			var $form = $('#create-article');
			var $api_data = $form.serializeArray();
			this.get('ajaxActions').postAPI(this.get('ajaxActions').django_api_article_post, $api_data);
			$form.trigger("reset");
		},
	},
});
