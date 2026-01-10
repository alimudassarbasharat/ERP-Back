<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Http\Requests\Event\EventRequest;
use App\Http\Responses\Event\EventResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function index()
    {
        // TenantScope automatically filters by merchant_id
        // Additional filtering by user_id for personal events or holidays
        $events = Event::where(function($query) {
                $query->where('user_id', Auth::id())
                    ->orWhere('type', 'holiday');
            })
            ->get();
        return EventResponse::list($events);
    }

    public function store(EventRequest $request)
    {
        // TenantScope automatically sets merchant_id from authenticated user
        $event = Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'type' => $request->type,
            'color' => $request->color ?? '#409EFF',
            'user_id' => Auth::id(),
        ]);

        return EventResponse::created($event);
    }

    public function show(Event $event)
    {
        // if ($event->user_id !== Auth::id() && $event->type !== 'holiday') {
        //     return EventResponse::unauthorized();
        // }
        return EventResponse::success($event);
    }

    public function update(EventRequest $request, Event $event)
    {
        // if ($event->user_id !== Auth::id()) {
        //     return EventResponse::unauthorized();
        // }

        $event->update($request->validated());
        return EventResponse::updated($event);
    }

    public function destroy(Event $event)
    {
        // if ($event->user_id !== Auth::id()) {
        //     return EventResponse::unauthorized();
        // }

        $event->delete();
        return EventResponse::deleted();
    }

    public function getEventsByDateRange(Request $request)
    {
        $validator = validator($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return EventResponse::validationError($validator->errors());
        }

        $events = Event::where(function($query) {
                $query->where('user_id', Auth::id())
                    ->orWhere('type', 'holiday');
            })
            ->whereBetween('start_date', [$request->start_date, $request->end_date])
            ->get();

        return EventResponse::list($events);
    }
} 