<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class UserCall extends QuickCallTestCase {
    public function main($sip_uri) {
        self::$admin_user->getUser()->quickcall(self::A_EXT, ['auto-answer' => 'true']);
        $ch_a = self::ensureChannel( self::$admin_device->waitForInbound() );

        $this->assertEquals('true', $a_channel->getAutoAnswerDetected());
        $ch_a->answer();
        self::ensureEvent($ch_a->waitAnswer());

        $ch_b = self::ensureChannel( self::$a_device->waitForInbound() );
        $ch_b->answer();
        self::ensureEvent($ch_b->waitAnswer());

        self::hangupBridged($ch_a, $ch_b);
    }
}