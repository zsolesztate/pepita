<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\CrudEvents;

class CalenderController extends Controller
{

        public function index(Request $request)
        {
            if($request->ajax()) {  
            
                $data = CrudEvents::whereDate('event_start', '>=', $request->start)
                    ->whereDate('event_end',   '<=', $request->end)
                    ->get(['id', 'event_name', 'event_start', 'event_end']);
                    $formattedEvents = [];
                    foreach ($data as $event) {
                        $formattedEvents[] = [
                            'title' => $event->event_name,
                            'start' => $event->event_start,
                            'end' => $event->event_end,
                            'id' => $event->id,
                        ];
                    }
            
                    return response()->json($formattedEvents);
            }
            return view('calendar');
        }
 
  
  
    public function calendarEvents(Request $request)
    {
       
        switch ($request->type) {
           case 'create':
            
                $formattedEventEnd = $this->formatEventEnd($request->event_end, $request->allDay);

                $check = $this->checkOverlap($request->event_start,$formattedEventEnd);
            
                if (!$check) {            
                    return response()->json(['error' => 'Az időpont már foglalt.'], 400);
                }

                $checkAppointments = $this->AppointmentOverlap($request->event_start, $formattedEventEnd, $request->dayOfWeek, $request->weekNumber);

                if ($checkAppointments) {
                
                    return response()->json(['error' => 'Nem rendelési idő.'], 400);
                }

                $event = CrudEvents::create([
                    'event_name' => $request->event_name,
                    'event_start' => $request->event_start,
                    'event_end' => $formattedEventEnd,
                ]);
    
                return response()->json($event);
                break;
    
           case 'edit':

                $check = $this->checkOverlap($request->event_start,$request->event_end,$request->id);
       
                if (!$check) {
                    return response()->json(['error' => 'Az időpont már foglalt.'], 400);
                }

                $formattedEventEnd = $this->formatEventEnd($request->event_end, $request->allDay);

                $checkAppointments = $this->AppointmentOverlap($request->event_start, $formattedEventEnd, $request->dayOfWeek, $request->weekNumber);

                if ($checkAppointments) {
                
                    return response()->json(['error' => 'Nem rendelési idő.'], 400);
                }
                     
                $event = CrudEvents::find($request->id)->update([
                  'event_name' => $request->event_name,
                  'event_start' => $request->event_start,
                  'event_end' => $request->event_end,
              ]);
              
              return response()->json($event);
              break;

           case 'exist_edit':               

                $checkAppointments = $this->AppointmentOverlap($request->event_start, $request->event_end, $request->dayOfWeek, $request->weekNumber);

                if ($checkAppointments) {
                
                    return response()->json(['error' => 'Nem rendelési idő.'], 400);
                }

                $check = $this->checkOverlap($request->event_start,$request->event_end,$request->id);
          
                if (!$check) {
                    return response()->json(['error' => 'Az időpont már foglalt.'], 400);
                }
                        
                            
                $event = CrudEvents::find($request->id)->update([
                    'event_name' => $request->event_name,
                    'event_start' => $request->event_start,
                    'event_end' => $request->event_end,
                ]);
                              
                return response()->json($event);
                break;
  
           case 'delete':
             
                $event = CrudEvents::find($request->id)->delete();

                return response()->json($event);
                break;

           default:
             # ...
             break;
        }
    }


    private function checkOverlap($event_start, $event_end, $excludeId = null) {

        $query = CrudEvents::where(function ($query) use ($event_start, $event_end) {
            $query->where(function ($query) use ($event_start, $event_end) {
                $query->whereBetween('event_start', [$event_start, $event_end])
                      ->orWhereBetween('event_end', [$event_start, $event_end]);
                    })
                    ->orWhere(function ($query) use ($event_start, $event_end) {
                        $query->where('event_start', '<=', $event_start)
                              ->where('event_end', '>=', $event_end);
            });
        });

    

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
    
        $overlap = $query->exists();
    
        return !$overlap; 
    }


    private function AppointmentOverlap($event_start, $event_end, $dayOfWeek, $weekNumber) {
            //egyszeri időpont
        $query = Appointment::where(function ($query) use ($event_start, $event_end, $dayOfWeek, $weekNumber) {
            $query->where(function ($query) use ($event_start, $event_end) {
                $query->where(function ($query) use ($event_start, $event_end) {
                    $query->where('recurrence_type', 'none')
                        ->where(function ($query) use ($event_start) {
                            $query->whereRaw('CONCAT(start_time, " ", SUBSTRING_INDEX(time_within_day, "-", 1)) < ?', [$event_start]);
                        })
                        ->where(function ($query) use ($event_end) {
                            $query->whereRaw('CONCAT(end_time, " ", SUBSTRING_INDEX(time_within_day, "-", -1)) > ?', [$event_end]);
                        });
                });
            });

             //páros páratlan hét végső dátum nélkül
            $query->orWhere(function ($query) use ($event_start, $event_end, $dayOfWeek, $weekNumber) {
                $query->whereNull('end_time')
                    ->where('recurrence_type', $weekNumber)
                    ->where('day_of_week', $dayOfWeek)
                    ->where(function ($query) use ($event_start,$event_end) {
                        $query
                        ->whereRaw('CONCAT(start_time, " ", SUBSTRING_INDEX(time_within_day, "-", 1)) < ?', [$event_start])
                        ->whereRaw('STR_TO_DATE(SUBSTRING_INDEX(time_within_day, "-", 1), "%H:%i") <= ?', [$event_start])
                        ->whereRaw('STR_TO_DATE(SUBSTRING_INDEX(time_within_day, "-", -1), "%H:%i") >= ?', [$event_end]);
                    });
                 });

                 //páros páratlan hét végső dátummal
            $query->orWhere(function ($query) use ($event_start, $event_end, $dayOfWeek, $weekNumber) {
                $query->whereNotNull('end_time')
                    ->where('recurrence_type', $weekNumber)
                    ->where('day_of_week', $dayOfWeek)
                    ->where(function ($query) use ($event_start,$event_end) {
                        $query
                        ->whereRaw('CONCAT(start_time, " ", SUBSTRING_INDEX(time_within_day, "-", 1)) < ?', [$event_start])
                        ->whereRaw('CONCAT(end_time, " ", SUBSTRING_INDEX(time_within_day, "-", -1)) > ?', [$event_end])
                        ->whereRaw('STR_TO_DATE(SUBSTRING_INDEX(time_within_day, "-", 1), "%H:%i") <= ?', [$event_start])
                        ->whereRaw('STR_TO_DATE(SUBSTRING_INDEX(time_within_day, "-", -1), "%H:%i") >= ?', [$event_end]);
                     });
                 });

                 //heti esemény végső dátummal
            $query->orWhere(function ($query) use ($event_start, $event_end, $dayOfWeek) {
                $query->whereNotNull('end_time')
                    ->where('recurrence_type', 'weekly')
                    ->where('day_of_week', $dayOfWeek)
                    ->where(function ($query) use ($event_start,$event_end) {
                        $query
                        ->whereRaw('CONCAT(start_time, " ", SUBSTRING_INDEX(time_within_day, "-", 1)) <= ?', [$event_start])
                        ->whereRaw('CONCAT(end_time, " ", SUBSTRING_INDEX(time_within_day, "-", -1)) >= ?', [$event_end])
                        ->whereRaw('STR_TO_DATE(SUBSTRING_INDEX(time_within_day, "-", 1), "%H:%i") <= ?', [$event_start])
                        ->whereRaw('STR_TO_DATE(SUBSTRING_INDEX(time_within_day, "-", -1), "%H:%i") >= ?', [$event_end]);
                     });
                 });

                  //heti esemény végső dátum nélkül
            $query->orWhere(function ($query) use ($event_start, $event_end, $dayOfWeek) {
                $query->whereNull('end_time')
                    ->where('recurrence_type', 'weekly')
                    ->where('day_of_week', $dayOfWeek)
                    ->where(function ($query) use ($event_start,$event_end) {
                        $query
                        ->whereRaw('CONCAT(start_time, " ", SUBSTRING_INDEX(time_within_day, "-", 1)) < ?', [$event_start])
                        ->whereRaw('STR_TO_DATE(SUBSTRING_INDEX(time_within_day, "-", 1), "%H:%i") <= ?', [$event_start])
                        ->whereRaw('STR_TO_DATE(SUBSTRING_INDEX(time_within_day, "-", -1), "%H:%i") >= ?', [$event_end]);
                     });
                 }); 
             });
    
            $overlap = $query->exists();
        
            return !$overlap; 
    }


        private function formatEventEnd($eventEnd, $allDay) {
            if ($allDay == "true") {
                return date("Y-m-d 23:59", strtotime($eventEnd . " -1 day"));
            } else {
                return $eventEnd;
            }
        }

}