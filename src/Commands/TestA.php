<?php

namespace Crumbls\ReColorado\Commands;

use Crumbls\ReColorado\Configuration;
use Crumbls\ReColorado\Session;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class TestA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rets:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge invalid entities from the activity log.';

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
        $rets = \ReColorado::getSession();

/*
        $system = $rets->GetSystemMetadata();
        var_dump($system);

        $resources = $system->getResources();
        $classes = $resources->first()->getClasses();
        var_dump($classes);
*/
        /*
        $classes = $rets->GetClassesMetadata('Property');
        var_dump($classes->first());
*/
//        $objects = $rets->GetObject('Property', 'Photo', '*', '*', 1);
        $limit = 5;
        $residential = $rets->Search('Property', 'Residential', '(OfficeCoListCode=PLKT01,PLKT02)', ['Limit' => $limit]);
        var_dump($residential);
exit;
        $fields = $rets->GetTableMetadata('Property', 'A');
        var_dump($fields[0]);

        $results = $rets->Search('Property', 'A', '*', ['Limit' => 3, 'Select' => 'LIST_1,LIST_105,LIST_15,LIST_22,LIST_87,LIST_133,LIST_134']);
        foreach ($results as $r) {
            var_dump($r);
        }
        exit;

        $system = $rets->GetSystemMetadata();

        $resources = $system->getResources();
        $classes = $resources->first()->getClasses();

        $temp = $rets->SearchGetFields('a');
        print_r($temp);
exit;
$mlsNumber = 45661;
        //Perform search query for a specific MLS Id
        $search = $rets->Search("Property", "A", "(MLNumber_f139={$mlsNumber})");//, array('Limit' => 1, 'Format' => 'COMPACT'));

        // make the request and get the results
//        $search = $rets->Search('Property', $pc, $query);

        $numRows = $rets->NumRows();
        echo $numRows;
        exit;

        $classes = $rets->GetClassesMetadata('Property');
        foreach($classes as $class) {
            var_dump($class);
        }
//        var_dump($classes->first());
        exit;

        $timestamp_field = 'LIST_87';
        $property_classes = ['A', 'B', 'C'];
print_r(get_class_methods($rets));exit;
        foreach ($property_classes as $pc) {
            // generate the DMQL query
            $query = "({$timestamp_field}=2000-01-01T00:00:00+)";
$query = '';
            // make the request and get the results
            $results = $rets->Search('Property', $pc, $query);

            // save the results in a local file
            print_r($results);
//            file_put_contents('data/Property_' . $pc . '.csv', $results->toCSV());
        }
//        dd($connect);
        exit;
        /**
         *
         */
        $model = with(new Activity);
        $tableActivity = $model->getTable();

        $subjectTypes = \DB::table($tableActivity)
            ->select('subject_type')
            ->where('subject_type','<>','')
            ->whereNotNull('subject_type')
            ->where('subject_id','<>','')
            ->whereNotNull('subject_id')
            ->distinct()
            ->get()
            ->pluck('subject_type');

        foreach($subjectTypes as $subjectType) {
            $model = with(new $subjectType);
            $table = $model->getTable();
            $tableKey = $model->getKeyName();

            try {
                $count = Activity::where('subject_type', $subjectType)
                    ->whereNotIn('subject_id',
                        \DB::table($table)
                            ->select($tableKey)
                    )
                    ->delete();

                if ($count) {
                    $this->info('Reference to ' . $model . ' had ' . $count . ' rows removed.');
                }
            } catch (\Throwable $e) {
                $this->info($e->getMessage());
            }
        }
    }
}
