<?php
// v1/admin_calendar.php
require_once 'admin_header.php';
?>

<div class="admin-container">
    <div class="page-header">
        <h1><i class="fas fa-calendar-alt"></i> Schedule</h1>
    </div>

    <div id='calendar'></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var isMobile = window.innerWidth < 768;
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: isMobile ? 'listWeek' : 'dayGridMonth',
        headerToolbar: isMobile ? {
            left: 'prev,next',
            center: 'title',
            right: 'listWeek,timeGridDay'
        } : {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        height: 'auto',
        events: 'api_calendar.php',
        eventClick: function(info) {
            if (info.event.url) {
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            }
        }
    });
    calendar.render();
});
</script>
</body>
</html>
