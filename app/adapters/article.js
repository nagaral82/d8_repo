import DS from 'ember-data';

export default DS.RESTAdapter.extend({
    host: "http://localhost:7003",
    namespace: "articles/api/v1",
    pathForType(){
        return "list/";
    }
});
