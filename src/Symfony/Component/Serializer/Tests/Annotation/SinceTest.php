<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Annotation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Annotation\Since;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * @author Arnaud Tarroux <ta.arnaud@gmail.com>
 */
class SinceTest extends TestCase
{
    public function testNotAStringVersionParameter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Parameter of annotation "Symfony\Component\Serializer\Annotation\Since" must be a non-empty string.'
        );
        new Since('');
    }

    public function testVersionParameters()
    {
        $since = new Since('1.1.2');
        $this->assertSame('1.1.2', $since->getVersion());
    }
}
