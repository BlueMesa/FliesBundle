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

use Bluemesa\Bundle\CrudBundle\Event\EditActionEvent;
use Bluemesa\Bundle\CrudBundle\Event\NewActionEvent;
use Bluemesa\Bundle\CrudBundle\Event\ShowActionEvent;
use Bluemesa\Bundle\CoreBundle\Doctrine\ObjectManagerRegistry;
use Bluemesa\Bundle\FliesBundle\Entity\Stock;
use Bluemesa\Bundle\FliesBundle\Entity\StockVial;
use Bluemesa\Bundle\FliesBundle\Event\FlyEvents;
use Bluemesa\Bundle\FliesBundle\Event\VialEvent;
use Bluemesa\Bundle\FliesBundle\Filter\VialFilter;
use Bluemesa\Bundle\FliesBundle\Repository\StockVialRepository;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


/**
 * @DI\Service("bluemesa.flies.listener.stock")
 * @DI\Tag("kernel.event_listener",
 *     attributes = {
 *         "event" = "bluemesa.controller.show_completed",
 *         "method" = "onShowCompleted",
 *         "priority" = 100
 *     }
 * )
 * @DI\Tag("kernel.event_listener",
 *     attributes = {
 *         "event" = "bluemesa.controller.edit_completed",
 *         "method" = "onEditCompleted",
 *         "priority" = 100
 *     }
 * )
 * @DI\Tag("kernel.event_listener",
 *     attributes = {
 *         "event" = "bluemesa.controller.new_submitted",
 *         "method" = "onNewSubmitted",
 *         "priority" = 100
 *     }
 * )
 * @DI\Tag("kernel.event_listener",
 *     attributes = {
 *         "event" = "bluemesa.controller.new_success",
 *         "method" = "onNewSuccess",
 *         "priority" = 100
 *     }
 * )
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class StockListener
{
    /**
     * @var ObjectManagerRegistry
     */
    protected $registry;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "registry" = @DI\Inject("bluemesa.core.doctrine.registry"),
     *     "authorizationChecker" = @DI\Inject("security.authorization_checker"),
     *     "tokenStorage" = @DI\Inject("security.token_storage"),
     *     "dispatcher" = @DI\Inject("event_dispatcher")
     * })
     *
     * @param ObjectManagerRegistry          $registry
     * @param AuthorizationCheckerInterface  $authorizationChecker
     * @param TokenStorageInterface          $tokenStorage
     * @param EventDispatcherInterface       $dispatcher
     */
    public function __construct(ObjectManagerRegistry $registry, AuthorizationCheckerInterface $authorizationChecker,
                                TokenStorageInterface $tokenStorage, EventDispatcherInterface $dispatcher)
    {
        $this->registry = $registry;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param ShowActionEvent $event
     */
    public function onShowCompleted(ShowActionEvent $event)
    {
        $stock = $event->getEntity();
        if (! $stock instanceof Stock) {
          return;
        };

        $repository = $this->registry->getRepository(StockVial::class);
        if (! $repository instanceof StockVialRepository) {
            throw new \LogicException();
        }

        $filter = new VialFilter(null, $this->authorizationChecker, $this->tokenStorage);
        $filter->setAccess('private');

        $userVials = $repository->findLivingVialsByStock($stock, $filter);

        $small = new ArrayCollection();
        $medium = new ArrayCollection();
        $large = new ArrayCollection();

        /** @var StockVial $vial */
        foreach ($userVials as $vial) {
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
        $view = $event->getView();
        $view->setTemplateData(array_merge($view->getTemplateData(), $vials));
    }

    /**
     * @param EditActionEvent $event
     */
    public function onEditCompleted(EditActionEvent $event)
    {
        $stock = $event->getEntity();
        if (! $stock instanceof Stock) {
            return;
        };

        $view = $event->getView();
        if ($view->getResponse() instanceof RedirectResponse) {
            return;
        }

        $filter = new VialFilter(null, $this->authorizationChecker, $this->tokenStorage);
        $filter->setAccess('insecure');

        $repository = $this->registry->getRepository(StockVial::class);
        if (! $repository instanceof StockVialRepository) {
            throw new \LogicException();
        }

        $used = $repository->getUsedVialCountByStock($stock, $filter);
        $canDelete = $this->authorizationChecker->isGranted('ROLE_ADMIN') || ($used == 0);
        $view->setTemplateData(array_merge($view->getTemplateData(), array('can_delete' => $canDelete)));
    }

    /**
     * @param NewActionEvent $event
     */
    public function onNewSubmitted(NewActionEvent $event)
    {
        $stock = $event->getEntity();
        if (! $stock instanceof Stock) {
            return;
        };

        $form = $event->getForm();
        /** @var StockVial $vial */
        $vial = $form->get('vial')->getData();
        /** @var integer $number */
        $number = $form->get('number')->getData();

        for ($i=0; $i<$number; $i++) {
            $stock->addVial(clone $vial);
        }
    }

    /**
     * @param NewActionEvent $event
     */
    public function onNewSuccess(NewActionEvent $event)
    {
        $stock = $event->getEntity();
        if (! $stock instanceof Stock) {
            return;
        };

        $event = new VialEvent($stock->getVials());
        $this->dispatcher->dispatch(FlyEvents::VIALS_CREATED, $event);
    }

    /**
     * @param NewActionEvent $event
     */
    public function onNewCompleted(NewActionEvent $event)
    {
        $stock = $event->getEntity();
        if (!$stock instanceof Stock) {
            return;
        };

        $view = $event->getView();
        if ($view->getResponse() instanceof RedirectResponse) {
            return;
        }

        if ($stock->getId() === null) {
            $repository = $this->registry->getRepository(Stock::class);
            $existing = $repository->findOneBy(array('name' => $stock->getName()));
            $view->setTemplateData(array_merge($view->getTemplateData(), array('existing_stock' => $existing)));
        }
    }
}
