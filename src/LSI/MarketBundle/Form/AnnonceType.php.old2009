<?php

namespace LSI\MarketBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class AnnonceType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('titre', TextType::class, array(
                'label' => 'Titre de l\'annonce',
                'required' => true,

            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Description de l\'annonce',
                'required' => true,

            ))
            ->add('regleCond', TextType::class, array(
                'label' => 'Règle',
                'required' => true,

            ))
            ->add('prixDefaut', TextType::class, array(
                'label' => 'Prix par defaut',
                'required' => true,

            ))
           /* ->add('dateCreation', DateTimeType::class, array(
                'label' => 'Date de création de l\'annonce',
                'required' => true,
            ))*/
            ->add('annonceUpdateAt', DateTimeType::class)
            ->add('mairie')
            /*->add('statut', EntityType::class, array(
                'label' => 'Statut de l\'annonce',
                'class' => 'LSIMarketBundle:Statut',
                'choice_label' => 'libelle',
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'Sélectionner un statut',
            ))*/
            ->add('images', ImageType::class, array('label' => ''))
            ->add('categorie', EntityType::class, array(
                'label' => 'Catégorie de l\'annonce',
                'class' => 'LSIMarketBundle:Categorie',
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
                'placeholder' => 'Sélectionner une catégorie',
            ))
            ->add('save', SubmitType::class, array())
        ;

        $builder
            ->remove('dateCreation')
            ->remove('annonceUpdateAt')
            ->remove('mairie')
            //->remove('dateCreation')
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'LSI\MarketBundle\Entity\Annonce'
        ));
    }


    public function getBlockPrefix()
    {
        return 'lsi_marketbundle_annonce';
    }


}
