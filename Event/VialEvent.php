<?php

/*
 * This file is part of the XXX.
 * 
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\FliesBundle\Event;


use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\EventDispatcher\Event;

class VialEvent extends Event
{
    /**
     * @var Collection
     */
    protected $vials;


    /**
     * VialEvent constructor.
     *
     * @param Vial|Collection $vials
     */
    public function __construct($vials = null)
    {
        if ($vials instanceof Collection) {
            $this->vials = $vials;
        } else {
            $this->vials = new ArrayCollection();
            if ($vials !== null) {
                $this->addVial($vials);
            }
        }
    }

    /**
     * @return Vial
     * @throws \LogicException
     */
    public function getVial()
    {
        if ($this->vials->count() > 1) {
            throw new \LogicException("Event contains more than one vial. Call getVials() instead.");
        }

        return $this->vials->first();
    }

    /**
     * @param Vial $vial
     */
    public function setVial(Vial $vial = null)
    {
        $this->vials->clear();
        $this->addVial($vial);
    }

    /**
     * @return Collection
     */
    public function getVials()
    {
        return $this->vials;
    }

    /**
     * @param Collection $vials
     */
    public function setVials(Collection $vials = null)
    {
        $this->vials = $vials;
    }

    /**
     * Add vial
     *
     * @param Vial $vial
     */
    public function addVial(Vial $vial)
    {
        $vials = $this->getVials();
        if (! $vials->contains($vial)) {
            $vials->add($vial);
        }
    }

    /**
     * Remove vial
     *
     * @param Vial $vial
     */
    public function removeVial(Vial $vial)
    {
        $this->getVials()->removeElement($vial);
    }
}
