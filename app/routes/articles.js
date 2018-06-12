import Route from '@ember/routing/route';

export default Route.extend({
	ajaxActions: Ember.inject.service('ajax-actions'),

	model(){
        return this.store.findAll("article");
    },
	actions: {
		upVote(id) {
			var $api_data = { id: id };
			this.get('ajaxActions').postAPI(this.get('ajaxActions').django_api_vote_post, $api_data);
		},
	},
});
