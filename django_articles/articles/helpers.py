from . models import Articles
from vote.models import Vote
from django.core.exceptions import ObjectDoesNotExist
from django.contrib.auth.models import User
class VoteHelpers:
    def upVote(self, Articles, uid):
        try:
            vote = Vote.objects.get(user_id=uid, object_id=Articles.id)
            vote.action = 0
            vote.save()
            new_count = Articles.num_vote_up+1
            Articles.num_vote_score = Articles.num_vote_up = new_count
            Articles.save()
        except ObjectDoesNotExist:
            Articles.votes.up(uid)
        return Articles
    def resetVoteCounter(self):
        vote = Vote.objects.all()
        vote.delete()
        new_count = 0
        articles = Articles.objects.all()
        for article in articles:
                article.num_vote_score = article.num_vote_up = new_count
                article.save()
        return Articles


