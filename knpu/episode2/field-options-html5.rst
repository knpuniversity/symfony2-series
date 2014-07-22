Registration Validation
=======================

Let's add some validation. What if I don't enter a valid email address or
I choose a username that's already taken? Right now, the form would submit
just fine, which is lame.

HTML5 Validation
----------------

Try to submit a blank form right now! Woh! We *do* have some validation.
In fact, if I enter an invalid email address, we see another error. This
is HTML5 validation. When I inspect a field, we see what's triggering it:

.. code-block:: text

    <input type="email" id="user_register_email" name="user_register[email]" required="required" />

First, each field has a ``required`` attribute, which tells an HTML5-compliant
browser to throw an error if the field is left blank. Second, the input ``type=email``
field tells the browser to expect a valid email address instead of any string.

We got an input ``type=email`` field because we're using the ``email`` field
type in our form. But where are these ``required`` attributes coming from?

Field Options
-------------

To answer that, look at the third argument when adding a field: the
options array::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    $builder->add('email', 'email', array(
        // an array of options to pass to this field
    ))

Every field type can be configured in different ways. For example, the ``repeated``
field has a ``type`` option. There are also a bunch of options that
every field has, ``required`` being one of them. Set the ``required`` option
on email to false and refresh::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    $builder
        // ...
        ->add('email', 'email', array(
            'required' => false
        ))
        // ...
    ;

When we inspect, we see that the ``required`` attribute is gone. All fields
have this option, and it's important to realize that it defaults to ``true``.

There are a lot more options available for each field type. The easiest way
to learn about them is via the documentation. Remember that `Form Field Type Reference`_
page we saw earlier? Yep, it shows you all the field types *and* all of their
options.

Digging into the Core
~~~~~~~~~~~~~~~~~~~~~

You can also dig into the source code to find out what options are available
For the ``repeated`` type, there is a class called, well,
:symfonyclass:`Symfony\\Component\\Form\\Extension\\Core\\Type\\RepeatedType`.
The ``setDefaultOptions`` method shows you the options that are special
to this type.

Most of the global options are inherited from a class called
:symfonyclass:`Symfony\\Component\\Form\\Extension\\Core\\Type\\FormType`.
In here you can see ``required`` and a few others. In its parent class,
:symfonyclass:`Symfony\\Component\\Form\\Extension\\Core\\Type\\BaseType`,
we see a few more, like ``label`` and ``attr``. We can use these to customize
the label or add a class to the field from right inside the form class::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    $builder
        ->add('email', 'email', array(
            'required' => false,
            'label' => 'Email Address',
            'attr'    => array('class' => 'C-3PO')
        ))
        // ...
    ;

When we refresh, we see these options working for us.

Disabling HTML5 Validation
--------------------------

HTML5 validation is nice, but not enough: we still need server-side validation.
Also, you can't really customize how HTML5 validation looks or its messages
easily. And finally, Symfony automatically defaults all fields to have the
``required`` attribute, which is kind of annoying.

I recommend avoiding HTML5 validation entirely. To disable it, just add a
``novalidate`` attribute to your ``form`` tag:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}

    {# ... #}
    <form action="{{ path('user_register') }}" method="POST" novalidate="novalidate">

Refresh the form and try to submit empty. We get a *huge* error from the database
which proves that HTML5 validation is off! Now let's add some server-side
validation!

.. _`Form Field Type Reference`: http://symfony.com/doc/current/reference/forms/types.html
