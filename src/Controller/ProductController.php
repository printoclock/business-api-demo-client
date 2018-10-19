<?php

namespace App\Controller;

use App\SDK\PrintoclockBAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
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
     * @Route("/")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function listAction()
    {
        return $this->render('products.html.twig', array(
            'products' => $this->businessApiClient->getProducts(),
        ));
    }

    /**
     * @Route("/products/{code}")
     *
     * @param string $code
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function getAction($code)
    {
        return $this->render('product.html.twig', array(
            'product' => $this->businessApiClient->getProduct($code),
        ));
    }

    /**
     * @Route("/products/{productId}/variants")
     *
     * @param Request $request
     * @param int $productId
     * @return JsonResponse
     * @throws \Exception
     */
    public function getVariantsAction(Request $request, $productId)
    {
        $optionValueIds = $request->get('optionValueIds');
        $userInputs = $request->get('userInputs');

        return new JsonResponse($this->businessApiClient->getProductVariants($productId, $optionValueIds, $userInputs));
    }
}
