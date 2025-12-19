<?php
// .envから設定を読み込む（簡易実装）
$env = parse_ini_file('.env');
$api_key = $env['OPENAI_API_KEY'] ?? '';
$model = "gpt-5-mini";

$result = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $user_input = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');

    // OpenAI APIへのリクエスト設定
    $url = "https://api.openai.com/v1/chat/completions";
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key"
    ];

    $data = [
        "model" => $model,
        "messages" => [
            [
                "role" => "system", 
                "content" => "与えられたテキストから最も重要なキーワードを1つだけ選び、その『原語: 英訳』の形式のみで回答してください。余計な文章は一切含めないでください。"
            ],
            ["role" => "user", "content" => $user_input]
        ],
        "temperature" => 0.7
    ];

    // cURLによるリクエスト（外部ライブラリ不使用）
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = 'Error: ' . curl_error($ch);
    } else {
        $response_data = json_decode($response, true);
        if (isset($response_data['choices'][0]['message']['content'])) {
            $result = $response_data['choices'][0]['message']['content'];
        } else {
            $error = "APIエラー: " . ($response_data['error']['message'] ?? '不明なエラー');
        }
    }
    curl_close($ch);
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Keyword Translator</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; line-height: 1.6; }
        .result { background: #f4f4f4; padding: 15px; border-radius: 5px; margin-top: 20px; font-weight: bold; }
        .error { color: red; }
        input[type="text"] { width: 80%; padding: 10px; }
        button { padding: 10px 20px; cursor: pointer; }
    </style>
</head>
<body>
    <h2>キーワード抽出・翻訳</h2>
    <form method="POST">
        <input type="text" name="message" placeholder="文章を入力してください" required>
        <button type="submit">送信</button>
    </form>

    <?php if ($result): ?>
        <div class="result">
            結果: <?php echo htmlspecialchars($result, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
</body>
</html>