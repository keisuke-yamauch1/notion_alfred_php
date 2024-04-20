<?php

enum TaskType: string
{
    case INFRA = 'infra';
    case CHORE = 'chore';
    case REVIEW = 'review';
    case EXECUTION = 'execution';
    case IMPLEMENT = 'implement';
    case DESIGN = 'design';
    case MEETING = 'meeting';
    case SURVEY = 'survey';
    case QUESTION = 'question';
    case UNSPECIFIED = 'unspecified';

    public function getLabel(): string
    {
        return match ($this) {
            self::INFRA => '基盤',
            self::CHORE => '雑務',
            self::REVIEW => 'レビュー',
            self::EXECUTION => '実行',
            self::IMPLEMENT => '実装',
            self::DESIGN => '設計',
            self::MEETING => '打ち合わせ',
            self::SURVEY => '調査',
            self::QUESTION => '質問',
            self::UNSPECIFIED => '未設定',
        };
    }

    public function defaultGtdType(): GtdType
    {
        return match ($this) {
            self::INFRA,
            self::DESIGN,
            self::CHORE,
            self::REVIEW,
            self::EXECUTION,
            self::IMPLEMENT,
            self::SURVEY,
            self::QUESTION,
            self::UNSPECIFIED => GtdType::NEXT,
            self::MEETING => GtdType::WAIT,
        };
    }

    public function defaultWaitStatus(): WaitStatus
    {
        return match ($this) {
            self::INFRA,
            self::DESIGN,
            self::CHORE,
            self::REVIEW,
            self::EXECUTION,
            self::IMPLEMENT,
            self::SURVEY,
            self::QUESTION,
            self::UNSPECIFIED => WaitStatus::NONE,
            self::MEETING => WaitStatus::TIME,
        };
    }

    public static function fromAbbreviation(string $abbreviation): TaskType
    {
        foreach (self::cases() as $case) {
            if (substr($case->value, 0, 2) === strtolower($abbreviation)) {
                return $case;
            }
        }
        throw new \InvalidArgumentException("Invalid abbreviation: {$abbreviation}");
    }
}

enum GtdType: string
{
    case DO = 'やる';
    case NEXT = '次にやる';
    case WAIT = '待ち';
    case DONE = '完了';
    case WONT = 'やらない';
}

enum WaitStatus: string
{
    case REVIEW = 'レビュー';
    case RELEASE = 'リリース';
    case TIME = '時間';
    case WIP = 'WIP';
    case QUESTION = '質問中';
    case TASK = '後続タスク';
    case NONE = '';
}

enum Column: string
{
    case NAME = 'Name';
    case TASK_TYPE = 'タスク種別';
    case GTD_TYPE = 'GTD種別';
    case WAIT_STATUS = '待ち状態';
}

$token = '';
$databaseId = '';

$token = getenv('TOKEN');
$databaseId = getenv('DATABASE_ID');

if ($token === false || $databaseId === false) {
    exit("TOKEN and DATABASE_ID must be set\n");
}

if (count($argv) < 2) {
    exit("タスク名を入力してください\n");
}

array_shift($argv);
$args = explode(' ', $argv[0]);

try {
    $taskType = TaskType::fromAbbreviation($args[0]);
    array_shift($args);
    $taskName = implode(' ', $args);
} catch (\InvalidArgumentException $e) {
    $taskType = TaskType::UNSPECIFIED;
    $taskName = $argv[0];
}

$task = new Task($taskName, $taskType, $taskType->defaultGtdType());
$apiClient = new NotionApiClient($token, $databaseId);
$apiClient->addTask($task);

class NotionApiClient
{
    const string URL = 'https://api.notion.com/v1/pages';

    private Header $header;
    private string $databaseId;

    public function __construct(string $token, string $databaseId)
    {
        $this->header = new Header($token);
        $this->databaseId = $databaseId;
    }

    public function addTask(Task $task): void
    {
        $data = $this->makeAddTaskData($task);

        $this->exec($data);
    }

    private function makeAddTaskData(Task $task): array
    {
        $data = [
            "parent" => ["database_id" => $this->databaseId],
        ];

        $properties = [
            Column::NAME->value => [
                "title" => [
                    ["text" => ["content" => $task->name()]]
                ]
            ],
            Column::TASK_TYPE->value => [
                "select" => ["name" => $task->type()->getLabel()]
            ],
            Column::GTD_TYPE->value => [
                "select" => ["name" => $task->gdtType()->value]
            ],
        ];

        if ($task->shouldSetTimeToWaitStatus()) {
            $properties[Column::WAIT_STATUS->value] = [
                "select" => ["name" => $task->waitStatus()->value]
            ];
        }

        $data['properties'] = $properties;

        return $data;
    }

    private function exec(array $data): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header->getHeaders());
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            exit("Failed to add page\n");
        }

        echo "Page added successfully\n";
    }
}

class Header
{
    private string $authorization;
    private string $notionVersion;
    private string $contentType;

    public function __construct(string $token)
    {
        $this->authorization = "Bearer {$token}";
        $this->notionVersion = "2022-06-28";
        $this->contentType = "application/json";
    }

    public function getHeaders(): array
    {
        return [
            "Authorization: {$this->authorization}",
            "Notion-Version: {$this->notionVersion}",
            "Content-Type: {$this->contentType}",
        ];
    }
}

class Task {
    private string $name;
    private TaskType $type;
    private GtdType $gtdType;

    public function __construct(
        string   $name,
        TaskType $type,
        GtdType  $gtdType
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->gtdType = $gtdType;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): TaskType
    {
        return $this->type;
    }

    public function gdtType(): GtdType
    {
        return $this->gtdType;
    }

    public function shouldSetTimeToWaitStatus(): bool
    {
        return $this->type->defaultWaitStatus() === WaitStatus::TIME;
    }

    public function waitStatus(): WaitStatus
    {
        return $this->type->defaultWaitStatus();
    }
}
