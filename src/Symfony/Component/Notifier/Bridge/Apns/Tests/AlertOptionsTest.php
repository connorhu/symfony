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
use Symfony\Component\Notifier\Bridge\Apns\AlertOptions;

final class AlertOptionsTest extends TestCase
{
    public function testFullAlertOptions()
    {
        $apnsOptions = (new AlertOptions())
            ->title('title string')
            ->body('body string')
            ->localizedTitleKey('TITLE_KEY')
            ->localizedTitleArgs(['title arg1', 'title arg2'])
            ->localizedActionKey('ACTION_KEY')
            ->localizedMessageKey('MESSAGE_KEY')
            ->localizedMessageArgs(['message arg1', 'message arg2'])
            ;

        $this->assertSame($apnsOptions->toArray(), [
            'title' => 'title string',
            'body' => 'body string',
            'title-loc-key' => 'TITLE_KEY',
            'title-loc-args' => ['title arg1', 'title arg2'],
            'action-loc-key' => 'ACTION_KEY',
            'loc-key' => 'MESSAGE_KEY',
            'loc-args' => ['message arg1', 'message arg2'],
        ]);
    }
}
