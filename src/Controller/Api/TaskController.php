<?php

namespace App\Controller\Api;

use App\Entity\Project;
use App\Entity\Task;
use App\Repository\TaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/project/{project_id}', name: 'app_api_')]
#[ParamConverter('project', class: Project::class, options: ['mapping' => ['project_id' => 'id']])]
class TaskController extends AbstractController
{

    /** @var TaskRepository|EntityRepository */
    private $repo;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        $this->repo = $this->entityManager->getRepository(Task::class);
    }

    #[Route('/task', name: 'task', methods: ['GET'])]
    public function index(Project $project): JsonResponse
    {

        $tasks = $this->repo->findAllByProject($project);

        $responseData = [
            'data' => [],
            'count' => count($tasks)
        ];

        foreach ($tasks as $task) {
            $responseData['data'] = [
                'id' => $task->getId(),
                'title' => $task->getName(),
            ];
        }

        return $this->json($responseData);
    }


    #[Route('/task', name: 'task_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator, Project $project): Response
    {
        $task = new Task();

        $task->setName($request->get('name'));
        $task->setProject($project);

        $errors = $validator->validate($task);

        if (count($errors)) {
            $errorMessages = [];
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = [$error->getMessage()];
            }

            return $this->json([
                'errors' => $errorMessages
            ], 422);
        }

        $this->repo->add($task, true);


        return new JsonResponse(
            [
                'id' => $task->getId(),
                'name' => $task->getName(),
            ],
            201
        );
    }

    #[Route('/task/{id}', name: 'task_show', methods: ['GET'])]
    public function show(Project $project, Task $task)
    {
        return new JsonResponse(
            [
                'id' => $task->getId(),
                'name' => $task->getName(),
            ],
            201
        );

    }


    #[Route('/task/{id}', name: 'task_update', methods: ['PUT'])]
    public function update(Request $request, ValidatorInterface $validator, Project $project, Task $task): Response
    {

        $task->setName($request->get('name'));
        $task->setProject($project);

        $errors = $validator->validate($task);

        if (count($errors)) {
            $errorMessages = [];
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = [$error->getMessage()];
            }

            return $this->json([
                'errors' => $errorMessages
            ], 422);

        }

        $this->repo->add($task, true);

        return new JsonResponse(
            [
                'id' => $task->getId(),
                'name' => $task->getName(),
            ],
            200
        );
    }

    #[Route('/task/{id}', name: 'task_delete', methods: ['DELETE'])]
    public function delete(Project $project, Task $task)
    {
        $task->setDeletedAt(new DateTimeImmutable());

        $this->repo->add($task, true);
        return new Response(
            '',
            200,
            ['Content-Type' => 'application/json']
        );

    }
}
