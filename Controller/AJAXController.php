<?php

/*
 * Copyright 2011 Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Bluemesa\Bundle\FliesBundle\Controller;

use Bluemesa\Bundle\AclBundle\Doctrine\OwnedObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\NoResultException;

use Bluemesa\Bundle\CoreBundle\Controller\AbstractController;
use Bluemesa\Bundle\AclBundle\Controller\SecureController;
use Bluemesa\Bundle\FliesBundle\Search\SearchQuery;
use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Bluemesa\Bundle\FliesBundle\Entity\StockVial;
use Bluemesa\Bundle\FliesBundle\Entity\CrossVial;
use Bluemesa\Bundle\FliesBundle\Entity\InjectionVial;
use Bluemesa\Bundle\FliesBundle\Entity\Stock;
use Bluemesa\Bundle\FliesBundle\Entity\Rack;
use Bluemesa\Bundle\FliesBundle\Entity\RackPosition;

/**
 * Description of AJAXController
 *
 * @Route("/_ajax")
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class AJAXController extends AbstractController
{
    use SecureController;
    
    /**
     * {@inheritdoc}
     */
    protected function getObjectManager($object = null)
    {
        return parent::getObjectManager('Bluemesa\Bundle\AclBundle\Entity\OwnedEntity');
    }
    
    /**
     * @Route("/foods")
     *
     * @param  \Symfony\Component\HttpFoundation\Request       $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function foodsAction(Request $request)
    {
        $food = $request->query->get('query');
        
        $qb = $this->getObjectManager()->getRepository('BluemesaFliesBundle:Vial')->createQueryBuilder('v');
        $qb->select('v.food')->groupBy('v.food');
        $foods = $qb->getQuery()->getArrayResult();
        
        $result = array();
        if (! empty($food)) {
            $result[] = array('id' => $food,'text' => ucfirst($food));
        }
        
        foreach($foods as $item) {
            $food = $item['food'];
            if (null !== $food) {
                $result[] = array('id' => $food,'text' => ucfirst($food));
            }
        }
        
        return new JsonResponse($result);
    }
    
    /**
     * @Route("/genotypes")
     *
     * @param  \Symfony\Component\HttpFoundation\Request       $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function genotypesAction(Request $request)
    {
        $id = $request->query->get('id');
        $query = $request->query->get('query');
        
        $om = $this->getObjectManager();
        try {
            $vial = $om->find('BluemesaFliesBundle:Vial', $id);
        } catch (NoResultException $e) {
            return new JsonResponse(array());
        }
        
        $genotypes = $vial->getGenotypes();
        $terms = explode(' ', $query);
        $export = array();
        
        foreach ($genotypes as $key => $genotype) {
            $remove = true;
            foreach ($terms as $term) {
                $remove = $remove && (strpos($genotype, $term) === false) && (!empty($term));
            }
            if ($remove) {
                unset($genotypes[$key]);
            } else {
                $export[] = array('genotype' => $genotype);
            }
        }
        
        return new JsonResponse($export);
    }
    
    /**
     * @Route("/fbstock")
     *
     * @param  \Symfony\Component\HttpFoundation\Request       $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function flybaseStockAction(Request $request)
    {
        $stock = $request->query->get('stock');
        $vendor = $request->query->get('vendor', '');
        $sql = <<<FLYBASE_SQL
SELECT stockcollection.uniquename AS stock_center,
    stock.name AS stock_id,
    'http://flybase.org/reports/' || stock.uniquename || '.html' AS stock_link,
    genotype.uniquename AS stock_genotype
    FROM stock
    JOIN stock_genotype on stock.stock_id = stock_genotype.stock_id
    JOIN genotype on stock_genotype.genotype_id = genotype.genotype_id
    JOIN stockcollection_stock on stock.stock_id = stockcollection_stock.stock_id
    JOIN stockcollection on stockcollection_stock.stockcollection_id = stockcollection.stockcollection_id
    WHERE stock.name ILIKE :stock
FLYBASE_SQL;
        if ($vendor !== '') {
             $sql .= ' AND stockcollection.uniquename ILIKE :vendor';
        }
        $sql .= ' ORDER BY char_length(stock.name), stock.name LIMIT 10';
        $conn = $this->get('doctrine.dbal.flybase_connection');
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("stock", "%" . $stock . "%");
        if ($vendor !== '') {
            $stmt->bindValue("vendor", "%" . $vendor . "%");
        }
        $stmt->execute();
        $stocks = $stmt->fetchAll();
        
        return new JsonResponse($stocks);
    }
    
    /**
     * @Route("/fbvendor")
     *
     * @param  \Symfony\Component\HttpFoundation\Request       $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function flybaseVendorAction(Request $request)
    {
        $vendor = $request->query->get('vendor');
        $sql = <<<FLYBASE_SQL
SELECT stockcollection.uniquename AS stock_center
    FROM stockcollection
    WHERE stockcollection.uniquename ILIKE :vendor
FLYBASE_SQL;
        $conn = $this->get('doctrine.dbal.flybase_connection');
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("vendor", "%" . $vendor . "%");
        $stmt->execute();
        $vendors = $stmt->fetchAll();
        
        return new JsonResponse($vendors);
    }
    
    /**
     * Handle vial AJAX request
     *
     * @Route("/vials")
     * @Template()
     *
     * @param  \Symfony\Component\HttpFoundation\Request   $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function vialAction(Request $request)
    {
        $id = $request->query->get('id');
        $filter = $request->query->get('filter', null);
        $format = $request->query->get('format', null);
        $order = $request->query->get('order', null);

        $om = $this->getObjectManager();
        $authorizationChecker = $this->getAuthorizationChecker();
        try {
            $vial = $om->find('BluemesaFliesBundle:Vial', $id);
        } catch (NoResultException $e) {
            $vial = null;
        }
        $type = $filter !== null ? ' ' . $filter : '';

        if ((! $vial instanceof Vial)||(($filter !== null)&&($vial->getType() != $filter))) {
            return new Response('The' . $type . ' vial ' . sprintf("%06d",$id) . ' does not exist', 404);
        } elseif (!($authorizationChecker->isGranted('ROLE_ADMIN') || $authorizationChecker->isGranted('VIEW', $vial))) {
            return new Response('Access to' . $type . ' vial ' . sprintf("%06d",$id) . ' denied', 401);
        }

        $serializer = $this->get('serializer');

        if ($format == 'json') {
            return new JsonResponse($serializer->serialize($vial, 'json'));
        } else {
            return array('entity' => $vial, 'checked' => 'checked', 'type' => $filter, 'order' => $order);
        }
    }

    /**
     * Handle rack vial AJAX request
     *
     * @Route("/racks/vials")
     *
     * @param  \Symfony\Component\HttpFoundation\Request   $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rackVialAction(Request $request)
    {
        $vialID = $request->query->get('vialID');
        $positionID = $request->query->get('positionID');
        $rackID = $request->query->get('rackID');
        $order = $request->query->get('order',null);

        $om = $this->getObjectManager();
        $authorizationChecker = $this->getAuthorizationChecker();
        try {
            $vial = $om->find('BluemesaFliesBundle:Vial', $vialID);
        } catch (NoResultException $e) {
            $vial = null;
        }
        try {
            $position = $om->find('BluemesaFliesBundle:RackPosition', $positionID);
        } catch (NoResultException $e) {
            $position = null;
        }

        if (! $vial instanceof Vial) {
            return new Response('The vial ' . sprintf("%06d",$vialID) . ' does not exist', 404);
        } elseif (!($authorizationChecker->isGranted('ROLE_ADMIN') || $authorizationChecker->isGranted('VIEW', $vial))) {
            return new Response('Access to vial ' . sprintf("%06d",$vialID) . ' denied', 401);
        }

        if (! $position instanceof RackPosition) {
            return new Response('Selected position does not exist', 404);
        } elseif (($vialID != null)&&(! $position->isEmpty())) {
            return new Response('Selected position is not empty', 406);
        }

        $vial->setPosition($position);
        $om->persist($vial);
        $om->flush();

        $positionView = $this->renderView("BluemesaFliesBundle:Rack:position.html.twig",
                array('content' => $vial, 'rackID' => $rackID, 'order' => $order));
        
        $detailView = $this->renderView("BluemesaFliesBundle:Rack:detail.html.twig",
                array('content' => $vial, 'position' => $position, 'order' => $order));
        
        if ($vial->isDead() || $vial->isOverDue()) {
            $class = "danger";
        } elseif ($vial->isDue()) {
            $class = "warning";
        } else {
            $class = "success";
        }
        
        $response = new JsonResponse();
        $response->setData(array('position' => $positionView, 'detail' => $detailView, 'class' => $class));

        return $response;
    }

    /**
     * Handle rack vial AJAX request
     *
     * @Route("/racks/vials/remove")
     *
     * @param  \Symfony\Component\HttpFoundation\Request   $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rackVialRemoveAction(Request $request)
    {
        $vialID = $request->query->get('vialID');
        $rackID = $request->query->get('rackID');

        $om = $this->getObjectManager();
        $authorizationChecker = $this->getAuthorizationChecker();

        try {
            $vial = (null !== $vialID) ? $om->find('BluemesaFliesBundle:Vial', $vialID) : null;
        } catch (NoResultException $e) {
            $vial = null;
        }
        try {
            $rack = (null !== $rackID) ? $om->find('BluemesaFliesBundle:Rack', $rackID) : null;
        } catch (NoResultException $e) {
            $rack = null;
        }

        if ((null !== $vialID)&&(! $vial instanceof Vial)) {
            return new Response('The vial ' . sprintf("%06d",$vialID) . ' does not exist', 404);
        } elseif (!($authorizationChecker->isGranted('ROLE_ADMIN') || $authorizationChecker->isGranted('VIEW', $vial))) {
            return new Response('Access to vial ' . sprintf("%06d",$vialID) . ' denied', 401);
        }

        if ((null === $vialID)&&(! $rack instanceof Rack)) {
            return new Response('The rack R'. sprintf("%06d",$rackID) . ' does not exist', 404);
        } elseif (($vialID != null)&&(! $rack->hasContent($vial))) {
            return new Response('The vial ' . sprintf("%06d",$vialID) . ' is not in the rack R'. sprintf("%06d",$rackID), 404);
        }

        if ($vialID !== null) {
            $vial->setPosition(null);
            $om->persist($vial);
            $om->flush();

            return new Response('The vial '. sprintf("%06d",$rackID) . ' was removed from rack R'. sprintf("%06d",$rackID), 200);
        } else {
            $rack->clearContents();
            $om->persist($rack);
            $om->flush();

            return new Response('The rack R'. sprintf("%06d",$rackID) . ' was cleared', 200);
        }
    }

    /**
     * Handle stock search AJAX request
     *
     * @Route("/stocks/search")
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function stockSearchAction(Request $request)
    {
        $searchQuery = new SearchQuery();
        $searchQuery->setTokenStorage($this->getTokenStorage());
        $searchQuery->setAuthorizationChecker($this->getAuthorizationChecker());
        $searchQuery->setTerms($request->query->get('query'));
        $query = $this->getObjectManager()->getRepository('BluemesaFliesBundle:Stock')->getSearchQuery($searchQuery);
        $found = $query->getResult();

        $stockNames = array();
        /** @var Stock $stock */
        foreach ($found as $stock) {
            $stockNames[] = $stock->getName();
        }

        $response = new JsonResponse();
        $response->setData(array('options' => $stockNames));

        return $response;
    }

    /**
     * Handle popover AJAX request
     *
     * @Route("/popover")
     * @Template()
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function popoverAction(Request $request)
    {
        $type = $request->query->get('type');
        $id = $request->query->get('id');
        $rack = $request->query->get('rack');
        /** @var OwnedObjectManager $om */
        $om = $this->getObjectManager();

        try {
            switch ($type) {
                case 'vial':
                    $entity =  $om->getRepository('BluemesaFliesBundle:Vial')->find($id);
                    $etype = "Vial";
                    break;
                case 'stock':
                    $entity =  $om->getRepository('BluemesaFliesBundle:Stock')->find($id);
                    $etype = "Stock";
                    break;
                default:
                    return new Response('Unrecognized type', 406);
            }
        } catch (NoResultException $e) {
            $etype = null;
            $entity = null;
        }

        $status = '<div class="status">';
        if ($entity instanceof Vial) {
            if ($entity->isTrashed()) {
                $status .= '<span title="trashed" class="label status label-default"><i class="fa fa-trash-o"></i></span>';
            } elseif ($entity->isAlive()) {
                $status .= '<span title="alive" class="label status label-success"><i class="fa fa-heart"></i></span>';
            } else {
                $status .= '<span title="dead" class="label status label-danger"><i class="fa fa-times-circle"></i></span>';
            }
            if ($entity->getTemperature() < 21) {
                $status .= '<span class="label status label-info">' . $entity->getTemperature() . '℃</span>';
            } elseif ($entity->getTemperature() < 25) {
                $status .= '<span class="label status label-success">' . $entity->getTemperature() . '℃</span>';
            } elseif ($entity->getTemperature() < 28) {
                $status .= '<span class="label status label-warning">' . $entity->getTemperature() . '℃</span>';
            } else {
                $status .= '<span class="label status label-danger">' . $entity->getTemperature() . '℃</span>';
            }
            if ($entity instanceof CrossVial) {
                if ($entity->isSuccessful()) {
                    $status .= '<span title="successful" class="label status label-success"><i class="fa fa-check"></i></span>';
                } elseif ($entity->isSterile()) {
                    $status .= '<span title="sterile" class="label status label-important"><i class="fa fa-times-circle"></i></span>';
                } elseif (null !== $entity->isSuccessful()) {
                    $status .= '<span title="failed" class="label status label-warning"><i class="fa fa-times"></i></span>';
                }

                $type  = "crossvial";
                $etype = "Cross";
            } elseif (($entity instanceof StockVial)&&(null !== $entity->getStock())) {
                $type  = "stockvial";
            } elseif (($entity instanceof InjectionVial)&&(null !== $entity->getTargetStock())) {
                $type  = "injectionvial";
                $etype = "Injection";
            }
        } elseif ($entity instanceof Stock) {
            $vials = count($entity->getLivingVials());
            if ($vials > 3) {
                $status .= '<span title="amplified" class="label status label-success"><i class="fa fa-plus-circle"></i></span>';
            } elseif ($vials > 1) {
                $status .= '<span title="healthy" class="label status label-success"><i class="fa fa-check-circle"></i></span>';
            } elseif ($vials < 1) {
                $status .= '<span title="dead" class="label status label-danger"><i class="fa fa-times-circle"></i></span>';
            } else {
                $status .= '<span title="expand" class="label status label-warning"><i class="fa fa-minus-circle"></i></span>';
            }
        } else {
             return new Response('Not found', 404);
        }
        $status .= '</div>';        
        $owner = $om->getOwner($entity);
        $html = $this->renderView('BluemesaFliesBundle:AJAX:popover.html.twig',
                array('type' => $type, 'entity' => $entity, 'owner' => $owner, 'rack' => $rack));
        $title = "<b>" . $etype . " " . $entity . "</b>" . $status;

        $response = new JsonResponse();
        $response->setData(array('title' => $title, 'html' => $html));

        return $response;
    }
}
