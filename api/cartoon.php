<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function respond(int $status, array $data): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

// Accept POST requests only.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, ['error' => 'শুধু POST request গ্রহণ করা হয়।']);
}

// Load credentials from Vercel Environment Variables.
$accountId = getenv('CLOUDFLARE_ACCOUNT_ID') ?: '';
$apiToken  = getenv('CLOUDFLARE_API_TOKEN') ?: '';

if ($accountId === '' || $apiToken === '') {
    error_log('Cartoon API: Cloudflare environment variables are missing.');

    respond(500, [
        'error' => 'AI সার্ভার সেটআপ সম্পূর্ণ হয়নি। পরে আবার চেষ্টা করুন।'
    ]);
}

// Validate the uploaded image.
if (
    !isset($_FILES['image'])
    || !is_array($_FILES['image'])
    || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
) {
    respond(400, [
        'error' => 'প্রথমে একটি ছবি আপলোড করুন।'
    ]);
}

$file = $_FILES['image'];
$tmp  = $file['tmp_name'] ?? '';
$size = (int) ($file['size'] ?? 0);

if (!is_uploaded_file($tmp)) {
    respond(400, ['error' => 'আপলোড করা ছবিটি সঠিক নয়।']);
}

if ($size < 1 || $size > 5 * 1024 * 1024) {
    respond(413, [
        'error' => 'ছবির আকার ৫ MB-এর মধ্যে রাখুন।'
    ]);
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);

$allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/webp'
];

if (
    !in_array($mime, $allowedTypes, true)
    || @getimagesize($tmp) === false
) {
    respond(415, [
        'error' => 'শুধু JPG, PNG অথবা WebP ছবি ব্যবহার করুন।'
    ]);
}

$imageBytes = file_get_contents($tmp);

if ($imageBytes === false) {
    respond(500, ['error' => 'ছবিটি পড়া যায়নি।']);
}

// Image-to-image model input.
$prompt = 'Transform the input photograph into a fun, colorful, '
    . 'high-quality cartoon illustration. Preserve the main subject, '
    . 'recognizable facial features, pose and composition. Use expressive '
    . 'but tasteful cartoon details, clean outlines, vibrant colors and '
    . 'polished digital illustration. Do not add text, logos or watermarks.';

$payload = [
    'prompt' => $prompt,
    'image_b64' => base64_encode($imageBytes),
    'strength' => 0.65,
    'guidance' => 7.5,
    'num_steps' => 15,
    'negative_prompt' => 'blurry, distorted face, extra fingers, '
        . 'deformed body, text, watermark, low quality'
];

$endpoint = 'https://api.cloudflare.com/client/v4/accounts/'
    . rawurlencode($accountId)
    . '/ai/run/@cf/runwayml/stable-diffusion-v1-5-img2img';

if (!function_exists('curl_init')) {
    respond(500, [
        'error' => 'সার্ভারে cURL সুবিধা চালু নেই।'
    ]);
}

$ch = curl_init($endpoint);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json',
        'Accept: image/png, image/jpeg, application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE
    )
]);

$result = curl_exec($ch);
$curlError = curl_error($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

curl_close($ch);

if ($result === false) {
    error_log('Cartoon API transport error: ' . $curlError);

    respond(502, [
        'error' => 'AI সার্ভারে সংযোগ করা যায়নি। আবার চেষ্টা করুন।'
    ]);
}

if ($status < 200 || $status >= 300) {
    // Keep provider details in server logs, not public responses.
    error_log(
        'Cloudflare AI returned HTTP ' . $status
        . ': ' . substr($result, 0, 1500)
    );

    if ($status === 401 || $status === 403) {
        respond(502, [
            'error' => 'AI API অনুমতি যাচাই করা যায়নি। সার্ভার সেটিংস পরীক্ষা করুন।'
        ]);
    }

    if ($status === 429) {
        respond(429, [
            'error' => 'AI ব্যবহারের সীমা সাময়িকভাবে পূর্ণ। পরে চেষ্টা করুন।'
        ]);
    }

    respond(502, [
        'error' => 'AI ছবি তৈরি করতে পারেনি। পরে আবার চেষ্টা করুন।'
    ]);
}

// Cloudflare can return the image directly or a JSON result,
// depending on the endpoint response format.
$imageOutput = null;
$outputMime = 'image/png';

if (
    str_contains(strtolower($contentType), 'image/png')
    || str_contains(strtolower($contentType), 'image/jpeg')
    || str_contains(strtolower($contentType), 'image/webp')
) {
    $imageOutput = $result;

    if (str_contains(strtolower($contentType), 'image/jpeg')) {
        $outputMime = 'image/jpeg';
    } elseif (str_contains(strtolower($contentType), 'image/webp')) {
        $outputMime = 'image/webp';
    }
} else {
    $decoded = json_decode($result, true);

    if (is_array($decoded)) {
        $candidate = $decoded['result'] ?? null;

        if (is_string($candidate) && $candidate !== '') {
            // Some responses may contain base64-encoded image data.
            if (str_starts_with($candidate, 'data:image/')) {
                $parts = explode(',', $candidate, 2);
                $candidate = $parts[1] ?? '';
            }

            $binary = base64_decode($candidate, true);

            if ($binary !== false) {
                $imageOutput = $binary;
            }
        }
    }
}

if (
    !is_string($imageOutput)
    || $imageOutput === ''
    || @getimagesizefromstring($imageOutput) === false
) {
    error_log(
        'Cartoon API: Unexpected image response. HTTP '
        . $status . ', content type: ' . $contentType
    );

    respond(502, [
        'error' => 'AI-এর উত্তর থেকে ছবিটি পাওয়া যায়নি। পরে চেষ্টা করুন।'
    ]);
}

respond(200, [
    'success' => true,
    'image' => 'data:' . $outputMime . ';base64,'
        . base64_encode($imageOutput)
]);
