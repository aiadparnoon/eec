/**
 * Form Editors
 */

"use strict";

(function () {
  // Snow Theme
  // --------------------------------------------------------------------
  const snowEditor = new Quill("#snow-editor", {
    bounds: "#snow-editor",
    modules: {
      formula: true,
      toolbar: "#snow-toolbar",
    },
    theme: "snow",
  });

  // Bubble Theme
  // --------------------------------------------------------------------
  const bubbleEditor = new Quill("#bubble-editor", {
    modules: {
      toolbar: "#bubble-toolbar",
    },
    theme: "bubble",
  });

  // Full Toolbar
  // --------------------------------------------------------------------
  const fullToolbar = [
    [
      {
        font: [],
      },
      {
        size: [],
      },
    ],
    ["bold", "italic", "underline", "strike"],
    [
      {
        color: [],
      },
      {
        background: [],
      },
    ],
    [
      {
        script: "super",
      },
      {
        script: "sub",
      },
    ],
    [
      {
        header: "1",
      },
      {
        header: "2",
      },
      "blockquote",
      "code-block",
    ],
    [
      {
        list: "ordered",
      },
      {
        list: "bullet",
      },
      {
        indent: "-1",
      },
      {
        indent: "+1",
      },
    ],
    [
      {
        direction: "rtl",
      },
      {
        align: [],
      },
    ],
    ["link", "image", "video", "formula"],
    ["clean"],
  ];
  const fullEditorId = new Quill("#full-editor", {
    bounds: ".full-editor",
    placeholder: "چیزی بنویسید ...",
    modules: {
      formula: true,
      toolbar: fullToolbar,
    },
    theme: "snow",
  });

  const textEditors = document.getElementsByClassName("full-editor");

  for (let index = 0; index < textEditors.length; index++) {
    const item = textEditors[index];

    const fullEditor = new Quill(`#${item.id}`, {
      bounds: `#${item.id}`,
      placeholder: "چیزی بنویسید ...",
      modules: {
        formula: true,
        toolbar: fullToolbar,
      },
      theme: "snow",
    });
  }

  // const form = document.querySelector("form");
  // form.addEventListener("submit", (event) => {
  //   // Append Quill content before submitting
  //   event.formData.append(
  //     "content",
  //     JSON.stringify(fullEditor.getContents().ops)
  //   );
  // });
})();
