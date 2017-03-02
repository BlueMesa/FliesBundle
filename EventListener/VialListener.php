<?php

/*
 * This file is part of the FliesBundle.
 * 
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\FliesBundle\EventListener;

use Bluemesa\Bundle\CrudBundle\Event\CrudControllerEvents;
use Bluemesa\Bundle\CrudBundle\Event\NewActionEvent;
use Bluemesa\Bundle\FliesBundle\Doctrine\VialManager;
use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Bluemesa\Bundle\FliesBundle\Event\FlyEvents;
use Bluemesa\Bundle\FliesBundle\Event\VialEvent;
use Bluemesa\Bundle\FliesBundle\Event\VialEventInterface;
use Bluemesa\Bundle\FliesBundle\Label\PDFLabel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Inflector\Inflector;
use FOS\RestBundle\View\View;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


/**
 * @DI\Service("bluemesa.flies.listener.vial")
 * @DI\Tag("kernel.event_subscriber")
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class VialListener implements EventSubscriberInterface
{
    /**
     * @var VialManager
     */
    protected $manager;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var PDFLabel
     */
    protected $pdf;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var ArrayCollection
     */
    protected $vials;


    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "manager" = @DI\Inject("bluemesa.doctrine.vial_manager"),
     *     "session" = @DI\Inject("session"),
     *     "pdf" = @DI\Inject("bluemesa.flies.pdf_label"),
     *     "dispatcher" = @DI\Inject("event_dispatcher")
     * })
     *
     * @param VialManager               $manager
     * @param SessionInterface          $session
     * @param PDFLabel                  $pdf
     * @param EventDispatcherInterface  $dispatcher
     */
    public function __construct(VialManager $manager, SessionInterface $session,
                                PDFLabel $pdf, EventDispatcherInterface $dispatcher)
    {
        $this->manager = $manager;
        $this->session = $session;
        $this->pdf = $pdf;
        $this->dispatcher = $dispatcher;
        $this->vials = new ArrayCollection();
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            CrudControllerEvents::NEW_SUBMITTED => array('onNewSubmitted', 100),
            CrudControllerEvents::NEW_SUCCESS => array('onNewSuccess', 90),
            FlyEvents::VIALS_CREATED => array('onVialsCreated', 100),
            FlyEvents::EXPAND_SUCCESS => array('onVialsCreated', 100)
        );
    }

    /**
     * @param NewActionEvent $event
     */
    public function onNewSubmitted(NewActionEvent $event)
    {
        $vial = $event->getEntity();
        if (! $vial instanceof Vial) {
            return;
        }

        $form = $event->getForm();
        $number = $form->get('number')->getData();
        $this->vials = $this->manager->expand($vial, $number - 1);
        $this->vials->add($vial);
    }

    /**
     * @param NewActionEvent $event
     */
    public function onNewSuccess(NewActionEvent $event)
    {
        $vial = $event->getEntity();
        if (! $vial instanceof Vial) {
            return;
        }

        $count = $this->vials->count();
        if ($count > 1) {
            $request = $event->getRequest();

            if ($this->session instanceof Session) {
                $name = strtolower($request->get('entity_name', 'entity'));
                // The line below must be kept in sync with CrudFlashListener
                $message = ucfirst(sprintf("%s %s was created.", $name, $vial));
                $flashes = $this->session->getFlashBag()->peek('success');
                foreach ($flashes as $key => $flash) {
                    if ($flash == $message) {
                        $flashes[$key] = ucfirst(sprintf("%d %s were created.", $count, Inflector::pluralize($name)));
                    }
                }
                $this->session->getFlashBag()->set('success', $flashes);
            }

            $route = str_replace("_show", "_index", $request->get('edit_redirect_route'));
            $event->setView(View::createRouteRedirect($route));
        }

        $event = new VialEvent($this->vials);
        $this->dispatcher->dispatch(FlyEvents::VIALS_CREATED, $event);
    }

    /**
     * @param VialEventInterface $event
     */
    public function onVialsCreated(VialEventInterface $event)
    {
        if ($this->session->get('autoprint') == 'enabled') {
            $vials = $event->getVials();
            $labelMode = ($this->session->get('labelmode', 'std') == 'alt');
            $this->pdf->addLabel($vials, $labelMode);
            if ($this->submitPrintJob($vials->count())) {
                $this->manager->markPrinted($vials);
                $this->manager->flush();
            }
        }
    }

    /**
     * Submit label print job
     *
     * @param  integer   $count
     * @return boolean
     */
    private function submitPrintJob($count = 1)
    {
        $jobStatus = $this->pdf->printPDF();
        $result = $jobStatus == 'successfull-ok';

        if ($this->session instanceof Session) {
            $bag = $this->session->getFlashBag();
            if ($result) {
                if ($count == 1) {
                    $bag->add('success', 'Label for 1 vial was sent to the printer.');
                } else {
                    $bag->add('success', 'Labels for ' . $count . ' vials were sent to the printer. ');
                }
            } else {
                $bag->add('error', 'There was an error printing labels. The print server said: ' . $jobStatus);
            }
        }

        return $result;
    }
}
