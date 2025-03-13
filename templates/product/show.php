{% extends 'base.html.twig' %}

{% block title %}{{ product.nom }}{% endblock %}

{% block body %}
<h1>{{ product.nom }}</h1>
<img src="{{ asset('uploads/' ~ product.image) }}" alt="{{ product.nom }}">
<p>Prix : {{ product.prix }} €</p>
<p>Description : {{ product.description }}</p>

<!-- Formulaire de commentaire -->
{% if commentForm is defined %}
{{ form_start(commentForm) }}
{{ form_widget(commentForm) }}
<button type="submit">Ajouter un commentaire</button>
{{ form_end(commentForm) }}
{% endif %}
{% endblock %}