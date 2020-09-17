<?php

namespace App\Controller;

use App\SDK\PrintoclockBAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DocumentController extends AbstractController
{
    /**
     * @var PrintoclockBAPI
     */
    protected $businessApiClient;

    public function __construct(PrintoclockBAPI $businessApiClient)
    {
        $this->businessApiClient = $businessApiClient;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function uploadAction(Request $request)
    {
        $file = $request->files->get('document');
        if ($file instanceof File) {
            return new JsonResponse($this->businessApiClient->uploadDocument($file));
        }

        return new JsonResponse('KO', 400);
    }
}
