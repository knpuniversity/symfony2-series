Keep Going!
===========

Ok! We've just learned a ton of really important tools and patterns inside
Symfony. We started off by creating our own base controller, which lets us
create our own shortcut methods that we can use in any controller throughout
our entire project. Since we needed the security context service, we added
``getSecurityContext()`` to make it really simple.

We can also create a private function inside our controller, which acts as
a shortcut that's available in just this one class. Use both of these ideas
to add more shortcuts to your controllers.

Review: Doctrine Relationships
------------------------------

In Doctrine, we created two relationships: a ``ManyToOne`` "owner" relation from
``Event`` to ``User`` and a ``ManyToMany`` "attendees" relation. These can
*always* be seen from two sides: you can ask which user owns this event or
you can ask which events are owned by this user.

Review: Doctrine Owning and Inverse Sides
-----------------------------------------

In Doctrine, one side of the relationship is always the "owning" side and
one side is always the "inverse" side. In a ``ManyToOne`` relationship, the
owning side is always where the foreign key lives in the database. In our
example, the ``Event`` class is the owning side because the ``yoda_event``
table has an ``owner_id`` foreign key column. In a ``ManyToMany`` relationship,
you'll choose which side is the "owning" side.

The owning versus inverse side distinction is important for 2 reasons. First,
only the "owning" side of a relationship is necessary. In our example, we
need the ``ManyToOne`` ``owner`` property, but we don't have to add a ``OneToMany``
``events`` property to ``User`` if we don't need it. That property would represent
the "inverse" side.

Second, only the owning side is used for persistence. For example, in our
play script, we can make a user an owner of an event by calling ``setOwner``,
which sets the "owning" side of the relationship. If we saved Doctrine would
handle this. But if we tried to add the user to the event and save, it would
have absolutely no effect::

    // play.php
    // ...

    // this totally works!
    $event->setOwner($user);

    // does nothing :(
    $events = $user->getEvents();
    $events[] = $event;
    $user->setEvents($events);

    $em->persist($user);
    $em->persist($event);
    $em->flush();
    
Keep this in mind when working with relationships: always set the owning side.

Review: Services
----------------

Finally, we saw how services work and how to create our own. A service is
nothing more than a class that we create that performs some task. By putting
our logic into a service, it makes it reusable, organized, and easier to unit
test. When we register a service with Symfony, we teach it how to create a
new instance of our object so that we have the convenience of simply getting
it out of the container.

We also saw a few "tags", and how you can use them to tell Symfony that your
service should be used in some special way.

So what's next? There's always more to learn about with Symfony, but we've
touched on almost all the most important things by now. In the next, and
final screencast in this series, we'll talk about assets, assetic, form customizations
and finally deployment. Seeya next time!
