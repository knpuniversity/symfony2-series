Error Formatting for Twitter Bootstrap
======================================

Submit the form with some bad data. Oh, it's terrible. The errors, they're
so ugly. We must fix this.

Go back to ``form_div_layout.html.twig``. We don't know which block renders
errors, but if you search for the word "errors", you'll find it: ``form_errors``.

Copy it into our template:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}    

    {% block form_errors %}
        {% if errors|length > 0 %}
        <ul>
            {% for error in errors %}
                <li>{{ error.message }}</li>
            {% endfor %}
        </ul>
        {% endif %}
    {% endblock form_errors %}

Here's the plan. Give the ``ul`` a ``help-block`` class. This class is from
Twitter Bootstrap:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_errors %}
        {% if errors|length > 0 %}
        <ul class="help-block">
            {% for error in errors %}
                <li>{{ error.message }}</li>
            {% endfor %}
        </ul>
        {% endif %}
    {% endblock form_errors %}

Refresh. It's a very minor improvement, but we've at least modified our second
form block. I'll leave the bullet point, but if you want to add some CSS
to get rid of it, be my guest. It *is* ugly.

Next, let's see if we can highlight the error message in red. Hardcode
a ``has-error`` field to the div in ``form_row``:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}

    {% block form_row %}
        <div class="form-group has-error">
            {{ form_label(form) }}
            {{ form_errors(form) }}
            {{ form_widget(form) }}
        </div>
    {% endblock form_row %}

Refresh. This worked, we have red error text but in a second
this class is also going to turn the fields red. But we don't
want every field to always look like an emergency, so what can we do?

Form Variables: The Holy Grail of Form Rendering Control
--------------------------------------------------------

Inside the ``form_errors`` block, we have access to some ``errors``
variable. In fact, in each block we have access to a bunch of variables,
like ``label``, ``value``, ``name``, ``full_name`` and ``required``.

Let's use a trick to see *all* of the variables we have access to in ``form_errors``:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_errors %}
        {{ dump(_context|keys) }}

        {% if errors|length > 0 %}
        <ul class="help-block">
            {% for error in errors %}
                <li>{{ error.message }}</li>
            {% endfor %}
        </ul>
        {% endif %}
    {% endblock form_errors %}

.. tip::

    ``dump`` is a Twig debugging function, like ``var_dump``. You can pass
    it any variable to print it out.

Refresh! For each field, you now see a giant list - for me, 27 things. *All*
of these are variables that you magically have access to inside a form theme
block. And the variables are the same no matter what block you're in.

Remove the ``dump`` call. So we can finally use the ``errors`` variable in ``form_row``
to *only* print the class if the field has errors:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_row %}
        <div class="form-group {{ errors|length > 0 ? 'has-error' : '' }}">
            {{ form_label(form) }}
            {{ form_errors(form) }}
            {{ form_widget(form) }}
        </div>
    {% endblock form_row %}
    {# ... #}

Re-submit, fill in some fields correctly. Cool, we still see the red
errors, but the other fields are missing this class. That's awesome.
