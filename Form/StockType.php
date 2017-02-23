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

use Bluemesa\Bundle\CoreBundle\Form\TextEntityType;
use Bluemesa\Bundle\FliesBundle\Entity\Stock;
use Bluemesa\Bundle\FliesBundle\Entity\StockVial;
use Bluemesa\Bundle\FormsBundle\Form\Type\TypeaheadType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Bluemesa\Bundle\FliesBundle\Form\Type\GenotypeType;
use Symfony\Component\Validator\Constraints\Range;

/**
 * StockType class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class StockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, array(
                        'label'      => 'Name',
                        'horizontal' => true
                    )
                )
                ->add('genotype', GenotypeType::class, array(
                        'label'      => 'Genotype',
                        'attr'       => array(
                            'data-id-source' => 'cross-id',
                            'class'          => 'fb-genotype'
                        ),
                        'data_route' => 'bluemesa_flies_ajax_genotypes',
                        'required'   => false,
                        'horizontal' => true
                    )
                )
                ->add('source_cross', TextEntityType::class, array(
                        'choice_label'        => 'id',
                        'class'               => 'BluemesaFliesBundle:CrossVial',
                        'format'              => '%06d',
                        'required'            => false,
                        'label'               => 'Source cross',
                        'attr'                => array('class' => 'barcode cross-id'),
                        'widget_addon_append' => array('icon' => 'qrcode'),
                        'horizontal'          => true
                    )
                )
                ->add('notes', TextareaType::class, array(
                        'label'      => 'Notes',
                        'required'   => false,
                        'horizontal' => true
                    )
                )
                ->add('vendor', TypeaheadType::class, array(
                        'label'      => 'Vendor',
                        'required'   => false,
                        'attr'       => array('class' => 'fb-vendor'),
                        'data_route' => 'bluemesa_flies_stock_ajax_flybase_vendor',
                        'horizontal' => true
                    )
                )
                ->add('vendorId', TypeaheadType::class, array(
                        'label'      => 'Vendor ID',
                        'required'   => false,
                        'attr'       => array('class' => 'fb-vendorid'),
                        'data_route' => 'bluemesa_flies_stock_ajax_flybase_stock',
                        'horizontal' => true
                    )
                )
                ->add('infoURL', UrlType::class, array(
                        'label'      => 'Info URL',
                        'required'   => false,
                        'attr'       => array(
                            'placeholder' => 'Paste address here',
                            'class'       => 'fb-link'
                        ),
                        'horizontal' => true
                    )
                )
                ->add('verified', CheckboxType::class, array(
                        'label'      => '',
                        'required'   => false,
                        'horizontal' => true
                    )
                )
                ->addEventListener(
                    FormEvents::PRE_SET_DATA,
                    array($this, 'addVialFields'))
                ->addEventListener(
                    FormEvents::PRE_SUBMIT,
                    array($this, 'addVialFields'));
    }

    /**
     * @param FormEvent $event
     */
    public function addVialFields(FormEvent $event)
    {
        /** @var Stock $stock */
        $builder = $event->getForm();
        $data = $event->getData();
        $stock = is_array($data) ? $builder->getData() : $data;

        if (null === $stock->getId()) {
            $builder->add('vial', Type\VialOptionsType::class, array(
                            'horizontal'        => false,
                            'label_render'      => false,
                            'widget_form_group' => false,
                            'inherit_data'      => false,
                            'mapped'            => false,
                            'data_class'        => StockVial::class
                        )
                    )
                    ->add('number', NumberType::class, array(
                            'label'       => 'Number of vials',
                            'mapped'      => false,
                            'constraints' => array(new Range(array('min' => 1)))
                        )
                    );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Stock::class
        ));
    }
}
