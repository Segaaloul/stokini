<?php

namespace App\Form;
use App\Entity\Dossier;
use App\Entity\Fichier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;

class FichierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('dossier', EntityType::class, [
        'class' => Dossier::class,
        'choice_label' => 'nom',
        'placeholder' => 'Sélectionnez un dossier',
        'required' => false,
        ])
        ->add('fichier', FileType::class, [
        'label' => 'Fichier à uploader',
        'mapped' => false, // pas relié à une propriété de l'entité
        'multiple' => true, // 👈 pour accepter plusieurs fichiers
        'required' => true,
        
        
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Fichier::class,
        ]);
    }
}
