<?php namespace Crumbls\ReColorado\Parsers\GetMetadata;

class Base
{
    protected function loadFromXml(\Crumbls\ReColorado\Models\Metadata\Base $model, $xml, $attributes = null)
    {
        foreach ($model->getXmlAttributes() as $attr) {
            if (isset($attributes[$attr])) {
                $method = 'set' . $attr;
                $model->$method((string)$attributes[$attr]);
            }
        }

        foreach ($model->getXmlElements() as $attr) {
            if (isset($xml->$attr)) {
                $method = 'set' . $attr;
                $model->$method((string)$xml->$attr);
            }
        }
        return $model;
    }
}
