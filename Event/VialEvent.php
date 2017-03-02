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

class VialEvent extends Event implements VialEventInterface
{
    use VialEventTrait;

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
}
