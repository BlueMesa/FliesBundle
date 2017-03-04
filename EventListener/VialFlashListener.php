<?php

/*
 * This file is part of the Flies Bundle.
 * 
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\FliesBundle\EventListener;

use Bluemesa\Bundle\CoreBundle\Event\ControllerEvent;
use Bluemesa\Bundle\FliesBundle\Event\BatchActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\ExpandActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\FlipActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\FlyEvents;
use Bluemesa\Bundle\FliesBundle\Event\VialEventInterface;
use Doctrine\Common\Inflector\Inflector;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


/**
 * The VialFlashListener handles Pagination annotation for controllers.
 *
 * @DI\Service("bluemesa.flies.listener.vials.flash")
 * @DI\Tag("kernel.event_subscriber")
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class VialFlashListener implements EventSubscriberInterface
{
    /**
     * @var string[]
     */
    private static $successMessages = array(
        FlyEvents::EXPAND_COMPLETED => array(
            '%s %s was flipped.',
            '%s %s was expanded into %d vials.'
        ),
        FlyEvents::FLIP_COMPLETED => array(
            '%s %s was flipped.',
            '%d %s were flipped.',
            ' Source %s was trashed.',
            ' Source %s were trashed.'
        ),
    );

    /**
     * @var SessionInterface
     */
    protected $session;


    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "session" = @DI\Inject("session"),
     * })
     *
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FlyEvents::EXPAND_COMPLETED => array('addSessionFlash', 100),
            FlyEvents::FLIP_COMPLETED => array('addSessionFlash', 100)
        );
    }

    /**
     * @param Event   $event
     * @param string  $eventName
     */
    public function addSessionFlash(Event $event, $eventName)
    {
        if ((! $event instanceof VialEventInterface)||(! $event instanceof ControllerEvent)) {
            return;
        }

        if (! isset(self::$successMessages[$eventName])) {
            throw new \InvalidArgumentException('This event does not correspond to a known flash message');
        }

        $request = $event->getRequest();
        $name = strtolower($request->get('entity_name', 'entity'));
        $plural = Inflector::pluralize($name);
        $vials = $event->getVials();
        $count = $vials->count();

        if ($event instanceof BatchActionEvent) {
            $message = ($count == 1) ?
                ucfirst(sprintf(self::$successMessages[$eventName][0], $name, $event->getSourceVial())) :
                ucfirst(sprintf(self::$successMessages[$eventName][1], $count, $plural));
            if (($event instanceof FlipActionEvent)&&($request->get('trash'))) {
                $message = $message . ($count == 1) ?
                    sprintf(self::$successMessages[$eventName][3], $name) :
                    sprintf(self::$successMessages[$eventName][4], $plural);
            }
        } elseif ($event instanceof ExpandActionEvent) {
            $message = ($count == 1) ?
                ucfirst(sprintf(self::$successMessages[$eventName][0], $name, $event->getEntity())) :
                ucfirst(sprintf(self::$successMessages[$eventName][1], $name, $event->getEntity(), $count));
        } else {
            return;
        }

        if ($this->session instanceof Session) {
            $this->session->getFlashBag()->add('success', $message);
        } else {
            throw new \InvalidArgumentException("Session should be an instance of Session");
        }
    }
}
