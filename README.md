# vikunja-api
Vibe-coded library to hit a Vikunja instance; currently only supports `/projects/{id}/views/{view}/tasks`

Example CLI script that I generated with my brain and haven't run so it may be flawed. Hopefully you get the gist:
```
require_once "vendor/autoload.php";

use Kjgcoop\Vikunja\VikunjaClient;

if (!isset($argv[1]) || $argv[1] == '' || !isset($argv[2]) || $argv[2] == '') {
    die('Usage: '.$argv[0].' [project ID] [view ID]'.PHP_EOL);
} else {
    $projectId = $argv[1];
    $viewId = $argv[2];
}

$client = new VikunjaClient($config['vikunja_endpoint'], $config['vikunja_key']);

$buckets = $client->tasks()->forView($projectId, $viewId);

print_r($buckets); 
```

