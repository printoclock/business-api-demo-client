<?php

namespace App\Controller;

use App\SDK\PrintoclockBAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
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
     * @Route("/orders", methods={"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createAction(Request $request)
    {
        $data = $request->request->all();

        return new JsonResponse($this->businessApiClient->createOrder($data));
    }

    /**
     * @Route("/orders", methods={"GET"})
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function indexAction(Request $request)
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', PrintoclockBAPI::DEFAULT_PAGE_LIMIT);
        $response = $this->businessApiClient->getOrders($page, $limit);
        $orders = json_decode($response->getBody()->getContents(), true);
        $availableLinks = $this->businessApiClient->getLinksFromResponse($response);

        return $this->render('orders.html.twig', array(
            'orders' => $orders,
            'links' => $availableLinks,
        ));
    }
}
