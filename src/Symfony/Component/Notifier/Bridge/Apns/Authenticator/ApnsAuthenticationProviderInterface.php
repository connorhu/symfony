<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\Apns\Authenticator;

use Symfony\Component\Notifier\Message\MessageInterface;

/**
 * @author Karoly Gossler <https://github.com/connorhu>
 *
 * @experimental in 5.x
 */
interface ApnsAuthenticationProviderInterface
{
    public function authenticate(array $requestOptions, MessageInterface $message);
}
