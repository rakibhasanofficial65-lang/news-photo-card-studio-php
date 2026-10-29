
document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const editorForm = document.getElementById("editorForm");
    const newspaper = document.getElementById("newspaper");
    const imageInput = document.getElementById("newsImage");
    const downloadBtn = document.getElementById("downloadBtn");

    // Check that the essential elements exist.
    if (!editorForm || !newspaper) {
        console.error("Live preview: #editorForm or #newspaper was not found.");
        return;
    }

    // Update text fields in the preview as the user types.
    editorForm.querySelectorAll("[data-target]").forEach(function (field) {
        function updatePreview() {
            const target = field.dataset.target;
            const previewElements = newspaper.querySelectorAll(
                '[data-view="' + target + '"]'
            );

            previewElements.forEach(function (element) {
                element.textContent = field.value;
            });
        }

        field.addEventListener("input", updatePreview);
        field.addEventListener("change", updatePreview);

        // Initialize preview from current field values.
        updatePreview();
    });

const newsImage = document.getElementById('newsImage');
const previewImage = document.getElementById('previewImage');
const photoPlaceholder = document.getElementById('photoPlaceholder');

if (newsImage && previewImage) {
    newsImage.addEventListener('change', function () {
        const file = this.files && this.files[0];

        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('একটি ছবি নির্বাচন করুন।');
            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {
            previewImage.onload = function () {
                previewImage.hidden = false;

                if (photoPlaceholder) {
                    photoPlaceholder.hidden = true;
                }
            };

            previewImage.src = event.target.result;
        };

        reader.onerror = function () {
            alert('ছবিটি পড়া যায়নি। আবার চেষ্টা করুন।');
        };

        reader.readAsDataURL(file);
    });
}




    // Download the newspaper card as a PNG.
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
