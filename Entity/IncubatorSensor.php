<?php

/*
 * This file is part of the XXX.
 * 
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\FliesBundle\Entity;


use Bluemesa\Bundle\SensorBundle\Entity\Sensor;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


/**
 * Incubator class
 *
 * @ORM\Entity(repositoryClass="Bluemesa\Bundle\FliesBundle\Repository\IncubatorSensorRepository")
 * @Serializer\ExclusionPolicy("all")
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class IncubatorSensor extends Sensor
{
    /**
     * @ORM\ManyToOne(targetEntity="Incubator", inversedBy="sensor")
     * @ORM\JoinColumn(onDelete="CASCADE")
     *
     * @var Incubator
     */
    private $incubator;


    /**
     * IncubatorSensor constructor.
     * @param $incubator
     */
    public function __construct($temperature, $humidity, $updateRate, Incubator $incubator)
    {
        parent::__construct($temperature, $humidity, $updateRate);
        $this->incubator = $incubator;
    }

    /**
     * @return Incubator
     */
    public function getIncubator()
    {
        return $this->incubator;
    }

    /**
     * @param Incubator $incubator
     */
    public function setIncubator($incubator)
    {
        $this->incubator = $incubator;
        if ($incubator->getSensor() !== $this) {
            $incubator->setSensor($this);
        }
    }
}
