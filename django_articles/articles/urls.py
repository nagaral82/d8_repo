from django.conf.urls import url
from django.contrib import admin
from . import views

# Web URLS - we can use it later, if needed

""""
url(r'^edit/(?P<id>\d+)$', views.edit, name='edit'),
url(r'^edit/update/(?P<id>\d+)$', views.update, name='update'),
url(r'^delete/(?P<id>\d+)$', views.delete, name='delete'),
url(r'^upvote/(?P<id>\d+)$', views.upvote, name='upvote')
url(r'^create', views.create, name='create'),
"""""

urlpatterns = [
    url(r'^$', views.index, name='articles_index'),
    # Web URLs

    url(r'^create', views.create, name='create'),
    url(r'^resetvote', views.resetvote, name='resetvote'),
    url(r'^ajax-list', views.ajax_list, name='ajax_list'),

    # API URLS
    url(r'^api/v1/create/', views.ArticlePost.as_view()),
    url(r'^api/v1/list/', views.ArticleGet.as_view()),
    url(r'^api/v1/upvote/', views.votePost.as_view()),

]
