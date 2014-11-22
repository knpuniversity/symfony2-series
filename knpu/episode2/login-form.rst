Creating a Login Form (Part 1)
==============================

So where's the actual login form? Well, that's our job - the security layer just
helps us by redirecting the user here.

Oh, and there's a really popular open source bundle called `FosUserBundle`_
that gives you a lot of what we're about to build. The good news is that
after building a login system in this tutorial, you'll better understand how it
works. So build it once here, then take a serious look at ``FosUserBundle``.

Creating a Bundle by Hand
-------------------------

Let's create a brand new shiny bundle called ``UserBundle`` for all of our
user and security stuff. We *could* use the ``app/console generate:bundle``
task to create this, but let's do it by hand. Seriously, it's easy.

Just create a ``UserBundle`` directory and an empty ``UserBundle`` class
inside of it. A bundle is nothing more than a directory with a bundle class::

    // src/Yoda/UserBundle/UserBundle.php
    namespace Yoda\UserBundle;

    use Symfony\Component\HttpKernel\Bundle\Bundle;

    class UserBundle extends Bundle
    {
    }

Now, just activate it in the AppKernel class and, voila! Our brand new shiny
bundle is ready::

    // app/AppKernel.php
    // ...

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Yoda\UserBundle\UserBundle(),
        );

        // ...
    }

Login Form Controller
---------------------

To make the login page, add a ``Controller`` directory and put a new ``SecurityController``
class inside of it. Give the class a ``loginAction`` method. This will render
our login form::

    // src/Yoda/UserBundle/Controller/SecurityController.php
    namespace Yoda\UserBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;

    class SecurityController extends Controller
    {
        public function loginAction()
        {
        }
    }

Using Annotation Routing
------------------------

Before we fill in the guts of ``loginAction``, we need a route! After watching
episode 1, you probably expect me to create a ``routing.yml`` file in ``UserBundle``
and add a route there.

Ha! I'm not so predictable! Instead, we're going to get crazy and build our
routes right inside the controller class using annotations. The 
`docs for this feature`_ live at symfony.com under a bundle called `SensioFrameworkExtraBundle`_.
This bundle came pre-installed in our project. How thoughtful!

First, add the ``Route`` annotation namespace::

    // src/Yoda/UserBundle/Controller/SecurityController.php
    // ...

    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

    class SecurityController extends Controller
    {
        // ...
    }

Now, we can add the route right above the method::

    // src/Yoda/UserBundle/Controller/SecurityController.php
    // ...

    /**
     * @Route("/login", name="login_form")
     */
    public function loginAction()
    {
        // ... todo still..
    }

Finally, tell Symfony to look for routes in our controller by adding an import
to the main ``routing.yml`` file:

.. code-block:: yaml

    # app/config/routing.yml
    # ...

    user_routes:
        resource: "@UserBundle/Controller"
        type: annotation

Remember that Symfony never automatically finds routing files: we always
import them manually from here.

Cool - change the URL in your browser to ``/login``. This big ugly error
about our controller not returning a response is great news! No seriously,
it means that the route is working. Now let's fill in the controller!

The loginAction Logic
---------------------

Most of the login page code is pretty boilerplate. So let's use the age-old art
of copy-and-paste from the docs.

Head to the security chapter and find the `login form section`_. Copy the
``loginAction`` and paste it into our controller. Don't forget to add
the ``use`` statements for the ``SecurityContextInterface`` and ``Request``
classes::

    // src/Yoda/UserBundle/Controller/SecurityController.php
    namespace Yoda\UserBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Symfony\Component\Security\Core\SecurityContextInterface;
    use Symfony\Component\HttpFoundation\Request;
    // ...

    class SecurityController extends Controller
    {
        /**
         * @Route("/login", name="login")
         */
        public function loginAction(Request $request)
        {
            $session = $request->getSession();

            // get the login error if there is one
            if ($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
                $error = $request->attributes->get(
                    SecurityContextInterface::AUTHENTICATION_ERROR
                );
            } else {
                $error = $session->get(SecurityContextInterface::AUTHENTICATION_ERROR);
                $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);
            }

            return $this->render(
                'AcmeSecurityBundle:Security:login.html.twig',
                array(
                    // last username entered by the user
                    'last_username' => $session->get(SecurityContextInterface::LAST_USERNAME),
                    'error'         => $error,
                )
            );
        }

The method *just* renders a login template: it doesn't handle the submit
or check to see if the username and password are correct. Another layer handles
that. It *does* pass the login error message to the template if there is
one, but that's it.

.. _symfony-ep2-template-annotation:

The Template Annotation Shortcut
--------------------------------

The pasted code is rendering a template using our favorite ``render`` method
that lives in Symfony's base controller.

Hmm, let's *not* do this. Instead, let's use another shortcut: the `@Template annotation`_,
which is also from SensioFrameworkExtraBundle.

Anytime we use an annotation in a class for the first time, we'll need to
add a ``use`` statement for it. Copy this from the docs. Now, put ``@Template``
above the method and just return the array of variables you want to pass
to Twig::

    // src/Yoda/UserBundle/Controller/SecurityController.php
    // ...

    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

    class SecurityController extends Controller
    {
        /**
         * @Route("/login", name="login_form")
         * @Template()
         */
        public function loginAction()
        {
            // ...

            return array(
                // last username entered by the user
                'last_username' => $session->get(SecurityContextInterface::LAST_USERNAME),
                'error'         => $error,
            );
        }
    }

With ``@Template``, Symfony renders a template automatically, and passes
the variables we're returning into it. It's cool, saves us some typing and
supports the rebel forces.


.. _`docs for this feature`: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/routing.html
.. _`login form section`: http://symfony.com/doc/current/book/security.html#using-a-traditional-login-form
.. _`@Template annotation`: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/view.html
.. _`SensioFrameworkExtraBundle`: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/index.html
.. _`FosUserBundle`: https://github.com/FriendsOfSymfony/FOSUserBundle
