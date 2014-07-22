Adding a Flash Message
======================

After registration, let's make the new user feel loved by giving them a big
happy success message! Symfony has a feature called "flash messages", which
is perfect for this. A flash is a message that we set to the session, but
that disappears after we access it exactly one time.

After registration, grab the ``session`` object from the request and get an
object called a "flash bag". Set a message on it using ``add``::

    // src/Yoda/UserBundle/Entity/Controller/RegisterController.php
    // ...

    if ($form->isValid()) {
        // .. code that saves the user

        $request->getSession()
            ->getFlashBag()
            ->add('success', 'Welcome to the Death Star, have a magical day!')
        ;

        $url = $this->generateUrl('event');

        return $this->redirect($url);
    }

Rendering a Flash Message
-------------------------

That's it! Now, if a flash message exists, we just need to print it on the
page. Let's do that in ``base.html.twig``. The session object is available
via ``app.session``. Use it to check to see if we have any ``success`` flash
messages. If we do, let's print the messages inside a styled container. You'll
typically only have one message at a time, but the flash bag is flexible
enough to store any number:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}

    <body>
        {% if app.session.flashBag.has('success') %}
            <div class="alert alert-success">
                {% for msg in app.session.flashBag.get('success') %}
                    {{ msg }}
                {% endfor %}
            </div>
        {% endif %}

        <!-- ... -->

The ``success`` key is just something I made up. With this setup, whenever
we need to show a happy message, we just need to set a ``success`` flash
message and it'll show up here!

Let's test it out. We register, the flash message is set, and then it's displayed
after the redirect. Nice!
