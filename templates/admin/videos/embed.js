{{ callback }}([{% for i in sources %}
{ type: '{{ i.format|raw }}', url: '{{ i.url|raw }}' }
{% if not loop.last %},{% endif %}
{% endfor %}]);
