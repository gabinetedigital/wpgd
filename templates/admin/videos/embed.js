{{ callback }}([{% for i in sources %}
{ content_type: '{{ i.format|raw }}', url: '{{ i.url|raw }}' }
{% if not loop.last %},{% endif %}
{% endfor %}]);
