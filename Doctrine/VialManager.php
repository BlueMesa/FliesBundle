<?php

/*
 * Copyright 2013 Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Bluemesa\Bundle\FliesBundle\Doctrine;

use JMS\DiExtraBundle\Annotation as DI;

use Bluemesa\Bundle\AclBundle\Doctrine\OwnedObjectManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Bluemesa\Bundle\FliesBundle\Entity\Incubator;
use Bluemesa\Bundle\FliesBundle\Repository\VialRepository;

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
     * @param  \Bluemesa\Bundle\FliesBundle\Entity\Vial|\Doctrine\Common\Collections\Collection  $vials
     * @param  boolean                                                               $setSource
     * @param  boolean                                                               $trashSource
     * @return \Bluemesa\Bundle\FliesBundle\Entity\Vial|\Doctrine\Common\Collections\Collection
     * @throws \ErrorException
     */
    public function flip($vials, $setSource = true, $trashSource = false)
    {
        if (($vial = $vials) instanceof Vial) {
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
     * @param  \Bluemesa\Bundle\FliesBundle\Entity\Vial|\Doctrine\Common\Collections\Collection  $vials
     * @throws \ErrorException
     */
    public function trash($vials)
    {
        if (($vial = $vials) instanceof Vial) {
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
     * @param  \Bluemesa\Bundle\FliesBundle\Entity\Vial|\Doctrine\Common\Collections\Collection  $vials
     * @throws \ErrorException
     */
    public function untrash($vials)
    {
        if (($vial = $vials) instanceof Vial) {
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
     * @param  \Bluemesa\Bundle\FliesBundle\Entity\Vial|\Doctrine\Common\Collections\Collection  $vials
     * @throws \ErrorException
     */
    public function markPrinted($vials)
    {
        if (($vial = $vials) instanceof Vial) {
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
     * @param \Bluemesa\Bundle\FliesBundle\Entity\Vial|\Doctrine\Common\Collections\Collection  $vials
     * @param \Bluemesa\Bundle\FliesBundle\Entity\Incubator
     * @throws \ErrorException
     */
    public function incubate($vials, Incubator $incubator = null)
    {
        if (($vial = $vials) instanceof Vial) {
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
     * @param  \Bluemesa\Bundle\FliesBundle\Entity\Vial             $vial
     * @param  integer                                  $count
     * @param  boolean                                  $setSource
     * @param  string                                   $size
     * @param  string                                   $food
     * @return \Doctrine\Common\Collections\Collection
     */
    public function expand(Vial $vial, $count = 1, $setSource = true, $size = null, $food = null)
    {
        $newVials = new ArrayCollection();
        for ($i = 0; $i < $count; $i++) {
            $newVial = $this->flip($vial, $setSource);
            if ((null !== $size)||(null !== $food)) {
                if (null !== $size) {
                    $newVial->setSize($size);
                }
                if (null !== $food) {
                    $newVial->setFood($food);
                }
                $this->persist($newVial);
            }
            $newVials->add($newVial);
        }

        return $newVials;
    }
    
    /**
     * 
     * @param  mixed  $object
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
