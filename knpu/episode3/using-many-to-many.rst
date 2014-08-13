Using the ManyToMany so Users can Attend an Event
=================================================

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

Start with ``attendAction``. The logic here should feel familiar. First,
query for an ``Event`` entity. Next, throw a ``createNotFoundException``
if no ``Event`` is found::

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

        // ... todo
    }

All we need to do now is add the current ``User`` object as an attendee on
this ``Event``. Remember that the ``attendees`` property is actually an
``ArrayCollection`` object. Use its ``add`` method then save the ``Event``.
Finally, redirect when you're finished::

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

        $event->getAttendees()->add($this->getUser());

        $em->persist($event);
        $em->flush();

        $url = $this->generateUrl('event_show', array(
            'slug' => $event->getSlug(),
        ));
        
        return $this->redirect($url);
    }

Notice that we just added an attendee without needing a ``setAttendees``
method on ``Event``. This works because ``attendees`` is an object, so we
can just call ``getAttendees`` and then modify it.

Printing Attendees in Twig
--------------------------

Before we try this out, let's update the event show page. Use the ``length``
filter to count the number of attendees, to make sure we make enough guacamole:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    <dt>who:</dt>
    <dd>
        {{ entity.attendees|length }} attending!
        
        <ul class="users">
            <li>nobody yet!</li>
        </ul>
    </dd>

We can even loop over the event's attendees and print each of them out. Print
a nice message when nobody's attending, using Twig's really nice `for-else`_
functionality:

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
                <li>We're cool! RSVP!</li>
            {% endfor %}
        </ul>
    </dd>

Now help me add a link to the new ``event_attend`` route if the user is logged in:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    <dt>who:</dt>
    <dd>
        {# ... #}

            <a href="{{ path('event_attend', {'id': entity.id}) }}" class="btn btn-success btn-xs">
                I totally want to go!
            </a>
    </dd>

Testing out the Relationship
----------------------------

Head over to an event in your browser. It says 0 attending. Now click the
new link. After the redirect, we see 1 attending, but we also see a huge
error:

    Catchable Fatal Error: Object of class Yoda\UserBundle\Entity\User could
    not be converted to string

The fact that we show 1 attending means that the database relationship was
stored correctly. We can prove it by querying for the join table:

.. code-block:: bash

    php app/console doctrine:query:sql "SELECT * FROM event_user"

Yep, we see one row that links our user to this event.

Adding a __toString to User
---------------------------

So what's the error? Look closely: PHP is trying to convert our ``User``
object into a string. This is happening because we're looping over ``event.attendees``,
which gives us User objects that we're printing:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}

    {% for attendee in entity.attendees %}
        <li>{{ attendee }}</li>
    {% else %}
        <li>nobody yet!</li>
    {% endfor %}

We have two options to fix this. First, we *could* just print out a specific
property on the ``User``:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}

    {% for attendee in entity.attendees %}
        <li>{{ attendee.username }}</li>
    {% else %}
        <li>nobody yet!</li>
    {% endfor %}

But if you *do* just want to print the object, you can add a ``__toString``
method to the ``User`` class::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    public function __toString()
    {
        return (string) $this->getUsername();
    }

Refresh now. Sweet, no errors!

Let's also take a second and fill in the # of attendees on the index page:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    {% for entity in entities %}
        {# ... #}

        <dt>who:</dt>
        <dd>{{ entity.attendees|length }} attending!</dd>

        {# ... #}
    {% endfor %}


.. _`for-else`: http://twig.sensiolabs.org/doc/tags/for.html#the-else-clause
