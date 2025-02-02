<?php

declare(strict_types=1);

namespace Tbbc\MoneyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tbbc\MoneyBundle\Form\DataTransformer\CurrencyToArrayTransformer;
use Tbbc\MoneyBundle\Manager\DoctrineCurrencyManager;

/**
 * Formtype for the Currency object.
 */
class CurrencyType extends AbstractType
{
    public function __construct(
        protected DoctrineCurrencyManager $doctrineCurrencyManager,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress MixedAssignment, MixedArgument, MixedArgumentTypeCoercion, MixedArrayOffset
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choiceList = [];
        foreach ($options['currency_choices'] as $currencyCode) {
            $choiceList[$currencyCode] = $currencyCode;
        }

        $builder->add('tbbc_name', ChoiceType::class, array_merge([
            'choices' => $choiceList,
            'preferred_choices' => [$options['reference_currency']],
        ], $options['currency_options']));

        $builder->addModelTransformer(new CurrencyToArrayTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $referenceCurrencyCode = $this->doctrineCurrencyManager->getReferenceCurrency()->getCurrencyCode();
        $currencyCodeList = $this->doctrineCurrencyManager->getCurrencyCodeList();

        $resolver->setRequired(['reference_currency', 'currency_choices']);
        $resolver->setDefaults([
            'reference_currency' => $referenceCurrencyCode,
            'currency_choices' => $currencyCodeList,
            'currency_options' => [],
        ]);
        $resolver->setAllowedTypes('reference_currency', 'string');
        $resolver->setAllowedTypes('currency_choices', 'array');
        $resolver->setAllowedValues('reference_currency', $currencyCodeList);
        $resolver->setAllowedTypes('currency_options', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'tbbc_currency';
    }
}
