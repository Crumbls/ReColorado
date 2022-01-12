<?php

namespace Crumbls\ReColorado\Commands;

use Crumbls\Egent\Core\Models\User;
use Crumbls\ReColorado\Configuration;
use Crumbls\ReColorado\Session;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class ImportAgent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recolorado:import:agent {agentId?}';

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

        $agentId = $this->argument('agentId');
        if (!$agentId) {
            /**
             * There is a better way to do this, but it works for now.
             */
            $agentId = \DB::table('properties')
                ->select('list_agent_mls_id as agentId')
                ->union(
                    \DB::table('properties')->select('buyer_agent_mls_id as agentId')
                )
                ->union(
                    \DB::table('properties')->select('co_buyer_agent_mls_id as agentId')
                )
                ->inRandomOrder()
                ->distinct()
                ->pluck('agentId');

                if ($agentId->count()) {
                    foreach($agentId->chunk(10) as $chunk) {
                        $chunk->push('023942');
                        $remove = User::whereIn('extended->mls_id', $chunk)->get();
                        if ($remove->count()) {
                            $clean = $remove->map(function($e) { return $e->extended['mls_id']; });
                            $chunk = $chunk->diff($clean);
                        }
                        if ($chunk->count()) {
                            $agentId = $chunk->random();
                            break;
                        }
                    }
                }
                if (is_object($agentId)) {
                    throw new \Exception('Unable to find a needed mls id.');
                }


        }

        $data = \ReColorado::getAgentByMlsId($agentId);
        if (!$data) {
            throw new \Exception('Agent not found.');
        }

        $schema = \DB::connection()->select('explain `'.$data->getTable().'`');
        $schema = array_map(function($e) { return array_change_key_case((array)$e, CASE_LOWER); }, array_column($schema, null, 'Field'));

        unset($schema['extended']);
        $schema = array_keys($schema);

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
            $user = $data;
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
