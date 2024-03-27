<?php


namespace StackTrace\Builder\Facades;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \StackTrace\Builder\BuilderContent|null resolvePageFromRequest(Request $request)
 * @method static \StackTrace\Builder\BuilderEditor|null resolveEditorFromRequest(Request $request)
 * @method static \Illuminate\Database\Eloquent\Collection getSectionsForRequest(Request $request)
 * @method static \Illuminate\Support\Collection<int, \StackTrace\Builder\BuilderModel> getModels()
 * @method static \StackTrace\Builder\BuilderModel|null getModelByName(string $name)
 * @method static \StackTrace\Builder\BuilderModel|null getModelById(string $id)
 */
class Builder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'builder.io';
    }
}
