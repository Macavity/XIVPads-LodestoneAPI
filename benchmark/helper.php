<?php

function show($data) { echo '<pre>'. print_r($data, true) .'</pre>'; }
function cMem($size, $type = false) {
    $tmp = array('b','kb','mb','gb','tb','pb');
    $v = @round($size/pow(1024,($i=floor(log($size,1024)))),2);
    if ($type) {
        $v .= ' '. $tmp[$i];
    } else {
        if ($tmp[$i] == 'kb') {
            $v = '0.'. ceil($v);
        }
    }

    return $v;
}
function aMem($key) { global $memory; $memory[$key] = [ cMem(memory_get_usage(), true), cMem(memory_get_usage(true), true) ]; }

function layout_header(){
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js"></script>
    <style>
        html, body { font-family: Arial; font-size: 13px; }
        .box { display: inline-block; padding:8px; background-color: #eee; }
        .error { background-color: #f00; color: #fff; }
        canvas { margin: 20px; padding: 20px; border: solid 2px #1285EA; border-radius: 3px; }
    </style>
    <?php
}

function layout_choose_benchmark_version($benchmarkSize){
    ?>
    <h1>Choose Benchmark Version</h1>
    <ul>
        <li><a href="?size=<?php echo $benchmarkSize?>v=1">Version 1 (benchmark_old)</a></li>
        <li><a href="?size=<?php echo $benchmarkSize?>v=2">Version 2 (benchmark)</a></li>
        <li><a href="?size=<?php echo $benchmarkSize?>v=3">Version 3</a></li>
    </ul>
    <?php
}

function randomProfileIds($count = 10){
    $files = glob('./profiles/*.html');

    $max = count($files);

    if($max < $count){
        return $files;
    }

    shuffle($files);

    return array_slice($files, 0, $count);

}

function str_cut($html, $start, $end)
{
    $temp = $html;

    // Start position
    $start  = strpos($temp, $start);

    // cut to start
    $temp   = substr($html, $start);

    // Cut to end
    $end    = strpos($temp, $end) + strlen($end);

    // sub from entire
    $html   = substr($html, $start, $end);

    return $html;
}

function clearRegExpArray(&$array)
{
    $tmp = array();
    foreach($array as $key => $value) {
        if(is_array($value)){
            $tmp[$key] = clearRegExpArray($value);
        }else if(!is_numeric($key) ){
            $tmp[$key] = $value;
        }
    }
    $array = $tmp;
    unset($tmp);
    return $array;
}

function getRegExp($type,$name=""){
    $types = array(
        'image' => '<img.+?src="(?<%1$s>[^\?"]+)(?:\?(?(?=[\d\w]+")(?<%1$sTimestamp>[\d\w^\?"]+)|(?<%1$sQueryString>[^\?"=]+=[^\?"]+?)))?".*?>'
    );
    return sprintf($types[$type],$name);
}

function parseNameV2(\Viion\Lodestone\Character &$character, $nameHtml){
    $nameMatches = array();
    // Build Namesectionpattern
    $namePattern = '<a href=".*?/(?<id>\d+)/">(?<name>[^<]+?)</a>';
    $worldPattern = '<span>\s?\((?<world>[^<]+?)\)\s?</span>';
    $titlePattern = '<div class="chara_title">(?<title>[^<]+?)</div>';
    $avatarPattern = '<div class="player_name_thumb"><a.+?>' . getRegExp('image','avatar') . '</a></div>';

    // Build complete Expression and use condition to identify if title is before or after name
    $nameRegExp = sprintf('#(?J:%4$s<h2>(?:(?=<div)(?:%1$s)?%2$s%3$s|%2$s%3$s(?:%1$s)?)</h2>)#',$titlePattern,$namePattern,$worldPattern,$avatarPattern);

    if(preg_match($nameRegExp, $nameHtml, $nameMatches)){
        $character->id = $nameMatches['id'];
        $character->name = $nameMatches['name'];
        $character->title = $nameMatches['title'];
        $character->world = $nameMatches['world'];
        $character->avatar = $nameMatches['avatar'];
        $character->avatarTimestamp = $nameMatches['avatarTimestamp'];
    }

    unset($nameHtml);
    unset($nameMatches);
    unset($nameRegExp);

    return $character;
}


function parseNameV3(\Viion\Lodestone\Character &$character, $nameHtml){
    $nameMatches = array();

    $nameRegExp = '#'
        .'(?J:'
            .'<div class="player_name_thumb">'
                .'<a.+?>'
                    .'<img.+?src="(?<avatar>[^\?"]+)(?:\?(?(?=[\d\w]+")(?<avatarTimestamp>[\d\w^\?"]+)|(?<avatarQueryString>[^\?"=]+=[^\?"]+?)))?".*?>'
                .'</a>'
            .'</div>'
            .'<h2>'
            .'(?:'
                .'(?=<div)'
                .'(?:<div class="chara_title">(?<title>[^<]+?)</div>)?'
                .'<a href=".*?/(?<id>\d+)/">(?<name>[^<]+?)</a>'
                .'<span>\s?\((?<world>[^<]+?)\)\s?</span>'
                .'|'
                .'<a href=".*?/(?<id>\d+)/">(?<name>[^<]+?)</a>'
                .'<span>\s?\((?<world>[^<]+?)\)\s?</span>'
                .'(?:<div class="chara_title">(?<title>[^<]+?)</div>)?)'
            .'</h2>'
        .')#';

    if(preg_match($nameRegExp, $nameHtml, $nameMatches)){
        $character->id = $nameMatches['id'];
        $character->name = $nameMatches['name'];
        $character->title = $nameMatches['title'];
        $character->world = $nameMatches['world'];
        $character->avatar = $nameMatches['avatar'];
        $character->avatarTimestamp = $nameMatches['avatarTimestamp'];
    }

    unset($nameHtml);
    unset($nameMatches);
    unset($nameRegExp);

    return $character;
}

function parseNameV4(\Viion\Lodestone\Character &$character, $nameHtml){
    $nameMatches = array();

    $nameRegExp = '(?J:<div class="player_name_thumb"><a[^>]+?><img[^>]+?src="([^\?"]+)(?:\?(?(?=[\d\w]+")([\d\w^\?"]+)|([^\?"=]+=[^\?"]+?)))?".*?></a></div><h2>((<div class="chara_title">([^<]+?)</div>)?<a href="[^"]*?/(\d+)/">([^<]+?)</a><span>\s?\(([^<]+?)\)\s?</span>(<div class="chara_title">([^<]+?)</div>)?)</h2>)';

    if(preg_match('#'.$nameRegExp.'#', $nameHtml, $nameMatches)){
        $character->id = $nameMatches[7];
        $character->name = $nameMatches[8];
        $character->world = $nameMatches[9];

        if(!empty($nameMatches[6])){
            $character->title = $nameMatches[6];
        }
        elseif(!empty($nameMatches[11])){
            $character->title = $nameMatches[11];
        }
        $character->avatar = $nameMatches[1];
        $character->avatarTimestamp = $nameMatches[2];
    }

    unset($nameHtml);
    unset($nameMatches);
    unset($nameRegExp);

    return $character;
}

function parseGearV2(\Viion\Lodestone\Character &$character,$gearHtml){
    $itemsMatch = array();
    $gearRegExp = '#<!-- ITEM Detail -->.*?'
        . '<div class="name_area[^>].*?>.*?'
        . '(?:<div class="(?<mirage>mirage)_staining (?<mirageType>unpaitable|painted_cover|no_paint)"(?: style="background\-color:\s?(?<miragePaintColor>\#[a-fA-F0-9]{6});")?></div>)?'
        . '<img[^>]+?>' . getRegExp('image','icon') . '.*?'
        . '<div class="item_name_right">'
        . '<div class="item_element[^"]*?">'
        . '<span class="rare">(?<rare>[^<]*?)</span>'
        . '<span class="ex_bind">\s*(?<binding>[^<]*?)\s*</span></div>'
        . '<h2 class="item_name\s?(?<color>[^_]*?)_item">(?<name>[^<]+?)(?<hq><img.*?>)?</h2>.*?'
        // Glamoured?
        . '(?(?=<div)(<div class="mirageitem.*?">)'
        . '<div class="mirageitem_ic">' . getRegExp('image','mirageItemIcon') . '.*?'
        . '<p>(?<mirageItemName>[^<]+?)<a.*?href="/lodestone/playguide/db/item/(?<mirageItemId>[\w\d^/]+)/".*?></a></p>'
        . '</div>)'
        //
        . '<h3 class="category_name">(?<slot>[^<]*?)</h3>.*?'
        . '<a href="/lodestone/playguide/db/item/(?<id>[\w\d]+?)/".*?>.*?</a></div>'
        . '(?(?=<div class="popup_w412_body_inner mb10">).*?'
        . '<div class="parameter\s?.*?"><strong>(?<parameter1>[^<]+?)</strong></div>'
        . '<div class="parameter"><strong>(?<parameter2>[^<]+?)</strong></div>'
        . '(?:<div class="parameter"><strong>(?<parameter3>[^<]+?)</strong></div>)?'
        . '</div>)'
        . '.*?<div class="pt3 pb3">.+?\s(?<ilv>[0-9]{1,3})</div>.*?'
        . '<span class="class_ok">(?<classes>[^<]*?)</span><br>'
        . '<span class="gear_level">[^\d]*?(?<gearlevel>[\d]+?)</span>.*?'
        . '(?(?=<ul class="basic_bonus")<ul class="basic_bonus">(?<bonuses>.*?)</ul>.*?)'
        . '(?(?=<ul class="list_1col)<ul class="list_1col.*?>'
        . '<li class="clearfix".*?><div>(?<durability>.*?)%</div></li>'
        . '<li class="clearfix".*?><div>(?<spiritbond>.*?)%</div></li>'
        . '<li class="clearfix".*?><div>(?<repairClass>[\w]+?)\s[\w\.]+?\s(?<repairLevel>\d*?)</div></li>'
        . '<li class="clearfix".*?><div>(?<materials>.*?)<\/div><\/li>.*?)'
        /** @TODO mutlilanguage **/
        . '(?(?=<ul class="ml12")<ul class="ml12"><li>[\s\w]+?:\s(?<convertible>Yes|No)[\s\w]+?:\s(?<projectable>Yes|No)[\s\w]+?:\s(?<desynthesizable>Yes|No)[\s\w]*?<\/li><\/ul>.*?)'
        . '<span class="sys_nq_element">(?<sellable>.*?)</span>'
        . '.*?<!-- //ITEM Detail -->#u';


    preg_match_all($gearRegExp, $gearHtml, $itemsMatch, PREG_SET_ORDER);


    $i = 0;
    $iLevelTotal = 0;
    $iLevelArray = [];
    $bonusRegExp = '#<li>(?<type>.*?)\s\+?(?<value>\-?\d+)</li>#i';
    foreach($itemsMatch as $match) {
        clearRegExpArray($match);
        // Basestats
        if($match['slot'] == 'Shield'){ // Shield
            $match['block_strength'] = $match['parameter1'];
            $match['block_rate'] = $match['parameter2'];
        }else if($match['parameter3'] == ""){ // Normalitem
            $match['defense'] = $match['parameter1'];
            $match['magical_defense'] = $match['parameter2'];
        }else{ // Weapon
            $match['damage'] = $match['parameter1'];
            $match['auto_attack'] = $match['parameter2'];
            $match['delay'] = $match['parameter3'];
        }
        unset($match['parameter1']);
        unset($match['parameter2']);
        unset($match['parameter3']);
        // HighQualityItem
        $match['hq'] = ($match['hq'] == "") ? false : true;
        //Bonuses
        $bonusMatch = array();
        preg_match_all($bonusRegExp,$match['bonuses'],$bonusMatch, PREG_SET_ORDER);
        $match['bonuses'] = clearRegExpArray($bonusMatch);
        if(array_key_exists('bonuses', $match)){
            foreach($match['bonuses'] as $b){
                $keyCleaned = strtolower(str_ireplace(' ', '-', $b['type']));
                if(!array_key_exists($keyCleaned, $character->gearBonus)){
                    $character->gearBonus[$keyCleaned] = [
                        'total' => 0,
                        'items' => []
                    ];
                }
                $character->gearBonus[$keyCleaned]['total'] += intval($b['value']);
                $character->gearBonus[$keyCleaned]['items'][] = [
                    'value' => intval($b['value']),
                    'name' => $match['name']
                ];
            }
        }

        $character->gear[] = $match;

        if ($match['slot'] != 'Soul Crystal') {
            $iLevelTotal = $iLevelTotal + $match['ilv'];
            $iLevelArray[] = $match['ilv'];
            $iLevelCalculated[] = $match['ilv'];

            if (in_array($match['slot'], getTwoHandedItems())) {
                $iLevelCalculated[] = $match['ilv'];
            }
        }

        // active job
        // TODO multilanguage
        if ($match['slot'] == 'Soul Crystal') {
            $character->activeJob = str_ireplace('Soul of the ', null, $match['name']);
        }
        $i++;
    }

    // active class
    $activeClassMatch= array();
    $possibleClasses = array();
    foreach($character->classjobs as $job){
        $possibleClasses[] = $job['name'];
    }

    if (isset($itemsMatch[0])) {
        if (preg_match('#('. implode('?|',$possibleClasses).')#i',$itemsMatch[0]['slot'],$activeClassMatch) === 1) {
            $character->activeClass = $activeClassMatch[1];
        }
    }

    $character->gearStats = [
        'total' => $iLevelTotal,
        'average' => isset($iLevelCalculated) ? floor(array_sum($iLevelCalculated) / 13) : 0,
        'array' => $iLevelArray,
    ];
    //Unsets
    unset($gearHtml);
    unset($gearRegExp);
    unset($itemsMatch);
    unset($activeClassMatch);
    unset($possibleClasses);
    unset($iLevelArray);
    unset($iLevelCalculated);

    return $character;

}

function getTwoHandedItems()
{
    /**
     * In the game, the Item Level for 2 handed equipment (or where you cannot
     * equip anything in the offhand slot) is doubled to balance the overall
     * item level with other classes that can have an offhand. The equipment
     * type below all have double item level.
     *
     * Items in this array are added twice to the Average
     */
        return [
            "Pugilist's Arm",
            "Marauder's Arm",
            "Archer's Arm",
            "Lancer's Arm",
            "Rogue's Arms",
            "Two-handed Thaumaturge's Arm",
            "Two-handed Conjurer's Arm",
            "Arcanist's Grimoire",
            "Fisher's Primary Tool",
            "Dark Knight's Arm",
            "Machinist's Arm",
            "Astrologian's Arm",
        ];
    }

/*function parseGearV3(\Viion\Lodestone\Character &$character,$gearHtml){
    $itemsMatch = array();
    $gearRegExp = '#<!-- ITEM Detail -->.*?<div class="name_area[^>].*?>.*?(?:<div class="(?<mirage>mirage)_staining (?<mirageType>unpaitable|painted_cover|no_paint)"(?: style="background\-color:\s?(?<miragePaintColor>\#[a-fA-F0-9]{6});")?></div>)?<img[^>]+?><img.+?src="(?<icon>[^\?"]+)(?:\?(?(?=[\d\w]+")(?<iconTimestamp>[\d\w^\?"]+)|(?<iconQueryString>[^\?"=]+=[^\?"]+?)))?".*?>.*?<div class="item_name_right"><div class="item_element[^"]*?"><span class="rare">(?<rare>[^<]*?)</span><span class="ex_bind">\s*(?<binding>[^<]*?)\s*</span></div><h2 class="item_name\s?(?<color>[^_]*?)_item">(?<name>[^<]+?)(?<hq><img.*?>)?</h2>.*?(?(?=<div)(<div class="mirageitem.*?">)<div class="mirageitem_ic"><img.+?src="(?<mirageItemIcon>[^\?"]+)(?:\?(?(?=[\d\w]+")(?<mirageItemIconTimestamp>[\d\w^\?"]+)|(?<mirageItemIconQueryString>[^\?"=]+=[^\?"]+?)))?".*?>.*?<p>(?<mirageItemName>[^<]+?)<a.*?href="/lodestone/playguide/db/item/(?<mirageItemId>[\w\d^/]+)/".*?></a></p></div>)<h3 class="category_name">(?<slot>[^<]*?)</h3>.*?<a href="/lodestone/playguide/db/item/(?<id>[\w\d]+?)/".*?>.*?</a></div>(?(?=<div class="popup_w412_body_inner mb10">).*?<div class="parameter\s?.*?"><strong>(?<parameter1>[^<]+?)</strong></div><div class="parameter"><strong>(?<parameter2>[^<]+?)</strong></div>(?:<div class="parameter"><strong>(?<parameter3>[^<]+?)</strong></div>)?</div>).*?<div class="pt3 pb3">.+?\s(?<ilv>[0-9]{1,3})</div>.*?<span class="class_ok">(?<classes>[^<]*?)</span><br><span class="gear_level">[^\d]*?(?<gearlevel>[\d]+?)</span>.*?(?(?=<ul class="basic_bonus")<ul class="basic_bonus">(?<bonuses>.*?)</ul>.*?)(?(?=<ul class="list_1col)<ul class="list_1col.*?><li class="clearfix".*?><div>(?<durability>.*?)%</div></li><li class="clearfix".*?><div>(?<spiritbond>.*?)%</div></li><li class="clearfix".*?><div>(?<repairClass>[\w]+?)\s[\w\.]+?\s(?<repairLevel>\d*?)</div></li><li class="clearfix".*?><div>(?<materials>.*?)<\/div><\/li>.*?)(?(?=<ul class="ml12")<ul class="ml12"><li>[\s\w]+?:\s(?<convertible>Yes|No)[\s\w]+?:\s(?<projectable>Yes|No)[\s\w]+?:\s(?<desynthesizable>Yes|No)[\s\w]*?<\/li><\/ul>.*?)<span class="sys_nq_element">(?<sellable>.*?)</span>.*?<!-- //ITEM Detail -->#u';

    preg_match_all($gearRegExp, $gearHtml, $itemsMatch, PREG_SET_ORDER);


    $i = 0;
    $iLevelTotal = 0;
    $iLevelArray = [];
    $bonusRegExp = '#<li>(?<type>.*?)\s\+?(?<value>\-?\d+)</li>#i';
    foreach($itemsMatch as $match) {
        clearRegExpArray($match);
        // Basestats
        if($match['slot'] == 'Shield'){ // Shield
            $match['block_strength'] = $match['parameter1'];
            $match['block_rate'] = $match['parameter2'];
        }else if($match['parameter3'] == ""){ // Normalitem
            $match['defense'] = $match['parameter1'];
            $match['magical_defense'] = $match['parameter2'];
        }else{ // Weapon
            $match['damage'] = $match['parameter1'];
            $match['auto_attack'] = $match['parameter2'];
            $match['delay'] = $match['parameter3'];
        }
        unset($match['parameter1']);
        unset($match['parameter2']);
        unset($match['parameter3']);
        // HighQualityItem
        $match['hq'] = ($match['hq'] == "") ? false : true;
        //Bonuses
        $bonusMatch = array();
        preg_match_all($bonusRegExp,$match['bonuses'],$bonusMatch, PREG_SET_ORDER);
        $match['bonuses'] = clearRegExpArray($bonusMatch);
        if(array_key_exists('bonuses', $match)){
            foreach($match['bonuses'] as $b){
                $keyCleaned = strtolower(str_ireplace(' ', '-', $b['type']));
                if(!array_key_exists($keyCleaned, $character->gearBonus)){
                    $character->gearBonus[$keyCleaned] = [
                        'total' => 0,
                        'items' => []
                    ];
                }
                $character->gearBonus[$keyCleaned]['total'] += intval($b['value']);
                $character->gearBonus[$keyCleaned]['items'][] = [
                    'value' => intval($b['value']),
                    'name' => $match['name']
                ];
            }
        }

        $character->gear[] = $match;

        if ($match['slot'] != 'Soul Crystal') {
            $iLevelTotal = $iLevelTotal + $match['ilv'];
            $iLevelArray[] = $match['ilv'];
            $iLevelCalculated[] = $match['ilv'];

            if (in_array($match['slot'], $this->getTwoHandedItems())) {
                $iLevelCalculated[] = $match['ilv'];
            }
        }

        // active job
        // TODO multilanguage
        if ($match['slot'] == 'Soul Crystal') {
            $character->activeJob = str_ireplace('Soul of the ', null, $match['name']);
        }
        $i++;
    }

    // active class
    $activeClassMatch= array();
    $possibleClasses = array();
    foreach($character->classjobs as $job){
        $possibleClasses[] = $job['name'];
    }

    if (isset($itemsMatch[0])) {
        if (preg_match('#('. implode('?|',$possibleClasses).')#i',$itemsMatch[0]['slot'],$activeClassMatch) === 1) {
            $character->activeClass = $activeClassMatch[1];
        }
    }

    $character->gearStats = [
        'total' => $iLevelTotal,
        'average' => isset($iLevelCalculated) ? floor(array_sum($iLevelCalculated) / 13) : 0,
        'array' => $iLevelArray,
    ];
    //Unsets
    unset($gearHtml);
    unset($gearRegExp);
    unset($itemsMatch);
    unset($activeClassMatch);
    unset($possibleClasses);
    unset($iLevelArray);
    unset($iLevelCalculated);

    return $character;

}

function parseGearV4(\Viion\Lodestone\Character &$character,$gearHtml){
    $itemsMatch = array();
    $gearRegExp = '<!-- ITEM Detail -->.*?<div class="name_area[^>].*?>.*?(?:<div class="(?<mirage>mirage)_staining (?<mirageType>unpaitable|painted_cover|no_paint)"(?: style="background\-color:\s?(?<miragePaintColor>\#[a-fA-F0-9]{6});")?></div>)?<img[^>]+?><img.+?src="(?<icon>[^\?"]+)(?:\?(?(?=[\d\w]+")(?<iconTimestamp>[\d\w^\?"]+)|(?<iconQueryString>[^\?"=]+=[^\?"]+?)))?".*?>.*?<div class="item_name_right"><div class="item_element[^"]*?"><span class="rare">(?<rare>[^<]*?)</span><span class="ex_bind">\s*(?<binding>[^<]*?)\s*</span></div><h2 class="item_name\s?(?<color>[^_]*?)_item">(?<name>[^<]+?)(?<hq><img.*?>)?</h2>.*?(?(?=<div)(<div class="mirageitem.*?">)<div class="mirageitem_ic"><img.+?src="(?<mirageItemIcon>[^\?"]+)(?:\?(?(?=[\d\w]+")(?<mirageItemIconTimestamp>[\d\w^\?"]+)|(?<mirageItemIconQueryString>[^\?"=]+=[^\?"]+?)))?".*?>.*?<p>(?<mirageItemName>[^<]+?)<a.*?href="/lodestone/playguide/db/item/(?<mirageItemId>[\w\d^/]+)/".*?></a></p></div>)<h3 class="category_name">(?<slot>[^<]*?)</h3>.*?<a href="/lodestone/playguide/db/item/(?<id>[\w\d]+?)/".*?>.*?</a></div>(?(?=<div class="popup_w412_body_inner mb10">).*?<div class="parameter\s?.*?"><strong>(?<parameter1>[^<]+?)</strong></div><div class="parameter"><strong>(?<parameter2>[^<]+?)</strong></div>(?:<div class="parameter"><strong>(?<parameter3>[^<]+?)</strong></div>)?</div>).*?<div class="pt3 pb3">.+?\s(?<ilv>[0-9]{1,3})</div>.*?<span class="class_ok">(?<classes>[^<]*?)</span><br><span class="gear_level">[^\d]*?(?<gearlevel>[\d]+?)</span>.*?(?(?=<ul class="basic_bonus")<ul class="basic_bonus">(?<bonuses>.*?)</ul>.*?)(?(?=<ul class="list_1col)<ul class="list_1col.*?><li class="clearfix".*?><div>(?<durability>.*?)%</div></li><li class="clearfix".*?><div>(?<spiritbond>.*?)%</div></li><li class="clearfix".*?><div>(?<repairClass>[\w]+?)\s[\w\.]+?\s(?<repairLevel>\d*?)</div></li><li class="clearfix".*?><div>(?<materials>.*?)<\/div><\/li>.*?)(?(?=<ul class="ml12")<ul class="ml12"><li>[\s\w]+?:\s(?<convertible>Yes|No)[\s\w]+?:\s(?<projectable>Yes|No)[\s\w]+?:\s(?<desynthesizable>Yes|No)[\s\w]*?<\/li><\/ul>.*?)<span class="sys_nq_element">(?<sellable>.*?)</span>.*?<!-- //ITEM Detail -->';

    preg_match_all('#'.$gearRegExp.'#u', $gearHtml, $itemsMatch, PREG_SET_ORDER);


    $i = 0;
    $iLevelTotal = 0;
    $iLevelArray = [];
    $bonusRegExp = '#<li>(?<type>.*?)\s\+?(?<value>\-?\d+)</li>#i';
    foreach($itemsMatch as $match) {
        clearRegExpArray($match);
        // Basestats
        if($match['slot'] == 'Shield'){ // Shield
            $match['block_strength'] = $match['parameter1'];
            $match['block_rate'] = $match['parameter2'];
        }else if($match['parameter3'] == ""){ // Normalitem
            $match['defense'] = $match['parameter1'];
            $match['magical_defense'] = $match['parameter2'];
        }else{ // Weapon
            $match['damage'] = $match['parameter1'];
            $match['auto_attack'] = $match['parameter2'];
            $match['delay'] = $match['parameter3'];
        }
        unset($match['parameter1']);
        unset($match['parameter2']);
        unset($match['parameter3']);
        // HighQualityItem
        $match['hq'] = ($match['hq'] == "") ? false : true;
        //Bonuses
        $bonusMatch = array();
        preg_match_all($bonusRegExp,$match['bonuses'],$bonusMatch, PREG_SET_ORDER);
        $match['bonuses'] = clearRegExpArray($bonusMatch);
        if(array_key_exists('bonuses', $match)){
            foreach($match['bonuses'] as $b){
                $keyCleaned = strtolower(str_ireplace(' ', '-', $b['type']));
                if(!array_key_exists($keyCleaned, $character->gearBonus)){
                    $character->gearBonus[$keyCleaned] = [
                        'total' => 0,
                        'items' => []
                    ];
                }
                $character->gearBonus[$keyCleaned]['total'] += intval($b['value']);
                $character->gearBonus[$keyCleaned]['items'][] = [
                    'value' => intval($b['value']),
                    'name' => $match['name']
                ];
            }
        }

        $character->gear[] = $match;

        if ($match['slot'] != 'Soul Crystal') {
            $iLevelTotal = $iLevelTotal + $match['ilv'];
            $iLevelArray[] = $match['ilv'];
            $iLevelCalculated[] = $match['ilv'];

            if (in_array($match['slot'], $this->getTwoHandedItems())) {
                $iLevelCalculated[] = $match['ilv'];
            }
        }

        // active job
        // TODO multilanguage
        if ($match['slot'] == 'Soul Crystal') {
            $character->activeJob = str_ireplace('Soul of the ', null, $match['name']);
        }
        $i++;
    }

    // active class
    $activeClassMatch= array();
    $possibleClasses = array();
    foreach($character->classjobs as $job){
        $possibleClasses[] = $job['name'];
    }

    if (isset($itemsMatch[0])) {
        if (preg_match('#('. implode('?|',$possibleClasses).')#i',$itemsMatch[0]['slot'],$activeClassMatch) === 1) {
            $character->activeClass = $activeClassMatch[1];
        }
    }

    $character->gearStats = [
        'total' => $iLevelTotal,
        'average' => isset($iLevelCalculated) ? floor(array_sum($iLevelCalculated) / 13) : 0,
        'array' => $iLevelArray,
    ];
    //Unsets
    unset($gearHtml);
    unset($gearRegExp);
    unset($itemsMatch);
    unset($activeClassMatch);
    unset($possibleClasses);
    unset($iLevelArray);
    unset($iLevelCalculated);

    return $character;

}*/