Server-Side Validation
======================

In Symfony, validation is done a little bit differently. Instead of validating
the submitted form data itself, validation is applied to the ``User`` object.
Let's see how this works. Start by heading to the
`validation chapter of the documentation`_. Click on the "Annotations" tab
of the code example and copy the ``use`` statement. Paste this into your
``User`` class::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    use Symfony\Component\Validator\Constraints as Assert;

Basic Constraints and Options
-----------------------------

Adding validation constraints is easy. To make the ``username`` field required,
just add ``@Assert\NotBlank``::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\Column(name="username", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $username;

Let's try it out! When we submit the form, we can see the validation error
above the field. To customize the message, add the `message` option::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\Column(name="username", type="string", length=255)
     * @Assert\NotBlank(message="Put in a username of course!")
     */
    private $username;

Refresh to see the new error.

All of this magic happens automatically when we call ``bind``. This takes
the submitted values, pushes them into the User object, and then applies
validation.

Let's keep going. The ``Length`` constraint has several options including
the minimum length and the error message. For the ``email`` property, let's
add ``NotBlank`` and ``Email`` to guarantee that it's a valid email address.
For the ``plainPassword``, we can use the ``NotBlank`` constraint and the
``Regex`` constraint to guarantee a strong password::

    /**
     * @ORM\Column(name="username", type="string", length=255)
     * @Assert\NotBlank(message="Put in a username of course!")
     * @Assert\Length(min=2, minMessage="[0,+Inf] Enter something longer!")
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Email
     */
    private $email;

    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *      pattern="/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s).*$/",
     *      message="Please use at least one upper case letter, one lower case letter, and one number"
     * )
     */
    private $plainPassword;

.. note::

    The ``[0,+Inf]`` relates to translations and pluralizations. See the
    `Translation Chapter`_ for more details.

We can see this in action by trying various values. All the errors come up
as expected, however, probably not where you want them. We'll clean these up later.

The UniqueEntity Constraint
---------------------------

Symfony comes packed with a lot of other constraints you can use. Check them
out in the `reference section of the documentation`_. Check out the
`UniqueEntity constraint`_. This constraint is useful if you need to make
sure a value stays unique in the database. Let's use it, since we need to
make sure that nobody signs up with an existing username or email address.

The :symfonyclass:`Symfony\\Bridge\\Doctrine\\Validator\\Constraints\\UniqueEntity`
constraint is special because unlike the others, this one requires its own
``use`` statement. Copy it into your ``User`` class. Also, ``@UniqueEntity``
goes above the class itself. It takes two options: the field that should be
unique followed by a message. Add a constraint for both the username and the
email::

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

If we try to register with an existing username or email, we can see the error.

The Callback Constraint
-----------------------

Before we move on, I just want to point out one more useful constraint:
`Callback`_. This constraint lets you create a method inside your class that's
called during the validation process. You can apply whatever logic you need
to in order figure out if the object is valid. We won't show it here, but
check it out.


.. _`Form Field Type Reference`: http://symfony.com/doc/current/reference/forms/types.html
.. _`validation chapter of the documentation`: http://symfony.com/doc/current/book/validation.html
.. _`Translation Chapter`: http://symfony.com/doc/current/book/translation.html
.. _`reference section of the documentation`: http://symfony.com/doc/current/reference/constraints.html
.. _`UniqueEntity constraint`: http://symfony.com/doc/current/reference/constraints/UniqueEntity.html
.. _`Callback`: http://symfony.com/doc/current/reference/constraints/Callback.html
.. _`Constraint Configuration`: http://bit.ly/sf2-validation-config
