Using a shortcut Base Controller Class
======================================

Getting the ``security.context`` service requires too much typing. So let's
make some improvements so we can get things done faster.

Create a new class called ``Controller`` inside the ``EventBundle`` and make
this class extend Symfony's standard base controller. But watch out! Both
classes are called ``Controller``, so we need to alias Symfony's class to
``BaseController``::

    // src/Yoda/EventBundle/Controller/Controller.php

    namespace Yoda\EventBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;

    class Controller extends BaseController
    {
        // ...
    }

Inside this class, create a function that returns the security context from
the service container::

    // src/Yoda/EventBundle/Controller/Controller.php
    // ...
    
    public function getSecurityContext()
    {
        return $this->container->get('security.context');
    }

Using the Custom Base Controller
--------------------------------

Head back to ``EventController``. Right now, this extends Symfony's Controller,
which means that we get access to all of its shortcuts. Remove the ``use``
statement for Symfony's Controller and replace it with a ``use`` statement
for *our* fancy Controller class::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    use Yoda\EventBundle\Controller\Controller;

    class EventController extends Controller
    {
        // ...
    }

Now we can access all of Symfony's shortcut methods *and* the new ``getSecurityContext``
method we created. And actually, we don't even need the ``use`` statement
because this class lives in the same namespace as the new ``Controller`` class.

Ok! Let's use the new ``getSecurityContext`` method to shorten things::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    private function enforceUserSecurity($role = 'ROLE_USER')
    {
        if (!$this->getSecurityContext()->isGranted($role)) {
            // in Symfony 2.5
            // throw $this->createAccessDeniedException('message!');
            throw new AccessDeniedException('Need '.$role);
        }
    } 

And even though we're not really using its page, remove the ``use`` statement
in ``DefaultController`` as well so that we're using the new class::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    // ...

    // no use statement here anymore

    class DefaultController extends Controller
    {
    // ...

Change the ``use`` statements in both ``RegisterController`` and ``SecurityController``.
In ``RegisterController``, we can also take advantage of the new shortcut::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    use Yoda\EventBundle\Controller\Controller;

    class RegisterController extends Controller
    {
        // ...

        private function authenticateUser(User $user)
        {
            $providerKey = 'secured_area'; // your firewall name
            $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

            $this->getSecurityContext()->setToken($token);
        }
    }

.. code-block:: php

    // src/Yoda/UserBundle/Controller/SecurityController.php
    // ...

    use Yoda\EventBundle\Controller\Controller;
    // ...
    
    class SecurityController extends Controller

These controllers *do* need to have a ``use`` statement, because they don't
live in the same namespace as the new ``Controller`` class.

Add More Methods to Controller!
-------------------------------

Now that all of our controllers extend *our* Controller class, we can add
whatever shortcut functions we want here. For example, if we needed to check
for ``Event`` owner security in another controller, we could just move that
function into ``Controller`` and make it public::

    // src/Yoda/EventBundle/Controller/Controller.php
    // ...
    
    use Yoda\EventBundle\Entity\Event;
    use Symfony\Component\Security\Core\Exception\AccessDeniedException;
    
    class Controller extends BaseController
    {
        // ...
    
        public function enforceOwnerSecurity(Event $event)
        {
            $user = $this->getUser();

            if ($user != $event->getOwner()) {
                // if you're using 2.5 or higher
                // throw $this->createAccessDeniedException('You are not the owner!!!');
                throw new AccessDeniedException('You are not the owner!!!');
            }
        }
    }
