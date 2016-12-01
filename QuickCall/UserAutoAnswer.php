<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class UserAutoAnswer extends QuickCallTestCase {
    public function main($sip_uri) {
        self::$admin_user->getUser()->quickcall(self::A_EXT);
        $channel_a = self::ensureChannel( self::$admin_device->waitForInbound() );
        $channel_a->answer();
        self::ensureEvent($channel_a->waitAnswer());

        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        $channel_b->answer();
        self::ensureEvent($channel_b->waitAnswer());

        self::hangupBridged($channel_a, $channel_b);
    }
}