<?php


namespace StackTrace\Builder;


class Utils
{
    public static function normalizePath(string $path): string
    {
        if ($path == "" || $path == "/") {
            return "/";
        }

        return rtrim('/'.ltrim($path, '/'), '/');
    }
}
