<?php

/*
 * This file is part of the Flies Bundle.
 * 
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\FliesBundle\Event;

use Bluemesa\Bundle\FliesBundle\Entity\VialInterface;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\View\View;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;


class ExpandActionEvent extends BatchActionEvent
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * ExpandActionEvent constructor.
     *
     * @param Request        $request
     * @param VialInterface  $vial
     * @param Collection     $vials
     * @param FormInterface  $form
     * @param View           $view
     */
    public function __construct(Request $request, $vial = null, $vials = null,
                                FormInterface $form, View $view = null)
    {
        if (! $vial instanceof VialInterface) {
            throw new \LogicException("ExpandActionEvent supports only single vial as a source.");
        }

        parent::__construct($request, $vial, $vials, $form, $view);
    }
}
