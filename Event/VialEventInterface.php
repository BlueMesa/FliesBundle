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


interface VialEventInterface
{
    /**
     * @return VialInterface
     */
    public function getVial();

    /**
     * @param VialInterface $vial
     */
    public function setVial(VialInterface $vial = null);

    /**
     * @return Collection
     */
    public function getVials();

    /**
     * @param Collection $vials
     */
    public function setVials(Collection $vials = null);

    /**
     * Add vial
     *
     * @param VialInterface $vial
     */
    public function addVial(VialInterface $vial);

    /**
     * Remove vial
     *
     * @param VialInterface $vial
     */
    public function removeVial(VialInterface $vial);
}
