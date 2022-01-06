<?php namespace Crumbls\ReColorado\Parsers\GetMetadata;

use Crumbls\ReColorado\Http\Response;
use Illuminate\Support\Collection;
use Crumbls\ReColorado\Session;

class BaseObject extends Base
{
    public function parse(Session $rets, Response $response)
    {
        /** @var \Crumbls\ReColorado\Parsers\XML $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide(\Crumbls\ReColorado\Strategies\Strategy::PARSER_XML);
        $xml = $parser->parse($response);

        $collection = new Collection;

        if ($xml->METADATA) {
            if ($xml->METADATA->{'METADATA-OBJECT'}) {
                foreach ($xml->METADATA->{'METADATA-OBJECT'}->Object as $key => $value) {
                    $metadata = new \Crumbls\ReColorado\Models\Metadata\BaseObject;
                    $metadata->setSession($rets);
                    $obj = $this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-OBJECT'});
                    $collection->put($obj->getObjectType(), $obj);
                }
            }
        }

        return $collection;
    }
}
