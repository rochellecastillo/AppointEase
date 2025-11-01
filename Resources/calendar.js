

document.addEventListener('DOMContentLoaded', function() {
  
  var max=2;
  var calendarEl = document.getElementById('calendar');

  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',

    dateClick: function(info) {
      // Get all events from the calendar
      var events = calendar.getEvents();

      // Filter events that occur on the clicked date
      var clickedDate = info.dateStr;
      var eventsOnDate = events.filter(function(event) {
        // Convert start date to YYYY-MM-DD for easy comparison
        let start = (event.startStr || '').split('T')[0];
        return start === clickedDate;
      });

      if (eventsOnDate.length > 0) {
        // Collect titles of all events for that date
        let titles = eventsOnDate.slice(0, -1).map(e => e.title).join('\n');
        //alert('Appointments on ' + clickedDate + ':\n' + titles);
        apcount=eventsOnDate.slice(0,-1).length;
        if(apcount<max){
          //alert(apcount);
          var myModal = new bootstrap.Modal(document.getElementById('appointmentmodal'));
          myModal.show();
          document.getElementById("apdate").value=clickedDate;
        }else{
          Swal.fire({
            title: "Warning",
            text: "Appointment Limit Reached!",
            icon: "warning"
          });
        }
      } else {
        //alert('No events on ' + clickedDate);
      }
    },


    // use eventDidMount to hide event elements and build badge
    eventDidMount: function(info) {
      try {
        var dateStr = (info.event.startStr || '').split('T')[0];
        if (!dateStr) return;

        // find the day cell for this date
        var dayCell = calendarEl.querySelector('.fc-daygrid-day[data-date="' + dateStr + '"]');
        if (!dayCell) return;

        // hide all rendered event elements for that day (they may be multiple)
        var renderedEvents = dayCell.querySelectorAll('.fc-event');
        renderedEvents.forEach(function(el) { el.style.display = 'none'; });

        // count events for that date using calendar.getEvents() (works with recurrings)
        var allEvents = calendar.getEvents();
        var count = allEvents.filter(function(ev) {
          let s = (ev.startStr || '').split('T')[0];
          return s === dateStr;
        }).length;

        // top area where we place the badge
        var topArea = dayCell.querySelector('.fc-daygrid-day-top') || dayCell;

        // create or update badge
        var badge = topArea.querySelector('.event-count-badge');
        if (!badge) {
          badge = document.createElement('div');
          badge.className = 'event-count-badge';
          // insert after the day number for good placement
          var dayNumber = topArea.querySelector('.fc-daygrid-day-number');
          if (dayNumber && dayNumber.parentNode) {
            dayNumber.insertAdjacentElement('afterend', badge);
          } else {
            topArea.appendChild(badge);
          }
        }

        // update text
        //badge.textContent = count + (count > 1 ? ' Events' : ' Event');
        badge.textContent = count-1 + " / "+max;

      } catch (err) {
        console.error('eventDidMount error:', err);
      }
    },

    // events data
    events: eventlist
  });

  calendar.render();

  // helper to recalc badges for all rendered day cells
  function updateAllBadges() {
    var dayCells = calendarEl.querySelectorAll('.fc-daygrid-day[data-date]');
    var allEvents = calendar.getEvents();

    dayCells.forEach(function(dayCell) {
      var dateStr = dayCell.getAttribute('data-date');
      if (!dateStr) return;

      // hide any raw event elements inside
      var renderedEvents = dayCell.querySelectorAll('.fc-event');
      renderedEvents.forEach(function(el) { el.style.display = 'none'; });

      // count using calendar.getEvents (handles recurring occurrences too)
      var count = allEvents.filter(function(ev) {
        let s = (ev.startStr || '').split('T')[0];
        return s === dateStr;
      }).length;

      var topArea = dayCell.querySelector('.fc-daygrid-day-top') || dayCell;
      var badge = topArea.querySelector('.event-count-badge');

      if (count === 0) {
        if (badge) badge.remove();
      } else {
        if (!badge) {
          badge = document.createElement('div');
          badge.className = 'event-count-badge';
          var dayNumber = topArea.querySelector('.fc-daygrid-day-number');
          if (dayNumber && dayNumber.parentNode) dayNumber.insertAdjacentElement('afterend', badge);
          else topArea.appendChild(badge);
        }
        //badge.textContent = count + (count > 1 ? ' Appointments' : ' Appointment');
        badge.innerHTML = '<i class="fa-solid fa-square-check"></i>';
      }
    });
  }

  // update badges after initial render and when navigating
  setTimeout(updateAllBadges, 120);
  // when user navigates months, refresh badges
  calendarEl.addEventListener('click', function(e){
    // small debounce: recalc a bit after potential navigation click
    setTimeout(updateAllBadges, 120);
  });
});
