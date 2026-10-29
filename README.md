# News Photo Card Studio (PHP)

## চালানোর নিয়ম
1. PHP 8.0+ ইনস্টল করুন, অথবা XAMPP ব্যবহার করুন।
2. `news-photo-card-studio` ফোল্ডারটি XAMPP-এর `htdocs` ফোল্ডারে রাখুন।
3. Apache চালু করুন।
4. ব্রাউজারে `http://localhost/news-photo-card-studio/` খুলুন।
5. ফর্মে লেখা পরিবর্তন করুন। `তথ্য প্রয়োগ করুন` চাপলে তথ্য PHP-তে জমা হবে।
6. ছবি আপলোড করে `PNG ডাউনলোড` চাপুন।

## বৈশিষ্ট্য
- বাংলা সংবাদপত্র-ধাঁচের ক্রিম পেপার ডিজাইন
- শিরোনাম, পত্রিকার নাম, তারিখ, ফ্যাক্ট বক্স, উদ্ধৃতি, লেখা ও টিকার এডিট
- JPG/PNG/WebP আপলোড (সর্বোচ্চ ৮ MB)
- লাইভ টেক্সট প্রিভিউ এবং PNG export

## নোট
- বাংলা ফন্টের জন্য Google Fonts এবং PNG export-এর জন্য html2canvas CDN ব্যবহৃত হয়েছে; এগুলোর জন্য ইন্টারনেট দরকার।
- লাইভ লেখা সঙ্গে সঙ্গে বদলায়। সার্ভারে তথ্য সংরক্ষণ করার জন্য `তথ্য প্রয়োগ করুন` চাপুন।
- Production-এ ব্যবহারের আগে authentication, CSRF protection, upload throttling এবং server configuration যোগ করুন।
