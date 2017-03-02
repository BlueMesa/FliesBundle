<?php

/*
 * This file is part of the Flies Bundle.
 *
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\FliesBundle\Doctrine;

use Bluemesa\Bundle\AclBundle\Doctrine\OwnedObjectManager;
use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Bluemesa\Bundle\FliesBundle\Entity\Incubator;
use Bluemesa\Bundle\FliesBundle\Repository\VialRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\DiExtraBundle\Annotation as DI;


/**
 * VialManager is a class used to manage common operations on vials
 *
 * @DI\Service("bluemesa.doctrine.vial_manager")
 * @DI\Tag("bluemesa_core.object_manager")
 * 
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class VialManager extends OwnedObjectManager
{
    /**
     * Interface that classes managed by this ObjectManager must implement
     */
    const MANAGED_INTERFACE = 'Bluemesa\Bundle\FliesBundle\Entity\VialInterface';
    
    /**
     * {@inheritdoc}
     */
    public function getRepository($className)
    {
        $repository = parent::getRepository($className);

        if (! $repository instanceof VialRepository) {
            print "\n" . get_class($repository) . "\n";
            throw new \ErrorException('Repository must be an instance of Bluemesa\Bundle\FliesBundle\Repository\VialRepository');
        }

        return $repository;
    }
    
    /**
     * Flip vial(s)
     *
     * @param  Vial|Collection  $vials
     * @param  boolean          $setSource
     * @param  boolean          $trashSource
     * @return Vial|Collection
     * @throws \ErrorException
     */
    public function flip($vials, $setSource = true, $trashSource = false)
    {
        $vial = $vials;
        if ($vial instanceof Vial) {
            $vialClass = str_replace("Proxies\\__CG__\\", "", get_class($vial));
            /** @var Vial $newVial */
            $newVial = new $vialClass($vial, $setSource);
            if ($trashSource) {
                $newVial->setPosition($vial->getPosition());
                $vial->setTrashed(true);
                $this->persist($vial);
            }
            $this->persist($newVial);
            
            return $newVial;
        } elseif ($vials instanceof Collection) {
            $newVials = new ArrayCollection();
            foreach ($vials as $vial) {
                $newVials->add($this->flip($vial, $setSource, $trashSource));
            }

            return $newVials;
        } elseif (null === $vials) {
            throw new \ErrorException('Argument 1 must not be null');
        } else {
            throw new \ErrorException('Argument 1 must be an object of class
                Bluemesa\Bundle\FliesBundle\Entity\Vial or Doctrine\Common\Collections\Collection');
        }
    }

    /**
     * Trash vial(s)
     *
     * @param  Vial|Collection  $vials
     * @throws \ErrorException
     */
    public function trash($vials)
    {
        $vial = $vials;
        if ($vial instanceof Vial) {
            $vial->setTrashed(true);
            $this->persist($vial);
        } elseif ($vials instanceof Collection) {
            foreach ($vials as $vial) {
                $this->trash($vial);
            }
        } elseif (null === $vials) {
            throw new \ErrorException('Argument 1 must not be null');
        } else {
            throw new \ErrorException('Argument 1 must be an object of class
                Bluemesa\Bundle\FliesBundle\Entity\Vial or Doctrine\Common\Collections\Collection');
        }
    }

    /**
     * UnTrash vial(s)
     *
     * @param  Vial|Collection  $vials
     * @throws \ErrorException
     */
    public function untrash($vials)
    {
        $vial = $vials;
        if ($vial instanceof Vial) {
            $vial->setTrashed(false);
            $this->persist($vial);
        } elseif ($vials instanceof Collection) {
            foreach ($vials as $vial) {
                $this->untrash($vial);
            }
        } elseif (null === $vials) {
            throw new \ErrorException('Argument 1 must not be null');
        } else {
            throw new \ErrorException('Argument 1 must be an object of class
                Bluemesa\Bundle\FliesBundle\Entity\Vial or Doctrine\Common\Collections\Collection');
        }
    }

    /**
     * Mark vial(s) as having their label printed
     *
     * @param  Vial|Collection  $vials
     * @throws \ErrorException
     */
    public function markPrinted($vials)
    {
        $vial = $vials;
        if ($vial instanceof Vial) {
            $vial->setLabelPrinted(true);
            $this->persist($vial);
        } elseif ($vials instanceof Collection) {
            foreach ($vials as $vial) {
                $this->markPrinted($vial);
            }
        } elseif (null === $vials) {
            throw new \ErrorException('Argument 1 must not be null');
        } else {
            throw new \ErrorException('Argument 1 must be an object of class
                Bluemesa\Bundle\FliesBundle\Entity\Vial or Doctrine\Common\Collections\Collection');
        }
    }

    /**
     * Put vials into $incubator
     *
     * @param Vial|Collection  $vials
     * @param Incubator        $incubator
     * @throws \ErrorException
     */
    public function incubate($vials, Incubator $incubator = null)
    {
        $vial = $vials;
        if ($vial instanceof Vial) {
            $vial->setStorageUnit($incubator);
            $this->persist($vial);
        } elseif ($vials instanceof Collection) {
            foreach ($vials as $vial) {
                $this->incubate($vial, $incubator);
            }
        } elseif (null === $vials) {
            throw new \ErrorException('Argument 1 must not be null');
        } else {
            throw new \ErrorException('Argument 1 must be an object of class
                Bluemesa\Bundle\FliesBundle\Entity\Vial or Doctrine\Common\Collections\Collection');
        }
    }

    /**
     * Expand a vial into multiple vials of arbitrary size
     *
     * @param  Vial             $vial
     * @param  integer          $count
     * @param  boolean          $setSource
     * @param  Vial             $template
     * @return Collection
     */
    public function expand(Vial $vial, $count = 1, $setSource = true, Vial $template = null)
    {
        $newVials = new ArrayCollection();
        for ($i = 0; $i < $count; $i++) {
            $newVial = $this->flip($vial, $setSource);
            if (null !== $template) {
                $newVial->setSize($template->getSize());
                $newVial->setFood($template->getFood());
                $this->persist($newVial);
            }
            $newVials->add($newVial);
        }

        return $newVials;
    }

    /**
     * @param  mixed $object
     * @param  mixed $user
     * @return array
     */
    public function getDefaultACL($object = null, $user = null)
    {
        $acl = parent::getDefaultACL($object, $user);
        
        if (($vial = $object) instanceof Vial) {
            $sourceVial = $vial->getParent();
            $acl = ((null !== $sourceVial)&&($this->authorizationChecker->isGranted('OPERATOR', $sourceVial))) ?
                $this->getACL($sourceVial) : $acl;
        }
        
        return $acl;
    }
}
