/**
 * File Upload
 */

"use strict";

(function () {
  var messages = {
    dictDefaultMessage: "فایل‌ها را برای ارسال اینجا رها کنید",
    dictFallbackMessage:
      "مرورگر شما از ارسال فایل با کشیدن و رها کردن پشتیبانی نمی‌کند.",
    dictFallbackText:
      "لطفا از فرم زیر برای ارسال فایل های خود مانند دوران های گذشته استفاده کنید.",
    dictFileTooBig:
      "فایل خیلی بزرگ است ({{filesize}}MiB). حداکثر اندازه فایل: {{maxFilesize}}MiB.",
    dictInvalidFileType: "شما نمی‌توانید فایل‌هایی از این نوع را ارسال کنید.",
    dictResponseError: "سرور با کد {{statusCode}} پاسخ داد.",
    dictCancelUpload: "لغو ارسال",
    dictCancelUploadConfirmation: "آیا از لغو کردن این ارسال اطمینان دارید؟",
    dictRemoveFile: "حذف فایل",
    dictMaxFilesExceeded: "شما نمی‌توانید فایل دیگری ارسال کنید.",
  };

  // previewTemplate: Updated Dropzone default previewTemplate
  // ! Don't change it unless you really know what you are doing
  const previewTemplate = `<div class="dz-preview dz-file-preview">
<div class="dz-details">
  <div class="dz-thumbnail">
    <img data-dz-thumbnail>
    <span class="dz-nopreview">No preview</span>
    <div class="dz-success-mark"></div>
    <div class="dz-error-mark"></div>
    <div class="dz-error-message"><span data-dz-errormessage></span></div>
    <div class="progress">
      <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
    </div>
  </div>
  <div class="dz-filename" data-dz-name></div>
  <div class="dz-size" data-dz-size></div>
</div>
</div>`;

  // Start your code from here

  // Basic Dropzone
  // --------------------------------------------------------------------
  // const dropzoneOptions = Object.assign(options, messages);
  $(document).ready(function () {
    const dropzones = document.getElementsByClassName("drop-zone");

    dropzones.forEach((element) => {
      let cropper;

      $(`#${element.id} .drop-file`).on("change", (e) => {
        $(`#${element.id} .drop-title`).text(e.target.value + "");

        let preview = $(`#${element.id} .dz-message`).children(
          ".upload-preview"
        );
        const isImg = e.target?.files[0];

        if (isImg?.type?.includes("image")) {
          let reader = new FileReader();

          reader.onload = function (e) {
            if (!preview?.length) {
              preview = $(`#${element.id} .dz-message`)
                .prepend("<img src='' alt='preview' class='upload-preview' />")
                .children(".upload-preview");

              preview.css({
                display: "block",
                margin: "5px auto 10px",
                width: "100px",
                height: "100px",
                "object-fit": "cover",
                "border-radius": "12px",
              });
            }
            preview.attr("src", e.target.result);

            const cropperModal = $(`#${element.id}`).attr("cropper");
            if (cropperModal) {
              const image = document.querySelector(
                `#${cropperModal} .cropper-image`
              );
              image.src = e.target.result;

              cropper?.destroy();

              const modalButton = $("<button>", {
                "data-bs-toggle": "modal",
                "data-bs-target": `#${cropperModal}`,
                text: "",
              });

              $("body").append(modalButton);
              modalButton.click();

              cropper = new Cropper(image, {
                aspectRatio: 16 / 9,
                minContainerWidth: 500,
                minContainerHeight: 500,
              });

              window.resizeTo(100, 100);

              $(`#${cropperModal} .submit-crop`).click(function () {
                const croppedData = cropper.getCroppedCanvas()?.toDataURL();
                preview.attr("src", croppedData);
                modalButton.click();
                modalButton.remove();
                $(`#${element.id} .base64_img`).val(croppedData);
              });
            }
          };

          reader.readAsDataURL(e.target?.files[0]);
          preview.show();
        } else {
          preview.hide();
        }
      });

      $(`#${element.id}`).on("dragover", (e) => {
        $(`#${element.id}`).css("border", "2px dashed #7ba4f1");
      });

      $(`#${element.id}`).on("dragleave", (e) => {
        $(`#${element.id}`).css("border", "2px dashed #d4d8dd");
      });

      $(`#${element.id}`).on("drop", (e) => {
        $(`#${element.id}`).css("border", "2px dashed #d4d8dd");
      });

      // if ($(`#${element.id} .drop-file`).attr("value")?.length) {
      //   const previewValue = $(`#${element.id} .drop-file`).attr("value");

      //   $(`#${element.id} .drop-title`).text(previewValue + "");
      //   let preview = $(`#${element.id} .dz-message`).children(".upload-preview");

      //   const imgFormats = ["png", "jpg", "jpeg", "svg"];
      //   const isImg = imgFormats?.includes(previewValue.split(".").at(-1));
      //   let imgFolder = "package_images";

      //   if (window.location.href?.includes("collages-manage")) {
      //     imgFolder = "college_logos";
      //   }

      //   if (window.location.href?.includes("teacher-manage")) {
      //     imgFolder = "teacher_profiles";
      //   }

      //   if (isImg) {
      //     if (!preview?.length) {
      //       preview = $(`#${element.id} .dz-message`)
      //         .prepend(
      //           `<img src='https://eec1.ut.ac.ir/${imgFolder}/${previewValue}' alt='preview' class='upload-preview' />`
      //         )
      //         .children(".upload-preview");

      //       preview.css({
      //         display: "block",
      //         margin: "5px auto 10px",
      //         width: "100px",
      //         height: "100px",
      //         "object-fit": "cover",
      //         "border-radius": "12px",
      //       });
      //     }

      //     preview.show();
      //   } else {
      //     preview.hide();
      //   }
      // }

      // let myDropzone = new Dropzone(`#${element.id}`, {
      //   parallelUploads: 1,
      //   maxFilesize: 100,
      //   addRemoveLinks: true,
      //   maxFiles: 1,
      //   url: "/test",
      //   hiddenInputContainer: `#${element.id}`,
      //   autoProcessQueue: false,
      // });

      // $(".dz-hidden-input").attr("disabled", "true");
      // $(".dz-hidden-input").on("change", () => {});

      // myDropzone.on("addedfile", (file) => {
      //   console.log(file);
      // });
    });
  });
})();
