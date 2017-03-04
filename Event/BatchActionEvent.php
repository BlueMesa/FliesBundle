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

use Bluemesa\Bundle\CoreBundle\Event\ControllerEvent;
use Bluemesa\Bundle\CoreBundle\Event\FormEventInterface;
use Bluemesa\Bundle\CoreBundle\Event\FormEventTrait;
use Bluemesa\Bundle\FliesBundle\Entity\VialInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\View\View;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;


class BatchActionEvent extends ControllerEvent implements VialEventInterface, FormEventInterface
{
    use VialEventTrait;
    use FormEventTrait;

    /**
     * @var Collection
     */
    protected $sources;


    /**
     * BatchActionEvent constructor.
     *
     * @param Request                   $request
     * @param VialInterface|Collection  $source
     * @param VialInterface|Collection  $result
     * @param FormInterface             $form
     * @param View                      $view
     */
    public function __construct(Request $request, $source, $result = null,
                                FormInterface $form, View $view = null)
    {
        $this->request = $request;

        if ($source instanceof Collection) {
            $this->setSourceVials($source);
        } else {
            $this->sources = new ArrayCollection();
            $this->setSourceVial($source);
        }

        if ($result instanceof Collection) {
            $this->setVials($result);
        } else {
            $this->vials = new ArrayCollection();
            $this->setVial($result);
        }

        $this->view = $view;
    }

    /**
     * @return Collection|VialInterface
     */
    public function getSource()
    {
        return $this->sources->count() > 1 ? $this->sources : $this->sources->first();
    }

    /**
     * @return VialInterface
     * @throws \LogicException
     */
    public function getSourceVial()
    {
        if ($this->sources->count() > 1) {
            throw new \LogicException("Event contains more than one vial. Call getSourceVials() instead.");
        }

        return $this->sources->first();
    }

    /**
     * @param VialInterface $vial
     */
    public function setSourceVial(VialInterface $vial = null)
    {
        $this->sources->clear();
        if (null !== $vial) {
            $this->addSourceVial($vial);
        }
    }

    /**
     * @return Collection
     */
    public function getSourceVials()
    {
        return $this->sources;
    }

    /**
     * @param Collection $vials
     */
    public function setSourceVials(Collection $vials = null)
    {
        $this->sources = $vials;
    }

    /**
     * Add vial
     *
     * @param VialInterface $vial
     */
    public function addSourceVial(VialInterface $vial)
    {
        $sources = $this->getSourceVials();
        if (! $sources->contains($vial)) {
            $sources->add($vial);
        }
    }

    /**
     * Remove vial
     *
     * @param VialInterface $vial
     */
    public function removeSourceVial(VialInterface $vial)
    {
        $this->getSourceVials()->removeElement($vial);
    }
}
