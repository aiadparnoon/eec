/**
 * Selects & Tags
 */

"use strict";
$(function () {
  const selectPicker = $(".selectpicker"),
    select2 = $(".select2"),
    select2Icons = $(".select2-icons");

  // Bootstrap Select
  // --------------------------------------------------------------------
  if (selectPicker.length) {
    // selectPicker.selectpicker();
  }

  // Select2
  // --------------------------------------------------------------------

  // Default
  if (select2.length) {
    select2.each(function () {
      var $this = $(this);
      var select2Input = $this.select2({
        placeholder: "لطفا انتخاب کنید",
        dropdownParent: $this.hasClass("none-parent") ? null : $this.parent(),
        value: "borna",
      });
      if (!$this.attr("multiple")) {
        $this.on("select2:select", function (e) {
          $this.val(e.target.value);
          select2Input.val(e.target.value);
          $this.trigger("change");
          console.log("select event", $this.val());
        });
      }
    });
  }
});
