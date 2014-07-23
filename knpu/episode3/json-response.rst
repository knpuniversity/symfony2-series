JSON up in your Response
========================

Yea, we can RSVP for an event. But it's not super-impressive yet. You
and I both know that a little AJAX could spice things up.

Creating JSON-returning Actions for AJAX
----------------------------------------

Our attend and unattend endpoints aren't really ready for AJAX yet. They
both return a redirect response, which really only makes sense when you want
the browser to do full page refreshes.

So why not return something different, like a JSON response? JSON is great
because it's easy to create in PHP and easy for JavaScript to understand.
And actually, could we make the endpoints return both? Why not!

Start by adding a ``format`` wildcard to both of the routes. Give it a default
value of ``html``:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # ...

    event_attend:
        pattern:  /{id}/attend.{format}
        defaults: { _controller: "EventBundle:Event:attend", format: html }

    event_unattend:
        pattern:  /{id}/unattend.{format}
        defaults: { _controller: "EventBundle:Event:unattend", format: html }

As soon as we give a wildcard a default value, it makes it optional. For
us, it means that we can now go to ``/5/attend.json``, but ``/5/attend``
still works too. So if the ``format`` part is missing, the route
still matches.

In a truly RESTful API, it's more "correct" to read the ``Accept`` header
instead of putting the format in the URL like we're doing here. If you're
interested in that, check out our `REST Series`_, it'll blow your mind.

Routing Wildcard requirements
-----------------------------

I don't really feel like also making the endpoints return XML, so let's add
a ``requirements`` key to the route:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # ...

    event_attend:
        pattern:  /{id}/attend.{format}
        defaults: { _controller: "EventBundle:Event:attend", format: html }
        requirements:
            format: json

    event_unattend:
        pattern:  /{id}/unattend.{format}
        defaults: { _controller: "EventBundle:Event:unattend", format: html }
        requirements:
            format: json

Now try going to the URL with ``.xml`` in the end. The route doesn't match!
Requirements are little regular expressions that you can use to restrict
any wildcard.

Returning a JSON Response from a Controller
-------------------------------------------

With this new wildcard in our route, we can now use it to return JSON
*or* a redirect response.

You know what the next step is: give ``attendAction`` a ``$format`` argument::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function attendAction($id, $format)
    {
        // ...
    }

If it's equal to ``json``, we can reutrn a JSON string instead of a redirect::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    use Symfony\Component\HttpFoundation\Response;

    public function attendAction($id, $format)
    {
        // ...
        $em->flush();

        if ($format == 'json') {
            $data = array(
                'attending' => true
            );

            $response = new Response(json_encode($data));

            return $response;
        }

        return $this->redirect($this->generateUrl('event_show', array(
            'slug' => $event->getSlug()
        )));
    }

How? Just create an array and then convert it to JSON with ``json_encode``.
And do you remember the cardinal rule of controllers? A controller *always*
returns a Symfony Response object. So just create a new ``Response`` object
and set the JSON as its body. It's that simple, stop over-complicating it!

Test it out by copying the link and adding ``.json`` to the end. Hello, beautiful
JSON!

.. tip::

    The JSON is pretty in my browser because of the `JSONView`_ Chrome extension.

.. _`REST Series`: knpuniversity.com/screencast/rest
.. _`JSONView`: https://chrome.google.com/webstore/detail/jsonview/chklaanhfefbnpoihckbnefhakgolnmc
