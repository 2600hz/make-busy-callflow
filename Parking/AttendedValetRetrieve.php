<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-74: Attended transfer, using both park (*4) and retrieve (*5)
class AttendedValetRetrieve extends ParkingTestCase {

    public function main($sip_uri) {
        $target = self::B_EXT . '@' . $sip_uri;
        $referred_by = sprintf("<sip:%s@%s:5060;transport=udp>", self::$b_device->getSipUsername(), self::getEsl("auth")->getIpAddress());
        $valet = self::VALET . '@' . $sip_uri;
        //TODO: Get valet spot from prompt. We are hard coding valet spots in sequence.
        $retrieve = self::RETRIEVE . '101@' . $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );

        $ch_b->answer();
        self::assertEquals($ch_b->getChannelCallState(), "ACTIVE");
        $ch_b->onHold();
        $this->assertEquals($ch_b->getChannelCallState(), "HELD");

        $ch_b_2 = self::ensureChannel( self::$b_device->originate($valet) );
        $event = $ch_b_2->waitAnswer();

        $to_tag = $event->getHeader('variable_sip_to_tag');
        $from_tag = $event->getHeader('variable_sip_from_tag');
        $sip_uri = urldecode($event->getHeader('variable_sip_req_uri'));
        $call_uuid = $event->getHeader('variable_call_uuid');

        $refer_to =     '<sip:' . $sip_uri . '101'
                 . '?Replaces=' . $call_uuid
               . '%3Bto-tag%3D' . $to_tag
             . '%3Bfrom-tag%3D' . $from_tag
             . '>';

        $ch_b->setVariables('sip_h_refer-to', $refer_to);
        $ch_b->setVariables('sip_h_referred-by', $referred_by);
        $ch_b->deflect($refer_to);
        $ch_b->waitDestroy();

        $ch_c = self::ensureChannel( self::$c_device->originate($retrieve) );

        self::ensureTalking($ch_a, $ch_c);
        self::hangupBridged($ch_a, $ch_c);
    }

}