# vikunja-api
Vibe-coded library to hit a Vikunja instance; currently only supports `/projects/{id}/views/{view}/tasks`

## This is pre-1.0 and is very broken; don't even bother.

Example CLI script
```
require_once "vendor/autoload.php";

use Vikunja\VikunjaClient;
use Vikunja\Config;

if (!isset($argv[1]) || $argv[1] == '' || !isset($argv[2]) || $argv[2] == '') {
    die('Usage: '.$argv[0].' [project ID] [view ID]'.PHP_EOL);
} else {
    $projectId = $argv[1];
    $viewId = $argv[2];
}

$vkConfig = new Config($vikunjaEndpoint, $vikunjaKey);
$client = new VikunjaClient($vkConfig);

$tasks = $client->tasks()->forView($projectId, $viewId);

print_r($tasks);


