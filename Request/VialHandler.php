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


use Bluemesa\Bundle\CoreBundle\Entity\EntityInterface;
use Bluemesa\Bundle\CoreBundle\EventListener\RoutePrefixTrait;
use Bluemesa\Bundle\CoreBundle\Form\TextEntityType;
use Bluemesa\Bundle\CoreBundle\Request\AbstractHandler;
use Bluemesa\Bundle\CoreBundle\Request\FormHandlerTrait;
use Bluemesa\Bundle\FliesBundle\Doctrine\VialManager;
use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Bluemesa\Bundle\FliesBundle\Event\BatchActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\ExpandActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\FlipActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\FlyEvents;
use Bluemesa\Bundle\FliesBundle\Event\GiveActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\TrashActionEvent;
use Bluemesa\Bundle\FliesBundle\Event\UntrashActionEvent;
use Bluemesa\Bundle\FliesBundle\Form\VialExpandType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\View\View;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\Event;
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
    use FormHandlerTrait;

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
        $vm = $this->getVialManager($vial, $request->get('entity_class'), $request->get('action'));
        $form = $this->factory->create(VialExpandType::class, array(
            'source' => $vial,
            'template' => $vial,
            'number' => 1,
        ));

        $events = array(
            'class' => ExpandActionEvent::class,
            'initialize' => FlyEvents::EXPAND_INITIALIZE,
            'submitted' => FlyEvents::EXPAND_SUBMITTED,
            'success' => FlyEvents::EXPAND_SUCCESS,
            'completed' => FlyEvents::EXPAND_COMPLETED
        );

        $handler = function(Request $request, BatchActionEvent $event) use ($vm) {
            $form = $event->getForm();
            $source = $form->get('source')->getData();
            $template = $form->get('template')->getData();
            $number = $form->get('number')->getData();

            $vials = $vm->expand($source, $number, true, $template);
            $vm->flush();

            return $vials;
        };

        return $this->handleFormRequest($request, $vial, $form, $events, $handler);
    }

    /**
     * This method handles expand action requests.
     *
     * @param  Request $request
     * @return View
     */
    public function handleGiveAction(Request $request)
    {
        $vial = $request->get('entity');
        $vm = $this->getVialManager($vial, $request->get('entity_class'), $request->get('action'));
        $form = $this->factory->create(VialExpandType::class, array(
            'source' => $vial,
            'user' => null,
            'type' => 'give',
            'template' => $vial,
        ));

        $events = array(
            'class' => GiveActionEvent::class,
            'initialize' => FlyEvents::EXPAND_INITIALIZE,
            'submitted' => FlyEvents::EXPAND_SUBMITTED,
            'success' => FlyEvents::EXPAND_SUCCESS,
            'completed' => FlyEvents::EXPAND_COMPLETED
        );

        $handler = function(Request $request, BatchActionEvent $event) use ($vm) {
            $form = $event->getForm();
            $source = $form->get('source')->getData();
            $user = $form->get('user')->getData();
            $type = $form->get('type')->getData();
            /** @var Vial $template */
            $template = $form->get('template')->getData();

            $vm->disableAutoAcl();

            if (($type == 'flip') || ($type == 'flipped')) {
                $vial = $vm->flip($source);
                $vial->setSize($template->getSize());
                $vial->setFood($template->getFood());
                if ($type == 'flip') {
                    $vial->setPosition($source->getPosition());
                    $vm->persist($source);
                }
                $vm->persist($vial);
            }

            $vm->flush();
            $vials = new ArrayCollection();

            switch($type) {
                /** @noinspection PhpMissingBreakStatementInspection */
                case 'give':
                    $vm->setOwner($source, $user);
                    $vials->add($source);
                case 'flip':
                    /** @noinspection PhpUndefinedVariableInspection */
                    $vm->createACL($vial);
                    $vials->add($vial);
                    break;
                case 'flipped':
                    /** @noinspection PhpUndefinedVariableInspection */
                    $vm->createACL($vial, $user);
                    $vials->add($vial);
                    break;
            }

            $vm->enableAutoAcl();

            return $vials;
        };

        return $this->handleFormRequest($request, $vial, $form, $events, $handler);
    }

    /**
     * This method handles flip action requests.
     *
     * @param  Request $request
     * @return View
     */
    public function handleFlipAction(Request $request)
    {
        $entity = $request->get('entity');
        $vm = $this->getVialManager($entity, $request->get('entity_class'), $request->get('action'));
        list($source, $form) = $this->getSourceAndForm($entity, 'POST');

        $events = array(
            'class' => FlipActionEvent::class,
            'initialize' => FlyEvents::FLIP_INITIALIZE,
            'submitted' => FlyEvents::FLIP_SUBMITTED,
            'success' => FlyEvents::FLIP_SUCCESS,
            'completed' => FlyEvents::FLIP_COMPLETED
        );

        $handler = function(Request $request, BatchActionEvent $event) use ($vm) {
            $trash = $request->get('trash', false);
            $result = $vm->flip($event->getSource(), true, $trash);
            $vm->flush();

            return $result;
        };

        return $this->handleFormRequest($request, $source, $form, $events, $handler);
    }

    /**
     * This method handles trash action requests.
     *
     * @param  Request $request
     * @return View
     */
    public function handleTrashAction(Request $request)
    {
        $entity = $request->get('entity');
        $vm = $this->getVialManager($entity, $request->get('entity_class'), $request->get('action'));
        list($source, $form) = $this->getSourceAndForm($entity, 'PATCH');

        $events = array(
            'class' => TrashActionEvent::class,
            'initialize' => FlyEvents::TRASH_INITIALIZE,
            'submitted' => FlyEvents::TRASH_SUBMITTED,
            'success' => FlyEvents::TRASH_SUCCESS,
            'completed' => FlyEvents::TRASH_COMPLETED
        );

        $handler = function(Request $request, BatchActionEvent $event) use ($vm) {
            $source = $event->getSource();
            $vm->trash($source);
            $vm->flush();

            return $source;
        };

        return $this->handleFormRequest($request, $source, $form, $events, $handler);
    }

    /**
     * This method handles untrash action requests.
     *
     * @param  Request $request
     * @return View
     */
    public function handleUntrashAction(Request $request)
    {
        $entity = $request->get('entity');
        $vm = $this->getVialManager($entity, $request->get('entity_class'), $request->get('action'));
        list($source, $form) = $this->getSourceAndForm($entity, 'PATCH');

        $events = array(
            'class' => UntrashActionEvent::class,
            'initialize' => FlyEvents::UNTRASH_INITIALIZE,
            'submitted' => FlyEvents::UNTRASH_SUBMITTED,
            'success' => FlyEvents::UNTRASH_SUCCESS,
            'completed' => FlyEvents::UNTRASH_COMPLETED
        );

        $handler = function(Request $request, BatchActionEvent $event) use ($vm) {
            $source = $event->getSource();
            $vm->untrash($source);
            $vm->flush();

            return $source;
        };

        return $this->handleFormRequest($request, $source, $form, $events, $handler);
    }

    /**
     * This method handles label action requests.
     *
     * @param  Request $request
     * @return View
     */
    public function handleLabelAction(Request $request)
    {
        return View::create();
    }

    /**
     * This method handles print action requests.
     *
     * @param  Request $request
     * @return View
     */
    public function handlePrintAction(Request $request)
    {

    }

    /**
     * @param  string         $class
     * @param  Request        $request
     * @param  mixed          $entity
     * @param  mixed          $result
     * @param  FormInterface  $form
     * @param  View           $view
     * @return Event
     */
    protected function createEvent($class, Request $request, $entity,
                                   $result = null, FormInterface $form = null, View $view = null)
    {
        if (is_a($class, BatchActionEvent::class, true)) {
            $event = new $class($request, $entity, $result, $form, $view);
        } else {
            $event = new $class($request, $entity, $form, $view);
        }

        return $event;
    }

    /**
     * @param  EntityInterface  $entity
     * @param  string           $method
     * @return array
     */
    private function getSourceAndForm(EntityInterface $entity = null, $method)
    {
        if (null === $entity) {
            $source = new ArrayCollection();
            $form = $this->createBatchForm($source, $method);
        } else {
            $source = $entity;
            $form = $this->factory->createBuilder()->setMethod($method)->getForm();
        }

        return array($source, $form);
    }

    /**
     * @param  mixed  $entity
     * @param  string $class
     * @param  string $action
     * @return VialManager
     */
    private function getVialManager($entity, $class, $action)
    {
        $vm = (null !== $entity) ? $this->registry->getManagerForClass($entity) :
            $this->registry->getManagerForClass($class);

        if (! $vm instanceof VialManager) {
            throw new \LogicException(sprintf(
                "%s action can only be performed on VialInterface instances managed by an instance of VialManager",
                ucfirst($action)));
        }

        return $vm;
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
     * @param  Collection $collection
     * @param  string     $method
     * @return FormInterface
     */
    private function createBatchForm(Collection $collection, $method)
    {
        $options = array(
            'allow_add'       => true,
            'entry_type'      => TextEntityType::class,
            'entry_options'   => array('class' =>  Vial::class),
            'csrf_protection' => false
        );

        $builder = $this->factory->createNamedBuilder('vials', CollectionType::class, $collection, $options)
            ->setMethod($method);

        return $builder->getForm();
    }
}
