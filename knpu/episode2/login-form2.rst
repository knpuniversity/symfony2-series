Creating a Login Form (Part 2)
==============================

Ok, we're almost done, seriously!

Creating the Template
---------------------

Copy the template code from the docs and create the ``login.html.twig`` file:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Security/login.html.twig #}
    {% if error %}
        <div>{{ error.message }}</div>
    {% endif %}

    <form action="{{ path('login_check') }}" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="_username" value="{{ last_username }}" />

        <label for="password">Password:</label>
        <input type="password" id="password" name="_password" />

        {#
            If you want to control the URL the user
            is redirected to on success (more details below)
            <input type="hidden" name="_target_path" value="/account" />
        #}

        <button type="submit">login</button>
    </form>

This prints the login error message if there is one and has a form with ``_username``
and ``_password`` fields. When we submit, Symfony is going to be looking
for these fields, so their names are important.

.. tip::

    You can of course change these form field names to something else.
    Google for the ``username_parameter`` and ``password_parameter`` options.

Let's make this extend our ``base.html.twig`` template. I'll also add in
a little bit of extra markup:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Security/login.html.twig #}
    {% extends '::base.html.twig' %}

    {% block body %}
    <section class="login">
        <article>

            {% if error %}
                <div class="error">{{ error.message }}</div>
            {% endif %}

            <form action="{{ path('login_check') }}" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="_username" value="{{ last_username }}" />

                <label for="password">Password:</label>
                <input type="password" id="password" name="_password" />

                <button type="submit">login</button>
            </form>

        </article>
    </section>
    {% endblock %}

Handling Login: login_check
---------------------------

Route check! Controller check! Template check! Let's try it! Oh boy, an error:

    Unable to generate a URL for the named route "login_check" as such
    route does not exist.

Ah, the copied template code has a form that submits to a route called ``login_check``.
Let's create another action method and use ``@Route`` to create that route::

    // ...
    // src/Yoda/UserBundle/Controller/SecurityController.php

    /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
    }

Call me crazy, but I'm going to leave this action method completely blank.
Normally, it means that if you went to ``/login_check`` it would execute this controller
and cause an error since we're not returning anything.

Configuring login_path and check_path
-------------------------------------

But this controller will never be executed. Before I show you, open up ``security.yml``
and look at the ``form_login`` configuration:

.. code-block:: yaml

    # app/config/security.yml
    # ...

    firewalls:
        secured_area:
            pattern:    ^/
            form_login:
                check_path: _security_check
                login_path: /my-login-url
            # ...

``login_path`` is the URL *or* route name the user should be sent to
when they hit a secured page. Change this to be ``login_form``: the name
of our ``loginAction`` route. ``check_path`` is the URL or route name that
the login form will be submitted to. Change this to be ``login_check``.

In your browser, try going to ``/new``. Yes! *Now* we're redirected to ``/login``,
thanks to the ``login_path`` config key. The page looks just terrible, but
it's working.

Using and Understanding the Login Process
------------------------------------------

Now, let me show you one of the strangest parts of Symfony's security system.
When we login using ``user`` and ``userpass``... it works! We can see our
username in the web debug toolbar and even a role assigned to us. What the
heck just happened?

When we submit, Symfony's security system intercepts the request and processes
the login information. This works as long as we POST ``_username`` and ``_password``
to the URL ``/login_check``. This URL is special because its route is configured
as the ``check_path`` in ``security.yml``. The ``loginCheckAction`` method
is *never* executed, because Symfony intercepts POST requests to that URL.

If the login is successful, the user is redirected to the page they last
visited or the homepage. If login fails, the user is sent back to ``/login``
and an error is shown.

And where did the ``user`` and ``userpass`` stuff come from? Actually, right
now the users are just being loaded directly from ``security.yml``:

.. code-block:: yaml

    # app/config/security.yml
    # ...
    providers:
        in_memory:
            memory:
                # this was here when we started: 2 hardcoded users
                users:
                    user:  { password: userpass, roles: [ 'ROLE_USER' ] }
                    admin: { password: adminpass, roles: [ 'ROLE_ADMIN' ] }

In a minute, we'll load users from the database instead.
