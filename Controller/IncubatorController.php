<?php

/*
 * This file is part of the BluemesaFliesBundle.
 *
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bluemesa\Bundle\FliesBundle\Controller;

use Bluemesa\Bundle\AclBundle\Controller\SecureCRUDController;
use Bluemesa\Bundle\CoreBundle\Controller\RestControllerTrait;
use Bluemesa\Bundle\CoreBundle\Entity\DatePeriod;
use Bluemesa\Bundle\FliesBundle\Entity\Incubator;
use Bluemesa\Bundle\FliesBundle\Form\IncubatorType;
use Bluemesa\Bundle\SensorBundle\Charts\SensorChart;
use Bluemesa\Bundle\SensorBundle\Entity\Reading;
use Bluemesa\Bundle\SensorBundle\Form\SensorChartType;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\View\View;
use JMS\SecurityExtraBundle\Annotation\SatisfiesParentSecurityPolicy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;


/**
 * IncubatorController class
 *
 * @Route("/incubators")
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class IncubatorController extends SecureCRUDController
{
    use RestControllerTrait;

    const ENTITY_CLASS = 'Bluemesa\Bundle\FliesBundle\Entity\Incubator';
    const ENTITY_NAME = 'incubator|incubators';

    /**
     * @REST\View()
     * @REST\Get("/{incubator}",
     *     defaults={"_format" = "html", "period" = "24"}, requirements={"incubator" = "\d+"})
     * @REST\Post("/{incubator}",
     *     defaults={"_format" = "html", "period" = "24"}, requirements={"incubator" = "\d+"})
     * @REST\Get("/{incubator}/from/{start}/until/{end}",
     *     defaults={"_format" = "html"}, requirements={"incubator" = "\d+"})
     * @REST\Get("/{incubator}/from/{start}",
     *     defaults={"_format" = "html"}, requirements={"incubator" = "\d+"})
     *
     * @ParamConverter("incubator", class="BluemesaFliesBundle:Incubator", options={"id" = "incubator"})
     * @ParamConverter("period")
     *
     * @param  Request     $request
     * @param  Incubator   $incubator
     * @param  DatePeriod  $period
     * @return View
     */
    public function getIncubatorAction(Request $request, Incubator $incubator, DatePeriod $period)
    {
        $form = $this->createForm(SensorChartType::class, $period, array(
            'action' => $this->generateUrl('bluemesa_flies_incubator_getincubator_1', array(
                'incubator' => $incubator->getId(),
                '_format' => $request->get('_format')))
        ));
        $form->handleRequest($request);
        $chart = new SensorChart($incubator->getSensor(), $period);
        $view = $this->view()
            ->setData(array('incubator' => $incubator))
            ->setTemplateData(array(
                'period' => $period,
                'form' => $form->createView(),
                'chart' => $chart));

        return $view;
    }

    /**
     * {@inheritdoc}
     *
     * @Route("/show/{id}")
     */
    public function showAction(Request $request, $id)
    {
        return $this->redirectToRoute('bluemesa_flies_incubator_getincubator', array(
            'incubator' => $id,
            '_format' => $request->get('_format')));
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditForm()
    {
        return IncubatorType::class;
    }

    /**
     * {@inheritdoc}
     *
     * @SatisfiesParentSecurityPolicy
     */
    public function listAction(Request $request)
    {
        throw $this->createNotFoundException();
    }

    /**
     * Delete incubator
     *
     * @Route("/delete/{id}")
     * @Template()
     *
     * @param  \Symfony\Component\HttpFoundation\Request   $request
     * @param  mixed                                       $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $id)
    {
        $response = parent::deleteAction($request, $id);
        $url = $this->generateUrl('bluemesa_flies_welcome_index');

        return is_array($response) ? $response : $this->redirect($url);
    }
    
    /**
     * Generate links for putting stuff into incubator
     *
     * @Template()
     *
     * @return array
     */
    public function incubateAction()
    {
        return $this->menuAction();
    }

    /**
     * Generate links for incubator menu
     *
     * @Template()
     *
     * @return array
     */
    public function menuAction()
    {
        $entities = $this->getObjectManager(self::ENTITY_CLASS)->findAll($this->getEntityClass());

        return array('entities' => $entities);
    }
}
