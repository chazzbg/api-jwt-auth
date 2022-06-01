<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Factory\UuidFactory;

class TaskTest extends WebTestCase
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


        $data = json_decode($client->getInternalResponse()->getContent(), false);
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data->token));

        return $client;
    }

    public function createProject($client)
    {

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

        return $data->id;
    }


    public function testInvalidRequest()
    {
        $client = $this->createAuthenticatedClient();
        $projectId = $this->createProject($client);

        $client->request('POST', '/api/project/'.$projectId.'/task', [], [],  [
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
        $projectId = $this->createProject($client);

        $client->request('POST', '/api/project/'.$projectId.'/task', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode([]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getInternalResponse()->getContent(), false);
        self::assertEquals(422, $data->code);
    }


    public function testSuccessfullCreation()
    {
        $client = $this->createAuthenticatedClient();
        $projectId = $this->createProject($client);

        $taskName = 'Task ' . substr(sha1(random_bytes(10)), 0, 6);

        $client->request('POST', '/api/project/'.$projectId.'/task', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode([
            'name' => $taskName
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);


        self::assertObjectHasAttribute('name', $data);
        self::assertEquals($taskName, $data->name);

    }

    public function testShowMissingTask()
    {
        $client = $this->createAuthenticatedClient();
        $projectId = $this->createProject($client);


        $uuidFactory = new UuidFactory();
        $client->request('GET', '/api/project/'.$projectId.'/task/' . $uuidFactory->randomBased()->create(), [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ]);

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertEquals(404, $data->code);

    }

    public function testShowTask()
    {
        $client = $this->createAuthenticatedClient();
        $projectId = $this->createProject($client);

        $taskName = 'Task ' . substr(sha1(random_bytes(10)), 0, 6);

        $client->request('POST', '/api/project/'.$projectId.'/task', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => $taskName
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('id', $data);

        $taskId = $data->id;

        $client->request('GET', '/api/project/'.$projectId.'/task/' . $taskId, [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ]);

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('name', $data);
        self::assertEquals($taskName, $data->name);
    }

    public function testEditTask()
    {
        $client = $this->createAuthenticatedClient();
        $projectId = $this->createProject($client);

        $taskName = 'Task ' . substr(sha1(random_bytes(10)), 0, 6);

        $client->request('POST', '/api/project/'.$projectId.'/task', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => $taskName,
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('id', $data);

        $taskId = $data->id;

        $taskNameEdited = $taskName.' Edit';

        $data->name = $taskNameEdited;
        $client->request('PUT', '/api/project/'.$projectId.'/task/' . $taskId, [],[],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode($data));

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('name', $data);
        self::assertEquals($taskNameEdited, $data->name);
    }

    public function testEditWithEmptyName()
    {
        $client = $this->createAuthenticatedClient();
        $projectId = $this->createProject($client);

        $taskName = 'Task ' . substr(sha1(random_bytes(10)), 0, 6);

        $client->request('POST', '/api/project/'.$projectId.'/task', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode([
            'name' => $taskName,
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('id', $data);

        $taskId = $data->id;
        $data->name = null;

        $client->request('PUT', '/api/project/'.$projectId.'/task/' . $taskId, [],[],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode($data));

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);
        self::assertEquals(422, $data->code);
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();
        $projectId = $this->createProject($client);

        $taskName = 'Task ' . substr(sha1(random_bytes(10)), 0, 6);

        $client->request('POST', '/api/project/'.$projectId.'/task', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ], json_encode([
            'name' => $taskName
        ]));

        self::assertResponseIsSuccessful();

        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('id', $data);

        $taskId = $data->id;


        $client->request('DELETE', '/api/project/'.$projectId.'/task/' . $taskId, [],[],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ]);

        self::assertResponseIsSuccessful();
        self::assertEmpty($client->getResponse()->getContent());

    }

    public function testList()
    {
        $client = $this->createAuthenticatedClient();
        $projectId = $this->createProject($client);

        for($i =0; $i < 6; $i++){
            $taskName = 'Task ' . substr(sha1(random_bytes(10)), 0, 6);


            $client->request('POST', '/api/project/'.$projectId.'/task', [], [],  [
                'CONTENT_TYPE' => 'application/json',
                'Content-type' => 'application/json'
            ], json_encode([
                'name' => $taskName
            ]));
        }
        $client->request('GET', '/api/project/'.$projectId.'/task', [], [],  [
            'CONTENT_TYPE' => 'application/json',
            'Content-type' => 'application/json'
        ]);

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getInternalResponse()->getContent(), false);

        self::assertObjectHasAttribute('count', $data);
        self::assertEquals(6, $data->count );
    }

}
