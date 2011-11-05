{{ callback }}([{% for i in sources %}
{ content_type: '{{ i.format }}', url: '{{ i.url }}' }
{% if not loop.last %},{% endif %}
{% endfor %}]);
