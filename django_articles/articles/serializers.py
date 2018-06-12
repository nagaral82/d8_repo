from rest_framework import serializers
from . models import Articles
from django.contrib.auth.models import User
from django.shortcuts import get_object_or_404
class ArticlesSerializer(serializers.ModelSerializer):
    class Meta:
        model = Articles
        fields = '__all__'
        #fields = ['id', 'firstname']
    #def validate_author(self, author):
        #users = get_object_or_404(User, username=author)
        #return users

