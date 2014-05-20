Automatically Authenticating after Registration
===============================================

Before we try this out, let's also automatically log the user in after registration.
To do this, create a private function inside the controller. Normally, authentication
happens automatically, but we can also trigger it manually::

    // src/Yoda/UserBundle/Entity/Controller/RegisterController.php
    // ...

    private function authenticateUser(UserInterface $user)
    {
        $providerKey = 'secured_area'; // your firewall name
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }

This code might look a little strange, but don't worry about that now. The
basic idea is that we create an authentication package, called a token, and
pass it off to Symfony's security system. Call this method after registration
to automatically log the user in::

    // src/Yoda/UserBundle/Entity/Controller/RegisterController.php
    // ...

    if ($form->isValid()) {
        // .. code that saves the user, sets the flash message

        $this->authenticateUser($user);

        $url = $this->generateUrl('event');

        return $this->redirect($url);
    }

Head back to the browser to try the whole process. After registration, we're
redirected back to the homepage, but this time with our message. If you check
the web debug toolbar, you'll see that we're also authenticated as the new
user. Perfect, that was easy, right?!

.. sidebar:: Redirecting back to the original URL

    If you want to redirect to the page that the user was trying to request
    before being forced to register, you can take advantage of the fact that
    this URL is stored to the session::

        $key = '_security.'.$providerKey.'.target_path';
        $session = $this->getRequest()->getSession();

         // get the URL to the last page, or fallback to the homepage
         if ($session->has($key)) {
             $url = $session->get($key)
             $session->remove($key);
         } else {
             $url = $this->generateUrl('homepage');
         }

    The session storage key used here is pretty internal, and could change
    in the future. So use with caution!
