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

namespace Bluemesa\Bundle\FliesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Range;

/**
 * StockNewType class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class StockNewType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('stock', StockType::class, array(
                         'horizontal'        => false,
                         'label_render'      => false,
                         'widget_form_group' => false,
                    )
                )
                ->add('options', Type\VialOptionsType::class, array(
                        'horizontal'        => false,
                        'label_render'      => false,
                        'widget_form_group' => false
                    )
                )
                ->add('number', NumberType::class, array(
                        'label'       => 'Number of vials',
                        'constraints' => array(new Range(array('min' => 1)))
                    )
                );
    }
}
