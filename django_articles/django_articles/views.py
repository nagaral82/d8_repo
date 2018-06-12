from django.shortcuts import redirect
from django.views.generic.base import TemplateView

def index_redirect(request):
    return  redirect('/articles/')

class HomePageView(TemplateView):

    template_name = "index.html"

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context['latest_articles'] = "test"
        return context

