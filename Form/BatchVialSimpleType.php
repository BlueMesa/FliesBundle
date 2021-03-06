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

use Bluemesa\Bundle\FormsBundle\Form\Type\DatePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Date;

/**
 * BatchVialSimpleType class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class BatchVialSimpleType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('setupDate', DatePickerType::class, array(
                        'label'       => 'Setup date',
                        'required'    => false,
                        'horizontal'  => true,
                        'constraints' => array(new Date())
                    )
                )
                ->add('flipDate', DatePickerType::class, array(
                        'label'       => 'Flip date',
                        'required'    => false,
                        'horizontal'  => true,
                        'constraints' => array(new Date())
                    )
                )
                ->add('options', Type\VialOptionsType::class, array(
                        'horizontal'       => false,
                        'label_render'      => false,
                        'widget_form_group' => false
                    )
                );
    }
}
