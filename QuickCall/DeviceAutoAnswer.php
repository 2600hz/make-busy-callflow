<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class DeviceAutoAnswer extends QuickCallTestCase {
    public function main($sip_uri) {
        self::$admin_device->getDevice()->quickcall(self::A_EXT, ['auto_answer' => 'true']);
        $channel_a = self::ensureChannel( self::$admin_device->waitForInbound() );
        $this->assertEquals('true', $channel_a->getAutoAnswerDetected());
        $channel_a->answer();
        self::ensureAnswered($channel_a);

        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        $channel_b->answer();
        self::ensureAnswered($channel_b);

        self::hangupBridged($channel_a, $channel_b);
    }
}