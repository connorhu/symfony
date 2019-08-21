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

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SmtpEnvelope;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Kevin Verschaeve
 */
class SesApiTransport extends AbstractApiTransport
{
    use RequestSignTrait;

    private const ENDPOINT = 'https://email.%region%.amazonaws.com';

    private $credential;
    private $region;

    /**
     * @param string $region Amazon SES region (currently one of us-east-1, us-west-2, or eu-west-1)
     */
    public function __construct($credential, string $region = null, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->credential = $credential;
        $this->region = $region ?: 'eu-west-1';

        parent::__construct($client, $dispatcher, $logger);
    }

    public function getName(): string
    {
        $login = null;
        if ($this->credential instanceof ApiTokenCredential) {
            $login = $this->credential->getAccessKey();
        } else {
            $login = $this->credential->getUsername();
        }

        return sprintf('api://%s@ses?region=%s', $login, $this->region);
    }

    protected function doSendApi(Email $email, SmtpEnvelope $envelope): ResponseInterface
    {
        $headers = $this->getSignatureHeaders();
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        $endpoint = str_replace('%region%', $this->region, self::ENDPOINT);
        $response = $this->client->request('POST', $endpoint, [
            'headers' => $headers,
            'body' => $this->getPayload($email, $envelope),
        ]);

        if (200 !== $response->getStatusCode()) {
            $error = new \SimpleXMLElement($response->getContent(false));

            throw new HttpTransportException(sprintf('Unable to send an email: %s (code %s).', $error->Error->Message, $error->Error->Code), $response);
        }

        return $response;
    }

    private function getPayload(Email $email, SmtpEnvelope $envelope): array
    {
        if ($email->getAttachments()) {
            return [
                'Action' => 'SendRawEmail',
                'RawMessage.Data' => base64_encode($email->toString()),
            ];
        }

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

        return $payload;
    }
}
