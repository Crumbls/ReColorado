<?php namespace Crumbls\ReColorado\Models\Metadata;

/**
 * Class System
 * @package Crumbls\ReColorado\Models\Metadata
 * @method string getSystemID
 * @method string getSystemDescription
 * @method string getTimeZoneOffset
 * @method string getComments
 * @method string getVersion
 */
class System extends Base
{
    protected $elements = [
        'SystemID',
        'SystemDescription',
        'TimeZoneOffset',
        'Comments',
        'Version',
    ];

    /**
     * @return \Illuminate\Support\Collection|\Crumbls\ReColorado\Models\Metadata\Resource[]
     * @throws \Crumbls\ReColorado\Exceptions\MetadataNotFound
     */
    public function getResources()
    {
        return $this->getSession()->GetResourcesMetadata();
    }
}
