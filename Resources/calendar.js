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
        alert("No appointments on " + clickedDate);
      } else {
        if(page=="client"){
        //alert("Appointments on " + clickedDate + ": " + apcount + " / " + max);
       var html=`
        <div class="text-center mb-3">
          <h6 class="text-center mb-4">
              <i class="fa-solid fa-info-circle me-2"></i>Appointment Details
          </h6>
          <h6 class="text-primary">${formatDate(clickedDate)}</h6>
          <small class="text-muted">${eventsOnDate[0].extendedProps.time}</small>
        </div>
                
                <hr>
                
                <div class="doctor-profile mb-3">
                    <div class="d-flex align-items-center">
                        <img src="Resources/Images/default_profile.webp" 
                             class="doctor-image me-3" 
                             alt="Doctor">
                        <div>
                            <h5 class="mb-1">${eventsOnDate[0].extendedProps.doctor}</h5>
                            <p class="text-muted mb-0">
                                <i class="fa-solid fa-stethoscope me-1"></i>
                                ${eventsOnDate[0].extendedProps.specialization}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                  <strong>Status:</strong>
                 ${eventsOnDate[0].extendedProps.status == 0 
                  ? '<span class="status-badge status-approved">Approved</span>'
                  : eventsOnDate[0].extendedProps.status == 1
                  ? '<span class="status-badge status-pending">Completed</span>'
                    : '<span class="status-badge status-cancelled">Cancelled</span>'}
                </div>
                    ${eventsOnDate[0].extendedProps.status === 0 ? `
                    <button class="btn btn-danger w-100 mb-2" onclick="cancelAppointment(${eventsOnDate[0].extendedProps.id})">
                        <i class="fa-solid fa-times me-2"></i>Cancel Appointment
                    </button>
                    <button class="btn btn-outline-primary w-100" onclick="rescheduleAppointment()">
                        <i class="fa-solid fa-calendar-alt me-2"></i>Reschedule
                    </button>
                    `:""}
                  
                
       `;
       document.getElementById("appointmentDetails").innerHTML=html;
  
      }

       if(page=='booking'){
        var myModal = new bootstrap.Modal(document.getElementById('appointmentmodal'));
        myModal.show();
        document.getElementById("aptdate").innerHTML=clickedDate;
        document.getElementById("btnbook").value=clickedDate;
       }
       
      }
    },

    eventDidMount: function(info) {
        info.el.style.display = 'none';
      try {
        var dateStr = (info.event.startStr || '').split('T')[0];
        if (!dateStr) return;

        var dayCell = calendarEl.querySelector('.fc-daygrid-day[data-date="' + dateStr + '"]');
        if (!dayCell) return;

        // hide all events (we just want the badge)
        //var renderedEvents = dayCell.querySelectorAll('.fc-event');
        //renderedEvents.forEach(el => el.style.display = 'none');

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
          badge.textContent = info.event.extendedProps.count + " / " + max;
        }else if(page=='client'){
          badge.innerHTML = `
          <div><i class="fa-solid fa-eye"></i></div>
          <div>${info.event.title}</div>
          <div style="font-size:0.8em;text-align:center;">${info.event.extendedProps.time}</div>
          `;
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

      //var renderedEvents = dayCell.querySelectorAll('.fc-event');
      //renderedEvents.forEach(el => el.style.display = 'none');

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
          //badge.innerHTML = '<div>'+event.time+'</div>';

        }
      }
    });
  }

  setTimeout(updateAllBadges, 120);
  calendarEl.addEventListener('click', function() {
    setTimeout(updateAllBadges, 120);
  });
});

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}