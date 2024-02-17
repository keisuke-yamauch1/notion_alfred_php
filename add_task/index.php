<?php

$token = getenv('TOKEN');
$databaseId = getenv('DATABASE_ID');

if ($token === false || $databaseId === false) {
    exit("TOKEN and DATABASE_ID must be set\n");
}

if (count($argv) < 2) {
    exit("タスク名を入力してください\n");
}

$taskName = '';
$taskType = '';

$args = explode(' ', $argv[1]);

if (count($args) == 1) {
    $taskName = $args[0];
} else {
    $taskType = $args[0];
    $taskName = $args[1];
}

addPage($token, $databaseId, convertTaskType($taskType), $taskName);

function addPage($token, $databaseId, $taskType, $taskName) {
    $url = 'https://api.notion.com/v1/pages';

    $headers = [
        "Authorization: Bearer {$token}",
        "Notion-Version: 2022-06-28",
        "Content-Type: application/json",
    ];

    $data = [
        "parent" => ["database_id" => $databaseId],
        "properties" => [
            "Name" => [
                "title" => [
                    ["text" => ["content" => $taskName]]
                ]
            ],
            "タスク種別" => [
                "select" => ["name" => $taskType]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        exit("Failed to add page\n");
    }

    echo "Page added successfully\n";
}

function convertTaskType($taskType) {
    switch ($taskType) {
        case "in": return "infra";
        case "ch": return "chore";
        case "re": return "review";
        case "ex": return "execution";
        case "im": return "implementation";
        case "de": return "design";
        case "me": return "meeting";
        default: return "no set";
    }
}

