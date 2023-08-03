<?php

declare(strict_types=1);

namespace Tbbc\MoneyBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tbbc\MoneyBundle\Entity\DoctrineCurrency;
use Tbbc\MoneyBundle\Form\DataTransformer\SimpleMoneyToArrayTransformer;
use Tbbc\MoneyBundle\Repository\DoctrineCurrencyRepository;

/**
 * Formtype for the Money object.
 */
class SimpleMoneyType extends MoneyType
{
    protected int $decimals;

    public function __construct(
        int $decimals,
        protected DoctrineCurrencyRepository $doctrineCurrencyRepository,
    ) {
        parent::__construct($decimals);

        $this->decimals = $decimals;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<string, mixed> $amountOptions */
        $amountOptions = $options['amount_options'];
        $builder
            ->add('tbbc_amount', TextType::class, $amountOptions)
        ;

        $transformer = new SimpleMoneyToArrayTransformer($this->decimals);
        /** @var string $currency */
        $currency = $options['currency'];
        $transformer->setCurrency($currency);

        $builder
            ->addModelTransformer($transformer)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $referenceCurrencyCode = $this->doctrineCurrencyRepository->getReferenceCurrency()->getCurrencyCode();
        $currencyCodeList = array_map(function (DoctrineCurrency $doctrineCurrency) {
            return $doctrineCurrency->getCurrencyCode();
        }, $this->doctrineCurrencyRepository->findAll());

        $resolver->setDefaults([
            'currency' => $referenceCurrencyCode,
            'amount_options' => [],
        ]);
        $resolver->setAllowedTypes('currency', 'string');
        $resolver->setAllowedValues('currency', $currencyCodeList);
        $resolver->setAllowedTypes('amount_options', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'tbbc_simple_money';
    }
}
