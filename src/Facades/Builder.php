<?php


namespace StackTrace\Builder\Facades;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \StackTrace\Builder\BuilderContent|null resolvePageFromRequest(Request $request)
 * @method static \StackTrace\Builder\BuilderEditor|null resolveEditorFromRequest(Request $request)
 * @method static \Illuminate\Database\Eloquent\Collection getSectionsForRequest(Request $request)
 * @method static \Illuminate\Support\Collection getModels()
 */
class Builder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'builder.io';
    }
}
