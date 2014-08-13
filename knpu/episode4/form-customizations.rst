Form Theming: Making Forms Pretty(ish)
======================================

Where Form Markup comes from
----------------------------

In episode 2, we built a registration form. Cool! Open up the ``register.html.twig``
template for that page. Twig's ``form_row`` function renders the label, input
widget and any errors for each field. And with a few other Twig functions,
we can render each part individually. That's all old news, way back from
`episode 2`_.

But  where does the markup actually come from? Why is the row surrounded
in a ``div`` and the errors in a ``ul``?

The answer lives deep inside Symfony, in a file called `form_div_layout.html.twig`_.
Open it up in your editor.

.. tip::

    The location of this file is deep inside Symfony in the vendor directory:

    vendor/symfony/symfony/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig

This odd little file holds a lot of blocks and each renders a different part
of the form. There's a block for input fields, labels, errors, and everything
else. Every piece of markup for a form is somewhere in here.

Customizing form_row
--------------------

Find the ``form_row`` block. I know it's shocking, but this is what's used
when we call ``form_row``.

Let's change it! You should be reminding me that we can't just modify this
file. So, let's go with your idea and copy this block and create a new
``form_theme.html.twig`` file inside ``app/Resources/views``. Copy in the
block and add your favorite tag to it, just to see if it's working:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}

    {% block form_row %}
        <marquee>It looks like it's working</marquee>
        <div>
            {{ form_label(form) }}
            {{ form_errors(form) }}
            {{ form_widget(form) }}
        </div>
    {% endblock form_row %}

To tell Symfony about this, go to ``config.yml`` and find the ``twig`` key.
Add ``form`` and ``resources`` keys and then the name of this template. Since
it lives in ``app/Resources``, we use the double-colon syntax, just like when
we reference our base template:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    twig:
        # ...
        form:
            resources:
                - "::form_theme.html.twig"

Refresh! For some reason my Marquee takes its time, but there it is! Now,
we can override *any* of the blocks from Symfony's core ``form_div_layout.html.twig``
file.

Twitter Bootstrap Form Theming
------------------------------

Let's do something useful. A few bundles exist that can help you style your
forms for Twitter Bootstrap. Just go to KnpBundles.com and look for them.

To learn a few things, we'll do some of this by hand. Find the `Bootstrap Form Docs`_.

Every field should have a ``form-group`` div around it. As cool as it is, let's
take out the marquee and give the div this class:

.. code-block:: yaml

    {# app/Resources/views/form_theme.html.twig #}

    {% block form_row %}
        <div class="form-group">
            {{ form_label(form) }}
            {{ form_errors(form) }}
            {{ form_widget(form) }}
        </div>
    {% endblock form_row %}

Refresh! It's minor, but we've got a little extra margin now. Let's keep going.

.. _`form_div_layout.html.twig`: https://github.com/symfony/symfony/blob/master/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig
.. _`cookbook article`: http://symfony.com/doc/current/cookbook/form/form_customization.html
.. _`episode 2`: http://knpuniversity.com/screencast/symfony2-ep2/form-rendering#using-form-widget-form-label-and-form-errors
.. _`Bootstrap Form Docs`: http://getbootstrap.com/css/#forms
