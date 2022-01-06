<?php

namespace Crumbls\ReColorado\Commands;

use App\Models\Brokerage;
use App\Models\User;
use Crumbls\ReColorado\Configuration;
use Crumbls\ReColorado\Session;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class ImportAgentsByOffice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recolorado:import:agent-by-office {officeId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import an agent.';

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

        $officeId = $this->argument('officeId');

        $office = Brokerage::find($officeId);
        if (!$office) {
            throw new \Exception('Unable to locate office.');
        }

        if (!$office->mls_id) {
            throw new \Exception('Office does not have an MLS ID set.');
        }


        $collection = \ReColorado::getAgentByOfficeByMlsId($office->mls_id);

        if (!$collection) {
            return;
        }

        $table = with(new User())->getTable();

        $schema = \DB::connection()->select('explain `'.$table.'`');
        $schema = array_map(function($e) { return array_change_key_case((array)$e, CASE_LOWER); }, array_column($schema, null, 'Field'));

        unset($schema['extended']);
        $schema = array_keys($schema);

        foreach($collection as $data) {
            $user = User::where('email', $data->email)->take(1)->first();
            if ($user) {
                $temp = $data->toArray();

                $extended = (array)$user->extended;

                foreach($temp as $k => $v) {
                    if (!in_array($k, $schema)) {
                        $extended[$k] = $v;
                    } else {
                        $user->$k = $v;
                    }
                }

                /**
                 * The extended field will be deprecated soon, we are just using it as a
                 * place to hold temporary data before we parse it to where it should be.
                 */
                $user->extended = $extended;

                $user->save();

                $user->brokerages()->attach($office);
                $this->info('Updated user: '.$user->getKey());
            } else {

                $extended = (array)$data->extended;
                foreach($data->toArray() as $k => $v) {
                    if (!in_array($k, $schema)) {
                        $extended[$k] = $v;
                        unset($data->$k);
                    }
                }

                $data->extended = $extended;
                if (!$data->exists) {
                    $data->password = '';
                }
                $data->save();
                $data->brokerages()->attach($office);
                $user = $data;
                $this->info('Created user: '.$user->getKey());
                \App\Events\Auth\RegisteredSystem::dispatch($user);
            }

            /**
             * TODO: Import the extra data.
             */
            if ($user->roles->isEmpty()) {
                $user->assign('agent');
            }
        }


    }
}
