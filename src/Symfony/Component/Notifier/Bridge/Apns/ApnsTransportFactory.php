<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\Apns;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Apns\Authenticator\ApnsAuthenticatiorInterface;
use Symfony\Component\Notifier\Apns\Authenticator\CertBasedAuthenticator;
use Symfony\Component\Notifier\Apns\Authenticator\TokenBasedAuthenticator;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

/**
 * @author Karoly Gossler <https://github.com/connorhu>
 *
 * @experimental in 5.x
 */
final class ApnsTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $isProduction = 'default' === $dsn->getHost();

        if ('apns' === $scheme) {
            $authenticator = $this->getAuthenticationProvider($dsn);
            return (new ApnsTransport($isProduction, $authenticator, $this->client, $this->dispatcher));
        }

        throw new UnsupportedSchemeException($dsn, 'apns', $this->getSupportedSchemes());
    }
    
    protected function getAuthenticationProvider(Dsn $dsn): ApnsAuthenticationProviderInterface
    {
        if ($dsn->getOption('cert', false) !== false) {
            return new CertBasedAuthenticator($dsn->getOption('cert'), $dsn->getOption('phasspase'));
        }
        
        if ($dsn->getOption('key_id', false) !== false) {
            return new TokenBasedAuthenticator($dsn->getOption('key_id'), $dsn->getOption('team_id'), $dsn->getOption('private_key'), $dsn->getOption('passphrase', ''));
        }
        
        throw new LogicException('todo: wrong settings');
    }

    protected function getSupportedSchemes(): array
    {
        return ['apns'];
    }
}
