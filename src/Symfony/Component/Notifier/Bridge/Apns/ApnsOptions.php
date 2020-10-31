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

use Symfony\Component\Notifier\Message\MessageOptionsInterface;

/**
 * @author Jeroen Spee <https://github.com/Jeroeny>
 *
 * @see https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/sending_notification_requests_to_apns/#2947610
 *
 * @experimental in 5.x
 */
class ApnsOptions implements MessageOptionsInterface
{
    private $to;
    
    const PUSH_ALERT_TYPE = 'alert';
    const PUSH_BACKGROUND_TYPE = 'background';
    
    /** @var string apns-push-type */
    private $type = self::PUSH_ALERT_TYPE;
    
    /** @var string apns-id */
    private $id;

    /** @var string apns-expiration */
    private $expiration;
    
    const PUSH_IMMEDIATELY_PRIORITY = 10;
    const PUSH_LOW_PRIORITY = 5;
    const PUSH_PRIORITIES = [
        self::PUSH_IMMEDIATELY_PRIORITY,
        self::PUSH_LOW_PRIORITY,
    ];
    
    /** @var string apns-priority */
    private $priority;

    /** @var string apns-topic */
    private $topic;
    
    /** @var string apns-collapse-id */
    private $collapseId;

    /**
     * @var array
     *
     * @see https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref.html#notification-payload-support
     */
    protected $options;
    
    /** @var AlertOptions */
    private $alert;

    public function __construct(string $to, array $options = [])
    {
        $this->to = $to;
        $this->options = $options;
    }
    
    public function getRecipientId(): ?string
    {
        return $this->to;
    }
    
    public function type(string $type): self
    {
        $this->type = $type;
        
        return $this;
    }
    
    public function getType(): string
    {
        return $this->type;
    }
    
    /** @arg id string UUIDs */
    public function id(string $id): self
    {
        $this->id = $id;
        
        return $this;
    }
    
    public function getId(): ?string
    {
        return $this->id;
    }
    
    public function expiration(\DateTime $expiration): self
    {
        if ($expiration->getTimezone()->getName() !== 'UTC') {
            $expiration->setTimezone(new \DateTimeZone('UTC'));
        }
        
        $this->expiration = $expiration;
        
        return $this;
    }
    
    public function getExpiration(): \DateTime
    {
        return $this->expiration;
    }
    
    public function priority(int $priority): self
    {
        if (!in_array($priority, self::PUSH_PRIORITIES)) {
            throw new LogicException(sprintf('todo'));
        }

        $this->priority = $priority;
        
        return $this;
    }
    
    public function getPriority(): ?int
    {
        return $this->priority;
    }
    
    public function topic(string $topic): self
    {
        $this->topic = $topic;
        
        return $this;
    }
    
    public function getTopic(): ?string
    {
        return $this->topic;
    }
    
    public function collapseId(string $collapseId): self
    {
        $this->collapseId = $collapseId;
        
        return $this;
    }
    
    public function getCollapseId(): ?string
    {
        return $this->collapseId;
    }
    
    public function background(bool $background): self
    {
        $this->options['background'] = $background;
        
        return $this;
    }
    
    public function sound(string $sound): self
    {
        $this->options['sound'] = $sound;
        
        return $this;
    }
    
    public function badge(int $badge): self
    {
        $this->options['badge'] = $badge;
        
        return $this;
    }
    
    public function category(string $category): self
    {
        $this->options['category'] = $category;
        
        return $this;
    }
    
    public function threadId(string $threadId): self
    {
        $this->options['thread-id'] = $threadId;
        
        return $this;
    }
    
    public function alert(AlertOptions $alert): self
    {
        $this->alert = $alert;
        
        return $this;
    }
    
    public function addAppSpecificData(string $key, $value): self
    {
        if (!isset($this->options['app-data'])) {
            $this->options['app-data'] = [];
        }
        
        $this->options['app-data'][$key] = $value;
        
        return $this;
    }
    
    public function payload(): array
    {
        if (isset($this->options['app-data'])) {
            $payload = $this->options['app-data'];
        }
        
        if (isset($this->options['background']) && $this->options['background'] === true) {
            $payload['aps'] = [
                'content-available' => 1
            ];
            
            return $payload;
        }
        
        if ($this->alert !== null) {
            $payload['aps'] = [
                'alert' => $this->alert->toArray(),
            ];
        }
        else {
            $payload['aps'] = [
                'alert' => '',
            ];
        }
        
        if (isset($this->options['sound'])) {
            $payload['aps']['sound'] = $this->options['sound'];
        }
        
        if (isset($this->options['badge'])) {
            $payload['aps']['badge'] = $this->options['badge'];
        }
        
        if (isset($this->options['category'])) {
            $payload['aps']['category'] = $this->options['category'];
        }
        
        if (isset($this->options['thread-id'])) {
            $payload['aps']['thread-id'] = $this->options['thread-id'];
        }
        
        return $payload;
    }
    
    public function toArray(): array
    {
        $headers = [
            'apns-push-type' => $this->type,
            'apns-id' => $this->id,
            'apns-expiration' => $this->expiration !== null ? $this->expiration->format('U') : null,
            'apns-priority' => $this->priority,
            'apns-topic' => $this->topic,
            'apns-collapse-id' => $this->collapseId,
        ];
        
        return [
            'headers' => array_filter($headers),
            'payload' => array_filter($this->payload()),
        ];
    }
    
    public function getOptions(): array
    {
        return $this->options;
    }
}
