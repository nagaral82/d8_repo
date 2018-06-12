from django.db import models
from datetime import datetime
from vote.models import VoteModel
# Create your models here.
class Articles(VoteModel, models.Model):
    title = models.CharField(max_length=200)
    author = models.CharField(max_length=200)
    content = models.TextField()
    created_at = models.DateTimeField(default=datetime.now, blank=True)
    def __str__(self):
        return self.title
    class Meta:
        verbose_name_plural = "Articles"