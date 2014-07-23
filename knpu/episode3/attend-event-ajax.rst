

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

.. _`FOSRestBundle`: https://github.com/FriendsOfSymfony/FOSRestBundle
