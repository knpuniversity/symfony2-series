Come on, Set the Content-Type Header!
=====================================

If you go to the network tab of your brower's tools and refresh, you'll find
an ugly surprise. Our response has a ``text/html`` ``Content-Type``! Silly
browser!

Ok, this is our fault. Every response has a ``Content-Type`` header and its
job is to tell the client if the page is ``text/html``, ``application/json``,
or ``text/turtle``. Yea, that's a real format. It's actually XML, so not
as cute as the name sounds.

Anyways, it's *our* job to set this header, which defaults to ``text/html``
in Symfony. Use the ``headers`` property on the ``$response`` to set it::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function attendAction($id, $format)
    {
        // ...

        if ($format == 'json') {
            // ...

            $response = new Response(json_encode($data));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        // ...
    }

Alright! Refresh again. Mmm, a beautiful ``application/json`` ``Content-Type``.

The JsonResponse Class
----------------------

Ok, so there's an even *lazier* way to do this. So throw on your sweat pants, grab
that bag of chips and let's get *lazy*. Instead of ``Response``, use a class called 
``JsonResponse`` and pass it the array directly. Oh, and get rid of the ``Content-Type`` 
header while you're in there::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    use Symfony\Component\HttpFoundation\JsonResponse;

    public function attendAction($id, $format)
    {
        // ...

        if ($format == 'json') {
            $data = array(
                'attending' => true
            );

            $response = new JsonResponse($data);

            return $response;
        }

        // ...
    }

Refresh again. Yea, we still see JSON *and* the ``Content-Type`` header is
still ``application/json``. ``JsonResponse`` is just a sub-class of ``Response``,
but it removes a few steps for us, and I like that.

Finishing up the Controller
---------------------------

Time to stop playing and finish ``unattendAction``. Just copy the logic from
``attendAction``, change the value to ``false``, and don't forget the ``$format``
argument::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function unattendAction($id, $format)
    {
        // ...
        $em->flush();

        if ($format == 'json') {
            $data = array(
                'attending' => false
            );

            $response = new JsonResponse($data);

            return $response;
        }

        $url = $this->generateUrl('event_show', array(
            'slug' => $event->getSlug()
        ));

        return $this->redirect($url);
    }

When we try it manually, it seems to work!

Removing Duplication
--------------------

Looking at these 2 methods, do you see any duplication? Um, yea, just about
every line is duplicated. We can fix at least some of this by creating a
new private method called ``createAttendingResponse`` with ``$event`` and
``$format`` arguments.

Copy in the logic that figures out which response to return::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    /**
     * @param Event $event
     * @param string $format
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function createAttendingResponse(Event $event, $format)
    {
        if ($format == 'json') {
            $data = array(
                'attending' => $event->hasAttendee($this->getUser())
            );

            $response = new JsonResponse($data);

            return $response;
        }

        $url = $this->generateUrl('event_show', array(
            'slug' => $event->getSlug()
        ));

        return $this->redirect($url);
    }

For the ``attending`` value, why not just use our ``hasAttendee`` method
to figure this out?

Sweet, let's do my favorite thing -- delete some code! Call the new method 
in ``attendAction`` and ``unattendAction`` and return its value.


We can use this function to easily generate the JSON response for both controllers::

    // src/Yoda/EventBundle/Controller/EventController.php
    use Symfony\Component\HttpFoundation\Request;
    // ...

    public function attendAction($id, $format)
    {
        // ...

        return $this->createAttendingResponse($event, $format);
    }

    public function unattendAction($id, $format)
    {
        // ...

        return $this->createAttendingResponse($event, $format);
    }

Try it out! Isn't it nice when things *don't* break?
