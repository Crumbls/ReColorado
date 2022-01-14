<?php namespace Crumbls\ReColorado\Parsers\Search;

use Crumbls\ReColorado\Http\Response;
use Crumbls\ReColorado\Models\Search\Record;
use Crumbls\ReColorado\Models\Search\Results;
use Crumbls\ReColorado\Session;
use Crumbls\ReColorado\Strategies\Strategy;

class OneX
{
    public function parse(Session $rets, Response $response, $parameters)
    {
        /** @var \Crumbls\ReColorado\Parsers\XML $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide(Strategy::PARSER_XML);
        $xml = $parser->parse($response);

        $rs = new Results;
        $rs->setSession($rets)
            ->setResource($parameters['SearchType'])
            ->setClass($parameters['Class']);

        if ($this->getRestrictedIndicator($rets, $xml, $parameters)) {
            $rs->setRestrictedIndicator($this->getRestrictedIndicator($rets, $xml, $parameters));
        }

        $rs->setHeaders($this->getColumnNames($rets, $xml, $parameters));
        $rets->debug(count($rs->getHeaders()) . ' column headers/fields given');

        $this->parseRecords($rets, $xml, $parameters, $rs);

        if ($this->getTotalCount($rets, $xml, $parameters) !== null) {
            $rs->setTotalResultsCount($this->getTotalCount($rets, $xml, $parameters));
            $rets->debug($rs->getTotalResultsCount() . ' total results found');
        }
        $rets->debug($rs->getReturnedResultsCount() . ' results given');

        if ($this->foundMaxRows($rets, $xml, $parameters)) {
            // MAXROWS tag found.  the RETS server withheld records.
            // if the server supports Offset, more requests can be sent to page through results
            // until this tag isn't found anymore.
            $rs->setMaxRowsReached();
            $rets->debug('Maximum rows returned in response');
        }

        unset($xml);

        return $rs;
    }

    /**
     * @param Session $rets
     * @param $xml
     * @param $parameters
     * @return string
     */
    protected function getDelimiter(Session $rets, $xml, $parameters)
    {
        if (isset($xml->DELIMITER)) {
            // delimiter found so we have at least a COLUMNS row to parse
            return chr("{$xml->DELIMITER->attributes()->value}");
        } else {
            // assume tab delimited since it wasn't given
            $rets->debug('Assuming TAB delimiter since none specified in response');
            return chr("09");
        }
    }

    /**
     * @param Session $rets
     * @param $xml
     * @param $parameters
     * @return string|null
     */
    protected function getRestrictedIndicator(Session $rets, &$xml, $parameters)
    {
        if (array_key_exists('RestrictedIndicator', $parameters)) {
            return $parameters['RestrictedIndicator'];
        } else {
            return null;
        }
    }

    protected function getColumnNames(Session $rets, &$xml, $parameters)
    {
        $delim = $this->getDelimiter($rets, $xml, $parameters);
        $delimLength = strlen($delim);

        // break out and track the column names in the response
        $column_names = "{$xml->COLUMNS[0]}";

        // Take out the first delimiter
        if (substr($column_names, 0, $delimLength) == $delim) {
            $column_names = substr($column_names, $delimLength);
        }

        // Take out the last delimiter
        if (substr($column_names, -$delimLength) == $delim) {
            $column_names = substr($column_names, 0, -$delimLength);
        }

        // parse and return the rest
        return explode($delim, $column_names);
    }

    protected function parseRecords(Session $rets, &$xml, $parameters, Results $rs)
    {
        if (isset($xml->DATA)) {
            foreach ($xml->DATA as $line) {
                $rs->addRecord($this->parseRecordFromLine($rets, $xml, $parameters, $line, $rs));
            }
        }
    }

    protected function parseRecordFromLine(Session $rets, &$xml, $parameters, &$line, Results $rs)
    {
        $delim = $this->getDelimiter($rets, $xml, $parameters);
        $delimLength = strlen($delim);

        $originalClass = $rs->getClass();
        $castTo = \Config::get('recolorado.model_map.'.$originalClass, Record::class);

        // Temporary.
        $castTo = Record::class;

        $field_data = (string) $line;

        // Take out the first delimiter
        if (substr($field_data, 0, $delimLength) == $delim) {
            $field_data = substr($field_data, $delimLength);
        }

        // Take out the last delimiter
        if (substr($field_data, -$delimLength) == $delim) {
            $field_data = substr($field_data, 0, -$delimLength);
        }

        $field_data = explode($delim, $field_data);

        $modelKey = \Config::get('recolorado.model_key.'.$originalClass, false);

        // Temporary.
        $modelKey = false;

        $r = null;

        $attributes = array_combine(array_map(function($e) {
            return \Str::snake($e);;
        }, $rs->getHeaders()), $field_data);

        if ($modelKey) {
            if (array_key_exists($modelKey, $attributes) && $attributes[$modelKey]) {
                $keyValue = $attributes[$modelKey];
                $keyName = $modelKey;
                if (method_exists($castTo, 'getMap')) {
                    $temp = with(new $castTo)->getMap();
                    $keyName = array_key_exists($modelKey, $temp) ? $temp[$modelKey] : $modelKey;
                }
                $r = $castTo::where($keyName, $keyValue)->take(1)->first();
            }
        }

        if (!$r) {
            $r = new $castTo();
        }

        foreach($attributes as $key => $value) {
            $r->$key = $value;
        }

        return $r;
    }

    protected function getTotalCount(Session $rets, &$xml, $parameters)
    {
        if (isset($xml->COUNT)) {
            return (int)"{$xml->COUNT->attributes()->Records}";
        } else {
            return null;
        }
    }

    protected function foundMaxRows(Session $rets, &$xml, $parameters)
    {
        return isset($xml->MAXROWS);
    }
}
