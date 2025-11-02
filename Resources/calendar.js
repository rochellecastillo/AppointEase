document.addEventListener('DOMContentLoaded', function() {

  var max = 2; // max allowed appointments per day
  var calendarEl = document.getElementById('calendar');

  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',

    dateClick: function(info) {
      var events = calendar.getEvents();
      var clickedDate = info.dateStr;

      // Get events on clicked date
      var eventsOnDate = events.filter(function(event) {
        let start = (event.startStr || '').split('T')[0];
        return start === clickedDate;
      });

      var apcount = eventsOnDate.length;

      // 🟢 Show alert every time user clicks a date
      if (apcount === 0) {
        alert("No appointments on " + clickedDate);
      } else {
        alert("Appointments on " + clickedDate + ": " + apcount + " / " + max);
      }
    },

    eventDidMount: function(info) {
      try {
        var dateStr = (info.event.startStr || '').split('T')[0];
        if (!dateStr) return;

        var dayCell = calendarEl.querySelector('.fc-daygrid-day[data-date="' + dateStr + '"]');
        if (!dayCell) return;

        // hide all events (we just want the badge)
        var renderedEvents = dayCell.querySelectorAll('.fc-event');
        renderedEvents.forEach(el => el.style.display = 'none');

        var allEvents = calendar.getEvents();
        var count = allEvents.filter(ev => (ev.startStr || '').split('T')[0] === dateStr).length;

        var topArea = dayCell.querySelector('.fc-daygrid-day-top') || dayCell;
        var badge = topArea.querySelector('.event-count-badge');
        if (!badge) {
          badge = document.createElement('div');
          badge.className = 'event-count-badge';
          var dayNumber = topArea.querySelector('.fc-daygrid-day-number');
          if (dayNumber && dayNumber.parentNode)
            dayNumber.insertAdjacentElement('afterend', badge);
          else
            topArea.appendChild(badge);
        }

        // update badge display
        badge.innerHTML = '<i class="fa-solid fa-eye"></i>';

      } catch (err) {
        console.error('eventDidMount error:', err);
      }
    },

    // your event data
    events: eventlist
  });

  calendar.render();

  // update all badges when navigating calendar
  function updateAllBadges() {
    var dayCells = calendarEl.querySelectorAll('.fc-daygrid-day[data-date]');
    var allEvents = calendar.getEvents();

    dayCells.forEach(dayCell => {
      var dateStr = dayCell.getAttribute('data-date');
      if (!dateStr) return;

      var renderedEvents = dayCell.querySelectorAll('.fc-event');
      renderedEvents.forEach(el => el.style.display = 'none');

      var count = allEvents.filter(ev => (ev.startStr || '').split('T')[0] === dateStr).length;
      var topArea = dayCell.querySelector('.fc-daygrid-day-top') || dayCell;
      var badge = topArea.querySelector('.event-count-badge');

      if (count === 0) {
        if (badge) badge.remove();
      } else {
        if (!badge) {
          badge = document.createElement('div');
          badge.className = 'event-count-badge';
          var dayNumber = topArea.querySelector('.fc-daygrid-day-number');
          if (dayNumber && dayNumber.parentNode)
            dayNumber.insertAdjacentElement('afterend', badge);
          else
            topArea.appendChild(badge);
        }
        badge.textContent = count + " / " + max;
      }
    });
  }

  setTimeout(updateAllBadges, 120);

  calendarEl.addEventListener('click', function() {
    setTimeout(updateAllBadges, 120);
  });
});
