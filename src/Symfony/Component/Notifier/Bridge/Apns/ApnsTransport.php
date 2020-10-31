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

use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Jeroen Spee <https://github.com/Jeroeny>
 *
 * @experimental in 5.x
 */
final class ApnsTransport extends AbstractTransport
{
    protected const HOST = 'api.push.apple.com';
    protected const HOST_DEV = 'api.development.push.apple.com';

    /** @var bool */
    private $production;

    public function __construct(bool $isProduction, ApnsAuthenticatiorInterface $authenticator, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->production = $isProduction;
        $this->host = $this->production ? : self::HOST : self::HOST_DEV;
        $this->port = 443;
        $this->authenticator = $authenticator;
        $this->client = $client;

        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        return sprintf('apns://%s', $this->getEndpoint());
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage;
    }
    
    protected function getPayload(MessageInterface $message, array $messageOptions): array
    {
        if (!(isset($messageOptions['options']) && count($messageOptions['options']) > 0)) {
            return = [
                'aps' => [
                    'alert' => $message->getSubject(),
                ]
            ];
        }
        
        $messageOptions = $messageOptions['options'];
        $payload = [
            'aps' => []
        ];

        // if (isset($messageOptions['badge'])) {
        //     $payload['aps']['badge'] = $messageOptions['badge'];
        // }
        //
        // if (isset($messageOptions['sound'])) {
        //     $payload['aps']['sound'] = $messageOptions['sound'];
        // }
        //
        // if (isset($messageOptions['category'])) {
        //     $payload['aps']['category'] = $messageOptions['category'];
        // }
        //
        // if (isset($messageOptions['loc-args'])) {
        //     $payload['aps'] = [
        //         'alert' => [
        //             'loc-key' => $message->getSubject(),
        //             'loc-args' => $messageOptions['loc-args']
        //         ]
        //     ];
        // }
        //
        // if (isset($messageOptions['alert']['action-loc-key'])) {
        //     $payload['aps']['alert']['action-loc-key'] = $messageOptions['alert']['action-loc-key'];
        // }
        //
        // if (isset($messageOptions['alert']['launch-image'])) {
        //     $payload['aps']['alert']['launch-image'] = $messageOptions['alert']['launch-image'];
        // }
        //
        // $payload['aps']['alert']['title'] = $message->getSubject();
        
        if (isset($messageOptions['background']) && $messageOptions['background'] === true) {
            $payload['aps'] = [
                'content-available' => 1,
            ];
        }
        
        if (isset($messageOptions['app-data'])) {
            $payload = array_merge($payload, $messageOptions['app-data']);
        }
        
        return $payload;
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof ChatMessage) {
            throw new LogicException(sprintf('The "%s" transport only supports instances of "%s" (instance of "%s" given).', __CLASS__, ChatMessage::class, get_debug_type($message)));
        }

        $endpoint = sprintf('https://%s/3/device/$s', $this->getEndpoint(), $message->getRecipientId());
        $messageOptions = ($opts = $message->getOptions()) ? $opts->toArray() : [];
        
        if (isset($messageOptions['payload']['aps']['alert']) && is_string($messageOptions['payload']['aps']['alert'])) {
            $messageOptions['payload']['aps']['alert'] = $message->getSubject();
        }

        $requestOptions = [
            'headers' => $messageOptions['headers'],
            'json' => $messageOptions['payload'],
        ];

        $this->authenticator->authenticate($requestOptions, $message);
        
        dump($endpoint);
        dump($requestOptions);
        exit;
        
        $response = $this->client->request('POST', $endpoint, $requestOptions);

        $contentType = $response->getHeaders(false)['content-type'][0] ?? '';
        $jsonContents = 0 === strpos($contentType, 'application/json') ? $response->toArray(false) : null;

        if (200 !== $response->getStatusCode()) {
            $errorMessage = $jsonContents ? $jsonContents['results']['error'] : $response->getContent(false);

            throw new TransportException(sprintf('Unable to post the Firebase message: %s.', $errorMessage), $response);
        }
        if ($jsonContents && isset($jsonContents['results']['error'])) {
            throw new TransportException(sprintf('Unable to post the Firebase message: %s.', $jsonContents['error']), $response);
        }

        $success = $response->toArray(false);

        $message = new SentMessage($message, (string) $this);
        $message->setMessageId($success['results'][0]['message_id']);

        return $message;
    }
}
