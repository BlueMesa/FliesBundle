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

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\SatisfiesParentSecurityPolicy;

use Doctrine\Common\Collections\ArrayCollection;

use Bluemesa\Bundle\AclBundle\Controller\SecureCRUDController;
use Bluemesa\Bundle\CoreBundle\Filter\RedirectFilterInterface;

use Bluemesa\Bundle\FliesBundle\Doctrine\VialManager;
use Bluemesa\Bundle\FliesBundle\Label\PDFLabel;

use Bluemesa\Bundle\FliesBundle\Form\StockType;
use Bluemesa\Bundle\FliesBundle\Form\StockNewType;

use Bluemesa\Bundle\FliesBundle\Entity\Stock;
use Bluemesa\Bundle\FliesBundle\Entity\StockVial;

use Bluemesa\Bundle\FliesBundle\Filter\StockFilter;
use Bluemesa\Bundle\FliesBundle\Filter\VialFilter;

/**
 * StockController class
 *
 * @Route("/stocks")
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class StockController extends SecureCRUDController
{
    const ENTITY_CLASS = 'Bluemesa\Bundle\FliesBundle\Entity\Stock';
    const ENTITY_NAME = 'stock|stocks';
    
    
    /**
     * {@inheritdoc}
     */
    protected function getCreateForm()
    {
        return StockNewType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditForm()
    {
        return StockType::class;
    }

    /**
     * List stocks
     *
     * @Route("/")
     * @Route("/list/{access}", defaults={"access" = "owned"})
     * @Route("/list/{access}/sort/{sort}/{order}", defaults={"access" = "mtnt", "sort" = "name", "order" = "asc"})
     * @Template()
     * @SatisfiesParentSecurityPolicy
     *
     * @param  \Symfony\Component\HttpFoundation\Request   $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request)
    {
        return parent::listAction($request);
    }
    
    /**
     * Show stock
     *
     * @Route("/show/{id}")
     * @Template()
     *
     * @param  \Symfony\Component\HttpFoundation\Request           $request
     * @param  mixed                                               $id
     * @return \Symfony\Component\HttpFoundation\Response | array
     */
    public function showAction(Request $request, $id)
    {
        /** @var Stock $stock */
        $stock = $this->getEntity($id);
        $response = parent::showAction($request, $stock);
        $om = $this->getObjectManager();
        
        $filter = new VialFilter(null, $this->getAuthorizationChecker(), $this->getTokenStorage());
        $filter->setAccess('private');
        
        $myVials = $om->getRepository('Bluemesa\Bundle\FliesBundle\Entity\StockVial')
                      ->findLivingVialsByStock($stock, $filter);

        $small = new ArrayCollection();
        $medium = new ArrayCollection();
        $large = new ArrayCollection();

        /** @var StockVial $vial */
        foreach ($myVials as $vial) {
            switch ($vial->getSize()) {
                case 'small':
                    $small->add($vial);
                    break;
                case 'medium':
                    $medium->add($vial);
                    break;
                case 'large':
                    $large->add($vial);
                    break;
            }
        }

        $vials = array('small' => $small, 'medium' => $medium, 'large' => $large);

        return is_array($response) ? array_merge($response, $vials) : $response;
    }

    /**
     * Create stock
     *
     * @Route("/new")
     * @Template()
     * @SatisfiesParentSecurityPolicy
     *
     * @param  \Symfony\Component\HttpFoundation\Request           $request
     * @return \Symfony\Component\HttpFoundation\Response | array
     */
    public function createAction(Request $request)
    {
        $om = $this->getObjectManager();
        /** @var VialManager $vm */
        $vm = $this->getObjectManager('Bluemesa\Bundle\FliesBundle\Entity\Vial');
        $class = $this->getEntityClass();
        $stock = new $class();
        $existingStock = null;
        $data = array('stock' => $stock, 'number' => 1, 'size' => 'medium');
        $form = $this->createForm($this->getCreateForm(), $data);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            /** @var Stock $stock */
            $stock = $data['stock'];
            $number = $data['number'];
            $size = $data['size'];
            $food = $data['food'];

            for ($i = 0; $i < $number - 1; $i++) {
                $vial = new StockVial();
                $stock->addVial($vial);
            }

            $vials = $stock->getVials();

            foreach ($vials as $vial) {
                $vial->setSize($size);
                $vial->setFood($food);
            }

            $om->persist($stock);
            $om->flush();

            $this->addSessionFlash('success', 'Stock ' . $stock . ' was created.');

            if ($this->getSession()->get('autoprint') == 'enabled') {
                $labelMode = ($this->getSession()->get('labelmode','std') == 'alt');
                $pdf = $this->get('bluemesafolks.pdflabel');
                $pdf->addLabel($vials, $labelMode);
                if ($this->submitPrintJob($pdf)) {
                    $vm->markPrinted($vials);
                    $vm->flush();
                }
            }

            $route = str_replace("_create", "_show", $request->attributes->get('_route'));
            $url = $this->generateUrl($route,array('id' => $stock->getId()));

            return $this->redirect($url);
        } elseif ($stock instanceof Stock) {
            $existingStock = $om->getRepository($this->getEntityClass())
                                ->findOneBy(array('name' => $stock->getName()));
        }

        return array('form' => $form->createView(), 'existingStock' => $existingStock);
    }

    /**
     * Edit entity
     *
     * @Route("/edit/{id}")
     * @Template()
     *
     * @param  \Symfony\Component\HttpFoundation\Request           $request
     * @param  mixed                                               $id
     * @return \Symfony\Component\HttpFoundation\Response | array
     */
    public function editAction(Request $request, $id)
    {
        $response = parent::editAction($request, $id);
        
        if (is_array($response)) {
            $om = $this->getObjectManager();
            $filter = new VialFilter(null, $this->getAuthorizationChecker(), $this->getTokenStorage());
            $filter->setAccess('insecure');
            $stock = isset($response['form']) ? $response['form']->vars['value'] : $this->getEntity($id);
            $used = $om->getRepository('Bluemesa\Bundle\FliesBundle\Entity\StockVial')
                        ->getUsedVialCountByStock($stock, $filter);
            $canDelete = $this->getAuthorizationChecker()->isGranted('ROLE_ADMIN') || ($used == 0);
            
            return array_merge($response, array('can_delete' => $canDelete));
        }
        
        return $response;
    }
    
    /**
     * Submit print job
     *
     * @param  \Bluemesa\Bundle\FliesBundle\Label\PDFLabel  $pdf
     * @param  integer                          $count
     * @return boolean
     */
    protected function submitPrintJob(PDFLabel $pdf, $count = 1)
    {
        $jobStatus = $pdf->printPDF();
        if ($jobStatus == 'successfull-ok') {
            if ($count == 1) {
                $this->get('session')->getFlashBag()
                     ->add('success', 'Label for 1 vial was sent to the printer.');
            } else {
                $this->get('session')->getFlashBag()
                     ->add('success', 'Labels for ' . $count . ' vials were sent to the printer. ');
            }

            return true;
        } else {
            $this->get('session')->getFlashBag()
                 ->add('error', 'There was an error printing labels. The print server said: ' . $jobStatus);

            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getFilterRedirect(Request $request, RedirectFilterInterface $filter)
    {
        $currentRoute = $request->attributes->get('_route');
        
        if ($currentRoute == '') {
            $route = 'bluemesa_flies_stock_list_1';
        } else {
            $pieces = explode('_',$currentRoute);
            if (! is_numeric($pieces[count($pieces) - 1])) {
                $pieces[] = '2';
            }
            $route = ($currentRoute == 'default') ? 'bluemesa_flies_stock_list_1' : implode('_', $pieces);
        }

        $routeParameters = ($filter instanceof StockFilter) ?
            array(
                'access' => $filter->getAccess(),
                'sort' => $filter->getSort(),
                'order' => $filter->getOrder()) :
            array();
        
        $url = $this->generateUrl($route, $routeParameters);
        
        return $this->redirect($url);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getFilter(Request $request)
    {
        return new StockFilter($request, $this->getAuthorizationChecker(), $this->getTokenStorage());
    }
}
