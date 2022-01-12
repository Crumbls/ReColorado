<?php

namespace Crumbls\ReColorado\Commands;

use Crumbls\Egent\Core\Models\Brokerage;
use Crumbls\Egent\Core\Models\Property;
use Crumbls\Egent\Core\Models\User;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class PatchOffice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recolorado:patch:office';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import an office.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tableProperties = with(new Property)->getTable();
        $tableBrokerages = with(new Brokerage)->getTable();
        $schema = \DB::connection()->select('explain `'.$tableProperties.'`');
        $schema = array_map(function($e) { return array_change_key_case((array)$e, CASE_LOWER); }, array_column($schema, null, 'Field'));
        $schema = array_keys($schema);

        $reduced = preg_grep('#\_office\_mls\_id#', $schema);

        $tablePivot = 'property_brokerages';


        $brokerages = Brokerage::whereNull('office_key_numeric')
            ->inRandomOrder()
            ->whereNotNull('extended->office_key_numeric')
            ->get();
        if ($brokerages->count()) {
            $this->info('Updating brokerages office keys.');
            foreach($brokerages as $brokerage) {
                $brokerage->office_key_numeric = $brokerage->extended['office_key_numeric'];
                $brokerage->save();
            }
        }


        /**
         * More accurate?
         */

//        properties.list_office_key_numeric

        // properties.buyer_agent_key_numeric

        /**
         * Handle offices
         */
        foreach($reduced as $column) {
            $this->info('Scanning '.$column);

            $alt = substr($column, 0, -6).'id';

            $temp = substr($column, 0, -6).'key_numeric';
            $q = \DB::table($tableProperties)
                ->join($tableBrokerages, $tableBrokerages.'.office_key_numeric','=',$tableProperties.'.'.$temp)
                ->whereRaw($tableProperties.'.'.$temp.' <> ""')
                ->whereNotNull($tableProperties.'.'.$temp)
                ->update([
                    $tableProperties.'.'.$alt => \DB::raw('`'.$tableBrokerages.'`.`id`'),
                    $tableProperties.'.'.$temp => '',
                    $tableProperties.'.'.$column => '',
                ]);

            $alt = substr($column, 0, -6).'id';

            /**
             * Merge with known.
             */
            \DB::table($tableProperties)
                ->join($tableBrokerages, $tableBrokerages.'.mls_id','=',$tableProperties.'.'.$column)
                ->whereRaw($tableProperties.'.'.$column.' <> ""')
                ->whereNotNull($tableProperties.'.'.$column)
                ->update([
                    $tableProperties.'.'.$alt => \DB::raw('`'.$tableBrokerages.'`.`id`'),
                    $tableProperties.'.'.$column => ''
                ]);

            $request = \DB::table($tableProperties)
                ->select($column)
                ->whereNotNull($column)
                ->where($column,'<>','')
                ->whereNull($alt)
                ->inRandomOrder()
                ->distinct()
                ->take(5)
                ->get()
                ->pluck($column);
            foreach($request as $id) {
                try {
                    $this->info('Importing Realtor MLS ID: '.$id);
                    \Artisan::call('recolorado:import:office ' . $id);
                    \DB::table($tableProperties)
                        ->join($tableBrokerages, $tableBrokerages.'.mls_id','=',$tableProperties.'.'.$column)
                        ->whereRaw($tableProperties.'.'.$column.' <> ""')
                        ->whereNotNull($tableProperties.'.'.$column)
                        ->update([
                            $tableProperties.'.'.$alt => \DB::raw('`'.$tableBrokerages.'`.`id`'),
                            $tableProperties.'.'.$column => ''
                        ]);
                } catch (\Throwable $e) {
$this->info($e->getMessage());
                }
            }
        }


        /**
         * Quickly handle user agents.
         */
        $reduced = preg_grep('#\_agent\_key\_numeric#', $schema);
        foreach($reduced as $key) {
            $this->info('Scanning '.$key);
            $alt = str_replace('_key_numeric', '', $key).'_id';
            $this->info($alt);

            $a = \DB::table($tableProperties)
                ->join('users', 'users.extended->key_numeric','=',$key)//\DB::raw("find_in_set(users.id, JSON_UNQUOTE(JSON_EXTRACT(users.extended, '$.key_numeric')))"), \DB::raw(''),\DB::raw(''))
                ->whereNull($key)
                ->orWhereRaw($key.' = ""')
                ->update([$alt => 'users.id'])
                ;

            $a = \DB::table($tableProperties)
                ->join('users','users.id','=',$key)
                ->take(1)
                ->get();

            print_r($a);
        }
        print_r($reduced);exit;
        print_r($schema);exit;
    }
}
