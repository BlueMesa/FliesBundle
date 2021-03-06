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

use Bluemesa\Bundle\CoreBundle\Form\TextEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CrossVialType class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class CrossVialType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('basic', Type\CrossVialType::class, array(
                        'horizontal'        => false,
                        'label_render'      => false,
                        'widget_form_group' => false
                    )
                )
                ->add('sourceVial', TextEntityType::class, array(
                        'choice_label'        => 'id',
                        'class'               => 'BluemesaFliesBundle:CrossVial',
                        'format'              => '%06d',
                        'label'               => 'Flipped from',
                        'attr'                => array('class' => 'barcode'),
                        'widget_addon_append' => array('icon' => 'qrcode'),
                        'required'            => false
                    )
                )
                ->add('options', Type\VialOptionsType::class, array(
                        'horizontal'        => false,
                        'label_render'      => false,
                        'widget_form_group' => false
                    )
                )
                ->add('outcome', ChoiceType::class, array(
                        'choices'     => array(
                            'Successful' => 'successful',
                            'Failed'     => 'failed',
                            'Sterile'    => 'sterile',
                            'Undefined'  => 'undefined'
                        ),
                        'placeholder' => false,
                        'expanded'    => true,
                        'label'       => 'Outcome',
                        'required'    => false,
                    )
                )
                ->add('trashed', CheckboxType::class, array(
                        'label'    => '',
                        'required' => false
                    )
                );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Bluemesa\Bundle\FliesBundle\Entity\CrossVial',
            'error_mapping' => array(
                'basic.maleValid' => 'maleName',
                'basic.virginValid' => 'virginName')));
    }
}
