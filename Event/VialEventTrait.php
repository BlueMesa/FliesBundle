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


trait VialEventTrait
{
    /**
     * @var Collection
     */
    protected $vials;


    /**
     * @return VialInterface
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
     * @param VialInterface $vial
     */
    public function setVial(VialInterface $vial = null)
    {
        $this->vials->clear();
        if (null !== $vial) {
            $this->addVial($vial);
        }
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
     * @param VialInterface $vial
     */
    public function addVial(VialInterface $vial)
    {
        $vials = $this->getVials();
        if (! $vials->contains($vial)) {
            $vials->add($vial);
        }
    }

    /**
     * Remove vial
     *
     * @param VialInterface $vial
     */
    public function removeVial(VialInterface $vial)
    {
        $this->getVials()->removeElement($vial);
    }
}
