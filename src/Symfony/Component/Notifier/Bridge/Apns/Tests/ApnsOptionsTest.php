<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\Apns\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Bridge\Apns\ApnsOptions;
use Symfony\Component\Notifier\Bridge\Apns\AlertOptions;

final class ApnsOptionsTest extends TestCase
{
    public function testBackgroundNotificationPayload()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->background(true);

        $this->assertSame($apnsOptions->payload(), [
            'aps' => [
                'content-available' => 1,
            ],
        ]);

        $apnsOptions = (new ApnsOptions('recipient'))
            ->background(true)
            ->addAppSpecificData('acme', ['acme data'])
            ->addAppSpecificData('acme2', ['acme2 data'])
        ;

        $this->assertSame($apnsOptions->payload(), [
            'acme' => ['acme data'],
            'acme2' => ['acme2 data'],
            'aps' => [
                'content-available' => 1,
            ],
        ]);


        $apnsOptions = (new ApnsOptions('recipient'))
            ->background(true)
            ->addAppSpecificData('acme', ['acme data'])
            ->addAppSpecificData('acme2', ['acme2 data'])
            ->addAppSpecificData('acme', ['acme overrided data'])
        ;

        $this->assertSame($apnsOptions->payload(), [
            'acme' => ['acme overrided data'],
            'acme2' => ['acme2 data'],
            'aps' => [
                'content-available' => 1,
            ],
        ]);
    }
    
    public function testSimpleNotificationPayload()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
        ;
        
        $this->assertSame($apnsOptions->payload(), [
            'aps' => [
                'alert' => '',
            ],
        ]);
        
        $apnsOptions = (new ApnsOptions('recipient'))
            ->addAppSpecificData('acme', ['acme data'])
        ;
        
        $this->assertSame($apnsOptions->payload(''), [
            'acme' => ['acme data'],
            'aps' => [
                'alert' => '',
            ],
        ]);
    }
    
    public function testSoundPayload()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->sound('sound.aff')
        ;
        
        $this->assertSame($apnsOptions->payload(), [
            'aps' => [
                'alert' => '',
                'sound' => 'sound.aff',
            ],
        ]);
    }
    
    public function testBadgePayload()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->badge(3)
        ;
        
        $this->assertSame($apnsOptions->payload(), [
            'aps' => [
                'alert' => '',
                'badge' => 3,
            ],
        ]);
    }
    
    public function testThreadIdPayload()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->threadId('thread-id-1234')
        ;
        
        $this->assertSame($apnsOptions->payload(), [
            'aps' => [
                'alert' => '',
                'thread-id' => 'thread-id-1234',
            ],
        ]);
    }
    
    public function testAlertPayload()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->alert(
                (new AlertOptions())
                    ->title('title string')
                    ->body('body string')
                    ->localizedTitleKey('TITLE_KEY')
                    ->localizedTitleArgs(['title arg1', 'title arg2'])
                    ->localizedActionKey('ACTION_KEY')
                    ->localizedMessageKey('MESSAGE_KEY')
                    ->localizedMessageArgs(['message arg1', 'message arg2'])
            )
        ;
        
        $this->assertSame($apnsOptions->payload(), [
            'aps' => [
                'alert' => [
                    'title' => 'title string',
                    'body' => 'body string',
                    'title-loc-key' => 'TITLE_KEY',
                    'title-loc-args' => ['title arg1', 'title arg2'],
                    'action-loc-key' => 'ACTION_KEY',
                    'loc-key' => 'MESSAGE_KEY',
                    'loc-args' => ['message arg1', 'message arg2'],
                ],
            ],
        ]);
    }
    
    public function testFullOptionPayload()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->sound('sound.aff')
            ->badge(3)
            ->threadId('thread-id-1234')
            ->addAppSpecificData('acme', ['acme data'])
            ->addAppSpecificData('acme2', ['acme2 data'])
            ->background(false)
            ->alert(
                (new AlertOptions())
                    ->title('title string')
                    ->body('body string')
                    ->localizedTitleKey('TITLE_KEY')
                    ->localizedTitleArgs(['title arg1', 'title arg2'])
                    ->localizedActionKey('ACTION_KEY')
                    ->localizedMessageKey('MESSAGE_KEY')
                    ->localizedMessageArgs(['message arg1', 'message arg2'])
            )
        ;
        
        $this->assertSame($apnsOptions->payload(), [
            'acme' => ['acme data'],
            'acme2' => ['acme2 data'],
            'aps' => [
                'alert' => [
                    'title' => 'title string',
                    'body' => 'body string',
                    'title-loc-key' => 'TITLE_KEY',
                    'title-loc-args' => ['title arg1', 'title arg2'],
                    'action-loc-key' => 'ACTION_KEY',
                    'loc-key' => 'MESSAGE_KEY',
                    'loc-args' => ['message arg1', 'message arg2'],
                ],
                'sound' => 'sound.aff',
                'badge' => 3,
                'thread-id' => 'thread-id-1234',
            ],
        ]);

        $apnsOptions = (new ApnsOptions('recipient'))
            ->sound('sound.aff')
            ->badge(3)
            ->threadId('thread-id-1234')
            ->addAppSpecificData('acme', ['acme data'])
            ->addAppSpecificData('acme2', ['acme2 data'])
            ->background(true)
            ->alert(
                (new AlertOptions())
                    ->title('title string')
                    ->body('body string')
                    ->localizedTitleKey('TITLE_KEY')
                    ->localizedTitleArgs(['title arg1', 'title arg2'])
                    ->localizedActionKey('ACTION_KEY')
                    ->localizedMessageKey('MESSAGE_KEY')
                    ->localizedMessageArgs(['message arg1', 'message arg2'])
            )
        ;
        
        $this->assertSame($apnsOptions->payload(), [
            'acme' => ['acme data'],
            'acme2' => ['acme2 data'],
            'aps' => [
                'content-available' => 1
            ],
        ]);
    }
    
    public function testTypeHeader()
    {
        $apnsOptions = (new ApnsOptions('recipient'));
        
        $this->assertSame($apnsOptions->toArray(), [
            'headers' => [
                'apns-push-type' => 'alert'
            ],
            'payload' => [
                'aps' => [
                    'alert' => ''
                ],
            ],
        ]);
        
        $apnsOptions = (new ApnsOptions('recipient'))
            ->type(ApnsOptions::PUSH_BACKGROUND_TYPE);
        
        $this->assertSame($apnsOptions->toArray(), [
            'headers' => [
                'apns-push-type' => 'background'
            ],
            'payload' => [
                'aps' => [
                    'alert' => ''
                ],
            ],
        ]);
    }
    
    public function testIdHeader()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->id('1234bhcid');
        
        $this->assertSame($apnsOptions->toArray(), [
            'headers' => [
                'apns-push-type' => 'alert',
                'apns-id' => '1234bhcid',
            ],
            'payload' => [
                'aps' => [
                    'alert' => ''
                ],
            ],
        ]);
    }
    
    public function testExpirationHeader()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->expiration(new \DateTime('2020-05-06 12:13:14 +2000'));
        
        $this->assertSame($apnsOptions->toArray(), [
            'headers' => [
                'apns-push-type' => 'alert',
                'apns-expiration' => '1588695194',
            ],
            'payload' => [
                'aps' => [
                    'alert' => ''
                ],
            ],
        ]);
    }
    
    public function testPriorityHeader()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->priority(ApnsOptions::PUSH_LOW_PRIORITY);
        
        $this->assertSame($apnsOptions->toArray(), [
            'headers' => [
                'apns-push-type' => 'alert',
                'apns-priority' => 5,
            ],
            'payload' => [
                'aps' => [
                    'alert' => ''
                ],
            ],
        ]);
        
        $apnsOptions = (new ApnsOptions('recipient'))
            ->priority(ApnsOptions::PUSH_IMMEDIATELY_PRIORITY);
        
        $this->assertSame($apnsOptions->toArray(), [
            'headers' => [
                'apns-push-type' => 'alert',
                'apns-priority' => 10,
            ],
            'payload' => [
                'aps' => [
                    'alert' => ''
                ],
            ],
        ]);
    }
    
    public function testTopicHeader()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->topic('<app.bundle.id>');
        
        $this->assertSame($apnsOptions->toArray(), [
            'headers' => [
                'apns-push-type' => 'alert',
                'apns-topic' => '<app.bundle.id>',
            ],
            'payload' => [
                'aps' => [
                    'alert' => ''
                ],
            ],
        ]);
    }
    
    public function testCollapseIdHeader()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->collapseId('collapseid1234');
        
        $this->assertSame($apnsOptions->toArray(), [
            'headers' => [
                'apns-push-type' => 'alert',
                'apns-collapse-id' => 'collapseid1234',
            ],
            'payload' => [
                'aps' => [
                    'alert' => ''
                ],
            ],
        ]);
    }
    
    public function testAllHeaders()
    {
        $apnsOptions = (new ApnsOptions('recipient'))
            ->type(ApnsOptions::PUSH_BACKGROUND_TYPE)
            ->id('1234bhcid')
            ->expiration(new \DateTime('2020-05-06 12:13:14 +2000'))
            ->priority(ApnsOptions::PUSH_IMMEDIATELY_PRIORITY)
            ->topic('<app.bundle.id>')
            ->collapseId('collapseid1234');
        
        $this->assertSame($apnsOptions->toArray(), [
            'headers' => [
                'apns-push-type' => 'background',
                'apns-id' => '1234bhcid',
                'apns-expiration' => '1588695194',
                'apns-priority' => 10,
                'apns-topic' => '<app.bundle.id>',
                'apns-collapse-id' => 'collapseid1234',
            ],
            'payload' => [
                'aps' => [
                    'alert' => ''
                ],
            ],
        ]);
    }
}
