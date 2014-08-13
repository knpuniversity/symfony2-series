Changing and Using Form Variables 
=================================

So we know that we have access to a bunch of variables from within the form
blocks. Awesome.

Overriding Form Variables
-------------------------

Open up ``register.html.twig``. Remember that ``attr`` variable we have access
to in our form theme blocks? We can override that variable, or any other,
right when we render the field. Give the username field a clever class:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    {{ form_row(form.username, {
        'attr': { 'class': 'a-clever-class' }
    }) }}

Refresh and inspect the field to see the class. In addition to the trick
I showed you earlier, Symfony has a reference page called
`Twig Template Form Function and Variable Reference`_ that lists *most*
of these variables. Really you can customize almost anything when rendering
a field.

Adding a Help Feature
---------------------

I want to be able to add a little bit of help text beneath any form field.
I'll open ``form_theme.html.twig`` and just hardcode a message in so you
can see what I mean:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_row %}
        <div class="form-group {{ errors|length > 0 ? 'has-error' : '' }}">
            {{ form_label(form) }}
            {{ form_errors(form) }}
            {{ form_widget(form) }}

            <div class="help-block">This is the field you're looking for.</div>
        </div>
    {% endblock form_row %}

I know - it's pointless so far. The same message shows up for every field.
How can we customize this?

Inventing a New Form Variable
-----------------------------

Why not just pass in a new variable? Go back to ``register.html.twig`` and
add a ``help`` variable to the username field:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    {{ form_row(form.username, {
        'attr': { 'class': 'the-username-field' },
        'help': 'Choose something unique and clever'
    }) }}

In normal Symfony, there is no ``help`` variable - I totally just made that
up. But even though it doesn't normally exist, it *is* being passed into
the form theme blocks. So use it!

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_row %}
        <div class="form-group {{ errors|length > 0 ? 'has-error' : '' }}">
            {{ form_label(form) }}
            {{ form_errors(form) }}
            {{ form_widget(form) }}

            <div class="help-block">{{ help }}</div>
        </div>
    {% endblock form_row %}

Alright, time to try it. Woh, BIG error:

    Variable "help" does not exist in
    kernel.root_dir/Resources/views/form_theme.html.twig at line 9

I promise, I wasn't lying! The problem is that the *other* fields like email
and password *aren't* passing in this variable, so we need to code defensively
in the block. Add an ``if`` statement to make sure the variable is defined
and actually set to some real value:

.. code-block:: html+jinja

    {# app/Resources/views/form_theme.html.twig #}
    {# ... #}

    {% block form_row %}
        <div class="form-group {{ errors|length > 0 ? 'has-error' : '' }}">
            {{ form_label(form) }}
            {{ form_errors(form) }}
            {{ form_widget(form) }}

            {% if help is defined and help %}
                <div class="help-block">{{ help }}</div>
            {% endif %}
        </div>
    {% endblock form_row %}

Try it again. It works! We can pass in a ``help`` variable to *any* field
on *any* form to use this.

FormView: Customizing Form Variables from your Form Type
--------------------------------------------------------

Ok, but one more challenge. Could we set this help message from inside our
form class?

Open up ``RegisterFormType``. The ``buildForm`` method adds the fields and
``setDefaultOptions`` does exactly that. To customize the form variables
directly, create a third method called ``finishView``. I'll use my IDE to
generate this for me. Don't forget the ``use`` statements for ``FormView``
and ``FormInterface``::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...
    use Symfony\Component\Form\FormInterface;
    use Symfony\Component\Form\FormView;
    // ...

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        
    }

This method is called right before we start rendering the form. We can use
the ``FormView`` object to change any variable on any field. Use it to add
a help message to the email field::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view['email']->vars['help'] = 'Hint: it will have an @ symbol';
    }

Refresh! Yep, you're one dangerous form customizer.

.. tip::

    Most of the core built-in form view variables come from a ``FormType::buildView``
    method: http://bit.ly/sf2-form-build-view

.. _`Twig Template Form Function and Variable Reference`: http://symfony.com/doc/current/reference/forms/twig_reference.html
