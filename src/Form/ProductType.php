<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; 
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver; 
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType; 
class ProductType extends AbstractType
{
    private ?\DateTimeImmutable $createdAt = null;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $usersTimezone = 'Asia/Singapore';
            $date = new \DateTime( 'now', new \DateTimeZone($usersTimezone) );
            $time_set_for_sg =  $date->format('Y-m-d H:i:s'); 


            // date_default_timezone_set('Asia/Singapore');
            // $product = new Product();
            // $product->setCreatedAt(new \DateTimeImmutable());
            // $date = new \DateTime(); // existing DateTime object
            // $immutableDate = \DateTimeImmutable::createFromMutable($date);
            // $product->setCreatedAt($immutableDate);


            
                $builder
                    ->add('name', TextType::class, [
                        'label' => 'Product Name'
                    ])
                    ->add('description', TextType::class, [
                        'label' => 'Description'
                    ])
                    ->add('price', NumberType::class, [
                        'label' => 'Price',
                        'scale' => 2,  // Optional: Number of decimal places
                        'attr' => [
                            'min' => 0, // Optional: Minimum value for the field
                            'step' => 0.01, // Optional: Step value for decimals
                        ],
                    ])
                    ->add('stockQuantity', NumberType::class, [
                        'label' => 'Stock Quantity',
                        'attr' => [
                            'min' => 0, // Optional: Minimum stock quantity
                            'step' => 1, // Optional: Integer step
                        ],
                    ])
                    ->add('createdAt', DateTimeType::class, [
                        'label' => 'Created Date',
                        'widget' => 'single_text',
                        'input' => 'datetime_immutable', // to handle DateTimeImmutable if used 
                        'view_timezone' => 'Asia/Singapore', // display timezone
                    ]);
                   
            
 
            // $builder
            // ->add('name', TextType::class, [
            //     'label' => 'Product Name',
            // ])
            // ->add('description', TextareaType::class, [
            //     'label' => 'Product Description',
            // ])
            // ->add('price', NumberType::class, [
            //     'label' => 'Price',
            //     'scale' => 2, // For decimal precision
            // ])
            // ->add('stockQuantity', NumberType::class, [
            //     'label' => 'Stock Quantity',
            // ])
            // ->add('createdAt', DateTimeType::class, [
            //     'label' => 'Created Date',
            //     'widget' => 'single_text',
            //     'input' => 'datetime_immutable', // to handle DateTimeImmutable if used 
            //     'model_timezone' => 'UTC',        // storage timezone
            //     'view_timezone' => 'Asia/Singapore', // display timezone
            // ]);
            

           
            // $builder
            // ->add('name')
            // ->add('description')
            // ->add('price')
            // ->add('stock_quantity')
            // ->add('created_at', DateTimeType::class,[
            //     'widget' => 'single_text', 
            //     'view_timezone' => 'Asia/Singapore', // or the timezone you want to display
            //     'model_timezone' => 'Asia/Singapore' // timezone in which it should be stored
            // ])
        // ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

}
