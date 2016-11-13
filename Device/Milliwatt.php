<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class BasicCall extends DeviceTestCase {

    public function main($sip_uri) {
        $target = self::MILLIWATT_NUMBER . '@' . $sip_uri;
        $channel = self::$no_device->originate($target);
        $this->hangupChannels($channel);
   }

}