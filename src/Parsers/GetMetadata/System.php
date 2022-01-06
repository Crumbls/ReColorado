<?php namespace Crumbls\ReColorado\Parsers\GetMetadata;

use Crumbls\ReColorado\Http\Response;
use Crumbls\ReColorado\Session;

class System extends Base
{
    public function parse(Session $rets, Response $response)
    {
        /** @var \Crumbls\ReColorado\Parsers\XML $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide(\Crumbls\ReColorado\Strategies\Strategy::PARSER_XML);
        $xml = $parser->parse($response);

        $base = $xml->METADATA->{'METADATA-SYSTEM'};

        $metadata = new \Crumbls\ReColorado\Models\Metadata\System;
        $metadata->setSession($rets);

        $configuration = $rets->getConfiguration();

        if ($configuration->getRetsVersion()->is1_5()) {
            if (isset($base->System->SystemID)) {
                $metadata->setSystemId((string)$base->System->SystemID);
            }
            if (isset($base->System->SystemDescription)) {
                $metadata->setSystemDescription((string)$base->System->SystemDescription);
            }
        } else {
            if (isset($base->SYSTEM->attributes()->SystemID)) {
                $metadata->setSystemId((string)$base->SYSTEM->attributes()->SystemID);
            }
            if (isset($base->SYSTEM->attributes()->SystemDescription)) {
                $metadata->setSystemDescription((string)$base->SYSTEM->attributes()->SystemDescription);
            }
            if (isset($base->SYSTEM->attributes()->TimeZoneOffset)) {
                $metadata->setTimezoneOffset((string)$base->SYSTEM->attributes()->TimeZoneOffset);
            }
        }

        if (isset($base->SYSTEM->Comments)) {
            $metadata->setComments((string)$base->SYSTEM->Comments);
        }
        if (isset($base->attributes()->Version)) {
            $metadata->setVersion((string)$xml->METADATA->{'METADATA-SYSTEM'}->attributes()->Version);
        }

        return $metadata;
    }
}
