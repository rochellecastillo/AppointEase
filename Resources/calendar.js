document.addEventListener('DOMContentLoaded', function() {

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

      // ✅ Find which schedule applies to this date (based on day of week)
      var dow = new Date(clickedDate).getDay(); // 0=Sun, 1=Mon, ..., 6=Sat
      //var scheduleEvent = eventlist.find(e => e.daysOfWeek.includes(dow));

      // ✅ Use its maxAppointment (default 0 if not found)
      //var max = scheduleEvent ? scheduleEvent.extendedProps.maxAppointment : 0;
      //var max=2;

      if (apcount === 0) {
        //alert("No appointments on " + clickedDate);
      } else {
       // alert("Appointments on " + clickedDate + ": " + apcount + " / " + max);
       //alert(page);
       if(page=='booking'){
        var myModal = new bootstrap.Modal(document.getElementById('appointmentmodal'));
        myModal.show();
        document.getElementById("aptdate").innerHTML=clickedDate;
        document.getElementById("btnbook").value=clickedDate;
       }
       
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
        //var count = allEvents.filter(ev => (ev.startStr || '').split('T')[0] === dateStr).length;

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

        //badge.textContent = count-1 + " / " + max;
        if(page=='booking'){
          badge.textContent = count + " / " + max;
        }else if(page=='client'){
          badge.innerHTML = '<i class="fa-solid fa-eye"></i>';
        }

      } catch (err) {
        console.error('eventDidMount error:', err);
      }
    },

    events: eventlist
  });

  calendar.render();

  // ✅ Update badges when navigating the calendar
  function updateAllBadges() {
    var dayCells = calendarEl.querySelectorAll('.fc-daygrid-day[data-date]');
    var allEvents = calendar.getEvents();

    dayCells.forEach(dayCell => {
      var dateStr = dayCell.getAttribute('data-date');
      if (!dateStr) return;

      var renderedEvents = dayCell.querySelectorAll('.fc-event');
      renderedEvents.forEach(el => el.style.display = 'none');

      //var count = allEvents.filter(ev => (ev.startStr || '').split('T')[0] === dateStr).length;
      var count = allEvents.filter(ev =>
        (ev.startStr || '').split('T')[0] === dateStr &&
        ev.title !== 'Clinic Schedule' // exclude recurring schedule
      ).length;

      var topArea = dayCell.querySelector('.fc-daygrid-day-top') || dayCell;
      var badge = topArea.querySelector('.event-count-badge');

      // ✅ Get day of week and find schedule
      var dow = new Date(dateStr).getDay();
      if(page=='booking'){
        var scheduleEvent = eventlist.find(e => e.daysOfWeek.includes(dow));
      }else if(page=='client'){
        var scheduleEvent = eventlist.find(e => e.start);

      }
      //var max = scheduleEvent ? scheduleEvent.extendedProps.maxAppointment : 0;

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

        if(page=='booking'){
          badge.textContent = (count - 1) + " / " + max;
        }else if(page=='client'){
          badge.innerHTML = '<i class="fa-solid fa-eye"></i>';

        }
      }
    });
  }

  setTimeout(updateAllBadges, 120);
  calendarEl.addEventListener('click', function() {
    setTimeout(updateAllBadges, 120);
  });
});
