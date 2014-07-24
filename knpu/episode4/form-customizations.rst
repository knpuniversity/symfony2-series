Form Customizations
===================

In part 2 of the series, we built a registration form, handled the form submit,
and even customized the way some of our fields look. The form component is
one of the most powerful parts of Symfony, and we'll see some of that now
as we learn how to customize how our forms are rendered.

Where Form Markup comes from
----------------------------

Open up the ``register.html.twig`` template that renders the registration
form. Twig's ``form_row`` function is responsible for rendering the label,
input widget and any errors. And with a few other Twig functions, we can render
each part individually. But where does the markup come from? How does Symfony
know what an input text field looks like or that it should render errors in
a ``ul`` element?

The answer lives deep inside Symfony, in a file called `form_div_layout.html.twig`_.

.. tip::

    The location of this file is deep inside Symfony in the vendor directory:

    vendor/symfony/symfony/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig

This is a strange little file that contains a lot of different blocks, and
each renders a different form element. There's a block for input fields, labels,
errors, and everything else. Every part of a form is rendered from something
inside this file.

Find the ``form_row`` block. This is what's used when we call the ``form_row``
function. Let's customize it! Of course, by now you should be reminding me
that we can't just modify this file. So, let's go with your idea and copy
this block and create a new ``forms.html.twig`` file inside the ``app/Resources/views``
directory. The name and location of this file aren't important. Now, copy
the block in.

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}

    {% block form_row %}
    {% spaceless %}
        <div>
            {{ form_label(form) }}
            {{ form_errors(form) }}
            {{ form_widget(form) }}
        </div>
    {% endspaceless %}
    {% endblock form_row %}

Adding Twitter Bootstrap
------------------------

We're going to customize it to work nicely with Twitter Bootstrap. But first,
let's upgrade Bootstrap to version 2 to make things more interesting.

.. tip::

    Even if you're using a newer version of Bootstrap, the lessons learned
    here will prepare you to customize things for any version.

First, download the zip file. Now, we can unzip it and move the whole ``bootstrap``
directory into the ``Resources/public`` directory of EventBundle:

.. code-block:: bash

    $ unzip /path/to/downloaded/bootstrap.zip
    $ mv bootstrap src/Yoda/EventBundle/Resources/public/css

Finally, head to ``base.html.twig``, remove the old URL, and add the new CSS
file to our ``stylesheets`` tag:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}
    
    {% stylesheets
        'bundles/event/css/event.css'
        'bundles/event/css/events.css'
        'bundles/event/css/main.css'
        'bundles/event/css/bootstrap/css/bootstrap.css'
        filter='?cssmin'
        output='css/generated/layout.css'
    %}
        <link rel="stylesheet" href="{{ asset_url }}" />
    {% endstylesheets %}

Remember that you should be running the ``assetic:dump`` command in the background.

.. code-block:: bash

    php app/console assetic:dump --watch --force

If you are, you'll see that it just noticed and dumped the new bootstrap file
automagically.

Customizing Form Markup: Form Theming
-------------------------------------

Back in the ``forms.html.twig`` file, add a ``control-group`` class to the
div and surround the widget and errors in another div with a ``controls``
class:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}

    {% block form_row %}
    {% spaceless %}
        <div class="control-group">
            {{ form_label(form) }}

            <div class="controls">
                {{ form_widget(form) }}
                {{ form_errors(form) }}
            </div>
        </div>
    {% endspaceless %}
    {% endblock form_row %}    

If you check out the bootstrap documentation, this is the minimum you need
to get your form styling working.

If we refresh the form now, it still looks pretty plain and isn't using our
new markup. That's because Symfony doesn't know to use our new form template
file! To fix this, open up the ``config.yml`` file and add a new ``form``
key under ``twig``:

.. code-block:: html+jinja

    # app/config/config.yml
    # ...

    twig:
        # ...
        form:
            resources:
                - "::forms.html.twig"

This takes an array of files that Symfony should use in addition to the base
template. Any blocks you list in these files will override those provided
by Symfony.

Refresh the page again. Success! It doesn't look perfect yet, but it's now
using our new block! By overriding ``form_row`` block in our new template and
telling Symfony to use it, we're now in control of how the ``form_row`` function
is rendered. This process is called "form theming". Let's also add a ``form-horizontal``
class:

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}
    
    <form ... class="form-horizontal">
    {# ... #}

This tells Bootstrap to render our labels and fields next to each other.

Customizing Error Formatting
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is cool! Let's keep going! Submit the form blank. Ok, the errors are
still pretty ugly, so let's fix those next. We don't really know yet which
blocks exactly render which piece of the form, but if you search Symfony's
core template for "errors", you'll find the block we're looking for (``form_errors``).
Copy it into our template:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}    

    {% block form_errors %}
    {% spaceless %}
        {% if errors|length > 0 %}
        <ul>
            {% for error in errors %}
                <li>{{ error.message }}</li>
            {% endfor %}
        </ul>
        {% endif %}
    {% endspaceless %}
    {% endblock form_errors %}

Replace the ``ul`` with a span and remove the ``li`` elements:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}

    {% block form_errors %}
    {% spaceless %}
        {% if errors|length > 0 %}
        <span>
            {% for error in errors %}
                {{ error.message }}
            {% endfor %}
        </span>
        {% endif %}
    {% endspaceless %}
    {% endblock form_errors %}

This uses Bootstrap's ``help-inline`` class. Now when we submit, the first
two errors look great! And we can do even better. Hard-code an "error" class
into the form row div and refresh:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}

    {% block form_row %}
    {% spaceless %}
        <div class="control-group error">
            {# ... #}
        </div>
    {% endspaceless %}
    {% endblock form_row }
    {# ... #}

When the error class is present, Bootstrap highlights the field in red. But
how can we make this only show up when it's needed?

Form Variables: The Holy Grail of Form Rendering Control
--------------------------------------------------------

Notice in the ``form_errors`` block that we have access to an ``errors`` variable.
In fact, in each of these blocks, you have access to a bunch of variables,
including ``label``, ``value``, ``name``, ``full_name`` and ``required``,
to name a few of the most common ones.

.. tip::

    To see these magic variables you have, you can temporarily add them to
    the bottom of your ``form_errors`` block:

    .. code-block

        {# app/Resources/views/forms.html.twig #}
        {# ... #}

        {% block form_errors %}
            {# ... #}

            {# place this code temporarily in this block to see the variable values #}
            Label: {{ label }}<br/>
            Value: {{ value }}<br/>
            Name: {{ name }}<br/>
            Full Name: {{ full_name }}<br/>
            Required: {{ required }}
        {% endblock form_errors %}

        This would print the following next to - for example - the ``password``
        field:

        .. code-block:: text
        
            Label: Password
            Value: 
            Name: first
            Full Name: user_register[plainPassword][first]
            Required: 1

You can see how each of these variables describe different parts of the field
that's being rendered. These variables are available everywhere, regardless
of whether we're rendering the errors, the label, or the "row" of a field.
In the ``form_row`` block, we can check the length of the ``errors`` array
to see if we should render the ``error`` class:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}

    {% block form_row %}
    {% spaceless %}
        <div class="control-group{{ errors|length > 0 ? ' error' : '' }}">
            {# ... #}
        </div>
    {% endspaceless %}
    {% endblock form_row }
    {# ... #}

Refresh the page to check that it's working.

Customizing the Field Label Markup
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Finally, let's look at the label. To make Twitter Bootstrap happy, I'd like
to add a ``control-label`` class to it. This will make the labels appear
a bit more inline with the field itself. Let's follow the exact same process
we used for the row and errors. First, find the right block in Symfony's base
template. By searching for ``label``, we can find it easily (``form_label``).
Second, copy this block into our template:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}

    {% block form_label %}
    {% spaceless %}
        {% if label is not sameas(false) %}
            {% if not compound %}
                {% set label_attr = label_attr|merge({'for': id}) %}
            {% endif %}
            {% if required %}
                {% set label_attr = label_attr|merge({'class': (label_attr.class|default('') ~ ' required')|trim}) %}
            {% endif %}
            {% if label is empty %}
                {% set label = name|humanize %}
            {% endif %}
            <label{% for attrname, attrvalue in label_attr %} {{ attrname }}="{{ attrvalue }}"{% endfor %}>{{ label|trans({}, translation_domain) }}</label>
        {% endif %}
    {% endspaceless %}
    {% endblock form_label %}

And finally, customize it. One way to do this is to just add a new class attribute.
But, this isn't a great idea. One variable that's available here is ``label_attr``,
which is an array of attributes for the label. If there is already a ``class``
attribute, our label will have two. Instead, we'll copy a nice piece of code
which merges the class we want into the ``class`` attribute:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}
            
    {# add just this one line #}
    {% set label_attr = label_attr|merge({'class': (label_attr.class|default('') ~ ' control-label')|trim}) %}

    <label{% for attrname, attrvalue in label_attr %} {{ attrname }}="{{ attrvalue }}"{% endfor %}>{{ label|trans({}, translation_domain) }}</label>

Take a closer look at this line later - it's a pretty cool example of some
nice Twig filters.

When you refresh the page again, you'll see the labels move into place because
of the new class. Nice!

Thanks to Twitter Bootstrap and some form customizations, things are starting
to look great. We'll show a few more cool customizations tricks in the next
section, but there's also a `cookbook article`_ covering some of these features.

The Significance of the Block Names (e.g. form_row versus textarea_widget)
--------------------------------------------------------------------------

One of the trickiest things about form rendering is knowing which block to
override. Fortunately, there's some basic logic behind this.

First, there are only 4 parts of any field:

1) label
2) widget
3) errors
4) row

When you're customizing part of a form field, you're always customizing one
of these four pieces. This is important because the block name ends in the
part being modified. In our example, we've modified ``form_row``, ``form_errors``,
and ``form_label``.

The first part of the block name is the "field type", which is what you used
when building your form. Field types include ``text``, ``email``, ``repeated``,
``password`` and a few more.

If we put this together, we can see how the blocks are named. For example,
what is the block name to render the "widget" for a "textarea" field type?
The answer is ``textarea_widget``. And if you search in Symfony's base template,
you'll find this block.

+------------+------------+-----------------+
| Field type | Which part | Block name      |
+------------+------------+-----------------+
| textarea   | widget     | textarea_widget |
+------------+------------+-----------------+

So, to customize the ``errors`` of a ``textarea`` field, you might think that
you need to find the ``textarea_errors`` block. But no such block exists.
Instead, we only have a ``form_errors`` block. Symfony first looks for a
``textarea_errors`` block, but if it doesn't find it, it then looks for ``form_errors``.

+------------+------------+-----------------+
| Field type | Which part | Block name      |
+------------+------------+-----------------+
| textarea   | widget     | textarea_widget |
+------------+------------+-----------------+
| textarea   | errors     | form_errors     |
+------------+------------+-----------------+

.. note::

    If a ``textarea_errors`` block existed, then it would be used instead
    of ``form_errors`` for textarea errors.

This is because of field "inheritance": the "textarea" type "extends" the
"form" type. In fact, almost every type extends the "form" type, which is
why so many important blocks start with ``form_``. To see the parent of a
field, just check out its reference documentation.

And that's it! To customize any part of any form, just find the right block,
copy it into your template, use the variables that are available, and customize
it. In the next section we'll show you one more trick.

.. _`form_div_layout.html.twig`: https://github.com/symfony/symfony/blob/master/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig
.. _`cookbook article`: http://symfony.com/doc/current/cookbook/form/form_customization.html