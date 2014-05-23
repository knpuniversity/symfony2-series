Accessing the User 
==================

Now that we're logged in, how can we get access to the User object?

In a Template
-------------

In a template, it's as simple as ``app.user``. For example, we can use it
to print out the username:

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

I showed you the longer option first so that you'll understand that there
is a service called ``security.context`` which is your key to getting the current
``User`` object. Remove this debug code before moving on.
