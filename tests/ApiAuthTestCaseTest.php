<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiAuthTestCaseTest extends WebTestCase
{
    public function testWrongCredentials(): void
    {
        $client = static::createClient();
        $data = [
            'username'=> 'free',
            'password' => 'man'
        ];
        $client->catchExceptions(false);
        $client->request('POST', '/api/login_check', [],[],[
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode($data) );


        $this->assertResponseIsSuccessful();
        $this->isJson();
        $data = json_decode($client->getInternalResponse()->getContent(), false);
        self::assertEquals(401, $data->code);
    }

    public function testTokenGeneration(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);
        $data = [
            'username'=> 'chazzbg',
            'password' => 'unforeseen_consequences'
        ];
        $client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode($data));


        $this->assertResponseIsSuccessful();
        $this->isJson();
        $data = json_decode($client->getInternalResponse()->getContent(), false);
        self::assertNotEmpty($data->token);
    }
}
