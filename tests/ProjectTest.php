<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Factory\UuidFactory;

class ProjectTest extends WebTestCase
{
    private function createAuthenticatedClient(): KernelBrowser
    {
        $client = static::createClient();
        $data = [
            'username' => 'chazzbg',
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

    public function testInvalidRequest()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/project', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], 'broken json');

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getInternalResponse()->getContent(), false);
        self::assertEquals(400, $data->code);

    }

    public function testEmptyRequest()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/project', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode([]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getInternalResponse()->getContent(), false);
        self::assertEquals(422, $data->code);
    }


    public function testClientAndCompanyNotSetFields()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/project', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode([
            'title' => 'Project ' . substr(sha1(random_bytes(10)), 0, 6)
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertEquals(422, $data->code);

        self::assertObjectHasAttribute('errors', $data);
        self::assertObjectHasAttribute('clientOrCompanySet', $data->errors);
    }

    public function testSuccessfullCreation()
    {
        $client = $this->createAuthenticatedClient();

        $projectTitle = 'Project ' . substr(sha1(random_bytes(10)), 0, 6);
        $clientName = 'Client ' . substr(sha1(random_bytes(10)), 0, 6);

        $client->request('POST', '/api/project', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode([
            'title' => $projectTitle,
            'client' => $clientName
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('title', $data);
        self::assertObjectHasAttribute('client', $data);
        self::assertEquals($projectTitle, $data->title);
        self::assertEquals($clientName, $data->client);

    }

    public function testShowMissingProject()
    {
        $client = $this->createAuthenticatedClient();


        $uuidFactory = new UuidFactory();
        $client->request('GET', '/api/project/' . $uuidFactory->randomBased()->create(), [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ]);

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertEquals(404, $data->code);

    }

    public function testShowProject()
    {
        $client = $this->createAuthenticatedClient();

        $projectTitle = 'Project ' . substr(sha1(random_bytes(10)), 0, 6);
        $clientName = 'Client ' . substr(sha1(random_bytes(10)), 0, 6);

        $client->request('POST', '/api/project', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => $projectTitle,
            'client' => $clientName
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('id', $data);

        $projectId = $data->id;

        $client->request('GET', '/api/project/' . $projectId, [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ]);

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('title', $data);
        self::assertEquals($projectTitle, $data->title);
    }

    public function testEditProject()
    {
        $client = $this->createAuthenticatedClient();

        $projectTitle = 'Project ' . substr(sha1(random_bytes(10)), 0, 6);
        $clientName = 'Client ' . substr(sha1(random_bytes(10)), 0, 6);

        $client->request('POST', '/api/project', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => $projectTitle,
            'client' => $clientName
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('id', $data);

        $projectId = $data->id;

        $projectTitleEdited = $projectTitle.' Edit';

        $data->title = $projectTitleEdited;
        $client->request('PUT', '/api/project/' . $projectId, [],[],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode($data));

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('title', $data);
        self::assertEquals($projectTitleEdited, $data->title);
    }

    public function testEditWithEmptyTitleProject()
    {
        $client = $this->createAuthenticatedClient();

        $projectTitle = 'Project ' . substr(sha1(random_bytes(10)), 0, 6);
        $clientName = 'Client ' . substr(sha1(random_bytes(10)), 0, 6);

        $client->request('POST', '/api/project', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode([
            'title' => $projectTitle,
            'client' => $clientName
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('id', $data);

        $projectId = $data->id;
        $data->title = null;

        $client->request('PUT', '/api/project/' . $projectId, [],[],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode($data));

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);
        self::assertEquals(422, $data->code);
    }

    public function testDeleteAProject()
    {
        $client = $this->createAuthenticatedClient();

        $projectTitle = 'Project ' . substr(sha1(random_bytes(10)), 0, 6);
        $clientName = 'Client ' . substr(sha1(random_bytes(10)), 0, 6);

        $client->request('POST', '/api/project', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode([
            'title' => $projectTitle,
            'client' => $clientName
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('id', $data);

        $projectId = $data->id;


        $client->request('DELETE', '/api/project/' . $projectId, [],[],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ]);

        self::assertResponseIsSuccessful();
        self::assertEmpty($client->getResponse()->getContent());

    }

    public function testProjectList()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/project', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ]);

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('count', $data);

        $initialCount = $data->count;

        for($i =0; $i < 6; $i++){
            $projectTitle = 'Project ' . substr(sha1(random_bytes(10)), 0, 6);
            $clientName = 'Client ' . substr(sha1(random_bytes(10)), 0, 6);

            $client->request('POST', '/api/project', [], [],  [
                'CONTENT_TYPE' => 'application/json',
                'Content-type' => 'application/json'
            ], json_encode([
                'title' => $projectTitle,
                'client' => $clientName
            ]));
        }
        $client->request('GET', '/api/project', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ]);

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('count', $data);
        self::assertEquals(6, $data->count - $initialCount);
    }

}
