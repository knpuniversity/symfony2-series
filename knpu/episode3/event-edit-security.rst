Restricting Edit Access to Owners
=================================

Now that every ``Event`` has an owner, let's prevent that meddling Darth from
editing any events that he didn't create.

This should be pretty easy. If the current logged in ``User`` object doesn't
match the Event's owner, we'll just deny access. And remember, you can deny
access anywhere in your app just by throwing the special ``AccessDeniedException``.

Since we'll need the same security logic in ``editAction``, ``updateAction``
and ``deleteAction``, let's create a private function called ``enforceOwnerSecurity``
that holds it::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...
    
    use Symfony\Component\Security\Core\Exception\AccessDeniedException;
    // ...

    private function enforceOwnerSecurity(Event $event)
    {
        $user = $this->getUser();

        if ($user != $event->getOwner()) {
            // if you're using 2.5 or higher
            // throw $this->createAccessDeniedException('You are not the owner!!!');
            throw new AccessDeniedException('You are not the owner!!!');
        }
    }

It's now pretty simple to prevent Darth from doing things with events he
didn't create. Just call this function from ``editAction``, ``updateAction``
and ``deleteAction``::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function editAction($id)
    {
        // ...

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Event entity.');
        }

        $this->enforceOwnerSecurity($entity);
        // ...
    }
    
    // repeate for updateAction and deleteAction

Ok, log in as Darth and try to edit an event. Denied!

In the production environment, the user will see a 403 page that you can
customize. And in a few minutes, we'll show you :ref:`how <symfony2-ep3-error-template>`.

.. tip::

    There is an even cleaner, but more advanced, approach to restricting
    access to specific objects called "voters". You can learn more about
    these from our `Question and Answer Day`_. An
    even more advanced approach is available called `ACLs`_.

Now that Darth can only edit an event if he created it, add an ``if`` statement
around the edit link that hides it for all other users:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    {% if app.user == entity.owner %}
        <a class="button" href="{{ path('event_edit', {'id': entity.id}) }}">edit</a>
    {% endif %}

Remember that this works because ``app.user`` gives us the ``User`` object
for whoever is logged in.

.. _`ACLs`: http://symfony.com/doc/current/cookbook/security/acl.html
.. _`Question and Answer Day`: http://knpuniversity.com/screencast/question-answer-day/symfony2-users-menu-cms
