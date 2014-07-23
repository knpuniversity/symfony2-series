Server-Side Validation
======================

In Symfony, validation is done a little bit differently. Instead of validating
the submitted form data itself, validation is applied to the ``User`` object.

Start by heading to the `validation chapter of the documentation`_. Click
on the "Annotations" tab of the code example and copy the ``use`` statement.
Paste this into your ``User`` class::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    use Symfony\Component\Validator\Constraints as Assert;

Whenever you add annotations, you need a ``use`` statement.

Basic Constraints and Options
-----------------------------

Adding a validation constraint is easy. To make the ``username`` field required,
add ``@Assert\NotBlank`` above the property::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\Column(name="username", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $username;

Try it out! When we submit the form blank, we see the validation error above
the field. It looks terrible, but we'll work on that later. To customize
the message, add the ``message`` option::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\Column(name="username", type="string", length=255)
     * @Assert\NotBlank(message="Put in a username you rebel scum :P")
     */
    private $username;

Refresh to see the new error.

All of this magic happens automatically when we call ``handleRequest`` in
our controller. This takes the submitted values, pushes them into the User
object, and then applies validation.

Add all the Constraints!
------------------------

Let's keep going. We can use the ``Length`` constraint to make sure the
``username`` is at least 3 characters long::

    /**
     * @ORM\Column(name="username", type="string", length=255)
     * @Assert\NotBlank(message="Put in a username of course!")
     * @Assert\Length(min=3, minMessage="Give us at least 3 characters!")
     */
    private $username;

For the ``email`` property, use ``NotBlank`` *and* ``Email`` to guarantee
that it's a valid email address::

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Email
     */
    private $email;

For ``plainPassword``, we can use the ``NotBlank`` constraint and the ``Regex``
constraint to guarantee a strong password::

    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *      pattern="/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s).*$/",
     *      message="Use 1 upper case letter, 1 lower case letter, and 1 number"
     * )
     */
    private $plainPassword;

Let's try this out by filling out the form in different ways. All the errors
show up! They're just really ugly.

Docs for The Built-In Constraints
---------------------------------

Symfony comes packed with a lot of other constraints you can use. Check them
out in the `reference section of the documentation`_. You can see the ``Length``
constraint we just used and all of the options for it. Cool!

The UniqueEntity Constraint
---------------------------

Check out the `UniqueEntity constraint`_. This is useful if you need to make
sure a value stays unique in the database. We need to make sure that nobody
signs up using an existing username or email address, so this is perfect.

The :symfonyclass:`Symfony\\Bridge\\Doctrine\\Validator\\Constraints\\UniqueEntity`
constraint is special because unlike the others, this one requires a different
``use`` statement. Copy it into your ``User`` class. Also, ``@UniqueEntity``
goes *above* the class, not above a property. It takes two options: the field
that should be unique followed by a message. Add a constraint for both the
username and the email::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

    /**
     * @ORM\Table(name="yoda_user")
     * @ORM\Entity(repositoryClass="Yoda\UserBundle\Entity\UserRepository")
     * @UniqueEntity(fields="username", message="That username is taken!")
     * @UniqueEntity(fields="email", message="That email is taken!")
     */
    class User implements AdvancedUserInterface, Serializable

.. tip::

    ``"username"`` is equivalent to ``fields="username"``. ``fields`` is
    the "default" option. If it's the only option you're using, saying ``fields``
    isn't needed. See `Constraint Configuration`_.

If we try to register with an existing username or email, we see the error!

The Callback Constraint
-----------------------

Before we move on, I want to show you one more useful constraint: `Callback`_.
This constraint is *awesome* because it lets you create a method inside your
class that's called during validation. You can apply whatever logic you need
to figure out if the object is valid. You can even place the errors on exactly
which field you want. If you have a more difficult validation problem, this
might be exactly what you need.

We won't show it here, but check it out.

.. _`validation chapter of the documentation`: http://symfony.com/doc/current/book/validation.html
.. _`Validation Constraints Reference`: http://symfony.com/doc/current/reference/constraints.html
.. _`UniqueEntity constraint`: http://symfony.com/doc/current/reference/constraints/UniqueEntity.html
.. _`Callback`: http://symfony.com/doc/current/reference/constraints/Callback.html
.. _`Constraint Configuration`: http://bit.ly/sf2-validation-config
.. _`reference section of the documentation`: http://symfony.com/doc/current/reference/constraints.html
