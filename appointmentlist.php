<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appointment List</title>
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
  <?php include_once'Resources/include.php';?>
</head>
<body style="font-family:arial">
<?php include_once'Resources/navbar.php';?>
<div class="container">
    <div class="row mt-3">
      <div class="col-md-3">
        
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


<script>
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');

  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    validRange: {
        start: new Date()
    },
    dateClick: function(info) {
      alert('Date clicked: ' + info.dateStr);
    },

  
    events: [
      {
        title: 'Mr Castro',
        start: '2025-10-20T10:00:00',
        //end: '2025-10-20T12:00:00'
      },
      {
        title: 'Mr Johnson',
        start: '2025-10-20T10:00:00',
        //end: '2025-10-20T12:00:00'
      },
      {
        title: 'No Schedule',
        start: '2025-10-25'
      },
      /*{
        title: 'Weekly Training',
        startRecur: '2025-10-01',
        endRecur: '2025-12-31',
        daysOfWeek: [1], // Monday (0=Sunday, 1=Monday, ...)
        startTime: '09:00:00',
        endTime: '10:00:00',
        title: 'Training (Every Monday)'
      }*/
    ]
  });

  calendar.render();
});
</script>

</body>
</html>

<style>
.fc-event-title {
  font-size: 12px !important;  /* adjust size as needed */
  font-weight: normal !important;
}
.fc-col-header-cell-cushion {
  text-decoration: none !important;
  font-weight: bold;
}
/* Reduce spacing between multiple events in the same day */
.fc-daygrid-event {
  margin: 1px 0 !important; /* vertical spacing (default ~3–4px) */
  padding: 2px 4px !important; /* smaller padding inside event box */
  line-height: 0.7 !important; /* tighter line spacing inside event text */
  font-size: 0.7em;
}
/* Remove underline from date numbers */
.fc-daygrid-day-number {
  text-decoration: none !important;
}

/* Optional: change date text color (if you want) */
.fc-daygrid-day-number {
  color: black; /* or any color you prefer */
  font-weight: bold;
  font-size: 1.2em;
}

.fc-daygrid-day-number:hover {
  background-color: #f0f0f0;
  border-radius: 5px;
  cursor: pointer;
}
</style>
