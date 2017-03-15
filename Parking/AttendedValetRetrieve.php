<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-74: Attended transfer, using both park (*4) and retrieve (*5)
class AttendedValetRetrieve extends ParkingTestCase {

    public function main($sip_uri) {
        $target = self::B_EXT . '@' . $sip_uri;
        $referred_by = self::$b_device->makeReferredByUri();
        $valet = self::VALET . '@' . $sip_uri;

        //TODO: Get valet spot from prompt. We are hard coding valet spots in sequence.
        $retrieve = self::RETRIEVE . '101@' . $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );

        $channel_b->answer();
        self::assertEquals($channel_b->getChannelCallState(), "ACTIVE");
        $channel_b->onHold();
        $this->assertEquals($channel_b->getChannelCallState(), "HELD");

        $channel_b_2 = self::ensureChannel( self::$b_device->originate($valet) );
        $channel_b_2->waitAnswer();
        $channel_b_2->waitPark();

        $channel_b->deflectChannel($channel_b_2, $referred_by);

        $channel_c = self::ensureChannel( self::$c_device->originate($retrieve) );
        $channel_c->waitAnswer();

        self::ensureTalking($channel_a, $channel_c);
        self::hangupBridged($channel_a, $channel_c);
    }

}