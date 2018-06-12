import DS from 'ember-data';

const { Model, attr } = DS;

export default Model.extend({
    title: attr("string"),
    content: attr("string"),
	author: attr("string"),
	created_at: attr("string"),
	num_vote_up: attr("number")
});
