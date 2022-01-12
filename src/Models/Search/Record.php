<?php
namespace Crumbls\ReColorado\Models\Search;

use Crumbls\Egent\Core\Models\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rinvex\Addresses\Traits\Addressable;

class Record extends Model implements \ArrayAccess
{
    protected $resource;
    protected $class;
    protected $fields = [];
    protected $restricted_value = '****';
    protected $values = [];

    /**
     * @param $field
     * @return bool
     */
    public function isRestricted($field)
    {
        $val = $this->get($field);
        return ($val == $this->restricted_value);
    }

    /**
     * @param Results $results
     * @return $this
     */
    public function setParent(Results $results)
    {
        $this->resource = $results->getResource();
        $this->class = $results->getClass();

        /**
         * TODO: Improve this.  It's ugly, but works for now.  It casts the response to a model.
         * See note in Results.php
         */
        dd($this);
        dd(\Config::get('recolorado.model_map.'.$this->class, Results::class));
        echo $this->class;exit;
        $model = '\\App\\Models\\'.$this->class;
        echo $model;
        exit;
        if (class_exists($model)) {
            $model = with(new $model);
            $this->table = $model->getTable();
        } else {
            $this->table = \Str::plural(strtolower($this->class));
        }

        $this->restricted_value = $results->getRestrictedIndicator();
        $this->fields = $results->getHeaders();
        return $this;
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

}
