<?php

require_once __DIR__.'/../init.php';

cli_set_process_title('Usage V1 Worker');

echo APP_NAME.' usage worker v1 has started';

class UsageV1
{
    /**
     * @var array
     */
    public $args = [];

    public function setUp()
    {
    }

    public function perform()
    {
        global $register, $version;

        $projectId = $this->args['projectId'];
        $method = $this->args['method'];
        $request = $this->args['request'];
        $response = $this->args['response'];
        $storage = $this->args['storage'];

        $statsd = $register->get('statsd', true);

        $tags = ",project={$projectId},version=".$version.'';

        // the global namespace is prepended to every key (optional)
        $statsd->setNamespace('appwrite.usage');

        $statsd->increment('requests.all'.$tags.',method='.strtolower($method));

        $statsd->count('network.all'.$tags, $request + $response);
        $statsd->count('network.inbound'.$tags, $request);
        $statsd->count('network.outbound'.$tags, $response);
        $statsd->count('storage.all'.$tags, $storage);
    }

    public function tearDown()
    {
        // ... Remove environment for this job
    }
}
