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
final class TokenBasedAuthenticator implements ApnsAuthenticationProviderInterface
{
    protected $keyId;
    protected $teamId;
    private $privateKey;
    
    public function __construct(string $keyId, string $teamId, string $privateKey, string $passphrase = '')
    {
        $this->keyId = $keyId;
        $this->teamId = $teamId;
        
        $this->privateKey = self::getPrivateKey($privateKey, $passphrase);
    }
    
    protected static function getPrivateKey(string $privateKey, string $passphrase = '')
    {
        // based on Luís Cobucci's jwt library
        $privateKey = \openssl_pkey_get_private($privateKey, $passphrase);
        
        if (is_bool($privateKey)) {
            throw new LogicException(sprintf('Impossible to parse the private key, reason: "%s".', \openssl_error_string()));
        }
        
        $details = \openssl_pkey_get_details($privateKey);
        
        if (!isset($details['key']) || $details['type'] !== OPENSSL_KEYTYPE_EC) {
            throw new LogicException(sprintf('Private Key type must be ec. Type of the given key is "%s".', $details['type']));
        }
        
        return $privateKey;
    }
    
    protected function getSignature(): string
    {
        $header = json_encode([
            'alg' => 'ES256',
            'kid' => $this->keyId,
        ]);
        $payload = json_encode([
            'iss' => $this->teamId,
            'iat' => (new \DateTime('now', new \DateTimeZone('UTC'))),
        ]);
        
        \openssl_sign(base64_encode($header), $headerSignature, $this->privateKey, OPENSSL_ALGO_SHA256);
        \openssl_sign(base64_encode($payload), $payloadSignature, $this->privateKey, OPENSSL_ALGO_SHA256);
        
        return $headerSignature.'.'.$payloadSignature;
    }
    
    public function authenticate(array $requestOptions, MessageInterface $message)
    {
        $requestOptions['headers']['Authorization'] = 'bearer '. $this->getSignature();
    }
    
    public function __destruct()
    {
        if ($this->privateKey && PHP_VERSION_ID < 80000) {
            openssl_free_key($this->privateKey);
        }
    }
}