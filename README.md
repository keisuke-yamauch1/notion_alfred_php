# 使い方

1. https://github.com/keisuke-yamauch1/notion_alfred_php/blob/main/add_task/index.php を開く
2. コピーする
   1. 必要に応じて、convertTaskTypeは変更する
3. Alfredの設定画面を開く
4. Workflowsを開く
5. 下の「+」→「Blank workflow」 でワークフローを追加する
6. Nameし、Createをクリック
7. 右クリック→「Inputs」→「Keyword」
8. 任意のKeyword、Titleを入力し、Saveをクリック
9. 右側の出っ張りをクリックし、「Actions」→「Run Script」をクリック
10. Languageを「/path/to/php」に変更（/path/to/は自身の環境によって変わる）
11. Scriptに2でコピーしたコードを貼り付け、Saveをクリック
12. 画面右上の[x]をクリック
13. 環境変数にTOKEN、DATABASE_IDを追加
    1. TOKENはインテグレーションのシークレットトークン
    2. DATABASE_IDはNotionの任意のデータベースのID
    3. 取得方法はググると色々な記事が出てくると思うので、そちらに任せる
14. Alfredを開き、 8で設定したKeywordを入力し、タスク名を入力しEnterを押す
15. Notionの任意のデータベースにタスクが追加される
