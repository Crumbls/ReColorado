<?php namespace Crumbls\ReColorado\Parsers\GetMetadata;

use Crumbls\ReColorado\Http\Response;
use Illuminate\Support\Collection;
use Crumbls\ReColorado\Session;

class Table extends Base
{
    public function parse(Session $rets, Response $response, $keyed_by)
    {
        /** @var \Crumbls\ReColorado\Parsers\XML $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide(\Crumbls\ReColorado\Strategies\Strategy::PARSER_XML);
        $xml = $parser->parse($response);

        $collection = new Collection;

        if ($xml->METADATA) {
            foreach ($xml->METADATA->{'METADATA-TABLE'}->Field as $key => $value) {
                $metadata = new \Crumbls\ReColorado\Models\Metadata\Table;
                $metadata->setSession($rets);
                $this->loadFromXml($metadata, $value, $xml->METADATA->{'METADATA-TABLE'});
                $method = 'get' . $keyed_by;
                $collection->put((string)$metadata->$method(), $metadata);
            }
        }

        return $collection;
    }
}
