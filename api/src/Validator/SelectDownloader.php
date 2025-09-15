<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class SelectDownloader extends Constraint
{
    public string $message = 'The string "{{ value }}" is not a valid downloader.';

    // You can use #[HasNamedArguments] to make some constraint options required.
    // All configurable options must be passed to the constructor.
    public function __construct(
        public string $mode = 'strict',
        ?array        $groups = null,
        mixed         $payload = null
    )
    {
        parent::__construct([], $groups, $payload);
    }
}
