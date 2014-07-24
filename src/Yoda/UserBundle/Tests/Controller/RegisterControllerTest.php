<?php

namespace Yoda\EventBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterControllerTest extends WebTestCase
{
    public function testRegister()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Register', $response->getContent());

        $usernameVal = $crawler
            ->filter('#user_register_username')
            ->attr('value')
        ;
        $this->assertEquals('Leia', $usernameVal);
    }
}
