
document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const editorForm = document.getElementById("editorForm");
    const newspaper = document.getElementById("newspaper");
    const imageInput = document.getElementById("newsImage");
    const previewImage = document.getElementById("previewImage");
    const photoPlaceholder = document.getElementById("photoPlaceholder");
    const downloadBtn = document.getElementById("downloadBtn");
    const cartoonBtn = document.getElementById("cartoonBtn");
    const cartoonStatus = document.getElementById("cartoonStatus");

    if (!editorForm || !newspaper) {
        console.error("Live preview: required elements not found.");
        return;
    }

    // Live text preview
    editorForm.querySelectorAll("[data-target]").forEach(function (field) {
        function updatePreview() {
            const target = field.dataset.target;

            newspaper.querySelectorAll(
                '[data-view="' + target + '"]'
            ).forEach(function (element) {
                element.textContent = field.value;
            });
        }

        field.addEventListener("input", updatePreview);
        field.addEventListener("change", updatePreview);
        updatePreview();
    });

    // Uploaded image preview
    if (imageInput && previewImage) {
        imageInput.addEventListener("change", function () {
            const file = this.files && this.files[0];
            if (!file) return;

            if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
                alert("JPG, PNG অথবা WebP ছবি নির্বাচন করুন।");
                this.value = "";
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                previewImage.onload = function () {
                    previewImage.hidden = false;
                    if (photoPlaceholder) photoPlaceholder.hidden = true;
                };

                previewImage.src = event.target.result;
            };

            reader.onerror = function () {
                alert("ছবিটি পড়া যায়নি। আবার চেষ্টা করুন।");
            };

            reader.readAsDataURL(file);
        });
    }

    // AI cartoon generation
    if (cartoonBtn && cartoonStatus && imageInput && previewImage) {
        cartoonBtn.addEventListener("click", async function () {
            const file = imageInput.files && imageInput.files[0];

            if (!file) {
                cartoonStatus.textContent = "প্রথমে একটি ছবি আপলোড করুন।";
                imageInput.focus();
                return;
            }

            if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
                cartoonStatus.textContent = "JPG, PNG অথবা WebP ছবি ব্যবহার করুন।";
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                cartoonStatus.textContent = "ছবির আকার ৫ MB-এর মধ্যে রাখুন।";
                return;
            }

            const formData = new FormData();
            formData.append("image", file);

            cartoonBtn.disabled = true;
            cartoonStatus.textContent = "AI কার্টুন তৈরি করছে। অপেক্ষা করুন...";

            try {
                const response = await fetch("/api/cartoon", {
                    method: "POST",
                    body: formData
                });

                
const rawResponse = await response.text();

let data;

try {
    data = JSON.parse(rawResponse);
} catch (error) {
    console.error("Cartoon API response:", {
        status: response.status,
        contentType: response.headers.get("content-type"),
        body: rawResponse.slice(0, 1000)
    });

    throw new Error(
        "API Error: HTTP " + response.status +
        ". Vercel-এর উত্তর JSON নয়।"
    );
}

                if (!response.ok || !data.success || !data.image) {
                    throw new Error(data.error || "কার্টুন তৈরি করা যায়নি।");
                }

                // Validate the returned image before replacing the preview.
                const generatedImage = new Image();

                generatedImage.onload = function () {
                    previewImage.src = data.image;
                    previewImage.hidden = false;

                    if (photoPlaceholder) {
                        photoPlaceholder.hidden = true;
                    }

                    cartoonStatus.textContent =
                        "কার্টুন তৈরি হয়েছে! এখন PNG ডাউনলোড করতে পারবেন।";
                };

                generatedImage.onerror = function () {
                    cartoonStatus.textContent =
                        "AI-এর তৈরি ছবিটি খোলা যায়নি। আবার চেষ্টা করুন।";
                };

                generatedImage.src = data.image;

            } catch (error) {
                console.error("Cartoon generation failed:", error);
                cartoonStatus.textContent =
                    error.message || "সমস্যা হয়েছে। পরে আবার চেষ্টা করুন।";
            } finally {
                cartoonBtn.disabled = false;
            }
        });
    }

    // Download newspaper card as PNG
    if (downloadBtn) {
        downloadBtn.addEventListener("click", async function () {
            if (typeof html2canvas === "undefined") {
                alert("PNG ডাউনলোড লাইব্রেরি লোড হয়নি। ইন্টারনেট সংযোগ পরীক্ষা করুন।");
                return;
            }

            downloadBtn.disabled = true;

            try {
                const canvas = await html2canvas(newspaper, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: "#ffffff",
                    onclone: function (clonedDocument) {
                        const clonedCard =
                            clonedDocument.getElementById("newspaper");

                        if (clonedCard) {
                            clonedCard.querySelectorAll(".action-row")
                                .forEach(function (element) {
                                    element.remove();
                                });
                        }
                    }
                });

                const link = document.createElement("a");
                link.download = "news-photo-card.png";
                link.href = canvas.toDataURL("image/png");
                link.click();

            } catch (error) {
                console.error("PNG download failed:", error);
                alert("PNG তৈরি করা যায়নি। Console-এর error পরীক্ষা করুন।");
            } finally {
                downloadBtn.disabled = false;
            }
        });
    }

    console.log("News Photo Card Studio: app.js loaded successfully.");
});
