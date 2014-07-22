Form Rendering
==============

Create an HTML ``form`` tag that submits right back to the same route and
controller we just created. The easiest way to render a form is all at once
by using a special ``form_widget`` Twig function. Give it the form variable
that we passed into the template, and add a submit button:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    {% block body %}
    <section class="main-block">
        <article>
            <section>
                <h1>Register</h1>

                <form action="{{ path('user_register') }}" method="POST">
                    {{ form_widget(form) }}

                    <input type="submit" class="btn btn-primary pull-right" value="Register!" />
                </form>
            </section>
        </article>
    </section>
    {% endblock %}

Form versus FormView
~~~~~~~~~~~~~~~~~~~~

Refresh the page. Woh man, another error!


    An exception has been thrown during the rendering of a template ("Catchable Fatal
    Error: Argument 1 passed to Symfony\Component\Form\FormRenderer::searchAndRenderBlock()
    must be an instance of Symfony\Component\Form\FormView, instance of Symfony\Component\Form\Form
    given, called in ...")

This one is more difficult to track down, but it *does* have one important
detail: Specifically:

    Argument 1 passed to FormRenderer::searchAndRenderBlock() must be an instance
    of FormView, instance of Form given

That's a lot of words but it means that somewhere, we're calling a method
and passing it the wrong type of object. It's expecting a ``FormView``, but
we're passing it a ``Form``. Something is wrong with the form we created.
Head back to ``RegisterController`` to fix this. Whenever you pass a form
to a template, you *must* call ``createView`` on it. This transforms the object
from a ``Form`` into a ``FormView``::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    public function registerAction()
    {
        // ...

        return array('form' => $form->createView());
    }

This isn't important... just remember to do it. The error is a bit
tough, but now you know it!

Refresh now and celebrate. We have a fully operational and rendred form.
Actually, we haven't written any code to handle the form submit - that'll
come in a minute.

Being event Lazier with form() and button
-----------------------------------------

There's an even *easier* way to render the form, and while I don't
love it, I want you to see it. Head to the `Forms Chapter`_ of the docs.
Under the `Rendering the Form`_ section, it renders it with just one line:

.. code-block:: html+jinja

    {{ form(form) }}

This displays the fields, the HTML form tag and even a submit button, if you
configure one. If you scroll up, you'll see that the form has a ``save`` field 
that's a ``submit`` type:

.. code-block:: html+jinja

    $form = $this->createFormBuilder($task)
        // ...
        ->add('save', 'submit')
        ->getForm();

I'm happy rendering the HTML form tags and the submit buttons myself, but
you will see this rendering syntax, and I don't want you to be confused.
It's all basically doing the same thing.

Rendering the Form one Field at a Time
--------------------------------------

In reality, rendering the form all at once probably won't be flexible enough
in most cases. To render each field individually, use the ``form_row`` function
on each of your fields:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    <form action="{{ path('user_register') }}" method="POST">
        {{ form_row(form.username) }}
        {{ form_row(form.email) }}
        {{ form_row(form.password) }}

        <input type="submit" class="btn btn-primary pull-right" value="Register!" />
    </form>

Refresh the page and inspect the form. Each field row is surrounded by a ``div``
and contains the label and input:

.. code-block:: html

    <div>
        <label for="form_username" class="required">Username</label>
        <input type="text" id="form_username" name="form[username]" required="required" />
    </div>
    <!-- ... -->

Using form_widget, form_label and form_errors
---------------------------------------------

In the next screencast, we'll learn how to customize how a field row is rendered.
But even now, we can take more control by using the ``form_label``, ``form_widget``
and ``form_errors`` functions individually. Let's try it on *just* the username
field:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    <form action="{{ path('user_register') }}" method="POST">
        <div class="awesome-username-wrapper">
            {{ form_errors(form.username) }}
            {{ form_label(form.username) }}
            {{ form_widget(form.username) }}
        </div>

        {{ form_row(form.email) }}
        {{ form_row(form.password) }}

        <input type="submit" class="btn btn-primary pull-right" value="Register!" />
    </form>

``form_row`` just renders these 3 parts automatically, so this is basically
the same as before. I usually try to use ``form_row`` whenever possible, so
let's change the ``username`` back to use this.

Don't forget form_errors and form_rest!
---------------------------------------

Apart from the fields themselves, there are two other things that should be
in every form. First, make sure you call ``form_errors`` on the entire form
object:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    <form action="{{ path('user_register') }}" method="POST">
        {{ form_errors(form) }}

        {{ form_row(form.username) }}
        {{ form_row(form.email) }}
        {{ form_row(form.password) }}

        <input type="submit" class="btn btn-primary pull-right" value="Register!" />
    </form>

Most errors appear next to the field they belong to. But in some cases,
you might have a "global" error that doesn't apply to any one specific field.
It's not common, but this takes care of rendering those.

Next, add ``form_rest``. It renders any fields that you forgot:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    <form action="{{ path('user_register') }}" method="POST">
        {{ form_errors(form) }}

        {{ form_row(form.username) }}
        {{ form_row(form.email) }}
        {{ form_row(form.password) }}

        {{ form_rest(form) }}

        <input type="submit" class="btn btn-primary pull-right" value="Register!" />
    </form>

In addition to that, ``form_rest`` is really handy because it renders any
hidden fields automatically.

*All* forms have a hidden "token" field by default to protect against
CSRF attacks. With ```form_rest```, you never have to worry or think about
hidden fields.

We talk more about these functions in future episodes, but under the reference
section of Symfony's documentation, there's a page called
`Twig Template Form Function and Variable Reference`_ that mentions all of
these functions and how to use them.

.. _`Forms Chapter`: http://symfony.com/doc/current/book/forms.html
.. _`Rendering the Form`: http://symfony.com/doc/current/book/forms.html#rendering-the-form
.. _`Twig Template Form Function and Variable Reference`: http://symfony.com/doc/current/reference/forms/twig_reference.html
