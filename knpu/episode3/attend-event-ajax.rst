Adding the AJAX Touch: JavaScript
=================================

Stop. We haven't touched JavaScript yet. But, because the attend and unattend
endpoints can return JSON, our app is fully ready for some AJAX. The attend/unattend
button will be a lot cooler with it anyways, so let's add some JavaScript.

Click Event to Send AJAX
------------------------

I'll give both links a ``js-attend-toggle`` class that we can look for in
jQuery:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

        {% if entity.hasAttendee(app.user) %}
            <a href="{{ path('event_unattend', {'id': entity.id}) }}"
                class="btn btn-warning btn-xs js-attend-toggle">

                Oh no! I can't go anymore!
            </a>
        {% else %}
            <a href="{{ path('event_attend', {'id': entity.id}) }}"
                class="btn btn-success btn-xs js-attend-toggle">

                I totally want to go!
            </a>
        {% endif %}

Adding the JavaScript
---------------------

Wait! We can't write jQuery without, ya know, including jQuery. So
open up the base template and add it inside the ``javascripts`` block.
I'm just going to use a CDN:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}
    
    {% block javascripts %}
        <script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
    {% endblock %}

To add JavaScript on just this page, we can override this block and call
the ``parent()`` function. I'll paste in some jQuery magic that makes an
AJAX call when the links are clicked. You can get this magic from the ``attend-javascript.js``
file in the code download:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    {% block javascripts %}
        {{ parent() }}
        
        <script>
            $(document).ready(function() {
                $('.js-attend-toggle').on('click', function(e) {
                    // prevents the browser from "following" the link
                    e.preventDefault();
    
                    var $anchor = $(this);
                    var url = $(this).attr('href')+'.json';
    
                    $.post(url, null, function(data) {
                        if (data.attending) {
                            var message = 'See you there!';
                        } else {
                            var message = 'We\'ll miss you!';
                        }
    
                        $anchor.after('<span class="label label-default">&#10004; '+message+'</span>');
                        $anchor.hide();
                    });
                });
            });
        </script>
    {% endblock %}

I know. In a perfect world, this should live in an external JavaScript file.
I'll leave that to you.

Let's try our new AJAX magic! Ooh, fancy. The link disappears and we get a cute message.

The code is simple enough: we listen on a click of either link, send an AJAX
request, then hide the link and show a message. To get the URL, I'm using
the href then adding ``.json`` to the end of it. That's actually kinda hacky.
There's a sweet bundle called `FOSJsRoutingBundle`_ that can do this much
better. It let's you actually generate Symfony routes right in JavaScript.

It's easy to use, so include it in your projects!

.. _`FOSJsRoutingBundle`: https://github.com/FriendsOfSymfony/FOSJsRoutingBundle
