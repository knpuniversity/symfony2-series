Adding form-control to the input
================================

Look back at the Bootstrap docs. Every input field should have a ``form-control``
class. Cool, let's override something else! In ``form_div_layout.html.twig``,
the block we want is called ``form_widget``:

.. code-block:: html+jinja

    {# vendor/symfony/symfony/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig #}
    {# ... #}

    {% block form_widget %}
    {% spaceless %}
        {% if compound %}
            {{ block('form_widget_compound') }}
        {% else %}
            {{ block('form_widget_simple') }}
        {% endif %}
    {% endspaceless %}
    {% endblock form_widget %}

A compound field is one that is actually several fields, like the repeated
password we're using on this form. When each individual field is actually
rendered, ``form_widget_simple`` is used.

Copy the block into ``form_theme.html.twig``.

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_widget_simple %}
        {% spaceless %}
            {% set type = type|default('text') %}
            <input type="{{ type }}" {{ block('widget_attributes') }} {% if value is not empty %}value="{{ value }}" {% endif %}/>
        {% endspaceless %}
    {% endblock form_widget_simple %}

One of the variables floating around right now is an array called ``attr``.
And if it has a ``class`` key, that'll be printed out by the ``widget_attributes``
block. Let's add our class to this variable. The code leverages the heck
out of Twig. I know it looks strange:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_widget_simple %}
        {% spaceless %}
            {% set attr = attr|merge({ 'class': (attr.class|default('') ~ ' form-control')|trim }) %}
            {% set type = type|default('text') %}
            <input type="{{ type }}" {{ block('widget_attributes') }} {% if value is not empty %}value="{{ value }}" {% endif %}/>
        {% endspaceless %}
    {% endblock form_widget_simple %}

Before we try this, open up the ``login.css`` file in ``UserBundle`` and
remove the form-related styles:

.. code-block:: css

    /* src/Yoda/UserBundle/Resources/public/css/login.css */
    /* ... */

    .login article h1 {
        margin-top: 0;
        font-family:Arial;
    }

    /* Remove everything after this */

Yes, this will make our login page terrible-looking, but we can add some
Bootstrap classes on *that* form later manually, since it doesn't use the
form component.

Refresh! Cool! Things are looking better and better.

Adding a Class to the Label
---------------------------

Let's do one more thing! The labels *also* need a class: ``control-label``.
This should be getting easy now. Find the ``form_label`` block in ``form_div_layout.html.twig``
but *don't* copy it. Instead, add a blank ``form_label`` block to our template:

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_label %}
    {% endblock form_label %}

Of course, if we refresh now, the label disappears completely. I want to
add a class to the label, but I'd rather not have to copy the *entire* ``form_label``
block - it's kind of big!

Instead, we can *call* the parent block from inside our template. First, 
add a Twig ``use`` tag that points at ``form_div_layout.html.twig``:

    {# app/Resources/views/form_theme.html.twig #}
    {% use 'form_div_layout.html.twig' with form_label as base_form_label %}
    
    {# ... #}

Now, we can call the parent block inside ``form_label``:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_label %}
        {{ block('base_form_label') }}
    {% endblock form_label %}

Refresh! The labels are back. I know, we're doing craziness with blocks.
This is something you'll only see with forms.

But it's also cool! To add a class, just modify the ``label_attr`` variable,
just like we did with ``attr``:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_label %}
        {% set label_attr = attr|merge({ 'class': (attr.class|default('') ~ ' control-label')|trim }) %}

        {{ block('base_form_label') }}
    {% endblock form_label %}

Hey! Now the labels are red, and they will be for *every* form on the site.

Want to know more? You're crazy! Ok, we'll see more cool stuff next. But
there's also a `cookbook article`_.

The Block Names (e.g. form_row versus textarea_widget)
------------------------------------------------------

So far, we've been able to guess which block renders which piece of the form.
But there's a science to it.

First, there are 4 parts to any field:

1) label
2) widget
3) errors
4) row

So when you're customizing part of a field, you're always cusotmizing one
of these four. That's important because each block name *ends* in the
part being modified. 

The first part of the block name is the "field type" that you used when building
your form. Field types are the things like ``text``, ``email``, ``repeated``
and ``password``.

Let's put this together. What is the block name to render the "widget" for
a "textarea" field type?

Answer? ``textarea_widget``. And if you search in Symfony's base template,
you'll find this block.

+------------+------------+-----------------+
| Field type | Which part | Block name      |
+------------+------------+-----------------+
| textarea   | widget     | textarea_widget |
+------------+------------+-----------------+

So to customize the ``errors`` of a ``textarea`` field, you'd look for a
``textarea_errors`` block. Ah, it doesn't exist!

But there *is* ``form_errors`` block. Symfony looks for ``textarea_errors``
first. And if it doesn't find it, it falls back to ``form_errors``.

+------------+------------+-----------------+
| Field type | Which part | Block name      |
+------------+------------+-----------------+
| textarea   | widget     | textarea_widget |
+------------+------------+-----------------+
| textarea   | errors     | form_errors     |
+------------+------------+-----------------+

Tweak all the things! Just find the right block, copy it into your template,
use the variables and customize it.

.. _`cookbook article`: http://symfony.com/doc/current/cookbook/form/form_customization.html
