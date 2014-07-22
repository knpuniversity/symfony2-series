Using More Fields: email and repeated
=====================================

We have just one password box, so lets turn this into 2 boxes by using the
``repeated`` field type. Let's also change the ``email`` field to be an
``email`` type::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    public function registerAction()
    {
        $form = $this->createFormBuilder()
            ->add('username', 'text')
            ->add('email', 'email')
            ->add('password', 'repeated', array(
                'type' => 'password',
            ))
            ->getForm()
        ;

        // ..
    }

The ``repeated`` field type is special because it actually renders *two*
fields, in this case, password fields. If the two values don't match,
a validation error will show up. If you refresh, you'll see the 2 fields.
Oh no, attack of the clones!

The ``email`` field looks the same, but if you inspect it, you'll see that
it's an input ``email`` field, an HTML5 field type that should be used:

.. code-block:: text

    <input type="email" ... />

Head over to Symfony's documentation and go into the `Reference Section`_.
There, you'll find a page called `Form Field Type Reference`_. This is awesome:
it shows you *all* of the built-in field types and the options you can pass
to each. For example, if you click ``repeated``, it shows you how to customize
the error message that shows up if the fields don't match and some other
stuff. Use this section to your advantage!

The Repeated Fields and "Compound" fields
-----------------------------------------

Now look back at our 2 password fields. This highlights a very special aspect
about the way forms work. Specifically, a single field may in fact be one
or *many* fields:

.. code-block:: text

    <div>
        <!-- -->
        <input type="password" id="form_password_first" name="form[password][first]" required="required" />
    </div>

    <div>
        <!-- -->
        <input type="password" id="form_password_second" name="form[password][second]" required="required" />
    </div>

When you use the `repeated field type`_, it creates two sub-fields called
"first" and "second". To see what I'm talking about, replace the ``form_row``
that renders the ``password`` field with two lines: one that renders the
first box and one that renders the second:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    {{ form_row(form.username) }}
    {{ form_row(form.email) }}
    {{ form_row(form.password.first) }}
    {{ form_row(form.password.second) }}

    {# ... #}

.. note::

    When a field is actually several fields, it's called a compound field.

When we refresh, we see exactly the same thing. I just wanted to highlight
how ``password`` is really now *two* fields, and we can render them individually
or both at once.

If this feels confusing, don't worry! This concept is a little bit more advanced.

Customizing Field Labels
------------------------

Since "first" and "second" are, well, terrible labels, let's change them!
One way to do this is by adding a second argument to ``form_row`` and passing
a ``label`` key:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    {{ form_row(form.password.first, {
        label: 'Password'
    }) }}

    {{ form_row(form.password.second, {
        label: 'Repeat Password'
    }) }}

Refresh! Much better!

.. _`Reference Section`: http://symfony.com/doc/current/reference/index.html
.. _`Form Field Type Reference`: http://symfony.com/doc/current/reference/forms/types.html
.. _`repeated field type`: http://symfony.com/doc/current/reference/forms/types/repeated.html
