<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', $this->listingPageProps($request));
    }

    public function visualOne(Request $request): Response
    {
        return Inertia::render('Events/VisualOne', $this->listingPageProps($request));
    }

    public function visualTwo(Request $request): Response
    {
        return Inertia::render('Events/VisualTwo', $this->listingPageProps($request));
    }

    /**
     * @return array{filters: array{status: mixed, from: mixed, to: mixed, location: mixed}, statuses: list<string>}
     */
    private function listingPageProps(Request $request): array
    {
        return [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', '2023-01-01'),
                'to' => $request->input('to'),
                'location' => $request->input('location'),
            ],
            'statuses' => ['draft', 'published', 'cancelled', 'sold_out'],
        ];
    }

    public function data(Request $request): JsonResponse
    {
        [$events, $stats] = $this->loadListing($request);

        return response()->json([
            'data' => $events->items(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
            'stats' => $stats,
        ]);
    }

    public function show(Event $event): Response
    {
        $event->load(['user', 'images']);

        $attendees = $event->attendees()
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Events/Show', [
            'event' => $event,
            'attendees' => $attendees,
        ]);
    }

    /**
     * @return array{0: LengthAwarePaginator<int, Event>, 1: array{ms: int, bytes: int}}
     */
    private function loadListing(Request $request): array
    {
        $start = microtime(true);

        $events = Event::with(['user', 'images'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->where(
                    'created_time',
                    '>=',
                    Carbon::parse($request->input('from'), 'UTC')->startOfDay()->timestamp,
                );
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->where(
                    'created_time',
                    '<=',
                    Carbon::parse($request->input('to'), 'UTC')->endOfDay()->timestamp,
                );
            })
            ->when($request->filled('location'), function ($q) use ($request) {
                $q->where('location_label', 'like', '%'.$request->input('location').'%');
            })
            ->orderByDesc('created_time')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'ms' => (int) round((microtime(true) - $start) * 1000),
            'bytes' => strlen((string) json_encode($events->items())),
        ];

        return [$events, $stats];
    }
}
