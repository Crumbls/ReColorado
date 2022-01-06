<?php

namespace Crumbls\ReColorado\Commands;

use App\Models\Brokerage;
use App\Models\User;
use Crumbls\ReColorado\Configuration;
use Crumbls\ReColorado\Session;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class ImportOffice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recolorado:import:office {officeMlsId?}';

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

        $officeMlsId = $this->argument('officeMlsId');
        if (!$officeMlsId) {
            /**
             * We need to figure out
             * There is a better way to do this, but it works for now.
             * Honestly, it will be extremely inefficient down the road.  So, rethink it.
             * Our problem is that the current version of mysql doesn't like not in queries based on a json field.
             */
            $table = with(new Brokerage())->getTable();

            for ($i = 0; $i < 10; $i++) {
                $random = User::select('extended->office_mls_id as office_mls_id')
                    ->whereNotNull('extended->office_mls_id')
                    ->inRandomOrder()
                    ->take(20)
                    ->get()
                    ->pluck('office_mls_id');

                if ($random->isEmpty()) {
                    break;
                }
                $exclude = \DB::table($table)->whereIn('mls_id', $random)->get()->pluck('mls_id');
                $random = $random->diff($exclude);
                if ($random->count()) {
                    $officeMlsId = $random->random();
                    break;
                }
            }
        }

        if (!$officeMlsId) {
            throw new \Exception('No office mls ID found.');
        }

        $data = \ReColorado::getOfficeByMlsId($officeMlsId);

        if (!$data) {
            throw new \Exception('Office not found.');
        }

        $schema = \DB::connection()->select('explain `'.$data->getTable().'`');
        $schema = array_map(function($e) { return array_change_key_case((array)$e, CASE_LOWER); }, array_column($schema, null, 'Field'));

        unset($schema['extended']);
        $schema = array_keys($schema);

        $brokerage = Brokerage::where('mls_id', $data->mls_id)->take(1)->first();
        if ($brokerage) {
            $temp = $data->toArray();

            $extended = (array)$brokerage->extended;

            foreach($temp as $k => $v) {
                if (!in_array($k, $schema)) {
                    $extended[$k] = $v;
                } else {
                    $brokerage->$k = $v;
                }
            }

            /**
             * The extended field will be deprecated soon, we are just using it as a
             * place to hold temporary data before we parse it to where it should be.
             */
            $brokerage->extended = $extended;

            $brokerage->save();

            $this->info('Updated brokerage: '.$brokerage->getKey());
        } else {
            $extended = (array)$data->extended;
            foreach($data->toArray() as $k => $v) {
                if (!in_array($k, $schema)) {
                    $extended[$k] = $v;
                    unset($data->$k);
                }
            }

            $data->extended = $extended;
            $data->save();
            $brokerage = $data;
            $this->info('Created broekrage: '.$brokerage->getKey());
        }

        /**
         * TODO: Save addresses in system.
         */
    }
}
