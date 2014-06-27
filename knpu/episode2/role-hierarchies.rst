Security: Creating Roles and Role Hierarchies
---------------------------------------------

Let's change gears and mention a few more things about security. Earlier,
we saw how you could enforce security in two different ways. The ``access_control``
method is the easiest, but we can always enforce things manually in the controller.
In both cases, we're checking whether or not a user has a specific role.
If they do, they get access. If they don't, they'll see the login page or
the access denied screen.

In our example, we showed a pretty basic system with just ``ROLE_USER`` and
``ROLE_ADMIN.`` If you need another role, just start using it. For example,
if only *some* users are able to create events, we can protect event creation
with a new role.

To show this, let's make the role that's passed to ``enforceUserSecurity`` configurable
and then only let a user create an event if they have some ``ROLE_EVENT_CREATE``
role::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function createAction(Request $request)
    {
        $this->enforceUserSecurity('ROLE_EVENT_CREATE');
    }
    
    // also change ROLE_USER to ROLE_EVENT_CREATE in newAction

    // ...
    private function enforceUserSecurity($role = 'ROLE_USER')
    {
        $securityContext = $this->container->get('security.context');
        if (!$securityContext->isGranted($role)) {
            // in Symfony 2.5
            // throw $this->createAccessDeniedException('message!');
            throw new AccessDeniedException('Need '.$role);
        }
    }

The *only* rule when creating a role is that it *must* start with ``ROLE_``.
If it doesn't, you won't get an error, but security won't be enforced. 

Try it out by logging in as admin. But first, reload the fixtures, since
our users were deleted earlier when running our functional test.

Now, try to create an event. No access! Our admin user has ``ROLE_USER`` and 
``ROLE_ADMIN``, but not ``ROLE_EVENT_CREATE``. If we want to give all administrators 
the ability to create events, we can take advantage of role hierarchy, which we can 
see in ``security.yml``. Add ``ROLE_EVENT_CREATE`` to ``ROLE_ADMIN`` and refresh again:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...
        role_hierarchy:
            ROLE_ADMIN:       [ROLE_USER, ROLE_EVENT_CREATE]
            ROLE_SUPER_ADMIN: [ROLE_USER, ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]

We are in! Now let's schedule that wookiee wine down!

Strategies for Controller Access
--------------------------------

Keep these two tips in mind when using roles:

1. Protect the actual parts of your application using feature-specific roles,
   not user-specific roles. This means your roles should describe the features
   they give you access to, like ``ROLE_EVENT_CREATE`` and not the type of
   user that should have access, like ``ROLE_ADMIN``.

2) Use the role hierarchy section to manage which types of users have which
   roles. For example, you might decide that ``ROLE_USER`` should have ``ROLE_BLOG_CREATE``
   and ``ROLE_EVENT_CREATE``, which you setup here. Assign your actual users
   these user-specific roles, like ``ROLE_USER`` or ``ROLE_MARKETING``.

By following these tips, you'll be able to easily control the exact areas
of your site that different users have access to.
