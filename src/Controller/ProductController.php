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
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function listAction(Request $request)
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', PrintoclockBAPI::DEFAULT_PAGE_LIMIT);
        $response = $this->businessApiClient->getProducts($page, $limit);
        $products = json_decode($response->getBody()->getContents(), true);
        $availableLinks = $this->businessApiClient->getLinksFromResponse($response);

        return $this->render('products.html.twig', array(
            'products' => $products,
            'links' => $availableLinks,
        ));
    }

    /**
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
     * @param Request $request
     * @param string $productCode
     * @return JsonResponse
     * @throws \Exception
     */
    public function getVariantsAction(Request $request, $productCode)
    {
        $optionValueCodes = $request->get('optionValueCodes');
        $userInputs = $request->get('userInputs');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', PrintoclockBAPI::DEFAULT_PAGE_LIMIT);
        $response = $this->businessApiClient->getProductVariants($productCode, $optionValueCodes, $userInputs, 'FR', null, $page, $limit);
        $variants = json_decode($response->getBody()->getContents(), true);
        $availableLinks = $this->businessApiClient->getLinksFromResponse($response);

        return new JsonResponse(array(
            'variants' => $variants,
            'links' => $availableLinks,
        ));
    }

    /**
     * @param Request $request
     * @param string $variantCode
     * @return JsonResponse
     * @throws \Exception
     */
    public function getVariantAction(Request $request, $variantCode)
    {
        return new JsonResponse($this->businessApiClient->getVariant($variantCode));
    }
}
