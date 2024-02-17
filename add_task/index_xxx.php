<?php

$token = getenv('TOKEN');
$databaseId = getenv('DATABASE_ID');

if ($token === false || $databaseId === false) {
    exit("TOKEN and DATABASE_ID must be set\n");
}

if (count($argv) < 2) {
    exit("タスク名を入力してください\n");
}

$taskName = $argv[1];

addPage($token, $databaseId, 'XXX', $taskName);

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
