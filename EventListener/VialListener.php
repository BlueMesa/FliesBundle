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

use Bluemesa\Bundle\FliesBundle\Doctrine\VialManager;
use Bluemesa\Bundle\FliesBundle\Event\VialEvent;
use Bluemesa\Bundle\FliesBundle\Label\PDFLabel;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


/**
 * @DI\Service("bluemesa.flies.listener.vial")
 * @DI\Tag("kernel.event_listener",
 *     attributes = {
 *         "event" = "bluemesa.flies.vials_created",
 *         "method" = "onVialsCreated",
 *         "priority" = 100
 *     }
 * )
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class VialListener
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
     * Constructor.
     *
     * @DI\InjectParams({
     *     "manager" = @DI\Inject("bluemesa.doctrine.vial_manager"),
     *     "session" = @DI\Inject("session"),
     *     "pdf" = @DI\Inject("bluemesa.flies.pdf_label")
     * })
     *
     * @param VialManager $manager
     * @param SessionInterface $session
     * @param PDFLabel $pdf
     */
    public function __construct(VialManager $manager, SessionInterface $session, PDFLabel $pdf)
    {
        $this->manager = $manager;
        $this->session = $session;
        $this->pdf = $pdf;
    }

    /**
     * @param VialEvent $event
     */
    public function onVialsCreated(VialEvent $event)
    {
        if ($this->session->get('autoprint') == 'enabled') {
            $vials = $event->getVials();
            $labelMode = ($this->session->get('labelmode','std') == 'alt');
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
