<?php namespace Crumbls\ReColorado\Parsers\GetMetadata;

use Crumbls\ReColorado\Http\Response;
use Illuminate\Support\Collection;
use Crumbls\ReColorado\Session;

class ResourceClass extends Base
{
    public function parse(Session $rets, Response $response)
    {
        /** @var \Crumbls\ReColorado\Parsers\XML $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide(\Crumbls\ReColorado\Strategies\Strategy::PARSER_XML);
        $xml = $parser->parse($response);

        $collection = new Collection;

        if ($xml->METADATA) {
            foreach ($xml->METADATA->{'METADATA-CLASS'}->Class as $key => $value) {
                $metadata = new \Crumbls\ReColorado\Models\Metadata\ResourceClass;
                $metadata->setSession($rets);
                /** @var \Crumbls\ReColorado\Models\Metadata\ResourceClass $obj */
                $obj = $this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-CLASS'});
                $collection->put($obj->getClassName(), $obj);
            }
        }

        return $collection;
    }
}
