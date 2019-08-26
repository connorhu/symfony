<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mailer\Bridge\Amazon;

use Symfony\Component\Mailer\SmtpEnvelope;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\Mailer\Bridge\Amazon\Credential\ApiTokenCredential;
use Symfony\Component\Mailer\Bridge\Amazon\Credential\UsernamePasswordCredential;
use Symfony\Component\Mailer\Exception\RuntimeException;

/**
 * @author Karoly Gossler
 */
class SesRequest
{
    private const SERVICE_NAME = 'ses';
    private const ENDPOINT_HOST = 'email.%region%.amazonaws.com';
    public const REQUEST_MODE_API = 1;
    public const REQUEST_MODE_HTTP = 2;
    
    private $mode = self::REQUEST_MODE_API;

    private $now;
    private $action;
    private $region;
    private $credential;
    
    private $requestHeaders = [];
    private $canonicalHeaders = '';
    private $signedHeaders = [];
    
    public function __construct(HttpClientInterface $client, string $region)
    {
        $this->client = $client;
        $this->region = $region ?: 'eu-west-1';
    }
    
    public function getRegion(): string
    {
        return $this->region;
    }
    
    public function setRegion(string $region): self
    {
        $this->region = $region;
        
        return $this;
    }
    
    public function getMode(): int
    {
        return $this->mode;
    }
    
    public function setMode(int $mode): self
    {
        $this->mode = $mode;
        
        return $this;
    }
    
    public function getCredential()
    {
        return $this->credential;
    }
    
    public function setCredential($credential): self
    {
        $this->credential = $credential;
        
        return $this;
    }
    
    public function sendEmail(Email $email, SmtpEnvelope $envelope): ResponseInterface
    {
        $this->now = new \DateTime();
        $this->action = 'SendEmail';
        $this->method = 'POST';
        
        $payload = [
            'Action' => 'SendEmail',
            'Destination.ToAddresses.member' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
            'Message.Subject.Data' => $email->getSubject(),
            'Source' => $envelope->getSender()->toString(),
        ];

        if ($emails = $email->getCc()) {
            $payload['Destination.CcAddresses.member'] = $this->stringifyAddresses($emails);
        }
        if ($emails = $email->getBcc()) {
            $payload['Destination.BccAddresses.member'] = $this->stringifyAddresses($emails);
        }
        if ($email->getTextBody()) {
            $payload['Message.Body.Text.Data'] = $email->getTextBody();
        }
        if ($email->getHtmlBody()) {
            $payload['Message.Body.Html.Data'] = $email->getHtmlBody();
        }
        
        $this->payload = $payload;
        
        $this->prepareRequestHeaders();
        
        return $this->sendRequest();
    }
    
    public function sendRawEmail(string $rawEmail): ResponseInterface
    {
        $this->now = new \DateTime();
        $this->action = 'SendRawEmail';
        $this->method = 'POST';
        $this->payload = [
            'Action' => 'SendRawEmail',
            'RawMessage.Data' => base64_encode($rawEmail),
        ];
        
        $this->prepareRequestHeaders();

        return $this->sendRequest();
    }
    
    private function sendRequest(): ResponseInterface
    {
        $options = [
            'headers' => $this->requestHeaders,
            'body' => $this->payload,
        ];
        return $this->client->request($this->method, 'https://'. $this->getEndpointHost(), $options);
    }
    
    private function prepareRequestHeaders(): void
    {
        $this->requestHeaders = [];
        
        if ($this->mode === self::REQUEST_MODE_API) {
            $this->requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        
        $this->requestHeaders['Host'] = $this->getEndpointHost();
        $this->requestHeaders['X-Amz-Date'] = $this->now->format('Ymd\THis\Z');
        if ($this->credential instanceof ApiTokenCredential) {
            $this->requestHeaders['X-Amz-Security-Token'] = $this->credential->getToken();
        }
        ksort($this->requestHeaders);
        
        
        $canonicalHeadersBuffer = [];
        foreach($this->requestHeaders as $key => $value) {
            $canonicalHeadersBuffer[] = strtolower($key).':'.$value;
        }
        $canonicalHeaders = implode("\n", $canonicalHeadersBuffer);
        
        
        $signedHeadersBuffer = [];
        foreach($this->requestHeaders as $key => $value) {
            $signedHeadersBuffer[] = strtolower($key);
        }
        $signedHeaders = implode(';', $signedHeadersBuffer);
        
        
        $canonicalRequestBuffer = [];
        $canonicalRequestBuffer[] = $this->method;
        $canonicalRequestBuffer[] = '/';
        $canonicalRequestBuffer[] = "";
        $canonicalRequestBuffer[] = $canonicalHeaders;
        $canonicalRequestBuffer[] = "";
        $canonicalRequestBuffer[] = $signedHeaders;
        $canonicalRequestBuffer[] = hash('sha256', $this->arrayToSignableString($this->payload));
        $hashedCanonicalRequest = hash('sha256', implode("\n", $canonicalRequestBuffer));
        
        
        $this->scope = $this->now->format('Ymd').'/'.$this->region.'/'.self::SERVICE_NAME.'/aws4_request';
        
        
        $stringToSignBuffer = [];
        $stringToSignBuffer[] = 'AWS4-HMAC-SHA256';
        $stringToSignBuffer[] = $this->now->format('Ymd\THis\Z');
        $stringToSignBuffer[] = $this->scope;
        $stringToSignBuffer[] = $hashedCanonicalRequest;
        $stringToSign = implode("\n", $stringToSignBuffer);
        
        
        if ($this->credential instanceof ApiTokenCredential) {
            $keySecret = 'AWS4'.$this->credential->getSecretKey();
        }
        elseif ($this->credential instanceof UsernamePasswordCredential) {
            $keySecret = 'AWS4'.$this->credential->getPassword();
        }
        else {
            throw new RuntimeException('Unsupported credential');
        }
        
        $keyDate = hash_hmac('sha256', $this->now->format('Ymd'), $keySecret, true);
        $keyRegion = hash_hmac('sha256', $this->region, $keyDate, true);
        $keyService = hash_hmac('sha256', self::SERVICE_NAME, $keyRegion, true);
        $keySigning = hash_hmac('sha256', 'aws4_request', $keyService, true);
        $signature = hash_hmac('sha256', $stringToSign, $keySigning);
        
        
        $authorizationBuffer = 'AWS4-HMAC-SHA256 ';
        if ($this->credential instanceof ApiTokenCredential) {
            $authorizationBuffer .= 'Credential='.$this->credential->getAccessKey().'/'.$this->scope;
        }
        elseif ($this->credential instanceof UsernamePasswordCredential) {
            $authorizationBuffer .= 'Credential='.$this->credential->getUsername().'/'.$this->scope;
        }
        $authorizationBuffer .= ', SignedHeaders='.$signedHeaders;
        $authorizationBuffer .= ', Signature='.$signature;
        
        $this->requestHeaders['Authorization'] = $authorizationBuffer;
    }
    
    private function arrayToSignableString(array $array): string
    {
        $buffer = '';
        
        foreach ($array as $key => $value) {
            $key = str_replace('%7E', '~', rawurlencode($key));
            $value = str_replace('%7E', '~', rawurlencode($value));
            
            $buffer .= '&'.$key.'='.$value;
        }
        
        return substr($buffer, 1);
    }
    
    private function getEndpointHost(): string
    {
        return str_replace('%region%', $this->region, self::ENDPOINT_HOST);
    }
    
    /**
     * @param Address[] $addresses
     *
     * @return string[]
     */
    private function stringifyAddresses(array $addresses): array
    {
        return array_map(function (Address $a) {
            return $a->toString();
        }, $addresses);
    }
}
