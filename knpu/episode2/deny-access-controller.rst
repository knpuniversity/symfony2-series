Denying Access From a Controller: AccessDeniedException
=======================================================

If you're still with us, let's see a few more things about roles, otherwise hit
rewind and we will see you in a minute. First, login as user again and surf
to ``/new``. Since we have the ``ROLE_USER`` role, we're allowed access.
In the ``access_control`` section of ``security.yml``, change the role for
this page to ``ROLE_ADMIN`` and refresh:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            - { path: ^/new, roles: ROLE_ADMIN }
            # ...

This is the access denied page. It means that we *are* authenticated, but
don't have access. Of course, if this were on production, the page would look
a bit different. We'll learn how to customize error pages in the next screencast.

The ``access_control`` section of ``security.yml`` is the easiest way to control
access to your application, but also the least flexible. Remove the ``access_control``
entry:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        access_control:
            # - { path: ^/new, roles: ROLE_USER }
            # ...

In most applications, you'll probably also need to enforce more fine-grained
controls right inside your controllers. Find the ``newAction`` of the ``EventController``.
To check if the current user has a given role, we need to get the "security context",
which is a scary sounding object with one easy method on it: ``isGranted``.
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

If she doesn't, we need to throw a very special exception:
:symfonyclass:`Symfony\\Component\\Security\\Core\\Exception\\AccessDeniedException`.
Add a ``use`` statement for this class and then throw it inside the ``if``
block. If you add a message, only the developers will be able to see it::

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

Why is this exception class so special? First, if the current user isn't already
logged in, this causes them to be correctly redirected to the login page.
If the user is logged in, this will cause the access denied status code 403
page to be shown. As I mentioned earlier, we'll learn how to customize these
error pages a bit later.

Phew! Security is hard, but you're well on your way to becoming a security
master! Now let's learn about loading users from the database.

.. sidebar:: A few Tweaks before Continuing!

    This last part was just an example of security in a controller, but we
    won't use it going forward!

    Before you continue, remove (or comment out) the ``if`` statement we
    just added to ``newAction``::

        public function newAction()
        {
            /*
             * left as an example - but enforcing security in security.yml
            $securityContext = $this->container->get('security.context');
            if (!$securityContext->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedException('Only an admin can do this!!!!')
            }
            */

            // ...
        }

    Also uncomment out the ``access_control`` entry and make sure it once
    again uses ``ROLE_USER``.
    
    .. code-block:: yaml

        # app/config/security.yml
        security:
            # ...
            access_control:
                - { path: ^/new, roles: ROLE_USER }
                # ...