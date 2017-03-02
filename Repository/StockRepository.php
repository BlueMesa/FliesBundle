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

namespace Bluemesa\Bundle\FliesBundle\Repository;

use Bluemesa\Bundle\CoreBundle\Filter\ListFilterInterface;
use Bluemesa\Bundle\AclBundle\Filter\SecureFilterInterface;
use Bluemesa\Bundle\CoreBundle\Filter\SortFilterInterface;
use Bluemesa\Bundle\SearchBundle\Repository\SearchableRepository;
use Bluemesa\Bundle\SearchBundle\Search\SearchQueryInterface;
use Bluemesa\Bundle\SearchBundle\Search\ACLSearchQueryInterface;
use Bluemesa\Bundle\FliesBundle\Search\SearchQuery;
use Bluemesa\Bundle\FliesBundle\Filter\StockFilter;

/**
 * StockRepository
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class StockRepository extends SearchableRepository
{
    /**
     * {@inheritdoc}
     */
    public function createIndexQueryBuilder()
    {
        $qb = parent::createIndexQueryBuilder();

        $date = new \DateTime();
        $date->sub(new \DateInterval('P2M'));

        if ($this->filter instanceof SortFilterInterface) {
            $order = ($this->filter->getOrder() == 'desc') ? 'DESC' : 'ASC';
            switch ($this->filter->getSort()) {
                case 'name':
                    $qb->orderBy('e.name', $order);
                    break;
                case 'gen':
                    $qb->orderBy('e.genotype', $order);
                    break;
                case 'vial':
                    $qb->addSelect('count(cntv) AS HIDDEN vialcount')
                        ->leftJoin('e.vials','cntv')
                        ->andWhere('cntv.setupDate > :date')
                        ->andWhere('cntv.trashed = false')
                        ->setParameter('date', $date->format('Y-m-d'))
                        ->groupBy('e.id')
                        ->orderBy('vialcount', $order);
                    break;
            }
        }

        if (($this->filter instanceof SecureFilterInterface)&&($this->filter->getAccess() == 'mtnt')) {
            $qb->distinct()
                ->join('e.vials','v')
                ->andWhere('v.setupDate > :date')
                ->andWhere('v.trashed = false')
                ->setParameter('date', $date->format('Y-m-d'));
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function secureQuery($qb, $query)
    {
        if (($this->filter instanceof SecureFilterInterface)&&($this->filter->getAccess() == 'mtnt')) {
            $permissions = array('OWNER');

            return $this->getAclFilter()->apply($qb, $permissions, $this->filter->getUser(), 'v');
        }

        return parent::secureQuery($qb, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchQuery(SearchQueryInterface $search)
    {
        $query = parent::getSearchQuery($search);
        $permissions = $search instanceof ACLSearchQueryInterface ? $search->getPermissions() : array();
        
        if ((false !== $permissions)&&(in_array('OWNER', $permissions))) {
            $qb = $this->getSearchQueryBuilder($search);
            $user = $search instanceof ACLSearchQueryInterface ? $search->getUser() : null;

            return $this->getAclFilter()->apply($qb, $permissions, $user, 'v');
        }
            
        return $query;
    }
        
    /**
     * {@inheritdoc}
     */
    protected function getSearchQueryBuilder(SearchQueryInterface $search)
    {
        $qb = parent::getSearchQueryBuilder($search);
        $permissions = $search instanceof ACLSearchQueryInterface ? $search->getPermissions() : array();
        
        if ((false !== $permissions)&&(in_array('OWNER', $permissions))) {
            $qb->join('e.vials','v');
            if (!($search instanceof SearchQuery ? $search->searchDead() : false)) {
                $date = new \DateTime();
                $date->sub(new \DateInterval('P2M'));
                $qb->andWhere('v.setupDate > :date')
                    ->andWhere('v.trashed = false')
                    ->setParameter('date', $date->format('Y-m-d'));
            }
        }
        
        $qb->addSelect('LENGTH(e.name) AS HIDDEN namelength')
            ->orderBy('namelength')->addOrderBy('e.name');
        
        return $qb;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSearchResultCountQuery(SearchQueryInterface $search)
    {
        $query = parent::getSearchResultCountQuery($search);
        $permissions = $search instanceof ACLSearchQueryInterface ? $search->getPermissions() : array();
        
        if ((false !== $permissions)&&(in_array('OWNER', $permissions))) {
            $qb = $this->getSearchResultCountQueryBuilder($search);
            $user = $search instanceof ACLSearchQueryInterface ? $search->getUser() : null;

            return $this->getAclFilter()->apply($qb, $permissions, $user, 'v');
        }
            
        return $query;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getSearchResultCountQueryBuilder(SearchQueryInterface $search)
    {
        $qb = parent::getSearchResultCountQueryBuilder($search)->select('count(DISTINCT e.id)');
        $permissions = $search instanceof ACLSearchQueryInterface ? $search->getPermissions() : array();
        
        if ((false !== $permissions)&&(in_array('OWNER', $permissions))) {
            $qb->join('e.vials','v');
            if (!($search instanceof SearchQuery ? $search->searchDead() : false)) {
                $date = new \DateTime();
                $date->sub(new \DateInterval('P2M'));
                $qb->andWhere('v.setupDate > :date')
                   ->andWhere('v.trashed = false')
                   ->setParameter('date', $date->format('Y-m-d'));
            }
        }
        
        return $qb;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getSearchFields(SearchQueryInterface $search)
    {
        $fields = array('e.name', 'e.genotype');
        if ($search instanceof SearchQuery ? $search->searchNotes() : false) {
            $fields[] = 'e.notes';
        }
        if ($search instanceof SearchQuery ? $search->searchVendor() : false) {
            $fields[] = 'e.vendorId';
        }
        
        return $fields;
    }
    
    /**
     * Return stocks
     *
     * @return mixed
     */
    public function findStocksByName($term)
    {
        $query = $this->createQueryBuilder('b')
                      ->andWhere('b.name like :term')
                      ->setParameter('term', '%' . $term .'%');

        return $query;
    }
}
