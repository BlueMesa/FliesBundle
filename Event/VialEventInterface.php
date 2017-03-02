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

use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Doctrine\Common\Collections\Collection;


interface VialEventInterface
{
    /**
     * @return Vial
     */
    public function getVial();

    /**
     * @param Vial $vial
     */
    public function setVial(Vial $vial = null);

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
     * @param Vial $vial
     */
    public function addVial(Vial $vial);

    /**
     * Remove vial
     *
     * @param Vial $vial
     */
    public function removeVial(Vial $vial);
}
