<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mailer\Bridge\Amazon\Transport;

use Symfony\Component\Mailer\Bridge\Amazon\Credential\ApiTokenCredential;
use Symfony\Component\Mailer\Bridge\Amazon\Credential\UsernamePasswordCredential;

/**
 * @author Karoly Gossler
 */
trait RequestSignTrait
{
    private function getSignatureHeaders(): array
    {
        $headers = [];
        $date = gmdate('D, d M Y H:i:s e');

        if ($this->credential instanceof ApiTokenCredential) {
            $accessKey = $this->credential->getAccessKey();
            $secretKey = $this->credential->getSecretKey();

            $headers['X-Amz-Security-Token'] = $this->credential->getToken();
        } elseif ($this->credential instanceof UsernamePasswordCredential) {
            $accessKey = $this->credential->getUsername();
            $secretKey = $this->credential->getPassword();
        }

        $signature = base64_encode(hash_hmac('sha256', $date, $secretKey, true));
        $headers['X-Amzn-Authorization'] = sprintf('AWS3-HTTPS AWSAccessKeyId=%s,Algorithm=HmacSHA256,Signature=%s', $accessKey, $signature);
        $headers['Date'] = $date;

        return $headers;
    }
}
