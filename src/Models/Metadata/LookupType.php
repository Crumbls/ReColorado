<?php namespace Crumbls\ReColorado\Models\Metadata;

/**
 * Class LookupType
 * @package Crumbls\ReColorado\Models\Metadata
 *
 * @method string getValue
 * @method string getLongValue
 * @method string getShortValue
 * @method string getMetadataEntryID
 * @method string getVersion
 * @method string getDate
 * @method string getResource
 * @method string getLookup
 */
class LookupType extends Base
{
    protected $elements = [
        'MetadataEntryID',
        'LongValue',
        'ShortValue',
        'Value',
    ];
    protected $attributes = [
        'Version',
        'Date',
        'Resource',
        'Lookup',
    ];
}
