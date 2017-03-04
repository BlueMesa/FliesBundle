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

namespace Bluemesa\Bundle\FliesBundle\Form;

use Bluemesa\Bundle\AclBundle\Form\UserTypeaheadType;
use Bluemesa\Bundle\CoreBundle\Form\TextEntityType;
use Bluemesa\Bundle\FliesBundle\Entity\Vial;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;


/**
 * VialGiveType class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class VialGiveType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('source', TextEntityType::class, array(
                        'choice_label'        => 'id',
                        'class'               => Vial::class,
                        'format'              => '%06d',
                        'label'               => 'Source',
                        'attr'                => array('class' => 'barcode'),
                        'widget_addon_append' => array('icon' => 'qrcode'),
                        'constraints'         => array(new NotNull())
                    )
                )
                ->add('user', UserTypeaheadType::class, array(
                        'label'               => 'Recipient',
                        'required'            => true,
                        'widget_addon_append' => array('icon' => 'user')
                    )
                )
                ->add('type', ChoiceType::class, array(
                        'choices'     => array(
                            'Just give this vial'                      => 'give',
                            'Flip and give this vial'                  => 'flip',
                            'Flip this vial and give the flipped vial' => 'flipped'
                        ),
                        'label'       => 'Flip',
                        'expanded'    => true,
                        'required'    => false,
                        'placeholder' => false,
                    )
                )
                ->add('template', Type\VialOptionsType::class, array(
                        'horizontal'        => false,
                        'label_render'      => false,
                        'widget_form_group' => false,
                        'inherit_data'      => false,
                        'data_class'        => Vial::class
                    )
                );
    }
}
