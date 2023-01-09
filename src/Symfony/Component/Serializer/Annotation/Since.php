<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Annotation;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * Annotation class for @Since().
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"PROPERTY", "METHOD"})
 *
 * @author Arnaud Tarroux <ta.arnaud@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
class Since
{
    public function __construct(private readonly string $version)
    {
        if ('' === $version) {
            throw new InvalidArgumentException(sprintf('Parameter of annotation "%s" must be a non-empty string.', self::class));
        }
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
