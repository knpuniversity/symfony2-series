More with ManyToMany: Avoiding Duplicates
=========================================

Now click the attend link again. Ah, an error!

    SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
    '4-4' for key 'PRIMARY'

Our User is once again added as an attendee to the Event. And when Doctrine saves,
it tries to add a second row to the join table. Not cool!

Adding the hasAttendee Method
-----------------------------

To fix this, create a new method in ``Event`` called ``hasAttendee``. This
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

Avoiding Duplicates
-------------------

Find ``attendAction`` in ``EventController``. We can use the new ``hasAttendee``
method to avoid adding duplicate Users::

    // src/Yoda/EventBundle/Controller/EventController.php

    public function attendAction($id)
    {
        // ...

        if (!$event->hasAttendee($this->getUser())) {
            $event->getAttendees()->add($this->getUser());
        }
        
        // ...
    }

Try it out! Go crazy, click the attend link as many times as you want: you're
only added the first time.

Adding Unattend Logic
---------------------

Let's fill in the logic in ``unattendAction``. Actually, we can just copy
``attendAction`` and *remove* the current user from the attendee list by using
the ``removeElement`` method::

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

        $url = $this->generateUrl('event_show', array(
            'slug' => $event->getSlug(),
        ));

        return $this->redirect($url);
    }

In our show template, let's show only the "attend" or "unattend" link based
on whether we're attending the event or not. That's easy with the ``hasAttendee``
method:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    <dt>who:</dt>
    <dd>
        {# ... #}

            {% if entity.hasAttendee(app.user) %}
                <a href="{{ path('event_unattend', {'id': entity.id}) }}" class="btn btn-warning btn-xs">
                    Oh no! I can't go anymore!
                </a>
            {% else %}
                <a href="{{ path('event_attend', {'id': entity.id}) }}" class="btn btn-success btn-xs">
                    I totally want to go!
                </a>
            {% endif %}
    </dd>

When we refresh, the unattend button is showing. Click it and then click the
attend button again. This bake sale is going to be off the hook!

What's really going on in the Base Controller
---------------------------------------------

Quickly, look back at the ``redirect`` and ``generateUrl`` methods we're
using in our controller. Let's see what these really do by opening up
:symfonyclass:`Symfony's base controller<Symfony\\Bundle\\FrameworkBundle\\Controller\\Controller>`
class::

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

Like we've seen over and over again, ``generateUrl`` is just a shortcut to
grab a service from the container and call a method on it. The ``redirect``
method is even simpler: it returns a special type of ``Response`` object
that's used when redirecting users.

The point is this: Symfony is actually pretty simple under the surface. Your
job in every controller is to return a ``Response`` object. The container
gives you access to all types of powerful objects to make that job easier.
