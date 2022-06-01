<?php

namespace App\Controller\Api;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;


#[Route('/api', name: 'app_api_')]
class ProjectController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('/project', name: 'project', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $repo = $this->entityManager->getRepository(Project::class);

        $projects = $repo->findAll();

        $responseData = [
            'data' => [],
            'count' => count($projects)
        ];

        foreach ($projects as $project) {
            $projectArray = [
                'id' => $project->getId(),
                'title' => $project->getTitle(),
                'description' => $project->getDescription(),
                'duration' => $project->getDuration(),
                'company' => $project->getCompany(),
                'client' => $project->getClient(),
                'status' => $project->getStatus(),
                'deleted_at' => $project->getDeletedAt()?->format('Y-m-d H:i:s'),
                'tasks' => [],
            ];

            if ($project->getTasks()->count()) {
                foreach ($project->getTasks() as $task) {
                    $projectArray['tasks'][] = [
                        'id' => $task->getId(),
                        'name' => $task->getName(),
                        'deleted_at' => $task->getDeletedAt()?->format('Y-m-d H:i:s'),
                    ];
                }
            }
            $responseData['data'] = $projectArray;
        }

        return $this->json($responseData);
    }

    #[Route('/project', name: 'project_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): Response
    {
        $project = new Project();

        $project->setTitle($request->get('title'));
        $project->setCompany($request->get('company'));
        $project->setClient($request->get('client'));
        $project->setDescription($request->get('description'));
        $project->setDuration($request->get('duration'));
        $project->setStatus($request->get('status'));

        $errors = $validator->validate($project);

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

        $repo = $this->entityManager->getRepository(Project::class);
        $repo->add($project, true);

        return new Response(
            $this->serializer->serialize($project, JsonEncoder::FORMAT,
                [AbstractNormalizer::IGNORED_ATTRIBUTES => ['clientOrCompanySet']]),
            201,
            ['Content-Type' => 'application/json']
        );
    }

    #[Route('/project/{id}', name: 'project_show', methods: ['GET'])]
    public function show(Project $project)
    {
        return new Response(
            $this->serializer->serialize($project, JsonEncoder::FORMAT,
                [AbstractNormalizer::IGNORED_ATTRIBUTES => ['clientOrCompanySet']]),
            201,
            ['Content-Type' => 'application/json']
        );

    }


    #[Route('/project/{id}', name: 'project_update', methods: ['PUT'])]
    public function update(Request $request, ValidatorInterface $validator, Project $project): Response
    {

        $project->setTitle($request->get('title'));
        $project->setCompany($request->get('company'));
        $project->setClient($request->get('client'));
        $project->setDescription($request->get('description'));
        $project->setDuration($request->get('duration'));
        $project->setStatus($request->get('status'));

        $errors = $validator->validate($project);

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

        $repo = $this->entityManager->getRepository(Project::class);
        $repo->add($project, true);

        return new Response(
            $this->serializer->serialize($project, JsonEncoder::FORMAT,
                [AbstractNormalizer::IGNORED_ATTRIBUTES => ['clientOrCompanySet']]),
            201,
            ['Content-Type' => 'application/json']
        );
    }

    #[Route('/project/{id}', name: 'project_delete', methods: ['DELETE'])]
    public function delete(Project $project)
    {
        $project->setDeletedAt(new \DateTimeImmutable());

        $repo = $this->entityManager->getRepository(Project::class);
        $repo->add($project, true);
        return new Response(
            '',
            200,
            ['Content-Type' => 'application/json']
        );

    }
}
