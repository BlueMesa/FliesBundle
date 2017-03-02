<?php

/*
 * This file is part of the Flies Bundle.
 * 
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\FliesBundle\Request;


use Bluemesa\Bundle\CoreBundle\EventListener\RoutePrefixTrait;
use Bluemesa\Bundle\CoreBundle\Form\TextEntityType;
use Bluemesa\Bundle\CoreBundle\Request\AbstractHandler;
use Bluemesa\Bundle\FliesBundle\Doctrine\VialManager;
use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Bluemesa\Bundle\FliesBundle\Entity\VialInterface;
use Bluemesa\Bundle\FliesBundle\Event\BatchActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\ExpandActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\FlipActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\FlyEvents;
use Bluemesa\Bundle\FliesBundle\Form\VialExpandType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\View\View;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class VialHandler
 *
 * @DI\Service("bluemesa.flies.vial.handler")
 *
 * @package Bluemesa\Bundle\AclBundle\Request
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class VialHandler extends AbstractHandler
{
    use RoutePrefixTrait;

    /**
     * This method calls a proper handler for the incoming request
     *
     * @param  Request $request
     * @return View
     * @throws \LogicException
     */
    public function handle(Request $request)
    {
        $action = $request->get('action');
        switch($action) {
            case 'expand':
                $result = $this->handleExpandAction($request);
                break;
            case 'flip':
                $result = $this->handleFlipAction($request);
                break;
            default:
                $message  = "The action '" . $action;
                $message .= "' is not one of the allowed vial actions ('expand, flip').";
                throw new \LogicException($message);
        }

        return $result;
    }

    /**
     * This method handles expand action requests.
     *
     * @param  Request $request
     * @return View
     */
    public function handleExpandAction(Request $request)
    {
        $vial = $request->get('entity');
        $vm = (null !== $vial) ? $this->registry->getManagerForClass($vial) :
            $this->registry->getManagerForClass($request->get('entity_class'));

        if (! $vm instanceof VialManager) {
            throw new \LogicException("Expand action can only be performed on VialInterface instances managed " .
                "by an instance of VialManager");
        }

        $data = array(
            'source' => $vial,
            'template' => $vial,
            'number' => 1,
        );

        $form = $this->factory->create(VialExpandType::class, $data);

        $event = new ExpandActionEvent($request, $vial, $form);
        $this->dispatcher->dispatch(FlyEvents::EXPAND_INITIALIZE, $event);

        if (null !== $event->getView()) {
            return $event->getView();
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = new ExpandActionEvent($request, $vial, $form);
            $this->dispatcher->dispatch(FlyEvents::EXPAND_SUBMITTED, $event);

            $source = $form->get('source')->getData();
            $template = $form->get('template')->getData();
            $number = $form->get('number')->getData();

            $vials = $vm->expand($source, $number, true, $template);
            $vm->flush();

            $event = new ExpandActionEvent($request, $vial, $form, $vials, $event->getView());
            $this->dispatcher->dispatch(FlyEvents::EXPAND_SUCCESS, $event);

            if (null === $view = $event->getView()) {
                $view = View::createRouteRedirect($this->getRedirectRoute($request));
            }

        } else {
            $route = str_replace("_expand", "_index", $request->attributes->get('_route'));
            $view = View::create(array('form' => $form->createView()));
            $vials = null;
        }

        $event = new ExpandActionEvent($request, $vial, $form, $vials, $view);
        $this->dispatcher->dispatch(FlyEvents::EXPAND_COMPLETED, $event);

        return $event->getView();
    }

    /**
     * This method handles flip action requests.
     *
     * @param  Request $request
     * @return View
     */
    public function handleFlipAction(Request $request)
    {
        /** @var VialInterface|Collection $source */
        $source = $request->get('entity');
        $trash = $request->get('trash', false);
        $vm = (null !== $source) ? $this->registry->getManagerForClass($source) :
            $this->registry->getManagerForClass($request->get('entity_class'));

        if (! $vm instanceof VialManager) {
            throw new \LogicException("Flip action can only be performed on VialInterface instances managed " .
                "by an instance of VialManager");
        }

        if (null === $source) {
            $source = new ArrayCollection();
            $form = $this->createBatchForm($source);
        } else {
            $form = $this->factory->createBuilder()->setMethod('POST')->getForm();
        }

        $event = new FlipActionEvent($request, $source);
        $this->dispatcher->dispatch(FlyEvents::FLIP_INITIALIZE, $event);

        if (null !== $event->getView()) {
            return $event->getView();
        }

        $form->handleRequest($request);
        $result = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $event = new FlipActionEvent($request, $source);
            $this->dispatcher->dispatch(FlyEvents::FLIP_SUBMITTED, $event);

            $result = $vm->flip($source, true, $trash);
            $vm->flush();

            $event = new FlipActionEvent($request, $source, $result, $event->getView());
            $this->dispatcher->dispatch(FlyEvents::FLIP_SUCCESS, $event);

            if (null === $view = $event->getView()) {
                $referer = $request->get('referer');
                if (is_array($referer)) {
                    $view = View::createRouteRedirect($referer['route'], $referer['parameters']);
                } else {
                    $view = View::createRouteRedirect($this->getRedirectRoute($request));
                }
            }

        } else {
            $view = View::create(array('form' => $form->createView()));
        }

        $event = new FlipActionEvent($request, $source, $result, $view);
        $this->dispatcher->dispatch(FlyEvents::FLIP_COMPLETED, $event);

        return $event->getView();
    }



    /**
     * This is a wrapper for vial batch actions
     *
     * @param Request $request
     * @param string  $eventClass
     * @param array  $eventNames
     * @param $handler
     * @return View
     */
    private function handleBatchAction(Request $request, $eventClass, array $eventNames, $handler)
    {
        $action = $request->get('action');

        /** @var VialInterface|Collection $source */
        $source = $request->get('entity');
        $vm = (null !== $source) ? $this->registry->getManagerForClass($source) :
            $this->registry->getManagerForClass($request->get('entity_class'));

        if (! $vm instanceof VialManager) {
            throw new \LogicException(sprintf("%s action can only be performed on VialInterface instances managed by an instance of VialManager", ucfirst($action)));
        }

        if (null === $source) {
            $source = new ArrayCollection();
            $form = $this->createBatchForm($source);
        } else {
            $form = $this->factory->createBuilder()->setMethod('POST')->getForm();
        }

        /** @var BatchActionEvent $event */
        $event = new $eventClass($request, $source);
        $this->dispatcher->dispatch($eventNames['initialize'], $event);

        if (null !== $event->getView()) {
            return $event->getView();
        }

        $form->handleRequest($request);
        $result = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $event = new $eventClass($request, $source);
            $this->dispatcher->dispatch($eventNames['submitted'], $event);

            $result = $handler($request, $source, $vm, $event);

            $event = new $eventClass($request, $source, $result, $event->getView());
            $this->dispatcher->dispatch($eventNames['success'], $event);

            if (null === $view = $event->getView()) {
                $referer = $request->get('referer');
                if (is_array($referer)) {
                    $view = View::createRouteRedirect($referer['route'], $referer['parameters']);
                } else {
                    $view = View::createRouteRedirect($this->getRedirectRoute($request));
                }
            }

        } else {
            $view = View::create(array('form' => $form->createView()));
        }

        $event = new $eventClass($request, $source, $result, $view);
        $this->dispatcher->dispatch($eventNames['completed'], $event);

        return $event->getView();
    }

    /**
     * @param  Request $request
     * @return string
     */
    private function getRedirectRoute(Request $request)
    {
        $route = $request->get('redirect');
        if (null === $route) {
            switch($request->get('action')) {
                case 'expand':
                case 'flip':
                    $route = $this->getPrefix($request) . 'index';
                    break;
            }
        }

        return $route;
    }

    /**
     * @param Collection $collection
     * @return FormInterface
     */
    private function createBatchForm(Collection $collection)
    {
        $options = array(
            'allow_add'     => true,
            'entry_type'    => TextEntityType::class,
            'entry_options' => array('class' =>  Vial::class)
        );
        $builder = $this->factory->createBuilder(CollectionType::class, $options, $collection)->setMethod('POST');

        return $builder->getForm();
    }
}
