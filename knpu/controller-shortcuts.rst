Do Less Work in the Controller
==============================

Great work! You've almost finished the entire first video about Symfony2.
If you're feeling overwhelmed - don't worry - like all frameworks, Symfony2
does have a learning curve. But you're becoming a better developer.

In this chapter, I am sharing some tricks and shortcuts you can use. I could
go on for another hour showing you cool stuff, but I'll pick just 4 things
for now.

The @Template Rendering Shortcut
--------------------------------

For cool things number 1 and 2, head to the Symfony documentation, click "Bundles"
on the left, and then click "SensioFrameworkExtraBundle". This bundle is
included in the Symfony Standard edition, and it's a, well, bundle of cool
stuff:

    http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/index.html

In the docs, scroll down to the ``@Template`` link and click it. Copy the ``use``
line from the code block and paste it into ``EventController``::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...
    
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

    class EventController extends Controller
    {
        // ...
    }

Instead of calling the ``render`` method, just return the array of variables
that you want to pass to your template. Finally, add an ``@Template`` annotation
above ``indexAction`` and pass it the template name::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    /**
     * @Template("EventBundle:Event:index.html.twig")
     */
    public function indexAction()
    {
        // ...
        
        return array(
            'entities' => $entities,
        );
    }

When we check the page in our browser, it works perfectly. This is a bit magical,
but it's really cool! This is telling Symfony to render the template for us.

But we can do even better. Remove the template name and refresh::

    /**
     * @Template()
     */
    public function indexAction()
    {
        // ...
    }

It still works! If we don't pass a template name, it guesses the template
based on the controller and action name. Since we've followed a good standard
by putting the template at ``Event/index.html.twig``, Symfony finds it automatically.

A quick note about the ``use`` statement we added. Whenever you use an annotation,
you have to include the ``use`` statement defining it. If you don't, you'll
see an exception about the namespace not being imported:

    [SemanticalError] The annotation "@Template" in method .. was never imported.
    Did you maybe forget to add a "use" statement for this annotation?

So if you see this error, you're missing your annotation ``use`` statement.
We actually saw this already in our ``Event`` entity, which imported and then
used the ``ORM`` annotation.

Annotation Routing
------------------

Ok, cool thing #2 involves routing. Start by removing the ``event`` route.
When you refresh, you'll of course see a 404 page. On the docs, go back and
click the `@Route and @Method`_ link. Like before, copy the ``use`` statement
into our controller. Next, put an ``@Route`` annotation above ``indexAction``::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

    class EventController extends Controller
    {
        /**
         * @Template()
         * @Route("/")
         */
        public function indexAction()
        {
            // ...
        }
    }

Importing Routes from a Controller
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

As you've probably already figured out, this lets us define a route to the
controller right above it. But like all other routing - it needs to be imported
before it works.

So far, we've used the ``resource`` key to import other YAML routing files.
But now, we can point it at the entire Controller directory, and pass it
a ``type`` option of ``annotation``:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    # ...
    
    EventBundle_event_annotation:
        resource: "@EventBundle/Controller"
        prefix:   /
        type:     annotation

This tells Symfony to scan that directory for PHP files with the ``@Route``
annotation. When we refresh, the page works just like before.

TODO - show it hitting the original route first (WDT), remove that route

But when we go to create a new event, we get an error! In the ``new.html.twig``
file, we're referencing our main page by referring to the route name - ``event``.
When we run ``router:debug``, we can see the new route that's being added
via the annotations. But instead of being called ``event``, it has a bit of
a longer name.

To name this route ``event`` once again, add a ``name="event"`` key to the
routing annotation::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    /**
     * @Template()
     * @Route("/", name="event")
     */
    public function indexAction()
    {
        // ...
    }

Without this, Symfony generates an arbitrary name. That's ok, but as soon
as you need to link to a route, you need to control its name.

For those of you looking to please weaverryan or how to create more complex
routes in annotations, check out the rest of the documentation.

.. _`@Route and @Method`: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/routing.html
