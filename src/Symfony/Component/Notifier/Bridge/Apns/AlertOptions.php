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
 * @author Karoly Gossler <https://github.com/connorhu>
 *
 * @see https://developer.apple.com/library/archive/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/PayloadKeyReference.html#//apple_ref/doc/uid/TP40008194-CH17-SW5
 *
 * @experimental in 5.x
 */
class AlertOptions
{
    private $options = [];
    
    public function title(string $title): self
    {
        $this->options['title'] = $title;
        
        return $this;
    }
    
    public function body(string $body): self
    {
        $this->options['body'] = $body;
        
        return $this;
    }
    
    public function localizedTitleKey(string $key): self
    {
        $this->options['title-loc-key'] = $key;
        
        return $this;
    }
    
    public function localizedTitleArgs(array $args): self
    {
        $this->options['title-loc-args'] = $args;
        
        return $this;
    }
    
    public function localizedActionKey(string $key): self
    {
        $this->options['action-loc-key'] = $key;
        
        return $this;
    }
    
    public function localizedMessageKey(string $key): self
    {
        $this->options['loc-key'] = $key;
        
        return $this;
    }
    
    public function localizedMessageArgs(array $args): self
    {
        $this->options['loc-args'] = $args;
        
        return $this;
    }
    
    public function toArray(): array
    {
        return $this->options;
    }
}
