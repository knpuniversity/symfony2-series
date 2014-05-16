Registration Validation
=======================

We now have a working form, but no actual validation yet. What if I don't
enter a valid email address or I choose a username that's already taken? Right
now, the form would submit just fine, which really isn't good enough.

HTML5 Validation
----------------

First, let's talk about HTML5 and client-side validation. To see what I'm
talking about, let's try to submit the form without any data. If you're using
a modern browser, you'll see an error. In fact, if I enter an invalid email 
address, we'll see another error. This is HTML5 validation. When I inspect
each element, we see what's triggering it:

.. code-block:: text

    <input type="email" id="user_register_email" name="user_register[email]" required="required" />

First, each field has a "required" attribute, which tells an HTML5-compliant
browser to throw an error if the field is left blank. Second, the input ``email``
field tells the browser to expect a valid email address instead of any string.

The input email is a result of using the ``email`` field type in our form.
But where are these ``required`` attributes coming from? To answer that, let's
look at the third argument when adding a field: the options array::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    $builder->add('username', 'text', array(
        // an array of options to pass to this field
    ))

Every field type can be configured in different ways. The ``repeated`` field
type, for example, has a required ``type`` option. There are a few options
that are shared by all fields, ``required`` being one of them. Set the ``required``
option on username to false and refresh::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    $builder->add('username', 'text', array(
        'required' => false,
    ))

When we inspect, we see that the ``required`` attribute is gone. All fields
have this option, and it's important to realize that it defaults to ``true``.

So what other options do we have? One is ``pattern``. Suppose we want to restrict
the username to only letters or numbers. By setting the pattern to a regular
expression, we can do that::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    $builder->add('username', 'text', array(
        'required' => false,
        'pattern' => '[a-zA-Z0-9]+',
    ))

Refresh the page and try a weird username to see this in action. If you like
this, check out `HTML5Pattern.com`_ for a list of other useful regular expressions.

There are a lot more options available for each field type. The easiest way
to learn about them is via the documentation. Go to the reference section
and click `Form Field Type Reference`_ for a list of all the core fields.
Each one has a list of the most important options for that type and what they
do. Unfortunately, at this time, the documentation is missing details about
several lesser-known options.

If you're ever curious about what's available, you can open up the source code.
For the ``text`` type, there is a class called, well,
:symfonyclass:`Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType`.
Most of the global options are inherited from a class called
:symfonyclass:`Symfony\\Component\\Form\\Extension\\Core\\Type\\FormType`.
In here you can see options like ``max_length``, which sets the ``maxlength``
attribute and ``read_only``, which adds the ``readonly`` attribute. There's
also a ``label`` option which controls the label. This means that you can
change the label here *or* when rendering it. The ``attr`` option is also
really great - you can use it to add a class or some other attribute to your
field::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    $builder->add('username', 'text', array(
        'required' => false,
        'pattern' => '[a-zA-Z0-9]+',
        'attr'    => array('class' => 'foob')
    ))

There are more, but it would take hours to cover them all here.

One important thing to realize is that this HTML5 validation is nice, but
not enough. Some browsers don't support it and a malicious user could always
easily bypass it. If you want to avoid HTML5 validation altogether, just
add a ``formnovalidate`` attribute to the submit button of your form:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}

    {# ... #}
    <input type="submit" value="Register!" formnovalidate />

Refresh the form to verify that all HTML5 validation is off. Now that we've
got that  out of the way, let's add some server-side validation.

Server-Side Validation
----------------------

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
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

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
~~~~~~~~~~~~~~~~~~~~~~~~~~~

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
~~~~~~~~~~~~~~~~~~~~~~~

Before we move on, I just want to point out one more useful constraint:
`Callback`_. This constraint lets you create a method inside your class that's
called during the validation process. You can apply whatever logic you need
to in order figure out if the object is valid. We won't show it here, but
check it out.

Adding a Flash Message
----------------------

Let's add two more things quickly. First, after registration, let's add a
message to tell the user that registration was successful. The best way to do
this is to set a "flash" message. A flash is a message that we set to the
session, but that only lasts for exactly one request. After registration,
grab the ``session`` object from the request, get an object called a "flash bag"
and call ``add`` to put a message on it::

    // src/Yoda/UserBundle/Entity/Controller/RegisterController.php
    // ...

    if ($form->isValid()) {
        // .. code that saves the user

        $request->getSession()
            ->getFlashBag()
            ->add('success', 'Registration went super smooth!')
        ;

        $url = $this->generateUrl('event');

        return $this->redirect($url);
    }

Open up the base layout so we can put this flash message to use. The session
object is available via ``app.session``, which we can use to check to see if
we have any ``success`` flash messages. If we do, let's print the messages
inside a styled container. You'll typically only store one message at a time,
but the flash bag is flexible enough to store any number of messages:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}

    <body>
        {% if app.session.flashBag.has('success') %}
            <div class="alert-message success">
                {% for msg in app.session.flashBag.get('success') %}
                    {{ msg }}
                {% endfor %}
            </div>
        {% endif %}

        <!-- ... -->

Automatically Authenticating after Registration
-----------------------------------------------

Before we try this out, let's also automatically log the user in after registration.
To do this, create a private function inside the controller. Normally, authentication
happens automatically, but we can also trigger it manually::

    // src/Yoda/UserBundle/Entity/Controller/RegisterController.php
    // ...

    private function authenticateUser(UserInterface $user)
    {
        $providerKey = 'secured_area'; // your firewall name
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }

This code might look a little strange, but don't worry about that now. The
basic idea is that we create an authentication package, called a token, and
pass it off to Symfony's security system. Call this method after registration
to automatically log the user in::

    // src/Yoda/UserBundle/Entity/Controller/RegisterController.php
    // ...

    if ($form->isValid()) {
        // .. code that saves the user, sets the flash message

        $this->authenticateUser($user);

        $url = $this->generateUrl('event');

        return $this->redirect($url);
    }

Head back to the browser to try the whole process. After registration, we're
redirected back to the homepage, but this time with our message. If you check
the web debug toolbar, you'll see that we're also authenticated as the new
user. Perfect, that was easy, right?!

.. sidebar:: Redirecting back to the original URL

    If you want to redirect to the page that the user was trying to request
    before being forced to register, you can take advantage of the fact that
    this URL is stored to the session::

        $key = '_security.'.$providerKey.'.target_path';
        $session = $this->getRequest()->getSession();

         // get the URL to the last page, or fallback to the homepage
         if ($session->has($key)) {
             $url = $session->get($key)
             $session->remove($key);
         } else {
             $url = $this->generateUrl('homepage');
         }

    The session storage key used here is pretty internal, and could change
    in the future. So use with caution!

.. _`HTML5Pattern.com`: http://html5pattern.com/
.. _`Form Field Type Reference`: http://symfony.com/doc/current/reference/forms/types.html
.. _`validation chapter of the documentation`: http://symfony.com/doc/current/book/validation.html
.. _`Translation Chapter`: http://symfony.com/doc/current/book/translation.html
.. _`reference section of the documentation`: http://symfony.com/doc/current/reference/constraints.html
.. _`UniqueEntity constraint`: http://symfony.com/doc/current/reference/constraints/UniqueEntity.html
.. _`Callback`: http://symfony.com/doc/current/reference/constraints/Callback.html
.. _`Constraint Configuration`: http://bit.ly/sf2-validation-config
