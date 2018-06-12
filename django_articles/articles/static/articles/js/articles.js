$(function() {
    // Common POST function
    function postAPI(api_url, article_data) {
            $.ajax({
              method: "POST",
              url: api_url,
              global: true,
              beforeSend: function( xhr,  settings) {
                    xhr.setRequestHeader("X-CSRFToken",  $CSRF_TOKEN);
                    $('#loading-indicator-'+article_data.id).addClass('glyphicon-refresh glyphicon-refresh-animate');

              },
               complete: function( xhr,  textStatus) {
                    $('#loading-indicator-'+article_data.id).removeClass('glyphicon-refresh glyphicon-refresh-animate');
                },
              data: article_data
            })
              .done(function( msg ) {
                console.log(msg);
                $('.messages').html('<li class="success">'+msg.msg+'</li>');
                if (msg.hasOwnProperty('id') && msg.hasOwnProperty('count')) {
                    $('#data-count-article-id-'+msg.id).html('('+msg.count+')');
                }
              })
              .fail(function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    $('.messages').html('<li class="error">'+jqXHR.statusText+' ('+jqXHR.status+')'+'</li>');

            });
    }

    // Upvote Article
    $( "#articles" ).on('click', ".article-upvote", function(event){
        var api_data = { id: $(this).attr("data-article-id") };
       // $('#loading-indicator-'+$(this).attr("data-article-id")).show();
        postAPI($API_VOTE_POST_URL, api_data);
        event.preventDefault();
    });

    // Create Article
    $( "#create-article" ).submit(function( event ) {
        var api_data = $( this ).serializeArray();
        postAPI($API_ARTICLE_POST_URL, api_data);
        $('#create-article').trigger("reset");
        event.preventDefault();
    });

        // If articles "div" is present then load the list by AJAX
        if($('#articles').length){
            $.ajax({
                  method: "GET",
                  url: '/articles/ajax-list/'
                })
                  .done(function( msg ) {
                    //$('.messages').html('<li class="success">'+msg.msg+'</li>');
                    console.log(msg);
                    $('#articles').html(msg.articles_list);
                  })
                  .fail(function(jqXHR, textStatus, errorThrown) {
                        console.log(jqXHR);
                        $('.messages').html('<li class="error">'+jqXHR.statusText+' ('+jqXHR.status+')'+'</li>');

            });
        }



});