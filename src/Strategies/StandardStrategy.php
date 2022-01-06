<?php namespace Crumbls\ReColorado\Strategies;

use Illuminate\Container\Container;
use Crumbls\ReColorado\Configuration;

class StandardStrategy implements Strategy
{
    /**
     * Default components
     *
     * @var array
     */
    protected $default_components = [
        Strategy::PARSER_LOGIN => \Crumbls\ReColorado\Parsers\Login\OneFive::class,
        Strategy::PARSER_OBJECT_SINGLE => \Crumbls\ReColorado\Parsers\GetObject\Single::class,
        Strategy::PARSER_OBJECT_MULTIPLE => \Crumbls\ReColorado\Parsers\GetObject\Multiple::class,
        Strategy::PARSER_SEARCH => \Crumbls\ReColorado\Parsers\Search\OneX::class,
        Strategy::PARSER_SEARCH_RECURSIVE => \Crumbls\ReColorado\Parsers\Search\RecursiveOneX::class,
        Strategy::PARSER_METADATA_SYSTEM => \Crumbls\ReColorado\Parsers\GetMetadata\System::class,
        Strategy::PARSER_METADATA_RESOURCE => \Crumbls\ReColorado\Parsers\GetMetadata\Resource::class,
        Strategy::PARSER_METADATA_CLASS => \Crumbls\ReColorado\Parsers\GetMetadata\ResourceClass::class,
        Strategy::PARSER_METADATA_TABLE => \Crumbls\ReColorado\Parsers\GetMetadata\Table::class,
        Strategy::PARSER_METADATA_OBJECT => \Crumbls\ReColorado\Parsers\GetMetadata\BaseObject::class,
        Strategy::PARSER_METADATA_LOOKUPTYPE => \Crumbls\ReColorado\Parsers\GetMetadata\LookupType::class,
        Strategy::PARSER_XML => \Crumbls\ReColorado\Parsers\XML::class,
    ];

    /**
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * @param $component
     * @return mixed
     */
    public function provide($component)
    {
        return $this->container->make($component);
    }

    /**
     * @param Configuration $configuration
     * @return void
     */
    public function initialize(Configuration $configuration)
    {
        // start up the service locator
        $this->container = new Container;

        foreach ($this->default_components as $k => $v) {
            if ($k == 'parser.login' and $configuration->getRetsVersion()->isAtLeast1_8()) {
                $v = \Crumbls\ReColorado\Parsers\Login\OneEight::class;
            }

            $this->container->singleton($k, function () use ($v) { return new $v; });
        }
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}
