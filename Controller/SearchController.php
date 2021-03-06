<?php

/*
 * Copyright 2011 Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
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

namespace Bluemesa\Bundle\FliesBundle\Controller;

use Bluemesa\Bundle\AclBundle\DependencyInjection\AuthorizationCheckerAwareTrait;
use Bluemesa\Bundle\AclBundle\DependencyInjection\TokenStorageAwareTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Bluemesa\Bundle\SearchBundle\Controller\SearchController as BaseSearchController;
use Bluemesa\Bundle\FliesBundle\Search\SearchQuery;
use Bluemesa\Bundle\FliesBundle\Form\SearchType;
use Bluemesa\Bundle\FliesBundle\Form\AdvancedSearchType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Search controller for the flies bundle
 *
 * @Route("/search")
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class SearchController extends BaseSearchController
{
    use TokenStorageAwareTrait, AuthorizationCheckerAwareTrait;
    
    /**
     * {@inheritdoc}
     */
    protected function handleSearchableRepository(Request $request, $repository, $searchQuery)
    {        
        if (! $searchQuery instanceof SearchQuery) {
            throw new \InvalidArgumentException();
        }
        
        $output = array_merge(
                parent::handleSearchableRepository($request, $repository, $searchQuery),
                array('filter' => $searchQuery->getFilter())
        );
        
        return $output;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function handleNonSearchableRepository(Request $request, $repository, $searchQuery)
    {
        if (! $searchQuery instanceof SearchQuery) {
            throw new \InvalidArgumentException();
        }
        
        $filter = $searchQuery->getFilter();
        $term = implode(' ', $searchQuery->getTerms());
        
        switch ($filter) {
            case 'rack':
                $id = (integer) str_replace('R', '', $term);
                break;
            case 'vial':
                $id = (integer) $term;
                break;
            default:
                $id = false;
        }
        
        if ((false !== $id)&&($id > 0)) {
            $url = $this->generateUrl($this->getSearchRealm() . "_" . $filter . '_show', array('id' => $id));
            
            return $this->redirect($url);
        } else {
            throw $this->createNotFoundException();
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getSearchForm()
    {
        return SearchType::class;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getAdvancedSearchForm()
    {
        return AdvancedSearchType::class;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getSearchRealm()
    {
        return 'bluemesa_flies';
    }
    
    /**
     * {@inheritdoc}
     */
    protected function createSearchQuery($advanced = false)
    {
        $searchQuery = new SearchQuery($advanced);
        $searchQuery->setTokenStorage($this->getTokenStorage());
        $searchQuery->setAuthorizationChecker($this->getAuthorizationChecker());
        
        return $searchQuery;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadSearchQuery()
    {
        $searchQuery = parent::loadSearchQuery();
        
        if (! $searchQuery instanceof SearchQuery) {
            throw $this->createNotFoundException();
        }
        
        $searchQuery->setTokenStorage($this->getTokenStorage());
        $searchQuery->setAuthorizationChecker($this->getAuthorizationChecker());

        return $searchQuery;
    }
}
