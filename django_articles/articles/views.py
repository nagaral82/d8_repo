from django.shortcuts import render, redirect
from .models import Articles
from django.contrib import messages
from django.contrib.auth.models import User

from django.shortcuts import render, get_object_or_404
from django.http import HttpResponse
from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status
from .models import Articles
from .serializers import ArticlesSerializer
from .helpers import VoteHelpers
import requests
from django.http import JsonResponse
from django.template import loader
from django.conf import settings

# Create your views here.
# Create your views here.
users = User.objects.all()

def index(request):
    articles = Articles.objects.order_by('-num_vote_up')
    context = {
        'title': 'List',
        'articles_data': articles,
    }
    return render(request, 'articles/index.html', context)

def ajax_list(request):
    results = requests.get(getattr(settings, 'API_ARTICLE_GET_URL'))
    results.raise_for_status()
    try:
        articles_list = loader.render_to_string('articles/list.html', {'articles_list': results.json()['articles']})
        output_data = {'articles_list': articles_list}
    except requests.exceptions.HTTPError as err:
        output_data = {'error': err}
    return JsonResponse(output_data)


def create(request):
    context = {}
    uids = {}
    if (request.method == 'POST'):
        title = request.POST['title']
        author = request.POST['author']
        content = request.POST['content']
        article = Articles(title=title, author=author, content=content)
        article.save()
        messages.add_message(request, messages.INFO, 'Article created successfully.')
        return redirect('/')
    else:
        for user in users:
            uids[user.id] = user.username
        context = {'names': uids}
        return render(request, 'articles/add.html', context)

def resetvote(request):
    VoteHelpers().resetVoteCounter()
    messages.add_message(request, messages.INFO, 'Vote counter reset done for all articles!!')
    return redirect('/')


class ArticleGet(APIView):
    def get(self, request):
        articles_list = Articles.objects.order_by('-num_vote_up')
        serializer = ArticlesSerializer(articles_list, many=True)
        content = {'articles': serializer.data}
        return Response(content)

class ArticlePost(APIView):
    def post(self, request):
        serializer = ArticlesSerializer(data=request.data)
        if serializer.is_valid():
            serializer.save()
            return Response({"msg": "Article created successfully"})
            #return Response(serializer.data)
        else:
            return Response({"msg": serializer.errors, "error": True})

class votePost(APIView):
    def post(self, request):
        article_id = request.data['id']
        article = get_object_or_404(Articles, id=article_id)
        # Allow any (Anonymous) users to upvote
        VoteHelpers().upVote(article, 0)
        article.refresh_from_db()
        return Response({"id": article.id, "msg": "Article (id="+article_id+") upvoted successfully", "count": article.num_vote_up})