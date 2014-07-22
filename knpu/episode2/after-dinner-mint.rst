After-dinner Mint
=================

The site is looking sweet! And now with most of the work behind us, let's
relax a little and have some fun. In this last part, we'll check out some
cool things related to forms and security.

Form Field Guessing
-------------------

Remember when we disabled HTML5 validation earlier. Let's add it back temporarily.
Remove the ``novalidate`` attribute so that it works again:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}

    {# ... #}
    <form action="{{ path('user_register') }}" method="POST">

Now, open the ``User`` class: let's do a little experimenting. Let's pretend
that the ``email`` field isn't requied. Remove the ``NotBlank`` constraint
from it and set a ``nullable=true`` option in the Doctrine metadata. Don't
worry about updating your schema - this change is just temporary::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Email
     */
    private $email;

Now, open up ``RegisterFormType`` and remove the ``required`` option, which
determines whether or not the HTML5 ``required`` attribute should be rendered::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    $builder
        // ...
        ->add('email', 'email', array(
            'label' => 'Email Address',
            'attr'    => array('class' => 'C-3PO')
        ))
        // ...
    ;

When we surf to the registration page and try to submit, HTML5 validation
stops us. And just like before, the ``email`` field has the ``required`` attribute
on it. We saw earlier that we can fix the problem by setting the ``required``
option to ``false`` for that field. But shouldn't the form be able to see
that the ``email`` field isn't required in ``User`` and set the option to
``false`` for us?

Actually, it can! The feature is called "field guessing" and it works like
this. Set the second argument of ``add`` for the ``email`` field to null::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    $builder
        // ...
        ->add('email', null, array(
            'required' => false,
            'label' => 'Email Address',
            'attr'    => array('class' => 'C-3PO')
        ))
        // ...
    ;

This might seem a little crazy, because this argument normally tells Symfony
what type of field this is. Will it be able to figure how to render this field?

Refresh the page and inspect ``email`` - there are a bunch of awesome
things happening:

.. code-block:: text

    <input type="email" id="user_register_email" name="user_register[email]" maxlength="255" />

First, notice that the field is still ``type="email"``. That's being
guessed based on the fact that there is an ``Email`` constraint on the property.
Remove the ``Email`` constraint and refresh::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

.. code-block:: text

    <input type="text" id="user_register_email" name="user_register[email]" maxlength="255" />

Symfony doesn't know anything about the field now, so it just
defaults to the ``text`` type.

Field Option Guessing
---------------------

But now, notice that the ``required`` attribute is gone. In addition to guessing
the field type, certain options are also guessed, like the ``required`` option.
Let's play with this. Add back the ``NotBlank`` constraint and refresh::

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $email;

Not surprisingly, the ``required`` attribute is back. Next, remove ``NotBlank``,
but also make the field ``not null``::

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $email;

Yep, the ``required`` attribute is *still* there. The form system guesses
that the field is required based on the fact that it's required in the database.

Even the ``maxlength`` attribute that's being rendered comes from the length
of the field in the database.

So here's the deal. If you leave the second argument empty when creating
a field, Symfony will try to guess the field type *and* some options, like
``required``, ``max_length`` and ``pattern``. Field guessing isn't
always perfect, but I tend to try it at first and explicitly set things that
aren't guessed correctly.

Let's put our 2 validation constraints back and add back the ``email`` type
option in the form and refresh::

    // src/Yoda/UserBundle/Entity/User.php
    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Email
     */
    private $email;

.. code-block:: php

    // src/Yoda/UserBundle/Form/RegisterFormType.php

    $builder
        // ...
        ->add('email', 'email')
        // ...
    ;

If you were watching closely, the ``maxlength`` attribute disappeared:

.. code-block:: text

    <input type="text" id="user_register_email" name="user_register[email]" required="required" />

This is a gotcha with guessing. As soon as you pass in the ``type`` argument,
none of the options like ``required`` or ``max_length`` are guessed anymore.
In other words, if you don't let Symfony guess the field type, it won't guess
any of the options either.
