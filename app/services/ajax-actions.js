import Service from '@ember/service';
import $ from 'jquery';
export default Service.extend({
	django_api_article_post: 'http://localhost:7003/articles/api/v1/create/',
	django_api_vote_post: 'http://localhost:7003/articles/api/v1/upvote/',
	postAPI(api_url, article_data) {
            $.ajax({
              method: "POST",
              url: api_url,
              global: true,
              beforeSend: function( xhr,  settings) {
                    //xhr.setRequestHeader("X-CSRFToken",  $CSRF_TOKEN);
                    $('#loading-indicator-'+article_data.id).addClass('glyphicon-refresh glyphicon-refresh-animate');

              },
               complete: function( xhr,  textStatus) {
                    $('#loading-indicator-'+article_data.id).removeClass('glyphicon-refresh glyphicon-refresh-animate');
                },
              data: article_data
            })
		  .done(function( msg ) {
			//console.log(msg);
			$('.alert').addClass('alert-success').html(msg.msg);
			if (msg.hasOwnProperty('id') && msg.hasOwnProperty('count')) {
				$('#data-count-article-id-'+msg.id).html('('+msg.count+')');
			}
		  })
		  .fail(function(jqXHR, textStatus, errorThrown) {
				//console.log(jqXHR);
				$('.alert').addClass('alert-danger').html(jqXHR.statusText+' ('+jqXHR.status+')');

		});
    }
});
