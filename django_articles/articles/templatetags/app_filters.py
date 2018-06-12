from django import template
from django.conf import settings
from datetime import date, timedelta
register = template.Library()

@register.simple_tag
def settings_value(name):
    return getattr(settings, name, "")
