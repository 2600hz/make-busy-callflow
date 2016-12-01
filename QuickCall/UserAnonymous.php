<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Kazoo\SDK;
use \MakeBusy\Common\Log;

class UserAnonymous extends QuickCallTestCase {
    public function main($sip_uri) {
        $url = self::$anon_device->getDevice()->getUri('/quickcall/' . self::A_EXT);
        SDK::getInstance()->getHttpClient()->get($url, [], []);

        $channel_a = self::ensureChannel( self::$anon_device->waitForInbound() );
        $channel_a->answer();
        self::ensureEvent($channel_a->waitAnswer());

        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        $channel_b->answer();
        self::ensureEvent($channel_b->waitAnswer());

        self::hangupBridged($channel_a, $channel_b);
    }
}