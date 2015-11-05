<?php

/**
 * Copy of benchmark.php without logic to download the files
 */

// General stuff
ini_set('max_execution_time', 300);

error_reporting(E_ALL ^ E_STRICT);

require('helper.php');

$benchmarkSize = empty($_GET['size']) ? 50 : intval($_GET['size']);


layout_header();

$memory = [];
$log = [
    'mem' => [],
    'memInc' => [],
    'times' => []
];
$logNew = $log;

$allStart = microtime(true);

aMem('start');

$idList = randomProfileIds($benchmarkSize);

$graphWidth = 1000;
if ($benchmarkSize > 300) {
    $graphWidth = 2000;
}

/**
 * Version 2 (Current)
 *
 */



// get api
require '../api-autoloader.php';
aMem('require autoloader');

require_once('../src/SearchV3.php');


//use Viion\Lodestone\LodestoneAPI;
aMem('use viion - lodestone - api');

// new API
$api = new Viion\Lodestone\LodestoneAPI();
aMem('api = new api');

// new version
$searchV3 = new Viion\Lodestone\SearchV3();


// ----------------------
// Start
// ----------------------

// run
$success = 0;
$errors = 0;

$chartColor = array(
    2 => '32,124,202',
    3 => '163, 190, 140',
    4 => '208, 135, 112',
);
$runTimes = array(
    2 => array(),
    3 => array(),
    4 => array(),
);
$sumTimes = array(
    2 => 0,
    3 => 0,
    4 => 0,
);

aMem('before benchmark');

foreach($idList as $i => $id)
{
    $id = str_replace('./profiles/', '', $id);
    $id = str_replace('.html', '', $id);

    $id = intval(trim($id));

    $profileFile = 'profiles/'.$id.'.html';
    if(!is_file($profileFile)){
        echo "<br>File missing:".$profileFile;
        return false;
    }
    $html = file_get_contents($profileFile);
    $rawHtml = $html;

    /**
     * Current v2
     */
    $start = microtime(true);

    $character = new \Viion\Lodestone\Character();

    # Namesection
    $nameHtml = str_cut($html, '<!-- playname -->', '<!-- //playname -->');
    $character = parseNameV2($character, $nameHtml);
    unset($nameHtml);

    # Gear
    $gearHtml = str_cut($html, 'param_class_info_area', 'chara_content_title mb10');
    $character = parseGearV2($character, $gearHtml);
    unset($gearHtml);

    unset($character);

    // Stats
    $roundTime = round(microtime(true) - $start,16);

    $runTimes[2][$id] = $roundTime;
    $sumTimes[2] += $roundTime;

    $log['mem'][] = cMem(memory_get_usage());
    $log['memInc'][] = cMem(memory_get_usage(true));

    // flush!!
    flush();

    /**
     * New v3
     */
    $start = microtime(true);

    // get character
    $character = new \Viion\Lodestone\Character();
    $nameHtml = str_cut($html, '<!-- playname -->', '<!-- //playname -->');
    $character = parseNameV3($character, $nameHtml);
    unset($nameHtml);

    # Gear
    $gearHtml = str_cut($html, 'param_class_info_area', 'chara_content_title mb10');
    $character = parseGearV2($character, $gearHtml);
    unset($gearHtml);

    unset($character);
    // Stats
    $roundTime = round(microtime(true) - $start,16);
    $runTimes[3][$id] = $roundTime;
    $sumTimes[3] += $roundTime;

    // flush!!
    flush();

    /**
     * New v4 - simpler regex
     */
    $start = microtime(true);

    // get character
    $character = new \Viion\Lodestone\Character();
    $nameHtml = str_cut($html, '<!-- playname -->', '<!-- //playname -->');
    $character = parseNameV4($character, $nameHtml);
    unset($nameHtml);

    # Gear
    $gearHtml = str_cut($html, 'param_class_info_area', 'chara_content_title mb10');
    $character = parseGearV2($character, $gearHtml);
    unset($gearHtml);

    unset($character);

    // Stats
    $roundTime = round(microtime(true) - $start,16);
    $runTimes[4][$id] = $roundTime;
    $sumTimes[4] += $roundTime;

    // flush!!
    flush();
}
aMem('finished benchmark');

$allfinish = microtime(true);
$allduration = ($allfinish - $allStart);
unset($api);

aMem('end');

// ----------------------
// fin
// ----------------------

show('Parsed '. $benchmarkSize .' characters');
show('There were: '. $success .' successful parses');
show('there were: '. $errors .' failed parses');
show('Start: '. $allStart);
show('Finish: '. $allfinish);
show('Duration: '. $allduration);

$v2Sum = array_sum($runTimes[2]);
foreach($runTimes as $version => $timesArray){
    $sum = array_sum($timesArray);
    $percent = round((1-($sum/$v2Sum))*100,2);
    show('Sum V'.$version.': '.array_sum($timesArray).' => '.$percent.'%');
}

    // ----------------------
    // New API
    // ----------------------

    $timeslabels = '"'. implode('", "', array_keys($log['times'])) .'"';
    $timesdata = implode(',', ($log['times']));
    $memlabels = '"'. implode('", "', array_keys($log['mem'])) .'"';
    $memdata = implode(',', ($log['mem']));
    $memInclabels = '"'. implode('", "', array_keys($log['memInc'])) .'"';
    $memIncdata = implode(',', ($log['memInc']));



// ----------------------
// Graphs
// ----------------------

?>
    <div>
        <h1>Times</h1>
        <canvas id="times" width="<?=$graphWidth;?>px" height="300"></canvas>
        <script>
            var data = {
                labels: [<?=implode(",",array_keys($runTimes[2]))?>],
                datasets: [
                    <?php foreach($runTimes as $version => $timesArray){ ?>
                    {
                        label: "Times v<?=$version?>",
                        fillColor: "rgba(<?=$chartColor[$version]?>,0.2)",
                        strokeColor: "rgba(<?=$chartColor[$version]?>,1)",
                        pointColor: "rgba(<?=$chartColor[$version]?>,1)",
                        pointStrokeColor: "#fff",
                        pointHighlightFill: "#fff",
                        pointHighlightStroke: "rgba(<?=$chartColor[$version]?>,1)",
                        data: [<?=implode(",",$timesArray)?>]
                    }<?php if($timesArray !== end($runTimes)){ echo ",";} ?>
                    <?php } ?>
                ]
            };

            var ctx = document.getElementById("times").getContext("2d");
            var myLineChart = new Chart(ctx).Line(data);
        </script>
    </div>

    <div>
        <h1>Memory</h1>
        <canvas id="mem" width="<?=$graphWidth;?>px" height="300"></canvas>
        <script>
            var data = {
                labels: [<?=$memlabels?>],
                datasets: [
                    {
                        label: "Memory",
                        fillColor: "rgba(32,124,202,0.2)",
                        strokeColor: "rgba(32,124,202,1)",
                        pointColor: "rgba(32,124,202,1)",
                        pointStrokeColor: "#fff",
                        pointHighlightFill: "#fff",
                        pointHighlightStroke: "rgba(32,124,202,1)",
                        data: [<?=$memdata?>]
                    }
                ]
            };

            var ctx = document.getElementById("mem").getContext("2d");
            var myLineChart = new Chart(ctx).Line(data);
        </script>
    </div>

    <div>
        <h1>Memory Peak</h1>
        <canvas id="memInc" width="<?=$graphWidth;?>px" height="300"></canvas>
        <script>
            var data = {
                labels: [<?=$memInclabels?>],
                datasets: [
                    {
                        label: "Memory",
                        fillColor: "rgba(32,124,202,0.2)",
                        strokeColor: "rgba(32,124,202,1)",
                        pointColor: "rgba(32,124,202,1)",
                        pointStrokeColor: "#fff",
                        pointHighlightFill: "#fff",
                        pointHighlightStroke: "rgba(32,124,202,1)",
                        data: [<?=$memIncdata?>]
                    }
                ]
            };

            var ctx = document.getElementById("memInc").getContext("2d");
            var myLineChart = new Chart(ctx).Line(data);
        </script>
    </div>


<?php
echo '<h1>Global memory usage</h1>';
show($memory);

echo '<h1>Log</h1>';
show($log);
