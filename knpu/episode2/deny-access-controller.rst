Denying Access: AccessDeniedException
=====================================

Let's login as ``user`` again and surf to ``/new``. Since we have the ``ROLE_USER``
role, we're allowed access. In the ``access_control`` section of ``security.yml``,
change the role for this page to be ``ROLE_ADMIN`` and refresh:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_ADMIN }
            # ...

This is the access denied page. It means that we *are* authenticated, but
don't have access. Of course in Symfony's `prod environment`_, we'll be able
to customize how this looks. We'll cover how to `customize error pages`_
in the next episode.

The ``access_control`` section of ``security.yml`` is the easiest way to control
access, but also the least flexible. Change the ``access_control`` entry
back to use ``ROLE_USER`` and then comment both of them out. We're going
to deny access from inside our controller class instead.

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            # - { path: ^/new, roles: ROLE_USER }
            # - { path: ^/create, roles: ROLE_USER }

Denying Access From a Controller: AccessDeniedException
-------------------------------------------------------

Find the ``newAction`` in ``EventController``. To check if the
current user has a role, we need to get the "security context". This is a
scary sounding object, which has just one easy method on it: ``isGranted``.

Use it to ask if the user has the ``ROLE_ADMIN`` role::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function newAction()
    {
        $securityContext = $this->container->get('security.context');
        if (!$securityContext->isGranted('ROLE_ADMIN')) {
            // panic?
        }

        // ...
    }

If the user *doesn't* have ``ROLE_ADMIN``, we need to throw a  very special
exception: called
:symfonyclass:`Symfony\\Component\\Security\\Core\\Exception\\AccessDeniedException`.
Add a ``use`` statement for this class and then throw a new instance inside
the ``if`` block. If you add a message, only the developers will see it::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    use Symfony\Component\Security\Core\Exception\AccessDeniedException;
    // ...

    public function newAction()
    {
        $securityContext = $this->container->get('security.context');
        if (!$securityContext->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only an admin can do this!!!!')
        }

        // ...
    }

In Symfony 2.5 and higher, there's event a shortcut ``createAccessDeniedException``
method::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    if (!$securityContext->isGranted('ROLE_ADMIN')) {
        // in Symfony 2.5
        throw $this->createAccessDeniedException('message!');
    }

When we refresh now, we see the access denied page. But if we were logged
in as ``admin``, who *does* have this role, we'd see the page just fine.

AccessDeniedException: The Special Class for Security
-----------------------------------------------------

Normally, if you throw an exception, it'll turn into a 500 page. But the
``AccessDeniedException`` is special. First, if we're not already logged in,
throwing this causes us to be redirected to the login page. But if we *are*
logged in, we'll be shown the access denied 403 page. We don't have to worry
about whether the user is logged in or not here, we can just throw this exception.

Phew! Security is hard, but wow, you seriously know almost everything you'll
need to know. You'll only need to worry about the *really* hard stuff if you 
need to create a custom authentication system, like if you're authenticating 
users via an API key instead of a login form. If you're in this situation, make 
sure you read the Symfony Cookbook entry called `How to Authenticate Users with 
API Keys`_. It uses a feature that's new to Symfony 2.4, so you may not see it 
mentioned in older blog posts.

Ok, let's unbreak our site. To keep things short, create a new private function
in the controller called ``enforceUserSecurity`` and copy our security check
into this::

    private function enforceUserSecurity()
    {
        $securityContext = $this->container->get('security.context');
        if (!$securityContext->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('Need ROLE_USER!');
        }
    }

Now, use this in ``newAction``, ``createAction``, ``editAction``, ``updateAction``
and ``deleteAction``::

    public function newAction()
    {
        $this->enforceUserSecurity();

        // ...
    }

    public function createAction(Request $request)
    {
        $this->enforceUserSecurity();

        // ...
    }

You can see how sometimes using ``access_control`` can be simpler, even if this
method is more flexible. Choose whichever works the best for you in each situation.

.. tip::

    You can also use annotations to add security to a controller! Check
    out `SensioFrameworkExtraBundle`_.

.. _`prod environment`: http://knpuniversity.com/screencast/symfony2-ep1/vhost#the-dev-and-prod-environments
.. _`customize error pages`: http://knpuniversity.com/screencast/symfony2-ep3/error-pages#overriding-the-error-template-content
.. _`How to Authenticate Users with API Keys`: http://symfony.com/doc/current/cookbook/security/api_key_authentication.html
.. _`SensioFrameworkExtraBundle`: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/security.html
