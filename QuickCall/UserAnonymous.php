<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Kazoo\SDK;
use \MakeBusy\Common\Log;

class UserAnonymous extends QuickCallTestCase {
    public function main($sip_uri) {
        $url = self::$anon_device->getDevice()->getUri('/quickcall/' . self::A_EXT);
        SDK::getInstance()->getHttpClient()->get($url, [], []);

        $ch_a = self::ensureChannel( self::$anon_device->waitForInbound() );
        $ch_a->answer();
        self::ensureEvent($ch_a->waitAnswer());

        $ch_b = self::ensureChannel( self::$a_device->waitForInbound() );
        $ch_b->answer();
        self::ensureEvent($ch_b->waitAnswer());

        self::hangupBridged($ch_a, $ch_b);
    }
}