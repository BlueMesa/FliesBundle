<?php

/*
 * This file is part of the BluemesaFliesBundle.
 *
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bluemesa\Bundle\FliesBundle\Entity;

use Bluemesa\Bundle\AclBundle\Entity\OwnedEntityInterface;
use Bluemesa\Bundle\CoreBundle\Entity\Entity;
use Bluemesa\Bundle\CoreBundle\Entity\NamedInterface;
use Bluemesa\Bundle\CoreBundle\Entity\NamedTrait;
use Bluemesa\Bundle\StorageBundle\Entity\StorageUnitInterface;
use Bluemesa\Bundle\StorageBundle\Entity\TermocontrolledInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Incubator class
 *
 * @ORM\Entity(repositoryClass="Bluemesa\Bundle\FliesBundle\Repository\IncubatorRepository")
 * @Serializer\ExclusionPolicy("all")
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class Incubator extends Entity implements
    NamedInterface,
    OwnedEntityInterface,
    StorageUnitInterface,
    TermocontrolledInterface
{
    use NamedTrait;
    
    /**
     * @ORM\OneToMany(targetEntity="Rack", mappedBy="incubator", fetch="EXTRA_LAZY")
     *
     * @var ArrayCollection
     */
    protected $racks;

    /**
     * @ORM\OneToMany(targetEntity="Vial", mappedBy="incubator", fetch="EXTRA_LAZY")
     *
     * @var ArrayCollection
     */
    protected $vials;

    /**
     * @ORM\OneToOne(targetEntity="IncubatorSensor",
     *     mappedBy="incubator",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true)
     * @Serializer\Expose
     *
     * @var IncubatorSensor
     */
    private $sensor;


    /**
     * Construct Incubator
     *
     * @param float $temperature
     * @param float $humidity
     */
    public function __construct($temperature = 25.0, $humidity = 50.0)
    {
        $this->name = 'New incubator';
        $this->racks = new ArrayCollection();
        $this->vials = new ArrayCollection();
        $this->sensor = new IncubatorSensor($temperature, $humidity, 600, $this);
    }

    /**
     * Get racks
     *
     * @return ArrayCollection
     */
    public function getRacks()
    {
        return $this->racks;
    }

    /**
     * Get vials
     *
     * @return ArrayCollection
     */
    public function getVials()
    {
        return $this->vials;
    }

    /**
     * Get living vials
     *
     * @return ArrayCollection
     */
    public function getLivingVials()
    {
        $date = new \DateTime();
        $date->sub(new \DateInterval('P2M'));
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('trashed', false))
            ->andWhere(Criteria::expr()->gt('setupDate', $date));

        return $this->getVials()->matching($criteria);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getContents() {
        $contents = array_merge($this->getRacks()->toArray(), $this->getVials()->toArray());
        return new ArrayCollection($contents);
    }
    
    /**
     * {@inheritdoc}
     *
     * @Assert\NotBlank(message = "Temperature must be specified")
     * @Assert\Range(
     *      min = 4,
     *      max = 42,
     *      minMessage = "Temperature cannot be lower than 4℃",
     *      maxMessage = "Temperature cannot be higher than 42℃"
     * )
     */
    public function getTemperature()
    {
        return $this->sensor->getPresetTemperature();
    }

    /**
     * Set temperature
     *
     * @param float $temperature
     */
    public function setTemperature($temperature)
    {
        $this->sensor->setPresetTemperature($temperature);
        
        foreach ($this->getRacks() as $rack) {
            /** @var Rack $rack */
            foreach ($rack->getContents() as $vial) {
                /** @var Vial $vial */
                $vial->updateStorageConditions();
            }
        }
        
        foreach ($this->getLivingVials() as $vial) {
            $vial->updateStorageConditions();
        }
    }

    /**
     * Get humidity
     *
     * @Assert\NotBlank(message = "Humidity must be specified")
     * @Assert\Range(
     *      min = 0,
     *      max = 100,
     *      minMessage = "Humidity cannot be lower than 0%",
     *      maxMessage = "Humidity cannot be higher than 100%"
     * )
     *
     * @return float
     */
    public function getHumidity()
    {
        return $this->sensor->getPresetHumidity();
    }

    /**
     * Set humidity
     *
     * @param float $humidity
     */
    public function setHumidity($humidity)
    {
        $this->sensor->setPresetTemperature($humidity);
    }

    /**
     * Get sensor
     *
     * @return IncubatorSensor
     */
    public function getSensor()
    {
        return $this->sensor;
    }

    /**
     * Set sensor
     *
     * @param IncubatorSensor $sensor
     */
    public function setSensor($sensor)
    {
        $this->sensor = $sensor;
        if ($sensor->getIncubator() !== $this) {
            $sensor->setIncubator($this);
        }
    }
}
