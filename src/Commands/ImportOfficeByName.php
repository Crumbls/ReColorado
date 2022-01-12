<?php

namespace Crumbls\ReColorado\Commands;

use Crumbls\Egent\Core\Models\Brokerage;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class ImportOfficeByName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recolorado:import:office-name {name?}';

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

        $officeName = $this->argument('name');

        if (!$officeName) {
            throw new \Exception('No office mls ID found.');
        }

        $officeName = preg_replace('/[^A-Za-z0-9\-]/', ' ', $officeName);
        $officeName = explode(' ', $officeName);
        $officeName = array_filter(array_unique($officeName));
        $officeName = array_map(function ($e) {
            return sprintf('(OfficeName=*%s*)', $e);
        }, $officeName);
        $query = implode(' AND ', $officeName);

        $rets = \ReColorado::getClient();
        $data = $rets->Search('Office', 'Office', $query);//, ['Limit' => 20]);

        if (!$data->count()) {
            throw new \Exception('Office not found.');
        }

        foreach ($data as $row) {
            $this->importOffice($row);
        }
    }

    private function importOffice($data) : void {

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
            $this->info('Created brokerage: '.$brokerage->getKey());
        }

        /**
         * TODO: Save addresses in system.
         */
    }
}
