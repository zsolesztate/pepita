<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pepita Fullcalendar Teszt</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</head>
<body>
    <div class="container mt-5" style="max-width: 700px">
        <h2 class="h2 text-center mb-5 border-bottom pb-3">Pepita Fullcalendar Teszt</h2>
        <div id='full_calendar_events'></div>
    </div>
    <div class="modal fade" tabindex="-1" role="dialog"><!-- modal -->
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Új esemény létrehozása</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <label class="col-xs-4" for="title">Esemény neve</label>
                            <input type="text" name="title" id="title" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <label class="col-xs-4" for="starts-at">Esemény kezdete</label>
                            <input type="text" name="starts_at" id="starts-at" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <label class="col-xs-4" for="ends-at">Esemény vége</label>
                            <input type="text" name="ends_at" id="ends-at" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default close" data-dismiss="modal">Bezár</button>
                    <button type="button" id="delete" class="btn btn-default">Esemény törlése</button>
                    <button type="button" id="edit" class="btn btn-primary" id="save-event">Változások mentése</button>
                </div>
            </div>
        </div>
    </div><!--modal vége -->

    <script>

        var dayOfWeek
        var weekNumber
        var allDay_bool

        $(document).ready(function () {            
            var SITEURL = "{{ url('/') }}";
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            var calendar = $('#full_calendar_events').fullCalendar({
                    header: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'month,agendaWeek,agendaDay'
                        },
                    defaultView: 'month',
                    editable: true,
                    editable: true,
                    events: SITEURL + "/calendar-event",     
                    displayEventTime: true,
                eventRender: function (event, element, view) {             
                        if (event.allDay === 'true') {
                            allDay_bool = true;
                        } else {
                            allDay_bool = false;
                        }
                },
                    selectable: true,
                    selectHelper: true,
                select: function (event_start, event_end, allDay) {
                   //esemény létrehozása
                    dayOfWeek = event_start.format('dddd');
                    weekNumber = moment(event_start).week() % 2 == 0 ? 'even_weeks' : 'odd_weeks';
                    var event_name = prompt('Esemény neve:');
                    if (event_name) {                     
                        event_start = $.fullCalendar.formatDate(event_start, "Y-MM-DD HH:mm");
                        event_end = $.fullCalendar.formatDate(event_end, "Y-MM-DD HH:mm");
                            $.ajax({
                                    url: SITEURL + "/calendar-crud-ajax",
                                    data: {
                                        event_name: event_name,
                                        event_start: event_start,
                                        event_end: event_end,
                                        allDay:allDay_bool,
                                        type: 'create',
                                        dayOfWeek : dayOfWeek,
                                        weekNumber : weekNumber
                                    },
                                    type: "POST",
                                success: function (data) {                              
                                    displayMessage("success","Új esemény létrehozva.");
                                
                                    calendar.fullCalendar('renderEvent', {
                                        id: data.id,
                                        title: event_name,
                                        start: event_start,
                                        end: event_end,
                                        allDay: allDay
                                }, true);
                                    calendar.fullCalendar('removeEvents');
                                    calendar.fullCalendar('addEventSource', data);
                                    calendar.fullCalendar('refetchEvents');
                                },
                                error: function (error){
                                    displayMessage('error',error.responseJSON.error);
                                }
                        });
                    }
                },
                //létező esemény áthelyezése
                eventDrop: function (event, delta) {

                    var dayOfWeek = event.start.format('dddd');
                    var weekNumber = moment(event.start).week() % 2 == 0 ? 'even_weeks' : 'odd_weeks';                
                    var event_start = $.fullCalendar.formatDate(event.start, "Y-MM-DD HH:mm");
                    var event_end = $.fullCalendar.formatDate(event.end, "Y-MM-DD HH:mm");
                            $.ajax({
                                url: SITEURL + '/calendar-crud-ajax',
                                data: {
                                    event_name: event.title,
                                    event_start: event_start,
                                    event_end: event_end,
                                    id: event.id,
                                    allDay:event.allDay,
                                    dayOfWeek : dayOfWeek,
                                    weekNumber : weekNumber,
                                    type: 'edit'
                                },
                                type: "POST",
                                success: function (response) {
                                    displayMessage("Esemény frissítve");
                                },
                                error: function (error){
                                    displayMessage('error',error.responseJSON.error);
                                    calendar.fullCalendar('refetchEvents');
                                    calendar.fullCalendar('unselect');
                                }
                            });
                         },
                eventClick: function (event,element) {
                    $('#delete').unbind('click');
                    $('#edit').unbind('click');
                    $('.close').unbind('click');
                    $('.modal').modal('show');

                    $(".close").click(function(){
                        $('.modal').modal('hide');
                    });

                    $('.modal').find('#title').val(event.title);
                    $('.modal').find('#starts-at').val( $.fullCalendar.formatDate(event.start, "Y-MM-DD HH:mm"));
                    $('.modal').find('#ends-at').val($.fullCalendar.formatDate(event.end, "Y-MM-DD HH:mm"));

                    //létező esemény törlése
                    $("#delete").click(function(){
                        var eventDelete = confirm("Biztos törölni akarod az eseményt?");
                    
                            if (eventDelete) {
                            $.ajax({
                                type: "POST",
                                url: SITEURL + '/calendar-crud-ajax',
                                data: {
                                    id: event.id,
                                    type: 'delete'
                                },
                                success: function (response) {
                                    calendar.fullCalendar('removeEvents', event.id);
                                    calendar.fullCalendar('refetchEvents');
                                    displayMessage("error","Esemény törölve");
                            
                                    $('.modal').modal('hide');                                   
                                }
                            });
                        }                                   
                    });

                    //létező esemény szerkesztése, modal
                    $("#edit").click(function(){
                        var eventEdit = confirm("Biztos megfelelőek az új adatok?");
                        if (eventEdit) {
                            var event_start = $('.modal').find('#starts-at').val();
                            var event_end = $('.modal').find('#ends-at').val();
                            var name = $('.modal').find('#title').val();
                            var date = moment(event_start)
                            var dayOfWeek = date.format('dddd');
                            var weekNumber = moment(date).week() % 2 == 0 ? 'even_weeks' : 'odd_weeks';

                            $.ajax({
                                type: "POST",
                                url: SITEURL + '/calendar-crud-ajax',
                                data: {
                                    event_name: name,
                                    event_start:event_start,
                                    event_end: event_end,
                                    id: event.id,
                                    allDay:event.allDay,
                                    dayOfWeek : dayOfWeek,
                                    weekNumber : weekNumber,
                                    type: 'exist_edit'
                                },
                                success: function (response) {
                                    calendar.fullCalendar('refetchEvents');
                                    displayMessage("success","Az esemény frissítve!");
                                    $('.modal').modal('hide');                                 
                                },
                                error: function (error){
                                        displayMessage('error',error.responseJSON.error);
                                }
                             });
                           }                
                        });                  
                    }
                });
            });
        
            //toastr üzenetek
        function displayMessage(response,message) {
            if(response == 'success'){
                toastr.success(message);
            }
            if(response == 'error'){
                toastr.error(message);
            }                    
         }
    </script>
</body>
</html>

<style>
.modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: white;
    padding: 20px;
    border: 1px solid #ccc;
    z-index: 9999;
}

.modal-content {
    text-align: center;
}

.modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9998;
}


</style>