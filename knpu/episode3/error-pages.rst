Customizing Error Pages and How Errors are Handled
==================================================

Let's talk error pages. Right now, when we hit a page that's not found, we
see a big descriptive exception screen. This is because we're in the ``dev``
environment, so Symfony wants to be friendly to you, the developer. In real
life, also known as the ``prod`` environment, it's different.

Switching into the prod Environment
-----------------------------------

To see this, change the URL in your app to use ``app.php``, the front controller
for the production environment:

    http://event.local/app.php

If you have Apache rewrite enabled, you can leave this off entirely:

    http://event.local/

When we load the page, we see the white screen of death. We covered this 
in episode one, and it is expected. The ``prod`` environment is built for
speed, so its cache needs to be manually cleared after you make changes. This
step will become part of your deployment process. Head to your command line to
run the ``cache:clear`` script:

.. code-block:: bash

    php app/console cache:clear --env=prod

Refresh again, the site looks fine. When I surf to a page that doesn't exist,
I see Symfony's standard ugly 404 page. Gross! So where is the content for
this page actually coming from?

Overriding the Error Template Content
-------------------------------------

To find out, let's use a trick that I love. Head to your terminal and go into
the directory that houses Symfony:

.. code-block:: bash

    cd vendor/symfony/symfony

This directory is its own git repository independent of your project. This
is awesome, because it means we can use the super-fast "git grep" command
to search for some content that appeared on the not found page.

    git grep "An Error Occurred"

It points us straight to a file in the core Twig bundle called ``error.html.twig``.
I'll use my "Goto file" shortcut in PHPStorm to take a look at it.

.. tip::

    The location of the file is:
    
        vendor/symfony/symfony/src/Symfony/Bundle/TwigBundle/Resources/views/Exception/error.html.twig

So, we've found the file, but how can we override it? Even though it lives
in the core of Symfony, the ``TwigBundle`` is just an ordinary bundle, and
Symfony has a built-in feature to override bundle templates in your application.
To make this happen, we need to create a template file by the same name in *just*
the right spot. In this example, create an
``app/Resources/TwigBundle/views/Exception/error.html.twig`` file. Notice how
similar both templates end in ``views/Exception/error.html.twig``. This method
can be used to override a bundle from *any* template.

.. tip::

    ``app/Resources/AnyBundle/views/SomeDir/myTemplate.html.twig``
    will always override
    ``@AnyBundle/Resources/views/SomeDir/myTemplate.html.twig``

Put some dummy content in the template and try it out:

.. code-block:: html+jinja

    {# app/Resources/TwigBundle/views/Exception/error.html.twig #}

    <section class="main-block">
        <article>
            <section>
                <h1>Ah crap!</h1>

                <div>These are not the droids you're looking for...</div>
            </section>
        </article>
    </section>

Remember, we're in the ``prod`` environment, so to make sure we're seeing
the latest changes, we need to clear the cache:

.. code-block:: bash

    php app/console cache:clear --env=prod

When we refresh, we see our custom template. Make the error page a little
more complete by extending our base template and adding our own content:

.. code-block:: html+jinja

    {# app/Resources/TwigBundle/views/Exception/error.html.twig #}
    {% extends '::base.html.twig' %}

    {% block body %}
    <section class="main-block">
        <article>
            <section>
                <h1>Ah crap!</h1>

                <div>These are not the droids you're looking for...</div>
            </section>
        </article>
    </section>
    {% endblock %}

Clear the cache again and refresh the page. Hmm, our layout is there, but
the CSS isn't loading. To fix this, run the ``assetic:dump`` task:

.. code-block:: bash

    php app/console assetic:dump --env=prod

Don't worry about what this is doing yet - we'll talk about it in the next
screencast. Customizing error pages can be a pain, since you have to clear
the cache and if you make a mistake, you won't see the error on screen.

Customizing Error Pages by Type (Status Code)
---------------------------------------------

That was easy, right? So the problem now is that this new template will be
rendered when any type of error is shown on the site - whether that's a 404
page not found error, a 403 Forbidden error for security, or a 500 error page,
which. ahem, you'll of course never see.

Fortunately, Symfony offers us a special trick that works for error pages.
By creating a Twig template called ``error404.html.twig``, Symfony will use
it instead of the generic ``error.html.twig`` for 404 pages:

.. code-block:: html+jinja

    {# app/Resources/TwigBundle/views/Exception/error404.html.twig #}
    {% extends '::base.html.twig' %}

    {% block body %}
    <section class="main-block">
        <article>
            <section>
                <h1>Ah crap!</h1>

                <div>These are not the droids you're looking for...</div>
            </section>
        </article>
    </section>
    {% endblock %}

We can also keep the generic error template, but let's give it some generic
language:

.. code-block:: html+jinja

    {# app/Resources/TwigBundle/views/Exception/error.html.twig #}

    {# ... #}
    <h1>Ah crap!</h1>

    <div>The servers are on fire!!!</div>

To check it, clear your cache and refresh again. Now we have a styled generic error
page and a special version for 404 pages.

Investigating how the Error Page is Rendered
--------------------------------------------

Customizing an error template is great, but sometimes you need more. Symfony
allows you two other types of hooks when you need to do something on an error.
The first is called a "listener", which we'll save for another screencast.
But the idea is simple: create a class and tell Symfony to execute one of
its methods whenever there's an exception. Inside that method, you can really
do anything. To see how listeners work in general, checkout the
`cookbook entry about adding a custom mime type`_. Then go look at how this
can be used to `listen to exceptions`_.

The second way to hook into an error is even more straightforward. When an
exception is thrown, Symfony executes an internal controller that renders
the error page. Even though the circumstances are different, this controller
looks and works just like any that you and I create. When we overrode the
error template, we were overriding the template rendered by this controller.

So where is this controller? To find out, head to the reference section in
the documentation and click into the Twig section. The ``exception_controller``
setting is the key, and points us to the one being used:

    twig.controller.exception:showAction

Notice that unlike the ``_controller`` values that we've been using in our
routing, this only has one colon. This is a special syntax for a controller
when the controller is registered as a service. It means that there is a
service called ``twig.controller.exception`` and that the ``showAction``
method is called on it.

To find the class behind this service, we can use our handy ``container:debug``
command:

.. code-block:: bash

    php app/console container:debug twig.controller.exception

The class is ``Symfony\Bundle\TwigBundle\Controller\ExceptionController``.

Let's open up the file and take a look. The ``showAction`` probably has a
few strange looking things, but overall, it's just a fancy bit of code to
render a template. In fact, it uses a protected function called ``findTemplate``
to figure out exactly which template to render::

    protected function findTemplate(Request $request, $format, $code, $debug)
    {
        $name = $debug ? 'exception' : 'error';
        if ($debug && 'html' == $format) {
            $name = 'exception_full';
        }

        // when not in debug, try to find a template for the specific HTTP status code and format
        if (!$debug) {
            $template = new TemplateReference('TwigBundle', 'Exception', $name.$code, $format, 'twig');
            if ($this->templateExists($template)) {
                return $template;
            }
        }

        // ...
    }

Inside of it, we can see how a verbose ``exception`` template is rendered
when we're in debug mode and a cleaner ``error`` template is rendered in production.
You can also see some code that allows us to create the error template with
the status code appended to it. All the magic is in this class.

Rendering an Embedded Controller
--------------------------------

Suppose now that we want to override the 404 page not only to show a message,
but also to show a list of the current events.

The problem, of course, is that we don't have a list of the upcoming events
inside the error template. Whenever you're in a template and you don't have
access to something you need, you should think about using the Twig ``render``
tag. It works like this.

First, create a new controller that will render the upcoming events, but
without a layout::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function _upcomingEventsAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('EventBundle:Event')
            ->getUpcomingEvents()
        ;

        return array(
            'entities' => $entities,
        );
    }

When a controller only renders a partial page, I usually like to prefix it
with an underscore, but this isn't necessary. Before we finish the controller,
let's abstract the event listing code into its own template and include it
from ``index.html.twig`` with an ``include``:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/_events.html.twig #}
    
    {% for entity in entities %}
        {# ... all the event-rendering code, copied from index.html.twig #}
    {% endfor %}

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}

    {# where the "for" loop through entities used to be #}
    {{ include('EventBundle:Event:_events.html.twig') }}
    {# ... #}

With that done, we can render the new mini-template directly from ``_upcomingEventsAction``::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    /**
     * @Template("EventBundle:Event:_events.html.twig")
     */
    public function _upcomingEventsAction()
    {
        // ...
    }

.. tip::

    Remember that ``@Template`` is just an alternative way to `render a template`_.

Try out the homepage to make sure everything is still working properly.

We now have a controller that renders *just* a list of events, without a
layout. We haven't given this controller a route, but we don't need to.
To use it, use a special Twig "render" tag:

.. code-block:: html+jinja

    {# app/Resources/TwigBundle/views/Exception/error404.html.twig #}
    {# ... #}

    {% block body %}
        {# ... #}

        <section class="events">
            {{ render(controller('EventBundle:Event:_upcomingEvents')) %}
        </section>
    {% endblock %}

Unlike the "include" tag which displays a template, and expects all the variables
needed to be passed in, ``render`` executes a controller, where you can prepare
data and then render a template.

Let's try this out. But first, since doing real development in the ``prod``
environment can be tough, let's temporarily short-circuit the exception controller
so that it renders our error template in the ``dev`` environment::

    // vendor/symfony/symfony/src/Symfony/Bundle/TwigBundle/Controller/ExceptionController.php
    // ...
    
    protected function findTemplate(Request $request, $format, $code, $debug)
    {
        // temporarily add this into the core of Symfony
        $debug = false;
        
        // ...
    }

Now surf to a made-up page in the dev environment - you should see the message along with
the event list:

    http://events.local/app_dev.php/foo

If you get an error in this situation, it may be tough to track down. Since
the error is inside an error, the messages tend to be abstract. The best thing
you can do is move your logic temporarily to a real controller, debug it there,
and then move it back.

Overriding the ExceptionController
----------------------------------

I want to add one more complication. Suppose that if a user is looking specifically
for an event that doesn't exist then we may want to show them a slightly different
error page. To accomplish this, let's override Symfony's base ``ExceptionController``.

Start by creating a new ``ExceptionController`` in EventBundle. Make the controller
extend Symfony's version so we can override it::

    // src/Yoda/EventBundle/Controller/ExceptionController.php
    namespace Yoda\EventBundle\Controller;

    use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseController;

    class ExceptionController extends BaseController
    {
    }

To make Symfony use our new controller on errors, change the ``exception_controller``
setting in ``config.yml``. But first, since the base `ExceptionController`
is registered as a service, we need to register our controller as a service.
We're actually going to talk about services in the next chapter, so don't
worry too much about the details here.

While most people don't do it, you can optionally register any controller
as a service. It's not really important right now, but you can read more
about it at `How to define Controllers as Services`.

Open up the ``config.yml`` define the service. We'll use a services trick
called `parent`_, which basically tells Symfony that our new service should
have all the same properties as the original service, except with a new class
name:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    services:
        yoda_event.controller.exception_controller:
            parent: twig.controller.exception
            class:  Yoda\EventBundle\Controller\ExceptionController

Again, don't worry about all of this right now. After learning about services,
you can read about controllers as services and this should make more sense.

Finally, set the ``exception_controller`` setting we saw earlier in the docs
to point to our new service:

.. code-block:: yaml

    # app/config/config.yml
    # ...
    
    twig:
        exception_controller: "yoda_event.controller.exception_controller:showAction"

Exceptions, Handling, and 404 versus 500 Pages
----------------------------------------------

Let's back up quickly to talk about how a 404 page occurs. Basically, whenever
*any* exception is thrown, the ``ExceptionController`` is eventually called.
Since an exception means that something went wrong, this usually results in
a 500 HTTP status code. But certain exception classes are different. Take
a look at the ``EventController`` where we call the ``createNotFoundException`` method::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function attendAction($id)
    {
        // ...
        
        if (!$event) {
            throw $this->createNotFoundException('No event found for id '.$id);
        }
    }

If you look in the base controller class, you can see that this is just a shortcut
to create a special type of exception called ``NotFoundHttpException``::

    // vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Controller/Controller.php
    // ...
    
    use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
    // ...

    public function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($message, $previous);
    }

This is a special class. It works like throwing any other exception, but the
final page has a 404 status code instead of 500. To create a 404 page, we're
actually just throwing this special exception class.

Creating a new Exception for special Handling
---------------------------------------------

I want to render a different template only for *some* 404 pages. Create a
new Exception class and make it extend Symfony's special ``NotFoundHttpException``::

    // src/Yoda/EventBundle/Exception/EventNotFoundException.php
    namespace Yoda\EventBundle\Exception;

    use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

    class EventNotFoundException extends NotFoundHttpException
    {
    }

The class is blank, but it's already really useful. Back in ``EventController``,
throw this exception instead of the standard exception on the event show page::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    use Yoda\EventBundle\Exception\EventNotFoundException;

    public function showAction($slug)
    {
        // ...

        if (!$entity) {
            throw new EventNotFoundException();
        }
    }

So far, everything should still work just like before. Refresh the 404 page
to make sure.

Now, let's put in the magic. Override the ``showAction`` method - and be sure
to add ``use`` statements for the three new classes::

    // src/EventBundle/Controller/ExceptionController.php
    // ...

    use Symfony\Component\HttpKernel\Exception\FlattenException;
    use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
    use Symfony\Component\HttpFoundation\Request;
    // ...

    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null, $format = 'html')
    {
        return parent::showAction($request, $exception, $logger, $format);
    }

The ``FlattenException`` object is a summary of the exception that was thrown.
You can use ``getClass`` to get the class name for the original exception.
Let's use this to do something different if we detect that our exception class
is being thrown. For now, just store the class as a property on the controller::

    // src/EventBundle/Controller/ExceptionController.php
    // ...

    class ExceptionController extends BaseController
    {
        private $exceptionClass;

        public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null, $format = 'html')
        {
            $this->exceptionClass = $exception->getClass();

            return parent::showAction($request, $exception, $logger, $format);
        }
    }

Next, override the ``findTemplate`` method - this is really where we want to
short-circuit things. Add an if statement checking for the class name with
some debug code::

    // src/EventBundle/Controller/ExceptionController.php
    // ...

    protected function findTemplate(Request $request, $format, $code, $debug)
    {
        if ($this->exceptionClass == 'Yoda\EventBundle\Exception\EventNotFoundException') {
            die('debuggin!');
        }

        return parent::findTemplate($request, $format, $code, $debug);
    }

When we refresh the 404 at the root level, it still works. But when we request
the event show page with a bad event (e.g. ``/fake-event/show``), our error catches!
Return a custom template in this case::

    if ($this->exceptionClass == 'Yoda\EventBundle\Exception\EventNotFoundException') {
        return 'EventBundle:Exception:error404.html.twig';
    }

I'll copy the existing 404 template and customize it just a little bit:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Exception/error404.html.twig #}
    {% extends '::base.html.twig' %}

    {% block body %}
    <section class="main-block">
        <article>
            <section>
                <h1>Event not found!</h1>

                <div>But check out these other events...</div>
            </section>
        </article>
    </section>

    <section class="events">
         {% render 'EventBundle:Event:_upcomingEvents' %}
    </section>
    {% endblock %}

Refresh to see our special "event not found" page. Then head back to another
404 page to see the other template. Awesome!

Putting the Pieces back Together
--------------------------------

Quickly, let's make our special template only show when we're *not* in debug
mode::

    // src/EventBundle/Controller/ExceptionController.php
    // ...

    protected function findTemplate(Request $request, $format, $code, $debug)
    {
        if (!$debug && $this->exceptionClass == 'Yoda\EventBundle\Exception\EventNotFoundException') {
            return 'EventBundle:Exception:error404.html.twig';
        }

        return parent::findTemplate($request, $format, $code, $debug);
    }

This will let the normal, expressive error page show while we're developing.
Let's also finally remove our temporary hack inside Symfony so that this all
works again::

    // vendor/symfony/symfony/src/Symfony/Bundle/TwigBundle/Controller/ExceptionController.php
    // ...

    protected function findTemplate(Request $request, $format, $code, $debug)
    {
        // remove this code, which we put there earlier
        // $debug = false;

        // ...
    }

After a refresh, we see our big developer error template. To make sure the
error pages actually work, clear your cache and refresh in the ``prod`` environment:

.. code-block:: bash

    php app/console cache:clear --env=prod

We have one error page for missing events, and a totally different page for
all the other 404 pages.

.. _`listen to exceptions`: http://bit.ly/sf2-error-listener
.. _`render a template`: http://bit.ly/sf2-extra-template
.. _`How to define Controllers as Services`: http://symfony.com/doc/current/cookbook/controller/service.html
.. _`cookbook entry about adding a custom mime type`: http://symfony.com/doc/current/cookbook/request/mime_type.html
.. _`parent`: http://symfony.com/doc/current/components/dependency_injection/parentservices.html
