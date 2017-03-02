<?php

/*
 * This file is part of the CRUD Bundle.
 * 
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\FliesBundle\Controller;


use Bluemesa\Bundle\FliesBundle\Request\VialHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait VialControllerTrait
{
    /**
     * @return VialHandler
     */
    public function getVialHandler()
    {
        if ((! property_exists($this, 'container'))||(! $this->container instanceof ContainerInterface)) {
            throw new \LogicException("Calling class must have container property set to ContainerInterface instance");
        }

        return $this->container->get('bluemesa.flies.vial.handler');
    }
}
