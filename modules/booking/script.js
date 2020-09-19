function initBookingCalendar() {
  new Calendar("booking-calendar", {
    url: WEB_URL + "index.php/booking/model/calendar/toJSON",
    onclick: function(d) {
      send(
        WEB_URL + "index.php/booking/model/index/action",
        "action=detail&id=" + this.id,
        doFormSubmit
      );
    }
  });
}


function initBooking() {
  $G('begin_date').addEvent("change", function() {
    if (this.value != "") {
      $E('end_date').calendar.minDate(this.value);
    }
  });
}

function initBookingOrder() {
  $G('begin_date').addEvent("change", function() {
    if (this.value != "") {
      $E('end_date').calendar.minDate(this.value);
    }
  });
}
