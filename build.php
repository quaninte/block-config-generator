<?php
// settings
require __DIR__ . '/setting.sample.php';

$settingFile = __DIR__ . '/setting.php';
if (file_exists($settingFile)) {
    require $settingFile;

    $settings = array_merge($sampleSettings, $settings);
} else {
    $settings = $sampleSettings;
}

// check file existed
if (!file_exists($settings['list_file'])) {
    throw new Exception('List file not found');
}

$ips = explode("\n", file_get_contents($settings['list_file']));

$blockTemplate = '<Directory />
order allow,deny
{%block_list%}
allow from all
</Directory>';

$blockRowTemplate = 'deny from {%ips_list%}';

$rows = array();

$i = 0;
$tmpIps = array();
foreach ($ips as $ip) {
    // check valid ip
    if(!filter_var($ip, FILTER_VALIDATE_IP)) {
        continue;
    }
    $tmpIps[] = $ip;

    $i++;

    if ($i == $settings['ips_per_block']) {
        // add to rows list
        $rows[] = str_replace('{%ips_list%}', implode(' ', $tmpIps), $blockRowTemplate);
        $tmpIps = array();
        $i = 0;
    }
}
if (count($tmpIps)) {
    $rows[] = str_replace('{%ips_list%}', implode(' ', $tmpIps), $blockRowTemplate);
}

$blockFileContent = str_replace('{%block_list%}', trim(implode("\n", $rows)), $blockTemplate);
file_put_contents(__DIR__ . '/block.conf', $blockFileContent);