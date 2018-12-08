<?php

namespace LSI\MarketBundle\Form;

use LSI\MarketBundle\Repository\EpciRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MairieType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextareaType::class, array('required' => false))
            ->add('service', TextareaType::class, array('required' => false))
            ->add('longitude', TextType::class, array('required' => false))
            ->add('latitude', TextType::class, array('required' => false))
            ->add('epci', EntityType::class, array(
                'required' => false,
                'class' => 'LSIMarketBundle:Epci',
                /*'query_builder' => function(EpciRepository $er){

                    return $er->getListEpci();
                },*/
                'choice_label' => 'nom',
                /*'choice_label' => function($x){
                     return $x->getNom();
                },*/
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'Sélectionner votre EPCI'
            ))
        ;

        $builder
            //->remove('epci')
            ->remove('longitude')
            ->remove('latitude')
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'LSI\MarketBundle\Entity\Mairie'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'lsi_marketbundle_mairie';
    }


}
