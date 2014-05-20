Adding a Flash Message
======================

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
