<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appointment List</title>
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
  <?php include_once'Resources/include.php';?>
 <link rel="stylesheet" href="Resources/calendar.css">
</head>
<body style="font-family:arial">
<?php include_once'Resources/navbar.php';?>
<div class="container">
    <div class="row mt-3">
      <div class="col-md-3 mt-2">
        
        <h4>Doctors List</h4>
        <?php
        for($c=1;$c<=5;$c++){
        ?>
        <div class="row">
          <button class="btn form-control shadow-none">
            <div class="d-flex">
              <div class="me-2"><img src="Resources/Images/default_profile.webp" alt="Profile" height="40px"></div>
              <div class="text-start">
                <div><h5>Dr. Juan dela Cruz</h5></div>
                <div style="margin-top:-0.8em;font-size:0.8em;">Physician</div>
              </div>
            </div>
          </button>
        </div>
        <?php 
        }
        ?>

      </div>
      <div class="col-md-8">
          <div id="calendar"></div>
      </div>
    </div>
</div>
<div class="modal" id="appointmentmodal">
  <div class="modal-dialog">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header bg-primary text-white">
        <h4 class="modal-title">Book an Appointment</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div class="container">
          <div class="row">
            <div class="col-md-8">
              <label for="apdate">Appointment Date</label>
              <input type="date" class="form-control" id="apdate" name="apdate" readonly>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>


<script>
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

    validRange: {
        start: new Date()
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
    events: [
      {
        title: 'Mr Castro',
        start: '2025-10-20T10:00:00',
      },
      {
        title: 'Mr Johnson',
        start: '2025-10-20T10:00:00',
      },
      {
        title: 'No Schedule',
        start: '2025-10-25',
        color: '#d61c2cff',
      },
      {
        title: 'Dr Schedule',
        startRecur: '2025-10-01',
        endRecur: '2025-12-31',
        daysOfWeek: [1,3], // Monday & Wed
        color: '#28a745',
      }
    ]
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
        badge.textContent = count-1 + " / "+max;
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
</script>

</body>
</html>
