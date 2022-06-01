<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityTest extends WebTestCase
{
    private function createAuthenticatedClient(): KernelBrowser
    {
        $client = static::createClient();
        $data = [
            'username'=> 'chazzbg',
            'password' => 'unforeseen_consequences'
        ];
        $client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode($data));


        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getInternalResponse()->getContent(), false);
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data->token));

        return $client;
    }

    public function testUnauthenticatedRequest()
    {
        $client = static::createClient();

        $client->request("GET", '/api/project');

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $response = json_decode($client->getResponse()->getContent());
        self::assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertEquals(401, $response->code);
    }
    public function testAuthenticatedRequest()
    {
        $client = $this->createAuthenticatedClient();

        $client->request("GET", '/api/project');

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent());
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
