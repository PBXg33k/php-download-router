<?php

namespace App\Validator;

use App\Factory\DownloaderFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class SelectDownloaderValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DownloaderFactory $downloaderFactory,
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var SelectDownloader $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        // Make sure the value is a valid downloader.
        if ($this->downloaderFactory->isValidDownloader($value)) {
            return;
        } else {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
