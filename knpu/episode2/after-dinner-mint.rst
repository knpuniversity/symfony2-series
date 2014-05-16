After-dinner Mint
=================

Nice work! We've walked through a lot of new concepts on this screencast,
from security to forms all the way to testing. These topics are all pretty
big and important, but with most of the work behind us, we can relax a little
bit and have some fun. In this last section, we'll check out some cool things
related to forms and security.

Form Field Guessing
-------------------

The first thing involves field types and HTML5 validation. Recall that we
disabled HTML5 validation earlier. Remove the ``formnovalidate`` attribute
so that it works again:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}

    {# ... #}
    <input type="submit" value="Register!" />

Now, open the ``User`` class: let's do a little experimenting. Remove the
``NotBlank`` constraint and pretend for a minute that the ``email`` field
isn't required. Also set ``nullable=true`` in the Doctrine metadata. Don't
worry about updating your schema - this change is just temporary::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Email
     */
    private $email;

When we open the registration page and try to submit, HTML5 validation stops
us. Like before, the ``email`` field has the ``required`` attribute on it.
We saw earlier that we can fix the problem by setting the ``required`` option
to ``false``. Shouldn't the form be able to see that the ``email`` field isn't
required in ``User`` and set the option to false for us?

Actually, it can! The feature is called "field guessing" and it works like
this. Open up ``RegistrationFormType`` and remove the second argument to
``add`` entirely::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    $builder
        // ...
        ->add('email')
        // ...
    ;

Refresh the page and inspect the ``email`` field - there are a bunch of awesome
things happening:

.. code-block:: text

    <input type="email" id="user_register_email" name="user_register[email]" maxlength="255" />

First, notice that the field is still ``type="email"``. The field type is
being guessed based on the fact that there is an ``Email`` constraint on this
property. Remove the ``Email`` constraint and refresh::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

.. code-block:: text

    <input type="text" id="user_register_email" name="user_register[email]" maxlength="255" />

If all else fails, the field is guessed as a ``text`` type.

Now, notice that the ``required`` attribute is gone. Other than the field type
itself, certain options are guessed, like ``required``. Let's play with this.
Add back the ``NotBlank`` constraint and refresh::

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

When we refresh, the ``required`` attribute is still there. The form system
guesses that the field is required based on the fact that it's required in
the database.

Finally, check out the ``maxlength`` attribute. This comes from the length
of the field in the database.

To wrap this up, if you leave the ``type`` argument empty when creating a field,
Symfony will try to guess the field type as well as the ``required``, ``max_length``,
and ``pattern`` options. Field guessing isn't always perfect, but I tend to
try it at first, and then explicitly set things that aren't guessed correctly.

Add back the ``email`` type option in the form and refresh::

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
none of the options such as ``required`` or ``max_length`` are guessed anymore.
In other words, if you don't let Symfony guess the field type, it won't guess
any of the options either.

Security: Creating Roles and Role Hierarchies
---------------------------------------------

Let's change gears and mention a few more things about security. Earlier,
we saw how you could enforce security in two different ways. The ``access_control``
method is the easiest, but we can always enforce the logic manually anywhere
else in our code. In both cases, we're checking whether or not a user has
a specific role. If they do, they get access. If they don't, they'll see
the login page or the access denied screen.

In our example, we showed a pretty basic system with just ``ROLE_USER`` and
``ROLE_ADMIN.`` If you need another role, just start using it. For example,
if only *some* users are able to create events, we can protect event creation
with a new role called ``ROLE_EVENT_MANAGER``:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_EVENT_MANAGER }
            - { path: ^/create , roles: ROLE_EVENT_MANAGER }

The *only* rule when creating a role is that it *must* start with ``ROLE_``.
If it doesn't, you won't get an error, but security won't be enforced. 

Try it out by logging in as admin, and trying to create an event. Our admin
user has ``ROLE_USER`` and ``ROLE_ADMIN``, but not ``ROLE_EVENT_MANAGER``.
If we want to give all administrators the ability to create events, we can
take advantage of role hierarchy. Add ``ROLE_EVENT_MANAGER`` to ``ROLE_ADMIN``
and refresh:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        role_hierarchy:
            ROLE_ADMIN:       [ROLE_USER, ROLE_EVENT_MANAGER]
            ROLE_SUPER_ADMIN: [ROLE_USER, ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]

We are in, now let's schedule that wookiee wine down!

Strategies for Controller Access
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Keep these two tips in mind when using roles:

1. Protect the actual parts of your application using feature-specific roles,
   not user-specific roles. This means your roles should describe the features
   they give you access to and not the type of user that has that access.

2) Use the role hierarchy section to manage which types of users have which
   roles. For example, you might decide that ``ROLE_USER`` has ``ROLE_BLOGGER``
   and ``ROLE_EVENT_MANAGER``. Give your actual users these user-specific roles.

By following these tips, you'll be able to easily control the exact areas
of your site that different users have access to.

Switching Users / Impersonation
-------------------------------

Let's look at just a few more quick things. Notice the ``ROLE_ALLOWED_TO_SWITCH``
role in ``security.yml``. What's that all about? One feature of Symfony is
the ability to actually change which user you're logged in as. Ever have a
client complaint you couldn't replicate? Well now you can login as them without
knowing their password to test things.

To activate this feature, add the ``switch_user`` key to your firewall:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        firewalls:
            secured_area:
                # ...
                switch_user: ~

To use it, just add a ``_switch_user`` query parameter to any page with the
username you want to change to:

    http://events.local/app_dev.php/new?_switch_user=user

When we try it initially, we get the access denied screen. Our user needs
``ROLE_ALLOWED_TO_SWITCH`` to be able to do this. Add it to the ``ROLE_ADMIN``
hierarchy to get it:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        role_hierarchy:
            ROLE_ADMIN:       [ROLE_USER, ROLE_EVENT_MANAGER, ROLE_ALLOWED_TO_SWITCH]
            # ...

When we refresh, you'll see that the our username in the web debug toolbar
has changed to user. So cool! To switch back, use the ``_exit`` key:

.. code-block:: text

    http://events.local/app_dev.php/new?_switch_user=_exit

Whitelisting: Securing all Pages, except a few
----------------------------------------------

Next, look again at ``access_control``. Right now, our entire site is open
to the public, except for the specific pages that we're locking down:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_EVENT_MANAGER }
            - { path: ^/create, roles: ROLE_EVENT_MANAGER }

This is a blacklisting strategy. If the majority of our site required login,
we could reverse. Add a new access control that matches *all* requests and
requires ``ROLE_USER``:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_EVENT_MANAGER }
            - { path: ^/create, roles: ROLE_EVENT_MANAGER }
            - { path: ^/, roles: ROLE_USER }

Now, every page is locked down. Logout and try it. Mmm a redirect loop!

.. _symfony-ep2-whitelisting-urls:

We've got too much security. When we go to any page, we don't have access
and are redirected to ``/login``. Of course, we don't have access to ``/login``
either, so we're redirected to ``/login``. Do you see the problem?

To fix this, add a new ``access_control`` entry for any page starting with
``/login``. For the role, type ``IS_AUTHENTICATED_ANONYMOUSLY``:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/new, roles: ROLE_EVENT_MANAGER }
            - { path: ^/create, roles: ROLE_EVENT_MANAGER }
            - { path: ^/, roles: ROLE_USER }

Refresh again. It works! We're missing our styles, but we'll fix that next.
The ``access_control`` entries match from top to bottom and stop after the
first match. When we go to ``/login``, the first control is matched and executed.
By saying ``IS_AUTHENTICATED_ANONYMOUSLY``, we're "whitelisting" this URL
pattern as one that should be available to everyone.

Run the ``router:debug`` task to see a few other URLs that we should whitelist,
including some URLs that load our CSS files as well as the web debug toolbar
and profiler during development:

    _assetic_01e9169                       ANY      /css/01e9169.css
    ...
    _wdt                                   ANY      /_wdt/{token}
    _profiler_home                         ANY      /_profiler/
    ... 

We haven't talked about assetic much yet, but by blocking it's URLs, we're
blocking our stylesheets. With these entries in place, we're in good shape:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/(css|js), roles: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/(_wdt|_profiler), roles: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/new, roles: ROLE_EVENT_MANAGER }
            - { path: ^/create, roles: ROLE_EVENT_MANAGER }
            - { path: ^/, roles: ROLE_USER }

Accessing the User in a Template
--------------------------------

Now that we're logged in, how can we get access to the User object? In a template,
it's as simple as ``app.user``. For example, we can use it to print out the
username:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}

    {# ... #}
    {% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}
        <a class="link" href="{{ path('logout') }}">
            Logout {{ app.user.username }}
        </a>
    {% endif %}

Accessing the User in a Controller
----------------------------------

From a controller, it's just as easy. Just grab an object called the security
context, get the token, and then get the user::

    public function indexAction()
    {
        $user = $this->container
            ->get('security.context')
            ->getToken()
            ->getUser()
        ;
        var_dump($user->getUsername());die;
        // ...
    }

Actually, since this is a bit long, the Symfony base controller gives us a
shortcut method called ``getUser``::

    public function indexAction()
    {
        $user = $this->getUser();
        var_dump($user->getUsername());die;
        // ...
    }

.. note::

    Remove this debug code before moving on.

I showed you the longer option first so that you'll understand that there
is a service called ``security.context`` which is your key to getting the current
``User`` object.

Remember Me Functionality
-------------------------

I want to leave you with just one more tip. We talked a bit about the remember
me functionality, but we didn't actually see how to use it. Activate the
feature by adding the ``remember_me`` entry to your firewall and giving it
a secret, random key:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        firewalls:
            secured_area:
                # ...
                remember_me:
                    key: The name of our cat is Edgar!

Now, just open the login template and add a field named ``_remember_me``:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Login/login.html.twig #}
    {# ... #}

    <form ...>
        <input type="checkbox" name="_remember_me" />
        Remember me
    </form>


This works a bit like the login itself - as long as we have a ``_remember_me``
field and its checked, everything else happens automatically.

Try it out. After logging in, we can see that a ``REMEMBERME`` cookie has been
set. Let's clear our session cookie to make sure it's working. When I refresh,
my session is gone but I'm still logged in. Nice!

Alright, that's it for now! I hope I'll see you in future Knp screencasts.
Also, be sure to checkout `KnpBundles.com`_ if you're curious about all
the open source bundles that you can bring into your app. Seeya next time!

.. _`KnpBundles.com`: http://knpbundles.com/
