<?php

namespace App\Http\Middleware;

use App\Support\RecordVisibility;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EnsureRecordVisibility
{
    public function handle(Request $request, Closure $next)
    {
        foreach ($request->route()->parameters() as $record) {
            if ($record instanceof Model) {
                abort_unless(RecordVisibility::apply($record->newQuery(), $request->user())->whereKey($record->getKey())->exists(), 404);
            }
        }
        return $next($request);
    }
}
