<?php

/*
 * This file is part of the Flies Bundle.
 * 
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\FliesBundle\Event;

use Bluemesa\Bundle\CrudBundle\Event\EntityModificationEvent;
use Bluemesa\Bundle\FliesBundle\Entity\VialInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\View\View;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;


class ExpandActionEvent extends EntityModificationEvent implements VialEventInterface
{
    use VialEventTrait;

    /**
     * PermissionsActionEvent constructor.
     *
     * @param Request $request
     * @param VialInterface $entity
     * @param FormInterface $form
     * @param Collection $vials
     * @param View $view
     */
    public function __construct(Request $request, VialInterface $entity = null, FormInterface $form,
                                Collection $vials = null, View $view = null)
    {
        $this->request = $request;
        $this->entity = $entity;
        $this->form = $form;
        $this->vials = null === $vials ? new ArrayCollection() : $vials;
        $this->view = $view;
    }
}
