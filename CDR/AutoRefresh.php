<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class AutoRefresh extends CdrTestCase {

    public function setUpTest() {
        $this->setStale(true);
        $this->setRefreshThreshold(1);
        $this->setRefreshTimeout(60);
    }

    public function main($sip_uri) {

        $this->makeRecord($sip_uri);

        $re1 = self::$account->getAccount()->Cdr("interaction")->toJson();
        self::assertEquals("{}", $re1); // returns empty stdClass if no records

        $this->makeRecord($sip_uri);
        sleep(3); 

        $re2 = self::$account->getAccount()->Cdr("interaction")->read();
        self::assertNotEmpty($re2[0]); // returns array if there are records
    }

}