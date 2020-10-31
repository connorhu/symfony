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

use Symfony\Component\Notifier\Exception\LogicException;

/**
 * @author Karoly Gossler <https://github.com/connorhu>
 *
 * @experimental in 5.x
 */
final class CertBasedAuthenticator implements ApnsAuthenticationProviderInterface
{
    public function __construct(string $certFilePath, string $passphrase = null)
    {
        if (!is_file($certFilePath)) {
            throw new LogicException(sprintf('Certificate file not found at path "%s".', $certFilePath));
        }
        
        $this->certFilePath = $certFilePath;
        $this->passphrase = $passphrase;
    }
    
    public function authenticate(array $requestOptions, MessageInterface $message)
    {
        $requestOptions['local_cert'] = $this->certFilePath;
        
        if ($this->passphrase !== null) {
            $requestOptions['passphrase'] = $this->passphrase;
        }
    }
}