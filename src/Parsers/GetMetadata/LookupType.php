<?php namespace Crumbls\ReColorado\Parsers\GetMetadata;

use Crumbls\ReColorado\Http\Response;
use Illuminate\Support\Collection;
use Crumbls\ReColorado\Session;

class LookupType extends Base
{
    public function parse(Session $rets, Response $response)
    {
        /** @var \Crumbls\ReColorado\Parsers\XML $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide(\Crumbls\ReColorado\Strategies\Strategy::PARSER_XML);
        $xml = $parser->parse($response);

        $collection = new Collection;

        if ($xml->METADATA) {

            // some servers don't name this correctly for the version of RETS used, so play nice with either way
            if (!empty($xml->METADATA->{'METADATA-LOOKUP_TYPE'}->LookupType)) {
                $base = $xml->METADATA->{'METADATA-LOOKUP_TYPE'}->LookupType;
            } else {
                $base = $xml->METADATA->{'METADATA-LOOKUP_TYPE'}->Lookup;
            }

            foreach ($base as $key => $value) {
                $metadata = new \Crumbls\ReColorado\Models\Metadata\LookupType;
                $metadata->setSession($rets);
                $collection->push($this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-LOOKUP_TYPE'}));
            }
        }

        return $collection;
    }
}
