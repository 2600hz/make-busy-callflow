<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class Stale extends CdrTestCase {

    public function setUpTest() {
        $this->setStale(true);
    }

    public function main($sip_uri) {
        $target = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b);
        self::ensureEvent($channel_a->waitPark());
        sleep(5);
        self::hangupBridged($channel_a, $channel_b);

        $re1 = self::$account->getAccount()->Cdr("interaction")->toJson();
        self::assertEquals("{}", $re1);
        
        $this->setStale(false);
        $re2 = self::$account->getAccount()->Cdr("interaction")->read();
        self::assertNotEmpty($re2[0]);
    }

}