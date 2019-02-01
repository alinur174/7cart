<?php

namespace App\Controller\Api\V1;

use App\Service\Filter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use \App\Entity\Node;

class NodeController extends Controller
{
    private $filterService;

    public function __construct(Filter $sf)
    {
        $this->filterService = $sf;
    }

    /**
     * @Route("/nodes", name="node_list")
     */
    public function list(Request $request)
    {
        $catId = $request->get('category_id', 0);
        $pageNo = $request->get('page', 1);
        $perPage = abs($request->get('per_page', 10));
        $filters = $this->filterService->selectFiltersByString($request->get('f', ''));
        $allActiveAttr = $this->filterService->getAllActiveAttributesFromCategory($catId);

        $nodes = $this->getDoctrine()
            ->getRepository( Node::class)
            ->findNodesByCategory($catId, $filters, $pageNo, $perPage);

        $count = $this->getDoctrine()
            ->getRepository( Node::class)
            ->countNodesByCategory($catId, $filters);

        $meta = ['total_pages' => ceil($count/$perPage)];

        if (!$request->get('event')) {
            $meta['attributes'] = json_decode($this->get('7cart.serializer')->serialize($allActiveAttr));
        }

        if (!$request->get('event') || $request->get('event') == 'filter') {
            $meta['filter-counter'] = $this->getDoctrine()
                ->getRepository(Node::class)
                ->countAttributesByCategory($catId, $filters, $allActiveAttr);
        }

        return new Response($this->get('7cart.serializer')->serialize($nodes, $meta));
    }

    /**
     *
     * @Route("/nodes/{id}", name="node_show")
     */
    public function show($id)
    {
        $node = $this->getDoctrine()
            ->getRepository( Node::class)
            ->findOneBy(['id' => $id]);

        $meta = [];
        $allActiveAttr = $this->filterService->selectActiveAttributesByName(array_keys($node->getAttributes()));
        if ($allActiveAttr) {
            $meta['attributes'] = json_decode($this->get('7cart.serializer')->serialize($allActiveAttr));
        }

        return new Response($this->get('7cart.serializer')->serialize($node, $meta));
    }

}
