AJAX and JSON Responses
=======================

Let's talk about AJAX. In this section, we'll allow users to say that they're
"attending" an event by clicking a link that communicates back to our app
via JavaScript. Doing AJAX in Symfony2 is just like creating a normal page.
We'll have a route and a controller, but instead of returning a full page,
we'll return just a small piece of HTML or a JSON string.

ManyToMany Relationships
------------------------

But before we get there, we need to update our model so that a user can "attend"
an event. From a database standpoint, this is a classic ``ManyToMany`` relationship
between the Event table and the User table. To model this, create a new ``attendees``
property on ``Event`` that'll hold an array of Users attending this event::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    protected $attendees;

To see how ManyToMany relationships are handled in Doctrine, let's head to
Doctrine's documentation and visit the "relations" section. Go to the
`Many-To-Many, Unidirectional section`_. Why unidirectional as opposed to
bidirectional? Unidirectional just means that we're only going to setup one
side of the relationship for now. In other words, we'll be able to call
``$event->getAttendees()`` but not ``$user->getAttendingEvents()``. Choosing
a unidirectional versus bidirectional makes no difference to your database
structure: you can start with unidirectional and add the other side of the
relationship later if you need it.

Copy the ``ManyToMany`` annotations from the example and paste them into ``Event``.
Add ``ORM\`` in front of each annotation to make them work inside of Symfony
and change group to be our user class::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToMany(targetEntity="Yoda\UserBundle\Entity\User")
     */
    protected $attendees;

The ``JoinTable`` line is totally optional. Use it only if you want to customize
the name of the join table or how it looks. One common thing to do is add
some onDelete behavior. Let's make entries in the join table delete if either
the related User or Event is removed::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToMany(targetEntity="Yoda\UserBundle\Entity\User")
     * @ORM\JoinTable(
     *      joinColumns={@ORM\JoinColumn(onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(onDelete="CASCADE")}
     *      )
     */
    protected $attendees;

Next, create a constructor. The ``attendees`` property is basically an array,
but as we explained before, Doctrine needs its array relationships to be
setup as the special ``ArrayCollection`` objects::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    use Doctrine\Common\Collections\ArrayCollection;
    
    public function __construct()
    {
        $this->attendees = new ArrayCollection();
    }

Usually, this is where we'd generate a getter and a setter. Instead, generate
only the getter - we'll see in a moment why the setter isn't needed::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    public function getAttendees()
    {
        return $this->attendees;
    }

While we're in here, create another method called ``hasAttendee``, which
will return true or false depending on whether or not a given user is attending
this event::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @param \Yoda\UserBundle\Entity\User $user
     * @return bool
     */
    public function hasAttendee(User $user)
    {
        return $this->getAttendees()->contains($user);
    }

This shows off the ``contains`` method of `ArrayCollection`_, which works
just like the ``in_array`` function.

Now that the relationship is setup, time to update the database. As a sanity
check, I'll dump the SQL first:

.. code-block:: bash

    php app/console doctrine:schema:update --dump-sql
    php app/console doctrine:schema:update --force

As you can see, the new relationship causes a new table to be created, called
``event_user``.

Allowing Users to Attend and Unattend an Event
----------------------------------------------

Let's put our new relationship into action. Create two new routes next to
our other event routes: one for attending an event and another for unattending:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # ...
    
    event_attend:
        pattern:  /{id}/attend
        defaults: { _controller: "EventBundle:Event:attend" }

    event_unattend:
        pattern:  /{id}/unattend
        defaults: { _controller: "EventBundle:Event:unattend" }

Next, hop into the ``EventController`` and create the two corresponding action
methods::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...
    
    public function attendAction($id)
    {
    
    }

    public function unattendAction($id)
    {
    
    }

These methods are pretty straightforward and follow a familiar pattern::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function attendAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var $event \Yoda\EventBundle\Entity\Event */
        $event = $em->getRepository('EventBundle:Event')->find($id);

        if (!$event) {
            throw $this->createNotFoundException('No event found for id '.$id);
        }

        if (!$event->hasAttendee($this->getUser())) {
            $event->getAttendees()->add($this->getUser());
        }

        $em->persist($event);
        $em->flush();

        return $this->redirect($this->generateUrl('event_show', array(
            'slug' => $event->getSlug()
        )));
    }

First, query the database for the Event object and throw the not found exception
if none exists. This guarantees that the user will see the 404 page if the
event doesn't exist. Next, add the user to the event if he's not already
attending. This makes use of the `hasAttendee`` method that we just created
on ``Event``. Remember that the ``getAttendees`` method actually returns an
``ArrayCollection`` object. This object has an ``add`` method on it, which
we use to add the new ``User``. This is why we didn't need a ``setAttendees``
method on ``Event``: we can just grab the ``ArrayCollection`` object and
add the user ourselves.

What's really going on in the Base Controller
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Be sure to flush your changes to the database. At the end of the action, just
redirect back to the event show page. If you're curious about what the ``generateUrl``
and ``redirect`` methods actually do, check out
:symfonyclass:`Symfony's base controller<Symfony\\Bundle\\FrameworkBundle\\Controller\\Controller>`.
I'll click into that class to give you a preview::

    // vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Controller/Controller.php
    // ...
    
    public function generateUrl($route, $parameters = array(), $absolute = false)
    {
        return $this->container->get('router')->generate($route, $parameters, $absolute);
    }

    public function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

Like we've seen over and over again, ``generateUrl` is just a shortcut to grab
a service from the container and call a method on it. The ``redirect`` method
is even simpler: it returns a special type of ``Response`` object that's used
when redirecting users. I hope you're starting to see that Symfony is actually
pretty simple under the surface. Your job in every controller is to return
a ``Response`` object. The container gives you access to all types of powerful
objects to make that job easier.

Updating the Template with Attending Details
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Before we try this out, let's update the event show page. First, use the
``length`` filter to count the number of attendees:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    <dt>who:</dt>
    <dd>
        {{ entity.attendees|length }} attending!
    </dd>

Next, iterate over the event's attendees and print each of them out. To give
a special message when nobody's attending, you can use Twig's really nice
:ref:`for-else<twig-for-else-tag>` functionality:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    <dt>who:</dt>
    <dd>
        {{ entity.attendees|length }} attending!

        <ul class="users">
            {% for attendee in entity.attendees %}
                <li>{{ attendee }}</li>
            {% else %}
                <li>nobody yet!</li>
            {% endfor %}
        </ul>
    </dd>

Next, if a user is logged in, we need to give him either an "i want to go"
or an "i can't go anymore" link so that they can change their status. This
is easy since we can just reuse our ``hasAttendee`` method once again:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    <dt>who:</dt>
    <dd>
        {# ... #}

        {% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}
            {% if entity.hasAttendee(app.user) %}
                <a href="{{ path('event_unattend', {'id': entity.id}) }}">Oh no! I can't go anymore!</a>
            {% else %}
                <a href="{{ path('event_attend', {'id': entity.id}) }}">I totally want to go!</a>
            {% endif %}
        {% endif %}
    </dd>

Head to the browser to try it out. When we try to attend, it works, but then
creates an error! The error is because we're trying to print out an entire
User object in the template. One way to fix this is just to print out one
specific field on the ``User``. Another way is to add a ``__toString`` method
on ``User``::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    public function __toString()
    {
        return (string) $this->getUsername();
    }

I don't technically need to type-hint the username to a string, but
it's usually a good idea in ``__toString`` methods. If for some reason the
username were null, PHP would give us a difficult-to-track-down error. Refresh
the page to see that we're attending.

Finishing the Unattend Action
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

To finish the cycle, copy the code into the ``unattendAction``. This time,
instead of using ``add``, use ``removeElement``::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function unattendAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var $event \Yoda\EventBundle\Entity\Event */
        $event = $em->getRepository('EventBundle:Event')->find($id);

        if (!$event) {
            throw $this->createNotFoundException('No event found for id '.$id);
        }

        if ($event->hasAttendee($this->getUser())) {
            $event->getAttendees()->removeElement($this->getUser());
        }

        $em->persist($event);
        $em->flush();

        return $this->redirect($this->generateUrl('event_show', array(
            'slug' => $event->getSlug()
        )));
    }

Head to the browser and try it again. Sure enough, we can toggle between
attending and unattending the event.

On the index page, we can now fill in the # of attendees:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    {% for entity in entities %}
        {# ... #}

        <dt>who:</dt>
        <dd>{{ entity.attendees|length }} attending!</dd>

        {# ... #}
    {% endfor %}

Creating JSON-returning Actions for AJAX
----------------------------------------

Since that's easy enough, let's make things better with some AJAX. Right now,
the attend and unattend pages return HTML. Ok, it's a redirect, but redirects
are inherently meant for browsers and Symfony's redirects actually contain
some HTML that a normal browser never displays.

Of course, instead of returning HTML, we could also return content in another
format like JSON. JSON is great because it's easy to create in PHP and easy
for JavaScript to understand. Start by adding a ``_format`` wildcard to each
of our routes and giving it a default value of ``html``:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # ...

    event_attend:
        pattern:  /{id}/attend.{_format}
        defaults: { _controller: "EventBundle:Event:attend", _format: html }

    event_unattend:
        pattern:  /{id}/unattend.{_format}
        defaults: { _controller: "EventBundle:Event:unattend", _format: html }

By giving this wildcard a default value it means that the route still matches
``/{id}/attend``, but that we could also create other URLs like ``/{id}/attend.json``.

.. tip::

    In a truly RESTful API, it's probably more correct to rely on reading
    the ``Accept`` header of the request rather than specify a format in
    the URL like we're doing here (e.g. ``/5/attend.json``).

For now, all of these URLs still do the same thing. Since we're not going
to support any other formats like XML, we can add a requirements key:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # ...

    event_attend:
        pattern:  /{id}/attend.{_format}
        defaults: { _controller: "EventBundle:Event:attend", _format: html }
        requirements:
            _format: html|json

    event_unattend:
        pattern:  /{id}/unattend.{_format}
        defaults: { _controller: "EventBundle:Event:unattend", _format: html }
        requirements:
            _format: html|json

.. tip::

    Requirements are regular expressions that can be applied to any of your
    routing wildcards (e.g. ``{id}``, ``{_format}``).

Now, when we try a different ending (e.g. ``/1/attend.xml``), the route
won't match.

Returning JSON from a Controller
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Create a ``$_format`` variable in your controller to go with the new wildcard.
If the format is JSON, let's return a JSON string instead of the redirect::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function attendAction($id, $_format)
    {
        // ...

        if ($_format == 'json') {
            $data = array(
                'attending' => 1
            );

            $response = new Response(json_encode($data));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return $this->redirect($this->generateUrl('event_show', array(
            'slug' => $event->getSlug()
        )));
    }

Doing this is easy: create your data array, convert it to a string with ``json_encode``,
and put it into a raw Symfony Response object. We also need to think about
the ``Content-Type`` header that's returned in the response. By default, Symfony
sets the ``Content-Type`` header to `text/html`. But if we're returning JSON,
this needs to be changed to ``application/json``. If we don't set this, JavaScript
might have problems understanding the data it's getting back.

.. tip::

    There is also a :symfonyclass:`Symfony\\Component\\HttpFoundation\\JsonResponse`
    class that's even easier. Just pass the array of data into its constructor.
    Internally, it will call ``json_encode`` for you and set the ``Content-Type``
    header::

        use Symfony\Component\HttpFoundation\JsonResponse;
        // ...

        return new JsonResponse($data);

Let's try it directly in the browser first. As expected, we see the JSON string.
If we open up the inspector, and refresh, we can see that the ``Content-Type``
on the response is set correctly.

The Request Format and _format
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

But before we roll this out to the unattend action, let's simplify. First,
remove the ``Content-Type`` header and refresh again. Mysteriously, the ``Content-Type``
is *still* ``application/json``. But didn't I just tell you that it defaults
to ``text/html``? The answer to this riddle is that the ``_format`` routing
parameter is special, and is used by Symfony in a very specific way. To see
this, remove the ``$_format`` argument from your controller and replace it
with a call to the ``getRequestFormat`` on the Request object::

    // src/Yoda/EventBundle/Controller/EventController.php
    use Symfony\Component\HttpFoundation\Request;
    // ...

    public function attendAction(Request $request, $id)
    {
        // ...

        if ($request->getRequestFormat() == 'json') {
            // create and return the json response
        }

        // ...
    }

When we refresh, everything still works. Internally, every request has a
"format", which is a simple string like ``html`` or ``json``. By using the
``_format`` routing parameter, the request format is automatically set to
that value. The request format is important for one big reason: its value
is used to set the ``Content-Type`` response header automatically for you.
So if the request format is json, xml, css, or js, for example, then the
right ``Content-Type`` header will take care of itself.

Finishing up the Controller
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Let's finish things up by abstracting a bit of our logic to a new private
function::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    /**
     * @param bool $attending
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function createAttendingJson($attending)
    {
        $data = array(
            'attending' => $attending
        );

        $response = new Response(json_encode($data));

        return $response;
    }

We can use this function to easily generate the JSON response for both controllers::

    // src/Yoda/EventBundle/Controller/EventController.php
    use Symfony\Component\HttpFoundation\Request;
    // ...

    public function attendAction(Request $request, $id)
    {
        // ...

        if ($request->getRequestFormat() == 'json') {
            return $this->createAttendingJson(true);
        }

        // ...
    }

    public function unattendAction(Request $request, $id)
    {
        // ...

        if ($request->getRequestFormat() == 'json') {
            return $this->createAttendingJson(false);
        }

        // ...
    }

Hooking up the JavaScript for AJAX
----------------------------------

These two controllers are now fully capable of returning either a proper HTML
or JSON response. This is perfect for JavaScript, so let's hook some
up! Since most people know it, I'll use jQuery. Since I'm going to attach
a jQuery click event to each of the links, let's add a class we can query
for. Let's actually display both links, but use some logic to hide the link
that we don't initially need::

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    <dt>who:</dt>
    <dd>
        {# ... #}

        {% if is_granted('IS_AUTHENTICATED_REMEMBERED') %}
            <a href="{{ path('event_unattend', {'id': entity.id}) }}"
               class="attend-toggle{{ entity.hasAttendee(app.user) ? '' : ' hidden' }}">
               Oh no! I can't go anymore!
            </a>

            <a href="{{ path('event_attend', {'id': entity.id}) }}"
                class="attend-toggle{{ entity.hasAttendee(app.user) ? ' hidden' : '' }}">
                I totally want to go!
            </a>
        {% endif %}
    </dd>

For the JavaScript, create a ``javascripts`` block and add the ``parent()``
function:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    {% block javascripts %}
        {{ parent() }}
    {% endblock %}

This lets us add JavaScript to the ``javascripts`` block that lives in our base
template. For ease I'll just paste in the logic:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    {% block javascripts %}
        {{ parent() }}

        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('.attend-toggle').click(function() {

                    $(this).siblings().show();
                    $(this).hide();

                    var url = $(this).attr('href')+'.json';

                    $.post(url, null, function(data) {
                        if (data.attending) {
                            $.growlUI('Awesome!', 'See you there!');
                        } else {
                            $.growlUI('Ah darn', 'We\'ll miss you!');
                        }
                    });

                    return false;
                });
            });
        </script>
    {% endblock %}

In an ideal world, this would live in an external JavaScript file, but we'll
let that be for now. The JavaScript is pretty straight-forward: we listen
on a click of either link, toggle which link is displayed, then make an AJAX
post to the server. Notice that I've appended the ``.json`` to the URL so
that we get the JSON response, not the HTML response. Since the JSON we return
says whether or not we're attending, we can use that to show a super cool
message. Try out these cool jedi powers.

So that's really it! Doing AJAX with Symfony is more about turning your application
into something that can serve multiple formats of content. Since JavaScript
loves JSON, it's a natural fit. To take this idea to the next level, check
out the `FOSRestBundle`_. This bundle is designed to make it really natural to
create controllers that can serve content in many different formats. If you're
creating a rich API for your app, it's definitely worth looking into.

.. _`Many-To-Many, Unidirectional section`: http://docs.doctrine-project.org/en/latest/reference/association-mapping.html#many-to-many-unidirectional
.. _`ArrayCollection`: http://docs.doctrine-project.org/en/latest/reference/association-mapping.html#collections
.. _`FOSRestBundle`: https://github.com/FriendsOfSymfony/FOSRestBundle
