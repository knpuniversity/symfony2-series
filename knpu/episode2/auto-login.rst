Automatically Authenticating after Registration
===============================================

After registration, let's log the user in automatically. Create a private
function called ``authenticateUser`` inside ``RegisterController``. Normally,
authentication happens automatically, but we can also trigger it manually::

    // src/Yoda/UserBundle/Entity/Controller/RegisterController.php
    // ...

    private function authenticateUser(User $user)
    {
        $providerKey = 'secured_area'; // your firewall name
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }

This code might look strange, and I don't want you to worry about it too
much. The basic idea is that we create a token, which holds details about
the user, and then pass this into Symrony's security system.

Call this method right after registration::

    // src/Yoda/UserBundle/Entity/Controller/RegisterController.php
    // ...

    if ($form->isValid()) {
        // .. code that saves the user, sets the flash message

        $this->authenticateUser($user);

        $url = $this->generateUrl('event');

        return $this->redirect($url);
    }

Try it out! After registration, we're redirected back to the homepage. But
if you check the web debug toolbar, you'll see that we're also authenticated
as Padmé. Sweet!

.. sidebar:: Redirecting back to the original URL

    Suppose an anonymous user tries to access a page and is redirected to
    ``/login``. Then, they register for a new account. After registration,
    wouldn't it be nice to redirect them back to the page they were originally
    trying to access?

    Yes! And that's possible because Symfony stores the original, protected
    URL in the session::

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
