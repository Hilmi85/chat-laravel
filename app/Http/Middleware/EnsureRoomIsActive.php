<?php

namespace App\Http\Middleware;

use App\Models\Room;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoomIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $room = $request->route('room');

        if ($room instanceof Room && $room->isExpired()) {
            $room->delete();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'This room has expired.'], 410);
            }

            return redirect()->route('home')->with('error', 'This room has expired.');
        }

        return $next($request);
    }
}
