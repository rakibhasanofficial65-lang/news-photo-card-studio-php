<?php
declare(strict_types=1);

function esc($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$defaults = [
    'paper'       => 'দৈনিক সোনাভাঙা দেশ',
    'day'         => '',
    'dayRed'      => '',
    'district'    => '',
    'districtRed' => '',
    'date'        => '',
    'motto'       => '',
    'price'       => 'মূল্য: ১০ টাকা',
    'section'     => 'শীর্ষ সংবাদ',
    'headline'    => '',
    'caption'     => 'ছবির ক্যাপশন এখানে লিখুন',

    'factLabel'   => '',
    'factTitle'   => '',
    'factValue'   => '',
    'factText'    => '',

    'quote'       => '',
    'quoteBy'     => '',
    'body1'       => '',
    'body2'       => '',
    'body3'       => '',

    'footer'      => '',
    'editor'      => '',
    'button'      => '',
    'ticker'      => '',
    'ticker2'     => '',
    'made'        => ''
];

$values = $defaults;

$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

if ($isPost) {
    foreach ($defaults as $key => $default) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $values[$key] = trim($_POST[$key]);
        }
    }
}

$imagePath = '';

if ($isPost && isset($_POST['existingImage']) && is_string($_POST['existingImage'])) {
    $candidate = basename($_POST['existingImage']);

    if (
        preg_match('/^news-[a-f0-9]+\.(jpg|png|webp)$/', $candidate) === 1
        && is_file(__DIR__ . '/uploads/' . $candidate)
    ) {
        $imagePath = 'uploads/' . $candidate;
    }
}

$uploadError = '';

if (
    $isPost
    && isset($_FILES['newsImage'])
    && ($_FILES['newsImage']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
) {
    $file = $_FILES['newsImage'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $uploadError = 'ছবি আপলোড করা যায়নি। আবার চেষ্টা করুন।';
    } elseif (($file['size'] ?? 0) > 8 * 1024 * 1024) {
        $uploadError = 'ছবির আকার সর্বোচ্চ ৮ MB হতে পারবে।';
    } elseif (!is_uploaded_file($file['tmp_name'] ?? '')) {
        $uploadError = 'আপলোড করা ছবিটি সঠিক নয়।';
    } else {
        $tmp = $file['tmp_name'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (
            !isset($allowed[$mime])
            || @getimagesize($tmp) === false
        ) {
            $uploadError = 'JPG, PNG অথবা WebP ছবি আপলোড করুন।';
        } else {
            $uploadDir = __DIR__ . '/uploads';

            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    $uploadError = 'uploads ফোল্ডার তৈরি করা যায়নি।';
                }
            }

            if ($uploadError === '') {
                try {
                    $filename = 'news-' . bin2hex(random_bytes(8))
                        . '.' . $allowed[$mime];

                    if (move_uploaded_file($tmp, $uploadDir . '/' . $filename)) {
                        $imagePath = 'uploads/' . $filename;
                    } else {
                        $uploadError = 'uploads ফোল্ডারে লেখার অনুমতি পরীক্ষা করুন।';
                    }
                } catch (Throwable $e) {
                    $uploadError = 'ছবি সংরক্ষণ করা যায়নি। আবার চেষ্টা করুন।';
                }
            }
        }
    }
}

$groups = [
    'পত্রিকার তথ্য' => [
        'paper'       => 'পত্রিকার নাম',
        'day'         => 'দিন',
        'dayRed'      => 'দিনের লাল লেখা',
        'district'    => 'ডান পাশের ছোট লেখা',
        'districtRed' => 'ডান পাশের লাল লেখা',
        'date'        => 'তারিখ ও সংখ্যা',
        'motto'       => 'মাঝের ছোট স্লোগান',
        'price'       => 'পত্রিকার মূল্য'
    ],

    'শিরোনাম ও ছবি' => [
        'section'  => 'বিভাগের নাম',
        'headline' => 'প্রধান শিরোনাম',
        'caption'  => 'ছবির ক্যাপশন'
    ],

    'ফ্যাক্ট বক্স' => [
        'factLabel' => 'ফ্যাক্ট বক্স লেবেল',
        'factTitle' => 'ফ্যাক্ট বক্স শিরোনাম',
        'factValue' => 'বড় লেখা',
        'factText'  => 'ফ্যাক্ট বক্সের বিবরণ'
    ],

    'নিউজের লেখা' => [
        'quote'  => 'উদ্ধৃতি',
        'quoteBy' => 'উদ্ধৃতির পরিচয়',
        'body1'  => 'নিউজ অনুচ্ছেদ ১',
        'body2'  => 'নিউজ অনুচ্ছেদ ২',
        'body3'  => 'নিউজ অনুচ্ছেদ ৩'
    ],

    'নিচের অংশ' => [
        'footer'  => 'নিচের সংবাদ লাইন',
        'editor'  => 'সম্পাদকের নাম',
        'button'  => 'হলুদ বাটনের লেখা',
        'ticker'  => 'লাল টিকারের লেখা',
        'ticker2' => 'টিকারের দ্বিতীয় লেখা',
        'made'    => 'ক্রেডিট লেখা'
    ]
];

$multiline = [
    'headline',
    'caption',
    'factText',
    'quote',
    'body1',
    'body2',
    'body3',
    'footer',
    'ticker',
    'ticker2'
];

?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>News Photo Card Studio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="topbar">
    <div>
        <strong>News Photo Card Studio</strong>
        <span>PHP Edition · বাংলা ফটোকার্ড নির্মাতা</span>
    </div>

    <button class="primary" type="button" id="downloadBtn">
        PNG ডাউনলোড
    </button>
</header>

<main class="workspace">

    <aside class="editor">
        <h1>ফটোকার্ড এডিটর</h1>

        <p class="hint">
            লেখা পরিবর্তন করুন, ছবি আপলোড করুন এবং লাইভ প্রিভিউ দেখুন।
        </p>

        <?php if ($uploadError !== ''): ?>
            <p class="error"><?= esc($uploadError) ?></p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="editorForm">

            <label class="upload">
                নিউজের ছবি আপলোড

                <input
                    type="file"
                    name="newsImage"
                    id="newsImage"
                    accept="image/jpeg,image/png,image/webp"
                >
            </label>

            
<label class="upload">
    নিউজের ছবি আপলোড

    <input
        type="file"
        name="newsImage"
        id="newsImage"
        accept="image/jpeg,image/png,image/webp"
    >
</label>

<!-- নতুন AI Cartoon Button -->
<div class="ai-cartoon-tools">
    <button type="button" id="cartoonBtn" class="primary wide">
        🎨 Make Funny Cartoon
    </button>

    <p id="cartoonStatus" role="status" aria-live="polite">
        ছবি আপলোড করে কার্টুন তৈরি করুন।
    </p>
</div>

<input
    type="hidden"
    name="existingImage"
    id="existingImage"
    value="<?= esc($imagePath ? basename($imagePath) : '') ?>"
>


            <div class="fields">

                <?php foreach ($groups as $groupTitle => $items): ?>
                    <section class="fieldgroup">

                        <h2><?= esc($groupTitle) ?></h2>

                        <?php foreach ($items as $key => $label): ?>
                            <label class="field">
                                <?= esc($label) ?>

                                <?php if (in_array($key, $multiline, true)): ?>
                                    <textarea
                                        name="<?= esc($key) ?>"
                                        data-target="<?= esc($key) ?>"
                                        rows="<?= $key === 'headline' ? 3 : 2 ?>"
                                    ><?= esc($values[$key]) ?></textarea>
                                <?php else: ?>
                                    <input
                                        type="text"
                                        name="<?= esc($key) ?>"
                                        data-target="<?= esc($key) ?>"
                                        value="<?= esc($values[$key]) ?>"
                                    >
                                <?php endif; ?>

                            </label>
                        <?php endforeach; ?>

                    </section>
                <?php endforeach; ?>

            </div>

            <button class="primary wide" type="submit">
                তথ্য প্রয়োগ করুন
            </button>

        </form>
    </aside>

    <section class="preview-area">

        <div class="preview-heading">
            <div>
                <h2>লাইভ প্রিভিউ</h2>
                <p>আপনার পরিবর্তন এখানে দেখা যাবে।</p>
            </div>

            <span class="paper-tag">সংবাদপত্র থিম</span>
        </div>

        <article class="newspaper" id="newspaper">

            <header class="masthead">

                <div class="corner">
                    <span data-view="day"><?= esc($values['day']) ?></span>
                    <b data-view="dayRed"><?= esc($values['dayRed']) ?></b>
                </div>

                <h1 data-view="paper"><?= esc($values['paper']) ?></h1>

                <div class="corner right">
                    <span data-view="district"><?= esc($values['district']) ?></span>
                    <b data-view="districtRed"><?= esc($values['districtRed']) ?></b>
                </div>

            </header>

            <div class="meta">
                <span data-view="date"><?= esc($values['date']) ?></span>
                <span data-view="motto"><?= esc($values['motto']) ?></span>
                <span data-view="price"><?= esc($values['price']) ?></span>
            </div>

            <div class="section-label">
                <i></i>
                <b data-view="section"><?= esc($values['section']) ?></b>
            </div>

            <h2 class="headline" data-view="headline"><?= esc($values['headline']) ?></h2>

            <div class="story-grid">

                <div class="photo-column">

                    <div class="photo-frame">

                        <div
                            class="photo-placeholder"
                            id="photoPlaceholder"
                            <?= $imagePath ? 'hidden' : '' ?>
                        >
                            <span>ছবি এখানে দেখাবে</span>
                            <small>আপনার নিউজের ছবি আপলোড করুন</small>
                        </div>

                        <img
                            src="<?= esc($imagePath) ?>"
                            id="previewImage"
                            alt="নিউজের ছবি"
                            <?= $imagePath ? '' : 'hidden' ?>
                        >

                    </div>

                    <p class="caption" data-view="caption"><?= esc($values['caption']) ?></p>

                </div>

                <div class="article-column">

                    <div class="factbox">

                        <span class="fact-ribbon" data-view="factLabel">
                            <?= esc($values['factLabel']) ?>
                        </span>

                        <h3>
                            <span data-view="factTitle"><?= esc($values['factTitle']) ?></span>
                            <strong data-view="factValue"><?= esc($values['factValue']) ?></strong>
                        </h3>

                        <p data-view="factText"><?= esc($values['factText']) ?></p>

                        <span class="scissors">✂</span>

                    </div>

                    <div class="quote">

                        <span class="quote-mark">“</span>

                        <div>
                            <strong data-view="quote"><?= esc($values['quote']) ?></strong>
                            <small data-view="quoteBy"><?= esc($values['quoteBy']) ?></small>
                        </div>

                    </div>

                    <p class="body-copy" data-view="body1"><?= esc($values['body1']) ?></p>
                    <p class="body-copy" data-view="body2"><?= esc($values['body2']) ?></p>
                    <p class="body-copy" data-view="body3"><?= esc($values['body3']) ?></p>

                </div>

            </div>

            <footer class="paper-footer">
                <p data-view="footer"><?= esc($values['footer']) ?></p>
                <strong data-view="editor"><?= esc($values['editor']) ?></strong>
            </footer>

            <div class="action-row">

                <div class="yellow-button" data-view="button">
                    <?= esc($values['button']) ?>
                </div>

            

            <div class="ticker">
                <strong data-view="ticker"><?= esc($values['ticker']) ?></strong>
                <span>•</span>
                <span data-view="ticker2"><?= esc($values['ticker2']) ?></span>
                <b data-view="made"><?= esc($values['made']) ?></b>
            </div>

            <footer class="card-founder">
                <span>Created by</span>
                <strong>MD RAKIB HASAN</strong>
            </footer>

        </article>

        <!-- এই মেনুগুলো কার্ডের বাইরে, তাই PNG-তে আসবে না -->
        <nav class="website-menu" aria-label="ওয়েবসাইট মেনু">
            <a href="./">হোম</a>
            <a href="#" id="facebookMenu">Facebook</a>
            <a href="#" id="instagramMenu">Instagram</a>
            <button type="button" id="shareMenu">শেয়ার</button>
        </nav>

    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="app.js"></script>

</body>
</html>
