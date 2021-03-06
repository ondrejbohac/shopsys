<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Component\Constraints;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProductCanBeOrderedValidator extends ConstraintValidator
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade
     */
    protected $productCachedAttributesFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser
     */
    protected $currentCustomerUser;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     */
    public function __construct(
        ProductFacade $productFacade,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        Domain $domain,
        CurrentCustomerUser $currentCustomerUser
    ) {
        $this->productFacade = $productFacade;
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
        $this->domain = $domain;
        $this->currentCustomerUser = $currentCustomerUser;
    }

    /**
     * @param mixed $value
     * @param \Symfony\Component\Validator\Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductCanBeOrdered) {
            throw new \Symfony\Component\Validator\Exception\UnexpectedTypeException($constraint, ProductCanBeOrdered::class);
        }

        // Field types and content is assured by GraphQL type definition
        $uuid = $value['uuid'];
        $priceWithoutVat = $value['unitPrice']['priceWithoutVat'];
        $priceWithVat = $value['unitPrice']['priceWithVat'];
        $vatAmount = $value['unitPrice']['vatAmount'];

        try {
            $productEntity = $this->productFacade->getSellableByUuid(
                $uuid,
                $this->domain->getId(),
                $this->currentCustomerUser->getPricingGroup()
            );
        } catch (ProductNotFoundException $exception) {
            $this->addViolationWithCodeToContext($constraint->productNotFoundMessage, ProductCanBeOrdered::PRODUCT_NOT_FOUND_ERROR, $uuid);
            return;
        }

        $sellingPrice = $this->productCachedAttributesFacade->getProductSellingPrice($productEntity);

        if ($sellingPrice === null) {
            $this->addViolationWithCodeToContext($constraint->noSellingPriceMessage, ProductCanBeOrdered::NO_SELLING_PRICE_ERROR, $uuid);
            return;
        }

        if (!$sellingPrice->getPriceWithoutVat()->equals($priceWithoutVat) ||
            !$sellingPrice->getPriceWithVat()->equals($priceWithVat) ||
            !$sellingPrice->getVatAmount()->equals($vatAmount)
        ) {
            $this->addViolationWithCodeToContext($constraint->pricesDoesNotMatchMessage, ProductCanBeOrdered::PRICES_DOES_NOT_MATCH_ERROR, $uuid);
        }
    }

    /**
     * @param string $message
     * @param string $code
     * @param string $uuid
     */
    protected function addViolationWithCodeToContext(string $message, string $code, string $uuid): void
    {
        $this->context->buildViolation($message)
            ->setParameter('{{ uuid }}', $uuid)
            ->setCode($code)
            ->addViolation();
    }
}
